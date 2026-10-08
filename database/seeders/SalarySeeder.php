<?php

namespace Database\Seeders;

use App\Domain\PayrollMath;
use App\Models\ExtraPay;
use App\Models\SalaryRecord;
use App\Models\Staff;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\Seeder;

class SalarySeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->where('email', 'owner@detailinggarage.test')->firstOrFail();
        $staff = Staff::query()->orderBy('id')->get();
        $periods = [
            [now()->subMonth()->year, now()->subMonth()->month, 'paid'],
            [now()->year, now()->month, 'generated'],
        ];

        foreach ($staff as $index => $member) {
            foreach ($periods as [$year, $month, $defaultStatus]) {
                $extra = Money::of(
                    ExtraPay::query()
                        ->where('staff_id', $member->id)
                        ->whereYear('date', $year)
                        ->whereMonth('date', $month)
                        ->sum('amount')
                );
                $bonus = $index % 3 === 0 ? '500.00' : '0.00';
                $deduction = $index % 4 === 0 ? '200.00' : '0.00';
                $advance = $index % 5 === 0 ? '1000.00' : '0.00';
                $status = ($defaultStatus === 'generated' && $index === $staff->count() - 1) ? 'draft' : $defaultStatus;

                SalaryRecord::query()->updateOrCreate(
                    ['staff_id' => $member->id, 'year' => $year, 'month' => $month],
                    [
                        'basic_salary' => $member->basic_salary,
                        'extra_pay' => $extra,
                        'bonus' => $bonus,
                        'deduction' => $deduction,
                        'advance' => $advance,
                        'final_payable' => PayrollMath::payable($member->basic_salary, $extra, $bonus, $deduction, $advance),
                        'status' => $status,
                        'paid_at' => $status === 'paid' ? now()->subMonth()->endOfMonth() : null,
                        'notes' => 'Seeded salary',
                        'created_by' => $owner->id,
                        'updated_by' => $owner->id,
                    ],
                );
            }
        }
    }
}
