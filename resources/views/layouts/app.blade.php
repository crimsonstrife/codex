<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts.partials.head', [
        'title' => config('app.name', 'Codex'),
        'viteEntries' => [
            'resources/css/app.css',
            'resources/js/app.js',
            'resources/js/editor/tinymce-init.js',
        ],
    ])
</head>
<body>
<x-banner />

<div class="min-vh-100 d-flex flex-column bg-body">
    @livewire('navigation-menu')

    {{-- Header partial --}}
    @include('layouts.partials.header', ['header' => $header ?? null])

    <main class="flex-grow-1">
        {{ $slot }}
    </main>

    {{-- Footer partial --}}
    @include('layouts.partials.footer')
</div>

@stack('modals')
@livewireScripts
@stack('scripts')
</body>
</html>

