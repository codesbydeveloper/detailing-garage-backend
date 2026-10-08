<?php

namespace App\Http\Requests;

use App\Enums\JobStatus;
use App\Enums\PaymentMethod;
use Illuminate\Validation\Rule;

class StoreJobRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'delivery_date' => ['nullable', 'date'],
            'car_name' => ['required', 'string', 'max:255'],
            'car_number' => ['required', 'string', 'max:30'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_mobile' => ['required', 'string', 'max:30'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'work_type_id' => ['required', 'exists:work_types,id'],
            'income' => $this->money(),
            'discount' => ['nullable', 'numeric', 'min:0'],
            'product_charge' => ['nullable', 'numeric', 'min:0'],
            'labour_charge' => ['nullable', 'numeric', 'min:0'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', Rule::in(PaymentMethod::values())],
            'job_status' => ['nullable', Rule::in(JobStatus::values())],
            'remark' => ['nullable', 'string'],
            'lead_id' => ['nullable', 'exists:leads,id'],
        ];
    }
}
