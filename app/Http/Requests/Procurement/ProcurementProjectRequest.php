<?php

namespace App\Http\Requests\Procurement;

use App\Domain\Planning\Enums\PlanningStatus;
use App\Domain\Planning\Models\AppItem;
use App\Domain\Procurement\Models\ProcurementProject;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProcurementProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->organization_id !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $organizationId = $this->user()?->organization_id;
        $routeProject = $this->route('procurementProject');
        $projectId = $routeProject instanceof ProcurementProject ? $routeProject->getKey() : null;

        return [
            'reference_no' => [
                'required',
                'string',
                'max:100',
                Rule::unique('procurement_projects', 'reference_no')
                    ->where(fn (Builder $query) => $query->where('organization_id', $organizationId))
                    ->ignore($projectId),
            ],
            'app_item_id' => ['required', 'integer', 'exists:app_items,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'approved_budget' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'procurement_officer_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(
                    fn (Builder $query) => $query
                        ->where('organization_id', $organizationId)
                        ->where('is_active', true),
                ),
            ],
            'target_start_date' => ['nullable', 'date'],
            'target_completion_date' => ['nullable', 'date', 'after_or_equal:target_start_date'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('app_item_id') || $validator->errors()->has('approved_budget')) {
                return;
            }

            $organizationId = $this->user()?->organization_id;
            $appItem = AppItem::query()
                ->with('annualProcurementPlan')
                ->find($this->integer('app_item_id'));

            if ($appItem === null || $appItem->annualProcurementPlan->organization_id !== $organizationId) {
                $validator->errors()->add('app_item_id', 'The selected APP item is not available to this organization.');

                return;
            }

            if ($appItem->annualProcurementPlan->getRawOriginal('status') !== PlanningStatus::Approved->value) {
                $validator->errors()->add('app_item_id', 'Procurement can only start from an approved APP.');

                return;
            }

            $routeProject = $this->route('procurementProject');
            $excludeId = $routeProject instanceof ProcurementProject ? $routeProject->getKey() : null;
            $committed = ProcurementProject::query()
                ->where('app_item_id', $appItem->id)
                ->when($excludeId !== null, fn ($query) => $query->whereKeyNot($excludeId))
                ->where('status', '!=', 'cancelled')
                ->sum('approved_budget');
            $available = (float) $appItem->estimated_budget - (float) $committed;

            if ((float) $this->input('approved_budget') > $available + 0.00001) {
                $validator->errors()->add(
                    'approved_budget',
                    'The approved budget exceeds the remaining amount available on the APP item.',
                );
            }
        }];
    }
}
