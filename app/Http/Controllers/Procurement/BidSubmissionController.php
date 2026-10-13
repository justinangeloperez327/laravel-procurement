<?php

namespace App\Http\Controllers\Procurement;

use App\Domain\Procurement\Enums\BidderParticipationStatus;
use App\Domain\Procurement\Enums\BidSubmissionStatus;
use App\Domain\Procurement\Enums\ProcurementRoundStatus;
use App\Domain\Procurement\Models\BidderParticipation;
use App\Domain\Procurement\Models\BidSubmission;
use App\Domain\Procurement\Models\ProcurementProject;
use App\Domain\Procurement\Models\ProcurementRound;
use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\BidOpeningRequest;
use App\Http\Requests\Procurement\BidSubmissionRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BidSubmissionController extends Controller
{
    public function index(Request $request, ProcurementProject $project, ProcurementRound $round): Response
    {
        $this->guardRound($request, $project, $round);

        $round->load([
            'procurementMethod:id,code,name',
            'bidSubmissions.bidderParticipation:id,procurement_round_id,supplier_id,status,supplier_code_snapshot,supplier_name_snapshot,philgeps_registration_no_snapshot',
            'bidSubmissions.receiver:id,name',
            'bidSubmissions.opener:id,name',
        ]);

        $eligibleParticipations = $round->bidderParticipations()
            ->where('status', BidderParticipationStatus::Registered->value)
            ->whereDoesntHave('bidSubmission')
            ->orderBy('supplier_name_snapshot')
            ->get([
                'id',
                'supplier_id',
                'supplier_code_snapshot',
                'supplier_name_snapshot',
                'philgeps_registration_no_snapshot',
            ]);

        return Inertia::render('procurement/bids/index', [
            'project' => $project->only(['id', 'reference_no', 'title', 'status', 'current_stage']),
            'round' => $round,
            'eligibleParticipations' => $eligibleParticipations,
            'canModify' => $this->canModify($round),
        ]);
    }

    public function store(
        BidSubmissionRequest $request,
        ProcurementProject $project,
        ProcurementRound $round,
    ): RedirectResponse {
        $this->guardRound($request, $project, $round);
        $this->guardMutable($round);

        $data = $request->validated();
        $participationId = (int) $data['bidder_participation_id'];

        DB::transaction(function () use ($round, $request, $data, $participationId): void {
            $lockedRound = ProcurementRound::query()
                ->whereKey($round->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->guardMutable($lockedRound);

            $participation = BidderParticipation::query()
                ->whereKey($participationId)
                ->where('procurement_round_id', $lockedRound->id)
                ->where('status', BidderParticipationStatus::Registered->value)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedRound->bidSubmissions()->create([
                'bidder_participation_id' => $participation->id,
                'status' => BidSubmissionStatus::Received->value,
                'receipt_reference' => $data['receipt_reference'] ?? null,
                'package_reference' => $data['package_reference'] ?? null,
                'received_at' => $data['received_at'] ?? now(),
                'received_by' => $this->procurementUser($request)->id,
            ]);
        });

        return to_route('procurement.projects.rounds.bids.index', [$project, $round])
            ->with('success', 'Bid receipt recorded.');
    }

    public function open(
        BidOpeningRequest $request,
        ProcurementProject $project,
        ProcurementRound $round,
        BidSubmission $submission,
    ): RedirectResponse {
        $this->guardRound($request, $project, $round);
        $this->guardSubmission($round, $submission);
        $this->guardMutable($round);

        $data = $request->validated();

        DB::transaction(function () use ($round, $submission, $request, $data): void {
            $lockedRound = ProcurementRound::query()
                ->whereKey($round->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->guardMutable($lockedRound);

            $lockedSubmission = BidSubmission::query()
                ->whereKey($submission->id)
                ->where('procurement_round_id', $lockedRound->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedSubmission->getRawOriginal('status') !== BidSubmissionStatus::Received->value) {
                throw ValidationException::withMessages([
                    'submission' => 'A bid submission can only be opened once.',
                ]);
            }

            $openedAt = isset($data['opened_at'])
                ? Carbon::parse((string) $data['opened_at'])
                : now();

            if ($lockedSubmission->received_at !== null && $openedAt->lt($lockedSubmission->received_at)) {
                throw ValidationException::withMessages([
                    'opened_at' => 'The bid opening time cannot be earlier than the receipt time.',
                ]);
            }

            $lockedSubmission->update([
                'status' => BidSubmissionStatus::Opened->value,
                'opened_at' => $openedAt,
                'opened_by' => $this->procurementUser($request)->id,
                'bid_amount' => $data['bid_amount'] ?? null,
                'eligibility_documents_present' => (bool) $data['eligibility_documents_present'],
                'technical_documents_present' => (bool) $data['technical_documents_present'],
                'financial_documents_present' => (bool) $data['financial_documents_present'],
                'bid_security_present' => (bool) $data['bid_security_present'],
                'opening_remarks' => $data['opening_remarks'] ?? null,
            ]);

            if ($lockedRound->bid_opening_at === null) {
                $lockedRound->update(['bid_opening_at' => $openedAt]);
            }
        });

        return to_route('procurement.projects.rounds.bids.index', [$project, $round])
            ->with('success', 'Bid opening recorded successfully.');
    }

    private function guardRound(Request $request, ProcurementProject $project, ProcurementRound $round): void
    {
        $organizationId = $this->organizationId($request);

        abort_unless($project->organization_id === $organizationId, 404);
        abort_unless($round->procurement_project_id === $project->id, 404);
    }

    private function guardSubmission(ProcurementRound $round, BidSubmission $submission): void
    {
        abort_unless($submission->procurement_round_id === $round->id, 404);
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
                'bid' => 'Historical or terminal procurement rounds cannot be modified.',
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
