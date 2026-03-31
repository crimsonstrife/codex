<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $script->title }} — {{ $workspace->name }}</title>
    <style>
        body {
            background: #fff;
            color: #111;
            font-family: "Courier New", Courier, monospace;
            font-size: 12pt;
            line-height: 1.5;
            margin: 0 auto;
            max-width: 8.5in;
            padding: 1in 0.8in;
        }
        .print-header { margin-bottom: 1.5rem; }
        .print-header h1 { margin: 0 0 0.3rem; font-size: 18pt; }
        .print-meta { color: #555; font-size: 10pt; }
        .screenplay-block { margin-bottom: 0.7rem; white-space: pre-wrap; }
        .screenplay-scene-heading,
        .screenplay-character,
        .screenplay-transition { text-transform: uppercase; }
        .screenplay-character { margin-left: 2.2in; width: 3.5in; }
        .screenplay-parenthetical { margin-left: 1.8in; width: 2.5in; }
        .screenplay-dialogue { margin-left: 1.5in; width: 3.7in; }
        .screenplay-transition { margin-left: auto; width: 2.2in; text-align: right; }
        .print-controls {
            position: fixed;
            top: 1rem;
            right: 1rem;
            display: flex;
            gap: 0.5rem;
        }
        .print-controls a,
        .print-controls button {
            border: 1px solid #ddd;
            background: #fff;
            color: #111;
            border-radius: 999px;
            padding: 0.45rem 0.85rem;
            text-decoration: none;
            font-size: 0.9rem;
            cursor: pointer;
        }
        @media print {
            .print-controls { display: none; }
            body { padding: 0.5in; }
        }
    </style>
</head>
<body>
    <div class="print-controls">
        <a href="{{ route('workspaces.scripts.show', [$workspace, $script]) }}">Back</a>
        <a href="{{ route('workspaces.scripts.export.fountain', [$workspace, $script]) }}">Fountain</a>
        <button onclick="window.print()">Print / Save PDF</button>
    </div>

    <header class="print-header">
        <h1>{{ $script->title }}</h1>
        <div class="print-meta">
            {{ $workspace->name }} • {{ $script->author?->name }} • {{ ucfirst($script->status) }}
        </div>
        @if($script->logline)
            <div class="print-meta">{{ $script->logline }}</div>
        @endif
    </header>

    <main>
        {!! $renderedScript !!}
    </main>
</body>
</html>
