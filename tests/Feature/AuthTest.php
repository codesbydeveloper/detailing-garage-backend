<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithCrm;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use InteractsWithCrm, RefreshDatabase;

    public function test_login_succeeds_with_valid_credentials(): void
    {
        $user = $this->makeUser('owner', [
            'name' => 'Rajesh Patel',
            'email' => 'owner@detailinggarage.test',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'owner@detailinggarage.test',
            'password' => 'Password@123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Login successful')
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonMissingPath('user.password');

        $this->assertNotEmpty($response->json('token'));
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'LOGIN',
        ]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $this->makeUser('owner', ['email' => 'owner@detailinggarage.test']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'owner@detailinggarage.test',
            'password' => 'wrong-password',
        ])->assertUnauthorized()
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('activity_logs', ['action' => 'LOGIN_FAILED']);
    }

    public function test_logout_revokes_the_token(): void
    {
        $user = $this->makeUser('manager', ['email' => 'manager@detailinggarage.test']);
        $token = $this->tokenFor($user);

        $this->withToken($token)->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'LOGOUT',
        ]);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);

        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_protected_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
        $this->getJson('/api/v1/leads')->assertUnauthorized();
    }

    public function test_authenticated_user_can_load_profile(): void
    {
        $user = $this->makeUser('staff', ['email' => 'staff1@detailinggarage.test']);

        $this->withToken($this->tokenFor($user))
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'staff1@detailinggarage.test')
            ->assertJsonMissingPath('data.password');
    }

    public function test_activity_log_never_stores_the_password(): void
    {
        $this->makeUser('owner', ['email' => 'owner@detailinggarage.test']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'owner@detailinggarage.test',
            'password' => 'Password@123',
        ])->assertOk();

        $this->assertFalse(
            ActivityLog::query()->where('new_values', 'like', '%Password@123%')->exists()
        );
    }
}
