<?php
namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\PagePin;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PagePinController extends Controller
{
    private const MAX_PINS = 6;

    public function toggle(Request $request, Workspace $workspace, Page $page): RedirectResponse
    {
        abort_if($page->workspace_id !== $workspace->id, 404);
        // Only workspace owners and editor/admin members can pin
        $user = auth()->user();
        $canPin = $workspace->owner_id === $user->id
            || $workspace->members()->where('user_id', $user->id)->whereIn('role', ['editor', 'admin'])->exists()
            || $user->can('pages.update');
        abort_if(! $canPin, 403);

        $existing = PagePin::where('workspace_id', $workspace->id)
            ->where('page_id', $page->id)
            ->first();

        if ($existing) {
            $existing->delete();
            return back()->with('pin-status', 'unpinned');
        }

        $count = PagePin::where('workspace_id', $workspace->id)->count();
        abort_if($count >= self::MAX_PINS, 422, 'Maximum of ' . self::MAX_PINS . ' pinned pages per workspace.');

        $nextPosition = PagePin::where('workspace_id', $workspace->id)->max('position') + 1;

        PagePin::create([
            'workspace_id' => $workspace->id,
            'page_id'      => $page->id,
            'position'     => $nextPosition,
        ]);

        return back()->with('pin-status', 'pinned');
    }
}
