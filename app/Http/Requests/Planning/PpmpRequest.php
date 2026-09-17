<?php

namespace App\Http\Requests\Planning;

use App\Domain\Planning\Enums\ProcurementCategory;
use App\Domain\Planning\Models\Ppmp;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PpmpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->organization_id !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $organizationId = $this->user()?->organization_id;
        $fiscalYearId = $this->integer('fiscal_year_id');
        $routePpmp = $this->route('ppmp');
        $ppmpId = $routePpmp instanceof Ppmp ? $routePpmp->getKey() : null;

        return [
            'reference_no' => [
                'required',
                'string',
                'max:100',
                Rule::unique('ppmps', 'reference_no')
                    ->where(fn (Builder $query) => $query
                        ->where('organization_id', $organizationId)
                        ->where('version', 1))
                    ->ignore($ppmpId),
            ],
            'fiscal_year_id' => [
                'required',
                'integer',
                Rule::exists('fiscal_years', 'id')
                    ->where(fn (Builder $query) => $query->where('organization_id', $organizationId)),
            ],
            'organizational_unit_id' => [
                'required',
                'integer',
                Rule::exists('organizational_units', 'id')
                    ->where(fn (Builder $query) => $query
                        ->where('organization_id', $organizationId)
                        ->where('is_active', true)),
            ],
            'title' => ['required', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1', 'max:250'],
            'items.*.item_no' => ['required', 'string', 'max:50', 'distinct'],
            'items.*.title' => ['required', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string', 'max:10000'],
            'items.*.procurement_category' => ['required', Rule::enum(ProcurementCategory::class)],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,3'],
            'items.*.unit' => ['required', 'string', 'max:50'],
            'items.*.estimated_unit_cost' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'items.*.estimated_budget' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'items.*.funding_source' => ['nullable', 'string', 'max:255'],
            'items.*.target_quarter' => ['nullable', 'integer', 'between:1,4'],
            'items.*.remarks' => ['nullable', 'string', 'max:10000'],
            'items.*.market_scoping_id' => [
                'nullable',
                'integer',
                Rule::exists('market_scopings', 'id')
                    ->where(fn (Builder $query) => $query
                        ->where('organization_id', $organizationId)
                        ->where('fiscal_year_id', $fiscalYearId)),
            ],
            'items.*.recommended_procurement_method_id' => [
                'nullable',
                'integer',
                Rule::exists('procurement_methods', 'id')
                    ->where(fn (Builder $query) => $query->where('is_active', true)),
            ],
        ];
    }
}
