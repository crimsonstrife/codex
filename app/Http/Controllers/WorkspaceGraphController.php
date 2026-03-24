<?php

namespace App\Http\Controllers;

use App\Models\PageLink;
use App\Models\Workspace;

/**
 * Link graph visualization for a workspace.
 *
 * Queries all pages and their wiki-link edges from page_links, then passes
 * them as JSON to vis-network for a force-directed layout.
 *
 * GET /workspaces/{workspace}/graph → workspaces.graph
 */
class WorkspaceGraphController extends Controller
{
    public function __invoke(Workspace $workspace)
    {
        $this->authorize('view', $workspace);

        // Load all non-deleted pages with just what the graph needs
        $pages = $workspace->pages()
            ->select('id', 'title', 'slug', 'status', 'parent_id')
            ->get();

        // Load all links whose source AND target both belong to this workspace
        $pageIds = $pages->pluck('id');

        $links = PageLink::whereIn('source_page_id', $pageIds)
            ->whereIn('target_page_id', $pageIds)
            ->select('source_page_id', 'target_page_id')
            ->get();

        // Build vis-network nodes
        $statusColours = [
            'published' => ['background' => '#d1fae5', 'border' => '#059669', 'font' => '#065f46'],
            'draft'     => ['background' => '#fef9c3', 'border' => '#ca8a04', 'font' => '#713f12'],
            'archived'  => ['background' => '#f1f5f9', 'border' => '#94a3b8', 'font' => '#475569'],
        ];

        $nodes = $pages->map(function ($page) use ($workspace, $statusColours) {
            $c = $statusColours[$page->status] ?? $statusColours['draft'];
            return [
                'id'    => $page->id,
                'label' => mb_strimwidth($page->title, 0, 30, '…'),
                'title' => $page->title, // tooltip
                'url'   => route('workspaces.pages.show', [$workspace, $page]),
                'color' => [
                    'background' => $c['background'],
                    'border'     => $c['border'],
                    'highlight'  => ['background' => '#dbeafe', 'border' => '#3b82f6'],
                ],
                'font'  => ['color' => $c['font']],
                'shape' => 'box',
            ];
        })->values();

        // Build vis-network edges
        $edges = $links->map(fn ($l) => [
            'from'   => $l->source_page_id,
            'to'     => $l->target_page_id,
            'arrows' => 'to',
        ])->values();

        return view('workspaces.graph', compact('workspace', 'nodes', 'edges'));
    }
}
