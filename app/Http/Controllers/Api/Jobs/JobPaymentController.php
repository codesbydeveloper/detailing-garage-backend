<?php

namespace App\Http\Controllers\Api\Jobs;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreJobPaymentRequest;
use App\Http\Requests\UpdateJobPaymentRequest;
use App\Http\Resources\JobPaymentResource;
use App\Models\Job;
use App\Models\JobPayment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobPaymentController extends ApiController
{
    public function index(Job $job): JsonResponse
    {
        $payments = $job->payments()->latest('payment_date')->get();

        return $this->success(JobPaymentResource::collection($payments)->resolve(request()), 'Payments loaded');
    }

    public function store(StoreJobPaymentRequest $request, Job $job, PaymentService $payments): JsonResponse
    {
        $payment = $payments->store($job, $request->validated(), $request->user());

        return $this->success(new JobPaymentResource($payment), 'Payment recorded', 201);
    }

    public function update(UpdateJobPaymentRequest $request, Job $job, JobPayment $payment, PaymentService $payments): JsonResponse
    {
        $payment = $payments->update($job, $payment, $request->validated(), $request->user());

        return $this->success(new JobPaymentResource($payment), 'Payment updated');
    }

    public function destroy(Request $request, Job $job, JobPayment $payment, PaymentService $payments): JsonResponse
    {
        $payments->delete($job, $payment, $request->user());

        return $this->success(null, 'Payment deleted');
    }
}
