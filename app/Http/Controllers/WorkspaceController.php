<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Activitylog\Models\Activity;

class WorkspaceController extends Controller
{
    public function index()
    {
        $workspaces = Workspace::withCount('pages')
            ->where(function ($q) {
                $q->where('is_public', true)
                    ->orWhereHas('members', fn($m) => $m->where('user_id', auth()->id()))
                    ->orWhere('owner_id', auth()->id());
            })
            ->paginate(12);

        return view('workspaces.index', compact('workspaces'));
    }

    public function show(Request $request, Workspace $workspace)
    {
        $this->authorize('view', $workspace);

        // Load pages flat (with author + tags for the table view), then derive tree in-memory.
        $allPages = $workspace->pages()
            ->with(['author', 'tags'])
            ->defaultOrder()
            ->get();

        $pages    = $allPages->toTree();
        $diagrams = $workspace->diagrams()->latest('updated_at')->get();

        $pageIds    = $workspace->pages()->pluck('id');
        $activities = Activity::with(['causer', 'subject'])
            ->whereIn('subject_id', $pageIds)
            ->where('subject_type', Page::class)
            ->latest()
            ->take(20)
            ->get();

        // Members for the sidebar strip (cap display at 6; pass total count for "+N" overflow)
        $memberCount    = $workspace->members()->count();
        $membersPreview = $workspace->members()->orderBy('name')->limit(6)->get();

        $pinnedPages = $workspace->pinnedPages()->get();

        $homePage = null;
        if ($workspace->home_page_id) {
            $homePage = $workspace->homePage()
                ->with(['author', 'categories', 'tags', 'children',
                        'comments.user', 'comments.replies.user'])
                ->first();
        }

        return view('workspaces.show', compact(
            'workspace', 'pages', 'allPages', 'diagrams', 'activities',
            'membersPreview', 'memberCount', 'pinnedPages', 'homePage'
        ));
    }

    public function create()
    {
        $this->authorize('create', Workspace::class);

        return view('workspaces.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Workspace::class);
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_public'   => 'boolean',
            'color'       => 'nullable|string|max:7',
        ]);

        $validated['owner_id'] = auth()->id();

        $workspace = Workspace::create($validated);

        return redirect()->route('workspaces.show', $workspace)
            ->with('status', 'workspace-created');
    }

    public function edit(Workspace $workspace)
    {
        $this->authorize('update', $workspace);

        $memberCount    = $workspace->members()->count();
        $membersPreview = $workspace->members()->orderBy('name')->limit(5)->get();

        $forgeProjects = [];
        if (config('codex.forge.enabled')) {
            // Pass the current user's Forge ID so only their projects are shown.
            // If they haven't linked their Forge account, getProjects() returns [].
            $forgeUserId = auth()->user()?->forge_user_id;
            $forgeProjects = app(\App\Services\ForgeService::class)->getProjects($forgeUserId);
        }

        return view('workspaces.edit', compact('workspace', 'membersPreview', 'memberCount', 'forgeProjects'));
    }

    public function update(Request $request, Workspace $workspace)
    {
        $this->authorize('update', $workspace);

        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'description'       => 'nullable|string',
            'color'             => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon'              => 'nullable|string|max:10',
            'is_public'         => 'boolean',
            'forge_project_id'  => 'nullable|string|max:255',
            'forge_project_key' => 'nullable|string|max:255',
        ]);

        if ($request->exists('is_public')) {
            $validated['is_public'] = $request->boolean('is_public');
        }

        if ($request->exists('forge_project_id') || $request->exists('forge_project_key')) {
            $forgeProjectId = $validated['forge_project_id'] ?? null;
            $forgeProjectKey = $validated['forge_project_key'] ?? null;

            // Normalize empty strings to null for Forge fields so unlinking clears the columns
            $validated['forge_project_id'] = $forgeProjectId ?: null;
            $validated['forge_project_key'] = $validated['forge_project_id']
                ? ($forgeProjectKey ?: null)
                : null;
        }

        $workspace->update($validated);

        return redirect()->route('workspaces.show', $workspace)
            ->with('status', 'workspace-updated');
    }

    /**
     * Sets or clears the workspace's embedded home page.
     * Only workspace owners/admins may change this setting.
     *   page_id — UUID of the page to embed as the workspace home, or empty to clear.
     */
    public function setHomePage(Request $request, Workspace $workspace): RedirectResponse
    {
        $this->authorize('update', $workspace);

        $validated = $request->validate([
            'page_id' => [
                'nullable',
                'uuid',
                Rule::exists('pages', 'id')->where('workspace_id', $workspace->id),
            ],
        ]);

        $workspace->update(['home_page_id' => $validated['page_id'] ?? null]);

        $status = ($validated['page_id'] ?? null) ? 'home-page-set' : 'home-page-cleared';

        // Always return to the workspace hub so the result is visible immediately
        return redirect()
            ->route('workspaces.show', $workspace)
            ->with('status', $status);
    }
}
