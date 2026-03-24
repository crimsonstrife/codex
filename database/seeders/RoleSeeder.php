<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $Role = app(config('permission.models.role'));
        $Permission = app(config('permission.models.permission'));

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $super        = $Role::firstOrCreate(['name' => 'SuperAdmin',   'guard_name' => 'web']);
        $admin        = $Role::firstOrCreate(['name' => 'Admin',        'guard_name' => 'web']);
        $editor       = $Role::firstOrCreate(['name' => 'Editor',       'guard_name' => 'web']);
        $contributor  = $Role::firstOrCreate(['name' => 'Contributor',  'guard_name' => 'web']);
        $viewer       = $Role::firstOrCreate(['name' => 'Viewer',       'guard_name' => 'web']);

        $get = static fn(array $names) => $Permission::query()
            ->whereIn('name', $names)
            ->pluck('name')
            ->all();

        $super->syncPermissions($get([
            'is-super-admin',
            'is-admin',
            'admin.panel.access',
            'filament.access',
            'settings.manage',
            'workspaces.view', 'workspaces.create', 'workspaces.update', 'workspaces.delete', 'workspaces.manage',
            'pages.view', 'pages.create', 'pages.update', 'pages.delete', 'pages.publish',
            'diagrams.view', 'diagrams.create', 'diagrams.update', 'diagrams.delete', 'diagrams.publish',
            'users.view', 'users.create', 'users.update',
            'roles.view', 'roles.manage',
            'permissions.view', 'permissions.manage',
        ]));

        $admin->syncPermissions($get([
            'is-admin',
            'admin.panel.access',
            'filament.access',
            'settings.manage',
            'workspaces.view', 'workspaces.create', 'workspaces.update', 'workspaces.delete', 'workspaces.manage',
            'pages.view', 'pages.create', 'pages.update', 'pages.delete', 'pages.publish',
            'diagrams.view', 'diagrams.create', 'diagrams.update', 'diagrams.delete', 'diagrams.publish',
            'users.view', 'users.create', 'users.update',
            'roles.view', 'roles.manage',
            'permissions.view', 'permissions.manage',
        ]));

        $editor->syncPermissions($get([
            'workspaces.view', 'workspaces.create', 'workspaces.update',
            'pages.view', 'pages.create', 'pages.update', 'pages.delete', 'pages.publish',
            'diagrams.view', 'diagrams.create', 'diagrams.update', 'diagrams.delete', 'diagrams.publish',
        ]));

        $contributor->syncPermissions($get([
            'workspaces.view',
            'pages.view', 'pages.create', 'pages.update',
            'diagrams.view', 'diagrams.create', 'diagrams.update',
        ]));

        $viewer->syncPermissions($get([
            'workspaces.view',
            'pages.view',
            'diagrams.view',
        ]));

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $firstUser = User::query()->oldest('id')->first();
        if ($firstUser && ! $firstUser->hasRole('SuperAdmin')) {
            $firstUser->assignRole('SuperAdmin');
        }
    }
}
