<?php

namespace Database\Seeders;

use App\Models\BusinessSetting;
use Illuminate\Database\Seeder;

class BusinessSettingSeeder extends Seeder
{
    public function run(): void
    {
        BusinessSetting::query()->updateOrCreate(
            ['business_name' => 'Detailing Garage'],
            [
                'logo' => null,
                'phone' => '+91 98765 00000',
                'email' => 'info@detailinggarage.test',
                'address' => 'Ahmedabad, Gujarat, India',
                'currency' => 'INR',
                'currency_symbol' => '₹',
                'timezone' => 'Asia/Kolkata',
            ],
        );
    }
}
