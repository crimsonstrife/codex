<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use App\Settings\CodexSettings;
use App\Settings\ForgeSettings;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CodexSettingsUiTest extends TestCase
{
    use RefreshDatabase;

    protected function createPanelUser(): User
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        $user->forceFill([
            'current_team_id' => $team?->id,
        ])->save();

        return $user->fresh();
    }

    public function test_page_create_screen_uses_the_configured_default_content_type(): void
    {
        $this->seed(PermissionSeeder::class);

        CodexSettings::fake([
            'defaultPageContentType' => 'richtext',
            'drawioUrl' => 'https://embed.diagrams.net',
        ]);

        $user = $this->createPanelUser();
        $user->givePermissionTo('pages.create');

        $workspace = Workspace::create([
            'name' => 'Docs',
            'owner_id' => $user->id,
            'is_public' => true,
        ]);

        $this->actingAs($user)
            ->get(route('workspaces.pages.create', $workspace))
            ->assertOk()
            ->assertSee('value="richtext" selected', false);
    }

    public function test_diagram_create_screen_uses_the_configured_drawio_url(): void
    {
        $this->seed(PermissionSeeder::class);

        CodexSettings::fake([
            'defaultPageContentType' => 'markdown',
            'drawioUrl' => 'https://drawio.example.com',
        ]);

        $user = $this->createPanelUser();
        $user->givePermissionTo('diagrams.create');

        $workspace = Workspace::create([
            'name' => 'Architecture',
            'owner_id' => $user->id,
            'is_public' => true,
        ]);

        $this->actingAs($user)
            ->get(route('workspaces.diagrams.create', $workspace))
            ->assertOk()
            ->assertSee('https://drawio.example.com', false);
    }

    public function test_login_page_only_shows_forge_sso_when_enabled_and_configured(): void
    {
        config()->set('codex.forge.client_id', 'forge-client');
        config()->set('codex.forge.client_secret', 'forge-secret');
        config()->set('codex.forge.redirect_uri', 'https://codex.example.com/auth/forge/callback');

        ForgeSettings::fake([
            'enabled' => true,
            'url' => 'https://forge.example.com',
            'disableTlsVerification' => false,
        ]);

        $this->get('/login')
            ->assertOk()
            ->assertSee('Sign in with Forge');

        ForgeSettings::fake([
            'enabled' => false,
            'url' => 'https://forge.example.com',
            'disableTlsVerification' => false,
        ]);

        $this->get('/login')
            ->assertOk()
            ->assertDontSee('Sign in with Forge');
    }
}
