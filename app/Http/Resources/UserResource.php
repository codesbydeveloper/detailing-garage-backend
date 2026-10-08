<?php

namespace App\Http\Resources;

use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'staff_id' => $this->staff_id,
            'status' => $this->status,
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'permissions' => Permissions::expand($this->isOwner()
                ? Permissions::ALL
                : $this->roleRecord?->permissions->pluck('name')->all() ?? []),
            'staff' => $this->whenLoaded('staff', fn () => [
                'id' => $this->staff->id,
                'staff_code' => $this->staff->staff_code,
                'full_name' => $this->staff->full_name,
                'phone' => $this->staff->phone,
            ]),
        ];
    }
}
