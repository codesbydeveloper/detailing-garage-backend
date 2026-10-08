<?php

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithCrm;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use InteractsWithCrm, RefreshDatabase;

    private function staffUser(): array
    {
        $staff = Staff::factory()->create();
        $user = $this->makeUser('staff', [
            'email' => 'staff1@detailinggarage.test',
            'staff_id' => $staff->id,
        ]);
        $staff->update(['user_id' => $user->id]);

        return [$user->fresh(), $this->tokenFor($user)];
    }

    public function test_staff_can_clock_in_and_out_and_minutes_are_calculated(): void
    {
        [$user, $token] = $this->staffUser();

        $this->travelTo(now()->setTime(9, 0));

        $this->withToken($token)->postJson('/api/v1/attendance/clock-in')->assertCreated();
        $this->assertDatabaseHas('activity_logs', ['action' => 'CLOCK_IN', 'user_id' => $user->id]);

        $this->withToken($token)->postJson('/api/v1/attendance/clock-in')
            ->assertStatus(422);

        $this->travelTo(now()->setTime(18, 0));

        $this->withToken($token)->postJson('/api/v1/attendance/clock-out')
            ->assertOk()
            ->assertJsonPath('data.total_minutes', 540);

        $this->assertDatabaseHas('activity_logs', ['action' => 'CLOCK_OUT', 'user_id' => $user->id]);

        $this->withToken($token)->getJson('/api/v1/attendance/my')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_staff_cannot_view_company_attendance(): void
    {
        [, $token] = $this->staffUser();

        $this->withToken($token)->getJson('/api/v1/attendance')
            ->assertForbidden()
            ->assertJsonPath('message', 'Forbidden');
    }
}
