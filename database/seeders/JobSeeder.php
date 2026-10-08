<?php

namespace Database\Seeders;

use App\Domain\JobFinancials;
use App\Models\Job;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\User;
use App\Models\WorkType;
use Illuminate\Database\Seeder;

class JobSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->where('email', 'owner@detailinggarage.test')->firstOrFail();
        $workTypes = WorkType::query()->pluck('id', 'name');
        $convertedId = LeadStatus::query()->where('slug', 'converted')->value('id');
        $leads = Lead::query()->with('leadStatus')->orderBy('id')->limit(8)->get();
        $samples = [
            [15000, 1000, 3500, 2500],
            [8000, 0, 1500, 1000],
            [12000, 500, 3000, 2000],
            [25000, 2000, 8000, 4000],
            [4500, 0, 800, 600],
            [18000, 1500, 5000, 3000],
            [9000, 500, 2000, 1500],
            [35000, 3000, 14000, 6000],
        ];
        $statuses = ['booked', 'in_progress', 'completed', 'delivered'];

        foreach (range(1, 20) as $index) {
            $lead = $leads->values()->get($index - 1);
            [$income, $discount, $product, $labour] = $samples[($index - 1) % count($samples)];
            $financials = JobFinancials::calculate($income, $discount, $product, $labour, 0);
            $service = $lead?->service_interested ?? 'Full Detailing';
            $number = 'JOB-'.now()->format('Ym').'-'.str_pad((string) $index, 4, '0', STR_PAD_LEFT);

            Job::query()->updateOrCreate(
                ['job_number' => $number],
                [
                    'date' => now()->subDays(20 - $index)->toDateString(),
                    'delivery_date' => now()->subDays(18 - $index)->toDateString(),
                    'car_name' => trim(($lead?->car_model ?? 'Hyundai Creta').' '.($lead?->car_colour ?? 'White')),
                    'car_number' => sprintf('GJ%02d%c%c%04d', ($index % 27) + 1, chr(65 + ($index % 26)), chr(66 + ($index % 25)), 1000 + $index),
                    'customer_name' => $lead?->full_name ?? 'Walk-in Customer '.$index,
                    'customer_mobile' => $lead?->phone_number ?? '987653'.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                    'customer_email' => $lead?->email ?? 'walkin'.$index.'@customer.test',
                    'work_type_id' => $workTypes[$service] ?? $workTypes['Full Detailing'],
                    'job_status' => $statuses[$index % count($statuses)],
                    'remark' => $lead ? 'Converted from lead. Source: '.$lead->platform : 'Workshop booking',
                    'lead_id' => $lead?->id,
                    'created_by' => $owner->id,
                    'updated_by' => $owner->id,
                    ...$financials,
                ],
            );

            if ($lead) {
                $lead->update(['lead_status_id' => $convertedId, 'updated_by' => $owner->id]);
            }
        }
    }
}
