<?php

namespace App\Http\Requests\Procurement;

use App\Domain\Procurement\Enums\BidSubmissionStatus;
use App\Domain\Procurement\Models\ProcurementRound;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EligibilityEvaluationRequest extends FormRequest
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
            'bid_submission_id' => [
                'required',
                'integer',
                Rule::exists('bid_submissions', 'id')
                    ->where(fn (Builder $query) => $query
                        ->where('procurement_round_id', $roundId)
                        ->where('status', BidSubmissionStatus::Opened->value)),
                Rule::unique('bid_eligibility_evaluations', 'bid_submission_id'),
            ],
            'criteria' => ['required', 'array', 'min:1', 'max:50'],
            'criteria.*' => ['required', 'string', 'max:500', 'distinct'],
        ];
    }
}
