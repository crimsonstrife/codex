<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_general_settings_update_preserves_existing_forge_link_when_fields_are_omitted(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::create([
            'name' => 'Docs',
            'description' => 'Original description',
            'owner_id' => $owner->id,
            'is_public' => true,
            'forge_project_id' => 'forge-123',
            'forge_project_key' => 'CRIMSON',
        ]);

        $response = $this->actingAs($owner)->put(route('workspaces.update', $workspace), [
            'name' => 'Docs Updated',
            'description' => 'Updated description',
            'color' => '#112233',
            'icon' => 'D',
            'is_public' => '1',
        ]);

        $response->assertRedirect(route('workspaces.show', $workspace));

        $workspace->refresh();

        $this->assertSame('Docs Updated', $workspace->name);
        $this->assertSame('Updated description', $workspace->description);
        $this->assertSame('forge-123', $workspace->forge_project_id);
        $this->assertSame('CRIMSON', $workspace->forge_project_key);
        $this->assertTrue($workspace->is_public);
    }

    public function test_forge_only_update_preserves_visibility_when_checkbox_is_not_submitted(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::create([
            'name' => 'Docs',
            'owner_id' => $owner->id,
            'is_public' => true,
            'forge_project_id' => 'forge-123',
            'forge_project_key' => 'CRIMSON',
        ]);

        $response = $this->actingAs($owner)->put(route('workspaces.update', $workspace), [
            'name' => 'Docs',
            'forge_project_id' => '',
            'forge_project_key' => '',
        ]);

        $response->assertRedirect(route('workspaces.show', $workspace));

        $workspace->refresh();

        $this->assertNull($workspace->forge_project_id);
        $this->assertNull($workspace->forge_project_key);
        $this->assertTrue($workspace->is_public);
    }
}
