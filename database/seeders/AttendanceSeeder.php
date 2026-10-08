<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Staff;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->where('email', 'owner@detailinggarage.test')->firstOrFail();
        $staff = Staff::query()->where('status', 'active')->orderBy('id')->get();
        $openClockIns = 0;

        for ($offset = 29; $offset >= 0; $offset--) {
            $date = now()->subDays($offset)->startOfDay();

            foreach ($staff as $index => $member) {
                $payload = $this->row($date, $index, $offset === 0, $openClockIns);
                if ($payload['clock_in'] && ! $payload['clock_out'] && $offset === 0) {
                    $openClockIns++;
                }

                Attendance::query()->updateOrCreate(
                    ['staff_id' => $member->id, 'date' => $date->toDateString()],
                    [
                        ...$payload,
                        'created_by' => $owner->id,
                        'updated_by' => $owner->id,
                    ],
                );
            }
        }
    }

    private function row(Carbon $date, int $staffIndex, bool $isToday, int $openClockIns): array
    {
        if ($date->isSunday()) {
            return [
                'clock_in' => null,
                'clock_out' => null,
                'total_minutes' => null,
                'status' => 'holiday',
                'notes' => 'Weekly holiday',
            ];
        }

        $bucket = ($staffIndex + $date->day) % 10;

        if ($bucket === 0) {
            return $this->timed(null, null, 'absent', 'Absent');
        }

        if ($bucket === 1) {
            return $this->timed(null, null, 'leave', 'Approved leave');
        }

        if ($bucket === 2) {
            return $this->shift($date, 9, 0, 13, 30, 'half_day', 'Half day');
        }

        if ($bucket === 3) {
            return $this->shift($date, 9, 30, 18, 15, 'late', 'Arrived late');
        }

        if ($isToday && $openClockIns < 3 && $bucket > 3) {
            $in = $date->copy()->setTime(9, 0);

            return [
                'clock_in' => $in,
                'clock_out' => null,
                'total_minutes' => null,
                'status' => 'present',
                'notes' => 'Currently working',
            ];
        }

        return $this->shift($date, 9, 0, 18, 0, 'present', 'Present');
    }

    private function shift(Carbon $date, int $inHour, int $inMinute, int $outHour, int $outMinute, string $status, string $notes): array
    {
        $in = $date->copy()->setTime($inHour, $inMinute);
        $out = $date->copy()->setTime($outHour, $outMinute);

        return [
            'clock_in' => $in,
            'clock_out' => $out,
            'total_minutes' => (int) round($in->diffInMinutes($out, true)),
            'status' => $status,
            'notes' => $notes,
        ];
    }

    private function timed(mixed $in, mixed $out, string $status, string $notes): array
    {
        return [
            'clock_in' => $in,
            'clock_out' => $out,
            'total_minutes' => null,
            'status' => $status,
            'notes' => $notes,
        ];
    }
}
