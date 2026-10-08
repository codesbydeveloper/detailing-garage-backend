<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Validation\Rule;

class StoreJobPaymentRequest extends ApiFormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'payment_date' => $this->input('payment_date') ?: now()->toDateString(),
            'payment_method' => $this->input('payment_method') ?: 'cash',
        ]);
    }

    public function rules(): array
    {
        return [
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', Rule::in(PaymentMethod::values())],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
