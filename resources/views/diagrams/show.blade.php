<x-app-layout>
    <x-slot name="header">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <a href="{{ route('workspaces.show', $workspace) }}" class="small text-primary text-decoration-none">
                    <i class="fas fa-arrow-left me-1"></i> {{ $workspace->name }}
                </a>
                <h2 class="h5 mb-0 mt-1">
                    {{ $diagram->title }}
                    <span class="badge ms-2 {{ in_array($diagram->diagram_type, ['mermaid','mindmap','flowchart']) ? 'bg-success-subtle text-success-emphasis' : 'bg-primary-subtle text-primary-emphasis' }}">
                        {{ $diagram->diagram_type === 'drawio' ? 'draw.io' : 'Mermaid (Native)' }}
                    </span>
                </h2>
            </div>
            @can('update', $diagram)
            <a href="{{ route('workspaces.diagrams.edit', [$workspace, $diagram]) }}"
               class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-edit me-1"></i> {{ __('Edit Diagram') }}
            </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container">
            @if($diagram->diagram_data)

                @if(in_array($diagram->diagram_type, ['mermaid', 'mindmap', 'flowchart']))
                <div class="card shadow-sm">
                    <div class="card-body p-4 text-center">
                        <div class="mermaid">
                            {{ $diagram->diagram_data }}
                        </div>
                    </div>
                </div>

                @else
                <div class="card shadow-sm" style="height: 75vh;">
                    <div id="diagram-container" class="w-100 h-100" data-diagram="{{ htmlspecialchars($diagram->diagram_data) }}"></div>
                </div>
                @endif

            @else
            <div class="card shadow-sm">
                <div class="card-body text-center py-5 text-body-secondary">
                    <i class="fas fa-project-diagram fa-3x mb-3 opacity-50"></i>
                    <p>No diagram content yet.</p>
                    @can('update', $diagram)
                    <a href="{{ route('workspaces.diagrams.edit', [$workspace, $diagram]) }}"
                       class="btn btn-primary btn-sm">
                        Open Editor <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                    @endcan
                </div>
            </div>
            @endif
        </div>
    </div>

    @if($diagram->diagram_type === 'drawio' && $diagram->diagram_data)
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const container = document.getElementById('diagram-container');
            if (!container) return;
            const xml = container.dataset.diagram;
            if (!xml) return;
            const iframe = document.createElement('iframe');
            iframe.style.width = '100%';
            iframe.style.height = '100%';
            iframe.style.border = 'none';
            iframe.src = `{{ config('codex.diagrams.drawio_url', 'https://embed.diagrams.net') }}?embed=1&spin=1&xml=${encodeURIComponent(xml)}&toolbar=0&lightbox=1`;
            container.appendChild(iframe);
        });
    </script>
    @endif
</x-app-layout>
