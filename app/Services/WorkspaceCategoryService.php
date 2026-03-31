<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Workspace;
use Illuminate\Support\Collection;

class WorkspaceCategoryService
{
    /**
     * Suggested category set for common Confluence-style documentation workspaces.
     */
    private const BUILT_INS = [
        [
            'key' => 'getting-started',
            'name' => 'Getting Started',
            'description' => 'Onboarding guides, setup instructions, and first-week documentation for new teammates.',
            'color' => '#2563eb',
        ],
        [
            'key' => 'architecture',
            'name' => 'Architecture',
            'description' => 'System overviews, diagrams, boundaries, and technical design documentation.',
            'color' => '#7c3aed',
        ],
        [
            'key' => 'apis-integrations',
            'name' => 'APIs & Integrations',
            'description' => 'API references, external service integrations, authentication flows, and contract details.',
            'color' => '#0f766e',
        ],
        [
            'key' => 'runbooks',
            'name' => 'Runbooks',
            'description' => 'Operational checklists, deployment steps, incident response notes, and support procedures.',
            'color' => '#b45309',
        ],
        [
            'key' => 'troubleshooting',
            'name' => 'Troubleshooting',
            'description' => 'Known issues, debugging notes, support fixes, and recovery steps for recurring problems.',
            'color' => '#dc2626',
        ],
        [
            'key' => 'decisions-adrs',
            'name' => 'Decisions & ADRs',
            'description' => 'Architecture decision records, tradeoff writeups, and important project decisions.',
            'color' => '#4f46e5',
        ],
        [
            'key' => 'product-specs',
            'name' => 'Product Specs',
            'description' => 'Requirements, briefs, scope notes, and product planning documents shared across teams.',
            'color' => '#db2777',
        ],
        [
            'key' => 'release-notes',
            'name' => 'Release Notes',
            'description' => 'Launch summaries, change logs, rollout details, and post-release communication artifacts.',
            'color' => '#059669',
        ],
        [
            'key' => 'team-processes',
            'name' => 'Team Processes',
            'description' => 'Meeting rhythms, rituals, ownership maps, working agreements, and internal team SOPs.',
            'color' => '#475569',
        ],
    ];

    public function availableForWorkspace(Workspace $workspace): Collection
    {
        return Category::query()
            ->where('workspace_id', $workspace->id)
            ->orWhereNull('workspace_id')
            ->orderByRaw('CASE WHEN workspace_id IS NULL THEN 1 ELSE 0 END')
            ->orderBy('name')
            ->get();
    }

    public function workspaceCategories(Workspace $workspace): Collection
    {
        return Category::query()
            ->where('workspace_id', $workspace->id)
            ->withCount([
                'pages as pages_count' => fn ($query) => $query->where('workspace_id', $workspace->id),
                'diagrams as diagrams_count' => fn ($query) => $query->where('workspace_id', $workspace->id),
                'scripts as scripts_count' => fn ($query) => $query->where('workspace_id', $workspace->id),
            ])
            ->orderBy('name')
            ->get();
    }

    public function builtInDefinitions(): Collection
    {
        return collect(self::BUILT_INS);
    }

    public function missingBuiltIns(Workspace $workspace): Collection
    {
        $existingSlugs = Category::query()
            ->where('workspace_id', $workspace->id)
            ->pluck('slug')
            ->filter()
            ->all();

        return $this->builtInDefinitions()
            ->reject(fn (array $definition) => in_array($definition['key'], $existingSlugs, true))
            ->values();
    }

    public function inlineCreationRules(): array
    {
        return [
            'new_category_name' => 'nullable|string|max:255',
            'new_category_description' => 'nullable|string|max:500',
            'new_category_color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
        ];
    }

    public function resolveSelectedCategoryIds(Workspace $workspace, array $validated): array
    {
        $categoryIds = collect($validated['category_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (string) $id);

        if (filled($validated['new_category_name'] ?? null)) {
            $category = $this->findOrCreateWorkspaceCategory(
                $workspace,
                $validated['new_category_name'],
                $validated['new_category_description'] ?? null,
                $validated['new_category_color'] ?? null,
            );

            $categoryIds->push((string) $category->id);
        }

        return $categoryIds->unique()->values()->all();
    }

    public function createBuiltIns(Workspace $workspace, array $keys): Collection
    {
        $definitions = $this->builtInDefinitions()->keyBy('key');

        return collect($keys)
            ->filter()
            ->unique()
            ->map(function (string $key) use ($workspace, $definitions) {
                $definition = $definitions->get($key);

                if (! $definition) {
                    return null;
                }

                return $this->findOrCreateWorkspaceCategory(
                    $workspace,
                    $definition['name'],
                    $definition['description'],
                    $definition['color'],
                );
            })
            ->filter()
            ->values();
    }

    public function findOrCreateWorkspaceCategory(
        Workspace $workspace,
        string $name,
        ?string $description = null,
        ?string $color = null
    ): Category {
        $trimmedName = trim($name);

        $existingCategory = Category::query()
            ->where('workspace_id', $workspace->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($trimmedName)])
            ->first();

        if ($existingCategory) {
            return $existingCategory;
        }

        return Category::create([
            'name' => $trimmedName,
            'description' => filled($description) ? trim($description) : null,
            'color' => filled($color) ? $color : null,
            'workspace_id' => $workspace->id,
        ]);
    }
}
