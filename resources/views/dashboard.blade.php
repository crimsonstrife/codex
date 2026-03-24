<x-app-layout>
    <x-slot name="header">
        <h2 class="h5 mb-0">{{ __('Dashboard') }}</h2>
    </x-slot>

    <div class="py-4">
        <div class="container">
            <div class="row g-4">
                {{-- Welcome card --}}
                <div class="col-12">
                    <div class="card shadow-sm overflow-hidden">
                        <x-welcome />
                    </div>
                </div>

                {{-- feat 1.6: Starred pages --}}
                <div class="col-lg-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header py-3 d-flex align-items-center justify-content-between">
                            <span class="small fw-semibold text-uppercase text-body-secondary">
                                <i class="fas fa-star me-1 text-warning"></i> Starred Pages
                            </span>
                        </div>
                        @if($starred->isEmpty())
                            <div class="card-body text-center py-5 text-body-secondary">
                                <i class="fas fa-star fa-2x mb-3 d-block opacity-25"></i>
                                <p class="small mb-0">No starred pages yet.</p>
                                <p class="small mb-0">Click <i class="fas fa-star"></i> on any page to pin it here.</p>
                            </div>
                        @else
                            <div class="list-group list-group-flush">
                                @foreach($starred as $page)
                                    <a href="{{ route('workspaces.pages.show', [$page->workspace, $page]) }}"
                                       class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3 px-4">
                                        <span class="rounded-2 flex-shrink-0 d-inline-block"
                                              style="width:10px;height:10px;background:{{ $page->workspace->color ?? '#6366f1' }};"></span>
                                        <div class="flex-grow-1 overflow-hidden">
                                            <div class="fw-medium small text-truncate">{{ $page->title }}</div>
                                            <div class="text-body-secondary" style="font-size:0.73rem;">
                                                {{ $page->workspace->name }} &middot; {{ $page->updated_at->diffForHumans() }}
                                            </div>
                                        </div>
                                        <span class="badge flex-shrink-0
                                            {{ $page->status === 'published' ? 'bg-success-subtle text-success-emphasis' : ($page->status === 'archived' ? 'bg-secondary-subtle text-secondary-emphasis' : 'bg-warning-subtle text-warning-emphasis') }}">
                                            {{ ucfirst($page->status) }}
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Recently Viewed pages --}}
                <div class="col-lg-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header py-3 d-flex align-items-center justify-content-between">
                            <span class="small fw-semibold text-uppercase text-body-secondary">
                                <i class="fas fa-history me-1 text-info"></i> Recently Viewed
                            </span>
                        </div>
                        @if($recentlyViewed->isEmpty())
                            <div class="card-body text-center py-5 text-body-secondary">
                                <i class="fas fa-history fa-2x mb-3 d-block opacity-25"></i>
                                <p class="small mb-0">No recently viewed pages yet.</p>
                                <p class="small mb-0">Pages you visit will appear here.</p>
                            </div>
                        @else
                            <div class="list-group list-group-flush">
                                @foreach($recentlyViewed as $view)
                                    @if($view->page)
                                    <a href="{{ route('workspaces.pages.show', [$view->page->workspace, $view->page]) }}"
                                       class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3 px-4">
                                        <span class="rounded-2 flex-shrink-0 d-inline-block"
                                              style="width:10px;height:10px;background:{{ $view->page->workspace->color ?? '#6366f1' }};"></span>
                                        <div class="flex-grow-1 overflow-hidden">
                                            <div class="fw-medium small text-truncate">{{ $view->page->title }}</div>
                                            <div class="text-body-secondary" style="font-size:0.73rem;">
                                                {{ $view->page->workspace->name }} &middot; {{ $view->viewed_at->diffForHumans() }}
                                            </div>
                                        </div>
                                        <span class="badge flex-shrink-0
                                            {{ $view->page->status === 'published' ? 'bg-success-subtle text-success-emphasis' : ($view->page->status === 'archived' ? 'bg-secondary-subtle text-secondary-emphasis' : 'bg-warning-subtle text-warning-emphasis') }}">
                                            {{ ucfirst($view->page->status) }}
                                        </span>
                                    </a>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Quick links to workspaces --}}
                <div class="col-12">
                    <div class="card shadow-sm h-100">
                        <div class="card-header py-3">
                            <span class="small fw-semibold text-uppercase text-body-secondary">
                                <i class="fas fa-folder-open me-1"></i> Your Workspaces
                            </span>
                        </div>
                        @if($myWorkspaces->isEmpty())
                            <div class="card-body text-center py-5 text-body-secondary">
                                <i class="fas fa-folder fa-2x mb-3 d-block opacity-25"></i>
                                <p class="small mb-2">No workspaces yet.</p>
                                @can('create', \App\Models\Workspace::class)
                                    <a href="{{ route('workspaces.create') }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-plus me-1"></i> Create Workspace
                                    </a>
                                @endcan
                            </div>
                        @else
                            <div class="list-group list-group-flush">
                                @foreach($myWorkspaces->take(6) as $ws)
                                    <a href="{{ route('workspaces.show', $ws) }}"
                                       class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-2 px-4">
                                        <div class="d-flex align-items-center justify-content-center rounded-2 text-white fw-bold flex-shrink-0"
                                             style="width:28px;height:28px;background:{{ $ws->color ?? '#6366f1' }};font-size:0.75rem;">
                                            {{ $ws->icon ?? strtoupper(substr($ws->name, 0, 1)) }}
                                        </div>
                                        <span class="small fw-medium text-truncate flex-grow-1">{{ $ws->name }}</span>
                                        @if($ws->owner_id === auth()->id())
                                            <span class="badge bg-primary-subtle text-primary-emphasis flex-shrink-0" style="font-size:0.65rem;">Owner</span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                            <div class="card-footer py-2 text-center">
                                <a href="{{ route('workspaces.index') }}" class="small text-body-secondary text-decoration-none">
                                    View all workspaces <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
