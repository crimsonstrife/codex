<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\PageTemplate;
use App\Models\Workspace;
use Illuminate\Http\Request;

/**
 * Sprint 12.2 — Page Template management.
 *
 * Handles viewing, saving, and deleting workspace page templates.
 * System templates (is_system = true) are read-only.
 */
class PageTemplateController extends Controller
{
    /**
     * List all templates available in this workspace (workspace + system).
     */
    public function index(Workspace $workspace)
    {
        $this->authorize('view', $workspace);

        $templates = PageTemplate::where(function ($q) use ($workspace) {
            $q->where('workspace_id', $workspace->id)
              ->orWhereNull('workspace_id');
        })
            ->with('creator')
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get();

        return view('pages.templates.index', compact('workspace', 'templates'));
    }

    /**
     * Save a page's current content as a new workspace template.
     *
     * POST /workspaces/{workspace}/pages/{page}/save-as-template
     */
    public function storeFromPage(Request $request, Workspace $workspace, Page $page)
    {
        abort_if($page->workspace_id !== $workspace->id, 404);
        $this->authorize('update', $page);

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        PageTemplate::create([
            'workspace_id' => $workspace->id,
            'created_by'   => auth()->id(),
            'name'         => $validated['name'],
            'description'  => $validated['description'] ?? null,
            'content'      => $page->content,
            'content_type' => $page->content_type,
            'is_system'    => false,
        ]);

        return back()->with('status', 'template-saved');
    }

    /**
     * Delete a workspace template (owners/admins only; system templates protected).
     */
    public function destroy(Workspace $workspace, PageTemplate $template)
    {
        if ($template->is_system) {
            abort(403, 'System templates cannot be deleted.');
        }

        abort_if($template->workspace_id !== $workspace->id, 404);
        $this->authorize('update', $workspace);

        $template->delete();

        return redirect()
            ->route('workspaces.templates.index', $workspace)
            ->with('status', 'template-deleted');
    }
}
