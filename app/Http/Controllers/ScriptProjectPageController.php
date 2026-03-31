<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\PageRevision;
use App\Models\PageTemplate;
use App\Models\ScriptProject;
use App\Models\ScriptProjectPageLink;
use App\Models\Workspace;
use App\Services\DiagramEmbedRenderer;
use App\Services\PageLinkResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ScriptProjectPageController extends Controller
{
    public const ROLES = [
        'research',
        'outline',
        'notes',
        'scene_card',
    ];

    public function store(Request $request, Workspace $workspace, ScriptProject $script): RedirectResponse
    {
        $this->authorize('update', $script);

        $validated = $request->validate([
            'page_id' => [
                'required',
                'uuid',
                Rule::exists('pages', 'id')->where('workspace_id', $workspace->id),
            ],
            'role' => ['required', Rule::in(self::ROLES)],
        ]);

        $position = (int) $script->binderLinks()->max('position') + 1;

        $script->binderLinks()->create([
            'page_id' => $validated['page_id'],
            'role' => $validated['role'],
            'position' => $position,
        ]);

        return back()->with('status', 'script-binder-page-attached');
    }

    public function storeFromTemplate(Request $request, Workspace $workspace, ScriptProject $script): RedirectResponse
    {
        $this->authorize('update', $script);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'role' => ['required', Rule::in(self::ROLES)],
            'template_id' => [
                'nullable',
                'uuid',
                Rule::exists('page_templates', 'id'),
            ],
        ]);

        $template = null;
        if (filled($validated['template_id'] ?? null)) {
            $template = PageTemplate::where('id', $validated['template_id'])
                ->where(function ($query) use ($workspace) {
                    $query->where('workspace_id', $workspace->id)->orWhereNull('workspace_id');
                })
                ->firstOrFail();
        }

        $page = Page::create([
            'title' => $validated['title'],
            'content' => $template?->content,
            'content_type' => $template?->content_type ?? 'richtext',
            'workspace_id' => $workspace->id,
            'author_id' => auth()->id(),
            'status' => 'draft',
        ]);

        PageRevision::create([
            'page_id' => $page->id,
            'user_id' => auth()->id(),
            'title' => $page->title,
            'content' => $page->content,
            'content_type' => $page->content_type,
            'revision_number' => 1,
            'change_summary' => 'Created from script binder',
        ]);

        app(PageLinkResolver::class)->sync($page);
        app(DiagramEmbedRenderer::class)->sync($page);

        $script->binderLinks()->create([
            'page_id' => $page->id,
            'role' => $validated['role'],
            'position' => (int) $script->binderLinks()->max('position') + 1,
        ]);

        return back()->with('status', 'script-binder-page-created');
    }

    public function update(Request $request, Workspace $workspace, ScriptProject $script, ScriptProjectPageLink $binderLink): RedirectResponse
    {
        $this->authorize('update', $script);

        abort_unless($binderLink->script_project_id === $script->id, HttpResponse::HTTP_NOT_FOUND);

        $validated = $request->validate([
            'role' => ['required', Rule::in(self::ROLES)],
            'position' => 'required|integer|min:1',
        ]);

        $links = $script->binderLinks()->orderBy('position')->get()->values();
        $targetPosition = min($validated['position'], max($links->count(), 1));

        $links = $links->reject(fn (ScriptProjectPageLink $link) => $link->id === $binderLink->id)->values();
        $links->splice($targetPosition - 1, 0, [$binderLink]);

        foreach ($links->values() as $index => $link) {
            $link->update([
                'role' => $link->id === $binderLink->id ? $validated['role'] : $link->role,
                'position' => $index + 1,
            ]);
        }

        return back()->with('status', 'script-binder-page-updated');
    }

    public function destroy(Workspace $workspace, ScriptProject $script, ScriptProjectPageLink $binderLink): RedirectResponse
    {
        $this->authorize('update', $script);

        abort_unless($binderLink->script_project_id === $script->id, HttpResponse::HTTP_NOT_FOUND);

        $binderLink->delete();

        $script->binderLinks()
            ->orderBy('position')
            ->get()
            ->values()
            ->each(fn (ScriptProjectPageLink $link, int $index) => $link->update(['position' => $index + 1]));

        return back()->with('status', 'script-binder-page-detached');
    }
}
