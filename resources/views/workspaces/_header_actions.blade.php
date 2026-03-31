@php
    $wrapperClass = $wrapperClass ?? 'd-flex flex-wrap gap-2';
@endphp

<div class="{{ $wrapperClass }}">
    <a href="{{ route('workspaces.pages.create', $workspace) }}" class="btn btn-primary btn-sm">
        <i class="fas fa-plus me-1"></i> {{ __('New Page') }}
    </a>
    <a href="{{ route('workspaces.scripts.create', $workspace) }}" class="btn btn-warning btn-sm">
        <i class="fas fa-scroll me-1"></i> {{ __('New Script') }}
    </a>
    <a href="{{ route('workspaces.diagrams.create', $workspace) }}" class="btn btn-success btn-sm">
        <i class="fas fa-plus me-1"></i> {{ __('New Diagram') }}
    </a>
    @can('update', $workspace)
        <a href="{{ route('workspaces.analytics', $workspace) }}"
           class="btn btn-outline-secondary btn-sm" title="Analytics">
            <i class="fas fa-chart-bar"></i>
        </a>
        <a href="{{ route('workspaces.edit', $workspace) }}"
           class="btn btn-outline-secondary btn-sm" title="Workspace Settings">
            <i class="fas fa-cog"></i>
        </a>
    @endcan
</div>
