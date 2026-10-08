<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Job;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\WorkType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithCrm;
use Tests\TestCase;

class LeadTest extends TestCase
{
    use InteractsWithCrm, RefreshDatabase;

    private function leadStatus(string $name, string $slug, int $order): LeadStatus
    {
        return LeadStatus::query()->create([
            'name' => $name,
            'slug' => $slug,
            'sort_order' => $order,
            'status' => 'active',
        ]);
    }

    public function test_owner_can_create_search_filter_update_and_delete_leads(): void
    {
        $owner = $this->makeUser('owner');
        $token = $this->tokenFor($owner);
        $new = $this->leadStatus('New', 'new', 1);
        $qualified = $this->leadStatus('Qualified', 'qualified', 4);

        $created = $this->withToken($token)->postJson('/api/v1/leads', [
            'platform' => 'Facebook',
            'service_interested' => 'Ceramic Coating',
            'full_name' => 'Arjun Mehta',
            'phone_number' => '9876501111',
            'email' => 'arjun@example.test',
            'car_model' => 'BMW X5',
            'lead_status_id' => $new->id,
            'campaign_name' => 'Premium Detailing Campaign',
        ])->assertCreated()
            ->assertJsonPath('data.full_name', 'Arjun Mehta');

        $leadId = $created->json('data.id');

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'CREATE',
            'module' => 'leads',
            'entity_id' => $leadId,
        ]);

        $this->withToken($token)->getJson('/api/v1/leads?search=Arjun&platform=Facebook&service=Ceramic%20Coating')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.full_name', 'Arjun Mehta');

        $this->withToken($token)->putJson('/api/v1/leads/'.$leadId, [
            'lead_status_id' => $qualified->id,
        ])->assertOk()
            ->assertJsonPath('data.lead_status_id', $qualified->id);

        $update = ActivityLog::query()->where('action', 'STATUS_CHANGED')->where('entity_id', $leadId)->first();
        $this->assertNotNull($update);
        $this->assertNotNull($update->old_values);
        $this->assertNotNull($update->new_values);

        $this->withToken($token)->deleteJson('/api/v1/leads/'.$leadId)->assertOk();
        $this->assertSoftDeleted('leads', ['id' => $leadId]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'DELETE',
            'entity_id' => $leadId,
        ]);
    }

    public function test_lead_converts_to_a_job_inside_one_transaction(): void
    {
        $owner = $this->makeUser('owner');
        $new = $this->leadStatus('New', 'new', 1);
        $this->leadStatus('Converted', 'converted', 7);
        $workType = WorkType::query()->create([
            'name' => 'Ceramic Coating',
            'status' => 'active',
            'default_price' => 15000,
        ]);

        $lead = Lead::query()->create([
            'created_time' => now(),
            'is_organic' => false,
            'platform' => 'Instagram',
            'service_interested' => 'Ceramic Coating',
            'car_model' => 'Audi Q5',
            'car_colour' => 'Black',
            'full_name' => 'Neel Shah',
            'phone_number' => '9876502222',
            'email' => 'neel@example.test',
            'lead_status_id' => $new->id,
            'campaign_name' => 'Premium Detailing Campaign',
        ]);

        $this->withToken($this->tokenFor($owner))->postJson('/api/v1/leads/'.$lead->id.'/convert-to-job', [
            'car_number' => 'GJ01AB1234',
            'work_type_id' => $workType->id,
            'income' => 15000,
            'discount' => 1000,
            'product_charge' => 3500,
            'labour_charge' => 2500,
        ])->assertCreated()
            ->assertJsonPath('data.job.customer_name', 'Neel Shah')
            ->assertJsonPath('data.job.final_amount', '14000.00')
            ->assertJsonPath('data.job.profit', '8000.00');

        $lead->refresh();
        $this->assertSame('converted', $lead->leadStatus->slug);
        $this->assertNotNull(Job::query()->where('lead_id', $lead->id)->first());
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'CONVERTED_TO_JOB',
            'entity_id' => $lead->id,
        ]);
    }
}
