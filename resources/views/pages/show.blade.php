<x-app-layout>
    <x-slot name="header">
        {{-- Breadcrumb navigation --}}
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('workspaces.show', $workspace) }}">{{ $workspace->name }}</a>
                </li>
                @foreach($breadcrumbs as $crumb)
                    @if(!$loop->last)
                        <li class="breadcrumb-item">
                            <a href="{{ route('workspaces.pages.show', [$workspace, $crumb]) }}">{{ $crumb->title }}</a>
                        </li>
                    @else
                        <li class="breadcrumb-item active" aria-current="page">{{ $crumb->title }}</li>
                    @endif
                @endforeach
            </ol>
        </nav>
    </x-slot>

    <div class="py-4">
        <div class="container">
            <div class="row g-4">
                {{-- Sidebar: page tree --}}
                <div class="col-lg-2 d-none d-lg-block">
                    <div class="card shadow-sm sticky-top" style="top: 1rem;" data-workspace-id="{{ $workspace->id }}">
                        <div class="card-header py-2">
                            <a href="{{ route('workspaces.show', $workspace) }}"
                               class="small text-primary text-decoration-none">
                                <i class="fas fa-arrow-left me-1"></i> {{ $workspace->name }}
                            </a>
                        </div>
                        <div class="list-group list-group-flush">
                            @include('pages._page_tree', ['pages' => $pageTree, 'workspace' => $workspace, 'depth' => 0, 'activePage' => $page, 'sortable' => auth()->user()?->can('update', $page)])
                        </div>
                    </div>
                </div>

                {{-- Main content --}}
                <div class="col-lg-7">
                    <div class="card shadow-sm">
                        {{-- Page header --}}
                        <div class="card-header p-4">
                            <div class="d-flex align-items-start justify-content-between gap-3">
                                <h1 class="h3 mb-2">{{ $page->title }}</h1>
                                <div class="d-flex gap-2 flex-shrink-0 mb-2">
                                    {{-- feat 1.6: Star / unstar toggle --}}
                                    @php $isStarred = $page->isStarredBy(auth()->user()); @endphp
                                    <form method="POST" id="star-form"
                                          action="{{ route('workspaces.pages.star', [$workspace, $page]) }}">
                                        @csrf
                                        <button type="submit" id="star-btn"
                                                class="btn btn-sm {{ $isStarred ? 'btn-warning' : 'btn-outline-secondary' }}"
                                                title="{{ $isStarred ? 'Unstar page' : 'Star page' }}"
                                                data-starred="{{ $isStarred ? 'true' : 'false' }}"
                                                data-url="{{ route('workspaces.pages.star', [$workspace, $page]) }}">
                                            <i class="fas fa-star"></i>
                                        </button>
                                    </form>
                                    {{-- Watch toggle --}}
                                    @php $isWatched = $page->isWatchedBy(auth()->user()); @endphp
                                    <form method="POST" id="watch-form"
                                          action="{{ route('workspaces.pages.watch', [$workspace, $page]) }}">
                                        @csrf
                                        <button type="submit" id="watch-btn"
                                                class="btn btn-sm {{ $isWatched ? 'btn-info' : 'btn-outline-secondary' }}"
                                                title="{{ $isWatched ? 'Unwatch page' : 'Watch page' }}"
                                                data-watching="{{ $isWatched ? 'true' : 'false' }}"
                                                data-url="{{ route('workspaces.pages.watch', [$workspace, $page]) }}">
                                            <i class="fas fa-bell"></i>
                                        </button>
                                    </form>
                                    {{-- Pin to workspace --}}
                                    @can('update', $page)
                                        @php $isPinned = \App\Models\PagePin::where('workspace_id', $workspace->id)->where('page_id', $page->id)->exists(); @endphp
                                        <form method="POST"
                                              action="{{ route('workspaces.pages.pin', [$workspace, $page]) }}">
                                            @csrf
                                            <button type="submit"
                                                    class="btn btn-sm {{ $isPinned ? 'btn-warning' : 'btn-outline-secondary' }}"
                                                    title="{{ $isPinned ? 'Unpin from workspace' : 'Pin to workspace' }}">
                                                <i class="fas fa-thumbtack"></i>
                                            </button>
                                        </form>
                                    @endcan
                                    {{-- feat 3.3: Set / clear as workspace home page --}}
                                    @can('update', $workspace)
                                        @php $isHomePage = $workspace->home_page_id === $page->id; @endphp
                                        <form method="POST"
                                              action="{{ route('workspaces.set-home-page', $workspace) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="page_id" value="{{ $isHomePage ? '' : $page->id }}">
                                            <button type="submit"
                                                    class="btn btn-sm {{ $isHomePage ? 'btn-primary' : 'btn-outline-secondary' }}"
                                                    title="{{ $isHomePage ? 'Remove from workspace home — hub will show an empty state' : 'Set as workspace home — content will appear on the workspace hub' }}">
                                                <i class="fas fa-home"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </div>

                            <div class="d-flex align-items-start justify-content-between gap-3">
                                <div class="d-flex gap-2 flex-shrink-0 mb-2">
                                    {{-- feat 5.3: Export dropdown --}}
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                                type="button" data-bs-toggle="dropdown"
                                                aria-expanded="false" title="Export">
                                            <i class="fas fa-download"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li>
                                                <a class="dropdown-item small"
                                                   href="{{ route('workspaces.pages.print', [$workspace, $page]) }}"
                                                   target="_blank" rel="noopener noreferrer">
                                                    <i class="fas fa-print me-2 text-body-secondary"></i>Print / Save PDF
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item small"
                                                   href="{{ route('workspaces.pages.export.markdown', [$workspace, $page]) }}">
                                                    <i class="fab fa-markdown me-2 text-body-secondary"></i>Download as Markdown
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                    <a href="{{ route('workspaces.pages.history', [$workspace, $page]) }}"
                                       class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-history me-1"></i> History
                                    </a>
                                    @can('update', $page)
                                        <a href="{{ route('workspaces.pages.edit', [$workspace, $page]) }}"
                                           class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-edit me-1"></i> Edit
                                        </a>
                                    @endcan
                                    @can('duplicate', $page)
                                        <form method="POST"
                                              action="{{ route('workspaces.pages.duplicate', [$workspace, $page]) }}"
                                              class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-secondary"
                                                    title="Duplicate page">
                                                <i class="fas fa-copy me-1"></i> Duplicate
                                            </button>
                                        </form>
                                    @endcan
                                    @can('update', $page)
                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                                data-bs-toggle="modal" data-bs-target="#saveAsTemplateModal"
                                                title="Save this page as a reusable template">
                                            <i class="fas fa-file-export me-1"></i> Save as Template
                                        </button>
                                    @endcan
                                    @can('update', $page)
                                        @if($transferableWorkspaces->isNotEmpty())
                                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                                    data-bs-toggle="modal" data-bs-target="#transferPageModal"
                                                    title="Move to another workspace">
                                                <i class="fas fa-share-square me-1"></i> Transfer
                                            </button>
                                        @endif
                                    @endcan
                                    @can('delete', $page)
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal" data-bs-target="#deletePageModal"
                                                title="Delete page">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    @endcan
                                </div>
                            </div>

                            {{-- Meta --}}
                            <div class="d-flex flex-wrap align-items-center gap-3 mt-2">
                                @if($page->author)
                                    <span class="small text-body-secondary">
                                        <i class="fas fa-user me-1"></i> {{ $page->author->name }}
                                    </span>
                                @endif
                                <span class="small text-body-secondary">
                                    <i class="fas fa-clock me-1"></i> {{ $page->updated_at->diffForHumans() }}
                                </span>
                                {{-- feat 1.8: Reading time estimate --}}
                                <span class="small text-body-secondary">
                                    <i class="fas fa-book-open me-1"></i> ~{{ $page->reading_time }} min read
                                </span>

                                {{-- feat 1.4: status badge — clickable dropdown for editors, plain badge for viewers --}}
                                @can('update', $page)
                                    <div class="dropdown">
                                        <button type="button"
                                                class="btn btn-sm badge border-0 dropdown-toggle
                                                    {{ $page->status === 'published' ? 'bg-success-subtle text-success-emphasis' : ($page->status === 'archived' ? 'bg-secondary-subtle text-secondary-emphasis' : 'bg-warning-subtle text-warning-emphasis') }}"
                                                data-bs-toggle="dropdown" aria-expanded="false"
                                                title="Change status">
                                            {{ ucfirst($page->status) }}
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li><h6 class="dropdown-header">Change Status</h6></li>
                                            @foreach(['draft', 'published', 'archived'] as $s)
                                                <li>
                                                    <form method="POST"
                                                          action="{{ route('workspaces.pages.status', [$workspace, $page]) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="status" value="{{ $s }}">
                                                        <button type="submit"
                                                                class="dropdown-item d-flex align-items-center gap-2 {{ $page->status === $s ? 'active' : '' }}">
                                                            {{ ucfirst($s) }}
                                                            @if($page->status === $s)
                                                                <i class="fas fa-check ms-auto text-success small"></i>
                                                            @endif
                                                        </button>
                                                    </form>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @else
                                    <span class="badge
                                        {{ $page->status === 'published' ? 'bg-success-subtle text-success-emphasis' : ($page->status === 'archived' ? 'bg-secondary-subtle text-secondary-emphasis' : 'bg-warning-subtle text-warning-emphasis') }}">
                                        {{ ucfirst($page->status) }}
                                    </span>
                                @endcan
                            </div>

                            {{-- feat 3.3: home page badge --}}
                            @if($workspace->home_page_id === $page->id)
                                <span class="badge bg-primary-subtle text-primary-emphasis mt-1">
                                    <i class="fas fa-home me-1"></i> Workspace Home
                                </span>
                            @endif

                            {{-- Categories & Tags --}}
                            @if($page->categories->isNotEmpty() || $page->tags->isNotEmpty())
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    @foreach($page->categories as $cat)
                                        <span class="badge bg-primary-subtle text-primary-emphasis">{{ $cat->name }}</span>
                                    @endforeach
                                    @foreach($page->tags as $tag)
                                        <span class="badge bg-secondary-subtle text-secondary-emphasis">#{{ $tag->name }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Page content --}}
                        {{-- feat 3.4: content is passed through PageLinkResolver so [[Page Title]]
                             syntax is resolved to hyperlinks at render time. --}}
                        @php $linkResolver = app(\App\Services\PageLinkResolver::class); @endphp
                        <div class="card-body p-4" id="page-content">
                            @if($page->content_type === 'markdown')
                                <div class="prose dark:prose-invert max-w-none">
                                    {!! $linkResolver->render(
                                            app(\Spatie\LaravelMarkdown\MarkdownRenderer::class)->toHtml($page->content ?? ''),
                                            $workspace
                                        ) !!}
                                </div>
                            @else
                                <div class="prose dark:prose-invert max-w-none">
                                    {!! $linkResolver->render($page->content ?? '', $workspace) !!}
                                </div>
                            @endif
                        </div>

                        @if($previousPage || $nextPage)
                            <div class="border-top px-4 py-4">
                                <nav aria-label="Page navigation">
                                    <div class="row g-3">
                                        @if($previousPage)
                                            <div class="col-sm-6">
                                                <a href="{{ route('workspaces.pages.show', [$workspace, $previousPage]) }}"
                                                   class="card card-hover text-decoration-none border p-3 h-100"
                                                   aria-label="Previous page: {{ $previousPage->title }}">
                                                    <div class="small fw-semibold text-uppercase text-body-secondary mb-1">
                                                        <i class="fas fa-arrow-left me-1"></i> Previous page
                                                    </div>
                                                    <div class="fw-medium">{{ $previousPage->title }}</div>
                                                </a>
                                            </div>
                                        @endif

                                        @if($nextPage)
                                            <div class="col-sm-6 {{ $previousPage ? '' : 'offset-sm-6' }}">
                                                <a href="{{ route('workspaces.pages.show', [$workspace, $nextPage]) }}"
                                                   class="card card-hover text-decoration-none border p-3 h-100 text-sm-end"
                                                   aria-label="Next page: {{ $nextPage->title }}">
                                                    <div class="small fw-semibold text-uppercase text-body-secondary mb-1">
                                                        Next page <i class="fas fa-arrow-right ms-1"></i>
                                                    </div>
                                                    <div class="fw-medium">{{ $nextPage->title }}</div>
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </nav>
                            </div>
                        @endif

                        {{-- Child pages --}}
                        @if($childPages->isNotEmpty())
                            <div class="card-footer p-4">
                                <h6 class="small fw-semibold text-uppercase text-body-secondary mb-3">Sub-pages</h6>
                                <div class="row g-2">
                                    @foreach($childPages as $child)
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
                    </div>

                    {{-- ── feat 1.9: Attachments ───────────────────────────────── --}}
                    @php $attachments = $page->getMedia('attachments'); @endphp
                    @if($attachments->isNotEmpty())
                    <div class="card shadow-sm mt-4">
                        <div class="card-header py-3 d-flex align-items-center justify-content-between">
                            <h2 class="h6 fw-semibold mb-0">
                                <i class="fas fa-paperclip me-2 text-body-secondary"></i>Attachments
                                <span class="badge bg-secondary-subtle text-secondary-emphasis fw-normal ms-1">
                                    {{ $attachments->count() }}
                                </span>
                            </h2>
                            @can('update', $page)
                            <a href="{{ route('workspaces.pages.edit', [$workspace, $page]) }}#attachments"
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

                    {{-- ── feat 3.4: Backlinks ("What links here") ─────────────── --}}
                    @php $backlinks = $page->incomingLinks()->with('sourcePage')->get(); @endphp
                    @if($backlinks->isNotEmpty())
                    <div class="card shadow-sm mt-4">
                        <div class="card-header py-2 d-flex align-items-center justify-content-between">
                            <span class="small fw-semibold text-uppercase text-body-secondary">
                                <i class="fas fa-link me-1"></i> Linked from
                                <span class="badge bg-secondary-subtle text-secondary-emphasis fw-normal ms-1">
                                    {{ $backlinks->count() }}
                                </span>
                            </span>
                        </div>
                        <div class="list-group list-group-flush">
                            @foreach($backlinks as $link)
                                @if($link->sourcePage)
                                <a href="{{ route('workspaces.pages.show', [$workspace, $link->sourcePage]) }}"
                                   class="list-group-item list-group-item-action py-2 px-3 small">
                                    <i class="fas fa-file-alt text-body-secondary me-2"></i>
                                    {{ $link->sourcePage->title }}
                                </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- ── feat 3.1: Comments ──────────────────────────────────── --}}
                    <div class="card shadow-sm mt-4" id="comments">
                        <div class="card-header p-4 d-flex align-items-center justify-content-between">
                            <h2 class="h6 mb-0 fw-semibold">
                                <i class="fas fa-comments me-2 text-body-secondary"></i>
                                Comments
                                @php $commentCount = $page->comments->reduce(fn ($c, $cm) => $c + 1 + $cm->replies->count(), 0); @endphp
                                @if($commentCount > 0)
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1">{{ $commentCount }}</span>
                                @endif
                            </h2>
                        </div>

                        {{-- Home page status flash --}}
                        @if(session('status') === 'home-page-set')
                            <div class="alert alert-success alert-dismissible m-3 mb-0 py-2" role="alert">
                                <i class="fas fa-home me-1"></i> This page is now the workspace home page.
                                Visitors will be redirected here when they open the workspace.
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif
                        {{-- Transfer flash --}}
                        @if(session('status') === 'page-transferred')
                            <div class="alert alert-success alert-dismissible m-3 mb-0 py-2" role="alert">
                                <i class="fas fa-check-circle me-1"></i> Page transferred successfully.
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif
                        {{-- Success flash --}}
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
                            {{-- Existing comments --}}
                            @php
                                $resolvedComments = $page->comments->filter(fn($c) => $c->isResolved());
                                $unresolvedComments = $page->comments->filter(fn($c) => !$c->isResolved());
                            @endphp
                            @forelse($unresolvedComments as $comment)
                                <div class="d-flex gap-3 mb-4" id="comment-{{ $comment->id }}">
                                    <div class="flex-shrink-0">
                                        <span class="avatar-circle bg-primary-subtle text-primary-emphasis fw-bold d-flex align-items-center justify-content-center rounded-circle"
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
                                            {{-- Delete own comment or moderator --}}
                                            @if(auth()->id() === $comment->user_id || auth()->user()->can('update', $page))
                                                <form method="POST" class="ms-auto"
                                                      action="{{ route('workspaces.pages.comments.destroy', [$workspace, $page, $comment]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-link btn-sm text-body-secondary p-0"
                                                            title="Delete comment"
                                                            onclick="return confirm('Delete this comment?')">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            {{-- Resolve button --}}
                                            @if(auth()->id() === $comment->user_id || auth()->user()?->can('update', $page))
                                            <form method="POST"
                                                  action="{{ route('workspaces.pages.comments.resolve', [$workspace, $page, $comment]) }}"
                                                  class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit"
                                                        class="btn btn-link btn-sm p-0 ms-2 text-body-secondary"
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
                                                    <span class="avatar-circle bg-secondary-subtle text-secondary-emphasis fw-bold d-flex align-items-center justify-content-center rounded-circle"
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
                                                        @if(auth()->id() === $reply->user_id || auth()->user()->can('update', $page))
                                                            <form method="POST" class="ms-auto"
                                                                  action="{{ route('workspaces.pages.comments.destroy', [$workspace, $page, $reply]) }}">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-link btn-sm text-body-secondary p-0"
                                                                        title="Delete reply"
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

                                        {{-- Reply form (collapsed by default) --}}
                                        <div class="mt-2">
                                            <button class="btn btn-link btn-sm p-0 text-body-secondary"
                                                    type="button"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#reply-form-{{ $comment->id }}"
                                                    aria-expanded="false">
                                                <i class="fas fa-reply me-1"></i> Reply
                                            </button>
                                            <div class="collapse mt-2" id="reply-form-{{ $comment->id }}">
                                                <form method="POST"
                                                      action="{{ route('workspaces.pages.comments.store', [$workspace, $page]) }}">
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

                            {{-- Resolved comments (collapsed) --}}
                            @if($resolvedComments->isNotEmpty())
                                <div class="mt-3">
                                    <button class="btn btn-link btn-sm p-0 text-body-secondary"
                                            type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#resolved-comments"
                                            aria-expanded="false">
                                        <i class="fas fa-check-circle me-1 text-success"></i>
                                        {{ $resolvedComments->count() }} resolved {{ Str::plural('comment', $resolvedComments->count()) }}
                                    </button>
                                    <div class="collapse" id="resolved-comments">
                                        @foreach($resolvedComments as $comment)
                                            <div class="d-flex gap-3 mt-3 opacity-75" id="comment-{{ $comment->id }}">
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
                                                        {{-- Unresolve button --}}
                                                        @if(auth()->id() === $comment->user_id || auth()->user()?->can('update', $page))
                                                        <form method="POST"
                                                              action="{{ route('workspaces.pages.comments.resolve', [$workspace, $page, $comment]) }}"
                                                              class="d-inline ms-auto">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit"
                                                                    class="btn btn-link btn-sm p-0 text-body-secondary"
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
                                      action="{{ route('workspaces.pages.comments.store', [$workspace, $page]) }}">
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
                    </div>
                </div>

                <div class="col-lg-3 d-none d-lg-block">
                    {{-- ── Sprint 12.1: Backlinks panel ─────────────────────────── --}}
                    @if($incomingLinks->isNotEmpty() || !empty($brokenOutgoingLinks))
                        <div class="card shadow-sm mb-4">
                            <div class="card-header py-3 d-flex align-items-center justify-content-between">
                                <h2 class="h6 fw-semibold mb-0">
                                    <i class="fas fa-link me-2 text-body-secondary"></i>References
                                </h2>
                                @if($incomingLinks->isNotEmpty())
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis fw-normal">
                                {{ $incomingLinks->count() }} {{ Str::plural('backlink', $incomingLinks->count()) }}
                            </span>
                                @endif
                            </div>
                            <div class="card-body p-4">

                                {{-- Incoming backlinks --}}
                                @if($incomingLinks->isNotEmpty())
                                    <h6 class="small fw-semibold text-uppercase text-body-secondary mb-3">
                                        <i class="fas fa-arrow-left me-1"></i> What Links Here
                                    </h6>
                                    <div class="d-flex flex-column gap-2 mb-3">
                                        @foreach($incomingLinks as $link)
                                            @if($link->sourcePage)
                                                <div class="d-flex align-items-center gap-2">
                                                    <a href="{{ route('workspaces.pages.show', [$link->sourcePage->workspace, $link->sourcePage]) }}"
                                                       class="text-decoration-none fw-medium small flex-grow-1 text-truncate">
                                                        {{ $link->sourcePage->title }}
                                                    </a>
                                                    @if($link->sourcePage->workspace_id !== $workspace->id)
                                                        <span class="badge bg-secondary-subtle text-secondary-emphasis"
                                                              style="font-size:.65rem;">
                                                <span class="rounded-circle d-inline-block me-1"
                                                      style="width:6px;height:6px;background:{{ $link->sourcePage->workspace->color ?? '#6366f1' }};"></span>
                                                {{ $link->sourcePage->workspace->name }}
                                            </span>
                                                    @endif
                                                    <span class="badge
                                            {{ $link->sourcePage->status === 'published' ? 'bg-success-subtle text-success-emphasis' : 'bg-secondary-subtle text-secondary-emphasis' }}"
                                                          style="font-size:.65rem;">
                                            {{ ucfirst($link->sourcePage->status) }}
                                        </span>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                    <p class="small text-body-secondary mb-3">
                                        <i class="fas fa-info-circle me-1"></i>
                                        No other pages link to this page yet.
                                    </p>
                                @endif

                                {{-- Broken outgoing links --}}
                                @if(!empty($brokenOutgoingLinks))
                                    <div class="border-top pt-3">
                                        <h6 class="small fw-semibold text-uppercase text-body-secondary mb-2">
                                            <i class="fas fa-exclamation-triangle text-warning me-1"></i> Broken Links
                                        </h6>
                                        <p class="small text-body-secondary mb-2">
                                            These <code>[[wiki links]]</code> on this page don't resolve to any page in this workspace:
                                        </p>
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($brokenOutgoingLinks as $broken)
                                                <span class="badge bg-danger-subtle text-danger-emphasis"
                                                      style="font-size:.72rem;">
                                            <i class="fas fa-unlink me-1"></i>{{ $broken }}
                                        </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                            </div>
                        </div>
                    @endif

                    {{-- Table of Contents --}}
                    <div id="toc-wrapper">
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
                </div>
            </div>
        </div>
    </div>

    {{-- Sprint 12.2: Save as Template modal --}}
    @can('update', $page)
    <div class="modal fade" id="saveAsTemplateModal" tabindex="-1"
         aria-labelledby="saveAsTemplateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title" id="saveAsTemplateModalLabel">
                        <i class="fas fa-file-export me-2 text-primary"></i>Save as Template
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST"
                      action="{{ route('workspaces.pages.save-as-template', [$workspace, $page]) }}">
                    @csrf
                    <div class="modal-body">
                        <p class="small text-body-secondary mb-3">
                            Save <strong>{{ $page->title }}</strong>'s current content as a reusable template
                            for new pages in <strong>{{ $workspace->name }}</strong>.
                        </p>
                        <div class="mb-3">
                            <label for="template-name" class="form-label small fw-semibold">
                                Template name <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="template-name" name="name"
                                   class="form-control"
                                   value="{{ $page->title }}"
                                   placeholder="e.g. Game Design Document, Sprint Retrospective…"
                                   required maxlength="255" />
                        </div>
                        <div class="mb-1">
                            <label for="template-description" class="form-label small fw-semibold">
                                Description <span class="text-body-secondary fw-normal">(optional)</span>
                            </label>
                            <textarea id="template-description" name="description"
                                      class="form-control" rows="2"
                                      placeholder="What is this template for?"
                                      maxlength="500"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary"
                                data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Template
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endcan

    {{-- feat 1.5: Delete page confirmation modal --}}
    @can('delete', $page)
    <div class="modal fade" id="deletePageModal" tabindex="-1" aria-labelledby="deletePageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title text-danger" id="deletePageModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Delete Page?
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">
                        You are about to delete <strong>{{ $page->title }}</strong>.
                    </p>
                    @if($childPages->isNotEmpty())
                        <div class="alert alert-warning py-2 small mb-2">
                            <i class="fas fa-sitemap me-1"></i>
                            This page has <strong>{{ $childPages->count() }}</strong> sub-{{ Str::plural('page', $childPages->count()) }}.
                            They will be moved up to this page's parent level.
                        </div>
                    @endif
                    <p class="small text-body-secondary mb-0">This action can be reversed by an administrator.</p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST"
                          action="{{ route('workspaces.pages.destroy', [$workspace, $page]) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash-alt me-1"></i> Delete Page
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endcan

    @can('update', $page)
        @if($transferableWorkspaces->isNotEmpty())
        <div class="modal fade" id="transferPageModal" tabindex="-1"
             aria-labelledby="transferPageModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title" id="transferPageModalLabel">
                            <i class="fas fa-share-square me-2 text-primary"></i>Transfer Page
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST"
                          action="{{ route('workspaces.pages.transfer', [$workspace, $page]) }}">
                        @csrf
                        <div class="modal-body">
                            <p class="small text-body-secondary mb-3">
                                Move <strong>{{ $page->title }}</strong>
                                @if($childPages->isNotEmpty())
                                    and its <strong>{{ $childPages->count() }}</strong>
                                    sub-{{ Str::plural('page', $childPages->count()) }}
                                @endif
                                to another workspace. You must have editor or admin access to the target workspace.
                            </p>

                            <div class="mb-3">
                                <label for="target_workspace_id" class="form-label small fw-semibold">
                                    Target workspace <span class="text-danger">*</span>
                                </label>
                                <select name="target_workspace_id" id="target_workspace_id"
                                        class="form-select" required>
                                    <option value="">— Select a workspace —</option>
                                    @foreach($transferableWorkspaces as $tw)
                                        <option value="{{ $tw->id }}">
                                            {{ $tw->icon ? $tw->icon . ' ' : '' }}{{ $tw->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="alert alert-warning py-2 small mb-0">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                The page will be moved to the root level of the target workspace.
                                Tags and categories may not exist in the target workspace.
                            </div>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-outline-secondary"
                                    data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-share-square me-1"></i> Transfer Page
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif
    @endcan

    @push('scripts')
    <script>
        // feat 1.6 — AJAX star toggle
        const starBtn  = document.getElementById('star-btn');
        if (starBtn) {
            starBtn.addEventListener('click', async () => {
                try {
                    const res  = await fetch(starBtn.dataset.url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                                          ?? '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const data = await res.json();
                    starBtn.dataset.starred = data.starred ? 'true' : 'false';
                    starBtn.classList.toggle('btn-warning', data.starred);
                    starBtn.classList.toggle('btn-outline-secondary', !data.starred);
                    starBtn.title = data.starred ? 'Unstar page' : 'Star page';
                } catch (e) {
                    // Fallback: plain form submit
                    document.getElementById('star-form').submit();
                }
            });
        }

        // Watch toggle (AJAX, mirrors star toggle)
        const watchBtn = document.getElementById('watch-btn');
        if (watchBtn) {
            watchBtn.addEventListener('click', async (e) => {
                e.preventDefault();
                try {
                    const res = await fetch(watchBtn.dataset.url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const data = await res.json();
                    watchBtn.dataset.watching = data.watching ? 'true' : 'false';
                    watchBtn.classList.toggle('btn-info', data.watching);
                    watchBtn.classList.toggle('btn-outline-secondary', !data.watching);
                    watchBtn.title = data.watching ? 'Unwatch page' : 'Watch page';
                } catch (e) {
                    document.getElementById('watch-form').submit();
                }
            });
        }

        // ── Sprint 13.1: @mention autocomplete for plain comment textareas ──
        (function () {
            const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

            // Shared dropdown element
            const dropdown = document.createElement('ul');
            dropdown.id = 'mention-dropdown';
            dropdown.className = 'list-group shadow-sm';
            dropdown.style.cssText =
                'position:fixed;z-index:9999;min-width:220px;max-height:220px;' +
                'overflow-y:auto;display:none;border:1px solid rgba(0,0,0,.15);border-radius:.375rem;';
            document.body.appendChild(dropdown);

            let activeTextarea = null;
            let mentionStart   = -1;
            let currentQuery   = '';

            function hideDrop() {
                dropdown.style.display = 'none';
                mentionStart = -1;
                currentQuery = '';
            }

            async function fetchUsers(q) {
                try {
                    const r = await fetch(`/api/mentions/users?q=${encodeURIComponent(q)}`, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    return r.ok ? r.json() : [];
                } catch { return []; }
            }

            function positionDrop(textarea) {
                // Place dropdown just below the textarea
                const rect = textarea.getBoundingClientRect();
                dropdown.style.left = rect.left + 'px';
                dropdown.style.top  = (rect.bottom + window.scrollY + 4) + 'px';
            }

            function showDrop(textarea, users) {
                dropdown.innerHTML = '';
                if (users.length === 0) { hideDrop(); return; }

                users.forEach(function (u) {
                    const li = document.createElement('li');
                    li.className = 'list-group-item list-group-item-action py-2 px-3';
                    li.style.cursor = 'pointer';
                    li.innerHTML =
                        '<div class="small fw-medium">' + escHtml(u.label) + '</div>' +
                        '<div class="text-body-secondary" style="font-size:.72rem;">' + escHtml(u.sublabel || '') + '</div>';

                    li.addEventListener('mousedown', function (e) {
                        e.preventDefault(); // don't lose textarea focus
                        insertMention(textarea, u.label);
                        hideDrop();
                    });
                    dropdown.appendChild(li);
                });

                positionDrop(textarea);
                dropdown.style.display = 'block';
            }

            function insertMention(textarea, name) {
                const val    = textarea.value;
                const before = val.slice(0, mentionStart); // up to and including @
                const after  = val.slice(textarea.selectionStart);
                textarea.value = before + name + ' ' + after;
                const pos = before.length + name.length + 1;
                textarea.setSelectionRange(pos, pos);
                textarea.focus();
            }

            function escHtml(s) {
                return String(s).replace(/[&<>"']/g, function (c) {
                    return { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c];
                });
            }

            let debounceTimer = null;

            function onInput(e) {
                const ta  = e.target;
                const pos = ta.selectionStart;
                const val = ta.value;

                // Find the nearest @ before the cursor on the current "word"
                const slice = val.slice(0, pos);
                const atPos = slice.lastIndexOf('@');

                if (atPos === -1) { hideDrop(); return; }

                // Only trigger if nothing but word chars between @ and cursor
                const between = slice.slice(atPos + 1);
                if (/\s/.test(between)) { hideDrop(); return; }

                mentionStart   = atPos;
                currentQuery   = between;
                activeTextarea = ta;

                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(async function () {
                    const users = await fetchUsers(currentQuery);
                    showDrop(ta, users);
                }, 200);
            }

            function onKeydown(e) {
                if (dropdown.style.display === 'none') return;

                const items = dropdown.querySelectorAll('li');
                const active = dropdown.querySelector('li.active');
                let idx = active ? [...items].indexOf(active) : -1;

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (active) active.classList.remove('active');
                    items[Math.min(idx + 1, items.length - 1)]?.classList.add('active');
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (active) active.classList.remove('active');
                    items[Math.max(idx - 1, 0)]?.classList.add('active');
                } else if ((e.key === 'Enter' || e.key === 'Tab') && active) {
                    e.preventDefault();
                    active.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
                } else if (e.key === 'Escape') {
                    hideDrop();
                }
            }

            // Attach to all comment textareas (present and future via event delegation)
            document.addEventListener('input', function (e) {
                if (e.target.matches('textarea[name="content"]')) onInput(e);
            });
            document.addEventListener('keydown', function (e) {
                if (e.target.matches('textarea[name="content"]')) onKeydown(e);
            });
            document.addEventListener('click', function (e) {
                if (!dropdown.contains(e.target)) hideDrop();
            });
        })();
    </script>
    @endpush
</x-app-layout>
