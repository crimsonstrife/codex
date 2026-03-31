<?php

namespace Tests\Feature;

use App\Models\Diagram;
use App\Models\Page;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillDiagramEmbedsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_backfills_embedded_diagram_relationships_for_existing_pages(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $workspace = Workspace::create([
            'name' => 'Docs',
            'owner_id' => $owner->id,
            'is_public' => false,
        ]);

        $diagram = Diagram::create([
            'title' => 'Release Flow',
            'workspace_id' => $workspace->id,
            'author_id' => $owner->id,
            'diagram_type' => 'mermaid',
            'diagram_data' => 'graph TD; A-->B;',
        ]);

        $page = Page::create([
            'title' => 'Deployment',
            'content' => '{{diagram:'.$diagram->id.'}}',
            'content_type' => 'markdown',
            'workspace_id' => $workspace->id,
            'author_id' => $owner->id,
            'status' => 'draft',
        ]);

        $this->assertDatabaseCount('page_diagram_embeds', 0);

        $this->artisan('codex:backfill-diagram-embeds')
            ->assertSuccessful()
            ->expectsOutputToContain('Diagram embed backfill complete.');

        $this->assertDatabaseHas('page_diagram_embeds', [
            'page_id' => $page->id,
            'diagram_id' => $diagram->id,
        ]);
    }

    public function test_it_can_limit_the_backfill_to_one_workspace(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $workspaceOne = Workspace::create([
            'name' => 'Alpha Docs',
            'owner_id' => $owner->id,
            'is_public' => false,
        ]);
        $workspaceTwo = Workspace::create([
            'name' => 'Beta Docs',
            'owner_id' => $owner->id,
            'is_public' => false,
        ]);

        $diagramOne = Diagram::create([
            'title' => 'Alpha Diagram',
            'workspace_id' => $workspaceOne->id,
            'author_id' => $owner->id,
            'diagram_type' => 'mermaid',
            'diagram_data' => 'graph TD; A-->B;',
        ]);
        $diagramTwo = Diagram::create([
            'title' => 'Beta Diagram',
            'workspace_id' => $workspaceTwo->id,
            'author_id' => $owner->id,
            'diagram_type' => 'mermaid',
            'diagram_data' => 'graph TD; A-->B;',
        ]);

        $pageOne = Page::create([
            'title' => 'Alpha Page',
            'content' => '{{diagram:'.$diagramOne->id.'}}',
            'content_type' => 'markdown',
            'workspace_id' => $workspaceOne->id,
            'author_id' => $owner->id,
            'status' => 'draft',
        ]);
        $pageTwo = Page::create([
            'title' => 'Beta Page',
            'content' => '{{diagram:'.$diagramTwo->id.'}}',
            'content_type' => 'markdown',
            'workspace_id' => $workspaceTwo->id,
            'author_id' => $owner->id,
            'status' => 'draft',
        ]);

        $this->artisan('codex:backfill-diagram-embeds', ['--workspace' => $workspaceOne->slug])
            ->assertSuccessful();

        $this->assertDatabaseHas('page_diagram_embeds', [
            'page_id' => $pageOne->id,
            'diagram_id' => $diagramOne->id,
        ]);
        $this->assertDatabaseMissing('page_diagram_embeds', [
            'page_id' => $pageTwo->id,
            'diagram_id' => $diagramTwo->id,
        ]);
    }
}
