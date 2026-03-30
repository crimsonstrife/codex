<?php

namespace Tests\Feature;

use App\Models\AppToken;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAppTokenResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_app_tokens_index(): void
    {
        $this->seed([
            PermissionSeeder::class,
            RoleSeeder::class,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('SuperAdmin');

        AppToken::generate('Forge', ['*']);

        $this->withoutExceptionHandling();

        $this->actingAs($admin)
            ->get('/admin/app-tokens')
            ->assertOk()
            ->assertSee('App Tokens')
            ->assertSee('Forge');
    }
}
