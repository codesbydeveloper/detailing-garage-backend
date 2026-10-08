<?php

namespace App\Http\Requests;

class UpdateOfficeExpenseRequest extends ApiFormRequest
{
    protected function prepareForValidation(): void
    {
        $this->mergeExpenseAliases();
    }

    public function rules(): array
    {
        return [
            'date' => ['sometimes', 'date'],
            'expense_category_id' => ['nullable', 'exists:expense_categories,id'],
            'expense' => ['sometimes', 'string', 'max:255'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ];
    }
}
