<?php

namespace App\Http\Controllers\Procurement;

use App\Domain\Procurement\Enums\ProcurementRoundStatus;
use App\Domain\Procurement\Enums\ProcurementStatus;
use App\Domain\Procurement\Models\ProcurementProject;
use App\Domain\Procurement\Models\ProcurementRound;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProcurementRoundController extends Controller
{
    public function index(Request $request, ProcurementProject $project): Response
    {
        $this->guardProject($request, $project);

        $project->load([
            'procurementMethod:id,code,name',
            'rounds.procurementMethod:id,code,name',
            'rounds.creator:id,name',
        ]);

        return Inertia::render('procurement/rounds/index', [
            'project' => $project,
            'canStartRound' => $this->canStartRound($project),
        ]);
    }

    public function store(Request $request, ProcurementProject $project): RedirectResponse
    {
        $user = $this->procurementUser($request);
        $organizationId = $this->organizationId($request);

        DB::transaction(function () use ($project, $user, $organizationId): void {
            $lockedProject = ProcurementProject::query()
                ->whereKey($project->id)
                ->where('organization_id', $organizationId)
                ->lockForUpdate()
                ->firstOrFail();

            $this->guardStartableProject($lockedProject);

            $latestRound = ProcurementRound::query()
                ->where('procurement_project_id', $lockedProject->id)
                ->orderByDesc('round_no')
                ->lockForUpdate()
                ->first();

            if ($latestRound !== null && $latestRound->getRawOriginal('status') !== ProcurementRoundStatus::Failed->value) {
                throw ValidationException::withMessages([
                    'round' => 'A new procurement round can start only after the previous round is formally failed.',
                ]);
            }

            $roundNo = $latestRound === null ? 1 : $latestRound->round_no + 1;

            ProcurementRound::query()->create([
                'procurement_project_id' => $lockedProject->id,
                'procurement_method_id' => $lockedProject->procurement_method_id,
                'round_no' => $roundNo,
                'status' => ProcurementRoundStatus::Draft->value,
                'created_by' => $user->id,
            ]);

            $lockedProject->update([
                'status' => ProcurementStatus::Preparation->value,
                'current_stage' => 'pre_procurement',
            ]);
        });

        return to_route('procurement.projects.rounds.index', $project)
            ->with('success', 'Procurement round started.');
    }

    private function canStartRound(ProcurementProject $project): bool
    {
        if (! in_array($project->getRawOriginal('status'), [
            ProcurementStatus::Planned->value,
            ProcurementStatus::Preparation->value,
        ], true)) {
            return false;
        }

        $latestRound = $project->rounds->last();

        return $latestRound === null
            || $latestRound->getRawOriginal('status') === ProcurementRoundStatus::Failed->value;
    }

    private function guardStartableProject(ProcurementProject $project): void
    {
        if (! in_array($project->getRawOriginal('status'), [
            ProcurementStatus::Planned->value,
            ProcurementStatus::Preparation->value,
        ], true)) {
            throw ValidationException::withMessages([
                'round' => 'This procurement project cannot start another round in its current status.',
            ]);
        }
    }

    private function guardProject(Request $request, ProcurementProject $project): void
    {
        abort_unless($project->organization_id === $this->organizationId($request), 404);
    }

    private function organizationId(Request $request): int
    {
        $organizationId = $this->procurementUser($request)->organization_id;

        if ($organizationId === null) {
            abort(403, 'An organization assignment is required.');
        }

        return $organizationId;
    }

    private function procurementUser(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
