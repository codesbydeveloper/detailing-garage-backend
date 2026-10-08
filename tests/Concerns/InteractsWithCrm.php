<?php

namespace Tests\Concerns;

use App\Models\SecurityPin;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Hash;

trait InteractsWithCrm
{
    protected function seedAccess(): void
    {
        $this->seed(RolePermissionSeeder::class);
    }

    protected function makeUser(string $role, array $overrides = []): User
    {
        $this->seedAccess();

        return User::factory()->create(array_merge([
            'role' => $role,
            'status' => 'active',
            'password' => 'Password@123',
        ], $overrides));
    }

    protected function tokenFor(User $user): string
    {
        return $user->createToken('phpunit')->plainTextToken;
    }

    protected function assignPin(User $user, string $pin = '123456'): void
    {
        SecurityPin::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'pin_hash' => Hash::make($pin),
                'pin_updated_at' => now(),
                'failed_attempts' => 0,
                'locked_until' => null,
            ],
        );
    }
}
