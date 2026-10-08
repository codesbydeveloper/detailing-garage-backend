<?php

namespace Database\Seeders;

use App\Models\LeadStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LeadStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['New', '#2563eb'],
            ['Contacted', '#0891b2'],
            ['Follow Up', '#d97706'],
            ['Qualified', '#7c3aed'],
            ['Quotation Sent', '#4f46e5'],
            ['Booked', '#0f766e'],
            ['Converted', '#15803d'],
            ['Lost', '#b91c1c'],
        ];

        foreach ($statuses as $index => [$name, $color]) {
            LeadStatus::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'color' => $color,
                    'sort_order' => $index + 1,
                    'status' => 'active',
                ],
            );
        }
    }
}
