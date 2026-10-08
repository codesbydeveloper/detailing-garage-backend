<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\ExtraPay;
use App\Models\SalaryRecord;
use App\Models\Staff;
use App\Support\Money;

class StaffReportService
{
    public function summarize(int $year, int $month): array
    {
        $staff = Staff::query()->with('category:id,name')->orderBy('full_name')->get();
        $attendance = Attendance::query()
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get()
            ->groupBy('staff_id');

        $salaries = SalaryRecord::query()
            ->where('year', $year)
            ->where('month', $month)
            ->get()
            ->keyBy('staff_id');

        $extra = ExtraPay::query()
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get()
            ->groupBy('staff_id');

        $rows = $staff->map(function (Staff $member) use ($attendance, $salaries, $extra) {
            $records = $attendance->get($member->id, collect());
            $counts = $records->countBy('status');
            $minutes = (int) $records->sum('total_minutes');
            $salary = $salaries->get($member->id);
            $extraTotal = Money::of($extra->get($member->id, collect())->sum('amount'));

            $hours = round($minutes / 60, 2);

            return [
                'id' => $member->id,
                'staff_id' => $member->id,
                'staff_code' => $member->staff_code,
                'full_name' => $member->full_name,
                'staff_name' => $member->full_name,
                'category' => $member->category?->name,
                'present' => (int) ($counts['present'] ?? 0),
                'absent' => (int) ($counts['absent'] ?? 0),
                'late' => (int) ($counts['late'] ?? 0),
                'leave' => (int) ($counts['leave'] ?? 0),
                'total_hours' => $hours,
                'attendance' => [
                    'present' => (int) ($counts['present'] ?? 0),
                    'absent' => (int) ($counts['absent'] ?? 0),
                    'late' => (int) ($counts['late'] ?? 0),
                    'leave' => (int) ($counts['leave'] ?? 0),
                    'half_day' => (int) ($counts['half_day'] ?? 0),
                    'holiday' => (int) ($counts['holiday'] ?? 0),
                ],
                'working_hours' => number_format($hours, 2, '.', ''),
                'salary' => $salary?->final_payable ?? '0.00',
                'salary_breakdown' => $salary ? [
                    'basic_salary' => $salary->basic_salary,
                    'extra_pay' => $salary->extra_pay,
                    'bonus' => $salary->bonus,
                    'deduction' => $salary->deduction,
                    'advance' => $salary->advance,
                    'final_payable' => $salary->final_payable,
                    'status' => $salary->status,
                ] : null,
                'extra_pay' => $extraTotal,
            ];
        })->values();

        return [
            'year' => $year,
            'month' => $month,
            'staff' => $rows,
        ];
    }
}
