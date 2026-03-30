<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_navigation_follows_workspace_depth_first_order(): void
    {
        $owner = $this->createOwner();
        $workspace = Workspace::create([
            'name' => 'Docs',
            'owner_id' => $owner->id,
            'is_public' => false,
        ]);

        $rootAlpha = $this->createRootPage($workspace, $owner, 'Alpha');
        $childOne = $this->createChildPage($rootAlpha, $owner, 'Alpha Child One');
        $childTwo = $this->createChildPage($rootAlpha, $owner, 'Alpha Child Two');
        $rootBeta = $this->createRootPage($workspace, $owner, 'Beta');

        $this->actingAs($owner)
            ->get(route('workspaces.pages.show', [$workspace, $rootAlpha]))
            ->assertOk()
            ->assertSee('aria-label="Page navigation"', false)
            ->assertDontSee('aria-label="Previous page:', false)
            ->assertSee('aria-label="Next page: Alpha Child One"', false);

        $this->actingAs($owner)
            ->get(route('workspaces.pages.show', [$workspace, $childOne]))
            ->assertOk()
            ->assertSee('aria-label="Previous page: Alpha"', false)
            ->assertSee('aria-label="Next page: Alpha Child Two"', false);

        $this->actingAs($owner)
            ->get(route('workspaces.pages.show', [$workspace, $childTwo]))
            ->assertOk()
            ->assertSee('aria-label="Previous page: Alpha Child One"', false)
            ->assertSee('aria-label="Next page: Beta"', false);

        $this->actingAs($owner)
            ->get(route('workspaces.pages.show', [$workspace, $rootBeta]))
            ->assertOk()
            ->assertSee('aria-label="Previous page: Alpha Child Two"', false)
            ->assertDontSee('aria-label="Next page:', false);
    }

    public function test_page_navigation_is_hidden_when_no_adjacent_pages_exist(): void
    {
        $owner = $this->createOwner();
        $workspace = Workspace::create([
            'name' => 'Solo Docs',
            'owner_id' => $owner->id,
            'is_public' => false,
        ]);

        $page = $this->createRootPage($workspace, $owner, 'Lone Page');

        $this->actingAs($owner)
            ->get(route('workspaces.pages.show', [$workspace, $page]))
            ->assertOk()
            ->assertDontSee('aria-label="Page navigation"', false)
            ->assertDontSee('aria-label="Previous page:', false)
            ->assertDontSee('aria-label="Next page:', false);
    }

    public function test_workspace_home_page_shows_navigation_using_the_same_depth_first_order(): void
    {
        $owner = $this->createOwner();
        $workspace = Workspace::create([
            'name' => 'Docs',
            'owner_id' => $owner->id,
            'is_public' => false,
        ]);

        $homePage = $this->createRootPage($workspace, $owner, 'Home');
        $firstChild = $this->createChildPage($homePage, $owner, 'Home Child');
        $this->createRootPage($workspace, $owner, 'Beta');

        $workspace->update(['home_page_id' => $homePage->id]);

        $this->actingAs($owner)
            ->get(route('workspaces.show', $workspace))
            ->assertOk()
            ->assertSee('aria-label="Page navigation"', false)
            ->assertDontSee('aria-label="Previous page:', false)
            ->assertSee('aria-label="Next page: Home Child"', false)
            ->assertSee(route('workspaces.pages.show', [$workspace, $firstChild]), false);
    }

    public function test_markdown_pages_render_callouts_without_fatal_errors(): void
    {
        $owner = $this->createOwner();
        $workspace = Workspace::create([
            'name' => 'Docs',
            'owner_id' => $owner->id,
            'is_public' => false,
        ]);

        $page = $this->createRootPage(
            $workspace,
            $owner,
            'Callout Page',
            <<<MD
            :::note
            Remember this
            :::
            MD,
            'markdown'
        );

        $this->actingAs($owner)
            ->get(route('workspaces.pages.show', [$workspace, $page]))
            ->assertOk()
            ->assertSee('Remember this')
            ->assertSee('callout callout-note', false);
    }

    private function createOwner(): User
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $team = $owner->ownedTeams()->firstOrFail();

        $owner->forceFill(['current_team_id' => $team->id])->save();

        return $owner->fresh();
    }

    private function createRootPage(
        Workspace $workspace,
        User $author,
        string $title,
        ?string $content = null,
        string $contentType = 'richtext'
    ): Page
    {
        $page = new Page([
            'title' => $title,
            'content' => $content ?? ($contentType === 'markdown'
                ? $title.' content'
                : '<p>'.$title.' content</p>'),
            'content_type' => $contentType,
            'workspace_id' => $workspace->id,
            'author_id' => $author->id,
            'status' => 'published',
        ]);

        $page->saveAsRoot();

        return $page->fresh();
    }

    private function createChildPage(
        Page $parent,
        User $author,
        string $title,
        ?string $content = null,
        string $contentType = 'richtext'
    ): Page
    {
        $page = new Page([
            'title' => $title,
            'content' => $content ?? ($contentType === 'markdown'
                ? $title.' content'
                : '<p>'.$title.' content</p>'),
            'content_type' => $contentType,
            'workspace_id' => $parent->workspace_id,
            'author_id' => $author->id,
            'status' => 'published',
        ]);

        $page->appendToNode($parent)->save();

        return $page->fresh();
    }
}
