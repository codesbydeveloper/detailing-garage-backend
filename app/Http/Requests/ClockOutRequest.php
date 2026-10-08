<?php

namespace App\Http\Requests;

class ClockOutRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string'],
        ];
    }
}
