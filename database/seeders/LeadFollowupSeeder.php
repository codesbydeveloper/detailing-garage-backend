<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\User;
use Illuminate\Database\Seeder;

class LeadFollowupSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->where('email', 'owner@detailinggarage.test')->firstOrFail();
        $assignees = User::query()->whereIn('role', ['owner', 'manager'])->pluck('id');
        $leads = Lead::query()->orderBy('id')->limit(15)->get();
        $statuses = ['pending', 'completed', 'cancelled'];

        foreach ($leads as $index => $lead) {
            $status = $statuses[$index % 3];
            LeadFollowup::query()->updateOrCreate(
                ['lead_id' => $lead->id, 'follow_up_date' => now()->addDays($index - 5)->toDateString()],
                [
                    'assigned_to' => $assignees[$index % $assignees->count()],
                    'status' => $status,
                    'notes' => 'Follow up with '.$lead->full_name.' about '.$lead->service_interested,
                    'completed_at' => $status === 'completed' ? now()->subDays(1) : null,
                    'created_by' => $owner->id,
                ],
            );
        }
    }
}
