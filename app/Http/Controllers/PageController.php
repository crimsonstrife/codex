<?php

namespace App\Http\Controllers;

use App\Mail\PageUpdatedMail;
use App\Models\CodexNotification;
use App\Models\Page;
use App\Models\PageLink;
use App\Models\PageRevision;
use App\Models\PageTemplate;
use App\Models\PageView;
use App\Models\PageWatch;
use App\Models\ScriptProjectPageLink;
use App\Models\User;
use App\Models\Workspace;
use App\Services\DiagramEmbedRenderer;
use App\Services\PageLinkResolver;
use App\Services\WorkspaceCategoryService;
use App\Support\CodexRuntimeConfig;
use App\Support\PageNavigationResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Jfcherng\Diff\Differ;
use Jfcherng\Diff\Factory\RendererFactory;
use League\HTMLToMarkdown\HtmlConverter;

class PageController extends Controller
{
    public function create(Workspace $workspace)
    {
        $this->authorize('create', Page::class);
        $defaultContentType = CodexRuntimeConfig::defaultPageContentType();
        $categories = app(WorkspaceCategoryService::class)->availableForWorkspace($workspace);
        $pages = $workspace->pages()->orderBy('title')->get(['id', 'title', 'parent_id']);
        $templates = PageTemplate::where(function ($q) use ($workspace) {
            $q->where('workspace_id', $workspace->id)->orWhereNull('workspace_id');
        })->orderByDesc('is_system')->orderBy('name')->get();

        return view('pages.create', compact('workspace', 'categories', 'pages', 'templates', 'defaultContentType'));
    }

    public function show(Workspace $workspace, Page $page)
    {
        abort_if($page->workspace_id !== $workspace->id, 404);
        $this->authorize('view', $page);

        $breadcrumbs = $page->ancestors()->get()->push($page);
        $orderedPages = $workspace->pages()->defaultOrder()->get();
        $pageTree = $orderedPages->toTree();
        [
            'childPages' => $childPages,
            'previousPage' => $previousPage,
            'nextPage' => $nextPage,
        ] = app(PageNavigationResolver::class)->resolve($orderedPages, $page);

        // Workspaces the user can transfer this page to (must be owner or editor/admin)
        $transferableWorkspaces = collect();
        if (auth()->check() && auth()->user()->can('update', $page)) {
            $user = auth()->user();
            $transferableWorkspaces = Workspace::where('id', '!=', $workspace->id)
                ->whereNull('deleted_at')
                ->where(function ($q) use ($user) {
                    $q->where('owner_id', $user->id)
                        ->orWhereHas('members', function ($mq) use ($user) {
                            $mq->where('user_id', $user->id)
                                ->whereIn('role', ['editor', 'admin']);
                        });
                })
                ->orderBy('name')
                ->get(['id', 'name', 'color', 'icon']);
        }

        // Track recently viewed (upsert so each user has at most one row per page)
        if (auth()->check()) {
            PageView::upsert(
                [[
                    'id' => Str::uuid()->toString(),
                    'user_id' => auth()->id(),
                    'page_id' => $page->id,
                    'workspace_id' => $workspace->id,
                    'viewed_at' => now(),
                ]],
                ['user_id', 'page_id'],
                ['viewed_at', 'workspace_id'],
            );
        }

        $incomingLinks = PageLink::where('target_page_id', $page->id)
            ->with([
                'sourcePage' => fn ($q) => $q
                    ->select('id', 'title', 'slug', 'workspace_id', 'status')
                    ->with('workspace:id,name,color'),
            ])
            ->get();

        $linkedScripts = ScriptProjectPageLink::where('page_id', $page->id)
            ->with([
                'scriptProject' => fn ($query) => $query
                    ->select('id', 'workspace_id', 'title', 'slug', 'status', 'logline', 'updated_at'),
            ])
            ->orderBy('position')
            ->get();

        // Broken outgoing wiki-link refs (referenced but unresolvable at last save)
        $brokenOutgoingLinks = [];
        if (str_contains($page->content ?? '', '[[')) {
            preg_match_all('/\[\[([^\[\]]+?)]]/u', $page->content ?? '', $wikiMatches);
            $allRefs = array_unique($wikiMatches[1] ?? []);
            $resolvedAnchors = $page->outgoingLinks()->pluck('anchor_text')->toArray();
            $brokenOutgoingLinks = array_values(array_diff($allRefs, $resolvedAnchors));
        }

        return view('pages.show', compact(
            'workspace', 'page', 'breadcrumbs', 'transferableWorkspaces',
            'incomingLinks', 'brokenOutgoingLinks', 'pageTree',
            'childPages', 'previousPage', 'nextPage', 'linkedScripts'
        ));
    }

