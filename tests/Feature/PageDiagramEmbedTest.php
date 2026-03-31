<?php

namespace Tests\Feature;

use App\Models\Diagram;
use App\Models\Page;
use App\Models\User;
use App\Models\Workspace;
use App\Services\PageContentRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageDiagramEmbedTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_show_renders_embedded_mermaid_diagram(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $workspace = $this->createWorkspace($owner, 'Engineering');
        $diagram = $this->createDiagram($workspace, $owner, [
            'title' => 'Incident Flow',
            'diagram_type' => 'mermaid',
            'diagram_data' => "graph LR\nA[Alert] --> B[Mitigate]",
        ]);
        $page = $this->createPage($workspace, $owner, [
            'content_type' => 'markdown',
            'content' => '{{diagram:'.$diagram->id.'}}',
        ]);

        $this->actingAs($owner)
            ->get(route('workspaces.pages.show', [$workspace, $page]))
            ->assertOk()
            ->assertSee('Embedded Diagram')
            ->assertSee('Incident Flow')
            ->assertSee('graph LR', false)
            ->assertDontSee('{{diagram:', false);
    }

    public function test_workspace_home_page_renders_embedded_diagram_with_shared_renderer(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $workspace = $this->createWorkspace($owner, 'Operations');
        $diagram = $this->createDiagram($workspace, $owner, [
            'title' => 'Escalation Map',
            'diagram_type' => 'mermaid',
            'diagram_data' => "graph TD\nA[Page] --> B[Escalate]",
        ]);
        $page = $this->createPage($workspace, $owner, [
            'title' => 'Home',
            'content_type' => 'richtext',
            'content' => '<p>{{diagram:'.$diagram->id.'}}</p>',
        ]);

        $workspace->update(['home_page_id' => $page->id]);

        $this->actingAs($owner)
            ->get(route('workspaces.show', $workspace))
            ->assertOk()
            ->assertSee('Escalation Map')
            ->assertSee('Embedded Diagram')
            ->assertDontSee('{{diagram:', false);
    }

    public function test_page_show_renders_drawio_embed_container(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $workspace = $this->createWorkspace($owner, 'Platform');
        $diagram = $this->createDiagram($workspace, $owner, [
            'title' => 'System Layout',
            'diagram_type' => 'drawio',
            'diagram_data' => '<mxfile><diagram>test</diagram></mxfile>',
        ]);
        $page = $this->createPage($workspace, $owner, [
            'content_type' => 'markdown',
            'content' => '{{diagram:'.$diagram->id.'}}',
        ]);

        $this->actingAs($owner)
            ->get(route('workspaces.pages.show', [$workspace, $page]))
            ->assertOk()
            ->assertSee('System Layout')
            ->assertSee('data-codex-drawio=', false)
            ->assertSee('data-codex-drawio-url=', false)
            ->assertDontSee('{{diagram:', false);
    }

    public function test_print_and_export_variants_render_static_diagram_embed_without_raw_token(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $workspace = $this->createWorkspace($owner, 'Release Notes');
        $diagram = $this->createDiagram($workspace, $owner, [
            'title' => 'Launch Checklist',
            'diagram_type' => 'drawio',
            'diagram_data' => '<mxfile><diagram>launch</diagram></mxfile>',
        ]);
        $page = $this->createPage($workspace, $owner, [
            'content_type' => 'markdown',
            'content' => '{{diagram:'.$diagram->id.'}}',
        ]);

        $this->actingAs($owner)
            ->get(route('workspaces.pages.print', [$workspace, $page]))
            ->assertOk()
            ->assertSee('Launch Checklist')
            ->assertSee('Open this draw.io diagram in Codex to view it interactively.')
            ->assertDontSee('{{diagram:', false);

        $rendered = app(PageContentRenderer::class)->render(
            $page->content,
            $page->content_type,
            $workspace,
            'export',
        );

        $this->assertStringContainsString('Launch Checklist', $rendered);
        $this->assertStringContainsString('Open this draw.io diagram in Codex to view it interactively.', $rendered);
        $this->assertStringNotContainsString('{{diagram:', $rendered);
    }

    public function test_diagram_search_endpoint_is_scoped_to_accessible_workspace(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $otherUser = User::factory()->withPersonalTeam()->create();

        $visibleWorkspace = $this->createWorkspace($owner, 'Visible');
        $hiddenWorkspace = $this->createWorkspace($otherUser, 'Hidden');

        $visibleDiagram = $this->createDiagram($visibleWorkspace, $owner, [
            'title' => 'System Overview',
        ]);
        $this->createDiagram($hiddenWorkspace, $otherUser, [
            'title' => 'System Secrets',
        ]);

        $this->actingAs($owner)
            ->getJson(route('api.diagrams.search', [
                'q' => 'System',
                'workspace_id' => $visibleWorkspace->id,
            ]))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'id' => $visibleDiagram->id,
                'label' => 'System Overview',
            ]);

        $this->actingAs($owner)
            ->getJson(route('api.diagrams.search', [
                'q' => 'System',
                'workspace_id' => $hiddenWorkspace->id,
            ]))
            ->assertOk()
            ->assertExactJson([]);
    }

    private function createWorkspace(User $owner, string $name): Workspace
    {
        return Workspace::create([
            'name' => $name,
            'owner_id' => $owner->id,
            'is_public' => false,
        ]);
    }

    private function createPage(Workspace $workspace, User $author, array $attributes = []): Page
    {
        return Page::create(array_merge([
            'title' => 'Embedded Page',
            'content' => '',
            'content_type' => 'markdown',
            'workspace_id' => $workspace->id,
            'author_id' => $author->id,
            'status' => 'draft',
        ], $attributes));
    }

    private function createDiagram(Workspace $workspace, User $author, array $attributes = []): Diagram
    {
        return Diagram::create(array_merge([
            'title' => 'Embedded Diagram',
            'workspace_id' => $workspace->id,
            'author_id' => $author->id,
            'diagram_type' => 'mermaid',
            'diagram_data' => 'graph TD; A-->B;',
        ], $attributes));
    }
}
