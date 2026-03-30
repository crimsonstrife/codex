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
</head>
<body class="bg-body-tertiary">
<div class="container py-5" style="max-width: 72rem;">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <p class="text-uppercase small text-body-secondary mb-1">System Status</p>
            <h1 class="h2 mb-1">{{ config('app.name', 'Codex') }}</h1>
            <p class="text-body-secondary mb-0">
                Latest stored health checks.
                @if ($checkResults?->finishedAt)
                    Last updated {{ $checkResults->finishedAt->format('M j, Y g:i:s A') }}.
                @endif
            </p>
        </div>

        <span class="badge fs-6 px-3 py-2 {{ $checkResults?->allChecksOk() ? 'bg-success-subtle text-success-emphasis' : 'bg-warning-subtle text-warning-emphasis' }}">
            {{ $checkResults?->allChecksOk() ? 'Operational' : 'Attention Needed' }}
        </span>
    </div>

    @if (! $checkResults || $checkResults->storedCheckResults->isEmpty())
        <div class="card shadow-sm">
            <div class="card-body p-4 text-body-secondary">
                No health results have been stored yet.
            </div>
        </div>
    @else
        <div class="row g-4">
            @foreach ($checkResults->storedCheckResults as $result)
                @php
                    $statusClass = match ($result->status) {
                        'ok' => 'bg-success-subtle text-success-emphasis',
                        'warning' => 'bg-warning-subtle text-warning-emphasis',
                        'failed', 'crashed' => 'bg-danger-subtle text-danger-emphasis',
                        default => 'bg-secondary-subtle text-secondary-emphasis',
                    };
                @endphp

                <div class="col-md-6 col-xl-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                <div>
                                    <h2 class="h5 mb-1">{{ $result->label }}</h2>
                                    @if ($result->shortSummary)
                                        <p class="text-body-secondary small mb-0">{{ $result->shortSummary }}</p>
                                    @endif
                                </div>
                                <span class="badge {{ $statusClass }}">{{ ucfirst($result->status) }}</span>
                            </div>

                            @if ($result->notificationMessage)
                                <p class="mb-3">{{ $result->notificationMessage }}</p>
                            @endif

                            @if (! empty($result->meta))
                                <dl class="mb-0 small">
                                    @foreach ($result->meta as $key => $value)
                                        <dt class="text-body-secondary mb-1">{{ str($key)->replace('_', ' ')->title() }}</dt>
                                        <dd class="mb-2">
                                            @if (is_array($value))
                                                {{ implode(', ', array_map(static fn ($item): string => (string) $item, $value)) }}
                                            @else
                                                {{ $value }}
                                            @endif
                                        </dd>
                                    @endforeach
                                </dl>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
</body>
</html>
