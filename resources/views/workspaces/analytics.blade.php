<x-app-layout>
    <x-slot name="header">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('workspaces.show', $workspace) }}">{{ $workspace->name }}</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Analytics</li>
            </ol>
        </nav>
    </x-slot>

    <div class="py-4">
        <div class="container" style="max-width: 64rem;">

            {{-- Summary stat cards --}}
            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-3">
                    <div class="card shadow-sm h-100 text-center p-3">
                        <div class="display-6 fw-bold text-primary">{{ number_format($totalPages) }}</div>
                        <div class="small text-body-secondary mt-1">Total Pages</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card shadow-sm h-100 text-center p-3">
                        <div class="display-6 fw-bold text-success">{{ number_format($totalRevisions) }}</div>
                        <div class="small text-body-secondary mt-1">Total Edits</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card shadow-sm h-100 text-center p-3">
                        <div class="display-6 fw-bold text-info">{{ number_format($recentViews) }}</div>
                        <div class="small text-body-secondary mt-1">Views (30 days)</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card shadow-sm h-100 text-center p-3">
                        <div class="display-6 fw-bold text-warning">{{ number_format($totalViews) }}</div>
                        <div class="small text-body-secondary mt-1">Total Views</div>
                    </div>
                </div>
            </div>

            {{-- Page status breakdown --}}
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header py-3">
                            <h2 class="h6 fw-semibold mb-0">
                                <i class="fas fa-chart-pie me-2 text-body-secondary"></i>Pages by Status
                            </h2>
                        </div>
                        <div class="card-body p-4">
                            @foreach(['published' => 'success', 'draft' => 'warning', 'archived' => 'secondary'] as $status => $colour)
                                @php $count = $statusCounts[$status] ?? 0; @endphp
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <span class="badge bg-{{ $colour }}-subtle text-{{ $colour }}-emphasis"
                                          style="min-width:5rem;text-align:center;">{{ ucfirst($status) }}</span>
                                    <div class="flex-grow-1">
                                        <div class="progress" style="height:0.5rem;">
                                            <div class="progress-bar bg-{{ $colour }}"
                                                 style="width:{{ $totalPages > 0 ? round($count / $totalPages * 100) : 0 }}%"></div>
                                        </div>
                                    </div>
                                    <span class="small text-body-secondary fw-medium" style="min-width:2rem;text-align:right;">
                                        {{ $count }}
                                    </span>
                                </div>
                            @endforeach
                            @if($totalPages === 0)
                                <p class="text-body-secondary small mb-0 text-center">No pages yet.</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Top pages by views --}}
                <div class="col-md-8">
                    <div class="card shadow-sm h-100">
                        <div class="card-header py-3">
                            <h2 class="h6 fw-semibold mb-0">
                                <i class="fas fa-eye me-2 text-body-secondary"></i>Most Viewed Pages
                                <span class="fw-normal text-body-secondary ms-1" style="font-size:.75rem;">(last 30 days)</span>
                            </h2>
                        </div>
                        @if($topPages->isEmpty())
                            <div class="card-body text-center text-body-secondary small py-4">No view data yet.</div>
                        @else
                        <div class="list-group list-group-flush">
                            @foreach($topPages as $row)
                                @if($row->page)
                                <div class="list-group-item d-flex align-items-center gap-3 py-2 px-4">
                                    <span class="text-body-secondary fw-bold" style="min-width:1.5rem;text-align:right;font-size:.8rem;">
                                        {{ $loop->iteration }}
                                    </span>
                                    <a href="{{ route('workspaces.pages.show', [$workspace, $row->page]) }}"
                                       class="text-decoration-none fw-medium small flex-grow-1 text-truncate">
                                        {{ $row->page->title }}
                                    </a>
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis fw-normal"
                                          style="font-size:.7rem;">
                                        <i class="fas fa-eye me-1"></i>{{ number_format($row->view_count) }}
                                    </span>
                                    <span class="badge
                                        {{ $row->page->status === 'published' ? 'bg-success-subtle text-success-emphasis' : 'bg-secondary-subtle text-secondary-emphasis' }}"
                                        style="font-size:.65rem;">
                                        {{ ucfirst($row->page->status) }}
                                    </span>
                                </div>
                                @endif
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Edit activity chart + top contributors --}}
            <div class="row g-3 mb-4">
                <div class="col-md-8">
                    <div class="card shadow-sm h-100">
                        <div class="card-header py-3">
                            <h2 class="h6 fw-semibold mb-0">
                                <i class="fas fa-pencil-alt me-2 text-body-secondary"></i>Edit Activity
                                <span class="fw-normal text-body-secondary ms-1" style="font-size:.75rem;">(last 30 days)</span>
                            </h2>
                        </div>
                        <div class="card-body p-4">
                            <canvas id="activityChart" height="120"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header py-3">
                            <h2 class="h6 fw-semibold mb-0">
                                <i class="fas fa-users me-2 text-body-secondary"></i>Top Contributors
                            </h2>
                        </div>
                        @if($topContributors->isEmpty())
                            <div class="card-body text-center text-body-secondary small py-4">No data yet.</div>
                        @else
                        <div class="list-group list-group-flush">
                            @foreach($topContributors as $row)
                                @if($row->user)
                                <div class="list-group-item d-flex align-items-center gap-3 py-2 px-3">
                                    <div class="rounded-circle bg-secondary-subtle text-secondary-emphasis fw-bold d-flex align-items-center justify-content-center flex-shrink-0"
                                         style="width:28px;height:28px;font-size:0.65rem;">
                                        {{ strtoupper(substr($row->user->name, 0, 2)) }}
                                    </div>
                                    <span class="small flex-grow-1 text-truncate">{{ $row->user->name }}</span>
                                    <span class="badge bg-primary-subtle text-primary-emphasis fw-normal"
                                          style="font-size:.7rem;">
                                        {{ number_format($row->revision_count) }} edits
                                    </span>
                                </div>
                                @endif
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Most linked-to pages --}}
            @if($mostLinked->isNotEmpty())
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3">
                    <h2 class="h6 fw-semibold mb-0">
                        <i class="fas fa-link me-2 text-body-secondary"></i>Most Referenced Pages
                        <span class="fw-normal text-body-secondary ms-1" style="font-size:.75rem;">(by internal wiki links)</span>
                    </h2>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">#</th>
                                <th>Page</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Incoming Links</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($mostLinked as $row)
                                @if($row->targetPage)
                                <tr>
                                    <td class="ps-4 text-body-secondary small">{{ $loop->iteration }}</td>
                                    <td>
                                        <a href="{{ route('workspaces.pages.show', [$workspace, $row->targetPage]) }}"
                                           class="text-decoration-none fw-medium small">
                                            {{ $row->targetPage->title }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge
                                            {{ $row->targetPage->status === 'published' ? 'bg-success-subtle text-success-emphasis' : 'bg-secondary-subtle text-secondary-emphasis' }}"
                                            style="font-size:.65rem;">
                                            {{ ucfirst($row->targetPage->status) }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <span class="badge bg-info-subtle text-info-emphasis fw-normal"
                                              style="font-size:.7rem;">
                                            <i class="fas fa-link me-1"></i>{{ $row->link_count }}
                                        </span>
                                    </td>
                                </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <script>
    (function () {
        const labels = @json($dailyLabels);
        const data   = @json($dailyCounts);
        const maxVal = Math.max(...data, 1);

        const ctx = document.getElementById('activityChart');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Edits',
                    data,
                    backgroundColor: 'rgba(99,102,241,0.25)',
                    borderColor:     'rgba(99,102,241,0.8)',
                    borderWidth: 1,
                    borderRadius: 2,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10 }, maxTicksLimit: 10 },
                    },
                    y: {
                        beginAtZero: true,
                        suggestedMax: maxVal + 1,
                        ticks: { precision: 0, font: { size: 10 } },
                        grid: { color: 'rgba(0,0,0,0.05)' },
                    },
                },
            },
        });
    })();
    </script>
    @endpush
</x-app-layout>
