<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Provides page-title search for the TinyMCE wiki-links autocomplete plugin.
 *
 * GET /api/pages/search?q=...&workspace_id=...
 *
 * Returns the top 10 pages whose title contains the query string.
 * The workspace_id filter is required to keep suggestions scoped correctly.
 */
class PageLinkController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $q           = trim((string) $request->get('q', ''));
        $workspaceId = (string) $request->get('workspace_id', '');

        $query = Page::query()
            ->whereNull('deleted_at')
            ->when($workspaceId !== '', fn ($q2) => $q2->where('workspace_id', $workspaceId))
            ->when($q !== '', fn ($q2) => $q2->where('title', 'like', "%{$q}%"))
            ->orderBy('title')
            ->limit(10)
            ->get(['id', 'title', 'workspace_id']);

        // Build workspace map for route generation
        $wsIds = $query->pluck('workspace_id')->unique()->values();
        $wsMap = Workspace::whereIn('id', $wsIds)->get(['id', 'slug'])->keyBy('id');

        return response()->json(
            $query->map(function (Page $p) use ($wsMap): array {
                $ws  = $wsMap->get($p->workspace_id);
                $url = $ws
                    ? route('workspaces.pages.show', [$ws, $p])
                    : '#';

                return [
                    'id'    => $p->id,
                    'label' => $p->title,
                    'url'   => $url,
                ];
            })
        );
    }
}
