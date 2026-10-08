<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            BusinessSettingSeeder::class,
            StaffCategorySeeder::class,
            WorkTypeSeeder::class,
            LeadStatusSeeder::class,
            ExpenseCategorySeeder::class,
            UserSeeder::class,
            StaffSeeder::class,
            SecurityPinSeeder::class,
            VendorSeeder::class,
            LeadSeeder::class,
            LeadNoteSeeder::class,
            LeadFollowupSeeder::class,
            JobSeeder::class,
            JobPaymentSeeder::class,
            OfficeExpenseSeeder::class,
            PersonalExpenseSeeder::class,
            VendorPaymentSeeder::class,
            AttendanceSeeder::class,
            ExtraPaySeeder::class,
            SalarySeeder::class,
            ActivityLogSeeder::class,
        ]);
    }
}
