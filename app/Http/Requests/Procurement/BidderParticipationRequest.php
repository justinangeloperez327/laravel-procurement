<?php

namespace App\Http\Requests\Procurement;

use App\Domain\Procurement\Models\ProcurementRound;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BidderParticipationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->organization_id !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $organizationId = $this->user()?->organization_id;
        $routeRound = $this->route('round');
        $roundId = $routeRound instanceof ProcurementRound ? $routeRound->getKey() : null;

        return [
            'supplier_id' => [
                'required',
                'integer',
                Rule::exists('suppliers', 'id')
                    ->where(fn (Builder $query) => $query
                        ->where('organization_id', $organizationId)
                        ->where('status', 'active')),
                Rule::unique('bidder_participations', 'supplier_id')
                    ->where(fn (Builder $query) => $query->where('procurement_round_id', $roundId)),
            ],
            'representative_name' => ['nullable', 'string', 'max:255'],
            'representative_email' => ['nullable', 'email', 'max:255'],
            'representative_phone' => ['nullable', 'string', 'max:50'],
        ];
    }
}
