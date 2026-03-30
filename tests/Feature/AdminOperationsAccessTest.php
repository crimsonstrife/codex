<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Health\Models\HealthCheckResultHistoryItem;
use Tests\TestCase;

class AdminOperationsAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_new_admin_operations_surfaces(): void
    {
        $this->seed([
            PermissionSeeder::class,
            RoleSeeder::class,
        ]);

        $batch = (string) Str::uuid();

        HealthCheckResultHistoryItem::create([
            'check_name' => 'database',
            'check_label' => 'Database',
            'status' => 'ok',
            'notification_message' => null,
            'short_summary' => 'Connected',
            'meta' => ['connection_name' => 'sqlite'],
            'ended_at' => now(),
            'batch' => $batch,
        ]);

        HealthCheckResultHistoryItem::create([
            'check_name' => 'security-advisories',
            'check_label' => 'Security Advisories',
            'status' => 'failed',
            'notification_message' => 'Security advisories found for `laravel/framework`',
            'short_summary' => 'Attention required',
            'meta' => [
                'laravel/framework' => [
                    [
                        'advisoryId' => 'PKSA-123',
                        'packageName' => 'laravel/framework',
                        'title' => 'Remote code execution in routing pipeline',
                        'link' => 'https://github.com/advisories/GHSA-test',
                        'cve' => 'CVE-2026-1234',
                        'affectedVersions' => '<11.4.1',
                        'reportedAt' => '2026-03-20T14:30:00+00:00',
                    ],
                ],
            ],
            'ended_at' => now(),
            'batch' => $batch,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('SuperAdmin');

        $routes = [
            '/admin/system-health' => 'System Health',
            '/admin/logs' => 'Logs',
            '/admin/roles' => 'Roles',
            '/admin/permissions' => 'Permissions',
            '/admin/permission-sets' => 'Permission Sets',
            '/admin/settings/authentication' => 'Authentication',
        ];

        foreach ($routes as $uri => $text) {
            $this->actingAs($admin)
                ->get($uri)
                ->assertOk()
                ->assertSee($text);
        }

        $this->actingAs($admin)
            ->get('/admin/system-health')
            ->assertOk()
            ->assertSee('laravel/framework')
            ->assertSee('Remote code execution in routing pipeline')
            ->assertSee('CVE-2026-1234');

        $this->get('/status')
            ->assertOk()
            ->assertSee('Laravel Health')
            ->assertSee('Database')
            ->assertSee('laravel/framework')
            ->assertSee('Remote code execution in routing pipeline')
            ->assertSee('CVE-2026-1234');
    }

    public function test_panel_user_without_specific_permissions_cannot_access_new_admin_operations_surfaces(): void
    {
        $this->seed(PermissionSeeder::class);

        $user = User::factory()->create();
        $user->givePermissionTo([
            'admin.panel.access',
            'filament.access',
        ]);

        $routes = [
            '/admin/system-health',
            '/admin/logs',
            '/admin/roles',
            '/admin/permissions',
            '/admin/permission-sets',
            '/admin/settings/authentication',
        ];

        foreach ($routes as $uri) {
            $this->actingAs($user)
                ->get($uri)
                ->assertForbidden();
        }
    }
}
