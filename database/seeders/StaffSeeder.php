<?php

namespace Database\Seeders;

use App\Models\Staff;
use App\Models\StaffCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->where('email', 'owner@detailinggarage.test')->firstOrFail();
        $categories = StaffCategory::query()->pluck('id', 'name');

        $people = [
            ['Rajesh Patel', 'owner@detailinggarage.test', 'Manager', 'manager', '9876500001', 45000, '1985-04-12'],
            ['Amit Shah', 'manager@detailinggarage.test', 'Supervisor', 'manager', '9876500002', 32000, '1988-09-03'],
            ['Rahul Mehta', 'staff1@detailinggarage.test', 'Detailer', 'staff', '9876500003', 22000, '1996-01-18'],
            ['Vishal Patel', 'staff2@detailinggarage.test', 'PPF Technician', 'staff', '9876500004', 24000, '1994-06-22'],
            ['Karan Joshi', 'staff3@detailinggarage.test', 'Ceramic Technician', 'staff', '9876500005', 23000, '1997-11-09'],
            ['Harsh Desai', 'staff4@detailinggarage.test', 'Washer', 'staff', '9876500006', 15000, '1999-02-14'],
            ['Yash Thakkar', 'staff5@detailinggarage.test', 'Helper', 'staff', '9876500007', 14000, '2000-08-30'],
            ['Dev Patel', 'staff6@detailinggarage.test', 'Sales', 'staff', '9876500008', 18000, '1995-12-05'],
            ['Jay Shah', 'staff7@detailinggarage.test', 'Supervisor', 'staff', '9876500009', 26000, '1992-03-27'],
            ['Manav Joshi', 'staff8@detailinggarage.test', 'Detailer', 'staff', '9876500010', 21000, '1998-07-16'],
        ];

        foreach ($people as $index => [$name, $email, $category, $role, $phone, $salary, $dob]) {
            $user = User::query()->where('email', $email)->firstOrFail();
            $code = 'STF'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);

            $staff = Staff::query()->updateOrCreate(
                ['email' => $email],
                [
                    'staff_code' => $code,
                    'user_id' => $user->id,
                    'full_name' => $name,
                    'phone' => $phone,
                    'address' => ($index + 12).', Satellite, Ahmedabad, Gujarat',
                    'date_of_birth' => $dob,
                    'joining_date' => now()->subMonths(18 + $index)->toDateString(),
                    'emergency_contact' => 'Family of '.$name,
                    'emergency_contact_number' => '987651'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'staff_category_id' => $categories[$category],
                    'role' => $role,
                    'department' => $category === 'Sales' ? 'Sales' : 'Workshop',
                    'salary_type' => 'monthly',
                    'basic_salary' => $salary,
                    'status' => 'active',
                    'created_by' => $owner->id,
                    'updated_by' => $owner->id,
                ],
            );

            $user->forceFill(['staff_id' => $staff->id])->save();
        }
    }
}
