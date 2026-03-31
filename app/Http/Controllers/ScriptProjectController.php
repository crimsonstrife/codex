<?php

namespace App\Http\Controllers;

use App\Models\PageTemplate;
use App\Models\ScriptProject;
use App\Models\ScriptRevision;
use App\Models\Workspace;
use App\Services\ScriptDocumentService;
use App\Services\ScriptRenderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ScriptProjectController extends Controller
{
    public function __construct(
        private readonly ScriptDocumentService $documentService,
        private readonly ScriptRenderService $renderService,
    ) {}

    public function create(Workspace $workspace): View
    {
        $this->authorize('view', $workspace);
        $this->ensureUserCanCreateScript($workspace);

        return view('scripts.create', compact('workspace'));
    }

    public function store(Request $request, Workspace $workspace): RedirectResponse
    {
        $this->authorize('view', $workspace);
        $this->ensureUserCanCreateScript($workspace);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'logline' => 'nullable|string|max:255',
            'synopsis' => 'nullable|string',
            'status' => 'required|in:draft,published,archived',
        ]);

        $scriptProject = ScriptProject::create([
            ...$validated,
            'workspace_id' => $workspace->id,
            'author_id' => auth()->id(),
            'type' => ScriptProject::TYPE_SCREENPLAY,
            'document' => $this->documentService->defaultDocument(),
        ]);

        $this->createRevision($scriptProject, 'Initial draft');

        return redirect()
            ->route('workspaces.scripts.edit', [$workspace, $scriptProject])
            ->with('status', 'script-created');
    }

    public function show(Workspace $workspace, ScriptProject $script): View
    {
        $this->authorize('view', $script);

        $script->load([
            'author',
            'entities',
            'binderLinks.page.author',
            'revisions.user',
        ]);

        $availablePages = $workspace->pages()
            ->whereNotIn('id', $script->binderLinks->pluck('page_id'))
            ->orderBy('title')
            ->get(['id', 'title']);

        $templates = PageTemplate::where(function ($query) use ($workspace) {
            $query->where('workspace_id', $workspace->id)
                ->orWhereNull('workspace_id');
        })->orderByDesc('is_system')->orderBy('name')->get();

        $renderedScript = $this->renderService->render($script);

        return view('scripts.show', compact('workspace', 'script', 'availablePages', 'templates', 'renderedScript'));
    }

    public function edit(Workspace $workspace, ScriptProject $script): View
    {
        $this->authorize('update', $script);
        $script->load(['characters', 'locations']);

        return view('scripts.edit', compact('workspace', 'script'));
    }

    public function update(Request $request, Workspace $workspace, ScriptProject $script): RedirectResponse
    {
        $this->authorize('update', $script);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'logline' => 'nullable|string|max:255',
            'synopsis' => 'nullable|string',
            'status' => 'required|in:draft,published,archived',
            'document' => 'required|string',
            'change_summary' => 'nullable|string|max:255',
        ]);

        $document = $this->documentService->parse($validated['document'], $script);

        $script->update([
            'title' => $validated['title'],
            'logline' => $validated['logline'] ?? null,
            'synopsis' => $validated['synopsis'] ?? null,
            'status' => $validated['status'],
            'document' => $document,
        ]);

        $this->createRevision($script, $validated['change_summary'] ?? null);

        return redirect()
            ->route('workspaces.scripts.show', [$workspace, $script])
            ->with('status', 'script-updated');
    }

    public function history(Workspace $workspace, ScriptProject $script): View
    {
        $this->authorize('view', $script);

        $revisions = $script->revisions()
            ->with('user')
            ->paginate(20);

        return view('scripts.history', compact('workspace', 'script', 'revisions'));
    }

    public function showRevision(Workspace $workspace, ScriptProject $script, ScriptRevision $revision): View
    {
        $this->authorize('view', $script);

        abort_unless($revision->script_project_id === $script->id, HttpResponse::HTTP_NOT_FOUND);

        $script->loadMissing('entities');
        $renderedScript = $this->renderService->render($script, $revision->document ?? $this->documentService->defaultDocument());

        return view('scripts.revision', compact('workspace', 'script', 'revision', 'renderedScript'));
    }

    public function print(Workspace $workspace, ScriptProject $script): View
    {
        $this->authorize('view', $script);

        $script->load(['workspace', 'author', 'entities']);
        $renderedScript = $this->renderService->render($script, variant: 'print');

        return view('scripts.print', compact('workspace', 'script', 'renderedScript'));
    }

    public function exportFountain(Workspace $workspace, ScriptProject $script): Response
    {
        $this->authorize('view', $script);

        $body = $this->renderService->exportFountain($script);

        return response($body, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$script->slug.'.fountain"',
        ]);
    }

    public function destroy(Workspace $workspace, ScriptProject $script): RedirectResponse
    {
        $this->authorize('delete', $script);

        $script->delete();

        return redirect()
            ->route('workspaces.show', $workspace)
            ->with('status', 'script-deleted');
    }

    protected function ensureUserCanCreateScript(Workspace $workspace): void
    {
        $user = auth()->user();

        $canCreate = $workspace->owner_id === $user->id
            || $workspace->members()->where('user_id', $user->id)->exists()
            || $user->can('scripts.create')
            || $user->can('pages.create');

        if (! $canCreate) {
            abort(HttpResponse::HTTP_FORBIDDEN, 'You are not allowed to create scripts in this workspace.');
        }
    }

    protected function createRevision(ScriptProject $scriptProject, ?string $summary): void
    {
        $lastRevision = $scriptProject->revisions()->latest()->first();

        ScriptRevision::create([
            'script_project_id' => $scriptProject->id,
            'user_id' => auth()->id(),
            'title' => $scriptProject->title,
            'status' => $scriptProject->status,
            'logline' => $scriptProject->logline,
            'synopsis' => $scriptProject->synopsis,
            'document' => $scriptProject->document,
            'revision_number' => $lastRevision ? $lastRevision->revision_number + 1 : 1,
            'change_summary' => $summary,
        ]);
    }
}
