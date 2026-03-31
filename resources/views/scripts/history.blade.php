<x-app-layout>
    <x-slot name="header">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('workspaces.show', $workspace) }}">{{ $workspace->name }}</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('workspaces.scripts.show', [$workspace, $script]) }}">{{ $script->title }}</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">History</li>
            </ol>
        </nav>
    </x-slot>

    <div class="py-4">
        <div class="container" style="max-width: 64rem;">
            <div class="card shadow-sm">
                <div class="card-header py-3">
                    <h1 class="h5 mb-0">Revision History</h1>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Revision</th>
                                <th>Updated By</th>
                                <th>Summary</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($revisions as $revision)
                                <tr>
                                    <td>
                                        <a href="{{ route('workspaces.scripts.revisions.show', [$workspace, $script, $revision]) }}" class="text-decoration-none fw-medium">
                                            v{{ $revision->revision_number }}
                                        </a>
                                    </td>
                                    <td>{{ $revision->user?->name ?? 'Unknown' }}</td>
                                    <td>{{ $revision->change_summary ?: 'No summary' }}</td>
                                    <td class="text-body-secondary">{{ $revision->created_at->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    {{ $revisions->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
