<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\PageStar;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PageStarController extends Controller
{
    /**
     * feat 1.6 — Toggle star on a page for the authenticated user.
     * Accepts JSON (XHR) or plain form POST; returns JSON for XHR.
     */
    public function toggle(Request $request, Workspace $workspace, Page $page): JsonResponse|RedirectResponse
    {
        abort_if($page->workspace_id !== $workspace->id, 404);
        $this->authorize('view', $page);

        $userId = auth()->id();

        $deleted = PageStar::where('user_id', $userId)
            ->where('page_id', $page->id)
            ->delete();

        if ($deleted > 0) {
            $starred = false;
        } else {
            PageStar::insertOrIgnore([
                'user_id' => $userId,
                'page_id' => $page->id,
            ]);

            $starred = true;
        }

        if ($request->expectsJson()) {
            return response()->json(['starred' => $starred]);
        }

        return back();
    }
}
