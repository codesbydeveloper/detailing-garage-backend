<?php

namespace Database\Factories;

use App\Models\SalaryRecord;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalaryRecord>
 */
class SalaryFactory extends Factory
{
    protected $model = SalaryRecord::class;

    public function definition(): array
    {
        return [
            'staff_id' => Staff::factory(),
            'year' => now()->year,
            'month' => now()->month,
            'basic_salary' => 20000,
            'extra_pay' => 0,
            'bonus' => 0,
            'deduction' => 0,
            'advance' => 0,
            'final_payable' => 20000,
            'status' => 'generated',
        ];
    }
}
