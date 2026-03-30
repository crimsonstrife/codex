<x-filament-panels::page>
    {!! $healthAssets !!}

    <div wire:poll.15s="loadResults">
        @include('vendor.health.list', [
            'presentedResults' => $presentedResults,
            'lastRanLabel' => $lastRanLabel,
            'staleResults' => $staleResults,
        ])
    </div>
</x-filament-panels::page>
