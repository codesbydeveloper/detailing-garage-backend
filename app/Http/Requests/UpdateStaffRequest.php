<?php

namespace App\Http\Requests;

use App\Enums\SalaryType;
use App\Enums\StaffStatus;
use Illuminate\Validation\Rule;

class UpdateStaffRequest extends ApiFormRequest
{
    public function rules(): array
    {
        $staffId = $this->route('staff')?->id;

        return [
            'full_name' => ['sometimes', 'string', 'max:255'],
            'profile_photo' => ['nullable', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('staff', 'email')->ignore($staffId)],
            'address' => ['nullable', 'string'],
            'date_of_birth' => ['nullable', 'date'],
            'joining_date' => ['sometimes', 'date'],
            'emergency_contact' => ['nullable', 'string', 'max:255'],
            'emergency_contact_number' => ['nullable', 'string', 'max:30'],
            'staff_category_id' => ['sometimes', 'exists:staff_categories,id'],
            'role' => ['sometimes', 'string', 'max:50'],
            'department' => ['nullable', 'string', 'max:255'],
            'salary_type' => ['sometimes', Rule::in(SalaryType::values())],
            'basic_salary' => ['sometimes', 'numeric', 'min:0'],
            'status' => ['sometimes', Rule::in(StaffStatus::values())],
        ];
    }
}
