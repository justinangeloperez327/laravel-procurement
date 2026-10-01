<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class ProcurementProjectStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'app_item_id' => ['required', 'integer'],
            'reference_no' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'approved_budget' => ['required', 'numeric', 'min:0.01', 'decimal:0,2'],
            'procurement_officer_id' => ['nullable', 'integer'],
            'target_start_date' => ['nullable', 'date'],
            'target_completion_date' => ['nullable', 'date', 'after_or_equal:target_start_date'],
        ];
    }
}
