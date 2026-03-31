<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column gap-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('workspaces.show', $workspace) }}">{{ $workspace->name }}</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('workspaces.tags.index', $workspace) }}">Tags</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        #{{ $tag->getTranslation('name', 'en') }}
                    </li>
                </ol>
            </nav>

            @include('workspaces._header_actions')
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container" style="max-width: 52rem;">
            <div class="card shadow-sm">
                <div class="card-header p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <h1 class="h4 mb-0">
                            <i class="fas fa-hashtag me-1 text-body-secondary"></i>{{ $tag->getTranslation('name', 'en') }}
                        </h1>
                        <p class="small text-body-secondary mb-0 mt-1">
                            {{ $pages->count() }} {{ Str::plural('page', $pages->count()) }} tagged
                        </p>
                    </div>
                    <a href="{{ route('workspaces.tags.index', $workspace) }}"
                       class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> All Tags
                    </a>
                </div>

                <div class="list-group list-group-flush">
                    @forelse($pages as $page)
                        <a href="{{ route('workspaces.pages.show', [$workspace, $page]) }}"
                           class="list-group-item list-group-item-action p-4">
                            <div class="d-flex align-items-start justify-content-between gap-3">
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="fw-medium text-truncate">{{ $page->title }}</span>
                                        <span class="badge flex-shrink-0
                                            {{ $page->status === 'published' ? 'bg-success-subtle text-success-emphasis' : ($page->status === 'archived' ? 'bg-secondary-subtle text-secondary-emphasis' : 'bg-warning-subtle text-warning-emphasis') }}">
                                            {{ ucfirst($page->status) }}
                                        </span>
                                    </div>
                                    @if($page->excerpt)
                                        <p class="small text-body-secondary mb-1 text-truncate">{{ $page->excerpt }}</p>
                                    @endif
                                    <div class="d-flex flex-wrap gap-3 small text-body-secondary">
                                        @if($page->author)
                                            <span><i class="fas fa-user me-1"></i>{{ $page->author->name }}</span>
                                        @endif
                                        <span><i class="fas fa-clock me-1"></i>{{ $page->updated_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                                <i class="fas fa-chevron-right text-body-tertiary flex-shrink-0 mt-1"></i>
                            </div>
                        </a>
                    @empty
                        <div class="list-group-item p-5 text-center text-body-secondary">
                            <i class="fas fa-file-alt fa-2x mb-3 d-block opacity-25"></i>
                            No pages with this tag yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
