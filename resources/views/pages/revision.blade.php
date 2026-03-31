<x-app-layout>
    <x-slot name="header">
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
                        <li class="breadcrumb-item">
                            <a href="{{ route('workspaces.pages.show', [$workspace, $crumb]) }}">{{ $crumb->title }}</a>
                        </li>
                    @endif
                @endforeach
                <li class="breadcrumb-item">
                    <a href="{{ route('workspaces.pages.history', [$workspace, $page]) }}">History</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">v{{ $revision->revision_number }}</li>
            </ol>
        </nav>
    </x-slot>

    <div class="py-4">
        <div class="container" style="max-width: 56rem;">

            {{-- Revision banner --}}
            <div class="alert alert-warning d-flex align-items-center gap-3 mb-4" role="alert">
                <i class="fas fa-clock-rotate-left fa-lg flex-shrink-0"></i>
                <div class="flex-grow-1">
                    You are viewing <strong>version {{ $revision->revision_number }}</strong>
                    @if($revision->user)
                        saved by <strong>{{ $revision->user->name }}</strong>
                    @endif
                    on {{ $revision->created_at->format('M j, Y \a\t g:i A') }}.
                    @if($revision->change_summary)
                        &mdash; <em>{{ $revision->change_summary }}</em>
                    @endif
                </div>
                <div class="d-flex gap-2 flex-shrink-0">
                    <a href="{{ route('workspaces.pages.history', [$workspace, $page]) }}"
                       class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-list me-1"></i> All revisions
                    </a>
                    <a href="{{ route('workspaces.pages.show', [$workspace, $page]) }}"
                       class="btn btn-sm btn-warning">
                        <i class="fas fa-arrow-right me-1"></i> Current version
                    </a>
                    @can('update', $page)
                    <form method="POST"
                          action="{{ route('workspaces.pages.revisions.restore', [$workspace, $page, $revision]) }}"
                          class="d-inline"
                          onsubmit="return confirm('Restore the page to v{{ $revision->revision_number }}? A new revision will be created.')">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success">
                            <i class="fas fa-undo me-1"></i> Restore this version
                        </button>
                    </form>
                    @endcan
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header p-4">
                    <h1 class="h3 mb-0">{{ $revision->title }}</h1>
                    <div class="d-flex align-items-center gap-3 mt-2">
                        <span class="badge bg-secondary-subtle text-secondary-emphasis">
                            v{{ $revision->revision_number }}
                        </span>
                        @if($revision->user)
                            <span class="small text-body-secondary">
                                <i class="fas fa-user me-1"></i>{{ $revision->user->name }}
                            </span>
                        @endif
                        <span class="small text-body-secondary">
                            <i class="fas fa-clock me-1"></i>{{ $revision->created_at->diffForHumans() }}
                        </span>
                    </div>
                </div>

                <div class="card-body p-4">
                    @php $pageContentRenderer = app(\App\Services\PageContentRenderer::class); @endphp
                    <div class="prose dark:prose-invert max-w-none">
                        {!! $pageContentRenderer->render($revision->content, $revision->content_type, $workspace) !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
