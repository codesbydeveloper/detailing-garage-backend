<?php

namespace App\Http\Requests;

class ClockInRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string'],
        ];
    }
}
