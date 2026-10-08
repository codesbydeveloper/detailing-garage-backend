<?php

namespace App\Http\Requests;

use App\Enums\ExtraPayReason;
use Illuminate\Validation\Rule;

class StoreExtraPayRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'staff_id' => ['required', 'exists:staff,id'],
            'date' => ['required', 'date'],
            'amount' => $this->money(),
            'reason' => ['required', Rule::in(ExtraPayReason::values())],
            'notes' => ['nullable', 'string'],
        ];
    }
}
