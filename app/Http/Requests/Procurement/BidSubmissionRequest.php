<?php

namespace App\Http\Requests\Procurement;

use App\Domain\Procurement\Enums\BidderParticipationStatus;
use App\Domain\Procurement\Models\ProcurementRound;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BidSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->organization_id !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $routeRound = $this->route('round');
        $roundId = $routeRound instanceof ProcurementRound ? $routeRound->getKey() : null;

        return [
            'bidder_participation_id' => [
                'required',
                'integer',
                Rule::exists('bidder_participations', 'id')
                    ->where(fn (Builder $query) => $query
                        ->where('procurement_round_id', $roundId)
                        ->where('status', BidderParticipationStatus::Registered->value)),
                Rule::unique('bid_submissions', 'bidder_participation_id'),
            ],
            'receipt_reference' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('bid_submissions', 'receipt_reference')
                    ->where(fn (Builder $query) => $query->where('procurement_round_id', $roundId)),
            ],
            'package_reference' => ['nullable', 'string', 'max:100'],
            'received_at' => ['nullable', 'date'],
        ];
    }
}
