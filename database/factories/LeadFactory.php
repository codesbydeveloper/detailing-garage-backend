<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\LeadStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        $status = LeadStatus::query()->firstOrCreate(
            ['slug' => 'new'],
            ['name' => 'New', 'sort_order' => 1, 'status' => 'active', 'color' => '#2563eb'],
        );

        return [
            'created_time' => now(),
            'is_organic' => false,
            'platform' => 'Facebook',
            'service_interested' => 'Ceramic Coating',
            'car_condition' => 'Good',
            'preferred_finish' => 'Gloss',
            'car_model' => 'Hyundai Creta',
            'car_colour' => 'White',
            'full_name' => fake()->name(),
            'phone_number' => fake()->unique()->numerify('98765#####'),
            'email' => fake()->safeEmail(),
            'lead_status_id' => $status->id,
            'ad_name' => 'Ceramic Coating Ad',
            'campaign_name' => 'Premium Detailing Campaign',
        ];
    }
}
