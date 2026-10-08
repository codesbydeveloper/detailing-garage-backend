<?php

namespace Tests\Feature;

use App\Models\ExpenseCategory;
use App\Models\Staff;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithCrm;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use InteractsWithCrm, RefreshDatabase;

    public function test_office_personal_and_vendor_payments_are_recorded(): void
    {
        $owner = $this->makeUser('owner');
        $this->assignPin($owner);
        $token = $this->tokenFor($owner);
        $this->withToken($token)->postJson('/api/v1/security/verify-pin', ['pin' => '123456'])->assertOk();

        $category = ExpenseCategory::query()->create(['name' => 'Electricity', 'status' => 'active']);
        $staff = Staff::factory()->create(['full_name' => 'Rajesh Patel']);
        $vendor = Vendor::factory()->create(['name' => 'AutoCare Supplies']);

        $this->withToken($token)->postJson('/api/v1/office-expenses', [
            'date' => now()->toDateString(),
            'expense_category_id' => $category->id,
            'expense' => 'Electricity',
            'price' => 4200,
        ])->assertCreated()
            ->assertJsonPath('data.price', '4200.00');

        $this->assertDatabaseHas('activity_logs', ['action' => 'OFFICE_EXPENSE_CREATED']);

        $this->withToken($token)->postJson('/api/v1/personal-expenses', [
            'date' => now()->toDateString(),
            'expense' => 'Personal Travel',
            'price' => 5000,
            'staff_id' => $staff->id,
        ])->assertCreated()
            ->assertJsonPath('data.staff_id', $staff->id);

        $this->assertDatabaseHas('activity_logs', ['action' => 'PERSONAL_EXPENSE_CREATED']);

        $this->withToken($token)->postJson('/api/v1/vendor-payments', [
            'vendor_id' => $vendor->id,
            'date' => now()->toDateString(),
            'amount' => 15000,
            'purpose' => 'Chemicals',
            'payment_method' => 'bank_transfer',
        ])->assertCreated()
            ->assertJsonPath('data.amount', '15000.00');

        $this->assertDatabaseHas('activity_logs', ['action' => 'VENDOR_PAYMENT_CREATED']);
    }
}
