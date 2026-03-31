<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column gap-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('workspaces.show', $workspace) }}">{{ $workspace->name }}</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Link Graph</li>
                </ol>
            </nav>

            @include('workspaces._header_actions')
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-fluid px-4">

            {{-- Legend + controls --}}
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <span class="small fw-semibold text-body-secondary">Status:</span>
                    @foreach(['Published' => ['bg-success-subtle text-success-emphasis'], 'Draft' => ['bg-warning-subtle text-warning-emphasis'], 'Archived' => ['bg-secondary-subtle text-secondary-emphasis']] as $label => [$cls])
                        <span class="badge {{ $cls }}">{{ $label }}</span>
                    @endforeach
                    <span class="text-body-secondary small ms-2">
                        {{ $nodes->count() }} pages &middot; {{ $edges->count() }} links
                    </span>
                </div>
                <div class="d-flex gap-2">
                    <button id="btn-fit" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-compress-arrows-alt me-1"></i>Fit
                    </button>
                    <a href="{{ route('workspaces.show', $workspace) }}"
                       class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Back
                    </a>
                </div>
            </div>

            {{-- Graph canvas --}}
            <div class="card shadow-sm">
                <div id="graph-container" style="height: calc(100vh - 14rem); width: 100%;"></div>
            </div>

            @if($nodes->isEmpty())
                <div class="text-center text-body-secondary py-5 small">
                    <i class="fas fa-project-diagram fa-2x mb-3 d-block opacity-25"></i>
                    No pages yet. <a href="{{ route('workspaces.pages.create', $workspace) }}">Create the first one.</a>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/vis-network@9.1.9/standalone/umd/vis-network.min.js"></script>
    <script>
    (function () {
        const container = document.getElementById('graph-container');
        if (!container) return;

        const rawNodes = @json($nodes);
        const rawEdges = @json($edges);

        if (!rawNodes.length) return;

        // Build DataSets
        const nodes = new vis.DataSet(rawNodes);
        const edges = new vis.DataSet(rawEdges);

        const options = {
            physics: {
                stabilization: { iterations: 150 },
                barnesHut: { gravitationalConstant: -8000, springLength: 120, springConstant: 0.04 },
            },
            edges: {
                color: { color: '#94a3b8', highlight: '#3b82f6' },
                width: 1.2,
                smooth: { type: 'continuous' },
                arrows: { to: { enabled: true, scaleFactor: 0.6 } },
            },
            nodes: {
                shape: 'box',
                borderWidth: 1.5,
                borderWidthSelected: 2.5,
                font: { size: 12 },
                margin: 8,
            },
            interaction: {
                hover: true,
                tooltipDelay: 150,
                navigationButtons: false,
                keyboard: true,
            },
            layout: {
                improvedLayout: true,
            },
        };

        const network = new vis.Network(container, { nodes, edges }, options);

        // Navigate to page on double-click
        network.on('doubleClick', function (params) {
            if (params.nodes.length) {
                const node = rawNodes.find(n => n.id === params.nodes[0]);
                if (node?.url) window.location.href = node.url;
            }
        });

        // Fit button
        document.getElementById('btn-fit')?.addEventListener('click', () => network.fit({ animation: true }));
    })();
    </script>
    @endpush
</x-app-layout>
