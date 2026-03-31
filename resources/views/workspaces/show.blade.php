<x-app-layout>
    <x-slot name="header">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <div class="d-flex align-items-center justify-content-center rounded-3 text-white fw-bold"
                     style="width:2rem;height:2rem;background-color:{{ $workspace->color ?? '#6366f1' }};font-size:0.85rem;">
                    {{ $workspace->icon ?? strtoupper(substr($workspace->name, 0, 1)) }}
                </div>
                <h2 class="h5 mb-0">{{ $workspace->name }}</h2>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('workspaces.pages.create', $workspace) }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i> {{ __('New Page') }}
                </a>
                <a href="{{ route('workspaces.diagrams.create', $workspace) }}" class="btn btn-success btn-sm">
                    <i class="fas fa-plus me-1"></i> {{ __('New Diagram') }}
                </a>
                @can('update', $workspace)
                    <a href="{{ route('workspaces.analytics', $workspace) }}"
                       class="btn btn-outline-secondary btn-sm" title="Analytics">
                        <i class="fas fa-chart-bar"></i>
                    </a>
                    <a href="{{ route('workspaces.edit', $workspace) }}" class="btn btn-outline-secondary btn-sm" title="Workspace Settings">
                        <i class="fas fa-cog"></i>
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container">
            <div class="row g-4">

                {{-- ── Left sidebar ─────────────────────────────────────────── --}}
                <div class="col-lg-3">
                    <div class="card shadow-sm" data-workspace-id="{{ $workspace->id }}">

                        {{-- Pages header with tree/table view toggle --}}
                        <div class="card-header py-2 d-flex align-items-center justify-content-between gap-2">
                            <span class="small fw-semibold text-uppercase text-body-secondary">Pages</span>
                            <div class="d-flex align-items-center gap-1">
                                <span id="page-sort-indicator" class="page-sort-indicator small me-1"></span>
                                <div class="btn-group btn-group-sm" role="group" aria-label="View mode">
                                    <button id="view-btn-tree" type="button"
                                            class="btn btn-outline-secondary active py-0 px-2"
                                            title="Tree view" aria-pressed="true">
                                        <i class="fas fa-sitemap" style="font-size:.7rem;"></i>
                                    </button>
                                    <button id="view-btn-table" type="button"
                                            class="btn btn-outline-secondary py-0 px-2"
                                            title="Table view" aria-pressed="false">
                                        <i class="fas fa-table" style="font-size:.7rem;"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="list-group list-group-flush">
                            @if($pages->isEmpty())
                                <div class="list-group-item text-body-secondary small">No pages yet.</div>
                            @else
                                @include('pages._page_tree', ['pages' => $pages, 'workspace' => $workspace, 'depth' => 0, 'sortable' => auth()->user()?->can('update', $workspace)])
                            @endif
                        </div>

                        {{-- Diagrams --}}
                        <div class="card-header py-2 border-top">
                            <span class="small fw-semibold text-uppercase text-body-secondary">Diagrams</span>
                        </div>
                        <div class="list-group list-group-flush">
                            @forelse($diagrams as $diagram)
                            <a href="{{ route('workspaces.diagrams.show', [$workspace, $diagram]) }}"
                               class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-2">
                                <i class="fas fa-project-diagram text-success small"></i>
                                <span class="text-truncate small">{{ $diagram->title }}</span>
                            </a>
                            @empty
                            <div class="list-group-item text-body-secondary small">No diagrams yet.</div>
                            @endforelse
                        </div>

                        {{-- Tags link --}}
                        <div class="card-header py-2 border-top">
                            <a href="{{ route('workspaces.tags.index', $workspace) }}"
                               class="d-flex align-items-center gap-2 text-decoration-none text-body-secondary small">
                                <i class="fas fa-tags small"></i>
                                <span class="fw-semibold text-uppercase">Browse Tags</span>
                            </a>
                        </div>

                        {{-- Link Graph --}}
                        <div class="card-header py-2 border-top">
                            <a href="{{ route('workspaces.graph', $workspace) }}"
                               class="d-flex align-items-center gap-2 text-decoration-none text-body-secondary small">
                                <i class="fas fa-project-diagram small"></i>
                                <span class="fw-semibold text-uppercase">Link Graph</span>
                            </a>
                        </div>

                        {{-- Members --}}
                        <div class="card-header py-2 border-top d-flex align-items-center justify-content-between">
                            <span class="small fw-semibold text-uppercase text-body-secondary">
                                <i class="fas fa-users me-1"></i> Members
                            </span>
                            @can('update', $workspace)
                                <a href="{{ route('workspaces.members.index', $workspace) }}"
                                   class="small text-decoration-none text-body-secondary">Manage</a>
                            @endcan
                        </div>
                        <div class="px-3 py-2">
                            <div class="d-flex align-items-center gap-2 py-1">
                                <div class="rounded-circle bg-primary-subtle text-primary-emphasis fw-bold d-flex align-items-center justify-content-center flex-shrink-0"
                                     style="width:24px;height:24px;font-size:0.6rem;">
                                    {{ strtoupper(substr($workspace->owner->name, 0, 2)) }}
                                </div>
                                <span class="small text-truncate flex-grow-1">{{ $workspace->owner->name }}</span>
                                <span class="badge bg-primary-subtle text-primary-emphasis" style="font-size:0.6rem;">Owner</span>
                            </div>
                            @foreach($membersPreview as $member)
                                <div class="d-flex align-items-center gap-2 py-1">
                                    <div class="rounded-circle bg-secondary-subtle text-secondary-emphasis fw-bold d-flex align-items-center justify-content-center flex-shrink-0"
                                         style="width:24px;height:24px;font-size:0.6rem;">
                                        {{ strtoupper(substr($member->name, 0, 2)) }}
                                    </div>
                                    <span class="small text-truncate flex-grow-1">{{ $member->name }}</span>
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis"
                                          style="font-size:0.6rem;">{{ ucfirst($member->pivot->role) }}</span>
                                </div>
                            @endforeach
                            @if($memberCount > 6)
                                <div class="pt-1 pb-1">
                                    <a href="{{ route('workspaces.members.index', $workspace) }}"
                                       class="small text-decoration-none text-body-secondary">
                                        +{{ $memberCount - 6 }} more {{ Str::plural('member', $memberCount - 6) }}
                                    </a>
                                </div>
                            @endif
                            @if($memberCount === 0)
                                <p class="small text-body-secondary mb-0 py-1">
                                    @can('update', $workspace)
                                        <a href="{{ route('workspaces.members.index', $workspace) }}"
                                           class="text-decoration-none">Add members</a> to collaborate.
                                    @else
                                        No other members.
                                    @endcan
                                </p>
                            @endif
                        </div>

                        {{-- Recent Activity (sidebar, condensed) --}}
                        <div class="card-header py-2 border-top">
                            <span class="small fw-semibold text-uppercase text-body-secondary">
                                <i class="fas fa-history me-1"></i> Recent Activity
                            </span>
                        </div>
                        <div class="list-group list-group-flush">
                            @forelse($activities as $activity)
                            <div class="list-group-item py-2 px-3">
                                <div class="d-flex align-items-start gap-2">
                                    <div class="rounded-circle text-white d-flex align-items-center justify-content-center flex-shrink-0"
                                         style="width:1.4rem;height:1.4rem;background-color:{{ $workspace->color ?? '#6366f1' }};font-size:0.6rem;margin-top:1px;">
                                        {{ strtoupper(substr($activity->causer?->name ?? 'S', 0, 1)) }}
                                    </div>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="mb-0 small lh-sm text-truncate">
                                            <span class="fw-medium">{{ $activity->causer?->name ?? 'System' }}</span>
                                            {{ $activity->description }}
                                            @if($activity->subject && !(method_exists($activity->subject, 'trashed') && $activity->subject->trashed()))
                                                "<a href="{{ route('workspaces.pages.show', [$workspace, $activity->subject]) }}"
                                                   class="text-decoration-none text-truncate">{{ Str::limit($activity->subject->title, 24) }}</a>"
                                            @endif
                                        </p>
                                        <p class="mb-0 text-body-secondary" style="font-size:0.68rem;">
                                            {{ $activity->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="list-group-item text-body-secondary small py-3 text-center">
                                No activity yet.
                            </div>
                            @endforelse
                        </div>

                    </div>{{-- /sidebar card --}}
                </div>{{-- /col-lg-3 --}}

                {{-- ── Main content ──────────────────────────────────────────── --}}
                <div class="col-lg-7">

                    {{-- ── Table view (hidden by default; JS toggles visibility) ─── --}}
                <div id="workspace-main-table" class="d-none mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header py-3 d-flex align-items-center justify-content-between">
                            <h2 class="h6 fw-semibold mb-0">
                                <i class="fas fa-table me-2 text-body-secondary"></i>All Pages
                                <span class="badge bg-secondary-subtle text-secondary-emphasis fw-normal ms-1">
                                    {{ $allPages->count() }}
                                </span>
                            </h2>
                            <a href="{{ route('workspaces.pages.create', $workspace) }}"
                               class="btn btn-sm btn-primary">
                                <i class="fas fa-plus me-1"></i> New Page
                            </a>
                        </div>
                        @if($allPages->isEmpty())
                            <div class="card-body text-center text-body-secondary py-5 small">
                                No pages yet. <a href="{{ route('workspaces.pages.create', $workspace) }}">Create the first one.</a>
                            </div>
                        @else
                        <div class="table-responsive">
                            <table id="pages-table" class="table table-hover table-sm mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3" data-sort="title">Title</th>
                                        <th data-sort="status">Status</th>
                                        <th data-sort="author" class="d-none d-md-table-cell">Author</th>
                                        <th data-sort="updated">Updated</th>
                                        <th class="d-none d-lg-table-cell">Tags</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($allPages as $page)
                                    <tr data-sorttitle="{{ $page->title }}"
                                        data-sortstatus="{{ $page->status }}"
                                        data-sortauthor="{{ $page->author?->name ?? '' }}"
                                        data-sortupdated="{{ $page->updated_at->timestamp }}">
                                        <td class="ps-3">
                                            <a href="{{ route('workspaces.pages.show', [$workspace, $page]) }}"
                                               class="text-decoration-none fw-medium">
                                                {{ $page->title }}
                                            </a>
                                            @if($page->parent_id)
                                                <span class="text-body-secondary small ms-1" title="Has parent page">
                                                    <i class="fas fa-level-up-alt fa-rotate-90" style="font-size:.6rem;"></i>
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge
                                                {{ $page->status === 'published' ? 'bg-success-subtle text-success-emphasis' : ($page->status === 'archived' ? 'bg-secondary-subtle text-secondary-emphasis' : 'bg-warning-subtle text-warning-emphasis') }}"
                                                style="font-size:.7rem;">
                                                {{ ucfirst($page->status) }}
                                            </span>
                                        </td>
                                        <td class="small text-body-secondary d-none d-md-table-cell">
                                            {{ $page->author?->name ?? '—' }}
                                        </td>
                                        <td class="small text-body-secondary text-nowrap">
                                            {{ $page->updated_at->diffForHumans() }}
                                        </td>
                                        <td class="d-none d-lg-table-cell">
                                            @foreach($page->tags->take(3) as $tag)
                                                <span class="badge bg-secondary-subtle text-secondary-emphasis me-1"
                                                      style="font-size:.65rem;">#{{ $tag->name }}</span>
                                            @endforeach
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- ── Default view (home page / welcome state) ───────────── --}}
                <div id="workspace-main-default">

                {{-- Flash alerts --}}
                    @if(session('status') === 'page-deleted')
                        <div class="alert alert-info alert-dismissible mb-4 py-2" role="alert">
                            <i class="fas fa-trash-alt me-1"></i> Page deleted successfully.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @if(session('status') === 'workspace-updated')
                        <div class="alert alert-success alert-dismissible mb-4 py-2" role="alert">
                            <i class="fas fa-check-circle me-1"></i> Workspace settings saved.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @if(session('status') === 'home-page-set')
                        <div class="alert alert-success alert-dismissible mb-4 py-2" role="alert">
                            <i class="fas fa-home me-1"></i> Home page updated.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @if(session('status') === 'home-page-cleared')
                        <div class="alert alert-info alert-dismissible mb-4 py-2" role="alert">
                            <i class="fas fa-home me-1"></i> Home page cleared.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    {{-- Pinned pages quick-access strip --}}
                    @if($pinnedPages->isNotEmpty())
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-4">
                        <span class="small text-body-secondary me-1">
                            <i class="fas fa-thumbtack me-1"></i>
                        </span>
                        @foreach($pinnedPages as $pin)
                            <a href="{{ route('workspaces.pages.show', [$workspace, $pin]) }}"
                               class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
                                <span class="text-truncate" style="max-width: 12rem;">{{ $pin->title }}</span>
                            </a>
                        @endforeach
                    </div>
                    @endif

                    {{-- Workspace description --}}
                    @if($workspace->description)
                    <div class="card shadow-sm mb-4">
                        <div class="card-body p-4">
                            <p class="text-body-secondary mb-0">{{ $workspace->description }}</p>
                        </div>
                    </div>
                    @endif

                    {{-- ── Home page embedded content ───────────────────────── --}}
                    @if($homePage)
                        @php $pageContentRenderer = app(\App\Services\PageContentRenderer::class); @endphp

                        {{-- Home page card --}}
                        <div class="card shadow-sm mb-4">
                            {{-- Page header --}}
                            <div class="card-header p-4">
                                <div class="d-flex align-items-start justify-content-between gap-3">
                                    <div>
                                        <h1 class="h3 mb-1">{{ $homePage->title }}</h1>
                                        <div class="d-flex flex-wrap align-items-center gap-3">
                                            @if($homePage->author)
                                                <span class="small text-body-secondary">
                                                    <i class="fas fa-user me-1"></i> {{ $homePage->author->name }}
                                                </span>
                                            @endif
                                            <span class="small text-body-secondary">
                                                <i class="fas fa-clock me-1"></i> {{ $homePage->updated_at->diffForHumans() }}
                                            </span>
                                            <span class="small text-body-secondary">
                                                <i class="fas fa-book-open me-1"></i> ~{{ $homePage->reading_time }} min read
                                            </span>
                                            <span class="badge
                                                {{ $homePage->status === 'published' ? 'bg-success-subtle text-success-emphasis' : ($homePage->status === 'archived' ? 'bg-secondary-subtle text-secondary-emphasis' : 'bg-warning-subtle text-warning-emphasis') }}">
                                                {{ ucfirst($homePage->status) }}
                                            </span>
                                        </div>
                                        @if($homePage->categories->isNotEmpty() || $homePage->tags->isNotEmpty())
                                            <div class="d-flex flex-wrap gap-2 mt-2">
                                                @foreach($homePage->categories as $cat)
                                                    <span class="badge bg-primary-subtle text-primary-emphasis">{{ $cat->name }}</span>
                                                @endforeach
                                                @foreach($homePage->tags as $tag)
                                                    <span class="badge bg-secondary-subtle text-secondary-emphasis">#{{ $tag->name }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                    <div class="d-flex gap-2 flex-shrink-0">
                                        @can('update', $homePage)
                                            <a href="{{ route('workspaces.pages.edit', [$workspace, $homePage]) }}"
                                               class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-edit me-1"></i> Edit
                                            </a>
                                        @endcan
                                        <a href="{{ route('workspaces.pages.show', [$workspace, $homePage]) }}"
                                           class="btn btn-sm btn-outline-secondary"
                                           title="View full page">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Page content — id="page-content" wires the ToC JS --}}
                            <div class="card-body p-4" id="page-content">
                                <div class="prose dark:prose-invert max-w-none">
                                    {!! $pageContentRenderer->render($homePage->content, $homePage->content_type, $workspace) !!}
                                </div>
                            </div>

                            @if($homePagePrevious || $homePageNext)
                            <div class="border-top px-4 py-4">
                                <nav aria-label="Page navigation">
                                    <div class="row g-3">
                                        @if($homePagePrevious)
                                            <div class="col-sm-6">
                                                <a href="{{ route('workspaces.pages.show', [$workspace, $homePagePrevious]) }}"
                                                   class="card card-hover text-decoration-none border p-3 h-100"
                                                   aria-label="Previous page: {{ $homePagePrevious->title }}">
                                                    <div class="small fw-semibold text-uppercase text-body-secondary mb-1">
                                                        <i class="fas fa-arrow-left me-1"></i> Previous page
                                                    </div>
                                                    <div class="fw-medium">{{ $homePagePrevious->title }}</div>
                                                </a>
                                            </div>
                                        @endif

                                        @if($homePageNext)
                                            <div class="col-sm-6 {{ $homePagePrevious ? '' : 'offset-sm-6' }}">
                                                <a href="{{ route('workspaces.pages.show', [$workspace, $homePageNext]) }}"
                                                   class="card card-hover text-decoration-none border p-3 h-100 text-sm-end"
                                                   aria-label="Next page: {{ $homePageNext->title }}">
                                                    <div class="small fw-semibold text-uppercase text-body-secondary mb-1">
                                                        Next page <i class="fas fa-arrow-right ms-1"></i>
                                                    </div>
                                                    <div class="fw-medium">{{ $homePageNext->title }}</div>
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </nav>
                            </div>
                            @endif

                            {{-- Sub-pages --}}
                            @if($homePageChildPages->isNotEmpty())
                            <div class="card-footer p-4">
                                <h6 class="small fw-semibold text-uppercase text-body-secondary mb-3">Sub-pages</h6>
                                <div class="row g-2">
                                    @foreach($homePageChildPages as $child)
                                        <div class="col-sm-6">
                                            <a href="{{ route('workspaces.pages.show', [$workspace, $child]) }}"
                                               class="card card-hover text-decoration-none border p-3 h-100">
                                                <div class="fw-medium small">{{ $child->title }}</div>
                                                @if($child->excerpt)
                                                    <div class="text-body-secondary small mt-1 text-truncate">{{ $child->excerpt }}</div>
                                                @endif
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>{{-- /home page card --}}

                        {{-- Attachments --}}
                        @php $attachments = $homePage->getMedia('attachments'); @endphp
                        @if($attachments->isNotEmpty())
                        <div class="card shadow-sm mb-4">
                            <div class="card-header py-3 d-flex align-items-center justify-content-between">
                                <h2 class="h6 fw-semibold mb-0">
                                    <i class="fas fa-paperclip me-2 text-body-secondary"></i>Attachments
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis fw-normal ms-1">
                                        {{ $attachments->count() }}
                                    </span>
                                </h2>
                                @can('update', $homePage)
                                <a href="{{ route('workspaces.pages.edit', [$workspace, $homePage]) }}#attachments"
                                   class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-upload me-1"></i> Manage
                                </a>
                                @endcan
                            </div>
                            <div class="list-group list-group-flush">
                                @foreach($attachments as $media)
                                <a href="{{ $media->getUrl() }}" target="_blank" rel="noopener"
                                   class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-2 px-3">
                                    @if(str_starts_with($media->mime_type ?? '', 'image/'))
                                        <img src="{{ $media->getUrl('thumb') }}" alt=""
                                             class="rounded flex-shrink-0 object-fit-cover"
                                             style="width:36px;height:36px;">
                                    @else
                                        <div class="d-flex align-items-center justify-content-center rounded bg-secondary-subtle flex-shrink-0"
                                             style="width:36px;height:36px;">
                                            <i class="fas fa-file text-secondary small"></i>
                                        </div>
                                    @endif
                                    <div class="flex-grow-1 overflow-hidden">
                                        <div class="small fw-medium text-truncate">{{ $media->name }}</div>
                                        <div class="text-body-secondary" style="font-size:0.72rem;">
                                            {{ strtoupper($media->extension) }} &middot; {{ \Illuminate\Support\Number::fileSize($media->size, precision: 1) }}
                                        </div>
                                    </div>
                                    <i class="fas fa-download text-body-secondary small flex-shrink-0"></i>
                                </a>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        {{-- Comments --}}
                        <div class="card shadow-sm" id="comments">
                            <div class="card-header p-4 d-flex align-items-center justify-content-between">
                                <h2 class="h6 mb-0 fw-semibold">
                                    <i class="fas fa-comments me-2 text-body-secondary"></i>
                                    Comments
                                    @php $commentCount = $homePage->comments->reduce(fn($c, $cm) => $c + 1 + $cm->replies->count(), 0); @endphp
                                    @if($commentCount > 0)
                                        <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1">{{ $commentCount }}</span>
                                    @endif
                                </h2>
                            </div>

                            @if(session('status') === 'comment-added')
                                <div class="alert alert-success alert-dismissible m-3 mb-0 py-2" role="alert">
                                    <i class="fas fa-check-circle me-1"></i> Comment added.
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endif
                            @if(session('status') === 'comment-deleted')
                                <div class="alert alert-info alert-dismissible m-3 mb-0 py-2" role="alert">
                                    <i class="fas fa-trash me-1"></i> Comment deleted.
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endif

                            <div class="card-body p-4">
                                @php
                                    $resolvedComments   = $homePage->comments->filter(fn($c) => $c->isResolved());
                                    $unresolvedComments = $homePage->comments->filter(fn($c) => !$c->isResolved());
                                @endphp

                                @forelse($unresolvedComments as $comment)
                                    <div class="d-flex gap-3 mb-4" id="comment-{{ $comment->id }}">
                                        <div class="flex-shrink-0">
                                            <span class="bg-primary-subtle text-primary-emphasis fw-bold d-flex align-items-center justify-content-center rounded-circle"
                                                  style="width:36px;height:36px;font-size:0.8rem;">
                                                {{ strtoupper(substr($comment->user->name ?? '?', 0, 2)) }}
                                            </span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-baseline gap-2 mb-1">
                                                <span class="fw-semibold small">{{ $comment->user->name ?? 'Unknown' }}</span>
                                                <span class="text-body-secondary" style="font-size:0.75rem;">
                                                    {{ $comment->created_at->diffForHumans() }}
                                                </span>
                                                @if(auth()->id() === $comment->user_id || auth()->user()->can('update', $homePage))
                                                    <form method="POST" class="ms-auto"
                                                          action="{{ route('workspaces.pages.comments.destroy', [$workspace, $homePage, $comment]) }}">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn btn-link btn-sm text-body-secondary p-0"
                                                                onclick="return confirm('Delete this comment?')">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                @if(auth()->id() === $comment->user_id || auth()->user()?->can('update', $homePage))
                                                    <form method="POST"
                                                          action="{{ route('workspaces.pages.comments.resolve', [$workspace, $homePage, $comment]) }}"
                                                          class="d-inline">
                                                        @csrf @method('PATCH')
                                                        <button type="submit" class="btn btn-link btn-sm p-0 ms-1 text-body-secondary"
                                                                title="Mark as resolved">
                                                            <i class="fas fa-check-circle"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                            <div class="small">{{ $comment->content }}</div>

                                            {{-- Replies --}}
                                            @foreach($comment->replies as $reply)
                                                <div class="d-flex gap-3 mt-3 ps-3 border-start border-2" id="comment-{{ $reply->id }}">
                                                    <div class="flex-shrink-0">
                                                        <span class="bg-secondary-subtle text-secondary-emphasis fw-bold d-flex align-items-center justify-content-center rounded-circle"
                                                              style="width:30px;height:30px;font-size:0.7rem;">
                                                            {{ strtoupper(substr($reply->user->name ?? '?', 0, 2)) }}
                                                        </span>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <div class="d-flex align-items-baseline gap-2 mb-1">
                                                            <span class="fw-semibold small">{{ $reply->user->name ?? 'Unknown' }}</span>
                                                            <span class="text-body-secondary" style="font-size:0.75rem;">
                                                                {{ $reply->created_at->diffForHumans() }}
                                                            </span>
                                                            @if(auth()->id() === $reply->user_id || auth()->user()->can('update', $homePage))
                                                                <form method="POST" class="ms-auto"
                                                                      action="{{ route('workspaces.pages.comments.destroy', [$workspace, $homePage, $reply]) }}">
                                                                    @csrf @method('DELETE')
                                                                    <button type="submit" class="btn btn-link btn-sm text-body-secondary p-0"
                                                                            onclick="return confirm('Delete this reply?')">
                                                                        <i class="fas fa-trash-alt"></i>
                                                                    </button>
                                                                </form>
                                                            @endif
                                                        </div>
                                                        <div class="small">{{ $reply->content }}</div>
                                                    </div>
                                                </div>
                                            @endforeach

                                            {{-- Reply form --}}
                                            <div class="mt-2">
                                                <button class="btn btn-link btn-sm p-0 text-body-secondary" type="button"
                                                        data-bs-toggle="collapse"
                                                        data-bs-target="#reply-form-{{ $comment->id }}"
                                                        aria-expanded="false">
                                                    <i class="fas fa-reply me-1"></i> Reply
                                                </button>
                                                <div class="collapse mt-2" id="reply-form-{{ $comment->id }}">
                                                    <form method="POST"
                                                          action="{{ route('workspaces.pages.comments.store', [$workspace, $homePage]) }}">
                                                        @csrf
                                                        <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                                                        <div class="input-group input-group-sm">
                                                            <textarea name="content" class="form-control" rows="2"
                                                                      placeholder="Write a reply…" required maxlength="5000"></textarea>
                                                            <button type="submit" class="btn btn-primary">Reply</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-body-secondary small text-center py-3 mb-0">
                                        No comments yet. Be the first to add one below.
                                    </p>
                                @endforelse

                                {{-- Resolved comments --}}
                                @if($resolvedComments->isNotEmpty())
                                    <div class="mt-3">
                                        <button class="btn btn-link btn-sm p-0 text-body-secondary" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#resolved-comments"
                                                aria-expanded="false">
                                            <i class="fas fa-check-circle me-1 text-success"></i>
                                            {{ $resolvedComments->count() }} resolved {{ Str::plural('comment', $resolvedComments->count()) }}
                                        </button>
                                        <div class="collapse" id="resolved-comments">
                                            @foreach($resolvedComments as $comment)
                                                <div class="d-flex gap-3 mt-3 opacity-75">
                                                    <div class="flex-shrink-0">
                                                        <div class="rounded-circle bg-secondary-subtle d-flex align-items-center justify-content-center"
                                                             style="width:32px;height:32px;font-size:0.75rem;">
                                                            {{ strtoupper(substr($comment->user->name ?? '?', 0, 1)) }}
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <div class="d-flex align-items-center gap-2 mb-1">
                                                            <span class="small fw-semibold">{{ $comment->user->name ?? 'Deleted user' }}</span>
                                                            <span class="text-body-secondary" style="font-size:0.73rem;">{{ $comment->created_at->diffForHumans() }}</span>
                                                            <span class="badge bg-success-subtle text-success-emphasis ms-1" style="font-size:0.65rem;">
                                                                <i class="fas fa-check me-1"></i>Resolved
                                                            </span>
                                                            @if(auth()->id() === $comment->user_id || auth()->user()?->can('update', $homePage))
                                                            <form method="POST"
                                                                  action="{{ route('workspaces.pages.comments.resolve', [$workspace, $homePage, $comment]) }}"
                                                                  class="d-inline ms-auto">
                                                                @csrf @method('PATCH')
                                                                <button type="submit" class="btn btn-link btn-sm p-0 text-body-secondary"
                                                                        title="Reopen">
                                                                    <i class="fas fa-undo"></i>
                                                                </button>
                                                            </form>
                                                            @endif
                                                        </div>
                                                        <div class="small text-body-secondary">{{ $comment->content }}</div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- New comment form --}}
                                <div class="border-top pt-4 mt-2">
                                    <h6 class="small fw-semibold mb-3">Add a comment</h6>
                                    <form method="POST"
                                          action="{{ route('workspaces.pages.comments.store', [$workspace, $homePage]) }}">
                                        @csrf
                                        @error('content')
                                            <div class="alert alert-danger py-2 mb-2 small">{{ $message }}</div>
                                        @enderror
                                        <div class="mb-2">
                                            <textarea name="content" class="form-control" rows="3"
                                                      placeholder="Share your thoughts, questions, or feedback…"
                                                      required maxlength="5000">{{ old('content') }}</textarea>
                                        </div>
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <i class="fas fa-paper-plane me-1"></i> Post Comment
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>{{-- /comments card --}}

                    @else
                        {{-- ── No home page: welcome / getting-started state ────── --}}
                        <div class="card shadow-sm">
                            <div class="card-body p-5 text-center text-body-secondary">
                                <i class="fas fa-home fa-2x mb-3 d-block opacity-25"></i>
                                <p class="mb-1 fw-semibold">No home page set</p>
                                <p class="small mb-3">
                                    Set any page as the workspace home and its content will appear here,
                                    complete with table of contents, attachments, and comments.
                                </p>
                                @if($pages->isNotEmpty())
                                    <p class="small mb-0">
                                        Open a page and click the
                                        <i class="fas fa-home mx-1"></i> button to designate it as the home page.
                                    </p>
                                @else
                                    <a href="{{ route('workspaces.pages.create', $workspace) }}"
                                       class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus me-1"></i> Create your first page
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                </div>{{-- /#workspace-main-default --}}

                </div>{{-- /col-lg-7 --}}

                {{-- ── Table of Contents (right column) ─────────────────────── --}}
                <div class="col-lg-2 d-none d-lg-block" id="toc-wrapper">
                    <div class="card shadow-sm sticky-top" style="top: 1rem;">
                        <div class="card-header py-2">
                            <span class="small fw-semibold text-uppercase text-body-secondary">
                                <i class="fas fa-list me-1"></i> Contents
                            </span>
                        </div>
                        <div class="card-body p-2">
                            <ul class="list-unstyled mb-0" id="toc-list"></ul>
                        </div>
                    </div>
                </div>

            </div>{{-- /row --}}
        </div>{{-- /container --}}
    </div>
</x-app-layout>
