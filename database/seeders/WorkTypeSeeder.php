<?php

namespace Database\Seeders;

use App\Models\WorkType;
use Illuminate\Database\Seeder;

class WorkTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['Ceramic Coating', 15000],
            ['PPF', 35000],
            ['Interior Detailing', 4500],
            ['Exterior Detailing', 3500],
            ['Paint Correction', 12000],
            ['Car Wash', 800],
            ['Polishing', 5000],
            ['Graphene Coating', 18000],
            ['Full Detailing', 9000],
            ['Interior Deep Cleaning', 5500],
            ['Engine Bay Cleaning', 2500],
            ['Headlight Restoration', 3000],
            ['Odour Treatment', 2000],
            ['Other', null],
        ];

        foreach ($types as [$name, $price]) {
            WorkType::query()->updateOrCreate(
                ['name' => $name],
                [
                    'description' => $name.' service',
                    'default_price' => $price,
                    'status' => 'active',
                ],
            );
        }
    }
}
