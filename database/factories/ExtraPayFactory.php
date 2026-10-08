<?php

namespace Database\Factories;

use App\Models\ExtraPay;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExtraPay>
 */
class ExtraPayFactory extends Factory
{
    protected $model = ExtraPay::class;

    public function definition(): array
    {
        return [
            'staff_id' => Staff::factory(),
            'date' => now()->toDateString(),
            'amount' => 1500,
            'reason' => 'Overtime',
        ];
    }
}
