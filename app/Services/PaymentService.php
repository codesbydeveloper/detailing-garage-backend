<?php

namespace App\Services;

use App\Domain\JobFinancials;
use App\Models\Job;
use App\Models\JobPayment;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private ActivityLogService $activity,
        private JobService $jobs,
    ) {}

    public function store(Job $job, array $data, User $actor): JobPayment
    {
        return DB::transaction(function () use ($job, $data, $actor) {
            $locked = Job::query()->lockForUpdate()->findOrFail($job->id);
            $this->assertFits($locked, Money::of($data['amount']), null);

            $payment = JobPayment::query()->create([
                'job_id' => $locked->id,
                'payment_date' => $data['payment_date'],
                'amount' => Money::of($data['amount']),
                'payment_method' => $data['payment_method'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            $this->jobs->syncPayments($locked, $actor);
            $this->activity->log('PAYMENT_CREATED', 'jobs', 'Job payment recorded', $payment, null, $this->activity->snapshot($payment), 'success', $actor);

            return $payment->load('job:id,job_number,amount_paid,pending_pay,payment_status');
        });
    }

    public function update(Job $job, JobPayment $payment, array $data, User $actor): JobPayment
    {
        return DB::transaction(function () use ($job, $payment, $data, $actor) {
            $this->assertPayment($job, $payment);
            $locked = Job::query()->lockForUpdate()->findOrFail($job->id);
            $payment = JobPayment::query()->lockForUpdate()->findOrFail($payment->id);
            $amount = Money::of($data['amount'] ?? $payment->amount);
            $this->assertFits($locked, $amount, $payment->id);

            $payment->fill([
                'payment_date' => $data['payment_date'] ?? $payment->payment_date,
                'amount' => $amount,
                'payment_method' => $data['payment_method'] ?? $payment->payment_method,
                'reference' => array_key_exists('reference', $data) ? $data['reference'] : $payment->reference,
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : $payment->notes,
            ]);
            $payment->save();

            $this->jobs->syncPayments($locked, $actor);
            [$old, $new] = $this->activity->changed($payment);
            $this->activity->log('PAYMENT_UPDATED', 'jobs', 'Job payment updated', $payment, $old, $new, 'success', $actor);

            return $payment->refresh()->load('job:id,job_number,amount_paid,pending_pay,payment_status');
        });
    }

    public function delete(Job $job, JobPayment $payment, User $actor): void
    {
        DB::transaction(function () use ($job, $payment, $actor) {
            $this->assertPayment($job, $payment);
            $locked = Job::query()->lockForUpdate()->findOrFail($job->id);
            $snapshot = $this->activity->snapshot($payment);
            $payment->delete();
            $this->jobs->syncPayments($locked, $actor);
            $this->activity->log('PAYMENT_DELETED', 'jobs', 'Job payment deleted', $payment, $snapshot, null, 'success', $actor);
        });
    }

    private function assertFits(Job $job, string $amount, ?int $ignorePaymentId): void
    {
        $current = Money::of(
            $job->payments()
                ->when($ignorePaymentId, fn ($query) => $query->where('id', '!=', $ignorePaymentId))
                ->sum('amount')
        );

        JobFinancials::calculate(
            $job->income,
            $job->discount,
            $job->product_charge,
            $job->labour_charge,
            Money::add($current, $amount),
        );
    }

    private function assertPayment(Job $job, JobPayment $payment): void
    {
        if ((int) $payment->job_id !== (int) $job->id) {
            throw ValidationException::withMessages([
                'payment' => ['Payment does not belong to this job.'],
            ]);
        }
    }
}
