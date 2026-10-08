<?php

namespace Database\Seeders;

use App\Models\PersonalExpense;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Seeder;

class PersonalExpenseSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->where('email', 'owner@detailinggarage.test')->firstOrFail();
        $staff = Staff::query()->orderBy('id')->get();
        $rows = [
            ['Rajesh Patel', 5000, 'Personal Travel'],
            ['Amit Shah', 2500, 'Personal Requirement'],
            ['Rajesh Patel', 8000, 'Family Expense'],
            ['Rahul Mehta', 1200, 'Mobile Recharge'],
            ['Vishal Patel', 3000, 'Tool Allowance'],
            ['Karan Joshi', 1500, 'Travel'],
            ['Harsh Desai', 800, 'Meal'],
            ['Yash Thakkar', 600, 'Local Travel'],
            ['Dev Patel', 2000, 'Client Visit'],
            ['Jay Shah', 1800, 'Personal Requirement'],
            ['Manav Joshi', 900, 'Travel'],
            ['Amit Shah', 4000, 'Family Expense'],
            ['Rahul Mehta', 2200, 'Medical'],
            ['Vishal Patel', 1600, 'Travel'],
            ['Karan Joshi', 2700, 'Personal Requirement'],
        ];

        foreach ($rows as $index => [$name, $price, $expense]) {
            $member = $staff->firstWhere('full_name', $name) ?? $staff[$index % $staff->count()];
            PersonalExpense::query()->updateOrCreate(
                ['expense_number' => 'PER-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT)],
                [
                    'date' => now()->subDays(15 - ($index % 15))->toDateString(),
                    'expense' => $expense,
                    'price' => $price,
                    'staff_id' => $member->id,
                    'description' => $expense,
                    'created_by' => $owner->id,
                    'updated_by' => $owner->id,
                ],
            );
        }
    }
}
