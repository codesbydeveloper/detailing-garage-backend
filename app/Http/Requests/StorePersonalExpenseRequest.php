<?php

namespace App\Http\Requests;

class StorePersonalExpenseRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'expense' => ['required', 'string', 'max:255'],
            'price' => $this->money(),
            'staff_id' => ['required', 'exists:staff,id'],
            'description' => ['nullable', 'string'],
        ];
    }
}
