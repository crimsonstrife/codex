<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Diagram;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Provides diagram-title search for page editor embeds.
 *
 * GET /api/diagrams/search?q=...&workspace_id=...
 *
 * Returns the top 10 diagrams whose title contains the query string.
 * The workspace_id filter is required to keep suggestions scoped correctly.
 */
class DiagramLinkController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->get('q', ''));
        $workspaceId = (string) $request->get('workspace_id', '');

        if ($workspaceId === '') {
            return response()->json([]);
        }

        $workspaceIsAccessible = Workspace::query()
            ->accessibleBy($request->user())
            ->whereKey($workspaceId)
            ->exists();

        if (! $workspaceIsAccessible) {
            return response()->json([]);
        }

        $query = Diagram::query()
            ->whereNull('deleted_at')
            ->where('workspace_id', $workspaceId)
            ->when($q !== '', fn ($q2) => $q2->where('title', 'like', "%{$q}%"))
            ->orderBy('title')
            ->limit(10)
            ->get(['id', 'title', 'workspace_id', 'diagram_type']);

        $wsIds = $query->pluck('workspace_id')->unique()->values();
        $wsMap = Workspace::whereIn('id', $wsIds)->get(['id', 'slug'])->keyBy('id');

        return response()->json(
            $query->map(function (Diagram $diagram) use ($wsMap): array {
                $workspace = $wsMap->get($diagram->workspace_id);
                $url = $workspace
                    ? route('workspaces.diagrams.show', [$workspace, $diagram])
                    : '#';

                return [
                    'id' => $diagram->id,
                    'label' => $diagram->title,
                    'type' => $diagram->diagram_type,
                    'url' => $url,
                ];
            })
        );
    }
}