    public function store(Request $request, Workspace $workspace)
    {
        $this->authorize('create', Page::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'content_type' => 'in:markdown,richtext',
            'parent_id' => [
                'nullable',
                'uuid',
                Rule::exists('pages', 'id')->where('workspace_id', $workspace->id),
            ],
            'status' => 'in:draft,published,archived',
            'category_ids' => 'nullable|array',
            'category_ids.*' => [
                'uuid',
                Rule::exists('categories', 'id')->where(function ($q) use ($workspace) {
                    $q->where('workspace_id', $workspace->id)->orWhereNull('workspace_id');
                }),
            ],
            'tags' => 'nullable|string',
            ...app(WorkspaceCategoryService::class)->inlineCreationRules(),
        ]);

        $validated['workspace_id'] = $workspace->id;
        $validated['author_id'] = auth()->id();
        $validated['content_type'] ??= CodexRuntimeConfig::defaultPageContentType();

        $categoryIds = app(WorkspaceCategoryService::class)->resolveSelectedCategoryIds($workspace, $validated);

        $page = Page::create($validated);

        if ($categoryIds !== []) {
            $page->categories()->sync($categoryIds);
        }

        if (! empty($validated['tags'])) {
            $tagNames = array_filter(array_map('trim', explode(',', $validated['tags'])));
            $page->syncTags($tagNames);
        }

        PageRevision::create([
            'page_id' => $page->id,
            'user_id' => auth()->id(),
            'title' => $page->title,
            'content' => $page->content,
            'content_type' => $page->content_type,
            'revision_number' => 1,
            'change_summary' => 'Initial version',
        ]);

        app(PageLinkResolver::class)->sync($page);
        app(DiagramEmbedRenderer::class)->sync($page);

