<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $permissionClass = app(config('permission.models.permission'));
        $roleClass = app(config('permission.models.role'));

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['health.view', 'logs.manage'] as $permissionName) {
            $permissionClass::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        $baselinePermissions = [
            'health.view',
            'logs.manage',
            'permission_sets.view',
            'permission_sets.manage',
            'users.delete',
        ];

        foreach (['SuperAdmin', 'Admin'] as $roleName) {
            $role = $roleClass::query()
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->first();

            if (! $role) {
                continue;
            }

            $role->givePermissionTo($baselinePermissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
