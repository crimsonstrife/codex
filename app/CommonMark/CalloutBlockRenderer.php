<?php

namespace App\CommonMark;

use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

/**
 * Renders a CalloutBlock as:
 *   <div class="callout callout-{type}">…children…</div>
 *
 * CSS for the callout classes lives in resources/css/app.css and is replicated
 * in TinyMCE's content_style so the editor preview matches the public view.
 */
final class CalloutBlockRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): \Stringable|string
    {
        assert($node instanceof CalloutBlock);

        $type    = $node->getCalloutType();
        $content = $childRenderer->renderNodes($node->children());

        return new HtmlElement(
            'div',
            ['class' => 'callout callout-' . $type],
            $content
        );
    }
}
