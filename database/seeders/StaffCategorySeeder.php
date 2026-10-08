<?php

namespace Database\Seeders;

use App\Models\StaffCategory;
use Illuminate\Database\Seeder;

class StaffCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Detailer',
            'Washer',
            'PPF Technician',
            'Ceramic Technician',
            'Manager',
            'Supervisor',
            'Helper',
            'Accountant',
            'Sales',
            'Cleaner',
        ];

        foreach ($categories as $name) {
            StaffCategory::query()->updateOrCreate(
                ['name' => $name],
                ['description' => $name.' team', 'status' => 'active'],
            );
        }
    }
}
