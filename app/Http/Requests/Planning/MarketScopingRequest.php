<?php

namespace App\Http\Requests\Planning;

use App\Domain\Planning\Enums\ProcurementCategory;
use App\Domain\Planning\Models\MarketScoping;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MarketScopingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->organization_id !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $organizationId = $this->user()?->organization_id;
        $routeMarketScoping = $this->route('market_scoping');
        $marketScopingId = $routeMarketScoping instanceof MarketScoping
            ? $routeMarketScoping->getKey()
            : null;

        return [
            'reference_no' => [
                'required',
                'string',
                'max:100',
                Rule::unique('market_scopings', 'reference_no')
                    ->where(fn (Builder $query) => $query->where('organization_id', $organizationId))
                    ->ignore($marketScopingId),
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
            'procurement_category' => ['required', Rule::enum(ProcurementCategory::class)],
            'description' => ['nullable', 'string', 'max:10000'],
            'market_findings' => ['nullable', 'string', 'max:10000'],
            'recommended_strategy' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
