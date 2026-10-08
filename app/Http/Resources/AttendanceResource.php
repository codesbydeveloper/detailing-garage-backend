<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
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
            'clock_in' => $this->clock_in?->toIso8601String(),
            'clock_out' => $this->clock_out?->toIso8601String(),
            'total_minutes' => $this->total_minutes,
            'total_hours' => $this->total_minutes === null ? null : round(((int) $this->total_minutes) / 60, 2),
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }
}
