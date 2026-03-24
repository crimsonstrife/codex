<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\PageComment;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PageCommentController extends Controller
{
    public function store(Request $request, Workspace $workspace, Page $page): \Illuminate\Http\RedirectResponse
    {
        abort_if($page->workspace_id !== $workspace->id, 404);
        $this->authorize('view', $page);

        $validated = $request->validate([
            'content'   => 'required|string|max:5000',
            'parent_id' => [
                'nullable',
                'uuid',
                function ($attribute, $value, $fail) use ($page) {
                    $exists = PageComment::where('id', $value)
                        ->where('page_id', $page->id)
                        ->whereNull('parent_id') // only allow one level of nesting
                        ->exists();
                    if (! $exists) {
                        $fail('The selected comment does not exist.');
                    }
                },
            ],
        ]);

        $comment = $page->comments()->create([
            'user_id'   => auth()->id(),
            'parent_id' => $validated['parent_id'] ?? null,
            'content'   => $validated['content'],
        ]);

        $this->dispatchMentionNotifications($workspace, $page, $validated['content']);

        return back()->with('status', 'comment-added')->withFragment('comments');
    }

    /**
     * Parse @Name mentions from plain-text comment content and create
     * CodexNotification entries for mentioned workspace members.
     *
     * Only users who are members of the workspace (or its owner) are matched,
     * and the commenter is excluded from their own notifications.
     */
    private function dispatchMentionNotifications(Workspace $workspace, Page $page, string $content): void
    {
        if (! preg_match_all('/@([\w][\w\s\-\.]{0,49})/u', $content, $matches)) {
            return;
        }

        $mentionedNames = array_unique(array_map('trim', $matches[1]));
        if (empty($mentionedNames)) {
            return;
        }

        // Collect all user IDs with access to this workspace
        $workspaceUserIds = $workspace->members()
            ->pluck('user_id')
            ->push($workspace->owner_id)
            ->unique()
            ->toArray();

        $mentionedUsers = \App\Models\User::whereIn('id', $workspaceUserIds)
            ->whereIn('name', $mentionedNames)
            ->where('id', '!=', auth()->id())
            ->pluck('id');

        if ($mentionedUsers->isEmpty()) {
            return;
        }

        $commenter = auth()->user()->name;
        $message   = "{$commenter} mentioned you in a comment on \"{$page->title}\"";

        $inserts = $mentionedUsers->map(fn ($uid) => [
            'id'         => \Illuminate\Support\Str::uuid()->toString(),
            'user_id'    => $uid,
            'type'       => 'page_mentioned',
            'page_id'    => $page->id,
            'message'    => $message,
            'read_at'    => null,
            'created_at' => now(),
            'updated_at' => now(),
        ])->values()->all();

        \App\Models\CodexNotification::insert($inserts);
    }

    public function destroy(Workspace $workspace, Page $page, PageComment $comment): \Illuminate\Http\RedirectResponse
    {
        abort_if($page->workspace_id !== $workspace->id, 404);
        abort_if($comment->page_id !== $page->id, 404);

        // Authors can delete their own comments; workspace owners can delete any comment
        if (auth()->id() !== $comment->user_id) {
            $this->authorize('update', $page); // reuse page update permission for moderation
        }

        $comment->delete();

        return back()->with('status', 'comment-deleted')->withFragment('comments');
    }

    public function resolve(Request $request, Workspace $workspace, Page $page, PageComment $comment): RedirectResponse
    {
        abort_if($page->workspace_id !== $workspace->id, 404);
        abort_if($comment->page_id !== $page->id, 404);
        // Only the comment author, page author, or a workspace editor/admin/owner can resolve
        $user = auth()->user();
        $canResolve = $comment->user_id === $user->id
            || $page->author_id === $user->id
            || $workspace->owner_id === $user->id
            || $workspace->members()->where('user_id', $user->id)->whereIn('role', ['editor', 'admin'])->exists()
            || $user->can('pages.update');
        abort_if(! $canResolve, 403);

        if ($comment->isResolved()) {
            $comment->update(['resolved_at' => null, 'resolved_by' => null]);
        } else {
            $comment->update(['resolved_at' => now(), 'resolved_by' => $user->id]);
        }

        return back()->with('comment-status', $comment->isResolved() ? 'resolved' : 'unresolved');
    }
}
