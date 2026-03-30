<x-filament-panels::page>
    <div wire:poll.15s="loadResults" class="space-y-6">
        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Current Status</p>
                <p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">
                    {{ $allChecksOk ? 'Operational' : (count($checkResults) ? 'Attention Needed' : 'No Results Yet') }}
                </p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Checks</p>
                <p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">
                    {{ count($checkResults) }}
                </p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Finished</p>
                <p class="mt-2 text-lg font-semibold text-gray-950 dark:text-white">
                    {{ $finishedAt ?? 'Not available yet' }}
                </p>
            </div>
        </div>

        @if (count($checkResults) === 0)
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-8 text-sm text-gray-600 shadow-sm dark:border-white/10 dark:bg-gray-900 dark:text-gray-300">
                No stored health results are available yet. Run the checks once from this page or let the scheduler create the first batch.
            </div>
        @else
            <div class="grid gap-4 xl:grid-cols-2">
                @foreach ($checkResults as $result)
                    @php
                        $statusColor = match ($result['status']) {
                            'ok' => 'bg-green-100 text-green-800 ring-green-600/20 dark:bg-green-500/15 dark:text-green-300',
                            'warning' => 'bg-amber-100 text-amber-800 ring-amber-600/20 dark:bg-amber-500/15 dark:text-amber-300',
                            'failed', 'crashed' => 'bg-red-100 text-red-800 ring-red-600/20 dark:bg-red-500/15 dark:text-red-300',
                            default => 'bg-gray-100 text-gray-800 ring-gray-600/20 dark:bg-white/10 dark:text-gray-300',
                        };
                    @endphp

                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-base font-semibold text-gray-950 dark:text-white">{{ $result['label'] }}</h3>
                                @if (filled($result['shortSummary']))
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $result['shortSummary'] }}</p>
                                @endif
                            </div>

                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $statusColor }}">
                                {{ ucfirst($result['status']) }}
                            </span>
                        </div>

                        @if (filled($result['notificationMessage']))
                            <p class="mt-4 text-sm text-gray-700 dark:text-gray-200">{{ $result['notificationMessage'] }}</p>
                        @endif

                        @if (! empty($result['meta']))
                            <dl class="mt-4 space-y-2 rounded-xl bg-gray-50 p-4 text-sm dark:bg-white/5">
                                @foreach ($result['meta'] as $key => $value)
                                    <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                                        <dt class="font-medium text-gray-600 dark:text-gray-300">{{ str($key)->replace('_', ' ')->title() }}</dt>
                                        <dd class="text-gray-800 dark:text-gray-100">
                                            @if (is_array($value))
                                                {{ implode(', ', array_map(static fn ($item): string => (string) $item, $value)) }}
                                            @else
                                                {{ $value }}
                                            @endif
                                        </dd>
                                    </div>
                                @endforeach
                            </dl>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>
