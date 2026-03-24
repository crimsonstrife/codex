<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API page search endpoint for external systems (e.g. Forge issue linking).
 * Protected by Sanctum Bearer token (machine-to-machine).
 *
 * GET /api/v1/pages/search?q=&workspace_id=
 *
 * Returns pages matching the query, enriched with workspace slug and
 * a full canonical URL so the caller can store/display links.
 * Results are scoped to workspaces the authenticated user can view.
 */
class PageSearchApiController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $q           = trim((string) $request->get('q', ''));
        $workspaceId = (string) $request->get('workspace_id', '');
        $user        = $request->user();

        // Build the set of workspace IDs the user is allowed to see (DB-level filter)
        $accessibleWorkspaceIds = Workspace::query()
            ->accessibleBy($user)
            ->pluck('id');

        // If a specific workspace_id was requested, verify access to it
        if ($workspaceId !== '' && ! $accessibleWorkspaceIds->contains($workspaceId)) {
            return response()->json(['data' => []]);
        }

        $pages = Page::query()
            ->whereNull('deleted_at')
            ->when(
                $workspaceId !== '',
                fn ($query) => $query->where('workspace_id', $workspaceId),
                fn ($query) => $query->whereIn('workspace_id', $accessibleWorkspaceIds)
            )
            ->when($q !== '', fn ($query) => $query->where('title', 'like', "%{$q}%"))
            ->orderBy('title')
            ->limit(15)
            ->get(['id', 'title', 'workspace_id']);

        $wsIds = $pages->pluck('workspace_id')->unique()->values();
        $wsMap = Workspace::query()->whereIn('id', $wsIds)->get(['id', 'slug', 'name'])->keyBy('id');

        return response()->json([
            'data' => $pages->map(function (Page $page) use ($wsMap): array {
                $workspace = $wsMap->get($page->workspace_id);
                $url       = $workspace
                    ? route('workspaces.pages.show', [$workspace, $page])
                    : '#';

                return [
                    'id'               => $page->id,
                    'title'            => $page->title,
                    'url'              => $url,
                    'workspace_id'     => $page->workspace_id,
                    'workspace_slug'   => $workspace?->slug,
                    'workspace_name'   => $workspace?->name,
                ];
            })->values(),
        ]);
    }
}
