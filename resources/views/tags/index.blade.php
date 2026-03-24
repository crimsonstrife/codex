<x-app-layout>
    <x-slot name="header">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('workspaces.show', $workspace) }}">{{ $workspace->name }}</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Tags</li>
            </ol>
        </nav>
    </x-slot>

    <div class="py-4">
        <div class="container" style="max-width: 52rem;">
            <div class="card shadow-sm">
                <div class="card-header p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <h1 class="h4 mb-0">
                            <i class="fas fa-tags me-2 text-body-secondary"></i>Tags
                        </h1>
                        <p class="small text-body-secondary mb-0 mt-1">
                            {{ $tags->count() }} {{ Str::plural('tag', $tags->count()) }} used across this workspace
                        </p>
                    </div>
                    <a href="{{ route('workspaces.show', $workspace) }}"
                       class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back
                    </a>
                </div>

                <div class="card-body p-4">
                    @if($tags->isEmpty())
                        <div class="text-center py-5 text-body-secondary">
                            <i class="fas fa-tags fa-2x mb-3 d-block opacity-25"></i>
                            <p class="mb-1">No tags yet.</p>
                            <p class="small mb-0">Add tags when creating or editing a page.</p>
                        </div>
                    @else
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($tags as $tag)
                                <a href="{{ route('workspaces.tags.show', [$workspace, $tag->getTranslation('slug', 'en')]) }}"
                                   class="text-decoration-none">
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle d-inline-flex align-items-center gap-1 px-3 py-2"
                                          style="font-size: 0.85rem;">
                                        <i class="fas fa-hashtag me-1" style="font-size: 0.7rem;"></i>
                                        {{ $tag->getTranslation('name', 'en') }}
                                        <span class="badge bg-secondary text-white rounded-pill ms-1"
                                              style="font-size: 0.65rem; min-width: 1.25rem;">
                                            {{ $tag->pages_count }}
                                        </span>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
