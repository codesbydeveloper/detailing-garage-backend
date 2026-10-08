<?php

namespace App\Http\Requests;

use App\Enums\JobStatus;
use Illuminate\Validation\Rule;

class UpdateJobRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'date' => ['sometimes', 'date'],
            'delivery_date' => ['nullable', 'date'],
            'car_name' => ['sometimes', 'string', 'max:255'],
            'car_number' => ['sometimes', 'string', 'max:30'],
            'customer_name' => ['sometimes', 'string', 'max:255'],
            'customer_mobile' => ['sometimes', 'string', 'max:30'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'work_type_id' => ['sometimes', 'exists:work_types,id'],
            'income' => ['sometimes', 'numeric', 'min:0'],
            'discount' => ['sometimes', 'numeric', 'min:0'],
            'product_charge' => ['sometimes', 'numeric', 'min:0'],
            'labour_charge' => ['sometimes', 'numeric', 'min:0'],
            'job_status' => ['sometimes', Rule::in(JobStatus::values())],
            'remark' => ['nullable', 'string'],
            'lead_id' => ['nullable', 'exists:leads,id'],
        ];
    }
}
