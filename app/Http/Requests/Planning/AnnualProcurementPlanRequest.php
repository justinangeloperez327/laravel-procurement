<?php

namespace App\Http\Requests\Planning;

use App\Domain\Planning\Enums\AnnualProcurementPlanType;
use App\Domain\Planning\Enums\PlanningStatus;
use App\Domain\Planning\Enums\ProcurementCategory;
use App\Domain\Planning\Models\AnnualProcurementPlan;
use App\Domain\Planning\Models\PpmpItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AnnualProcurementPlanRequest extends FormRequest
{
    public const BID_EVALUATION_CRITERIA = [
        'LCRB',
        'MEARB',
        'MARB',
        'HRRB',
        'SRRB',
        'LCCRB',
        'N/A',
    ];

    public const PROCUREMENT_STRATEGY_TOOLS = [
        'LCA/LCCA',
        'Subcontracting',
        'Multi-Year Contracting',
        'Design-and-Build',
        'Procurement Agent',
        'Framework Agreement',
        'Pooled Procurement',
        'Renewal of Recurring Services',
        'Warehousing and Inventory',
    ];

    public function authorize(): bool
    {
        return $this->user()?->organization_id !== null;
    }

    protected function prepareForValidation(): void
    {
        $items = $this->input('items');

        if (! is_array($items)) {
            return;
        }

        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            foreach (['schedule_start', 'schedule_end'] as $field) {
                $value = $item[$field] ?? null;

                if (is_string($value) && preg_match('/^\d{4}-\d{2}$/', $value) === 1) {
                    $items[$index][$field] = "{$value}-01";
                }
            }
        }

        $this->merge(['items' => $items]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $organizationId = $this->user()?->organization_id;
        $routePlan = $this->route('annual_procurement_plan');
        $planId = $routePlan instanceof AnnualProcurementPlan ? $routePlan->getKey() : null;

        return [
            'reference_no' => [
                'required',
                'string',
                'max:100',
                Rule::unique('annual_procurement_plans', 'reference_no')
                    ->where(fn (QueryBuilder $query) => $query
                        ->where('organization_id', $organizationId)
                        ->where('version', 1))
                    ->ignore($planId),
            ],
            'fiscal_year_id' => [
                'required',
                'integer',
                Rule::exists('fiscal_years', 'id')
                    ->where(fn (QueryBuilder $query) => $query->where('organization_id', $organizationId)),
            ],
            'app_type' => ['required', Rule::enum(AnnualProcurementPlanType::class)],
            'items' => ['required', 'array', 'min:1', 'max:500'],
            'items.*.ppmp_item_id' => ['required', 'integer', 'distinct', Rule::exists('ppmp_items', 'id')],
            'items.*.procurement_method_id' => [
                'required',
                'integer',
                Rule::exists('procurement_methods', 'id')
                    ->where(fn (QueryBuilder $query) => $query->where('is_active', true)),
            ],
            'items.*.app_item_no' => ['required', 'string', 'max:100', 'distinct'],
            'items.*.title' => ['required', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string', 'max:10000'],
            'items.*.procurement_category' => ['required', Rule::enum(ProcurementCategory::class)],
            'items.*.is_early_procurement_activity' => ['required', 'boolean'],
            'items.*.bid_evaluation_criteria' => [
                'nullable',
                'string',
                Rule::in(self::BID_EVALUATION_CRITERIA),
            ],
            'items.*.schedule_start' => ['required', 'date'],
            'items.*.schedule_end' => ['required', 'date'],
            'items.*.funding_source' => ['required', 'string', 'max:255'],
            'items.*.estimated_budget' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'items.*.procurement_strategy_tools' => ['nullable', 'array', 'max:9'],
            'items.*.procurement_strategy_tools.*' => [
                'string',
                'distinct',
                Rule::in(self::PROCUREMENT_STRATEGY_TOOLS),
            ],
            'items.*.remarks' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validatePpmpSources($validator);
            $this->validateScheduleRanges($validator);
        });
    }

    private function validatePpmpSources(Validator $validator): void
    {
        $organizationId = $this->user()?->organization_id;
        $fiscalYearId = $this->integer('fiscal_year_id');
        $type = AnnualProcurementPlanType::tryFrom((string) $this->input('app_type'));
        $items = $this->input('items', []);

        if ($organizationId === null || $fiscalYearId === 0 || $type === null || ! is_array($items)) {
            return;
        }

        $allowedStatuses = $type === AnnualProcurementPlanType::Indicative
            ? [
                PlanningStatus::Submitted->value,
                PlanningStatus::UnderReview->value,
                PlanningStatus::Approved->value,
            ]
            : [PlanningStatus::Approved->value];

        $sourceIds = collect($items)
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item): int => (int) ($item['ppmp_item_id'] ?? 0))
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($sourceIds->isEmpty()) {
            return;
        }

        $validIds = PpmpItem::query()
            ->whereIn('id', $sourceIds)
            ->whereHas('ppmp', function (Builder $query) use (
                $organizationId,
                $fiscalYearId,
                $allowedStatuses,
            ): void {
                $query
                    ->where('organization_id', $organizationId)
                    ->where('fiscal_year_id', $fiscalYearId)
                    ->whereIn('status', $allowedStatuses);
            })
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        $validLookup = array_fill_keys($validIds, true);

        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $sourceId = (int) ($item['ppmp_item_id'] ?? 0);

            if ($sourceId > 0 && ! isset($validLookup[$sourceId])) {
                $validator->errors()->add(
                    "items.{$index}.ppmp_item_id",
                    $type === AnnualProcurementPlanType::Indicative
                        ? 'The PPMP item must belong to this organization and fiscal year and come from a submitted, under-review, or approved PPMP.'
                        : 'The PPMP item must belong to this organization and fiscal year and come from an approved PPMP.',
                );
            }
        }
    }

    private function validateScheduleRanges(Validator $validator): void
    {
        $items = $this->input('items', []);

        if (! is_array($items)) {
            return;
        }

        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $start = $item['schedule_start'] ?? null;
            $end = $item['schedule_end'] ?? null;

            if (is_string($start) && is_string($end) && $end < $start) {
                $validator->errors()->add(
                    "items.{$index}.schedule_end",
                    'The procurement activity end month must be on or after the start month.',
                );
            }
        }
    }
}
