<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'job_number' => $this->job_number,
            'date' => $this->date?->toDateString(),
            'delivery_date' => $this->delivery_date?->toDateString(),
            'car_name' => $this->car_name,
            'car_number' => $this->car_number,
            'customer_name' => $this->customer_name,
            'customer_mobile' => $this->customer_mobile,
            'customer_email' => $this->customer_email,
            'work_type_id' => $this->work_type_id,
            'work_type' => $this->whenLoaded('workType', fn () => [
                'id' => $this->workType->id,
                'name' => $this->workType->name,
            ]),
            'income' => $this->income,
            'discount' => $this->discount,
            'product_charge' => $this->product_charge,
            'labour_charge' => $this->labour_charge,
            'final_amount' => $this->final_amount,
            'amount_paid' => $this->amount_paid,
            'pending_pay' => $this->pending_pay,
            'profit' => $this->profit,
            'payment_status' => $this->payment_status,
            'job_status' => $this->job_status,
            'remark' => $this->remark,
            'lead_id' => $this->lead_id,
            'payments' => $this->whenLoaded('payments', fn () => JobPaymentResource::collection($this->payments)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
