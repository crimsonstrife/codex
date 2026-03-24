<?php

namespace App\Http\Controllers;

use App\Models\Diagram;
use App\Models\Page;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query       = trim((string) $request->get('q', ''));
        $workspaceId = $request->get('workspace');

        $filterStatus   = $request->get('status');          // draft|published|archived
        $filterTag      = trim((string) $request->get('tag', ''));
        $filterAuthor   = trim((string) $request->get('author', ''));
        $filterDateFrom = $request->get('date_from');       // YYYY-MM-DD
        $filterDateTo   = $request->get('date_to');         // YYYY-MM-DD
        $filterScope    = $request->get('scope', 'anywhere'); // anywhere|title

        $pages    = collect();
        $diagrams = collect();

        $user = Auth::user();

        // Workspaces this user can access (used for filter dropdown + scope)
        $accessibleWorkspaces = Workspace::query()
            ->where(function ($q) use ($user) {
                $q->where('is_public', true)
                  ->orWhere('owner_id', $user->id)
                  ->orWhereHas('members', fn ($m) => $m->where('user_id', $user->id));
            })
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

        $accessibleWorkspaceIds = $accessibleWorkspaces->pluck('id');

        // Narrow to a single workspace if the filter is set and valid
        if ($workspaceId && $accessibleWorkspaceIds->contains($workspaceId)) {
            $scopedIds = collect([$workspaceId]);
        } else {
            $scopedIds   = $accessibleWorkspaceIds;
            $workspaceId = null;
        }

        if ($query !== '') {
            // Workspace IDs where this user is an owner or explicit member
            // (they can see non-published pages in their own workspaces)
            $memberWorkspaceIds = Workspace::query()
                ->where(function ($q) use ($user) {
                    $q->where('owner_id', $user->id)
                      ->orWhereHas('members', fn ($m) => $m->where('user_id', $user->id));
                })
                ->pluck('id');

            // "title-only" scope bypasses Scout's full-text search and queries
            // directly on the title column, which is faster and more precise.
            if ($filterScope === 'title') {
                $pageQuery = Page::query()
                    ->with(['workspace', 'author'])
                    ->where('title', 'like', '%' . $query . '%')
                    ->whereIn('workspace_id', $scopedIds)
                    ->where(function ($inner) use ($memberWorkspaceIds) {
                        $inner->where('status', 'published')
                              ->orWhereIn('workspace_id', $memberWorkspaceIds);
                    });

                $pageQuery = $this->applyPageFilters(
                    $pageQuery, $filterStatus, $filterTag, $filterAuthor, $filterDateFrom, $filterDateTo
                );

                $pages = $pageQuery->limit(25)->get();
            } else {
                // feat 5.1: use Scout search (database driver by default; swap via SCOUT_DRIVER)
                $pages = Page::search($query)
                    ->query(function ($q) use ($scopedIds, $memberWorkspaceIds, $filterStatus, $filterTag, $filterAuthor, $filterDateFrom, $filterDateTo) {
                        $q->with(['workspace', 'author'])
                          ->whereIn('workspace_id', $scopedIds)
                          ->where(function ($inner) use ($memberWorkspaceIds) {
                              $inner->where('status', 'published')
                                    ->orWhereIn('workspace_id', $memberWorkspaceIds);
                          });

                        $this->applyPageFilters($q, $filterStatus, $filterTag, $filterAuthor, $filterDateFrom, $filterDateTo);
                    })
                    ->take(25)
                    ->get();
            }

            $pages = $pages->map(function (Page $page) use ($query) {
                $page->snippet = $this->extractSnippet($page->content ?? '', $query);
                return $page;
            });

            // Filters other than workspace don't apply to diagrams
            $diagrams = Diagram::search($query)
                ->query(function ($q) use ($scopedIds) {
                    $q->with(['workspace', 'author'])
                      ->whereIn('workspace_id', $scopedIds)
                      ->where('is_published', true);
                })
                ->take(10)
                ->get();
        }

        // spatie/laravel-tags stores relationships in the `taggables` pivot table;
        // join through it to scope tags to pages the user can actually read.
        $accessibleTags = \Spatie\Tags\Tag::query()
            ->join('taggables', 'taggables.tag_id', '=', 'tags.id')
            ->join('pages', 'pages.id', '=', 'taggables.taggable_id')
            ->where('taggables.taggable_type', \App\Models\Page::class)
            ->whereIn('pages.workspace_id', $accessibleWorkspaceIds)
            ->orderBy('tags.order_column')
            ->limit(50)
            ->select('tags.*')
            ->distinct()
            ->get()
            ->map(fn($tag) => is_array($tag->name) ? ($tag->name['en'] ?? reset($tag->name)) : $tag->name)
            ->filter()
            ->unique()
            ->values();

        return view('search.index', compact(
            'query', 'pages', 'diagrams',
            'accessibleWorkspaces', 'workspaceId',
            'filterStatus', 'filterTag', 'filterAuthor',
            'filterDateFrom', 'filterDateTo', 'filterScope',
            'accessibleTags'
        ));
    }

    /**
     * Apply the shared page filters (status, tag, author, date) to an Eloquent
     * query builder or Scout query proxy.  Returns the builder for chaining
     * (useful in title-scope mode where we have a plain Eloquent builder).
     */
    private function applyPageFilters(
        $query,
        ?string $status,
        string  $tag,
        string  $author,
        ?string $dateFrom,
        ?string $dateTo
    ) {
        if ($status && in_array($status, ['draft', 'published', 'archived'], true)) {
            $query->where('status', $status);
        }

        if ($tag !== '') {
            $query->whereHas('tags', fn ($q) => $q->where('name->en', $tag)->orWhere('name', $tag));
        }

        if ($author !== '') {
            $query->whereHas('author', fn ($q) => $q->where('name', 'like', '%' . $author . '%'));
        }

        if ($dateFrom) {
            $query->where('updated_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('updated_at', '<=', $dateTo . ' 23:59:59');
        }

        return $query;
    }

    /**
     * Extract a ~240-character plain-text snippet around the first occurrence
     * of any word in the search query, with matched terms wrapped in <mark>.
     */
    private function extractSnippet(string $html, string $query): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', trim($text));

        if ($text === '') {
            return '';
        }

        // Find position of first word match
        $pos = false;
        foreach (preg_split('/\s+/', $query) as $word) {
            if ($word === '') {
                continue;
            }
            $p = mb_stripos($text, $word);
            if ($p !== false && ($pos === false || $p < $pos)) {
                $pos = $p;
            }
        }

        if ($pos === false) {
            return Str::limit($text, 200);
        }

        $start   = max(0, $pos - 60);
        $excerpt = mb_substr($text, $start, 240);

        if ($start > 0) {
            $excerpt = '…' . ltrim($excerpt);
        }
        if (($start + 240) < mb_strlen($text)) {
            $excerpt = rtrim($excerpt) . '…';
        }

        // Highlight every matched word
        $words   = array_filter(preg_split('/\s+/', $query));
        $pattern = '(' . implode('|', array_map(static fn($word) => preg_quote($word, '/'), $words)) . ')';

        return preg_replace('/' . $pattern . '/iu', '<mark>$1</mark>', e($excerpt));
    }
}
