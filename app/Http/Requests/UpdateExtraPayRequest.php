<?php

namespace App\Http\Requests;

use App\Enums\ExtraPayReason;
use Illuminate\Validation\Rule;

class UpdateExtraPayRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'staff_id' => ['sometimes', 'exists:staff,id'],
            'date' => ['sometimes', 'date'],
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'reason' => ['sometimes', Rule::in(ExtraPayReason::values())],
            'notes' => ['nullable', 'string'],
        ];
    }
}
