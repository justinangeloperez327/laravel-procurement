<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class BidOpeningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->organization_id !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'opened_at' => ['nullable', 'date'],
            'bid_amount' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'eligibility_documents_present' => ['required', 'boolean'],
            'technical_documents_present' => ['required', 'boolean'],
            'financial_documents_present' => ['required', 'boolean'],
            'bid_security_present' => ['required', 'boolean'],
            'opening_remarks' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
