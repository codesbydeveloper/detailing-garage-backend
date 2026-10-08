<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    public function view(User $user, Attendance $attendance): bool
    {
        if ($user->hasPermission('attendance.view') || $user->hasPermission('attendance.manage')) {
            return true;
        }

        return $user->hasPermission('attendance.my')
            && $user->staff_id
            && (int) $user->staff_id === (int) $attendance->staff_id;
    }
}
