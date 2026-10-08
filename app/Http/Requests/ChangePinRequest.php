<?php

namespace App\Http\Requests;

class ChangePinRequest extends ApiFormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->filled('new_pin_confirmation') && $this->filled('confirm_pin')) {
            $this->merge(['new_pin_confirmation' => $this->input('confirm_pin')]);
        }
    }

    public function rules(): array
    {
        return [
            'current_pin' => ['required', 'digits_between:4,6'],
            'new_pin' => ['required', 'digits_between:4,6', 'confirmed', 'different:current_pin'],
        ];
    }
}
