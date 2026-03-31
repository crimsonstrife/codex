<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Workspace;
use App\Services\WorkspaceCategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WorkspaceCategoryController extends Controller
{
    public function __construct(
        private readonly WorkspaceCategoryService $categoryService,
    ) {}

    public function index(Workspace $workspace): View
    {
        $this->authorize('update', $workspace);

        $categories = $this->categoryService->workspaceCategories($workspace);
        $suggestedCategories = $this->categoryService->missingBuiltIns($workspace);

        return view('workspaces.categories.index', compact('workspace', 'categories', 'suggestedCategories'));
    }

    public function store(Request $request, Workspace $workspace): RedirectResponse
    {
        $this->authorize('update', $workspace);

        $builtInKeys = $this->categoryService->builtInDefinitions()->pluck('key')->all();

        $validated = $request->validate([
            'name' => [
                'nullable',
                'string',
                'max:255',
                'required_without_all:built_in_key,built_in_keys',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (is_string($value) && trim($value) === '') {
                        $fail('The category name field is required.');
                    }
                },
            ],
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'built_in_key' => ['nullable', 'string', Rule::in($builtInKeys)],
            'built_in_keys' => 'nullable|array',
            'built_in_keys.*' => ['string', Rule::in($builtInKeys)],
        ]);

        $selectedBuiltInKeys = collect($validated['built_in_keys'] ?? [])
            ->when(filled($validated['built_in_key'] ?? null), fn ($keys) => $keys->push($validated['built_in_key']))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($selectedBuiltInKeys !== []) {
            $created = $this->categoryService->createBuiltIns($workspace, $selectedBuiltInKeys);

            return redirect()
                ->route('workspaces.categories.index', $workspace)
                ->with('status', $created->count() > 1 ? 'categories-added' : 'category-created');
        }

        $this->categoryService->findOrCreateWorkspaceCategory(
            $workspace,
            $validated['name'],
            $validated['description'] ?? null,
            $validated['color'] ?? null,
        );

        return redirect()
            ->route('workspaces.categories.index', $workspace)
            ->with('status', 'category-created');
    }

    public function update(Request $request, Workspace $workspace, Category $category): RedirectResponse
    {
        $this->authorize('update', $workspace);
        abort_if($category->workspace_id !== $workspace->id, 404);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (trim((string) $value) === '') {
                        $fail('The category name field is required.');
                    }
                },
                function (string $attribute, mixed $value, \Closure $fail) use ($workspace, $category) {
                    $slug = Str::slug((string) $value);

                    $exists = Category::query()
                        ->where('workspace_id', $workspace->id)
                        ->where('slug', $slug)
                        ->where('id', '!=', $category->id)
                        ->exists();

                    if ($exists) {
                        $fail('A category with a similar name already exists in this workspace.');
                    }
                },
            ],
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $category->update([
            'name' => trim($validated['name']),
            'description' => filled($validated['description'] ?? null) ? trim($validated['description']) : null,
            'color' => filled($validated['color'] ?? null) ? $validated['color'] : null,
        ]);

        return redirect()
            ->route('workspaces.categories.index', $workspace)
            ->with('status', 'category-updated');
    }

    public function destroy(Workspace $workspace, Category $category): RedirectResponse
    {
        $this->authorize('update', $workspace);
        abort_if($category->workspace_id !== $workspace->id, 404);

        $category->delete();

        return redirect()
            ->route('workspaces.categories.index', $workspace)
            ->with('status', 'category-deleted');
    }
}
