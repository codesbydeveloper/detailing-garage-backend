<?php

namespace Database\Seeders;

use App\Domain\JobFinancials;
use App\Models\Job;
use App\Models\JobPayment;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\Seeder;

class JobPaymentSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->where('email', 'owner@detailinggarage.test')->firstOrFail();
        $methods = ['cash', 'upi', 'card', 'bank_transfer', 'other'];
        $jobs = Job::query()->orderBy('id')->get();

        foreach ($jobs as $index => $job) {
            $mode = $index % 3;
            $final = Money::of($job->final_amount);
            $parts = [];

            if ($mode === 0 && Money::isPositive($final)) {
                $first = bcmul($final, '0.40', 2);
                $second = bcmul($final, '0.40', 2);
                $parts = [$first, $second, Money::sub(Money::sub($final, $first), $second)];
            } elseif ($mode === 1 && Money::isPositive($final)) {
                $parts = [bcmul($final, '0.50', 2)];
            }

            foreach ($parts as $partIndex => $amount) {
                if (! Money::isPositive($amount)) {
                    continue;
                }

                JobPayment::query()->updateOrCreate(
                    ['reference' => $job->job_number.'-P'.($partIndex + 1)],
                    [
                        'job_id' => $job->id,
                        'payment_date' => $job->date?->toDateString() ?? now()->toDateString(),
                        'amount' => $amount,
                        'payment_method' => $methods[($index + $partIndex) % count($methods)],
                        'notes' => 'Seeded payment',
                        'created_by' => $owner->id,
                    ],
                );
            }

            $paid = Money::of($job->payments()->sum('amount'));
            $financials = JobFinancials::calculate($job->income, $job->discount, $job->product_charge, $job->labour_charge, $paid);
            $job->update($financials);
        }
    }
}
