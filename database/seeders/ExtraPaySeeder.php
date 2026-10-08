<?php

namespace Database\Seeders;

use App\Models\ExtraPay;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExtraPaySeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->where('email', 'owner@detailinggarage.test')->firstOrFail();
        $staff = Staff::query()->orderBy('id')->get();
        $reasons = ['Overtime', 'Extra Work', 'Incentive', 'Bonus', 'Holiday Work', 'Performance Bonus'];
        $amounts = [800, 1200, 1500, 2000, 2500, 3000, 1800, 900, 2200, 1600, 1100, 2700, 1400, 1900, 2100];

        foreach ($amounts as $index => $amount) {
            $member = $staff[$index % $staff->count()];
            $date = $index < 8
                ? now()->subMonth()->startOfMonth()->addDays($index + 1)
                : now()->startOfMonth()->addDays($index % max(now()->day, 1));

            ExtraPay::query()->updateOrCreate(
                [
                    'staff_id' => $member->id,
                    'date' => $date->toDateString(),
                    'reason' => $reasons[$index % count($reasons)],
                ],
                [
                    'amount' => $amount,
                    'notes' => $reasons[$index % count($reasons)].' for '.$member->full_name,
                    'created_by' => $owner->id,
                ],
            );
        }
    }
}
