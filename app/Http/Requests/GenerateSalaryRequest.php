<?php

namespace App\Http\Requests;

use App\Support\Period;

class GenerateSalaryRequest extends ApiFormRequest
{
    protected function prepareForValidation(): void
    {
        Period::normalize($this);
    }

    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ];
    }
}
