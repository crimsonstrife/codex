<?php

namespace App\CommonMark;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ExtensionInterface;

/**
 * CommonMark extension that adds :::type … ::: callout / admonition blocks.
 *
 * Register in config/markdown.php under 'extensions':
 *   \App\CommonMark\CalloutExtension::class
 *
 * Usage in Markdown:
 *   :::note
 *   This is a note.
 *   :::
 *
 *   :::warning
 *   **Watch out!** Something to be careful about.
 *   :::
 *
 * Supported types: note, tip, warning, danger, success, design-decision
 */
final class CalloutExtension implements ExtensionInterface
{
    public function register(EnvironmentBuilderInterface $environment): void
    {
        // Priority 70 — higher than paragraph (50) so callouts take precedence
        $environment->addBlockStartParser(new CalloutStartParser(), 70);
        $environment->addRenderer(CalloutBlock::class, new CalloutBlockRenderer());
    }
}
