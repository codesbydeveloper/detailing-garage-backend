<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['Rajesh Patel', 'owner@detailinggarage.test', 'owner'],
            ['Amit Shah', 'manager@detailinggarage.test', 'manager'],
            ['Rahul Mehta', 'staff1@detailinggarage.test', 'staff'],
            ['Vishal Patel', 'staff2@detailinggarage.test', 'staff'],
            ['Karan Joshi', 'staff3@detailinggarage.test', 'staff'],
            ['Harsh Desai', 'staff4@detailinggarage.test', 'staff'],
            ['Yash Thakkar', 'staff5@detailinggarage.test', 'staff'],
            ['Dev Patel', 'staff6@detailinggarage.test', 'staff'],
            ['Jay Shah', 'staff7@detailinggarage.test', 'staff'],
            ['Manav Joshi', 'staff8@detailinggarage.test', 'staff'],
        ];

        foreach ($users as [$name, $email, $role]) {
            User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => 'Password@123',
                    'role' => $role,
                    'status' => 'active',
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
