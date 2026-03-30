<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API page search endpoint for Forge issue linking.
 * Protected by an app-level token (machine-to-machine).
 *
 * The caller may supply `for_forge_user_id` — the Forge user ID of the person
 * performing the search. This is resolved to the matching Codex user (who must
 * have linked their Forge account via SSO), and results are scoped to the
 * workspaces they can access.
 *
 * If `for_forge_user_id` is absent or the user has not linked their account,
 * only public workspaces are searched.
 *
 * GET /api/v1/pages/search?q=&workspace_id=&for_forge_user_id=
 */
class PageSearchApiController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->get('q', ''));
        $workspaceId = (string) $request->get('workspace_id', '');
        $forForgeUserId = $request->string('for_forge_user_id')->toString();

        $user = $forForgeUserId !== ''
            ? User::where('forge_user_id', $forForgeUserId)->first()
            : null;

        $accessibleWorkspaceIds = Workspace::query()
            ->accessibleBy($user)
            ->pluck('id');

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
                $url = $workspace
                    ? route('workspaces.pages.show', [$workspace, $page])
                    : '#';

                return [
                    'id' => $page->id,
                    'title' => $page->title,
                    'url' => $url,
                    'workspace_id' => $page->workspace_id,
                    'workspace_slug' => $workspace?->slug,
                    'workspace_name' => $workspace?->name,
                ];
            })->values(),
        ]);
    }
}
