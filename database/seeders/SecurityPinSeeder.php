<?php

namespace Database\Seeders;

use App\Models\SecurityPin;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SecurityPinSeeder extends Seeder
{
    public function run(): void
    {
        $pins = [
            'owner@detailinggarage.test' => '123456',
            'manager@detailinggarage.test' => '654321',
        ];

        foreach ($pins as $email => $pin) {
            $user = User::query()->where('email', $email)->firstOrFail();

            SecurityPin::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'pin_hash' => Hash::make($pin),
                    'pin_updated_at' => now(),
                    'failed_attempts' => 0,
                    'locked_until' => null,
                ],
            );
        }
    }
}
