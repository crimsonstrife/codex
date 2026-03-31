<?php

use App\Http\Controllers\Api\DiagramLinkController;
use App\Http\Controllers\Api\MentionController;
use App\Http\Controllers\Api\PageLinkController;
use App\Http\Controllers\Auth\ForgeSsoController;
use App\Http\Controllers\DiagramController;
use App\Http\Controllers\HealthCheckResultsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PageAttachmentController;
use App\Http\Controllers\PageCommentController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PagePinController;
use App\Http\Controllers\PageStarController;
use App\Http\Controllers\PageTemplateController;
use App\Http\Controllers\PageWatchController;
use App\Http\Controllers\ProfilePreferencesController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ScriptEntityController;
use App\Http\Controllers\ScriptProjectController;
use App\Http\Controllers\ScriptProjectPageController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\WorkspaceAnalyticsController;
use App\Http\Controllers\WorkspaceCategoryController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\WorkspaceExportController;
use App\Http\Controllers\WorkspaceGraphController;
use App\Http\Controllers\WorkspaceInvitationController;
use App\Http\Controllers\WorkspaceMemberController;
use App\Models\PageView;
use App\Models\Workspace;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/status', HealthCheckResultsController::class)->name('status');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    // feat 1.6: dashboard shows starred pages
    Route::get('/dashboard', function () {
        $starred = auth()->user()->starredPages()->take(10)->get();

        $myWorkspaces = auth()->user()->workspaces()->orderBy('name')->take(8)->get()
            ->merge(auth()->user()->ownedWorkspaces()->orderBy('name')->take(8)->get())
            ->unique('id')->sortBy('name');

        $recentlyViewed = PageView::with(['page.workspace'])
            ->where('user_id', auth()->id())
            ->whereHas('page', fn ($q) => $q->whereNull('deleted_at'))
            ->orderByDesc('viewed_at')
            ->limit(8)
            ->get();

        return view('dashboard', compact('starred', 'myWorkspaces', 'recentlyViewed'));
    })->name('dashboard');

    // Global search (feat 4.3 adds workspace filter + snippets)
    Route::get('/search', [SearchController::class, 'index'])->name('search');

    // Workspaces (feat 2.2 adds edit + update; feat 3.3 adds home-page)
    Route::resource('workspaces', WorkspaceController::class)
        ->only(['index', 'create', 'store', 'show', 'edit', 'update']);
    Route::patch('/workspaces/{workspace}/home-page', [WorkspaceController::class, 'setHomePage'])
        ->name('workspaces.set-home-page')
        ->scopeBindings();
    Route::get('/workspaces/{workspace}/export', WorkspaceExportController::class)
        ->name('workspaces.export')
        ->scopeBindings();
    Route::get('/workspaces/{workspace}/analytics', WorkspaceAnalyticsController::class)
        ->name('workspaces.analytics')
        ->scopeBindings();
    Route::prefix('/workspaces/{workspace}/categories')->name('workspaces.categories.')->scopeBindings()->group(function () {
        Route::get('/', [WorkspaceCategoryController::class, 'index'])->name('index');
        Route::post('/', [WorkspaceCategoryController::class, 'store'])->name('store');
        Route::put('/{category}', [WorkspaceCategoryController::class, 'update'])->name('update');
        Route::delete('/{category}', [WorkspaceCategoryController::class, 'destroy'])->name('destroy');
    });
    Route::get('/workspaces/{workspace}/graph', WorkspaceGraphController::class)
        ->name('workspaces.graph')
        ->scopeBindings();

    // Templates (sprint 12.2)
    Route::get('/workspaces/{workspace}/templates', [PageTemplateController::class, 'index'])
        ->name('workspaces.templates.index')
        ->scopeBindings();
    Route::delete('/workspaces/{workspace}/templates/{template}', [PageTemplateController::class, 'destroy'])
        ->name('workspaces.templates.destroy')
        ->scopeBindings();

    // Profile notification preferences (sprint 12.3)
    Route::patch('/profile/notification-preferences', [ProfilePreferencesController::class, 'update'])
        ->name('profile.preferences.update');

    // Workspace members (feat 2.4)
    Route::prefix('workspaces/{workspace}/members')->name('workspaces.members.')->scopeBindings()->group(function () {
        Route::get('/', [WorkspaceMemberController::class, 'index'])->name('index');
        Route::post('/', [WorkspaceMemberController::class, 'store'])->name('store');
        Route::patch('/{member}', [WorkspaceMemberController::class, 'update'])->name('update');
        Route::delete('/{member}', [WorkspaceMemberController::class, 'destroy'])->name('destroy');
        Route::get('/search', [WorkspaceMemberController::class, 'search'])->name('search');
    });

    // Workspace invitations (sprint 14.3)
    Route::prefix('workspaces/{workspace}/invitations')->name('workspaces.invitations.')->scopeBindings()->group(function () {
        Route::post('/', [WorkspaceInvitationController::class, 'store'])->name('store');
        Route::delete('/{invitation}', [WorkspaceInvitationController::class, 'destroy'])->name('destroy');
    });

    // Pages & Diagrams within a workspace
    Route::prefix('workspaces/{workspace}')->name('workspaces.')->scopeBindings()->group(function () {
        // Pages
        Route::get('/pages', fn (Workspace $workspace) => redirect()->route('workspaces.show', $workspace))->name('pages.index');
        Route::get('/pages/create', [PageController::class, 'create'])->name('pages.create');
        Route::post('/pages', [PageController::class, 'store'])->name('pages.store');
        Route::get('/pages/{page}', [PageController::class, 'show'])->name('pages.show');
        Route::get('/pages/{page}/edit', [PageController::class, 'edit'])->name('pages.edit');
        Route::put('/pages/{page}', [PageController::class, 'update'])->name('pages.update');
        Route::patch('/pages/{page}/status', [PageController::class, 'updateStatus'])->name('pages.status');
        Route::get('/pages/{page}/history', [PageController::class, 'history'])->name('pages.history');
        Route::get('/pages/{page}/history/diff', [PageController::class, 'diffRevisions'])->name('pages.revisions.diff');
        Route::get('/pages/{page}/history/{revision}', [PageController::class, 'showRevision'])->name('pages.revisions.show');
        Route::post('/pages/{page}/history/{revision}/restore', [PageController::class, 'restoreRevision'])->name('pages.revisions.restore');
        Route::post('/pages/{page}/comments', [PageCommentController::class, 'store'])->name('pages.comments.store');
        Route::delete('/pages/{page}/comments/{comment}', [PageCommentController::class, 'destroy'])->name('pages.comments.destroy');
        Route::patch('/pages/{page}/comments/{comment}/resolve', [PageCommentController::class, 'resolve'])->name('pages.comments.resolve');
        Route::delete('/pages/{page}', [PageController::class, 'destroy'])->name('pages.destroy');
        Route::post('/pages/{page}/star', [PageStarController::class, 'toggle'])->name('pages.star');
        Route::post('/pages/{page}/watch', [PageWatchController::class, 'toggle'])->name('pages.watch');
        Route::post('/pages/{page}/pin', [PagePinController::class, 'toggle'])->name('pages.pin');
        Route::post('/pages/{page}/duplicate', [PageController::class, 'duplicate'])->name('pages.duplicate');
        Route::post('/pages/{page}/transfer', [PageController::class, 'transfer'])->name('pages.transfer');
        Route::patch('/pages/{page}/move', [PageController::class, 'move'])->name('pages.move');
        Route::patch('/pages/{page}/lock', [PageController::class, 'heartbeat'])->name('pages.lock');
        Route::delete('/pages/{page}/lock', [PageController::class, 'unlock'])->name('pages.unlock');
        Route::get('/pages/{page}/print', [PageController::class, 'print'])->name('pages.print');
        Route::get('/pages/{page}/export/markdown', [PageController::class, 'exportMarkdown'])->name('pages.export.markdown');
        Route::post('/pages/{page}/save-as-template', [PageTemplateController::class, 'storeFromPage'])->name('pages.save-as-template');

        // Attachments (feat 1.9)
        Route::post('/pages/{page}/attachments', [PageAttachmentController::class, 'store'])->name('pages.attachments.store');
        Route::delete('/pages/{page}/attachments/{media}', [PageAttachmentController::class, 'destroy'])->name('pages.attachments.destroy');
        Route::post('/pages/{page}/editor-images', [PageAttachmentController::class, 'uploadEditorImage'])->name('pages.editor-images.store');

        // Tags (feat 4.2)
        Route::get('/tags', [TagController::class, 'index'])->name('tags.index');
        Route::get('/tags/{tagSlug}', [TagController::class, 'show'])->name('tags.show');

        // Scripts
        Route::get('/scripts/create', [ScriptProjectController::class, 'create'])->name('scripts.create');
        Route::post('/scripts', [ScriptProjectController::class, 'store'])->name('scripts.store');
        Route::get('/scripts/{script}', [ScriptProjectController::class, 'show'])->name('scripts.show');
        Route::get('/scripts/{script}/edit', [ScriptProjectController::class, 'edit'])->name('scripts.edit');
        Route::put('/scripts/{script}', [ScriptProjectController::class, 'update'])->name('scripts.update');
        Route::delete('/scripts/{script}', [ScriptProjectController::class, 'destroy'])->name('scripts.destroy');
        Route::get('/scripts/{script}/history', [ScriptProjectController::class, 'history'])->name('scripts.history');
        Route::get('/scripts/{script}/history/{revision}', [ScriptProjectController::class, 'showRevision'])->name('scripts.revisions.show');
        Route::get('/scripts/{script}/print', [ScriptProjectController::class, 'print'])->name('scripts.print');
        Route::get('/scripts/{script}/export/fountain', [ScriptProjectController::class, 'exportFountain'])->name('scripts.export.fountain');
        Route::post('/scripts/{script}/entities', [ScriptEntityController::class, 'store'])->name('scripts.entities.store');
        Route::patch('/scripts/{script}/entities/{entity}', [ScriptEntityController::class, 'update'])->name('scripts.entities.update');
        Route::delete('/scripts/{script}/entities/{entity}', [ScriptEntityController::class, 'destroy'])->name('scripts.entities.destroy');
        Route::post('/scripts/{script}/binder-pages', [ScriptProjectPageController::class, 'store'])->name('scripts.binder-pages.store');
        Route::post('/scripts/{script}/binder-pages/create', [ScriptProjectPageController::class, 'storeFromTemplate'])->name('scripts.binder-pages.create');
        Route::patch('/scripts/{script}/binder-pages/{binderLink}', [ScriptProjectPageController::class, 'update'])->name('scripts.binder-pages.update');
        Route::delete('/scripts/{script}/binder-pages/{binderLink}', [ScriptProjectPageController::class, 'destroy'])->name('scripts.binder-pages.destroy');

        // Diagrams
        Route::get('/diagrams', fn (Workspace $workspace) => redirect()->route('workspaces.show', $workspace))->name('diagrams.index');
        Route::get('/diagrams/create', [DiagramController::class, 'create'])->name('diagrams.create');
        Route::post('/diagrams', [DiagramController::class, 'store'])->name('diagrams.store');
        Route::get('/diagrams/{diagram}', [DiagramController::class, 'show'])->name('diagrams.show');
        Route::get('/diagrams/{diagram}/edit', [DiagramController::class, 'edit'])->name('diagrams.edit');
        Route::put('/diagrams/{diagram}', [DiagramController::class, 'update'])->name('diagrams.update');
    });

    // API for TinyMCE mentions
    Route::prefix('api/mentions')->name('api.mentions.')->group(function () {
        Route::get('/users', [MentionController::class, 'users'])->name('users');
    });

    // API for TinyMCE wiki-links autocomplete (feat 3.4)
    Route::prefix('api/pages')->name('api.pages.')->group(function () {
        Route::get('/search', [PageLinkController::class, 'search'])->name('search');
    });

    // API for TinyMCE diagram embeds
    Route::prefix('api/diagrams')->name('api.diagrams.')->group(function () {
        Route::get('/search', [DiagramLinkController::class, 'search'])->name('search');
    });

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
});

// Workspace invitations — public (no auth required so recipients can see the landing page)
Route::get('/invitations/{token}', [WorkspaceInvitationController::class, 'show'])->name('invitations.show');
Route::post('/invitations/{token}/accept', [WorkspaceInvitationController::class, 'accept'])->name('invitations.accept');

// Forge SSO
Route::prefix('auth/forge')->name('forge.')->group(function () {
    Route::get('/redirect', [ForgeSsoController::class, 'redirect'])->name('redirect');
    Route::get('/callback', [ForgeSsoController::class, 'callback'])->name('callback');
});
