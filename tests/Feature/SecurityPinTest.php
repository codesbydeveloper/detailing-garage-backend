<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\BusinessSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\InteractsWithCrm;
use Tests\TestCase;

class SecurityPinTest extends TestCase
{
    use InteractsWithCrm, RefreshDatabase;

    public function test_correct_pin_verifies_and_is_logged_without_the_pin_value(): void
    {
        $owner = $this->makeUser('owner', ['email' => 'owner@detailinggarage.test']);
        $this->assignPin($owner);
        $token = $this->tokenFor($owner);

        $this->withToken($token)->postJson('/api/v1/security/verify-pin', ['pin' => '123456'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'PIN verified')
            ->assertJsonStructure(['expires_at']);

        $log = ActivityLog::query()->where('action', 'PIN_VERIFIED')->first();
        $this->assertNotNull($log);
        $this->assertStringNotContainsString('123456', json_encode($log->toArray()));
    }

    public function test_wrong_pin_is_rejected_and_logged(): void
    {
        $owner = $this->makeUser('owner');
        $this->assignPin($owner);

        $this->withToken($this->tokenFor($owner))
            ->postJson('/api/v1/security/verify-pin', ['pin' => '000000'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'PIN_INVALID');

        $this->assertDatabaseHas('activity_logs', ['action' => 'PIN_FAILED']);
    }

    public function test_pin_locks_after_five_failed_attempts(): void
    {
        $owner = $this->makeUser('owner');
        $this->assignPin($owner);
        $token = $this->tokenFor($owner);

        for ($attempt = 1; $attempt <= 4; $attempt++) {
            $this->withToken($token)
                ->postJson('/api/v1/security/verify-pin', ['pin' => '111111'])
                ->assertStatus(422)
                ->assertJsonPath('code', 'PIN_INVALID');
        }

        $this->withToken($token)
            ->postJson('/api/v1/security/verify-pin', ['pin' => '111111'])
                ->assertStatus(429)
                ->assertJsonPath('code', 'PIN_LOCKED');

        $this->withToken($token)
            ->postJson('/api/v1/security/verify-pin', ['pin' => '123456'])
            ->assertStatus(429)
            ->assertJsonPath('code', 'PIN_LOCKED');
    }

    public function test_pin_session_expires(): void
    {
        BusinessSetting::query()->create([
            'business_name' => 'Detailing Garage',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'timezone' => 'Asia/Kolkata',
        ]);

        $owner = $this->makeUser('owner');
        $this->assignPin($owner);
        $token = $this->tokenFor($owner);

        $this->withToken($token)->postJson('/api/v1/security/verify-pin', ['pin' => '123456'])->assertOk();
        $this->travel(16)->minutes();

        $this->withToken($token)->getJson('/api/v1/dashboard')
            ->assertForbidden()
            ->assertJsonPath('code', 'PIN_EXPIRED');
    }

    public function test_changing_pin_invalidates_the_verification_session(): void
    {
        $owner = $this->makeUser('owner');
        $this->assignPin($owner, '123456');
        $token = $this->tokenFor($owner);

        $this->withToken($token)->postJson('/api/v1/security/verify-pin', ['pin' => '123456'])->assertOk();

        $this->withToken($token)->putJson('/api/v1/security/pin', [
            'current_pin' => '123456',
            'new_pin' => '987654',
            'new_pin_confirmation' => '987654',
        ])->assertOk();

        $this->assertDatabaseHas('activity_logs', ['action' => 'PIN_CHANGED']);
        $this->assertFalse(
            ActivityLog::query()->where('action', 'PIN_CHANGED')->where('description', 'like', '%987654%')->exists()
        );

        $this->withToken($token)->getJson('/api/v1/dashboard')
            ->assertForbidden()
            ->assertJsonPath('code', 'PIN_REQUIRED');

        Cache::flush();

        $this->withToken($token)->postJson('/api/v1/security/verify-pin', ['pin' => '987654'])
            ->assertOk()
            ->assertJsonPath('message', 'PIN verified');
    }

    public function test_sensitive_routes_require_pin_verification(): void
    {
        $owner = $this->makeUser('owner');
        $token = $this->tokenFor($owner);

        foreach (['/api/v1/dashboard', '/api/v1/reports/financial', '/api/v1/activity-logs'] as $uri) {
            $this->withToken($token)->getJson($uri)
                ->assertForbidden()
                ->assertJsonPath('code', 'PIN_REQUIRED')
                ->assertJsonPath('message', 'Security PIN verification required');
        }
    }
}
