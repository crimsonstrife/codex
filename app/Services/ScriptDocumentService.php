<?php

namespace App\Services;

use App\Models\ScriptEntity;
use App\Models\ScriptProject;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ScriptDocumentService
{
    public const BLOCK_TYPES = [
        'scene_heading',
        'action',
        'character_cue',
        'parenthetical',
        'dialogue',
        'transition',
    ];

    public const CHARACTER_MODIFIERS = [
        'O.S.',
        'V.O.',
        'CONT\'D',
    ];

    public const SCENE_PREFIXES = [
        'INT.',
        'EXT.',
        'INT./EXT.',
    ];

    public function parse(mixed $payload, ?ScriptProject $scriptProject = null): array
    {
        $document = is_array($payload)
            ? $payload
            : json_decode((string) $payload, true);

        if (! is_array($document)) {
            throw ValidationException::withMessages([
                'document' => 'The screenplay document is invalid JSON.',
            ]);
        }

        $rawBlocks = array_values($document['blocks'] ?? []);
        $entities = $scriptProject
            ? $scriptProject->entities()->get()->keyBy('id')
            : collect();

        $blocks = [];
        $lastCharacterCueEntityId = null;

        foreach ($rawBlocks as $index => $block) {
            if (! is_array($block)) {
                continue;
            }

            $type = (string) ($block['type'] ?? 'action');
            if (! in_array($type, self::BLOCK_TYPES, true)) {
                throw ValidationException::withMessages([
                    'document' => 'Unknown screenplay block type at line '.($index + 1).'.',
                ]);
            }

            $text = trim((string) ($block['text'] ?? ''));
            $characterEntityId = $this->nullableString($block['character_entity_id'] ?? null);
            $locationEntityId = $this->nullableString($block['location_entity_id'] ?? null);
            $modifiers = $this->nullableString($block['modifiers'] ?? null);
            $meta = is_array($block['meta'] ?? null) ? $block['meta'] : [];

            if ($type === 'scene_heading') {
                $meta['prefix'] = in_array(($meta['prefix'] ?? 'INT.'), self::SCENE_PREFIXES, true)
                    ? $meta['prefix']
                    : 'INT.';
                $meta['time_of_day'] = filled($meta['time_of_day'] ?? null)
                    ? Str::upper(trim((string) $meta['time_of_day']))
                    : null;
            } else {
                $meta = [];
            }

            if ($modifiers !== null) {
                $modifiers = Str::upper($modifiers);
                if (! in_array($modifiers, self::CHARACTER_MODIFIERS, true)) {
                    throw ValidationException::withMessages([
                        'document' => 'Unsupported character cue modifier at line '.($index + 1).'.',
                    ]);
                }
            }

            $isMeaningful = $text !== ''
                || $characterEntityId !== null
                || $locationEntityId !== null
                || ($type === 'scene_heading' && filled($meta['time_of_day'] ?? null));

            if (! $isMeaningful) {
                continue;
            }

            if ($characterEntityId !== null) {
                $this->assertEntityType($entities, $characterEntityId, ScriptEntity::TYPE_CHARACTER, $index);
            }

            if ($locationEntityId !== null) {
                $this->assertEntityType($entities, $locationEntityId, ScriptEntity::TYPE_LOCATION, $index);
            }

            if ($type === 'character_cue') {
                if ($characterEntityId === null) {
                    throw ValidationException::withMessages([
                        'document' => 'Character cue at line '.($index + 1)." must be linked to a saved character. Use 'Create Character' in the editor first.",
                    ]);
                }

                $lastCharacterCueEntityId = $characterEntityId;
            }

            if ($type === 'dialogue' && $lastCharacterCueEntityId === null) {
                throw ValidationException::withMessages([
                    'document' => 'Dialogue must come after a character cue that is linked to a character.',
                ]);
            }

            if ($type === 'scene_heading' || $type === 'action' || $type === 'transition') {
                $lastCharacterCueEntityId = null;
            }

            $blocks[] = [
                'id' => filled($block['id'] ?? null) ? (string) $block['id'] : Str::uuid()->toString(),
                'type' => $type,
                'text' => $text,
                'position' => count($blocks) + 1,
                'character_entity_id' => $characterEntityId,
                'location_entity_id' => $locationEntityId,
                'modifiers' => $modifiers,
                'meta' => $meta,
            ];
        }

        if ($blocks === []) {
            return ScriptProject::defaultDocument();
        }

        return [
            'version' => 1,
            'blocks' => $blocks,
        ];
    }

    public function defaultDocument(): array
    {
        return ScriptProject::defaultDocument();
    }

    protected function assertEntityType(Collection $entities, string $id, string $type, int $index): void
    {
        $entity = $entities->get($id);

        if (! $entity || $entity->type !== $type) {
            throw ValidationException::withMessages([
                'document' => 'Referenced screenplay entity at line '.($index + 1).' is invalid.',
            ]);
        }
    }

    protected function nullableString(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return $value !== '' ? $value : null;
    }
}
