<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Diagram;
use App\Models\Page;
use App\Models\ScriptProject;
use App\Models\User;
use App\Models\Workspace;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceCategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_category_management_supports_suggested_custom_updated_and_deleted_categories(): void
    {
        $owner = $this->createUser();
        $workspace = $this->createWorkspace($owner);

        $this->actingAs($owner)
            ->get(route('workspaces.categories.index', $workspace))
            ->assertOk()
            ->assertSeeText('Suggested Defaults')
            ->assertSeeText('Architecture');

        $this->actingAs($owner)->post(route('workspaces.categories.store', $workspace), [
            'built_in_key' => 'architecture',
        ])->assertRedirect(route('workspaces.categories.index', $workspace));

        $architecture = Category::query()
            ->where('workspace_id', (string) $workspace->id)
            ->where('name', 'Architecture')
            ->firstOrFail();

        $this->assertSame('Architecture', $architecture->name);
        $this->assertNotNull($architecture->description);

        $this->actingAs($owner)->post(route('workspaces.categories.store', $workspace), [
            'name' => 'Team Charter',
            'description' => 'How the team works together.',
            'color' => '#112233',
        ])->assertRedirect(route('workspaces.categories.index', $workspace));

        $customCategory = Category::query()
            ->where('workspace_id', (string) $workspace->id)
            ->where('name', 'Team Charter')
            ->firstOrFail();

        $this->actingAs($owner)->put(route('workspaces.categories.update', [$workspace, $customCategory]), [
            'name' => 'Team Processes',
            'description' => 'Documented workflows and rituals.',
            'color' => '#445566',
        ])->assertRedirect(route('workspaces.categories.index', $workspace));

        $this->assertDatabaseHas('categories', [
            'id' => $customCategory->id,
            'name' => 'Team Processes',
            'description' => 'Documented workflows and rituals.',
            'color' => '#445566',
        ]);

        $this->actingAs($owner)->delete(route('workspaces.categories.destroy', [$workspace, $customCategory]))
            ->assertRedirect(route('workspaces.categories.index', $workspace));

        $this->assertDatabaseMissing('categories', [
            'id' => $customCategory->id,
        ]);
    }

    public function test_create_and_edit_views_show_the_shared_category_selector_ui(): void
    {
        $owner = $this->createUser();
        $workspace = $this->createWorkspace($owner);
        $category = Category::create([
            'name' => 'Architecture',
            'description' => 'System boundaries and diagrams.',
            'color' => '#7c3aed',
            'workspace_id' => (string) $workspace->id,
        ]);

        $this->grantContentCreationPermissions($owner);

        $this->actingAs($owner)
            ->get(route('workspaces.pages.create', $workspace))
            ->assertOk()
            ->assertSeeText('Manage categories')
            ->assertSeeText('Create a New Category')
            ->assertSeeText('Architecture');

        $this->actingAs($owner)
            ->get(route('workspaces.diagrams.create', $workspace))
            ->assertOk()
            ->assertSeeText('Manage categories')
            ->assertSeeText('Create a New Category')
            ->assertSeeText('Architecture');

        $this->actingAs($owner)
            ->get(route('workspaces.scripts.create', $workspace))
            ->assertOk()
            ->assertSeeText('Manage categories')
            ->assertSeeText('Create a New Category')
            ->assertSeeText('Architecture');

        $page = new Page([
            'title' => 'Architecture Overview',
            'content' => '<p>Overview</p>',
            'content_type' => 'richtext',
            'workspace_id' => $workspace->id,
            'author_id' => $owner->id,
            'status' => 'published',
        ]);
        $page->saveAsRoot();
        $page->categories()->sync([(string) $category->id]);

        $diagram = Diagram::create([
            'title' => 'System Context',
            'workspace_id' => $workspace->id,
            'author_id' => $owner->id,
            'diagram_type' => 'mermaid',
            'diagram_data' => 'graph TD; A-->B',
        ]);
        $diagram->categories()->sync([(string) $category->id]);

        $script = ScriptProject::create([
            'workspace_id' => $workspace->id,
            'author_id' => $owner->id,
            'title' => 'Pilot',
            'status' => 'draft',
            'document' => ScriptProject::defaultDocument(),
        ]);
        $script->categories()->sync([(string) $category->id]);

        $this->actingAs($owner)
            ->get(route('workspaces.pages.edit', [$workspace, $page]))
            ->assertOk()
            ->assertSeeText('Manage categories')
            ->assertSeeText('Create a New Category');

        $this->actingAs($owner)
            ->get(route('workspaces.diagrams.edit', [$workspace, $diagram]))
            ->assertOk()
            ->assertSeeText('Manage categories')
            ->assertSeeText('Create a New Category');

        $this->actingAs($owner)
            ->get(route('workspaces.scripts.edit', [$workspace, $script]))
            ->assertOk()
            ->assertSeeText('Manage categories')
            ->assertSeeText('Create a New Category');
    }

    public function test_inline_category_creation_assigns_new_categories_to_pages_diagrams_and_scripts(): void
    {
        $owner = $this->createUser();
        $workspace = $this->createWorkspace($owner);

        $this->grantContentCreationPermissions($owner);

        $this->actingAs($owner)->post(route('workspaces.pages.store', $workspace), [
            'title' => 'Runbook',
            'content' => '<p>Deploy safely</p>',
            'content_type' => 'richtext',
            'status' => 'draft',
            'new_category_name' => 'Runbooks',
            'new_category_description' => 'Deployment and support procedures.',
            'new_category_color' => '#b45309',
        ])->assertRedirect();

        $page = Page::query()->where('title', 'Runbook')->firstOrFail();
        $runbooksCategory = Category::query()
            ->where('workspace_id', (string) $workspace->id)
            ->where('name', 'Runbooks')
            ->firstOrFail();
        $this->assertTrue($page->categories->contains('id', $runbooksCategory->id));

        $this->actingAs($owner)->post(route('workspaces.diagrams.store', $workspace), [
            'title' => 'Context Diagram',
            'diagram_type' => 'mermaid',
            'diagram_data' => 'graph TD; API-->DB',
            'new_category_name' => 'Architecture',
            'new_category_description' => 'Topology, boundaries, and technical diagrams.',
            'new_category_color' => '#7c3aed',
        ])->assertRedirect();

        $diagram = Diagram::query()->where('title', 'Context Diagram')->firstOrFail();
        $architectureCategory = Category::query()
            ->where('workspace_id', (string) $workspace->id)
            ->where('name', 'Architecture')
            ->firstOrFail();
        $this->assertTrue($diagram->categories->contains('id', $architectureCategory->id));

        $this->actingAs($owner)->post(route('workspaces.scripts.store', $workspace), [
            'title' => 'Pilot',
            'status' => 'draft',
            'new_category_name' => 'Product Specs',
            'new_category_description' => 'Narrative and planning documents.',
            'new_category_color' => '#db2777',
        ])->assertRedirect();

        $script = ScriptProject::query()->where('title', 'Pilot')->firstOrFail();
        $productSpecsCategory = Category::query()
            ->where('workspace_id', (string) $workspace->id)
            ->where('name', 'Product Specs')
            ->firstOrFail();
        $this->assertTrue($script->categories->contains('id', $productSpecsCategory->id));
    }

    protected function grantContentCreationPermissions(User $user): void
    {
        $this->seed(PermissionSeeder::class);

        $user->givePermissionTo(['pages.create', 'diagrams.create']);
    }

    protected function createUser(): User
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        $user->forceFill([
            'current_team_id' => $team?->id,
        ])->save();

        return $user->fresh();
    }

    protected function createWorkspace(User $owner): Workspace
    {
        return Workspace::create([
            'name' => 'Engineering Docs',
            'owner_id' => $owner->id,
            'is_public' => false,
        ]);
    }
}
