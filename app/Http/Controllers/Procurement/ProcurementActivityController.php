<?php

namespace App\Http\Controllers\Procurement;

use App\Domain\Procurement\Enums\ProcurementActivityStatus;
use App\Domain\Procurement\Enums\ProcurementActivityType;
use App\Domain\Procurement\Enums\ProcurementRoundStatus;
use App\Domain\Procurement\Models\ProcurementActivity;
use App\Domain\Procurement\Models\ProcurementProject;
use App\Domain\Procurement\Models\ProcurementRound;
use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\ProcurementActivityRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProcurementActivityController extends Controller
{
    public function index(Request $request, ProcurementProject $project, ProcurementRound $round): Response
    {
        $organizationId = $this->guardRound($request, $project, $round);

        $round->load([
            'procurementMethod:id,code,name',
            'creator:id,name',
            'activities.responsibleUser:id,name',
            'activities.creator:id,name',
        ]);

        $responsibleUsers = User::query()
            ->where('organization_id', $organizationId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('procurement/activities/index', [
            'project' => $project->only(['id', 'reference_no', 'title', 'status', 'current_stage']),
            'round' => $round,
            'activityTypes' => collect(ProcurementActivityType::cases())
                ->map(fn (ProcurementActivityType $type): array => [
                    'value' => $type->value,
                    'label' => Str::headline($type->value),
                ])
                ->values(),
            'responsibleUsers' => $responsibleUsers,
            'canModify' => $this->canModify($round),
        ]);
    }

    public function store(
        ProcurementActivityRequest $request,
        ProcurementProject $project,
        ProcurementRound $round,
    ): RedirectResponse {
        $organizationId = $this->guardRound($request, $project, $round);
        $this->guardMutable($round);

        $data = $request->validated();
        $responsibleUserId = $data['responsible_user_id'] ?? null;

        if ($responsibleUserId !== null) {
            $this->guardResponsibleUser($organizationId, (int) $responsibleUserId);
        }

        DB::transaction(function () use ($round, $request, $data, $responsibleUserId): void {
            $lockedRound = ProcurementRound::query()
                ->whereKey($round->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->guardMutable($lockedRound);

            $nextSequence = ((int) $lockedRound->activities()->max('sequence_no')) + 1;

            $lockedRound->activities()->create([
                'sequence_no' => $nextSequence,
                'activity_type' => $data['activity_type'],
                'status' => ProcurementActivityStatus::Scheduled->value,
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'responsible_user_id' => $responsibleUserId,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $this->procurementUser($request)->id,
            ]);
        });

        return to_route('procurement.projects.rounds.activities.index', [$project, $round])
            ->with('success', 'Procurement activity scheduled successfully.');
    }

    public function complete(
        Request $request,
        ProcurementProject $project,
        ProcurementRound $round,
        ProcurementActivity $activity,
    ): RedirectResponse {
        $this->guardRound($request, $project, $round);
        $this->guardActivity($round, $activity);
        $this->guardMutable($round);

        if ($activity->getRawOriginal('status') === ProcurementActivityStatus::Cancelled->value) {
            throw ValidationException::withMessages([
                'activity' => 'A cancelled procurement activity cannot be completed.',
            ]);
        }

        $data = $request->validate([
            'actual_at' => ['nullable', 'date'],
            'minutes' => ['nullable', 'string', 'max:20000'],
            'remarks' => ['nullable', 'string', 'max:5000'],
        ]);

        $activity->update([
            'status' => ProcurementActivityStatus::Completed->value,
            'actual_at' => $data['actual_at'] ?? now(),
            'minutes' => $data['minutes'] ?? $activity->minutes,
            'remarks' => $data['remarks'] ?? $activity->remarks,
            'updated_by' => $this->procurementUser($request)->id,
        ]);

        return to_route('procurement.projects.rounds.activities.index', [$project, $round])
            ->with('success', 'Procurement activity completed.');
    }

    private function guardRound(Request $request, ProcurementProject $project, ProcurementRound $round): int
    {
        $organizationId = $this->organizationId($request);

        abort_unless($project->organization_id === $organizationId, 404);
        abort_unless($round->procurement_project_id === $project->id, 404);

        return $organizationId;
    }

    private function guardActivity(ProcurementRound $round, ProcurementActivity $activity): void
    {
        abort_unless($activity->procurement_round_id === $round->id, 404);
    }

    private function guardResponsibleUser(int $organizationId, int $userId): void
    {
        $exists = User::query()
            ->whereKey($userId)
            ->where('organization_id', $organizationId)
            ->where('is_active', true)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'responsible_user_id' => 'The responsible user must be an active user in your organization.',
            ]);
        }
    }

    private function canModify(ProcurementRound $round): bool
    {
        return ! in_array($round->getRawOriginal('status'), [
            ProcurementRoundStatus::Failed->value,
            ProcurementRoundStatus::Completed->value,
            ProcurementRoundStatus::Cancelled->value,
        ], true);
    }

    private function guardMutable(ProcurementRound $round): void
    {
        if (! $this->canModify($round)) {
            throw ValidationException::withMessages([
                'activity' => 'Historical or terminal procurement rounds cannot be modified.',
            ]);
        }
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
