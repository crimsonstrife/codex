<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column gap-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('workspaces.show', $workspace) }}">{{ $workspace->name }}</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Page Templates</li>
                </ol>
            </nav>

            @include('workspaces._header_actions')
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container" style="max-width: 52rem;">

            @if(session('status') === 'template-saved')
                <div class="alert alert-success alert-dismissible mb-4 py-2" role="alert">
                    <i class="fas fa-check-circle me-1"></i> Template saved successfully.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('status') === 'template-deleted')
                <div class="alert alert-info alert-dismissible mb-4 py-2" role="alert">
                    <i class="fas fa-trash-alt me-1"></i> Template deleted.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h1 class="h4 mb-1">Page Templates</h1>
                    <p class="text-body-secondary small mb-0">
                        Templates pre-fill the editor when creating a new page.
                        Save any page as a template using the <i class="fas fa-file-export mx-1"></i> button on the page view.
                    </p>
                </div>
                <a href="{{ route('workspaces.pages.create', $workspace) }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i> New Page
                </a>
            </div>

            @if($templates->isEmpty())
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5 text-body-secondary">
                        <i class="fas fa-file-alt fa-2x mb-3 d-block opacity-25"></i>
                        <p class="mb-1 fw-semibold">No templates yet</p>
                        <p class="small mb-0">
                            Open any page and click <strong>Save as Template</strong> to add it here.
                        </p>
                    </div>
                </div>
            @else
                {{-- System templates --}}
                @php $systemTemplates = $templates->where('is_system', true); @endphp
                @if($systemTemplates->isNotEmpty())
                    <h2 class="h6 fw-semibold text-uppercase text-body-secondary mb-3">
                        <i class="fas fa-cube me-1"></i> System Templates
                    </h2>
                    <div class="row g-3 mb-4">
                        @foreach($systemTemplates as $tmpl)
                            <div class="col-md-4">
                                <div class="card h-100 shadow-sm border">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-start gap-2 mb-1">
                                            <i class="fas fa-file-alt text-primary mt-1 flex-shrink-0"></i>
                                            <div>
                                                <div class="fw-medium small">{{ $tmpl->name }}</div>
                                                @if($tmpl->description)
                                                    <div class="text-body-secondary" style="font-size:.75rem;">
                                                        {{ $tmpl->description }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-1 mt-2">
                                            <span class="badge bg-secondary-subtle text-secondary-emphasis"
                                                  style="font-size:.65rem;">
                                                {{ strtoupper($tmpl->content_type) }}
                                            </span>
                                            <span class="badge bg-info-subtle text-info-emphasis"
                                                  style="font-size:.65rem;">System</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Workspace templates --}}
                @php $workspaceTemplates = $templates->where('is_system', false); @endphp
                @if($workspaceTemplates->isNotEmpty())
                    <h2 class="h6 fw-semibold text-uppercase text-body-secondary mb-3">
                        <i class="fas fa-folder me-1"></i> Workspace Templates
                    </h2>
                    <div class="d-flex flex-column gap-3">
                        @foreach($workspaceTemplates as $tmpl)
                            <div class="card shadow-sm">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <i class="fas fa-file-alt text-primary fa-lg flex-shrink-0"></i>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <div class="fw-medium">{{ $tmpl->name }}</div>
                                        @if($tmpl->description)
                                            <div class="text-body-secondary small">{{ $tmpl->description }}</div>
                                        @endif
                                        <div class="d-flex align-items-center gap-2 mt-1">
                                            <span class="badge bg-secondary-subtle text-secondary-emphasis"
                                                  style="font-size:.65rem;">{{ strtoupper($tmpl->content_type) }}</span>
                                            @if($tmpl->creator)
                                                <span class="text-body-secondary" style="font-size:.72rem;">
                                                    Saved by {{ $tmpl->creator->name }} &middot; {{ $tmpl->created_at->diffForHumans() }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    @can('update', $workspace)
                                        <form method="POST"
                                              action="{{ route('workspaces.templates.destroy', [$workspace, $tmpl]) }}"
                                              onsubmit="return confirm('Delete this template?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                    title="Delete template">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </div>
                        @endforeach
                    </div>
                @elseif($systemTemplates->isEmpty())
                    <div class="card shadow-sm">
                        <div class="card-body text-center py-5 text-body-secondary">
                            <i class="fas fa-file-alt fa-2x mb-3 d-block opacity-25"></i>
                            <p class="mb-0 small">No workspace templates yet.</p>
                        </div>
                    </div>
                @endif
            @endif

        </div>
    </div>
</x-app-layout>
