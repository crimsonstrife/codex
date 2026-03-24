<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API endpoint for Forge to discover available Codex workspaces.
 * Protected by an app-level token (machine-to-machine).
 *
 * The caller must supply `for_forge_user_id` — the Forge user ID of the person
 * whose workspaces should be shown. This is resolved to the matching Codex user
 * (who must have linked their Forge account via SSO), and results are scoped
 * to workspaces they own, are a member of, or that are public.
 *
 * If `for_forge_user_id` is absent or the user has not linked their account,
 * only public workspaces are returned (the safest fallback).
 */
class WorkspaceApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search        = $request->string('search');
        $forForgeUserId = $request->string('for_forge_user_id')->toString();

        // Resolve to the Codex user who linked this Forge account.
        // Returns null if the user hasn't linked their account — that's fine,
        // accessibleBy(null) will only return public workspaces.
        $user = $forForgeUserId !== ''
            ? User::where('forge_user_id', $forForgeUserId)->first()
            : null;

        $workspaces = Workspace::query()
            ->accessibleBy($user)
            ->when($search->isNotEmpty(), function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'description', 'forge_project_id', 'forge_project_key']);

        return response()->json([
            'data' => $workspaces->map(fn ($w) => [
                'id'                => $w->id,
                'name'              => $w->name,
                'slug'              => $w->slug,
                'description'       => $w->description,
                'forge_project_id'  => $w->forge_project_id,
                'forge_project_key' => $w->forge_project_key,
            ])->values(),
        ]);
    }
}
