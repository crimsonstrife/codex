<?php

namespace App\Services;

use App\Models\ScriptEntity;
use App\Models\ScriptProject;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ScriptRenderService
{
    public function __construct(
        private readonly ScriptDocumentService $documentService,
    ) {}

    public function render(ScriptProject $scriptProject, ?array $document = null, string $variant = 'web'): string
    {
        $scriptProject->loadMissing('entities');

        $document ??= $scriptProject->document ?? $this->documentService->defaultDocument();
        $blocks = $document['blocks'] ?? [];
        $entities = $scriptProject->entities->keyBy('id');

        return collect($blocks)
            ->map(fn (array $block) => $this->renderBlock($block, $entities, $variant))
            ->implode("\n");
    }

    public function exportFountain(ScriptProject $scriptProject, ?array $document = null): string
    {
        $scriptProject->loadMissing(['entities', 'author']);

        $document ??= $scriptProject->document ?? $this->documentService->defaultDocument();
        $blocks = $document['blocks'] ?? [];
        $entities = $scriptProject->entities->keyBy('id');

        $titlePage = array_filter([
            'Title: '.$scriptProject->title,
            $scriptProject->logline ? 'Logline: '.$scriptProject->logline : null,
            'Author: '.($scriptProject->author?->name ?? 'Unknown'),
            'Draft date: '.now()->toFormattedDateString(),
        ]);

        $body = collect($blocks)
            ->map(fn (array $block) => $this->renderBlockAsFountain($block, $entities))
            ->filter()
            ->implode("\n\n");

        return implode("\n", $titlePage)."\n\n".$body."\n";
    }

    protected function renderBlock(array $block, Collection $entities, string $variant): string
    {
        $type = $block['type'] ?? 'action';
        $text = trim((string) ($block['text'] ?? ''));

        return match ($type) {
            'scene_heading' => '<div class="screenplay-block screenplay-scene-heading">'.$this->escape($this->sceneHeadingLabel($block, $entities)).'</div>',
            'action' => '<div class="screenplay-block screenplay-action">'.$this->multiline($text).'</div>',
            'character_cue' => '<div class="screenplay-block screenplay-character">'.$this->escape($this->characterCueLabel($block, $entities)).'</div>',
            'parenthetical' => '<div class="screenplay-block screenplay-parenthetical">'.$this->escape($this->formatParenthetical($text)).'</div>',
            'dialogue' => '<div class="screenplay-block screenplay-dialogue">'.$this->multiline($text).'</div>',
            'transition' => '<div class="screenplay-block screenplay-transition">'.$this->escape(Str::upper($text)).'</div>',
            default => '',
        };
    }

    protected function renderBlockAsFountain(array $block, Collection $entities): string
    {
        $type = $block['type'] ?? 'action';
        $text = trim((string) ($block['text'] ?? ''));

        return match ($type) {
            'scene_heading' => $this->sceneHeadingLabel($block, $entities),
            'action' => $text,
            'character_cue' => $this->characterCueLabel($block, $entities),
            'parenthetical' => $this->formatParenthetical($text),
            'dialogue' => $text,
            'transition' => '> '.Str::upper($text),
            default => '',
        };
    }

    protected function sceneHeadingLabel(array $block, Collection $entities): string
    {
        $meta = is_array($block['meta'] ?? null) ? $block['meta'] : [];
        $prefix = Str::upper((string) ($meta['prefix'] ?? 'INT.'));
        $timeOfDay = filled($meta['time_of_day'] ?? null)
            ? Str::upper((string) $meta['time_of_day'])
            : null;
        $microLocation = filled($block['text'] ?? null) ? Str::upper((string) $block['text']) : null;
        $locationLabel = null;

        if (filled($block['location_entity_id'] ?? null)) {
            /** @var ScriptEntity|null $location */
            $location = $entities->get($block['location_entity_id']);
            $locationLabel = $location ? Str::upper($location->label()) : null;
        }

        $locationPart = implode(', ', array_filter([$microLocation, $locationLabel]));
        $line = trim($prefix.($locationPart !== '' ? ' '.$locationPart : ''));

        if ($timeOfDay) {
            $line .= ' - '.$timeOfDay;
        }

        return trim($line);
    }

    protected function characterCueLabel(array $block, Collection $entities): string
    {
        $label = null;

        if (filled($block['character_entity_id'] ?? null)) {
            /** @var ScriptEntity|null $character */
            $character = $entities->get($block['character_entity_id']);
            $label = $character?->label();
        }

        $label = Str::upper($label ?: (string) ($block['text'] ?? ''));
        $modifier = filled($block['modifiers'] ?? null)
            ? ' ('.Str::upper((string) $block['modifiers']).')'
            : '';

        return trim($label.$modifier);
    }

    protected function formatParenthetical(string $text): string
    {
        $trimmed = trim($text);

        if ($trimmed === '') {
            return '';
        }

        if (Str::startsWith($trimmed, '(') && Str::endsWith($trimmed, ')')) {
            return $trimmed;
        }

        return '('.$trimmed.')';
    }

    protected function multiline(string $text): string
    {
        return nl2br($this->escape($text), false);
    }

    protected function escape(string $text): string
    {
        return e($text);
    }
}
