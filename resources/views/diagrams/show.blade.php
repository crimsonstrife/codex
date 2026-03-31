<x-app-layout>
    <x-slot name="header">
        <div class="d-flex align-items-center justify-content-between">
            @php $embedToken = '{{diagram:'.$diagram->id.'}}'; @endphp
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
            <div class="d-flex gap-2">
                <button type="button"
                        id="copy-diagram-embed"
                        class="btn btn-sm btn-outline-secondary"
                        data-embed-token="{{ $embedToken }}">
                    <i class="fas fa-copy me-1"></i> Copy Embed
                </button>
                @can('update', $diagram)
                <a href="{{ route('workspaces.diagrams.edit', [$workspace, $diagram]) }}"
                   class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-edit me-1"></i> {{ __('Edit Diagram') }}
                </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container">
            <div class="card shadow-sm">
                <div class="card-body p-4 {{ in_array($diagram->diagram_type, ['mermaid', 'mindmap', 'flowchart']) ? 'text-center' : '' }}">
                    @include('diagrams._surface', [
                        'diagram' => $diagram,
                        'drawioUrl' => $drawioUrl,
                        'variant' => 'web',
                        'surfaceClass' => 'w-100',
                        'surfaceStyle' => $diagram->diagram_type === 'drawio' ? 'min-height: 75vh;' : null,
                    ])
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const copyButton = document.getElementById('copy-diagram-embed');
            if (!copyButton) return;

            copyButton.addEventListener('click', async function () {
                const token = copyButton.dataset.embedToken ?? '';
                if (!token) return;

                try {
                    await navigator.clipboard.writeText(token);
                    copyButton.innerHTML = '<i class="fas fa-check me-1"></i> Copied';
                    setTimeout(() => {
                        copyButton.innerHTML = '<i class="fas fa-copy me-1"></i> Copy Embed';
                    }, 1800);
                } catch (_) {
                    window.prompt('Copy embed token', token);
                }
            });
        });
    </script>
</x-app-layout>
