<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Models\Workspace;
use App\Services\DiagramEmbedRenderer;
use Illuminate\Console\Command;

class BackfillDiagramEmbedsCommand extends Command
{
    protected $signature = 'codex:backfill-diagram-embeds
                            {--workspace= : Limit the backfill to a workspace UUID or slug}';

    protected $description = 'Backfill embedded diagram relationships for existing pages.';

    public function handle(DiagramEmbedRenderer $diagramEmbedRenderer): int
    {
        $query = Page::query()
            ->select(['id', 'workspace_id', 'content']);

        $workspaceOption = $this->option('workspace');

        if (filled($workspaceOption)) {
            $workspace = Workspace::query()
                ->whereKey($workspaceOption)
                ->orWhere('slug', $workspaceOption)
                ->first();

            if (! $workspace) {
                $this->error('Workspace not found for the provided UUID or slug.');

                return self::FAILURE;
            }

            $query->where('workspace_id', $workspace->id);

            $this->line('Backfilling diagram embeds for workspace: <comment>'.$workspace->name.'</comment>');
        } else {
            $this->line('Backfilling diagram embeds for all workspaces.');
        }

        $totalPages = (clone $query)->count();

        if ($totalPages === 0) {
            $this->info('No pages found to process.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($totalPages);
        $bar->start();

        $pagesWithEmbeds = 0;
        $trackedEmbeds = 0;

        $query
            ->orderBy('id')
            ->chunk(100, function ($pages) use ($diagramEmbedRenderer, &$pagesWithEmbeds, &$trackedEmbeds, $bar): void {
                foreach ($pages as $page) {
                    $embedCount = $diagramEmbedRenderer->sync($page);

                    if ($embedCount > 0) {
                        $pagesWithEmbeds++;
                        $trackedEmbeds += $embedCount;
                    }

                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);
        $this->info('Diagram embed backfill complete.');
        $this->line('Pages scanned: <comment>'.$totalPages.'</comment>');
        $this->line('Pages with embeds: <comment>'.$pagesWithEmbeds.'</comment>');
        $this->line('Tracked embeds: <comment>'.$trackedEmbeds.'</comment>');

        return self::SUCCESS;
    }
}
