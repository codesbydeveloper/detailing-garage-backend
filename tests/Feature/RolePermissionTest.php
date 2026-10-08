<?php

namespace Tests\Feature;

use App\Models\LeadStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithCrm;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use InteractsWithCrm, RefreshDatabase;

    public function test_owner_can_access_operational_routes(): void
    {
        $owner = $this->makeUser('owner', ['email' => 'owner@detailinggarage.test']);

        $this->withToken($this->tokenFor($owner))
            ->getJson('/api/v1/leads')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_manager_can_access_operational_routes_but_not_settings_management(): void
    {
        $manager = $this->makeUser('manager', ['email' => 'manager@detailinggarage.test']);
        $token = $this->tokenFor($manager);
        $this->assignPin($manager, '654321');

        $this->withToken($token)->getJson('/api/v1/leads')->assertOk();
        $this->withToken($token)->postJson('/api/v1/security/verify-pin', ['pin' => '654321'])->assertOk();

        $this->withToken($token)->putJson('/api/v1/settings', [
            'business_name' => 'Should Not Save',
        ])->assertForbidden()
            ->assertJsonPath('message', 'Forbidden');
    }

    public function test_staff_cannot_access_company_modules(): void
    {
        LeadStatus::query()->create([
            'name' => 'New',
            'slug' => 'new',
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $staff = $this->makeUser('staff', ['email' => 'staff1@detailinggarage.test']);
        $token = $this->tokenFor($staff);

        foreach ([
            '/api/v1/dashboard',
            '/api/v1/leads',
            '/api/v1/jobs',
            '/api/v1/office-expenses',
            '/api/v1/personal-expenses',
            '/api/v1/vendors',
            '/api/v1/vendor-payments',
            '/api/v1/staff',
            '/api/v1/reports/financial',
            '/api/v1/reports/leads',
            '/api/v1/activity-logs',
            '/api/v1/settings',
            '/api/v1/salary',
            '/api/v1/extra-pay',
        ] as $uri) {
            $this->withToken($token)->getJson($uri)
                ->assertForbidden()
                ->assertJsonPath('message', 'Forbidden')
                ->assertJsonMissingPath('code');
        }
    }
}
