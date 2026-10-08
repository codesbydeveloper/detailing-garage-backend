<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExtraPayResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'staff_id' => $this->staff_id,
            'staff' => $this->whenLoaded('staff', fn () => [
                'id' => $this->staff->id,
                'full_name' => $this->staff->full_name,
                'staff_code' => $this->staff->staff_code,
            ]),
            'date' => $this->date?->toDateString(),
            'amount' => $this->amount,
            'reason' => $this->reason,
            'notes' => $this->notes,
        ];
    }
}
