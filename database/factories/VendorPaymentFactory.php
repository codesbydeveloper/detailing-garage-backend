<?php

namespace Database\Factories;

use App\Models\Vendor;
use App\Models\VendorPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorPayment>
 */
class VendorPaymentFactory extends Factory
{
    protected $model = VendorPayment::class;

    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'date' => now()->toDateString(),
            'amount' => 5000,
            'purpose' => 'Chemicals',
            'payment_method' => 'upi',
        ];
    }
}
