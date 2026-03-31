<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Diagram;
use App\Models\Workspace;
use App\Support\CodexRuntimeConfig;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DiagramController extends Controller
{
    public function create(Workspace $workspace)
    {
        $this->authorize('create', Diagram::class);
        $drawioUrl = CodexRuntimeConfig::drawioUrl();
        $categories = Category::where('workspace_id', $workspace->id)
            ->orWhereNull('workspace_id')
            ->orderBy('name')
            ->get();

        return view('diagrams.create', compact('workspace', 'drawioUrl', 'categories'));
    }

    public function show(Workspace $workspace, Diagram $diagram)
    {
        abort_if($diagram->workspace_id !== $workspace->id, 404);
        $this->authorize('view', $diagram);
        $diagram->load([
            'categories',
            'embeddedPages' => fn ($query) => $query
                ->select('pages.id', 'pages.title', 'pages.slug', 'pages.workspace_id')
                ->orderBy('title'),
        ]);
        $drawioUrl = CodexRuntimeConfig::drawioUrl();

        return view('diagrams.show', compact('workspace', 'diagram', 'drawioUrl'));
    }

    public function store(Request $request, Workspace $workspace)
    {
        $this->authorize('create', Diagram::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'diagram_type' => 'in:drawio,mermaid,mindmap,flowchart',
            'diagram_data' => 'nullable|string',
            'is_published' => 'boolean',
            'category_ids' => 'nullable|array',
            'category_ids.*' => ['uuid', Rule::exists('categories', 'id')->where(fn ($q) => $q->where('workspace_id', $workspace->id)->orWhereNull('workspace_id'))],
            'tags' => 'nullable|string',
        ]);

        $validated['workspace_id'] = $workspace->id;
        $validated['author_id'] = auth()->id();

        $diagram = Diagram::create($validated);

        if (! empty($validated['category_ids'])) {
            $diagram->categories()->sync($validated['category_ids']);
        }

        if (! empty($validated['tags'])) {
            $tagNames = array_filter(array_map('trim', explode(',', $validated['tags'])));
            $diagram->syncTags($tagNames);
        }

        return redirect()->route('workspaces.diagrams.show', [$workspace, $diagram])
            ->with('status', 'diagram-created');
    }

    public function edit(Workspace $workspace, Diagram $diagram)
    {
        $this->authorize('update', $diagram);
        $drawioUrl = CodexRuntimeConfig::drawioUrl();
        $categories = Category::where('workspace_id', $workspace->id)
            ->orWhereNull('workspace_id')
            ->orderBy('name')
            ->get();

        return view('diagrams.edit', compact('workspace', 'diagram', 'drawioUrl', 'categories'));
    }

    public function update(Request $request, Workspace $workspace, Diagram $diagram)
    {
        $this->authorize('update', $diagram);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'diagram_data' => 'nullable|string',
            'is_published' => 'boolean',
            'category_ids' => 'nullable|array',
            'category_ids.*' => ['uuid', Rule::exists('categories', 'id')->where(fn ($q) => $q->where('workspace_id', $workspace->id)->orWhereNull('workspace_id'))],
            'tags' => 'nullable|string',
        ]);

        $diagram->update($validated);

        $diagram->categories()->sync($validated['category_ids'] ?? []);

        $tagNames = [];
        if (! empty($validated['tags'])) {
            $tagNames = array_filter(array_map('trim', explode(',', $validated['tags'])));
        }
        $diagram->syncTags($tagNames);

        return redirect()->route('workspaces.diagrams.show', [$workspace, $diagram])
            ->with('status', 'diagram-updated');
    }
}
