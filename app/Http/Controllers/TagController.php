<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Spatie\Tags\Tag;

class TagController extends Controller
{
    /**
     * feat 4.2 — List all tags used on pages within a workspace.
     */
    public function index(Workspace $workspace)
    {
        $this->authorize('view', $workspace);

        $pageIds = $workspace->pages()->pluck('id');

        // Join through the taggables pivot to get per-workspace tag counts.
        $tags = Tag::query()
            ->join('taggables', 'tags.id', '=', 'taggables.tag_id')
            ->where('taggables.taggable_type', (new Page())->getMorphClass())
            ->whereIn('taggables.taggable_id', $pageIds)
            ->select('tags.*', DB::raw('COUNT(taggables.taggable_id) as pages_count'))
            ->groupBy('tags.id')
            ->orderByDesc('pages_count')
            ->orderBy('tags.name->en')
            ->get();

        return view('tags.index', compact('workspace', 'tags'));
    }

    /**
     * feat 4.2 — List pages in a workspace that share a specific tag.
     */
    public function show(Workspace $workspace, string $tagSlug)
    {
        $this->authorize('view', $workspace);

        $tag = Tag::query()
            ->where(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(slug, '$.en'))"), $tagSlug)
            ->firstOrFail();

        $pages = Page::withAnyTags([$tag->name])
            ->where('workspace_id', $workspace->id)
            ->with('author')
            ->latest('updated_at')
            ->get();

        return view('tags.show', compact('workspace', 'tag', 'pages'));
    }
}
