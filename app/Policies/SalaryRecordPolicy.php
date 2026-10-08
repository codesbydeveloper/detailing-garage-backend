<?php

namespace App\Policies;

use App\Models\SalaryRecord;
use App\Models\User;

class SalaryRecordPolicy
{
    public function view(User $user, SalaryRecord $salary): bool
    {
        if ($user->hasPermission('salary.view') || $user->hasPermission('salary.manage')) {
            return true;
        }

        return $user->hasPermission('salary.my')
            && $user->staff_id
            && (int) $user->staff_id === (int) $salary->staff_id;
    }

    public function update(User $user, SalaryRecord $salary): bool
    {
        return $user->hasPermission('salary.manage');
    }
}
