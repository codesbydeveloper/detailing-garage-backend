<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use App\Models\OfficeExpense;
use App\Models\User;
use Illuminate\Database\Seeder;

class OfficeExpenseSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->where('email', 'owner@detailinggarage.test')->firstOrFail();
        $categories = ExpenseCategory::query()->pluck('id', 'name');
        $rows = [
            ['Electricity', 8500], ['Internet', 1499], ['Rent', 45000], ['Cleaning', 2500],
            ['Stationery', 750], ['Maintenance', 6200], ['Marketing', 12000], ['Fuel', 3400],
            ['Equipment', 18000], ['Water', 900], ['Tea & Refreshments', 1800], ['Miscellaneous', 2200],
            ['Electricity', 7900], ['Internet', 1499], ['Fuel', 2800], ['Cleaning', 2600],
            ['Maintenance', 4100], ['Marketing', 8000], ['Water', 850], ['Tea & Refreshments', 1600],
        ];

        foreach ($rows as $index => [$name, $price]) {
            OfficeExpense::query()->updateOrCreate(
                ['expense_number' => 'OFF-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT)],
                [
                    'date' => now()->subDays(20 - ($index % 20))->toDateString(),
                    'expense_category_id' => $categories[$name] ?? null,
                    'expense' => $name,
                    'price' => $price,
                    'description' => $name.' for the workshop',
                    'created_by' => $owner->id,
                    'updated_by' => $owner->id,
                ],
            );
        }
    }
}
