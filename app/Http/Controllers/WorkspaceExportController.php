<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Workspace;
use App\Services\PageLinkResolver;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Spatie\LaravelMarkdown\MarkdownRenderer;
use ZipArchive;

/**
 * Sprint 11.1 — Workspace ZIP Export
 *
 * Exports every page in a workspace as self-contained HTML files inside a ZIP
 * archive. The archive layout is:
 *
 *   workspace-slug-YYYY-MM-DD.zip
 *   ├── index.html             (workspace overview with nested page index)
 *   ├── assets/
 *   │   └── export.css         (standalone stylesheet — callouts, typography, etc.)
 *   └── pages/
 *       ├── {slug}.html        (one file per page, linked to each other)
 *       └── …
 *
 * [[wiki links]] and internal /workspaces/…/pages/… URLs are rewritten to
 * relative paths so the ZIP is navigable offline.  Attachment files are
 * listed as absolute links back to the live app (downloading binary blobs
 * into the ZIP would make it impractically large for media-heavy workspaces).
 */
class WorkspaceExportController extends Controller
{
    public function __invoke(Workspace $workspace)
    {
        $this->authorize('view', $workspace);

        // Single query — eager-load everything the export needs.
        // toTree() is called later in-memory (no second DB round-trip).
        $pages = $workspace->pages()
            ->with(['author', 'tags', 'categories'])
            ->defaultOrder()
            ->get();

        $pagesById = $pages->keyBy('id');
        $slugById  = $pages->pluck('slug', 'id');

        // Build ZIP in a temp file, stream it back, then delete.
        $tmpFile = tempnam(sys_get_temp_dir(), 'codex-export-');
        $zip     = new ZipArchive();
        $zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $zip->addFromString('assets/export.css', $this->stylesheet());
        $zip->addFromString('index.html', $this->buildIndex($workspace, $pages));

        $resolver = app(PageLinkResolver::class);

        foreach ($pages as $page) {
            $html = $this->buildPage($page, $workspace, $resolver, $pagesById, $slugById);
            $zip->addFromString('pages/' . $page->slug . '.html', $html);
        }

        $zip->close();

        $filename = Str::slug($workspace->name) . '-' . now()->format('Y-m-d') . '.zip';

        return response()
            ->download($tmpFile, $filename, ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }

    private function buildIndex(Workspace $workspace, Collection $pages): string
    {
        $name  = htmlspecialchars($workspace->name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $desc  = $workspace->description
            ? '<p class="description">' . htmlspecialchars($workspace->description, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</p>'
            : '';
        $date  = now()->toFormattedDateString();
        $count = $pages->count();
        $tree  = $this->buildIndexTree($pages, null);

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width,initial-scale=1">
            <title>{$name} — Codex Export</title>
            <link rel="stylesheet" href="assets/export.css">
        </head>
        <body>
            <div class="export-header">
                <h1>{$name}</h1>
                {$desc}
                <p class="meta">Exported {$date} &middot; {$count} pages</p>
            </div>
            <main>
                <h2>Pages</h2>
                <ul class="page-index">{$tree}</ul>
            </main>
            <footer>Exported from Codex &middot; {$date}</footer>
        </body>
        </html>
        HTML;
    }

    private function buildIndexTree(Collection $pages, ?string $parentId): string
    {
        $html = '';

        foreach ($pages->where('parent_id', $parentId) as $page) {
            $title    = htmlspecialchars($page->title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $slug     = $page->slug;
            $children = $this->buildIndexTree($pages, $page->id);

            $html .= "<li><a href=\"pages/{$slug}.html\">{$title}</a>";
            if ($children) {
                $html .= "<ul>{$children}</ul>";
            }
            $html .= '</li>';
        }

        return $html;
    }

    private function buildPage(
        Page $page,
        Workspace $workspace,
        PageLinkResolver $resolver,
        Collection $pagesById,
        Collection $slugById
    ): string {
        $e = fn(string $s) => htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $title         = $e($page->title);
        $workspaceName = $e($workspace->name);
        $author        = $e($page->author?->name ?? 'Unknown');
        $status        = $e(ucfirst($page->status ?? 'draft'));
        $updated       = $page->updated_at->toFormattedDateString();

        $rawContent = $page->content ?? '';
        if ($page->content_type === 'markdown') {
            $rendered = app(MarkdownRenderer::class)->toHtml($rawContent);
        } else {
            $rendered = $rawContent;
        }

        // Resolve [[wiki links]] → absolute app URLs, then rewrite to relative
        $rendered = $resolver->render($rendered, $workspace);
        $rendered = $this->rewriteInternalLinks($rendered, $slugById);

        $tagHtml = $page->tags->map(
            fn($t) => '<span class="tag">' . $e($t->name) . '</span>'
        )->join(' ');

        $attachHtml  = '';
        $attachments = $page->getMedia('attachments');

        if ($attachments->isNotEmpty()) {
            $items = $attachments->map(function ($m) use ($e) {
                $size = Number::fileSize($m->size, precision: 1);
                $ext  = strtoupper($m->extension);
                $url  = $e($m->getUrl());
                $name = $e($m->name);

                return "<li><a href=\"{$url}\" target=\"_blank\" rel=\"noopener\">{$name}</a> "
                    . "<span class=\"meta\">({$ext}, {$size})</span></li>";
            })->join("\n");

            $attachHtml = <<<HTML
            <section class="attachments">
                <h2>Attachments</h2>
                <ul>{$items}</ul>
            </section>
            HTML;
        }

        $ancestors = $this->resolveAncestors($page, $pagesById);
        $breadNav  = '<a href="../index.html">' . $workspaceName . '</a>';

        foreach ($ancestors as $ancestor) {
            $aSlug   = $ancestor->slug;
            $aTitle  = $e($ancestor->title);
            $breadNav .= " &rsaquo; <a href=\"../pages/{$aSlug}.html\">{$aTitle}</a>";
        }
        $breadNav .= " &rsaquo; {$title}";

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width,initial-scale=1">
            <title>{$title} &mdash; {$workspaceName}</title>
            <link rel="stylesheet" href="../assets/export.css">
        </head>
        <body>
            <nav class="breadcrumb">{$breadNav}</nav>
            <div class="page-header">
                <h1>{$title}</h1>
                <div class="meta">{$author} &middot; {$status} &middot; Updated {$updated}</div>
                <div class="tags">{$tagHtml}</div>
            </div>
            <main class="page-content">{$rendered}</main>
            {$attachHtml}
            <footer>Exported from Codex &middot; {$updated}</footer>
        </body>
        </html>
        HTML;
    }

    /**
     * Rewrite app-internal page URLs to export-relative paths.
     *
     * Matches both absolute (https://codex.test/…) and root-relative (/workspaces/…)
     * hrefs that contain a page UUID, replacing them with ../pages/{slug}.html.
     * Wiki-link-broken spans are left as-is since they have no href to rewrite.
     */
    private function rewriteInternalLinks(string $html, Collection $slugById): string
    {
        // UUID pattern — 8-4-4-4-12 hex groups
        $uuid = '[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}';

        return preg_replace_callback(
            "#href=\"(?:https?://[^/\"]+)?/workspaces/{$uuid}/pages/({$uuid})[^\"]*\"#i",
            function (array $m) use ($slugById): string {
                $slug = $slugById[$m[1]] ?? null;
                $href = $slug ? "../pages/{$slug}.html" : '#broken-link';

                return "href=\"{$href}\"";
            },
            $html
        );
    }

    /**
     * Walk up the parent_id chain using the already-loaded in-memory collection.
     * Returns ancestors oldest-first (root → … → direct parent).
     */
    private function resolveAncestors(Page $page, Collection $pagesById): array
    {
        $ancestors = [];
        $current   = $page;
        $visited   = [$page->id];

        while ($current->parent_id !== null) {
            $parent = $pagesById->get($current->parent_id);

            if (! $parent || in_array($parent->id, $visited, true)) {
                break;
            }

            array_unshift($ancestors, $parent);
            $visited[] = $parent->id;
            $current   = $parent;
        }

        return $ancestors;
    }

    private function stylesheet(): string
    {
        return <<<'CSS'
/* Codex Export Stylesheet */
*, *::before, *::after { box-sizing: border-box; }

body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    font-size: 16px;
    line-height: 1.65;
    color: #212529;
    max-width: 880px;
    margin: 0 auto;
    padding: 2rem 1.5rem;
}

h1 { font-size: 2rem; margin: 0 0 .5rem; line-height: 1.25; }
h2 { font-size: 1.5rem; margin: 1.75rem 0 .5rem; }
h3 { font-size: 1.2rem; margin: 1.5rem 0 .4rem; }
h4, h5, h6 { margin: 1.25rem 0 .3rem; }
p { margin: 0 0 1rem; }
ul, ol { padding-left: 1.5rem; margin: 0 0 1rem; }
li { margin-bottom: .2rem; }
a { color: #0d6efd; }
a[href="#broken-link"] { color: #dc3545; text-decoration: line-through; }
.wiki-link-broken { color: #dc3545; text-decoration: line-through; }

code {
    background: #f1f3f5;
    padding: .15em .4em;
    border-radius: 3px;
    font-size: .88em;
    font-family: "SFMono-Regular", Consolas, "Liberation Mono", monospace;
}
pre {
    background: #f1f3f5;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 1rem 1.25rem;
    overflow-x: auto;
    margin: 1rem 0;
}
pre code { background: none; padding: 0; font-size: .9em; }

blockquote {
    border-left: 4px solid #dee2e6;
    margin: 1rem 0;
    padding: .25rem 0 .25rem 1rem;
    color: #6c757d;
}

table { border-collapse: collapse; width: 100%; margin: 1rem 0; font-size: .95em; }
th, td { border: 1px solid #dee2e6; padding: .5rem .75rem; text-align: left; }
th { background: #f8f9fa; font-weight: 600; }

img { max-width: 100%; height: auto; border-radius: 4px; display: block; margin: .5rem 0; }
hr  { border: none; border-top: 1px solid #dee2e6; margin: 1.5rem 0; }

/* Callout blocks — mirrors app.css */
.callout { border-left: 4px solid; padding: .75rem 1rem; margin: 1rem 0; border-radius: 0 4px 4px 0; }
.callout p:last-child { margin-bottom: 0; }
.callout-note    { border-color: #0d6efd; background: rgba(13,110,253,.08); }
.callout-tip     { border-color: #198754; background: rgba(25,135,84,.08); }
.callout-warning { border-color: #fd7e14; background: rgba(253,126,20,.08); }
.callout-danger  { border-color: #dc3545; background: rgba(220,53,69,.08); }

/* Layout */
.export-header { border-bottom: 2px solid #dee2e6; margin-bottom: 2rem; padding-bottom: 1rem; }
.export-header .description { color: #6c757d; font-size: 1.05em; margin: .5rem 0 0; }
.page-header { border-bottom: 1px solid #dee2e6; margin-bottom: 2rem; padding-bottom: 1rem; }
.breadcrumb { font-size: .85em; color: #6c757d; margin-bottom: 1.5rem; }
.breadcrumb a { color: #6c757d; text-decoration: none; }
.breadcrumb a:hover { color: #0d6efd; }
.meta { color: #6c757d; font-size: .85em; margin-top: .3rem; }
.tag { background: #e9ecef; border-radius: 3px; padding: .1em .5em; font-size: .78em; margin-right: .2rem; color: #495057; }
.tags { margin-top: .5rem; }
.attachments { border-top: 1px solid #dee2e6; margin-top: 2rem; padding-top: 1rem; }
.attachments ul { padding-left: 1.25rem; }

.page-index { list-style: none; padding: 0; margin: 0; line-height: 2; }
.page-index ul { list-style: none; padding: 0 0 0 1.25rem; border-left: 2px solid #dee2e6; margin: 0; }

footer {
    border-top: 1px solid #dee2e6;
    margin-top: 3rem;
    padding-top: .75rem;
    font-size: .78em;
    color: #adb5bd;
    text-align: center;
}

/* Print */
@media print {
    body { padding: 0; max-width: none; }
    .breadcrumb { display: none; }
    footer { display: none; }
    a { color: inherit; text-decoration: none; }
}
CSS;
    }
}
