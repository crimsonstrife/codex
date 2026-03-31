<?php

namespace App\Http\Controllers;

use App\Models\ScriptEntity;
use App\Models\ScriptProject;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ScriptEntityController extends Controller
{
    public function store(Request $request, Workspace $workspace, ScriptProject $script): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $script);

        $validated = $request->validate([
            'type' => 'required|in:character,location',
            'name' => 'required|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'aliases' => 'nullable|string',
            'notes' => 'nullable|string',
            'hierarchy_text' => 'nullable|string|max:255',
        ]);

        $entity = $script->entities()->create([
            'created_by' => auth()->id(),
            'type' => $validated['type'],
            'name' => $validated['name'],
            'display_name' => $validated['display_name'] ?? null,
            'aliases' => $this->parseAliases($validated['aliases'] ?? null),
            'notes' => $validated['notes'] ?? null,
            'hierarchy_text' => $validated['hierarchy_text'] ?? null,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $entity->id,
                'type' => $entity->type,
                'label' => $entity->label(),
            ]);
        }

        return back()->with('status', 'script-entity-saved');
    }

    public function update(Request $request, Workspace $workspace, ScriptProject $script, ScriptEntity $entity): RedirectResponse
    {
        $this->authorize('update', $script);

        abort_unless($entity->script_project_id === $script->id, HttpResponse::HTTP_NOT_FOUND);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'aliases' => 'nullable|string',
            'notes' => 'nullable|string',
            'hierarchy_text' => 'nullable|string|max:255',
        ]);

        $entity->update([
            'name' => $validated['name'],
            'display_name' => $validated['display_name'] ?? null,
            'aliases' => $this->parseAliases($validated['aliases'] ?? null),
            'notes' => $validated['notes'] ?? null,
            'hierarchy_text' => $validated['hierarchy_text'] ?? null,
        ]);

        return back()->with('status', 'script-entity-saved');
    }

    public function destroy(Workspace $workspace, ScriptProject $script, ScriptEntity $entity): RedirectResponse
    {
        $this->authorize('update', $script);

        abort_unless($entity->script_project_id === $script->id, HttpResponse::HTTP_NOT_FOUND);

        $entity->delete();

        return back()->with('status', 'script-entity-deleted');
    }

    protected function parseAliases(?string $aliases): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $aliases))));
    }
}
