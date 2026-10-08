<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\LeadStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithCrm;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use InteractsWithCrm, RefreshDatabase;

    public function test_owner_can_read_logs_only_after_pin_verification(): void
    {
        $owner = $this->makeUser('owner');
        $this->assignPin($owner);
        $token = $this->tokenFor($owner);

        ActivityLog::factory()->create([
            'user_id' => $owner->id,
            'action' => 'CREATE',
            'module' => 'leads',
            'description' => 'Lead created',
        ]);

        $this->withToken($token)->getJson('/api/v1/activity-logs')
            ->assertForbidden()
            ->assertJsonPath('code', 'PIN_REQUIRED');

        $this->withToken($token)->postJson('/api/v1/security/verify-pin', ['pin' => '123456'])->assertOk();

        $this->withToken($token)->getJson('/api/v1/activity-logs?action=CREATE&module=leads')
            ->assertOk()
            ->assertJsonPath('data.0.action', 'CREATE');
    }

    public function test_lead_notes_are_audited(): void
    {
        $owner = $this->makeUser('owner');
        $status = LeadStatus::query()->create([
            'name' => 'New',
            'slug' => 'new',
            'sort_order' => 1,
            'status' => 'active',
        ]);
        $lead = Lead::factory()->create(['lead_status_id' => $status->id]);
        $token = $this->tokenFor($owner);

        $note = $this->withToken($token)->postJson('/api/v1/leads/'.$lead->id.'/notes', [
            'note' => 'Customer asked for ceramic coating pricing.',
        ])->assertCreated();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'NOTE_CREATED',
            'entity_id' => $note->json('data.id'),
        ]);
    }
}
