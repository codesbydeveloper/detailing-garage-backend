<?php

namespace App\Http\Resources;

use App\Services\SecurityPinService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $canSeeSalary = $user
            && ($user->isOwner() || $user->hasPermission('salary.view') || $user->hasPermission('salary.manage') || $user->hasPermission('staff.view'))
            && app(SecurityPinService::class)->isVerified($user);

        return [
            'id' => $this->id,
            'staff_code' => $this->staff_code,
            'user_id' => $this->user_id,
            'full_name' => $this->full_name,
            'profile_photo' => $this->profile_photo,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'joining_date' => $this->joining_date?->toDateString(),
            'emergency_contact' => $this->emergency_contact,
            'emergency_contact_number' => $this->emergency_contact_number,
            'staff_category_id' => $this->staff_category_id,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),
            'role' => $this->role,
            'department' => $this->department,
            'status' => $this->status,
            'salary_type' => $this->when($canSeeSalary, $this->salary_type),
            'basic_salary' => $this->when($canSeeSalary, $this->basic_salary),
            'base_salary' => $this->when($canSeeSalary, $this->basic_salary),
            'salary' => $this->when($canSeeSalary, $this->basic_salary),
            'user' => $this->whenLoaded('user', fn () => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'role' => $this->user->role,
                'status' => $this->user->status,
            ] : null),
        ];
    }
}