        return redirect()->route('workspaces.pages.edit', [$workspace, $page])
            ->with('status', 'page-created');
    }

    public function duplicate(Workspace $workspace, Page $page): RedirectResponse
    {
        abort_if($page->workspace_id !== $workspace->id, 404);
        $this->authorize('duplicate', $page);

        // Eager load tags and categories from the original page
        $page->load('tags', 'categories');

        $copy = Page::create([
            'title' => 'Copy of '.$page->title,
            'content' => $page->content,
            'content_type' => $page->content_type,
            'workspace_id' => $workspace->id,
            'author_id' => auth()->id(),
            'parent_id' => $page->parent_id,
            'status' => 'draft',
        ]);

        $copy->syncTags($page->tags->pluck('name')->toArray());
        $copy->categories()->sync($page->categories->pluck('id')->toArray());

        PageRevision::create([
            'page_id' => $copy->id,
            'user_id' => auth()->id(),
            'title' => $copy->title,
            'content' => $copy->content,
            'content_type' => $copy->content_type,
            'revision_number' => 1,
            'change_summary' => 'Duplicated from: '.$page->title,
        ]);

        app(DiagramEmbedRenderer::class)->sync($copy);

        return redirect()->route('workspaces.pages.edit', [$workspace, $copy])
            ->with('status', 'page-created');
    }

    public function edit(Workspace $workspace, Page $page)
    {
        $this->authorize('update', $page);

        $page->load('lockedBy');
        $lockedByOther = $page->isLockedByAnother(auth()->user());

        if (! $lockedByOther) {
            $page->acquireLock(auth()->user());
        }

        $categories = app(WorkspaceCategoryService::class)->availableForWorkspace($workspace);
        $pages = $workspace->pages()
            ->where('id', '!=', $page->id)
            ->orderBy('title')
            ->get(['id', 'title', 'parent_id']);

        return view('pages.edit', compact('workspace', 'page', 'categories', 'pages', 'lockedByOther'));
    }

    /**
     * Called by the editor JS every 60 seconds to keep the lock alive.
     */
    public function heartbeat(Workspace $workspace, Page $page): JsonResponse
    {
        abort_if($page->workspace_id !== $workspace->id, 404);
        $this->authorize('update', $page);

        $page->load('lockedBy');

        // Only refresh if the caller owns the lock (or the lock has expired)
        if (! $page->isLockedByAnother(auth()->user())) {
            $page->acquireLock(auth()->user());

            return response()->json(['locked' => false]);
        }

        // Someone else holds a valid lock
        return response()->json([
            'locked' => true,
            'lockedBy' => $page->lockedBy?->name,
            'since' => $page->locked_at?->diffForHumans(),
        ], 409);
    }

    /**
     * DELETE /workspaces/{workspace}/pages/{page}/lock
     * Releases the lock. Authors can release their own; admins/owners can force-unlock.
     */
    public function unlock(Workspace $workspace, Page $page): JsonResponse
    {
        abort_if($page->workspace_id !== $workspace->id, 404);

        $user = auth()->user();
        $canForce = $user->can('update', $workspace)
            || $workspace->owner_id === $user->id;

        if ($page->locked_by === $user->id || $canForce) {
            $page->releaseLock();

            return response()->json(['ok' => true]);
        }

        return response()->json(['error' => 'Not authorised to unlock this page.'], 403);
    }

    public function update(Request $request, Workspace $workspace, Page $page)
    {
        $this->authorize('update', $page);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            // content_type is intentionally excluded from update; it is immutable after creation
            'status' => 'in:draft,published,archived',
            'change_summary' => 'nullable|string|max:255',
            'category_ids' => 'nullable|array',
            'category_ids.*' => [
                'uuid',
                Rule::exists('categories', 'id')->where(function ($q) use ($workspace) {
                    $q->where('workspace_id', $workspace->id)->orWhereNull('workspace_id');
                }),
            ],
            'tags' => 'nullable|string',
            ...app(WorkspaceCategoryService::class)->inlineCreationRules(),
        ]);

        $categoryIds = app(WorkspaceCategoryService::class)->resolveSelectedCategoryIds($workspace, $validated);

        $lastRevision = $page->revisions()->latest()->first();
        $revNum = $lastRevision ? $lastRevision->revision_number + 1 : 1;

        $page->update($validated);

        $page->categories()->sync($categoryIds);

        $tagNames = [];
        if (! empty($validated['tags'])) {
            $tagNames = array_filter(array_map('trim', explode(',', $validated['tags'])));
        }
        $page->syncTags($tagNames);

        PageRevision::create([
            'page_id' => $page->id,
            'user_id' => auth()->id(),
            'title' => $page->title,
            'content' => $page->content,
            'content_type' => $page->content_type,
            'revision_number' => $revNum,
            'change_summary' => $validated['change_summary'] ?? null,
        ]);

        // Notify watchers (excluding the editor themselves)
        $watchers = PageWatch::where('page_id', $page->id)
            ->where('user_id', '!=', auth()->id())
            ->pluck('user_id');

        if ($watchers->isNotEmpty()) {
            $editor = auth()->user()->name;
            $message = "{$editor} updated \"{$page->title}\"";
            $inserts = $watchers->map(fn ($uid) => [
                'id' => Str::uuid()->toString(),
                'user_id' => $uid,
                'type' => 'page_updated',
                'page_id' => $page->id,
                'message' => $message,
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ])->values()->all();

            CodexNotification::insert($inserts);

            $emailRecipients = User::whereIn('id', $watchers->toArray())
                ->where('email_notifications', true)
                ->get();

            foreach ($emailRecipients as $recipient) {
                Mail::to($recipient)
                    ->queue(new PageUpdatedMail($page, $workspace, auth()->user()));
            }
        }

        if ($page->content_type === 'richtext' && ! empty($page->content)) {
            preg_match_all('/data-mention-id="([^"]+)"/i', $page->content, $mentionMatches);
            $mentionedIds = array_values(array_unique(
                array_filter($mentionMatches[1] ?? [], fn ($id) => $id !== auth()->id())
            ));
            if (! empty($mentionedIds)) {
                $editor = auth()->user()->name;
                $message = "{$editor} mentioned you in \"{$page->title}\"";
                $inserts = array_map(fn ($uid) => [
                    'id' => Str::uuid()->toString(),
                    'user_id' => $uid,
                    'type' => 'page_mentioned',
                    'page_id' => $page->id,
                    'message' => $message,
                    'read_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $mentionedIds);
                CodexNotification::insert($inserts);
            }
        }

        app(PageLinkResolver::class)->sync($page);
        app(DiagramEmbedRenderer::class)->sync($page);

        return redirect()->route('workspaces.pages.show', [$workspace, $page])
            ->with('status', 'page-updated');
    }

    public function updateStatus(Request $request, Workspace $workspace, Page $page): RedirectResponse
    {
        abort_if($page->workspace_id !== $workspace->id, 404);
        $this->authorize('update', $page);

        $validated = $request->validate([
            'status' => 'required|in:draft,published,archived',
        ]);

        $page->update(['status' => $validated['status']]);

        return back()->with('status', 'status-updated');
    }

    public function history(Workspace $workspace, Page $page)
    {
        abort_if($page->workspace_id !== $workspace->id, 404);
        $this->authorize('view', $page);

        $revisions = $page->revisions()
            ->with('user')
            ->latest()
            ->paginate(20);

        $breadcrumbs = $page->ancestors()->get()->push($page);

        return view('pages.history', compact('workspace', 'page', 'revisions', 'breadcrumbs'));
    }

    public function showRevision(Workspace $workspace, Page $page, PageRevision $revision)
    {
        abort_if($page->workspace_id !== $workspace->id, 404);
        abort_if($revision->page_id !== $page->id, 404);
        $this->authorize('view', $page);

        $breadcrumbs = $page->ancestors()->get()->push($page);

        return view('pages.revision', compact('workspace', 'page', 'revision', 'breadcrumbs'));
    }

    public function restoreRevision(Workspace $workspace, Page $page, PageRevision $revision): RedirectResponse
    {
        abort_if($page->workspace_id !== $workspace->id, 404);
        abort_if($revision->page_id !== $page->id, 404);
        $this->authorize('update', $page);

        $lastRevision = $page->revisions()->latest()->first();
        $nextNumber = $lastRevision ? $lastRevision->revision_number + 1 : 1;

        // Apply the old revision's content to the page
        $page->update([
            'title' => $revision->title,
            'content' => $revision->content,
        ]);

        // Record a new revision so the restore itself is auditable
        PageRevision::create([
            'page_id' => $page->id,
            'user_id' => auth()->id(),
            'title' => $revision->title,
            'content' => $revision->content,
            'content_type' => $revision->content_type,
            'revision_number' => $nextNumber,
            'change_summary' => 'Restored from v'.$revision->revision_number,
        ]);

        app(PageLinkResolver::class)->sync($page);
        app(DiagramEmbedRenderer::class)->sync($page);

        return redirect()
            ->route('workspaces.pages.edit', [$workspace, $page])
            ->with('status', 'page-restored');
    }

    public function diffRevisions(Request $request, Workspace $workspace, Page $page): View
    {
        abort_if($page->workspace_id !== $workspace->id, 404);
        $this->authorize('view', $page);

        $request->validate([
            'from' => ['required', 'uuid', Rule::exists('page_revisions', 'id')->where('page_id', $page->id)],
            'to' => ['nullable', 'uuid', Rule::exists('page_revisions', 'id')->where('page_id', $page->id)],
        ]);

        $fromRevision = $page->revisions()->with('user')->findOrFail($request->input('from'));

        if ($request->filled('to')) {
            $toRevision = $page->revisions()->with('user')->findOrFail($request->input('to'));
        } else {
            // Synthetic "current" revision
            $toRevision = (object) [
                'id' => null,
                'title' => $page->title,
                'content' => $page->content,
                'content_type' => $page->content_type,
                'revision_number' => 'Current',
                'created_at' => $page->updated_at,
                'change_summary' => 'Current version',
                'user' => $page->author,
            ];
        }

        // Normalise content to plain text for diffing
        $normalise = function (string $content, string $contentType): string {
            if ($contentType === 'richtext') {
                $content = html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }

            // Split into non-empty lines for better diff granularity
            return $content;
        };

        $oldText = $normalise((string) ($fromRevision->content ?? ''), (string) $fromRevision->content_type);
        $newText = $normalise((string) ($toRevision->content ?? ''), (string) $toRevision->content_type);

        $oldLines = explode("\n", $oldText);
        $newLines = explode("\n", $newText);

        $differOptions = [
            'context' => 3,
            'ignoreCase' => false,
            'ignoreWhitespace' => false,
        ];

        $rendererOptions = [
            'detailLevel' => 'word',
            'lineNumbers' => false,
            'showHeader' => false,
        ];

        $differ = new Differ($oldLines, $newLines, $differOptions);
        $renderer = RendererFactory::make('Inline', $rendererOptions);
        $diffHtml = $renderer->render($differ);

        $breadcrumbs = $page->ancestors()->get()->push($page);

        return view('pages.diff', compact(
            'workspace', 'page', 'breadcrumbs',
            'fromRevision', 'toRevision', 'diffHtml'
        ));
    }

    public function destroy(Workspace $workspace, Page $page): RedirectResponse
    {
        abort_if($page->workspace_id !== $workspace->id, 404);
        $this->authorize('delete', $page);

        // Reparent any children to this page's parent before soft-deleting,
        // so the tree doesn't orphan child pages.
        $page->children()->update(['parent_id' => $page->parent_id]);

        $page->delete();

        return redirect()->route('workspaces.show', $workspace)
            ->with('status', 'page-deleted');
    }

    // ── feat 5.3: Page Export ────────────────────────────────────────────────

    /**
     * GET workspaces/{workspace}/pages/{page}/print
     * Renders a minimal, print-ready view of the page (no nav chrome).
     * Works in any browser — user can File → Print / Save as PDF.
     */
    public function print(Workspace $workspace, Page $page): View
    {
        abort_if($page->workspace_id !== $workspace->id, 404);
        $this->authorize('view', $page);

        $breadcrumbs = $page->ancestors()->get()->push($page);

        return view('pages.print', compact('workspace', 'page', 'breadcrumbs'));
    }

    /**
     * GET workspaces/{workspace}/pages/{page}/export/markdown
     * Downloads the page content as a .md file.
     * Uses league/html-to-markdown for richtext pages; passes markdown through.
     */
    public function exportMarkdown(Workspace $workspace, Page $page): Response
    {
        abort_if($page->workspace_id !== $workspace->id, 404);
        $this->authorize('view', $page);

        if ($page->content_type === 'richtext') {
            $converter = new HtmlConverter([
                'strip_tags' => false,
                'header_style' => 'atx',   // # headings
                'bold_style' => '**',
                'italic_style' => '_',
                'list_item_style' => '-',
                'hard_break' => true,
            ]);
            $markdown = $converter->convert($page->content ?? '');
        } else {
            $markdown = $page->content ?? '';
        }

        // Prepend a YAML front-matter block so the file is useful outside Codex
        $yamlEscape = static function (string $value): string {
            return str_replace(
                ['\\',  '"',   "\r\n", "\r",  "\n"],
                ['\\\\', '\\"', '\\n', '\\n', '\\n'],
                $value
            );
        };

        $frontMatter = implode("\n", [
            '---',
            'title: "'.$yamlEscape($page->title).'"',
            'workspace: "'.$yamlEscape($workspace->name).'"',
            'status: '.$page->status,
            'author: "'.$yamlEscape($page->author?->name ?? '').'"',
            'exported_at: '.now()->toIso8601String(),
            '---',
            '',
        ]);

        $slug = Str::slug($page->title);
        $filename = ($slug !== '' ? $slug : $page->id).'.md';

        return response($frontMatter.$markdown, 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     *   parent_id — UUID of new parent, or null for root level
     *   before_id — UUID of the sibling to insert before, or null to append
     */
    public function move(Request $request, Workspace $workspace, Page $page): JsonResponse
    {
        abort_if($page->workspace_id !== $workspace->id, 404);
        $this->authorize('update', $page);

        $validated = $request->validate([
            'parent_id' => ['nullable', 'uuid', 'exists:pages,id'],
            'before_id' => ['nullable', 'uuid', 'exists:pages,id'],
        ]);

        $parentId = $validated['parent_id'] ?? null;
        $beforeId = $validated['before_id'] ?? null;

        // A page cannot be its own parent or be placed relative to itself
        abort_if($parentId === $page->id, 422, 'A page cannot be its own parent.');
        abort_if($beforeId === $page->id, 422, 'A page cannot be placed relative to itself.');

        // Prevent circular nesting: new parent must not be a descendant of this page
        if ($parentId) {
            $newParent = Page::findOrFail($parentId);
            abort_if(
                $newParent->workspace_id !== $workspace->id,
                422,
                'Parent page belongs to a different workspace.'
            );
            abort_if(
                $page->descendants()->where('id', $parentId)->exists(),
                422,
                'Cannot move a page into one of its own descendants.'
            );
        }

        // Prevent placing relative to a descendant (would corrupt the tree)
        if ($beforeId) {
            abort_if(
                $page->descendants()->where('id', $beforeId)->exists(),
                422,
                'Cannot place a page relative to one of its own descendants.'
            );
        }

        if ($beforeId) {
            $sibling = Page::findOrFail($beforeId);
            abort_if($sibling->workspace_id !== $workspace->id, 422);
            $page->beforeNode($sibling)->save();
        } elseif ($parentId) {
            $parent = Page::findOrFail($parentId);
            $page->appendToNode($parent)->save();
        } else {
            $page->saveAsRoot();
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Moves a page (and all its descendants) to a different workspace.
     * The authenticated user must:
     *   - have update permission on the source page
     *   - be owner OR editor/admin member in the target workspace
     */
    public function transfer(Request $request, Workspace $workspace, Page $page): RedirectResponse
    {
        abort_if($page->workspace_id !== $workspace->id, 404);
        $this->authorize('update', $page);

        $validated = $request->validate([
            'target_workspace_id' => [
                'required', 'uuid',
                Rule::exists('workspaces', 'id')
                    ->whereNull('deleted_at'),
                Rule::notIn([$workspace->id]),
            ],
            'target_parent_id' => ['nullable', 'uuid'],
        ]);

        $targetWorkspace = Workspace::findOrFail($validated['target_workspace_id']);

        // Verify the user has write access to the target workspace
        $user = auth()->user();
        $canWriteTarget = $targetWorkspace->owner_id === $user->id
            || $targetWorkspace->members()
                ->where('user_id', $user->id)
                ->whereIn('role', ['editor', 'admin'])
                ->exists()
            || $user->can('pages.create');

        abort_if(! $canWriteTarget, 403, 'You do not have permission to add pages to that workspace.');

        // Validate the target parent (if provided) belongs to the target workspace
        $targetParent = null;
        if (! empty($validated['target_parent_id'])) {
            $targetParent = Page::where('id', $validated['target_parent_id'])
                ->where('workspace_id', $targetWorkspace->id)
                ->firstOrFail();
        }

        // Collect all descendant IDs before touching the tree
        $descendantIds = $page->descendants()->pluck('id')->toArray();

        // Detach from current tree position cleanly
        $page->saveAsRoot();

        // Reassign workspace for the page and all its descendants
        $page->workspace_id = $targetWorkspace->id;
        $page->parent_id = $targetParent?->id;
        $page->save();

        if (! empty($descendantIds)) {
            Page::whereIn('id', $descendantIds)->update(['workspace_id' => $targetWorkspace->id]);
        }

        // Attach to new parent in target workspace (if specified)
        if ($targetParent) {
            $page->appendToNode($targetParent)->save();
        }

        $movedPages = Page::whereIn('id', array_merge([$page->id], $descendantIds))->get();

        foreach ($movedPages as $movedPage) {
            app(PageLinkResolver::class)->sync($movedPage);
            app(DiagramEmbedRenderer::class)->sync($movedPage);
        }

        return redirect()
            ->route('workspaces.pages.show', [$targetWorkspace, $page])
            ->with('status', 'page-transferred');
    }
}
