<x-app-layout>
    <x-slot name="header">
        <h2 class="h5 mb-0">{{ __('Search') }}</h2>
    </x-slot>

    <div class="py-4">
        <div class="container" style="max-width: 56rem;">

            {{-- ── Search form ──────────────────────────────────────────────── --}}
            <form method="GET" action="{{ route('search') }}" id="search-form" class="mb-3">

                {{-- Main search bar --}}
                <div class="row g-2 align-items-center mb-2">
                    <div class="col">
                        <div class="input-group">
                            <span class="input-group-text bg-body border-end-0">
                                <i class="fas fa-search text-body-secondary"></i>
                            </span>
                            <input type="search" name="q" id="search-q"
                                   value="{{ $query }}"
                                   placeholder="{{ __('Search pages and diagrams…') }}"
                                   autofocus
                                   class="form-control border-start-0 ps-0" />
                        </div>
                    </div>
                    <div class="col-sm-auto">
                        <x-button type="submit" class="w-100">Search</x-button>
                    </div>
                </div>

                {{-- Filter row --}}
                <div class="d-flex flex-wrap gap-2 align-items-center">

                    {{-- Workspace --}}
                    @if($accessibleWorkspaces->count() > 1)
                    <select name="workspace" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                        <option value="">All Workspaces</option>
                        @foreach($accessibleWorkspaces as $ws)
                            <option value="{{ $ws->id }}" {{ $workspaceId === $ws->id ? 'selected' : '' }}>
                                {{ $ws->name }}
                            </option>
                        @endforeach
                    </select>
                    @endif

                    {{-- Status --}}
                    <select name="status" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                        <option value="">Any Status</option>
                        <option value="published" {{ $filterStatus === 'published' ? 'selected' : '' }}>Published</option>
                        <option value="draft"     {{ $filterStatus === 'draft'     ? 'selected' : '' }}>Draft</option>
                        <option value="archived"  {{ $filterStatus === 'archived'  ? 'selected' : '' }}>Archived</option>
                    </select>

                    {{-- Tag --}}
                    <div class="position-relative">
                        <input type="text" name="tag" id="filter-tag"
                               value="{{ $filterTag }}"
                               placeholder="Tag…"
                               list="tag-suggestions"
                               class="form-control form-control-sm"
                               style="width: 9rem;"
                               autocomplete="off" />
                        <datalist id="tag-suggestions">
                            @foreach($accessibleTags as $tagName)
                                <option value="{{ $tagName }}">
                            @endforeach
                        </datalist>
                    </div>

                    {{-- Author --}}
                    <input type="text" name="author"
                           value="{{ $filterAuthor }}"
                           placeholder="Author…"
                           class="form-control form-control-sm"
                           style="width: 9rem;" />

                    {{-- Date range --}}
                    <input type="date" name="date_from"
                           value="{{ $filterDateFrom }}"
                           title="Updated after"
                           class="form-control form-control-sm"
                           style="width: 9rem;" />
                    <span class="small text-body-secondary">–</span>
                    <input type="date" name="date_to"
                           value="{{ $filterDateTo }}"
                           title="Updated before"
                           class="form-control form-control-sm"
                           style="width: 9rem;" />

                    {{-- Scope toggle --}}
                    <div class="btn-group btn-group-sm" role="group" aria-label="Search scope">
                        <input type="radio" class="btn-check" name="scope" id="scope-anywhere"
                               value="anywhere" autocomplete="off"
                               {{ $filterScope !== 'title' ? 'checked' : '' }}>
                        <label class="btn btn-outline-secondary" for="scope-anywhere"
                               title="Search in title and content">
                            <i class="fas fa-align-left me-1"></i> Anywhere
                        </label>

                        <input type="radio" class="btn-check" name="scope" id="scope-title"
                               value="title" autocomplete="off"
                               {{ $filterScope === 'title' ? 'checked' : '' }}>
                        <label class="btn btn-outline-secondary" for="scope-title"
                               title="Search in page title only">
                            <i class="fas fa-heading me-1"></i> Title only
                        </label>
                    </div>

                    {{-- Clear filters (only when any filter is active) --}}
                    @php
                        $hasFilters = $filterStatus || $filterTag || $filterAuthor || $filterDateFrom || $filterDateTo || $filterScope === 'title';
                    @endphp
                    @if($hasFilters)
                        <a href="{{ route('search', ['q' => $query, 'workspace' => $workspaceId]) }}"
                           class="btn btn-sm btn-link text-body-secondary text-decoration-none p-0">
                            <i class="fas fa-times me-1"></i> Clear filters
                        </a>
                    @endif
                </div>
            </form>

            {{-- ── Active filter pills ─────────────────────────────────────── --}}
            @if($query !== '' && $hasFilters)
                <div class="d-flex flex-wrap gap-1 mb-3">
                    @if($filterStatus)
                        <span class="badge bg-primary-subtle text-primary-emphasis">
                            Status: {{ ucfirst($filterStatus) }}
                        </span>
                    @endif
                    @if($filterTag)
                        <span class="badge bg-primary-subtle text-primary-emphasis">
                            Tag: {{ $filterTag }}
                        </span>
                    @endif
                    @if($filterAuthor)
                        <span class="badge bg-primary-subtle text-primary-emphasis">
                            Author: {{ $filterAuthor }}
                        </span>
                    @endif
                    @if($filterDateFrom)
                        <span class="badge bg-primary-subtle text-primary-emphasis">
                            From: {{ $filterDateFrom }}
                        </span>
                    @endif
                    @if($filterDateTo)
                        <span class="badge bg-primary-subtle text-primary-emphasis">
                            To: {{ $filterDateTo }}
                        </span>
                    @endif
                    @if($filterScope === 'title')
                        <span class="badge bg-secondary-subtle text-secondary-emphasis">
                            Title only
                        </span>
                    @endif
                </div>
            @endif

            {{-- ── Results ──────────────────────────────────────────────────── --}}
            @if($query === '')
                <div class="text-center py-5 text-body-secondary">
                    <i class="fas fa-search fa-2x mb-3 d-block opacity-25"></i>
                    <p class="mb-0">Enter a search term above to find pages and diagrams.</p>
                </div>

            @elseif($pages->isEmpty() && $diagrams->isEmpty())
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5 text-body-secondary">
                        <i class="fas fa-file fa-2x mb-3 d-block opacity-25"></i>
                        <p class="mb-1">No results found for <strong>{{ $query }}</strong>.</p>
                        @if($hasFilters)
                            <p class="small mb-0">
                                <a href="{{ route('search', ['q' => $query, 'workspace' => $workspaceId]) }}">
                                    Remove filters and try again
                                </a>
                            </p>
                        @elseif($workspaceId)
                            <p class="small mb-0">
                                <a href="{{ route('search', ['q' => $query]) }}">Search all workspaces?</a>
                            </p>
                        @endif
                    </div>
                </div>

            @else
                {{-- Summary bar --}}
                @php $total = $pages->count() + $diagrams->count(); @endphp
                <p class="small text-body-secondary mb-3">
                    {{ $total }} {{ Str::plural('result', $total) }} for
                    <strong>{{ $query }}</strong>
                    @if($workspaceId)
                        in <strong>{{ $accessibleWorkspaces->firstWhere('id', $workspaceId)?->name }}</strong>
                        &mdash; <a href="{{ route('search', ['q' => $query]) }}">Search all</a>
                    @endif
                </p>

                {{-- Pages --}}
                @if($pages->isNotEmpty())
                <div class="mb-4">
                    <h3 class="small fw-semibold text-uppercase text-body-secondary mb-3">
                        <i class="fas fa-file-alt me-1"></i>
                        Pages &mdash; {{ $pages->count() }} {{ Str::plural('result', $pages->count()) }}
                    </h3>
                    <div class="d-flex flex-column gap-3">
                        @foreach($pages as $page)
                        <div class="card shadow-sm">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-start justify-content-between gap-3">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <a href="{{ route('workspaces.pages.show', [$page->workspace, $page]) }}"
                                           class="fw-semibold text-primary text-decoration-none">
                                            {!! preg_replace('/(' . preg_quote($query, '/') . ')/iu', '<mark>$1</mark>', e($page->title)) !!}
                                        </a>
                                        <div class="small text-body-secondary mt-1 d-flex flex-wrap gap-2 align-items-center">
                                            <a href="{{ route('workspaces.show', $page->workspace) }}"
                                               class="text-decoration-none d-flex align-items-center gap-1">
                                                <span class="rounded-2 d-inline-block"
                                                      style="width:8px;height:8px;background:{{ $page->workspace->color ?? '#6366f1' }};"></span>
                                                {{ $page->workspace->name }}
                                            </a>
                                            <span>&middot; {{ $page->updated_at->diffForHumans() }}</span>
                                            @if($page->author)
                                                <span>&middot; {{ $page->author->name }}</span>
                                            @endif
                                        </div>
                                        @if($page->snippet)
                                            <p class="small text-body-secondary mt-2 mb-0 lh-sm">
                                                {!! $page->snippet !!}
                                            </p>
                                        @endif
                                    </div>
                                    <span class="badge flex-shrink-0
                                        {{ $page->status === 'published' ? 'bg-success-subtle text-success-emphasis' : ($page->status === 'archived' ? 'bg-secondary-subtle text-secondary-emphasis' : 'bg-warning-subtle text-warning-emphasis') }}">
                                        {{ ucfirst($page->status) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Diagrams --}}
                @if($diagrams->isNotEmpty())
                <div>
                    <h3 class="small fw-semibold text-uppercase text-body-secondary mb-3">
                        <i class="fas fa-project-diagram me-1"></i>
                        Diagrams &mdash; {{ $diagrams->count() }} {{ Str::plural('result', $diagrams->count()) }}
                    </h3>
                    <div class="d-flex flex-column gap-3">
                        @foreach($diagrams as $diagram)
                        <div class="card shadow-sm">
                            <div class="card-body p-3">
                                <a href="{{ route('workspaces.diagrams.show', [$diagram->workspace, $diagram]) }}"
                                   class="fw-semibold text-success text-decoration-none">
                                    {!! preg_replace('/(' . preg_quote($query, '/') . ')/iu', '<mark>$1</mark>', e($diagram->title)) !!}
                                </a>
                                <div class="small text-body-secondary mt-1 d-flex flex-wrap gap-2">
                                    <a href="{{ route('workspaces.show', $diagram->workspace) }}" class="text-decoration-none">
                                        {{ $diagram->workspace->name }}
                                    </a>
                                    <span>&middot; {{ $diagram->updated_at->diffForHumans() }}</span>
                                    <span>&middot; {{ ucfirst($diagram->diagram_type) }}</span>
                                </div>
                                @if($diagram->description)
                                    <p class="small text-body-secondary mt-1 mb-0">
                                        {!! preg_replace('/(' . preg_quote($query, '/') . ')/iu', '<mark>$1</mark>', e($diagram->description)) !!}
                                    </p>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            @endif

        </div>
    </div>

    @push('scripts')
    <script>
        // Auto-submit filter fields on change (tag/author/date resubmit on blur for UX)
        document.querySelectorAll('#search-form input[type="date"]').forEach(el => {
            el.addEventListener('change', () => el.closest('form').submit());
        });
        // Scope radio: auto-submit on change
        document.querySelectorAll('#search-form input[name="scope"]').forEach(el => {
            el.addEventListener('change', () => el.closest('form').submit());
        });
    </script>
    @endpush
</x-app-layout>
