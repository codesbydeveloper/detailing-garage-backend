<?php

namespace App\Http\Requests;

class VerifyPinRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'pin' => ['required', 'digits_between:4,6'],
        ];
    }
}
