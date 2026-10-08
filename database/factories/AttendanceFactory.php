<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        $in = now()->setTime(9, 0);
        $out = now()->setTime(18, 0);

        return [
            'staff_id' => Staff::factory(),
            'date' => now()->toDateString(),
            'clock_in' => $in,
            'clock_out' => $out,
            'total_minutes' => (int) round($in->diffInMinutes($out, true)),
            'status' => 'present',
        ];
    }
}
