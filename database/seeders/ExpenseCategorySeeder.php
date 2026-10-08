<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Electricity',
            'Internet',
            'Rent',
            'Cleaning',
            'Stationery',
            'Maintenance',
            'Marketing',
            'Fuel',
            'Equipment',
            'Water',
            'Tea & Refreshments',
            'Miscellaneous',
        ];

        foreach ($categories as $name) {
            ExpenseCategory::query()->updateOrCreate(
                ['name' => $name],
                ['description' => $name.' expense', 'status' => 'active'],
            );
        }
    }
}
