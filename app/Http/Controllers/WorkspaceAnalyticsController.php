<?php

namespace App\Http\Controllers;

use App\Models\PageLink;
use App\Models\PageRevision;
use App\Models\PageView;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

/**
 * Workspace analytics dashboard.
 *
 * All queries are scoped to the workspace and require at least a view policy
 * check. More expensive aggregations are kept in a single pass where possible.
 *
 * GET /workspaces/{workspace}/analytics → workspaces.analytics
 */
class WorkspaceAnalyticsController extends Controller
{
    public function __invoke(Workspace $workspace)
    {
        $this->authorize('view', $workspace);

        $totalPages  = $workspace->pages()->count();
        $statusCounts = $workspace->pages()
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $totalViews = PageView::whereHas(
            'page', fn ($q) => $q->where('workspace_id', $workspace->id)
        )->count();

        $recentViews = PageView::whereHas(
            'page', fn ($q) => $q->where('workspace_id', $workspace->id)
        )->where('viewed_at', '>=', now()->subDays(30))->count();

        $topPages = PageView::select('page_id', DB::raw('count(*) as view_count'))
            ->whereHas('page', fn ($q) => $q->where('workspace_id', $workspace->id))
            ->where('viewed_at', '>=', now()->subDays(30))
            ->groupBy('page_id')
            ->orderByDesc('view_count')
            ->limit(10)
            ->with('page:id,title,slug,status,workspace_id')
            ->get();

        $topContributors = PageRevision::select('user_id', DB::raw('count(*) as revision_count'))
            ->whereHas('page', fn ($q) => $q->where('workspace_id', $workspace->id))
            ->groupBy('user_id')
            ->orderByDesc('revision_count')
            ->limit(10)
            ->with('user:id,name')
            ->get();

        // Initialise every day to 0 so the chart has a continuous x-axis
        $dailyLabels   = [];
        $dailyCounts   = [];
        $dailyMap      = [];

        for ($i = 29; $i >= 0; $i--) {
            $d = now()->subDays($i)->format('Y-m-d');
            $dailyLabels[] = now()->subDays($i)->format('M j');
            $dailyCounts[] = 0;
            $dailyMap[$d]  = count($dailyCounts) - 1; // index
        }

        PageRevision::whereHas('page', fn ($q) => $q->where('workspace_id', $workspace->id))
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, count(*) as cnt')
            ->groupBy('date')
            ->get()
            ->each(function ($row) use (&$dailyCounts, $dailyMap) {
                if (isset($dailyMap[$row->date])) {
                    $dailyCounts[$dailyMap[$row->date]] = (int) $row->cnt;
                }
            });

        $mostLinked = PageLink::select('target_page_id', DB::raw('count(*) as link_count'))
            ->whereHas('targetPage', fn ($q) => $q->where('workspace_id', $workspace->id))
            ->groupBy('target_page_id')
            ->orderByDesc('link_count')
            ->limit(10)
            ->with('targetPage:id,title,slug,status,workspace_id')
            ->get();

        $totalRevisions = PageRevision::whereHas(
            'page', fn ($q) => $q->where('workspace_id', $workspace->id)
        )->count();

        return view('workspaces.analytics', compact(
            'workspace',
            'totalPages',
            'statusCounts',
            'totalViews',
            'recentViews',
            'topPages',
            'topContributors',
            'dailyLabels',
            'dailyCounts',
            'mostLinked',
            'totalRevisions',
        ));
    }
}
