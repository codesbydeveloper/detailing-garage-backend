<?php

namespace App\Http\Requests;

class StoreOfficeExpenseRequest extends ApiFormRequest
{
    protected function prepareForValidation(): void
    {
        $this->mergeExpenseAliases();
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'expense_category_id' => ['nullable', 'exists:expense_categories,id'],
            'expense' => ['required', 'string', 'max:255'],
            'price' => $this->money(),
            'description' => ['nullable', 'string'],
        ];
    }
}
