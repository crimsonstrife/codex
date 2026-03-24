<?php

        namespace App\Services;

        use App\Models\Page;
        use App\Models\PageLink;
        use App\Models\Workspace;

        /**
         * Resolves wiki-style links written as [[Page Title]].
         *
         * Responsibilities:
         * - Keep persisted outgoing link relationships in sync with page content.
         * - Render wiki-link markup into HTML anchors (or "broken link" markers).
         *
         * Design notes:
         * - Link relationships are stored in `page_links` so pages can be queried
         *   by their references.
         * - Rendering is done at view time so title changes are reflected immediately
         *   without rewriting historical content.
         */
        class PageLinkResolver
        {
            /**
             * Regex pattern for wiki-link tokens in the form [[...]].
             *
             * - Captures only the inner title text (capture group 1).
             * - Excludes nested `[` or `]` to avoid malformed matches.
             * - Uses Unicode mode (`u`) to support non-ASCII titles.
             */
            private const string PATTERN = '/\[\[([^\[\]]+?)]]/u';

            /**
             * Rebuild all outgoing `PageLink` records for a single source page.
             *
             * Workflow:
             * 1. Delete existing outgoing links from the source page.
             * 2. Parse all [[Title]] references from the page content.
             * 3. Resolve each unique title in the same workspace.
             * 4. Create a link row for each resolvable, non-self reference.
             *
             * Behavior details:
             * - Empty references (e.g., `[[   ]]`) are ignored.
             * - Duplicate references to the same title are collapsed.
             * - Soft-deleted pages are not considered valid targets.
             * - Self-links are intentionally skipped.
             *
             * @param Page $page The page whose outgoing wiki links should be synchronized.
             */
            public function sync(Page $page): void
            {
                // Remove stale outgoing links unconditionally; we recreate below.
                PageLink::where('source_page_id', $page->id)->delete();

                $content = $page->content ?? '';

                // Fast path: skip regex work if no wiki token opener is present.
                if (! str_contains($content, '[[')) {
                    return;
                }

                preg_match_all(self::PATTERN, $content, $matches);
                $titles = array_unique($matches[1] ?? []);

                foreach ($titles as $rawTitle) {
                    $title = trim($rawTitle);

                    if ($title === '') {
                        continue;
                    }

                    $target = Page::where('workspace_id', $page->workspace_id)
                        ->where('title', $title)
                        ->whereNull('deleted_at')
                        ->first(['id']);

                    // Skip self-references and unresolvable titles.
                    if (! $target || $target->id === $page->id) {
                        continue;
                    }

                    PageLink::firstOrCreate(
                        [
                            'source_page_id' => $page->id,
                            'target_page_id' => $target->id,
                        ],
                        [
                            'anchor_text' => $title,
                        ]
                    );
                }
            }

            /**
             * Render wiki-link tokens inside HTML content.
             *
             * Each `[[Page Title]]` becomes:
             * - `<a class="wiki-link" ...>` when the page exists in the workspace.
             * - `<span class="wiki-link wiki-link-broken" ...>` when not found.
             *
             * Safety and correctness:
             * - Output text and URLs are escaped with `e(...)`.
             * - Resolution is restricted to non-deleted pages in the given workspace.
             * - Since `[[...]]` is not valid HTML syntax, replacements target textual
             *   content and do not mutate existing tag structure intentionally.
             *
             * Performance:
             * - Uses an early return when no `[[` token exists.
             * - Builds a single in-memory title map for the workspace per invocation.
             *
             * @param string $content   The HTML/text block potentially containing wiki links.
             * @param Workspace $workspace The workspace context used for page resolution.
             * @return string The transformed content with wiki links rendered to HTML.
             */
            public function render(string $content, Workspace $workspace): string
            {
                if (! str_contains($content, '[[')) {
                    return $content;
                }

                // Build a title→Page lookup map once per render call.
                $pageMap = Page::where('workspace_id', $workspace->id)
                    ->whereNull('deleted_at')
                    ->get(['id', 'title', 'slug'])
                    ->keyBy('title');

                return preg_replace_callback(
                    self::PATTERN,
                    /**
                     * Convert one wiki-link match to either an anchor or a broken-link span.
                     *
                     * @param array<int, string> $m Regex match array:
                     *                              - $m[0]: full token (e.g., [[Home]])
                     *                              - $m[1]: inner title (e.g., Home)
                     * @return string HTML fragment for this matched wiki link.
                     */
                    static function (array $m) use ($workspace, $pageMap): string {
                        $title = trim($m[1]);
                        $page  = $pageMap->get($title);

                        if ($page) {
                            $url = route('workspaces.pages.show', [$workspace, $page]);

                            return '<a href="' . e($url) . '" class="wiki-link">'
                                . e($title)
                                . '</a>';
                        }

                        return '<span class="wiki-link wiki-link-broken" '
                            . 'title="Page not found: ' . e($title) . '">'
                            . e($title)
                            . '</span>';
                    },
                    $content
                );
            }
        }
