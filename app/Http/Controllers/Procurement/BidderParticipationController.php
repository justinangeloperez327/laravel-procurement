<?php

namespace App\Http\Controllers\Procurement;

use App\Domain\Procurement\Enums\BidderParticipationStatus;
use App\Domain\Procurement\Enums\ProcurementRoundStatus;
use App\Domain\Procurement\Models\BidderParticipation;
use App\Domain\Procurement\Models\ProcurementProject;
use App\Domain\Procurement\Models\ProcurementRound;
use App\Domain\Suppliers\Models\Supplier;
use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\BidderParticipationRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BidderParticipationController extends Controller
{
    public function index(Request $request, ProcurementProject $project, ProcurementRound $round): Response
    {
        $organizationId = $this->guardRound($request, $project, $round);

        $round->load([
            'procurementMethod:id,code,name',
            'bidderParticipations.supplier:id,supplier_code,legal_name,trade_name,philgeps_registration_no,email,phone,status',
        ]);

        $existingSupplierIds = $round->bidderParticipations
            ->pluck('supplier_id')
            ->all();

        $availableSuppliers = Supplier::query()
            ->where('organization_id', $organizationId)
            ->where('status', 'active')
            ->when($existingSupplierIds !== [], fn ($query) => $query->whereNotIn('id', $existingSupplierIds))
            ->orderBy('legal_name')
            ->get([
                'id',
                'supplier_code',
                'legal_name',
                'trade_name',
                'philgeps_registration_no',
                'email',
                'phone',
            ]);

        return Inertia::render('procurement/bidders/index', [
            'project' => $project->only(['id', 'reference_no', 'title', 'status', 'current_stage']),
            'round' => $round,
            'availableSuppliers' => $availableSuppliers,
            'canModify' => $this->canModify($round),
        ]);
    }

    public function store(
        BidderParticipationRequest $request,
        ProcurementProject $project,
        ProcurementRound $round,
    ): RedirectResponse {
        $this->guardRound($request, $project, $round);
        $this->guardMutable($round);

        $data = $request->validated();
        $supplier = Supplier::query()->findOrFail($data['supplier_id']);

        DB::transaction(function () use ($round, $supplier, $request, $data): void {
            $lockedRound = ProcurementRound::query()
                ->whereKey($round->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->guardMutable($lockedRound);

            $lockedRound->bidderParticipations()->create([
                'supplier_id' => $supplier->id,
                'status' => BidderParticipationStatus::Registered->value,
                'supplier_code_snapshot' => $supplier->supplier_code,
                'supplier_name_snapshot' => $supplier->legal_name,
                'philgeps_registration_no_snapshot' => $supplier->philgeps_registration_no,
                'representative_name' => $data['representative_name'] ?? null,
                'representative_email' => $data['representative_email'] ?? null,
                'representative_phone' => $data['representative_phone'] ?? null,
                'registered_at' => now(),
                'created_by' => $this->procurementUser($request)->id,
            ]);
        });

        return to_route('procurement.projects.rounds.bidders.index', [$project, $round])
            ->with('success', 'Bidder registered successfully.');
    }

    public function withdraw(
        Request $request,
        ProcurementProject $project,
        ProcurementRound $round,
        BidderParticipation $participation,
    ): RedirectResponse {
        $this->guardRound($request, $project, $round);
        $this->guardParticipation($round, $participation);
        $this->guardMutable($round);

        if ($participation->getRawOriginal('status') !== BidderParticipationStatus::Withdrawn->value) {
            $participation->update([
                'status' => BidderParticipationStatus::Withdrawn->value,
                'withdrawn_at' => now(),
                'updated_by' => $this->procurementUser($request)->id,
            ]);
        }

        return to_route('procurement.projects.rounds.bidders.index', [$project, $round])
            ->with('success', 'Bidder withdrawal recorded.');
    }

    private function guardRound(Request $request, ProcurementProject $project, ProcurementRound $round): int
    {
        $organizationId = $this->organizationId($request);

        abort_unless($project->organization_id === $organizationId, 404);
        abort_unless($round->procurement_project_id === $project->id, 404);

        return $organizationId;
    }

    private function guardParticipation(ProcurementRound $round, BidderParticipation $participation): void
    {
        abort_unless($participation->procurement_round_id === $round->id, 404);
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
                'bidder' => 'Historical or terminal procurement rounds cannot be modified.',
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
