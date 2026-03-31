<?php

namespace App\Services;

use App\Models\Diagram;
use App\Models\Page;
use App\Models\PageDiagramEmbed;
use App\Models\Workspace;
use App\Support\CodexRuntimeConfig;
use Illuminate\Support\Facades\Gate;

class DiagramEmbedRenderer
{
    private const string UUID_PATTERN = '[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}';

    private const string PATTERN = '/\{\{\s*diagram\s*:\s*('.self::UUID_PATTERN.')\s*}}/iu';

    private const string BLOCK_PATTERN = '/<p>(?:\s|&nbsp;)*\{\{\s*diagram\s*:\s*('.self::UUID_PATTERN.')\s*}}(?:\s|&nbsp;)*<\/p>/iu';

    public function render(string $content, Workspace $workspace, string $variant = 'web'): string
    {
        $ids = $this->extractIds($content);

        if ($ids === []) {
            return $content;
        }

        $diagrams = Diagram::query()
            ->where('workspace_id', $workspace->id)
            ->whereNull('deleted_at')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy(static fn (Diagram $diagram): string => strtolower($diagram->id));

        $viewer = auth()->user();
        $drawioUrl = CodexRuntimeConfig::drawioUrl();

        $renderMatch = static function (array $match) use ($diagrams, $drawioUrl, $variant, $viewer, $workspace): string {
            $diagramId = strtolower(trim($match[1]));
            $diagram = $diagrams->get($diagramId);

            if (! $diagram || ! Gate::forUser($viewer)->allows('view', $diagram)) {
                return view('diagrams._missing_embed', [
                    'diagramId' => $diagramId,
                ])->render();
            }

            return view('diagrams._embed', [
                'diagram' => $diagram,
                'drawioUrl' => $drawioUrl,
                'variant' => $variant,
                'workspace' => $workspace,
            ])->render();
        };

        $content = preg_replace_callback(self::BLOCK_PATTERN, $renderMatch, $content) ?? $content;

        return preg_replace_callback(self::PATTERN, $renderMatch, $content) ?? $content;
    }

    public function sync(Page $page): int
    {
        PageDiagramEmbed::where('page_id', $page->id)->delete();

        $ids = $this->extractIds($page->content ?? '');

        if ($ids === []) {
            return 0;
        }

        $diagramIds = Diagram::query()
            ->where('workspace_id', $page->workspace_id)
            ->whereNull('deleted_at')
            ->whereIn('id', $ids)
            ->pluck('id');

        foreach ($diagramIds as $diagramId) {
            PageDiagramEmbed::firstOrCreate([
                'page_id' => $page->id,
                'diagram_id' => $diagramId,
            ]);
        }

        return $diagramIds->count();
    }

    /**
     * @return array<int, string>
     */
    private function extractIds(string $content): array
    {
        if (! preg_match_all(self::PATTERN, $content, $matches)) {
            return [];
        }

        return array_values(array_unique(array_map(
            static fn (string $id): string => strtolower(trim($id)),
            $matches[1] ?? [],
        )));
    }
}
