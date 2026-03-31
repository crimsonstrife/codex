@php
    $variant ??= 'web';
    $surfaceClass = trim(($surfaceClass ?? '').' codex-diagram-surface');
    $surfaceStyle = $surfaceStyle ?? null;
    $isMermaid = in_array($diagram->diagram_type, ['mermaid', 'mindmap', 'flowchart'], true);
    $drawioEmbedId = $drawioEmbedId ?? uniqid('codex-drawio-');
@endphp

@if($diagram->diagram_data)
    @if($variant === 'web')
        @if($isMermaid)
            <div class="{{ $surfaceClass }}" @if($surfaceStyle) style="{{ $surfaceStyle }}" @endif>
                <div class="mermaid">
                    {{ $diagram->diagram_data }}
                </div>
            </div>
        @else
            <div id="{{ $drawioEmbedId }}"
                 class="{{ $surfaceClass }} codex-drawio-embed"
                 data-codex-drawio="{!! e($diagram->diagram_data) !!}"
                 data-codex-drawio-url="{{ $drawioUrl }}"
                 @if($surfaceStyle) style="{{ $surfaceStyle }}" @endif></div>
        @endif
    @else
        <div class="{{ $surfaceClass }} codex-diagram-surface--static" @if($surfaceStyle) style="{{ $surfaceStyle }}" @endif>
            @if($diagram->thumbnail_url)
                <img src="{{ $diagram->thumbnail_url }}" alt="{{ $diagram->title }} preview" class="img-fluid rounded">
            @else
                <p class="mb-0">
                    Open this {{ $diagram->diagram_type === 'drawio' ? 'draw.io' : 'Mermaid' }} diagram in Codex to view it interactively.
                </p>
            @endif
        </div>
    @endif
@else
    <div class="{{ $surfaceClass }} codex-diagram-surface--empty" @if($surfaceStyle) style="{{ $surfaceStyle }}" @endif>
        No diagram content yet.
    </div>
@endif
