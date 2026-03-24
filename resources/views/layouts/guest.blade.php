<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts.partials.head', [
        'title' => config('app.name', 'Codex'),
        'viteEntries' => [
            'resources/css/app.css',
            'resources/js/app.js',
        ],
    ])
</head>
<body>
<div class="min-vh-100 d-flex flex-column bg-body">
    <main class="flex-grow-1">
        {{ $slot }}
    </main>

    {{-- Footer partial --}}
    @include('layouts.partials.footer')
</div>

@livewireScripts
</body>
</html>

