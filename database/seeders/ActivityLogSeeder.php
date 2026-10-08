<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Job;
use App\Models\Lead;
use App\Models\OfficeExpense;
use App\Models\User;
use Illuminate\Database\Seeder;

class ActivityLogSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->where('email', 'owner@detailinggarage.test')->firstOrFail();
        $manager = User::query()->where('email', 'manager@detailinggarage.test')->firstOrFail();
        $staff = User::query()->where('email', 'staff1@detailinggarage.test')->firstOrFail();
        $lead = Lead::query()->first();
        $job = Job::query()->first();
        $expense = OfficeExpense::query()->first();

        $rows = [
            [$owner, 'LOGIN', 'auth', 'Login successful', 'success', null, ['email' => $owner->email]],
            [$manager, 'LOGIN_FAILED', 'auth', 'Failed login attempt', 'failed', null, ['email' => 'wrong@detailinggarage.test']],
            [$owner, 'LOGOUT', 'auth', 'Logout successful', 'success', null, null],
            [$owner, 'PIN_VERIFIED', 'security', 'Security PIN verified', 'success', null, ['expires_at' => now()->addMinutes(15)->toIso8601String()]],
            [$manager, 'PIN_FAILED', 'security', 'Incorrect security PIN', 'failed', null, ['failed_attempts' => 1]],
            [$owner, 'CREATE', 'leads', 'Lead created', 'success', null, $lead ? ['id' => $lead->id, 'full_name' => $lead->full_name] : null, 'Lead', $lead?->id],
            [$manager, 'UPDATE', 'leads', 'Lead updated', 'success', ['status' => 'New'], ['status' => 'Qualified'], 'Lead', $lead?->id],
            [$owner, 'DELETE', 'leads', 'Lead deleted', 'success', ['full_name' => 'Removed demo lead', 'phone_number' => '9000000000'], null],
            [$owner, 'PAYMENT_CREATED', 'jobs', 'Job payment recorded', 'success', null, ['amount' => '10000.00', 'payment_method' => 'upi'], 'Job', $job?->id],
            [$staff, 'CLOCK_IN', 'attendance', 'Staff clocked in', 'success', null, ['status' => 'present']],
            [$staff, 'CLOCK_OUT', 'attendance', 'Staff clocked out', 'success', ['clock_out' => null], ['total_minutes' => 540]],
            [$owner, 'EXTRA_PAY_CREATED', 'salary', 'Extra pay created', 'success', null, ['amount' => '1500.00', 'reason' => 'Overtime']],
            [$owner, 'SALARY_GENERATED', 'salary', 'Monthly salary generated', 'success', null, ['year' => now()->year, 'month' => now()->month]],
            [$owner, 'SALARY_PAID', 'salary', 'Salary marked as paid', 'success', ['status' => 'generated'], ['status' => 'paid']],
            [$manager, 'OFFICE_EXPENSE_CREATED', 'expenses', 'Office expense created', 'success', null, $expense ? ['expense' => $expense->expense, 'price' => $expense->price] : null, 'OfficeExpense', $expense?->id],
            [$owner, 'VENDOR_PAYMENT_CREATED', 'vendors', 'Vendor payment recorded', 'success', null, ['amount' => '15000.00', 'purpose' => 'Chemicals']],
            [$owner, 'STAFF_CREATED', 'staff', 'Staff member created', 'success', null, ['full_name' => 'Rahul Mehta']],
            [$manager, 'STATUS_CHANGED', 'jobs', 'Job status changed', 'success', ['job_status' => 'booked'], ['job_status' => 'in_progress'], 'Job', $job?->id],
            [$owner, 'NOTE_CREATED', 'leads', 'Lead note created', 'success', null, ['note' => 'Customer asked for ceramic coating pricing.']],
            [$owner, 'FOLLOWUP_CREATED', 'leads', 'Lead follow-up created', 'success', null, ['status' => 'pending']],
            [$owner, 'CONVERTED_TO_JOB', 'leads', 'Lead converted to job', 'success', ['status' => 'Qualified'], ['status' => 'Converted'], 'Lead', $lead?->id],
            [$manager, 'ATTENDANCE_UPDATED', 'attendance', 'Attendance record updated', 'success', ['status' => 'absent'], ['status' => 'present']],
            [$owner, 'WORK_TYPE_CREATED', 'settings', 'Work type created', 'success', null, ['name' => 'Ceramic Coating']],
            [$owner, 'PERMISSION_CHANGED', 'settings', 'Role permissions synced', 'success', ['settings.manage' => false], ['settings.manage' => true]],
            [$staff, 'LOGIN', 'auth', 'Login successful', 'success', null, ['email' => $staff->email]],
            [$owner, 'VENDOR_CREATED', 'vendors', 'Vendor created', 'success', null, ['name' => 'AutoCare Supplies']],
            [$manager, 'PERSONAL_EXPENSE_CREATED', 'expenses', 'Personal expense created', 'success', null, ['expense' => 'Personal Travel', 'price' => '5000.00']],
            [$owner, 'SALARY_UPDATED', 'salary', 'Salary record updated', 'success', ['bonus' => '0.00'], ['bonus' => '500.00']],
            [$manager, 'UPDATE', 'jobs', 'Job updated', 'success', ['discount' => '0.00'], ['discount' => '1000.00'], 'Job', $job?->id],
            [$owner, 'PIN_CHANGED', 'security', 'Security PIN changed', 'success', null, ['pin_updated_at' => now()->toIso8601String()]],
        ];

        foreach ($rows as $index => $row) {
            [$user, $action, $module, $description, $status, $old, $new] = array_pad($row, 7, null);
            $entityType = $row[7] ?? null;
            $entityId = $row[8] ?? null;

            ActivityLog::query()->updateOrCreate(
                ['user_id' => $user->id, 'action' => $action, 'description' => $description],
                [
                    'module' => $module,
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'old_values' => $old,
                    'new_values' => $new,
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Seeder',
                    'status' => $status,
                    'created_at' => now()->subDays(10)->addHours($index),
                    'updated_at' => now()->subDays(10)->addHours($index),
                ],
            );
        }
    }
}
