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
                <li class="breadcrumb-item active" aria-current="page">History</li>
            </ol>
        </nav>
    </x-slot>

    <div class="py-4">
        <div class="container" style="max-width: 56rem;">
            <div class="card shadow-sm">
                <div class="card-header p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <h1 class="h4 mb-0">
                            <i class="fas fa-history me-2 text-body-secondary"></i>
                            Page History
                        </h1>
                        <p class="text-body-secondary small mb-0 mt-1">
                            {{ $revisions->total() }} {{ Str::plural('revision', $revisions->total()) }} of
                            <strong>{{ $page->title }}</strong>
                        </p>
                    </div>
                    <a href="{{ route('workspaces.pages.show', [$workspace, $page]) }}"
                       class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to page
                    </a>
                </div>

                <div class="list-group list-group-flush">
                    @forelse($revisions as $revision)
                        <div class="list-group-item px-4 py-3">
                            <div class="d-flex align-items-start justify-content-between gap-3">
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="badge bg-secondary-subtle text-secondary-emphasis">
                                            v{{ $revision->revision_number }}
                                        </span>
                                        @if($loop->first)
                                            <span class="badge bg-success-subtle text-success-emphasis">Current</span>
                                        @endif
                                        <span class="fw-medium">
                                            {{ $revision->title }}
                                        </span>
                                    </div>
                                    <div class="small text-body-secondary d-flex flex-wrap gap-3">
                                        @if($revision->user)
                                            <span>
                                                <i class="fas fa-user me-1"></i>{{ $revision->user->name }}
                                            </span>
                                        @endif
                                        <span>
                                            <i class="fas fa-clock me-1"></i>{{ $revision->created_at->diffForHumans() }}
                                            <span class="text-body-tertiary ms-1">
                                                ({{ $revision->created_at->format('M j, Y g:i A') }})
                                            </span>
                                        </span>
                                        @if($revision->change_summary)
                                            <span>
                                                <i class="fas fa-comment-alt me-1"></i>{{ $revision->change_summary }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="d-flex gap-2 flex-shrink-0">
                                    <a href="{{ route('workspaces.pages.revisions.show', [$workspace, $page, $revision]) }}"
                                       class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-eye me-1"></i> View
                                    </a>
                                    @if(!$loop->first)
                                        <a href="{{ route('workspaces.pages.revisions.diff', [$workspace, $page]) }}?from={{ $revision->id }}"
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-code-branch me-1"></i> Compare
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="list-group-item px-4 py-5 text-center text-body-secondary">
                            <i class="fas fa-history fa-2x mb-3 d-block opacity-25"></i>
                            No revisions recorded yet.
                        </div>
                    @endforelse
                </div>

                @if($revisions->hasPages())
                    <div class="card-footer px-4 py-3">
                        {{ $revisions->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
