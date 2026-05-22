<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = (string) config('auth.defaults.guard', 'web');

        $permissions = [
            'admin.dashboard.view',
            'parcels.read',
            'parcels.write',
            'trips.read',
            'trips.write',
            'pricing.read',
            'pricing.write',
            'tickets.read',
            'tickets.write',
            'scan.write',
        ];

        foreach ($permissions as $name) {
            Permission::findOrCreate($name, $guard);
        }

        $rolePermissions = [
            'admin_super' => $permissions,
            'driver' => ['trips.read', 'scan.write'],
            'customer' => ['parcels.read', 'parcels.write', 'tickets.read', 'tickets.write'],
            'hub_staff' => ['parcels.read', 'scan.write', 'trips.read'],
            'hub_manager' => ['parcels.read', 'parcels.write', 'scan.write', 'trips.read', 'trips.write'],
            'support_admin' => ['tickets.read', 'tickets.write', 'parcels.read'],
            'finance_admin' => ['pricing.read', 'pricing.write', 'parcels.read'],
        ];

        foreach ($rolePermissions as $roleName => $allowed) {
            $role = Role::findOrCreate($roleName, $guard);
            $role->syncPermissions($allowed);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
