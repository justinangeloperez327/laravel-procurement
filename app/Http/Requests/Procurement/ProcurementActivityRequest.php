<?php

namespace App\Http\Requests\Procurement;

use App\Domain\Procurement\Enums\ProcurementActivityStatus;
use App\Domain\Procurement\Enums\ProcurementActivityType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcurementActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'activity_type' => ['required', Rule::enum(ProcurementActivityType::class)],
            'status' => ['sometimes', Rule::enum(ProcurementActivityStatus::class)],
            'scheduled_at' => ['nullable', 'date'],
            'actual_at' => ['nullable', 'date'],
            'responsible_user_id' => ['nullable', 'integer'],
            'minutes' => ['nullable', 'string', 'max:20000'],
            'remarks' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
