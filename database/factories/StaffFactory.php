<?php

namespace Database\Factories;

use App\Models\Staff;
use App\Models\StaffCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Staff>
 */
class StaffFactory extends Factory
{
    protected $model = Staff::class;

    public function definition(): array
    {
        $category = StaffCategory::query()->firstOrCreate(
            ['name' => 'Detailer'],
            ['status' => 'active'],
        );

        return [
            'staff_code' => 'STF'.fake()->unique()->numerify('####'),
            'full_name' => fake()->name(),
            'phone' => fake()->numerify('98765#####'),
            'email' => fake()->unique()->safeEmail(),
            'address' => 'Ahmedabad, Gujarat',
            'date_of_birth' => fake()->date(),
            'joining_date' => now()->subYear()->toDateString(),
            'staff_category_id' => $category->id,
            'role' => 'staff',
            'department' => 'Workshop',
            'salary_type' => 'monthly',
            'basic_salary' => 20000,
            'status' => 'active',
        ];
    }
}
