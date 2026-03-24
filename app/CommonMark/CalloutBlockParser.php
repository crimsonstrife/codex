<?php

namespace App\CommonMark;

use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Parser\Block\BlockContinue;
use League\CommonMark\Parser\Block\BlockContinueParserInterface;
use League\CommonMark\Parser\Cursor;

/**
 * Parses the content of a callout block (everything between :::type and :::).
 *
 * Acts as a container block (like blockquote): each line is passed to child
 * block parsers so paragraphs, lists, code blocks, etc. work inside callouts.
 * The closing ::: line signals BlockContinue::finished().
 */
final class CalloutBlockParser implements BlockContinueParserInterface
{
    private CalloutBlock $block;

    public function __construct(string $type)
    {
        $this->block = new CalloutBlock($type);
    }

    public function getBlock(): AbstractBlock
    {
        return $this->block;
    }

    public function isContainer(): bool
    {
        return true;
    }

    public function canHaveLazyContinuationLines(): bool
    {
        return false;
    }

    public function canContain(AbstractBlock $childBlock): bool
    {
        // Accept any child block — paragraphs, lists, code blocks, nested callouts
        return true;
    }

    public function tryContinue(Cursor $cursor, BlockContinueParserInterface $activeBlockParser): ?BlockContinue
    {
        // A lone ::: on its own line closes the block
        if ($cursor->match('/^:::\s*$/')) {
            return BlockContinue::finished();
        }

        return BlockContinue::at($cursor);
    }

    public function addLine(string $line): void
    {
        // Container blocks don't store raw lines; children handle their own content
    }

    public function closeBlock(): void
    {
        // Nothing to finalise
    }
}
