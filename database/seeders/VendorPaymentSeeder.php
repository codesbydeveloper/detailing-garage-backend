<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorPayment;
use Illuminate\Database\Seeder;

class VendorPaymentSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->where('email', 'owner@detailinggarage.test')->firstOrFail();
        $vendors = Vendor::query()->pluck('id', 'name');
        $rows = [
            ['AutoCare Supplies', 15000, 'Chemicals', 'upi'],
            ['PPF Solutions India', 35000, 'PPF Material', 'bank_transfer'],
            ['DetailPro Chemicals', 8500, 'Ceramic Products', 'upi'],
            ['Garage Tools India', 12000, 'Tools', 'cash'],
            ['Premium Auto Products', 6400, 'Accessories', 'card'],
            ['Shine Chemicals', 7200, 'Polish', 'upi'],
            ['CarCare Accessories', 4300, 'Microfiber', 'cash'],
            ['Auto Finish Distributors', 9800, 'Coating', 'bank_transfer'],
            ['AutoCare Supplies', 5600, 'Compounds', 'upi'],
            ['PPF Solutions India', 22000, 'Film Roll', 'bank_transfer'],
            ['DetailPro Chemicals', 4100, 'Interior Chemical', 'cash'],
            ['Garage Tools India', 3000, 'Pads', 'upi'],
            ['Shine Chemicals', 2500, 'Dressing', 'cash'],
            ['Premium Auto Products', 8700, 'Lights', 'card'],
            ['CarCare Accessories', 1900, 'Towels', 'cash'],
            ['Auto Finish Distributors', 15000, 'Graphene', 'bank_transfer'],
            ['AutoCare Supplies', 3300, 'Clay Bars', 'upi'],
            ['DetailPro Chemicals', 6100, 'Glass Coating', 'upi'],
            ['PPF Solutions India', 12500, 'Squeegees', 'bank_transfer'],
            ['Garage Tools India', 2700, 'Polishers', 'cash'],
        ];

        foreach ($rows as $index => [$name, $amount, $purpose, $method]) {
            VendorPayment::query()->updateOrCreate(
                ['reference' => 'VP-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT)],
                [
                    'vendor_id' => $vendors[$name],
                    'date' => now()->subDays(20 - ($index % 20))->toDateString(),
                    'amount' => $amount,
                    'purpose' => $purpose,
                    'payment_method' => $method,
                    'notes' => $purpose.' purchase',
                    'created_by' => $owner->id,
                    'updated_by' => $owner->id,
                ],
            );
        }
    }
}
