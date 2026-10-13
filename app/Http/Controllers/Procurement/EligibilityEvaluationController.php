<?php

namespace App\Http\Controllers\Procurement;

use App\Domain\Procurement\Enums\BidSubmissionStatus;
use App\Domain\Procurement\Enums\EligibilityEvaluationStatus;
use App\Domain\Procurement\Enums\ProcurementRoundStatus;
use App\Domain\Procurement\Models\BidEligibilityEvaluation;
use App\Domain\Procurement\Models\BidSubmission;
use App\Domain\Procurement\Models\ProcurementProject;
use App\Domain\Procurement\Models\ProcurementRound;
use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\EligibilityEvaluationRequest;
use App\Http\Requests\Procurement\FinalizeEligibilityEvaluationRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EligibilityEvaluationController extends Controller
{
    public function index(Request $request, ProcurementProject $project, ProcurementRound $round): Response
    {
        $this->guardRound($request, $project, $round);

        $round->load('procurementMethod:id,code,name');

        $submissions = $round->bidSubmissions()
            ->where('status', BidSubmissionStatus::Opened->value)
            ->with([
                'bidderParticipation:id,procurement_round_id,supplier_id,status,supplier_code_snapshot,supplier_name_snapshot,philgeps_registration_no_snapshot',
                'eligibilityEvaluation.items',
                'eligibilityEvaluation.evaluator:id,name',
            ])
            ->orderBy('opened_at')
            ->get();

        return Inertia::render('procurement/evaluations/eligibility', [
            'project' => $project->only(['id', 'reference_no', 'title', 'status', 'current_stage']),
            'round' => $round->only(['id', 'round_no', 'status', 'procurement_method']),
            'submissions' => $submissions,
            'canModify' => $this->canModify($round),
        ]);
    }

    public function store(
        EligibilityEvaluationRequest $request,
        ProcurementProject $project,
        ProcurementRound $round,
    ): RedirectResponse {
        $this->guardRound($request, $project, $round);
        $this->guardMutable($round);

        $data = $request->validated();
        $submissionId = (int) $data['bid_submission_id'];

        /** @var array<int, string> $criteria */
        $criteria = $data['criteria'];

        DB::transaction(function () use ($round, $request, $submissionId, $criteria): void {
            $lockedRound = ProcurementRound::query()
                ->whereKey($round->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->guardMutable($lockedRound);

            $submission = BidSubmission::query()
                ->whereKey($submissionId)
                ->where('procurement_round_id', $lockedRound->id)
                ->where('status', BidSubmissionStatus::Opened->value)
                ->lockForUpdate()
                ->firstOrFail();

            if ($submission->eligibilityEvaluation()->exists()) {
                throw ValidationException::withMessages([
                    'bid_submission_id' => 'This bid already has an eligibility evaluation.',
                ]);
            }

            $evaluation = $submission->eligibilityEvaluation()->create([
                'procurement_round_id' => $lockedRound->id,
                'status' => EligibilityEvaluationStatus::Pending->value,
                'created_by' => $this->procurementUser($request)->id,
            ]);

            foreach ($criteria as $index => $criterion) {
                $evaluation->items()->create([
                    'sequence_no' => $index + 1,
                    'criterion' => $criterion,
                ]);
            }
        });

        return to_route('procurement.projects.rounds.eligibility.index', [$project, $round])
            ->with('success', 'Eligibility evaluation checklist created.');
    }

    public function finalize(
        FinalizeEligibilityEvaluationRequest $request,
        ProcurementProject $project,
        ProcurementRound $round,
        BidEligibilityEvaluation $evaluation,
    ): RedirectResponse {
        $this->guardRound($request, $project, $round);
        $this->guardEvaluation($round, $evaluation);
        $this->guardMutable($round);

        $data = $request->validated();

        /** @var array<int, array{id: int, result: string, remarks?: string|null}> $items */
        $items = $data['items'];

        DB::transaction(function () use ($round, $evaluation, $request, $data, $items): void {
            $lockedRound = ProcurementRound::query()
                ->whereKey($round->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->guardMutable($lockedRound);

            $lockedEvaluation = BidEligibilityEvaluation::query()
                ->whereKey($evaluation->id)
                ->where('procurement_round_id', $lockedRound->id)
                ->with('items')
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedEvaluation->getRawOriginal('status') !== EligibilityEvaluationStatus::Pending->value) {
                throw ValidationException::withMessages([
                    'evaluation' => 'A finalized eligibility evaluation cannot be changed.',
                ]);
            }

            $expectedIds = $lockedEvaluation->items
                ->pluck('id')
                ->map(fn (mixed $id): int => (int) $id)
                ->sort()
                ->values()
                ->all();

            $providedIds = [];
            foreach ($items as $item) {
                $providedIds[] = (int) $item['id'];
            }
            sort($providedIds);

            if ($expectedIds !== $providedIds) {
                throw ValidationException::withMessages([
                    'items' => 'Every checklist criterion must be evaluated exactly once.',
                ]);
            }

            foreach ($items as $item) {
                $lockedEvaluation->items()
                    ->whereKey((int) $item['id'])
                    ->firstOrFail()
                    ->update([
                        'result' => $item['result'],
                        'remarks' => $item['remarks'] ?? null,
                    ]);
            }

            $lockedEvaluation->update([
                'status' => $data['status'],
                'evaluated_at' => now(),
                'evaluated_by' => $this->procurementUser($request)->id,
                'decision_remarks' => $data['decision_remarks'] ?? null,
            ]);
        });

        return to_route('procurement.projects.rounds.eligibility.index', [$project, $round])
            ->with('success', 'Eligibility evaluation finalized.');
    }

    private function guardRound(Request $request, ProcurementProject $project, ProcurementRound $round): void
    {
        $organizationId = $this->organizationId($request);

        abort_unless($project->organization_id === $organizationId, 404);
        abort_unless($round->procurement_project_id === $project->id, 404);
    }

    private function guardEvaluation(ProcurementRound $round, BidEligibilityEvaluation $evaluation): void
    {
        abort_unless($evaluation->procurement_round_id === $round->id, 404);
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
                'evaluation' => 'Historical or terminal procurement rounds cannot be modified.',
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
