<?php

namespace Tests\Feature;

use App\Models\Job;
use App\Models\WorkType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithCrm;
use Tests\TestCase;

class JobTest extends TestCase
{
    use InteractsWithCrm, RefreshDatabase;

    private function workType(): WorkType
    {
        return WorkType::query()->create([
            'name' => 'Ceramic Coating',
            'status' => 'active',
            'default_price' => 15000,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(WorkType $workType, array $overrides = []): array
    {
        return array_merge([
            'date' => now()->toDateString(),
            'car_name' => 'BMW X5 Black',
            'car_number' => 'GJ01AB1234',
            'customer_name' => 'Kiran Patel',
            'customer_mobile' => '9876503333',
            'work_type_id' => $workType->id,
            'income' => 15000,
            'discount' => 1000,
            'product_charge' => 3500,
            'labour_charge' => 2500,
            'profit' => 1,
            'pending_pay' => 1,
            'final_amount' => 1,
            'payment_status' => 'paid',
        ], $overrides);
    }

    public function test_job_financials_are_calculated_on_the_server(): void
    {
        $owner = $this->makeUser('owner');
        $token = $this->tokenFor($owner);

        $response = $this->withToken($token)->postJson('/api/v1/jobs', $this->payload($this->workType()))
            ->assertCreated()
            ->assertJsonPath('data.final_amount', '14000.00')
            ->assertJsonPath('data.profit', '8000.00')
            ->assertJsonPath('data.pending_pay', '14000.00')
            ->assertJsonPath('data.payment_status', 'pending');

        $jobId = $response->json('data.id');

        $this->withToken($token)->putJson('/api/v1/jobs/'.$jobId, [
            'income' => 20000,
            'discount' => 0,
            'profit' => 99,
        ])->assertOk()
            ->assertJsonPath('data.final_amount', '20000.00')
            ->assertJsonPath('data.profit', '14000.00');

        $this->withToken($token)->deleteJson('/api/v1/jobs/'.$jobId)->assertOk();
        $this->assertSoftDeleted('jobs', ['id' => $jobId]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'DELETE', 'module' => 'jobs', 'entity_id' => $jobId]);
    }

    public function test_payments_recalculate_pending_and_status(): void
    {
        $owner = $this->makeUser('owner');
        $token = $this->tokenFor($owner);
        $job = Job::factory()->create(['work_type_id' => $this->workType()->id]);

        $this->withToken($token)->postJson('/api/v1/jobs/'.$job->id.'/payments', [
            'payment_date' => now()->toDateString(),
            'amount' => 10000,
            'payment_method' => 'upi',
        ])->assertCreated();

        $job->refresh();
        $this->assertSame('10000.00', $job->amount_paid);
        $this->assertSame('4000.00', $job->pending_pay);
        $this->assertSame('partial', $job->payment_status);
        $this->assertDatabaseHas('activity_logs', ['action' => 'PAYMENT_CREATED', 'module' => 'jobs']);

        $this->withToken($token)->postJson('/api/v1/jobs/'.$job->id.'/payments', [
            'payment_date' => now()->toDateString(),
            'amount' => 4000,
            'payment_method' => 'cash',
        ])->assertCreated();

        $job->refresh();
        $this->assertSame('14000.00', $job->amount_paid);
        $this->assertSame('0.00', $job->pending_pay);
        $this->assertSame('paid', $job->payment_status);
    }
}
