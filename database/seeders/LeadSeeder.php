<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class LeadSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->where('email', 'owner@detailinggarage.test')->firstOrFail();
        $statuses = LeadStatus::query()->pluck('id', 'slug');
        $names = [
            'Aarav Shah', 'Vivaan Patel', 'Aditya Mehta', 'Arjun Desai', 'Reyansh Joshi',
            'Muhammad Khan', 'Sai Trivedi', 'Krishna Rao', 'Ishaan Gupta', 'Shaurya Jain',
            'Ayaan Kapoor', 'Dhruv Bhatt', 'Kabir Solanki', 'Rudra Chauhan', 'Anay Pandya',
            'Vihaan Modi', 'Aryan Dave', 'Kartik Parmar', 'Neil Shah', 'Om Patel',
            'Yuvan Mehta', 'Harshil Joshi', 'Parth Desai', 'Meet Thakkar', 'Jaymin Shah',
            'Het Patel', 'Tirth Joshi', 'Smit Mehta', 'Nirav Desai', 'Kunal Shah',
        ];
        $platforms = ['Facebook', 'Instagram', 'Google', 'Website', 'WhatsApp', 'Organic'];
        $statusSlugs = ['new', 'contacted', 'follow-up', 'qualified', 'quotation-sent', 'booked', 'converted', 'lost'];
        $services = ['Ceramic Coating', 'PPF', 'Interior Detailing', 'Exterior Detailing', 'Paint Correction', 'Graphene Coating', 'Full Detailing', 'Car Wash'];
        $vehicles = ['BMW X5', 'BMW 3 Series', 'Mercedes C-Class', 'Mercedes GLC', 'Audi A4', 'Audi Q5', 'Toyota Fortuner', 'Toyota Innova Crysta', 'Mahindra XUV700', 'Mahindra Thar', 'Hyundai Creta', 'Hyundai Tucson', 'Kia Seltos', 'Kia Carnival', 'Volkswagen Taigun', 'Skoda Kodiaq', 'Jeep Compass', 'MG Hector'];
        $colours = ['Black', 'White', 'Silver', 'Grey', 'Blue', 'Red', 'Pearl White', 'Dark Grey'];
        $conditions = ['Excellent', 'Good', 'Average', 'Needs Correction', 'Heavily Scratched', 'New Vehicle'];
        $finishes = ['Gloss', 'High Gloss', 'Matte', 'Satin', 'Factory Finish'];
        $campaigns = [
            ['ceramic-ad-01', 'Ceramic Coating Ad', 'ahmedabad-owners', 'Ahmedabad Car Owners', 'premium-detail', 'Premium Detailing Campaign', 'ceramic-form', 'Ceramic Lead Form'],
            ['ppf-ad-02', 'PPF Protection Ad', 'luxury-suv', 'Luxury SUV Owners', 'ppf-campaign', 'PPF Launch Campaign', 'ppf-form', 'PPF Lead Form'],
            ['interior-ad-03', 'Interior Detailing Ad', 'family-cars', 'Family Car Owners', 'interior-campaign', 'Interior Care Campaign', 'interior-form', 'Interior Lead Form'],
        ];

        foreach ($names as $index => $name) {
            $organic = $index % 4 === 0;
            $platform = $organic ? 'Organic' : $platforms[$index % 5];
            $campaign = $campaigns[$index % count($campaigns)];
            $phone = '987652'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);

            Lead::query()->updateOrCreate(
                ['phone_number' => $phone],
                [
                    'created_time' => now()->subDays(29 - $index)->setTime(10, ($index * 7) % 60),
                    'ad_id' => $organic ? null : $campaign[0],
                    'ad_name' => $organic ? null : $campaign[1],
                    'adset_id' => $organic ? null : $campaign[2],
                    'adset_name' => $organic ? null : $campaign[3],
                    'campaign_id' => $organic ? null : $campaign[4],
                    'campaign_name' => $organic ? null : $campaign[5],
                    'form_id' => $organic ? null : $campaign[6],
                    'form_name' => $organic ? null : $campaign[7],
                    'is_organic' => $organic,
                    'platform' => $platform,
                    'service_interested' => $services[$index % count($services)],
                    'car_condition' => $conditions[$index % count($conditions)],
                    'preferred_finish' => $finishes[$index % count($finishes)],
                    'planned_service_date' => now()->addDays(($index % 12) + 1)->toDateString(),
                    'car_model' => $vehicles[$index % count($vehicles)],
                    'car_colour' => $colours[$index % count($colours)],
                    'full_name' => $name,
                    'email' => 'lead'.($index + 1).'@customer.test',
                    'lead_status_id' => $statuses[$statusSlugs[$index % count($statusSlugs)]],
                    'notes' => 'Demo lead captured from '.$platform,
                    'created_by' => $owner->id,
                    'updated_by' => $owner->id,
                ],
            );
        }
    }
}
