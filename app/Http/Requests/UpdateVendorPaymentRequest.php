<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Validation\Rule;

class UpdateVendorPaymentRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'vendor_id' => ['sometimes', 'exists:vendors,id'],
            'date' => ['sometimes', 'date'],
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'purpose' => ['sometimes', 'string', 'max:255'],
            'payment_method' => ['nullable', Rule::in(PaymentMethod::values())],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
