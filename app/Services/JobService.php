<?php

namespace App\Services;

use App\Domain\JobFinancials;
use App\Models\Job;
use App\Models\JobPayment;
use App\Models\User;
use App\Support\Money;
use App\Support\Query;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class JobService
{
    public function __construct(
        private ActivityLogService $activity,
        private NumberGenerator $numbers,
    ) {}

    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Job::query()->with(['workType:id,name', 'lead:id,full_name']);
        $query->search($filters['search'] ?? null);

        if (! empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (! empty($filters['job_status'])) {
            $query->where('job_status', $filters['job_status']);
        }

        if (! empty($filters['work_type_id'])) {
            $query->where('work_type_id', $filters['work_type_id']);
        }

        Query::whereDateRange($query, 'date', $filters['date_from'] ?? null, $filters['date_to'] ?? null);
        Query::sort(
            $query,
            $filters['sort'] ?? null,
            $filters['direction'] ?? null,
            ['id', 'job_number', 'date', 'delivery_date', 'customer_name', 'final_amount', 'payment_status', 'job_status', 'created_at'],
            'date',
        );

        return $query->paginate(Query::perPage($filters['per_page'] ?? null))->withQueryString();
    }

    public function create(array $data, User $actor): Job
    {
        return DB::transaction(function () use ($data, $actor) {
            $paid = Money::of($data['amount_paid'] ?? 0);
            $financials = JobFinancials::calculate(
                $data['income'] ?? 0,
                $data['discount'] ?? 0,
                $data['product_charge'] ?? 0,
                $data['labour_charge'] ?? 0,
                $paid,
            );

            $job = Job::query()->create([
                'job_number' => $this->numbers->monthly(Job::class, 'job_number', 'JOB-'),
                'date' => $data['date'],
                'delivery_date' => $data['delivery_date'] ?? null,
                'car_name' => $data['car_name'],
                'car_number' => $data['car_number'],
                'customer_name' => $data['customer_name'],
                'customer_mobile' => $data['customer_mobile'],
                'customer_email' => $data['customer_email'] ?? null,
                'work_type_id' => $data['work_type_id'],
                'job_status' => $data['job_status'] ?? 'booked',
                'remark' => $data['remark'] ?? null,
                'lead_id' => $data['lead_id'] ?? null,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
                ...$financials,
            ]);

            if (Money::isPositive($paid)) {
                $payment = JobPayment::query()->create([
                    'job_id' => $job->id,
                    'payment_date' => $data['date'],
                    'amount' => $paid,
                    'payment_method' => $data['payment_method'] ?? 'cash',
                    'reference' => $data['payment_reference'] ?? null,
                    'notes' => 'Initial payment',
                    'created_by' => $actor->id,
                ]);
                $this->activity->log('PAYMENT_CREATED', 'jobs', 'Initial job payment recorded', $payment, null, $this->activity->snapshot($payment), 'success', $actor);
            }

            $job->load('workType');
            $this->activity->log('CREATE', 'jobs', 'Job created', $job, null, $this->activity->snapshot($job), 'success', $actor);

            return $job;
        });
    }

    public function update(Job $job, array $data, User $actor): Job
    {
        return DB::transaction(function () use ($job, $data, $actor) {
            $locked = Job::query()->lockForUpdate()->findOrFail($job->id);
            $originalStatus = $locked->job_status;
            $paid = Money::of($locked->payments()->sum('amount'));

            $financials = JobFinancials::calculate(
                $data['income'] ?? $locked->income,
                $data['discount'] ?? $locked->discount,
                $data['product_charge'] ?? $locked->product_charge,
                $data['labour_charge'] ?? $locked->labour_charge,
                $paid,
            );

            $locked->fill([
                'date' => $data['date'] ?? $locked->date,
                'delivery_date' => array_key_exists('delivery_date', $data) ? $data['delivery_date'] : $locked->delivery_date,
                'car_name' => $data['car_name'] ?? $locked->car_name,
                'car_number' => $data['car_number'] ?? $locked->car_number,
                'customer_name' => $data['customer_name'] ?? $locked->customer_name,
                'customer_mobile' => $data['customer_mobile'] ?? $locked->customer_mobile,
                'customer_email' => array_key_exists('customer_email', $data) ? $data['customer_email'] : $locked->customer_email,
                'work_type_id' => $data['work_type_id'] ?? $locked->work_type_id,
                'job_status' => $data['job_status'] ?? $locked->job_status,
                'remark' => array_key_exists('remark', $data) ? $data['remark'] : $locked->remark,
                'lead_id' => array_key_exists('lead_id', $data) ? $data['lead_id'] : $locked->lead_id,
                'updated_by' => $actor->id,
                ...$financials,
            ]);
            $locked->save();

            [$old, $new] = $this->activity->changed($locked);
            $statusChanged = $originalStatus !== $locked->job_status;

            if ($old && ! ($statusChanged && count((array) $old) === 1 && array_key_exists('job_status', (array) $old))) {
                $this->activity->log('UPDATE', 'jobs', 'Job updated', $locked, $old, $new, 'success', $actor);
            }

            if ($statusChanged) {
                $this->activity->log('STATUS_CHANGED', 'jobs', 'Job status changed', $locked, [
                    'job_status' => $originalStatus,
                ], [
                    'job_status' => $locked->job_status,
                ], 'success', $actor);
            }

            return $locked->load('workType');
        });
    }

    public function delete(Job $job, User $actor): void
    {
        $snapshot = $this->activity->snapshot($job);
        $job->delete();
        $this->activity->log('DELETE', 'jobs', 'Job deleted', $job, $snapshot, null, 'success', $actor);
    }

    public function syncPayments(Job $job, User $actor): Job
    {
        $locked = Job::query()->lockForUpdate()->findOrFail($job->id);
        $paid = Money::of($locked->payments()->sum('amount'));
        $financials = JobFinancials::calculate(
            $locked->income,
            $locked->discount,
            $locked->product_charge,
            $locked->labour_charge,
            $paid,
        );

        $locked->fill($financials);
        $locked->updated_by = $actor->id;
        $locked->save();

        return $locked;
    }
}
