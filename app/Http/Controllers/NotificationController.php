<?php
namespace App\Http\Controllers;

use App\Models\CodexNotification;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    /** Return the 15 most recent notifications for the authenticated user. */
    public function index(): JsonResponse
    {
        $notifications = CodexNotification::with('page.workspace')
            ->where('user_id', auth()->id())
            ->latest()
            ->limit(15)
            ->get()
            ->map(fn ($n) => [
                'id'         => $n->id,
                'message'    => $n->message,
                'read'       => $n->isRead(),
                'created_at' => $n->created_at->diffForHumans(),
                'url'        => $n->page && $n->page->workspace
                    ? route('workspaces.pages.show', [$n->page->workspace, $n->page])
                    : null,
            ]);

        $unread = CodexNotification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->count();

        return response()->json(['notifications' => $notifications, 'unread' => $unread]);
    }

    /** Mark all notifications as read. */
    public function markAllRead(): JsonResponse
    {
        CodexNotification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
