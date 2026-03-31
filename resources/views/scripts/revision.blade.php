<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column gap-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('workspaces.show', $workspace) }}">{{ $workspace->name }}</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('workspaces.scripts.show', [$workspace, $script]) }}">{{ $script->title }}</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Revision v{{ $revision->revision_number }}</li>
                </ol>
            </nav>

            @include('workspaces._header_actions')
        </div>
    </x-slot>

    <style>
        .screenplay-sheet {
            background: linear-gradient(180deg, #fffef8 0%, #fff 100%);
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 1rem;
            padding: 2rem 1.5rem;
            font-family: "Courier New", Courier, monospace;
            font-size: 12pt;
            line-height: 1.5;
        }
        .screenplay-block { margin-bottom: 0.7rem; white-space: pre-wrap; }
        .screenplay-character { margin-left: 22%; width: 40%; text-transform: uppercase; }
        .screenplay-parenthetical { margin-left: 18%; width: 30%; }
        .screenplay-dialogue { margin-left: 14%; width: 52%; }
        .screenplay-transition { margin-left: auto; width: 34%; text-align: right; text-transform: uppercase; }
        .screenplay-scene-heading { text-transform: uppercase; }
    </style>

    <div class="py-4">
        <div class="container" style="max-width: 64rem;">
            <div class="card shadow-sm">
                <div class="card-header py-3 d-flex align-items-center justify-content-between">
                    <div>
                        <h1 class="h5 mb-0">Revision v{{ $revision->revision_number }}</h1>
                        <p class="small text-body-secondary mb-0">
                            {{ $revision->user?->name ?? 'Unknown' }} • {{ $revision->created_at->diffForHumans() }}
                        </p>
                    </div>
                    <a href="{{ route('workspaces.scripts.history', [$workspace, $script]) }}" class="btn btn-sm btn-outline-secondary">Back to History</a>
                </div>
                <div class="card-body p-4">
                    <div class="screenplay-sheet">
                        {!! $renderedScript !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
