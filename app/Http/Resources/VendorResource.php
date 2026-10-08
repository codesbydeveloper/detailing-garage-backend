<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vendor_code' => $this->vendor_code,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'company' => $this->company,
            'address' => $this->address,
            'category' => $this->category,
            'notes' => $this->notes,
            'status' => $this->status,
            'is_active' => $this->status === 'active',
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
