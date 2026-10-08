<?php

namespace Database\Factories;

use App\Models\PersonalExpense;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PersonalExpense>
 */
class PersonalExpenseFactory extends Factory
{
    protected $model = PersonalExpense::class;

    public function definition(): array
    {
        return [
            'expense_number' => 'PER-'.fake()->unique()->numerify('####'),
            'date' => now()->toDateString(),
            'expense' => 'Personal Travel',
            'price' => 1000,
            'staff_id' => Staff::factory(),
        ];
    }
}
