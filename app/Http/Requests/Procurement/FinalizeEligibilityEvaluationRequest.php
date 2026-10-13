<?php

namespace App\Http\Requests\Procurement;

use App\Domain\Procurement\Enums\EligibilityCriterionResult;
use App\Domain\Procurement\Enums\EligibilityEvaluationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FinalizeEligibilityEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->organization_id !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in([
                    EligibilityEvaluationStatus::Eligible->value,
                    EligibilityEvaluationStatus::Ineligible->value,
                ]),
            ],
            'decision_remarks' => ['nullable', 'string', 'max:10000', 'required_if:status,ineligible'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.id' => ['required', 'integer', 'distinct'],
            'items.*.result' => [
                'required',
                Rule::in([
                    EligibilityCriterionResult::Compliant->value,
                    EligibilityCriterionResult::NonCompliant->value,
                    EligibilityCriterionResult::NotApplicable->value,
                ]),
            ],
            'items.*.remarks' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
