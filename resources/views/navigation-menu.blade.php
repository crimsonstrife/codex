<nav class="navbar navbar-expand-md bg-body border-bottom sticky-top" style="z-index:1025;">
    <div class="container">
        <!-- Brand -->
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ url('/') }}">
            <x-application-mark class="me-1" style="height: 2rem;" />
            <span class="fw-semibold">{{ config('app.name', 'Codex') }}</span>
        </a>

        <!-- Toggler -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#appNavbar"
                aria-controls="appNavbar" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Nav contents -->
        <div class="collapse navbar-collapse" id="appNavbar">
            <!-- Left: main nav -->
            <ul class="navbar-nav me-auto mb-2 mb-md-0 align-items-md-center">
                @auth
                    <li class="nav-item">
                        <x-nav-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">
                            {{ __('Dashboard') }}
                        </x-nav-link>
                    </li>
                    <li class="nav-item">
                        <x-nav-link href="{{ route('workspaces.index') }}" :active="request()->routeIs('workspaces*')">
                            {{ __('Workspaces') }}
                        </x-nav-link>
                    </li>
                @endauth
            </ul>

            @auth
            <!-- Middle: global search -->
            <form action="{{ route('search') }}" method="GET"
                  class="d-none d-md-flex align-items-center me-3">
                <label for="global-search" class="visually-hidden">{{ __('Search') }}</label>
                <input id="global-search" name="q" type="search"
                       class="form-control form-control-sm" style="width: 16rem;"
                       placeholder="{{ __('Search pages, diagrams…') }}"
                       value="{{ request('q') }}" />
            </form>
            @endauth

            <!-- Right: actions -->
            <div class="d-flex align-items-center gap-2">
                @auth
                    <!-- Forge cross-app link (shown when Forge SSO/integration is configured) -->
                    @if(\App\Support\CodexRuntimeConfig::forgeEnabled() && filled(\App\Support\CodexRuntimeConfig::forgeUrl()))
                        @php
                            $linkedForgeProject = optional(request()->route('workspace'))->forge_project_id;
                            $forgeUrl = \App\Support\CodexRuntimeConfig::forgeUrl();
                        @endphp
                        <a href="{{ $forgeUrl }}{{ $linkedForgeProject ? '/projects/' . $linkedForgeProject : '' }}"
                           class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1"
                           target="_blank"
                           rel="noopener"
                           title="{{ $linkedForgeProject ? __('Open linked Forge project') : __('Open Forge') }}">
                            <i class="fas fa-external-link-alt" style="font-size:0.7rem;"></i>
                            {{ __('Forge') }}
                        </a>
                    @endif

                    <!-- Teams dropdown -->
                    @if (Laravel\Jetstream\Jetstream::hasTeamFeatures())
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                {{ Auth::user()->currentTeam->name }}
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><span class="dropdown-header">{{ __('Manage Team') }}</span></li>
                                <li>
                                    <x-dropdown-link href="{{ route('teams.show', Auth::user()->currentTeam->id) }}">
                                        {{ __('Team Settings') }}
                                    </x-dropdown-link>
                                </li>
                                @can('create', Laravel\Jetstream\Jetstream::newTeamModel())
                                    <li>
                                        <x-dropdown-link href="{{ route('teams.create') }}">
                                            {{ __('Create New Team') }}
                                        </x-dropdown-link>
                                    </li>
                                @endcan

                                @if (Auth::user()->allTeams()->count() > 1)
                                    <li><hr class="dropdown-divider"></li>
                                    <li><span class="dropdown-header">{{ __('Switch Teams') }}</span></li>
                                    @foreach (Auth::user()->allTeams() as $team)
                                        <li>
                                            <x-switchable-team :team="$team" />
                                        </li>
                                    @endforeach
                                @endif
                            </ul>
                        </div>
                    @endif

                    <!-- Notification bell -->
                    <div class="dropdown" id="notification-dropdown">
                        <button class="btn btn-sm btn-outline-secondary position-relative"
                                type="button"
                                id="notificationBell"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                                data-url="{{ route('notifications.index') }}"
                                data-mark-url="{{ route('notifications.mark-all-read') }}">
                            <i class="fas fa-bell"></i>
                            <span id="notification-badge"
                                  class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none"
                                  style="font-size:0.6rem;">
                            </span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end shadow-sm p-0" style="width: 22rem; max-height: 28rem; overflow-y: auto;"
                             aria-labelledby="notificationBell">
                            <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                                <span class="small fw-semibold text-uppercase text-body-secondary">Notifications</span>
                                <button type="button" class="btn btn-link btn-sm p-0 text-body-secondary text-decoration-none"
                                        id="mark-all-read-btn">Mark all read</button>
                            </div>
                            <div id="notification-list">
                                <div class="px-3 py-4 text-center text-body-secondary small">
                                    <i class="fas fa-bell-slash opacity-25 d-block fa-2x mb-2"></i>
                                    No notifications yet.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Settings / Profile dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2"
                                type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                                <img class="rounded-circle object-fit-cover"
                                     src="{{ Auth::user()->profile_photo_url }}"
                                     alt="{{ Auth::user()->name }}"
                                     style="width:1.75rem;height:1.75rem;" />
                            @else
                                <i class="fas fa-user-circle"></i>
                                <span>{{ Auth::user()->name }}</span>
                            @endif
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" style="min-width: 12rem;">
                            <li><span class="dropdown-header">{{ __('Manage Account') }}</span></li>
                            <li>
                                <x-dropdown-link href="{{ route('profile.show') }}">
                                    {{ __('Profile') }}
                                </x-dropdown-link>
                            </li>
                            @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                                <li>
                                    <x-dropdown-link href="{{ route('api-tokens.index') }}">
                                        {{ __('API Tokens') }}
                                    </x-dropdown-link>
                                </li>
                            @endif
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}" class="m-0">
                                    @csrf
                                    <button type="submit" class="dropdown-item">
                                        <i class="fas fa-sign-out-alt me-1"></i>
                                        {{ __('Log Out') }}
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @else
                    @if (Route::has('login'))
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('login') }}">{{ __('Log in') }}</a>
                    @endif
                    @if (Route::has('register') && \App\Support\CodexRuntimeConfig::registrationEnabled())
                        <a class="btn btn-sm btn-primary" href="{{ route('register') }}">{{ __('Register') }}</a>
                    @endif
                @endauth
            </div>

            @auth
            <!-- Mobile: search -->
            <form action="{{ route('search') }}" method="GET" class="d-md-none mt-2">
                <input name="q" type="search" class="form-control form-control-sm"
                       placeholder="{{ __('Search…') }}" value="{{ request('q') }}" />
            </form>
            @endauth
        </div>
    </div>
