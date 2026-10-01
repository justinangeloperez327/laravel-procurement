<?php

namespace App\Http\Controllers\Planning;

use App\Domain\Organization\Models\FiscalYear;
use App\Domain\Planning\Enums\AnnualProcurementPlanType;
use App\Domain\Planning\Enums\PlanningStatus;
use App\Domain\Planning\Models\AnnualProcurementPlan;
use App\Domain\Planning\Models\PpmpItem;
use App\Domain\Planning\Services\PlanningWorkflowService;
use App\Domain\Procurement\Models\ProcurementMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Planning\AnnualProcurementPlanRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AnnualProcurementPlanController extends Controller
{
    public function index(Request $request, PlanningWorkflowService $workflow): Response
    {
        $user = $this->procurementUser($request);
        $organizationId = $this->organizationId($request);

        $plans = AnnualProcurementPlan::query()
            ->where('organization_id', $organizationId)
            ->with('fiscalYear:id,year')
            ->withCount('items')
            ->withSum('items', 'estimated_budget')
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        $plans->through(function (AnnualProcurementPlan $plan) use ($workflow, $user): AnnualProcurementPlan {
            $plan->setAttribute('available_actions', $workflow->availableActions($user, $plan));

            return $plan;
        });

        return Inertia::render('planning/annual-procurement-plans/index', [
            'plans' => $plans,
        ]);
    }

    public function create(Request $request): Response
    {
        $organizationId = $this->organizationId($request);

        return Inertia::render(
            'planning/annual-procurement-plans/create',
            $this->formOptions($organizationId),
        );
    }

    public function store(AnnualProcurementPlanRequest $request): RedirectResponse
    {
        $user = $this->procurementUser($request);
        $organizationId = $this->organizationId($request);
        $validated = $request->validated();
        /** @var array<int, array<string, mixed>> $items */
        $items = $validated['items'];
        unset($validated['items']);

        DB::transaction(function () use ($validated, $items, $user, $organizationId): void {
            $plan = AnnualProcurementPlan::query()->create([
                ...$validated,
                'organization_id' => $organizationId,
                'version' => 1,
                'status' => PlanningStatus::Draft->value,
                'prepared_by' => $user->id,
            ]);

            foreach ($items as $item) {
                $plan->items()->create($this->normalizeItem($item));
            }
        });

        return to_route('planning.annual-procurement-plans.index')
            ->with('success', 'Annual Procurement Plan saved as draft.');
    }

    public function edit(Request $request, AnnualProcurementPlan $annualProcurementPlan): Response
    {
        $organizationId = $this->organizationId($request);
        $this->guardEditableRecord($annualProcurementPlan, $organizationId);
        $annualProcurementPlan->load('items');

        return Inertia::render('planning/annual-procurement-plans/edit', [
            ...$this->formOptions($organizationId),
            'plan' => $annualProcurementPlan,
        ]);
    }

    public function update(
        AnnualProcurementPlanRequest $request,
        AnnualProcurementPlan $annualProcurementPlan,
    ): RedirectResponse {
        $organizationId = $this->organizationId($request);
        $this->guardEditableRecord($annualProcurementPlan, $organizationId);
        $validated = $request->validated();
        /** @var array<int, array<string, mixed>> $items */
        $items = $validated['items'];
        unset($validated['items']);

        DB::transaction(function () use ($annualProcurementPlan, $validated, $items): void {
            $annualProcurementPlan->update($validated);
            $annualProcurementPlan->items()->delete();

            foreach ($items as $item) {
                $annualProcurementPlan->items()->create($this->normalizeItem($item));
            }
        });

        return to_route('planning.annual-procurement-plans.index')
            ->with('success', 'Annual Procurement Plan updated.');
    }

    /**
     * @return array{
     *     fiscalYears: Collection<int, FiscalYear>,
     *     ppmpItems: Collection<int, PpmpItem>,
     *     procurementMethods: Collection<int, ProcurementMethod>,
     *     appTypes: array<int, array{value: string, label: string}>,
     *     bidEvaluationCriteria: array<int, string>,
     *     procurementStrategyTools: array<int, string>
     * }
     */
    private function formOptions(int $organizationId): array
    {
        return [
            'fiscalYears' => FiscalYear::query()
                ->where('organization_id', $organizationId)
                ->orderByDesc('year')
                ->get(['id', 'year']),
            'ppmpItems' => PpmpItem::query()
                ->whereHas('ppmp', fn ($query) => $query
                    ->where('organization_id', $organizationId)
                    ->whereIn('status', [
                        PlanningStatus::Submitted->value,
                        PlanningStatus::UnderReview->value,
                        PlanningStatus::Approved->value,
                    ]))
                ->with([
                    'ppmp:id,organization_id,fiscal_year_id,organizational_unit_id,reference_no,status',
                    'ppmp.organizationalUnit:id,code,name',
                    'recommendedProcurementMethod:id,code,name',
                ])
                ->orderBy('ppmp_id')
                ->orderBy('item_no')
                ->get([
                    'id',
                    'ppmp_id',
                    'recommended_procurement_method_id',
                    'item_no',
                    'title',
                    'description',
                    'procurement_category',
                    'estimated_budget',
                    'funding_source',
                    'target_quarter',
                ]),
            'procurementMethods' => ProcurementMethod::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
            'appTypes' => array_map(
                static fn (AnnualProcurementPlanType $type): array => [
                    'value' => $type->value,
                    'label' => match ($type) {
                        AnnualProcurementPlanType::Indicative => 'Indicative APP',
                        AnnualProcurementPlanType::Final => 'Final APP',
                        AnnualProcurementPlanType::Updated => 'Updated APP',
                    },
                ],
                AnnualProcurementPlanType::cases(),
            ),
            'bidEvaluationCriteria' => AnnualProcurementPlanRequest::BID_EVALUATION_CRITERIA,
            'procurementStrategyTools' => AnnualProcurementPlanRequest::PROCUREMENT_STRATEGY_TOOLS,
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function normalizeItem(array $item): array
    {
        $item['status'] = 'planned';

        return $item;
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

        if (! $user instanceof User || $user->organization_id === null) {
            abort(403, 'An organization assignment is required.');
        }

        return $user;
    }

    private function guardEditableRecord(
        AnnualProcurementPlan $annualProcurementPlan,
        int $organizationId,
    ): void {
        abort_unless($annualProcurementPlan->organization_id === $organizationId, 404);
        abort_unless(
            in_array($annualProcurementPlan->getRawOriginal('status'), [
                PlanningStatus::Draft->value,
                PlanningStatus::Returned->value,
            ], true),
            409,
            'Only draft or returned Annual Procurement Plans can be edited.',
        );
    }
}
