<?php

namespace App\CommonMark;

use League\CommonMark\Node\Block\AbstractBlock;

/**
 * AST node representing a callout / admonition block.
 *
 * Rendered from :::type ... ::: fenced blocks in Markdown source.
 * Supported types: note, tip, warning, danger, success, design-decision
 */
final class CalloutBlock extends AbstractBlock
{
    public function __construct(private readonly string $calloutType) {}

    public function getCalloutType(): string
    {
        return $this->calloutType;
    }
}
