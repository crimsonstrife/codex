<?php

namespace App\CommonMark;

use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserState;

/**
 * Detects the opening fence of a callout block: :::type
 *
 * Recognises the following types:
 *   note, tip, warning, danger, success, design-decision
 *
 * The opening line is fully consumed; subsequent lines are parsed as
 * children until the closing ::: is found by CalloutBlockParser.
 */
final class CalloutStartParser implements BlockStartParserInterface
{
    private const TYPES = [
        'note',
        'tip',
        'warning',
        'danger',
        'success',
        'design-decision',
    ];

    public function tryStart(Cursor $cursor, MarkdownParserState $parserState): ?BlockStart
    {
        if ($cursor->isIndented()) {
            return BlockStart::none();
        }

        $line = $cursor->getRemainder();

        if (! preg_match('/^:::([a-z][a-z\-]*)\s*$/', $line, $matches)) {
            return BlockStart::none();
        }

        $type = $matches[1];

        if (! in_array($type, self::TYPES, true)) {
            return BlockStart::none();
        }

        // Consume the entire opening :::type line
        $cursor->advanceToEnd();

        return BlockStart::of(new CalloutBlockParser($type))->at($cursor);
    }
}
