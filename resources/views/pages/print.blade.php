<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page->title }} — {{ $workspace->name }}</title>
    <style>
        /* ── Reset & base ───────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 12pt;
            line-height: 1.65;
            color: #111;
            background: #fff;
            padding: 2cm 2.5cm;
            max-width: 820px;
            margin: 0 auto;
        }

        /* ── Header ─────────────────────────────────────────────────── */
        .print-header {
            border-bottom: 2px solid #111;
            padding-bottom: 0.75rem;
            margin-bottom: 1.5rem;
        }
        .print-workspace {
            font-size: 9pt;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #555;
            margin-bottom: 0.25rem;
        }
        .print-title {
            font-size: 22pt;
            font-weight: bold;
            line-height: 1.2;
        }
        .print-meta {
            font-size: 9pt;
            color: #555;
            margin-top: 0.5rem;
        }
        .print-breadcrumb {
            font-size: 9pt;
            color: #777;
            margin-top: 0.25rem;
        }
        .print-breadcrumb span + span::before {
            content: ' › ';
        }

        /* ── Content ─────────────────────────────────────────────────── */
        .print-content { margin-top: 1.5rem; }

        .print-content h1,
        .print-content h2,
        .print-content h3,
        .print-content h4,
        .print-content h5,
        .print-content h6 {
            font-family: Arial, Helvetica, sans-serif;
            margin-top: 1.5em;
            margin-bottom: 0.4em;
            line-height: 1.25;
        }
        .print-content h1 { font-size: 18pt; border-bottom: 1px solid #ccc; padding-bottom: 0.2em; }
        .print-content h2 { font-size: 15pt; }
        .print-content h3 { font-size: 13pt; }
        .print-content h4 { font-size: 11pt; }

        .print-content p { margin-bottom: 0.75em; }

        .print-content ul,
        .print-content ol { margin: 0.5em 0 0.75em 1.5em; }
        .print-content li { margin-bottom: 0.25em; }

        .print-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 1em 0;
            font-size: 10pt;
        }
        .print-content th,
        .print-content td {
            border: 1px solid #ccc;
            padding: 0.35em 0.6em;
            text-align: left;
        }
        .print-content th { background: #f0f0f0; font-weight: bold; }

        .print-content blockquote {
            border-left: 3px solid #aaa;
            padding: 0.4em 0.75em;
            color: #444;
            margin: 0.75em 0;
            font-style: italic;
        }

        .print-content pre {
            background: #f5f5f5;
            padding: 0.75em 1em;
            border-radius: 4px;
            font-size: 9pt;
            overflow: hidden;
            white-space: pre-wrap;
            word-break: break-all;
            margin: 0.75em 0;
        }
        .print-content code {
            font-family: 'Courier New', monospace;
            font-size: 9pt;
            background: #f5f5f5;
            padding: 0.1em 0.3em;
            border-radius: 2px;
        }
        .print-content pre code { background: none; padding: 0; }

        .print-content img { max-width: 100%; height: auto; }

        .print-content a { color: #111; text-decoration: underline; }
        .print-content a[href]::after {
            content: ' (' attr(href) ')';
            font-size: 8pt;
            color: #555;
        }

        /* ── Footer ─────────────────────────────────────────────────── */
        .print-footer {
            margin-top: 2.5rem;
            padding-top: 0.75rem;
            border-top: 1px solid #ccc;
            font-size: 8pt;
            color: #888;
            display: flex;
            justify-content: space-between;
        }

        /* ── Screen-only controls ────────────────────────────────────── */
        @media screen {
            .print-controls {
                position: fixed;
                top: 1rem;
                right: 1rem;
                display: flex;
                gap: 0.5rem;
                z-index: 100;
            }
            .print-controls button,
            .print-controls a {
                padding: 0.4rem 0.9rem;
                border-radius: 4px;
                font-size: 0.85rem;
                cursor: pointer;
                text-decoration: none;
                font-family: Arial, sans-serif;
            }
            .btn-print {
                background: #111;
                color: #fff;
                border: none;
            }
            .btn-back {
                background: #fff;
                color: #111;
                border: 1px solid #ccc;
            }
        }
        @media print {
            .print-controls { display: none !important; }
            body { padding: 0; }
            .print-content a[href^="http"]::after { content: none; }
        }
    </style>
</head>
<body>

    {{-- Screen-only controls --}}
    <div class="print-controls">
        <a href="{{ route('workspaces.pages.show', [$workspace, $page]) }}" class="btn-back">
            ← Back
        </a>
        <a href="{{ route('workspaces.pages.export.markdown', [$workspace, $page]) }}" class="btn-back">
            ↓ .md
        </a>
        <button class="btn-print" onclick="window.print()">
            🖨 Print / Save PDF
        </button>
    </div>

    {{-- Page header --}}
    <header class="print-header">
        <div class="print-workspace">{{ $workspace->name }}</div>
        @if($breadcrumbs->count() > 1)
            <div class="print-breadcrumb">
                @foreach($breadcrumbs->slice(0, -1) as $crumb)
                    <span>{{ $crumb->title }}</span>
                @endforeach
            </div>
        @endif
        <h1 class="print-title">{{ $page->title }}</h1>
        <div class="print-meta">
            @if($page->author) By {{ $page->author->name }} · @endif
            Last updated {{ $page->updated_at->format('F j, Y') }}
            @if($page->status !== 'published')
                · <em>{{ ucfirst($page->status) }}</em>
            @endif
        </div>
    </header>

    {{-- Page content --}}
    <main class="print-content">
        {!! $page->content !!}
    </main>

    {{-- Footer --}}
    <footer class="print-footer">
        <span>{{ $workspace->name }} — {{ $page->title }}</span>
        <span>Exported {{ now()->format('Y-m-d') }}</span>
    </footer>

</body>
</html>
