<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Page;
use App\Models\Workspace;
use App\Services\ForgeService;
use App\Support\CodexRuntimeConfig;
use App\Support\PageNavigationResolver;
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
                    ->orWhereHas('members', fn ($m) => $m->where('user_id', auth()->id()))
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
            ->with(['author', 'categories', 'tags'])
            ->defaultOrder()
            ->get();

        $pages = $allPages->toTree();
        $scripts = $workspace->scripts()
            ->with('categories')
            ->withCount(['binderLinks', 'entities'])
            ->latest('updated_at')
            ->get();
        $diagrams = $workspace->diagrams()
            ->with('categories')
            ->latest('updated_at')
            ->get();

        $categoryGroups = Category::query()
            ->where(function ($query) use ($workspace) {
                $query->whereHas('pages', fn ($pageQuery) => $pageQuery->where('workspace_id', $workspace->id))
                    ->orWhereHas('diagrams', fn ($diagramQuery) => $diagramQuery->where('workspace_id', $workspace->id))
                    ->orWhereHas('scripts', fn ($scriptQuery) => $scriptQuery->where('workspace_id', $workspace->id));
            })
            ->with([
                'pages' => fn ($query) => $query
                    ->where('workspace_id', $workspace->id)
                    ->with('author')
                    ->orderBy('title'),
                'diagrams' => fn ($query) => $query
                    ->where('workspace_id', $workspace->id)
                    ->orderBy('title'),
                'scripts' => fn ($query) => $query
                    ->where('workspace_id', $workspace->id)
                    ->withCount(['binderLinks', 'entities'])
                    ->orderBy('title'),
            ])
            ->orderBy('name')
            ->get()
            ->map(function (Category $category): array {
                $pageCount = $category->pages->count();
                $diagramCount = $category->diagrams->count();
                $scriptCount = $category->scripts->count();

                return [
                    'category' => $category,
                    'pageCount' => $pageCount,
                    'diagramCount' => $diagramCount,
                    'scriptCount' => $scriptCount,
                    'totalCount' => $pageCount + $diagramCount + $scriptCount,
                ];
            });

        $uncategorizedPages = $allPages
            ->filter(fn (Page $page) => $page->categories->isEmpty())
            ->values();
        $uncategorizedDiagrams = $diagrams
            ->filter(fn ($diagram) => $diagram->categories->isEmpty())
            ->values();
        $uncategorizedScripts = $scripts
            ->filter(fn ($script) => $script->categories->isEmpty())
            ->values();

        $pageIds = $workspace->pages()->pluck('id');
        $activities = Activity::with(['causer', 'subject'])
            ->whereIn('subject_id', $pageIds)
            ->where('subject_type', Page::class)
            ->latest()
            ->take(20)
            ->get();

        // Members for the sidebar strip (cap display at 6; pass total count for "+N" overflow)
        $memberCount = $workspace->members()->count();
        $membersPreview = $workspace->members()->orderBy('name')->limit(6)->get();

        $pinnedPages = $workspace->pinnedPages()->get();

        $homePage = null;
        $homePageChildPages = collect();
        $homePagePrevious = null;
        $homePageNext = null;
        if ($workspace->home_page_id) {
            $homePage = $workspace->homePage()
                ->with(['author', 'categories', 'tags',
                    'comments.user', 'comments.replies.user'])
                ->first();

            if ($homePage) {
                [
                    'childPages' => $homePageChildPages,
                    'previousPage' => $homePagePrevious,
                    'nextPage' => $homePageNext,
                ] = app(PageNavigationResolver::class)->resolve($allPages, $homePage);
            }
        }

        return view('workspaces.show', compact(
            'workspace', 'pages', 'allPages', 'scripts', 'diagrams', 'activities',
            'membersPreview', 'memberCount', 'pinnedPages', 'homePage',
            'homePageChildPages', 'homePagePrevious', 'homePageNext',
            'categoryGroups', 'uncategorizedPages', 'uncategorizedDiagrams', 'uncategorizedScripts'
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_public' => 'boolean',
            'color' => 'nullable|string|max:7',
        ]);

        $validated['owner_id'] = auth()->id();

        $workspace = Workspace::create($validated);

        return redirect()->route('workspaces.show', $workspace)
            ->with('status', 'workspace-created');
    }

    public function edit(Workspace $workspace)
    {
        $this->authorize('update', $workspace);

        $memberCount = $workspace->members()->count();
        $membersPreview = $workspace->members()->orderBy('name')->limit(5)->get();

        $forgeProjects = [];
        if (CodexRuntimeConfig::forgeApiConfigured()) {
            // Pass the current user's Forge ID so only their projects are shown.
            // If they haven't linked their Forge account, getProjects() returns [].
            $forgeUserId = auth()->user()?->forge_user_id;
            $forgeProjects = app(ForgeService::class)->getProjects($forgeUserId);
        }

        return view('workspaces.edit', compact('workspace', 'membersPreview', 'memberCount', 'forgeProjects'));
    }

    public function update(Request $request, Workspace $workspace)
    {
        $this->authorize('update', $workspace);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'nullable|string|max:10',
            'is_public' => 'boolean',
            'forge_project_id' => 'nullable|string|max:255',
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
