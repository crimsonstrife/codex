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
    <nav class="navbar navbar-expand-md bg-body border-bottom">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="{{ url('/') }}">
                <x-application-logo style="height: 2rem;" />
                <span class="fw-semibold">{{ config('app.name', 'Codex') }}</span>
            </a>
            <div class="d-flex align-items-center gap-2">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn btn-sm btn-outline-secondary">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-sm btn-outline-secondary">Log in</a>
                        @if (Route::has('register') && \App\Support\CodexRuntimeConfig::registrationEnabled())
                            <a href="{{ route('register') }}" class="btn btn-sm btn-primary">Register</a>
                        @endif
                    @endauth
                @endif
            </div>
        </div>
    </nav>

    <main class="flex-grow-1">
        <section class="py-5">
            <div class="container text-center py-4">
                <x-application-logo style="height: 5rem;" class="mb-4" />
                <h1 class="display-5 fw-bold mb-3">{{ config('app.name', 'Codex') }}</h1>
                <p class="lead text-body-secondary mb-4">
                    Your collaborative knowledge base. Create workspaces, write rich pages, and draw diagrams — all in one place.
                </p>
                <div class="d-flex justify-content-center gap-3">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="btn btn-primary">
                                <i class="fas fa-tachometer-alt me-1"></i> Go to Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-outline-primary">Log in</a>
                            @if (Route::has('register') && \App\Support\CodexRuntimeConfig::registrationEnabled())
                                <a href="{{ route('register') }}" class="btn btn-primary">Get Started</a>
                            @endif
                        @endauth
                    @endif
                </div>
            </div>
        </section>

        <section class="py-5 bg-body-tertiary">
            <div class="container">
                <div class="row g-4 text-center">
                    <div class="col-md-4">
                        <i class="fas fa-folder-open fa-3x text-primary mb-3"></i>
                        <h5>Workspaces</h5>
                        <p class="text-body-secondary">Organise your knowledge into workspaces — one per project, team, or topic.</p>
                    </div>
                    <div class="col-md-4">
                        <i class="fas fa-file-alt fa-3x text-success mb-3"></i>
                        <h5>Rich Pages</h5>
                        <p class="text-body-secondary">Write rich-text or Markdown pages with full editor support, tags, and categories.</p>
                    </div>
                    <div class="col-md-4">
                        <i class="fas fa-project-diagram fa-3x text-warning mb-3"></i>
                        <h5>Diagrams</h5>
                        <p class="text-body-secondary">Create Mermaid flowcharts, mind maps, and sequence diagrams — in the browser.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    @include('layouts.partials.footer')
</div>
</body>
</html>
