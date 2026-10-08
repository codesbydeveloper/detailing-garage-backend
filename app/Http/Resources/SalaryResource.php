<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalaryResource extends JsonResource
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
            'year' => $this->year,
            'month' => $this->month,
            'basic_salary' => $this->basic_salary,
            'extra_pay' => $this->extra_pay,
            'bonus' => $this->bonus,
            'deduction' => $this->deduction,
            'advance' => $this->advance,
            'final_payable' => $this->final_payable,
            'paid_salary' => $this->status === 'paid' ? $this->final_payable : null,
            'month_key' => sprintf('%04d-%02d', $this->year, $this->month),
            'status' => $this->status,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'notes' => $this->notes,
        ];
    }
}
