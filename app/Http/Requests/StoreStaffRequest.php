<?php

namespace App\Http\Requests;

use App\Enums\SalaryType;
use App\Enums\StaffStatus;
use App\Enums\UserRole;
use Illuminate\Validation\Rule;

class StoreStaffRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'profile_photo' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255', 'unique:staff,email'],
            'address' => ['nullable', 'string'],
            'date_of_birth' => ['nullable', 'date'],
            'joining_date' => ['required', 'date'],
            'emergency_contact' => ['nullable', 'string', 'max:255'],
            'emergency_contact_number' => ['nullable', 'string', 'max:30'],
            'staff_category_id' => ['required', 'exists:staff_categories,id'],
            'role' => ['required', 'string', 'max:50'],
            'department' => ['nullable', 'string', 'max:255'],
            'salary_type' => ['required', Rule::in(SalaryType::values())],
            'basic_salary' => $this->money(),
            'status' => ['required', Rule::in(StaffStatus::values())],
            'user_id' => ['nullable', 'exists:users,id'],
            'create_login' => ['nullable', 'boolean'],
            'login_email' => ['required_if:create_login,true,1', 'nullable', 'email', 'unique:users,email'],
            'login_password' => ['required_if:create_login,true,1', 'nullable', 'string', 'min:8'],
            'login_role' => ['required_if:create_login,true,1', 'nullable', Rule::in(UserRole::values())],
        ];
    }
}
