<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->where('email', 'owner@detailinggarage.test')->firstOrFail();
        $vendors = [
            ['AutoCare Supplies', 'Chemicals', '9876610001'],
            ['DetailPro Chemicals', 'Chemicals', '9876610002'],
            ['PPF Solutions India', 'PPF', '9876610003'],
            ['Garage Tools India', 'Tools', '9876610004'],
            ['Premium Auto Products', 'Accessories', '9876610005'],
            ['Shine Chemicals', 'Chemicals', '9876610006'],
            ['CarCare Accessories', 'Accessories', '9876610007'],
            ['Auto Finish Distributors', 'Coatings', '9876610008'],
        ];

        foreach ($vendors as $index => [$name, $category, $phone]) {
            Vendor::query()->updateOrCreate(
                ['name' => $name],
                [
                    'vendor_code' => 'VND'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'phone' => $phone,
                    'email' => strtolower(str_replace(' ', '', $name)).'@vendor.test',
                    'company' => $name,
                    'address' => 'Industrial Estate, Ahmedabad, Gujarat',
                    'category' => $category,
                    'notes' => 'Demo vendor account',
                    'status' => 'active',
                    'created_by' => $owner->id,
                    'updated_by' => $owner->id,
                ],
            );
        }
    }
}
