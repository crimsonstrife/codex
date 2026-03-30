@php
    $resultCount = count($presentedResults ?? []);
@endphp

<div class="mx-auto max-w-7xl px-2 sm:px-6 lg:px-8">
    <div class="flex flex-wrap justify-center space-y-3 text-center">
        <h4 class="w-full text-2xl font-bold text-gray-900 dark:text-white">
            {{ __('health::notifications.laravel_health') }}
        </h4>

        <div class="flex w-full justify-center">
            <x-health-logo />
        </div>

        @if ($lastRanLabel)
            <div class="{{ $staleResults ? 'text-red-400' : 'text-gray-400 dark:text-gray-500' }} w-full text-sm font-medium">
                {{ __('health::notifications.check_results_from') }} {{ $lastRanLabel }}
            </div>
        @endif
    </div>

    <div class="my-6 px-2 md:mt-8 md:px-0">
        @if ($resultCount)
            <dl class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($presentedResults as $result)
                    @php
                        $indicator = match ($result['status']) {
                            'ok' => [
                                'wrapper' => 'bg-emerald-100 dark:bg-emerald-800/60',
                                'icon' => 'text-emerald-500',
                                'badge' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/15 dark:text-emerald-300',
                                'name' => 'check-circle',
                            ],
                            'warning' => [
                                'wrapper' => 'bg-yellow-100 dark:bg-yellow-800/60',
                                'icon' => 'text-yellow-500',
                                'badge' => 'bg-yellow-50 text-yellow-700 ring-yellow-600/20 dark:bg-yellow-500/15 dark:text-yellow-300',
                                'name' => 'exclamation-circle',
                            ],
                            'failed', 'crashed' => [
                                'wrapper' => 'bg-red-100 dark:bg-red-800/60',
                                'icon' => 'text-red-500',
                                'badge' => 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/15 dark:text-red-300',
                                'name' => 'x-circle',
                            ],
                            'skipped' => [
                                'wrapper' => 'bg-blue-100 dark:bg-blue-800/60',
                                'icon' => 'text-blue-500',
                                'badge' => 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-500/15 dark:text-blue-300',
                                'name' => 'arrow-circle-right',
                            ],
                            default => [
                                'wrapper' => 'bg-gray-100 dark:bg-gray-700/60',
                                'icon' => 'text-gray-500',
                                'badge' => 'bg-gray-50 text-gray-700 ring-gray-600/20 dark:bg-white/10 dark:text-gray-300',
                                'name' => 'question-mark-circle',
                            ],
                        };

                        $detailCount = count($result['metaRows']) + ($result['securityAdvisories']['advisoryCount'] ?? 0);
                        $hasDetails = $detailCount > 0;
                    @endphp

                    <div class="overflow-hidden rounded-xl bg-white shadow-md shadow-gray-200 dark:border-t dark:border-gray-700 dark:bg-gray-800 dark:shadow-black/25 dark:shadow-md">
                        <div class="flex items-start gap-3 px-4 py-5 sm:p-6">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $indicator['wrapper'] }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 {{ $indicator['icon'] }}" viewBox="0 0 20 20" fill="currentColor">
                                    @if ($indicator['name'] === 'check-circle')
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    @elseif ($indicator['name'] === 'exclamation-circle')
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    @elseif ($indicator['name'] === 'arrow-circle-right')
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 1.414L10.586 9H7a1 1 0 100 2h3.586l-1.293 1.293a1 1 0 101.414 1.414l3-3a1 1 0 000-1.414z" clip-rule="evenodd" />
                                    @elseif ($indicator['name'] === 'x-circle')
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    @else
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                    @endif
                                </svg>
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <dd class="font-bold text-gray-900 dark:text-white md:text-xl">
                                        {{ $result['label'] }}
                                    </dd>

                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $indicator['badge'] }}">
                                        {{ ucfirst($result['status']) }}
                                    </span>
                                </div>

                                <dt class="mt-1 text-sm font-medium text-gray-600 dark:text-gray-300">
                                    {{ $result['notificationMessage'] ?: $result['shortSummary'] }}
                                </dt>
                            </div>
                        </div>

                        @if ($hasDetails)
                            <details class="border-t border-gray-100 bg-gray-50/80 dark:border-gray-700 dark:bg-gray-900/40">
                                <summary class="cursor-pointer px-4 py-3 text-sm font-medium text-gray-700 marker:hidden sm:px-6 dark:text-gray-200">
                                    More details
                                </summary>

                                <div class="space-y-4 px-4 pb-4 sm:px-6">
                                    @if (! empty($result['securityAdvisories']))
                                        <div class="space-y-3">
                                            <div>
                                                <p class="text-sm font-semibold text-red-700 dark:text-red-300">Security advisories</p>
                                                <p class="text-sm text-gray-600 dark:text-gray-300">
                                                    {{ $result['securityAdvisories']['advisoryCount'] }}
                                                    {{ \Illuminate\Support\Str::plural('advisory', $result['securityAdvisories']['advisoryCount']) }}
                                                    across
                                                    {{ $result['securityAdvisories']['packageCount'] }}
                                                    {{ \Illuminate\Support\Str::plural('package', $result['securityAdvisories']['packageCount']) }}.
                                                </p>
                                            </div>

                                            @foreach ($result['securityAdvisories']['packages'] as $package)
                                                <div class="rounded-lg border border-red-100 bg-white p-3 dark:border-red-500/20 dark:bg-gray-800/80">
                                                    <div class="flex items-start justify-between gap-3">
                                                        <div>
                                                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $package['packageName'] }}</p>
                                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                                {{ $package['advisoryCount'] }}
                                                                {{ \Illuminate\Support\Str::plural('advisory', $package['advisoryCount']) }}
                                                            </p>
                                                        </div>
                                                    </div>

                                                    <div class="mt-3 space-y-3">
                                                        @foreach ($package['advisories'] as $advisory)
                                                            <div class="rounded-lg border border-gray-100 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/60">
                                                                @if ($advisory['link'])
                                                                    <a href="{{ $advisory['link'] }}" target="_blank" rel="noreferrer" class="text-sm font-semibold text-gray-900 underline underline-offset-2 dark:text-white">
                                                                        {{ $advisory['title'] }}
                                                                    </a>
                                                                @else
                                                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $advisory['title'] }}</p>
                                                                @endif

                                                                <div class="mt-2 flex flex-wrap gap-2">
                                                                    @if ($advisory['cve'])
                                                                        <span class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20 dark:bg-red-500/15 dark:text-red-300">{{ $advisory['cve'] }}</span>
                                                                    @endif

                                                                    @if ($advisory['affectedVersions'])
                                                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 ring-1 ring-inset ring-gray-600/20 dark:bg-white/10 dark:text-gray-300">Affected {{ $advisory['affectedVersions'] }}</span>
                                                                    @endif

                                                                    @if ($advisory['reportedAt'])
                                                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 ring-1 ring-inset ring-gray-600/20 dark:bg-white/10 dark:text-gray-300">Reported {{ $advisory['reportedAt'] }}</span>
                                                                    @endif
                                                                </div>

                                                                @if (! empty($advisory['sources']))
                                                                    <div class="mt-2 flex flex-wrap gap-2">
                                                                        @foreach ($advisory['sources'] as $source)
                                                                            <a href="{{ $source['url'] }}" target="_blank" rel="noreferrer" class="text-xs font-medium text-primary-600 underline underline-offset-2 dark:text-primary-400">
                                                                                {{ $source['label'] }}
                                                                            </a>
                                                                        @endforeach
                                                                    </div>
                                                                @endif

                                                                @if (! empty($advisory['details']))
                                                                    <dl class="mt-3 space-y-2 text-xs">
                                                                        @foreach ($advisory['details'] as $row)
                                                                            <div>
                                                                                <dt class="font-medium text-gray-500 dark:text-gray-400">{{ $row['label'] }}</dt>
                                                                                <dd class="mt-1 text-gray-700 dark:text-gray-200">
                                                                                    @if ($row['isBlock'])
                                                                                        <pre class="whitespace-pre-wrap break-all rounded-md bg-white p-3 font-mono dark:bg-gray-800">{{ $row['value'] }}</pre>
                                                                                    @elseif ($row['isUrl'])
                                                                                        <a href="{{ $row['value'] }}" target="_blank" rel="noreferrer" class="text-primary-600 underline underline-offset-2 dark:text-primary-400">{{ $row['value'] }}</a>
                                                                                    @else
                                                                                        {{ $row['value'] }}
                                                                                    @endif
                                                                                </dd>
                                                                            </div>
                                                                        @endforeach
                                                                    </dl>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if (! empty($result['metaRows']))
                                        <dl class="space-y-2">
                                            @foreach ($result['metaRows'] as $row)
                                                <div>
                                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $row['label'] }}</dt>
                                                    <dd class="mt-1 text-sm text-gray-700 dark:text-gray-200">
                                                        @if ($row['isBlock'])
                                                            <pre class="whitespace-pre-wrap break-all rounded-md bg-white p-3 font-mono text-xs dark:bg-gray-800">{{ $row['value'] }}</pre>
                                                        @elseif ($row['isUrl'])
                                                            <a href="{{ $row['value'] }}" target="_blank" rel="noreferrer" class="text-primary-600 underline underline-offset-2 dark:text-primary-400">{{ $row['value'] }}</a>
                                                        @else
                                                            {{ $row['value'] }}
                                                        @endif
                                                    </dd>
                                                </div>
                                            @endforeach
                                        </dl>
                                    @endif
                                </div>
                            </details>
                        @endif
                    </div>
                @endforeach
            </dl>
        @else
            <div class="rounded-xl bg-white px-4 py-6 text-sm text-gray-600 shadow-md shadow-gray-200 dark:border-t dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:shadow-black/25 dark:shadow-md">
                No stored health results are available yet.
            </div>
        @endif
    </div>
</div>
