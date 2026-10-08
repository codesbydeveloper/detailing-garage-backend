<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'job_id' => $this->job_id,
            'payment_date' => $this->payment_date?->toDateString(),
            'amount' => $this->amount,
            'payment_method' => $this->payment_method,
            'reference' => $this->reference,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'job' => $this->whenLoaded('job', fn () => [
                'id' => $this->job->id,
                'job_number' => $this->job->job_number,
                'amount_paid' => $this->job->amount_paid,
                'pending_pay' => $this->job->pending_pay,
                'payment_status' => $this->job->payment_status,
            ]),
        ];
    }
}
