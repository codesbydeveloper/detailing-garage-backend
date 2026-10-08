<?php

namespace Database\Factories;

use App\Models\Job;
use App\Models\WorkType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Job>
 */
class JobFactory extends Factory
{
    protected $model = Job::class;

    public function definition(): array
    {
        $workType = WorkType::query()->firstOrCreate(
            ['name' => 'Ceramic Coating'],
            ['status' => 'active', 'default_price' => 15000],
        );

        return [
            'job_number' => 'JOB-'.now()->format('Ym').'-'.fake()->unique()->numerify('####'),
            'date' => now()->toDateString(),
            'car_name' => 'Hyundai Creta White',
            'car_number' => 'GJ01AB'.fake()->numerify('####'),
            'customer_name' => fake()->name(),
            'customer_mobile' => fake()->numerify('98765#####'),
            'work_type_id' => $workType->id,
            'income' => 15000,
            'discount' => 1000,
            'product_charge' => 3500,
            'labour_charge' => 2500,
            'final_amount' => 14000,
            'amount_paid' => 0,
            'pending_pay' => 14000,
            'profit' => 8000,
            'payment_status' => 'pending',
            'job_status' => 'booked',
        ];
    }
}
