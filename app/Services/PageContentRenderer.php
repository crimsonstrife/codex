<?php

namespace App\Services;

use App\Models\Workspace;
use Spatie\LaravelMarkdown\MarkdownRenderer;

class PageContentRenderer
{
    public function __construct(
        private readonly MarkdownRenderer $markdownRenderer,
        private readonly PageLinkResolver $pageLinkResolver,
        private readonly DiagramEmbedRenderer $diagramEmbedRenderer,
    ) {}

    public function render(
        ?string $content,
        string $contentType,
        Workspace $workspace,
        string $variant = 'web',
    ): string {
        $rawContent = $content ?? '';

        $rendered = $contentType === 'markdown'
            ? $this->markdownRenderer->toHtml($rawContent)
            : $rawContent;

        $rendered = $this->pageLinkResolver->render($rendered, $workspace);

        return $this->diagramEmbedRenderer->render($rendered, $workspace, $variant);
    }
}
