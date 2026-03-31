@php
    $variant ??= 'web';
    $isNative = in_array($diagram->diagram_type, ['mermaid', 'mindmap', 'flowchart'], true);
@endphp

<figure class="codex-diagram-embed my-4" data-diagram-id="{{ $diagram->id }}">
    <div class="codex-diagram-embed__header">
        <div class="min-w-0">
            <div class="codex-diagram-embed__eyebrow">Embedded Diagram</div>
            <figcaption class="codex-diagram-embed__title">{{ $diagram->title }}</figcaption>
            @if($diagram->description)
                <p class="codex-diagram-embed__description">{{ $diagram->description }}</p>
            @endif
        </div>

        <div class="codex-diagram-embed__actions">
            <span class="badge {{ $isNative ? 'bg-success-subtle text-success-emphasis' : 'bg-primary-subtle text-primary-emphasis' }}">
                {{ $diagram->diagram_type === 'drawio' ? 'draw.io' : 'Mermaid' }}
            </span>
            <a href="{{ route('workspaces.diagrams.show', [$workspace, $diagram]) }}"
               class="btn btn-sm btn-outline-secondary flex-shrink-0">
                Open
            </a>
        </div>
    </div>

    @include('diagrams._surface', [
        'diagram' => $diagram,
        'drawioUrl' => $drawioUrl,
        'variant' => $variant,
        'surfaceClass' => 'codex-diagram-embed__surface',
    ])
</figure>
