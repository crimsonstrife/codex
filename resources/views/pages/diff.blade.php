<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column gap-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('workspaces.show', $workspace) }}">{{ $workspace->name }}</a>
                    </li>
                    @foreach($breadcrumbs as $crumb)
                        <li class="breadcrumb-item">
                            <a href="{{ route('workspaces.pages.show', [$workspace, $crumb]) }}">{{ $crumb->title }}</a>
                        </li>
                    @endforeach
                    <li class="breadcrumb-item">
                        <a href="{{ route('workspaces.pages.history', [$workspace, $page]) }}">History</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Compare</li>
                </ol>
            </nav>

            @include('workspaces._header_actions')
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container" style="max-width: 64rem;">

            {{-- Header --}}
            <div class="card shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                        <div>
                            <h1 class="h4 mb-1">
                                <i class="fas fa-code-branch me-2 text-body-secondary"></i>Comparing revisions
                            </h1>
                            <p class="text-body-secondary small mb-0">{{ $page->title }}</p>
                        </div>
                        <a href="{{ route('workspaces.pages.history', [$workspace, $page]) }}"
                           class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-list me-1"></i> All revisions
                        </a>
                    </div>

                    {{-- From / To chips --}}
                    <div class="d-flex align-items-center gap-3 mt-3 flex-wrap">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-danger-subtle text-danger-emphasis">
                                v{{ $fromRevision->revision_number }}
                            </span>
                            <span class="small text-body-secondary">
                                @if($fromRevision->user) {{ $fromRevision->user->name }} &middot; @endif
                                {{ \Carbon\Carbon::parse($fromRevision->created_at)->diffForHumans() }}
                                @if($fromRevision->change_summary)
                                    &mdash; <em>{{ $fromRevision->change_summary }}</em>
                                @endif
                            </span>
                        </div>
                        <i class="fas fa-arrow-right text-body-tertiary"></i>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-success-subtle text-success-emphasis">
                                v{{ $toRevision->revision_number }}
                            </span>
                            <span class="small text-body-secondary">
                                @if($toRevision->user) {{ $toRevision->user->name }} &middot; @endif
                                {{ \Carbon\Carbon::parse($toRevision->created_at)->diffForHumans() }}
                                @if(!empty($toRevision->change_summary))
                                    &mdash; <em>{{ $toRevision->change_summary }}</em>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Diff output --}}
            <div class="card shadow-sm">
                <div class="card-header py-3 d-flex align-items-center gap-2">
                    <i class="fas fa-file-alt text-body-secondary"></i>
                    <span class="small fw-semibold text-uppercase text-body-secondary">Content diff</span>
                    <span class="ms-auto">
                        <span class="badge bg-danger-subtle text-danger-emphasis me-1">
                            <i class="fas fa-minus me-1"></i>removed
                        </span>
                        <span class="badge bg-success-subtle text-success-emphasis">
                            <i class="fas fa-plus me-1"></i>added
                        </span>
                    </span>
                </div>
                <div class="card-body p-0">
                    @if(blank($diffHtml) || $diffHtml === '')
                        <div class="p-4 text-center text-body-secondary">
                            <i class="fas fa-check-circle fa-2x mb-3 d-block opacity-25"></i>
                            <p class="small mb-0">No differences found between these revisions.</p>
                        </div>
                    @else
                        <div class="diff-wrapper overflow-auto">
                            {!! $diffHtml !!}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
        /* jfcherng/php-diff Inline renderer styles — Bootstrap-harmonised */
        .diff-wrapper { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 0.8rem; }
        .diff-wrapper table { width: 100%; border-collapse: collapse; }
        .diff-wrapper td, .diff-wrapper th { padding: 2px 12px; vertical-align: top; white-space: pre-wrap; word-break: break-word; }
        .diff-wrapper .ChangeInsert td { background: rgba(25, 135, 84, 0.1); }
        .diff-wrapper .ChangeInsert ins { background: rgba(25, 135, 84, 0.35); text-decoration: none; }
        .diff-wrapper .ChangeDelete td { background: rgba(220, 53, 69, 0.1); }
        .diff-wrapper .ChangeDelete del { background: rgba(220, 53, 69, 0.3); text-decoration: none; }
        .diff-wrapper .ChangeReplace td { background: rgba(255, 193, 7, 0.1); }
        .diff-wrapper .ChangeReplace ins { background: rgba(25, 135, 84, 0.3); text-decoration: none; }
        .diff-wrapper .ChangeReplace del { background: rgba(220, 53, 69, 0.25); text-decoration: none; }
        .diff-wrapper .Skipped td { background: var(--bs-tertiary-bg); color: var(--bs-secondary-color); text-align: center; font-style: italic; }
        .diff-wrapper .LineNum { width: 1%; color: var(--bs-secondary-color); user-select: none; text-align: right; border-right: 1px solid var(--bs-border-color); }
    </style>
    @endpush
</x-app-layout>
