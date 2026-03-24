<x-app-layout>
    <x-slot name="header">
        <div class="d-flex align-items-center justify-content-between">
            <h2 class="h5 mb-0">{{ __('Workspaces') }}</h2>
            @can('create', \App\Models\Workspace::class)
            <a href="{{ route('workspaces.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i> {{ __('New Workspace') }}
            </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container">
            <div class="row g-4">
                @forelse($workspaces as $workspace)
                <div class="col-md-6 col-lg-4">
                    <a href="{{ route('workspaces.show', $workspace) }}" class="text-decoration-none">
                        <div class="card h-100 shadow-sm card-hover">
                            <div class="card-body">
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <div class="d-flex align-items-center justify-content-center rounded-3 text-white fw-bold"
                                         style="width:2.5rem;height:2.5rem;background-color:{{ $workspace->color ?? '#6366f1' }};font-size:1.1rem;">
                                        {{ strtoupper(substr($workspace->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <h5 class="mb-0 card-title">{{ $workspace->name }}</h5>
                                        <span class="text-body-secondary small">{{ $workspace->pages_count ?? 0 }} pages</span>
                                    </div>
                                </div>
                                @if($workspace->description)
                                <p class="card-text text-body-secondary small">{{ Str::limit($workspace->description, 100) }}</p>
                                @endif
                                @if($workspace->forge_project_key)
                                <span class="badge bg-primary-subtle text-primary-emphasis">
                                    <i class="fas fa-link me-1"></i> Forge: {{ $workspace->forge_project_key }}
                                </span>
                                @endif
                            </div>
                        </div>
                    </a>
                </div>
                @empty
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body text-center py-5 text-body-secondary">
                            <i class="fas fa-folder-open fa-3x mb-3 opacity-50"></i>
                            <p class="mb-3">No workspaces yet. Create your first one!</p>
                            @can('create', \App\Models\Workspace::class)
                            <a href="{{ route('workspaces.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-1"></i> {{ __('New Workspace') }}
                            </a>
                            @endcan
                        </div>
                    </div>
                </div>
                @endforelse
            </div>
            <div class="mt-4">{{ $workspaces->links() }}</div>
        </div>
    </div>
</x-app-layout>
