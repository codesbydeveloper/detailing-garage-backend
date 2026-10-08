<?php

namespace Database\Factories;

use App\Models\Job;
use App\Models\JobPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobPayment>
 */
class JobPaymentFactory extends Factory
{
    protected $model = JobPayment::class;

    public function definition(): array
    {
        return [
            'job_id' => Job::factory(),
            'payment_date' => now()->toDateString(),
            'amount' => 1000,
            'payment_method' => 'upi',
        ];
    }
}
