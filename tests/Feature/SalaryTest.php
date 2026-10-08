<?php

namespace Tests\Feature;

use App\Models\ExtraPay;
use App\Models\SalaryRecord;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithCrm;
use Tests\TestCase;

class SalaryTest extends TestCase
{
    use InteractsWithCrm, RefreshDatabase;

    public function test_salary_generation_includes_extra_pay_and_prevents_duplicates(): void
    {
        $owner = $this->makeUser('owner');
        $this->assignPin($owner);
        $token = $this->tokenFor($owner);
        $this->withToken($token)->postJson('/api/v1/security/verify-pin', ['pin' => '123456'])->assertOk();

        $staff = Staff::factory()->create([
            'basic_salary' => 20000,
            'status' => 'active',
        ]);

        ExtraPay::factory()->create([
            'staff_id' => $staff->id,
            'date' => now()->toDateString(),
            'amount' => 1500,
            'reason' => 'Overtime',
        ]);

        $payload = ['year' => now()->year, 'month' => now()->month];

        $this->withToken($token)->postJson('/api/v1/salary/generate', $payload)
            ->assertOk()
            ->assertJsonPath('data.0.extra_pay', '1500.00')
            ->assertJsonPath('data.0.final_payable', '21500.00');

        $this->withToken($token)->postJson('/api/v1/salary/generate', $payload)->assertOk();

        $this->assertSame(1, SalaryRecord::query()->where('staff_id', $staff->id)->count());
        $this->assertDatabaseHas('activity_logs', ['action' => 'SALARY_GENERATED']);
    }

    public function test_owner_can_mark_salary_paid(): void
    {
        $owner = $this->makeUser('owner');
        $this->assignPin($owner);
        $token = $this->tokenFor($owner);
        $this->withToken($token)->postJson('/api/v1/security/verify-pin', ['pin' => '123456'])->assertOk();

        $record = SalaryRecord::factory()->create(['status' => 'generated']);

        $this->withToken($token)->postJson('/api/v1/salary/'.$record->id.'/mark-paid')
            ->assertOk()
            ->assertJsonPath('data.status', 'paid');

        $this->assertDatabaseHas('activity_logs', ['action' => 'SALARY_PAID', 'entity_id' => $record->id]);
    }

    public function test_staff_can_only_see_their_own_salary(): void
    {
        $own = Staff::factory()->create();
        $other = Staff::factory()->create();
        $staffUser = $this->makeUser('staff', [
            'email' => 'staff1@detailinggarage.test',
            'staff_id' => $own->id,
        ]);
        $own->update(['user_id' => $staffUser->id]);

        $mine = SalaryRecord::factory()->create([
            'staff_id' => $own->id,
            'year' => now()->year,
            'month' => now()->month,
            'final_payable' => 18000,
        ]);
        $theirs = SalaryRecord::factory()->create([
            'staff_id' => $other->id,
            'year' => now()->year,
            'month' => now()->subMonth()->month === now()->month ? 1 : now()->subMonth()->month,
        ]);

        $token = $this->tokenFor($staffUser);

        $this->withToken($token)->getJson('/api/v1/salary/my')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $mine->id);

        $this->withToken($token)->getJson('/api/v1/salary/'.$theirs->id)
            ->assertForbidden()
            ->assertJsonPath('message', 'Forbidden');

        $this->withToken($token)->getJson('/api/v1/salary/'.$mine->id)
            ->assertForbidden();
    }
}
