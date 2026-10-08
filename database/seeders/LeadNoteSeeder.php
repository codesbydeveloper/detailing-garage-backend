<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\User;
use Illuminate\Database\Seeder;

class LeadNoteSeeder extends Seeder
{
    public function run(): void
    {
        $notes = [
            'Customer asked for ceramic coating pricing.',
            'Customer will visit Saturday.',
            'Follow up after vehicle inspection.',
            'Customer requested PPF quotation.',
            'Customer is comparing packages.',
            'Customer confirmed service date.',
            'Shared package photos on WhatsApp.',
            'Customer wants matte finish options.',
            'Waiting for spouse confirmation.',
            'Asked about warranty duration.',
            'Vehicle is a new delivery.',
            'Requested pickup and drop.',
            'Budget discussed for full detailing.',
            'Will decide after test panel.',
            'Asked to reschedule to next week.',
        ];
        $users = User::query()->whereIn('role', ['owner', 'manager'])->pluck('id');
        $leads = Lead::query()->orderBy('id')->limit(15)->get();

        foreach ($notes as $index => $note) {
            $lead = $leads[$index];
            LeadNote::query()->updateOrCreate(
                ['lead_id' => $lead->id, 'note' => $note],
                ['user_id' => $users[$index % $users->count()]],
            );
        }
    }
}
