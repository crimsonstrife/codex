<?php

namespace Tests\Feature;

use App\Models\AppToken;
use App\Models\Page;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Forge integration API endpoints:
 *   GET /api/v1/workspaces
 *   GET /api/v1/pages/search
 */
class ForgeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspaces_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/workspaces');

        $response->assertStatus(401);
    }

    public function test_workspaces_endpoint_returns_json_contract(): void
    {
        $user = User::factory()->create([
            'forge_user_id' => 'forge-user-1',
        ]);

        Workspace::create([
            'name' => 'Test Workspace',
            'owner_id' => $user->id,
            'is_public' => true,
        ]);

        $response = $this->withHeaders($this->appTokenHeaders())
            ->getJson('/api/v1/workspaces?for_forge_user_id='.$user->forge_user_id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'description',
                        'forge_project_id',
                        'forge_project_key',
                    ],
                ],
            ]);
    }

    public function test_workspaces_endpoint_excludes_private_workspaces_user_cannot_see(): void
    {
        $owner = User::factory()->create([
            'forge_user_id' => 'forge-owner',
        ]);
        $otherUser = User::factory()->create([
            'forge_user_id' => 'forge-other',
        ]);

        Workspace::create([
            'name' => 'Private Workspace',
            'owner_id' => $owner->id,
            'is_public' => false,
        ]);

        Workspace::create([
            'name' => 'Public Workspace',
            'owner_id' => $owner->id,
            'is_public' => true,
        ]);

        $response = $this->withHeaders($this->appTokenHeaders())
            ->getJson('/api/v1/workspaces?for_forge_user_id='.$otherUser->forge_user_id);

        $response->assertStatus(200);

        $names = collect($response->json('data'))->pluck('name')->all();
        $this->assertContains('Public Workspace', $names);
        $this->assertNotContains('Private Workspace', $names);
    }

    public function test_workspaces_endpoint_includes_private_workspaces_user_owns(): void
    {
        $owner = User::factory()->create([
            'forge_user_id' => 'forge-owner',
        ]);

        Workspace::create([
            'name' => 'My Private Workspace',
            'owner_id' => $owner->id,
            'is_public' => false,
        ]);

        $response = $this->withHeaders($this->appTokenHeaders())
            ->getJson('/api/v1/workspaces?for_forge_user_id='.$owner->forge_user_id);

        $response->assertStatus(200);
        $names = collect($response->json('data'))->pluck('name')->all();
        $this->assertContains('My Private Workspace', $names);
    }

    public function test_workspaces_endpoint_supports_search_filter(): void
    {
        $user = User::factory()->create([
            'forge_user_id' => 'forge-search',
        ]);

        Workspace::create(['name' => 'Alpha Docs', 'owner_id' => $user->id, 'is_public' => true]);
        Workspace::create(['name' => 'Beta Notes', 'owner_id' => $user->id, 'is_public' => true]);

        $response = $this->withHeaders($this->appTokenHeaders())
            ->getJson('/api/v1/workspaces?for_forge_user_id='.$user->forge_user_id.'&search=Alpha');

        $response->assertStatus(200);
        $names = collect($response->json('data'))->pluck('name')->all();
        $this->assertContains('Alpha Docs', $names);
        $this->assertNotContains('Beta Notes', $names);
    }

    public function test_pages_search_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/pages/search');

        $response->assertStatus(401);
    }

    public function test_pages_search_endpoint_returns_json_contract(): void
    {
        $user = User::factory()->create([
            'forge_user_id' => 'forge-pages',
        ]);
        $workspace = Workspace::create([
            'name' => 'Search WS',
            'owner_id' => $user->id,
            'is_public' => true,
        ]);
        Page::create([
            'title' => 'Hello World',
            'workspace_id' => $workspace->id,
            'author_id' => $user->id,
        ]);

        $response = $this->withHeaders($this->appTokenHeaders())
            ->getJson('/api/v1/pages/search?q=Hello&for_forge_user_id='.$user->forge_user_id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'url',
                        'workspace_id',
                        'workspace_slug',
                        'workspace_name',
                    ],
                ],
            ]);
    }

    public function test_pages_search_endpoint_excludes_pages_in_private_workspaces(): void
    {
        $owner = User::factory()->create([
            'forge_user_id' => 'forge-owner',
        ]);
        $otherUser = User::factory()->create([
            'forge_user_id' => 'forge-other',
        ]);

        $privateWorkspace = Workspace::create([
            'name' => 'Private WS',
            'owner_id' => $owner->id,
            'is_public' => false,
        ]);
        Page::create([
            'title' => 'Secret Page',
            'workspace_id' => $privateWorkspace->id,
            'author_id' => $owner->id,
        ]);

        $publicWorkspace = Workspace::create([
            'name' => 'Public WS',
            'owner_id' => $owner->id,
            'is_public' => true,
        ]);
        Page::create([
            'title' => 'Public Page',
            'workspace_id' => $publicWorkspace->id,
            'author_id' => $owner->id,
        ]);

        $response = $this->withHeaders($this->appTokenHeaders())
            ->getJson('/api/v1/pages/search?for_forge_user_id='.$otherUser->forge_user_id);

        $response->assertStatus(200);
        $titles = collect($response->json('data'))->pluck('title')->all();
        $this->assertContains('Public Page', $titles);
        $this->assertNotContains('Secret Page', $titles);
    }

    public function test_pages_search_endpoint_returns_empty_for_inaccessible_workspace_id(): void
    {
        $owner = User::factory()->create([
            'forge_user_id' => 'forge-owner',
        ]);
        $otherUser = User::factory()->create([
            'forge_user_id' => 'forge-other',
        ]);

        $privateWorkspace = Workspace::create([
            'name' => 'Private WS',
            'owner_id' => $owner->id,
            'is_public' => false,
        ]);

        $response = $this->withHeaders($this->appTokenHeaders())
            ->getJson('/api/v1/pages/search?workspace_id='.$privateWorkspace->id.'&for_forge_user_id='.$otherUser->forge_user_id);

        $response->assertStatus(200)
            ->assertJson(['data' => []]);
    }

    public function test_pages_search_endpoint_filters_by_title_query(): void
    {
        $user = User::factory()->create([
            'forge_user_id' => 'forge-filter',
        ]);
        $workspace = Workspace::create([
            'name' => 'WS',
            'owner_id' => $user->id,
            'is_public' => true,
        ]);
        Page::create(['title' => 'Getting Started', 'workspace_id' => $workspace->id, 'author_id' => $user->id]);
        Page::create(['title' => 'Advanced Config', 'workspace_id' => $workspace->id, 'author_id' => $user->id]);

        $response = $this->withHeaders($this->appTokenHeaders())
            ->getJson('/api/v1/pages/search?q=Getting&for_forge_user_id='.$user->forge_user_id);

        $response->assertStatus(200);
        $titles = collect($response->json('data'))->pluck('title')->all();
        $this->assertContains('Getting Started', $titles);
        $this->assertNotContains('Advanced Config', $titles);
    }

    /**
     * @return array<string, string>
     */
    private function appTokenHeaders(array $abilities = ['*']): array
    {
        ['plaintext' => $plaintext] = AppToken::generate('PHPUnit Forge API', $abilities);

        return [
            'Authorization' => 'Bearer '.$plaintext,
        ];
    }
}
