<footer class="bg-body border-top mt-auto py-4">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <x-application-logo style="height: 1.5rem;" />
                <span class="text-body-secondary small">{{ config('app.name', 'Codex') }}</span>
            </div>
            <div class="text-body-secondary small">
                &copy; {{ now()->year }} {{ config('app.name', 'Codex') }}. {{ __('All rights reserved.') }}
            </div>
            <div>
                @include('layouts.partials.theme-toggle')
            </div>
        </div>
    </div>
</footer>
