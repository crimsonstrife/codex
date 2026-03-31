<?php

namespace App\Http\Controllers;

use App\Models\Diagram;
use App\Models\Workspace;
use App\Services\WorkspaceCategoryService;
use App\Support\CodexRuntimeConfig;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DiagramController extends Controller
{
    public function create(Workspace $workspace)
    {
        $this->authorize('create', Diagram::class);
        $drawioUrl = CodexRuntimeConfig::drawioUrl();
        $categories = app(WorkspaceCategoryService::class)->availableForWorkspace($workspace);

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
            ...app(WorkspaceCategoryService::class)->inlineCreationRules(),
        ]);

        $validated['workspace_id'] = $workspace->id;
        $validated['author_id'] = auth()->id();

        $categoryIds = app(WorkspaceCategoryService::class)->resolveSelectedCategoryIds($workspace, $validated);

        $diagram = Diagram::create($validated);

        if ($categoryIds !== []) {
            $diagram->categories()->sync($categoryIds);
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
        $categories = app(WorkspaceCategoryService::class)->availableForWorkspace($workspace);

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
            ...app(WorkspaceCategoryService::class)->inlineCreationRules(),
        ]);

        $categoryIds = app(WorkspaceCategoryService::class)->resolveSelectedCategoryIds($workspace, $validated);

        $diagram->update($validated);

        $diagram->categories()->sync($categoryIds);

        $tagNames = [];
        if (! empty($validated['tags'])) {
            $tagNames = array_filter(array_map('trim', explode(',', $validated['tags'])));
        }
        $diagram->syncTags($tagNames);

        return redirect()->route('workspaces.diagrams.show', [$workspace, $diagram])
            ->with('status', 'diagram-updated');
    }
}
