<?php

namespace Database\Factories;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vendor>
 */
class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    public function definition(): array
    {
        return [
            'vendor_code' => 'VND'.fake()->unique()->numerify('####'),
            'name' => fake()->company(),
            'phone' => fake()->numerify('98766#####'),
            'email' => fake()->companyEmail(),
            'status' => 'active',
            'category' => 'Chemicals',
        ];
    }
}
