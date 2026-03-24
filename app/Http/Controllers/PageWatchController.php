<?php
namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\PageWatch;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PageWatchController extends Controller
{
    public function toggle(Request $request, Workspace $workspace, Page $page): JsonResponse|RedirectResponse
    {
        abort_if($page->workspace_id !== $workspace->id, 404);
        $this->authorize('view', $page);

        $userId  = auth()->id();
        $deleted = PageWatch::where('user_id', $userId)->where('page_id', $page->id)->delete();

        if ($deleted > 0) {
            $watching = false;
        } else {
            PageWatch::insertOrIgnore(['user_id' => $userId, 'page_id' => $page->id]);
            $watching = true;
        }

        if ($request->expectsJson()) {
            return response()->json(['watching' => $watching]);
        }
        return back();
    }
}
