<?php

namespace Database\Factories;

use App\Models\ExpenseCategory;
use App\Models\OfficeExpense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OfficeExpense>
 */
class OfficeExpenseFactory extends Factory
{
    protected $model = OfficeExpense::class;

    public function definition(): array
    {
        $category = ExpenseCategory::query()->firstOrCreate(
            ['name' => 'Electricity'],
            ['status' => 'active'],
        );

        return [
            'expense_number' => 'OFF-'.fake()->unique()->numerify('####'),
            'date' => now()->toDateString(),
            'expense_category_id' => $category->id,
            'expense' => 'Electricity',
            'price' => 1500,
        ];
    }
}