</nav>

@once
<script>
(function () {
    const bell = document.getElementById('notificationBell');
    if (!bell) return;

    const badge    = document.getElementById('notification-badge');
    const list     = document.getElementById('notification-list');
    const markBtn  = document.getElementById('mark-all-read-btn');
    const url      = bell.dataset.url;
    const markUrl  = bell.dataset.markUrl;
    let loaded     = false;

    function renderNotifications(data) {
        badge.textContent = data.unread > 9 ? '9+' : data.unread;
        badge.classList.toggle('d-none', data.unread === 0);

        if (!data.notifications.length) {
            list.innerHTML = '<div class="px-3 py-4 text-center text-body-secondary small"><i class="fas fa-bell-slash opacity-25 d-block fa-2x mb-2"></i>No notifications yet.</div>';
            return;
        }

        list.innerHTML = data.notifications.map(n => `
            <a href="${n.url || '#'}"
               class="d-flex align-items-start gap-2 px-3 py-2 text-decoration-none border-bottom ${n.read ? 'text-body-secondary' : 'fw-medium'}"
               style="font-size:0.85rem;">
                <i class="fas fa-bell mt-1 flex-shrink-0 ${n.read ? 'text-body-secondary opacity-50' : 'text-primary'}"></i>
                <div class="flex-grow-1 overflow-hidden">
                    <div class="text-truncate">${n.message}</div>
                    <div class="text-body-secondary" style="font-size:0.72rem;">${n.created_at}</div>
                </div>
                ${!n.read ? '<span class="flex-shrink-0 rounded-circle bg-primary mt-2" style="width:6px;height:6px;"></span>' : ''}
            </a>
        `).join('');
    }

    async function loadNotifications() {
        try {
            const res  = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await res.json();
            renderNotifications(data);
            loaded = true;
        } catch (_) {}
    }

    // Load count on page ready (just the badge)
    loadNotifications();
    // Reload every 60 seconds
    setInterval(loadNotifications, 60000);

    // Load full list on dropdown open
    bell.closest('.dropdown').addEventListener('show.bs.dropdown', loadNotifications);

    // Mark all read
    if (markBtn) {
        markBtn.addEventListener('click', async () => {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
            await fetch(markUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } });
            await loadNotifications();
        });
    }
})();
</script>
@endonce
