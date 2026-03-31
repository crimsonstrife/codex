<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    private array $crud = ['view', 'create', 'update', 'delete'];

    private array $extras = [
        'view_any', 'delete_any', 'restore', 'restore_any',
        'force_delete', 'force_delete_any', 'replicate', 'reorder',
        'export', 'import', 'manage', 'list',
    ];

    private array $flags = [
        'is-admin',
        'is-super-admin',
        'is-panel-user',
    ];

    private array $specials = [
        'admin.panel.access',
        'filament.access',
        'jetstream.access',
        'settings.manage',
        'settings.view',
        'health.view',
        'logs.manage',
        'workspaces.transition',
        'pages.publish',
        'diagrams.publish',
    ];

    private array $domains = [
        'workspaces',
        'pages',
        'scripts',
        'diagrams',
        'users',
        'roles',
        'permissions',
        'permission_sets',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $Permission = app(config('permission.models.permission'));

        foreach ($this->flags as $name) {
            $Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        foreach ($this->specials as $name) {
            $Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        foreach ($this->domains as $domain) {
            foreach (array_merge($this->crud, $this->extras) as $action) {
                $Permission::firstOrCreate(['name' => "{$domain}.{$action}", 'guard_name' => 'web']);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
