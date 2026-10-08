<?php

namespace App\Http\Requests;

use App\Enums\JobStatus;
use App\Enums\PaymentMethod;
use Illuminate\Validation\Rule;

class ConvertLeadRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'car_number' => ['required', 'string', 'max:30'],
            'car_name' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'date'],
            'delivery_date' => ['nullable', 'date'],
            'work_type_id' => ['nullable', 'exists:work_types,id'],
            'income' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'product_charge' => ['nullable', 'numeric', 'min:0'],
            'labour_charge' => ['nullable', 'numeric', 'min:0'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', Rule::in(PaymentMethod::values())],
            'job_status' => ['nullable', Rule::in(JobStatus::values())],
            'remark' => ['nullable', 'string'],
        ];
    }
}
