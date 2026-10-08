<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Permissions;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Permissions::ALL as $name) {
            Permission::query()->updateOrCreate(
                ['name' => $name],
                ['description' => str_replace('.', ' ', $name)],
            );
        }

        $owner = Role::query()->updateOrCreate(
            ['slug' => 'owner'],
            ['name' => 'Owner', 'description' => 'Full access to the CRM'],
        );
        $manager = Role::query()->updateOrCreate(
            ['slug' => 'manager'],
            ['name' => 'Manager', 'description' => 'Operational and financial access'],
        );
        $staff = Role::query()->updateOrCreate(
            ['slug' => 'staff'],
            ['name' => 'Staff', 'description' => 'Own attendance, salary, and profile'],
        );

        $all = Permission::query()->pluck('id', 'name');
        $owner->permissions()->sync($all->values()->all());
        $manager->permissions()->sync($all->only(Permissions::manager())->values()->all());
        $staff->permissions()->sync($all->only(Permissions::STAFF)->values()->all());
    }
}
