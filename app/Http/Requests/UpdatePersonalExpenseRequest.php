<?php

namespace App\Http\Requests;

class UpdatePersonalExpenseRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'date' => ['sometimes', 'date'],
            'expense' => ['sometimes', 'string', 'max:255'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'staff_id' => ['sometimes', 'exists:staff,id'],
            'description' => ['nullable', 'string'],
        ];
    }
}
