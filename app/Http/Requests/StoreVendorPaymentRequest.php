<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Validation\Rule;

class StoreVendorPaymentRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'vendor_id' => ['required', 'exists:vendors,id'],
            'date' => ['required', 'date'],
            'amount' => $this->money(),
            'purpose' => ['required', 'string', 'max:255'],
            'payment_method' => ['nullable', Rule::in(PaymentMethod::values())],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
