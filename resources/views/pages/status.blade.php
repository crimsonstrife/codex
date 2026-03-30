<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts.partials.head', [
        'title' => 'System Status',
        'viteEntries' => [
            'resources/css/app.css',
            'resources/js/app.js',
        ],
    ])

    {!! $healthAssets !!}
</head>
<body class="min-h-screen bg-gray-100 py-7 antialiased md:py-12 dark:bg-gray-900">
    @include('vendor.health.list', [
        'presentedResults' => $presentedResults,
        'lastRanLabel' => $lastRanLabel,
        'staleResults' => $staleResults,
    ])
</body>
</html>
