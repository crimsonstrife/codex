<div class="card-body p-4 border-bottom">
    <x-application-logo style="height: 3rem;" />

    <h1 class="mt-4 h4">
        Welcome to {{ config('app.name', 'Codex') }}!
    </h1>

    <p class="text-body-secondary">
        Your collaborative knowledge base. Create workspaces, write rich pages,
        and draw diagrams — all in one place.
    </p>
</div>

<div class="card-body p-4">
    <div class="row g-4">
        <div class="col-md-6">
            <div class="d-flex align-items-start gap-3">
                <i class="fas fa-folder-open fa-lg text-primary mt-1"></i>
                <div>
                    <h5 class="fw-semibold mb-1">Workspaces</h5>
                    <p class="text-body-secondary small">
                        Organise your knowledge into workspaces — one per project, team, or topic.
                    </p>
                    <a href="{{ route('workspaces.index') }}" class="small fw-semibold text-primary">
                        Browse workspaces <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="d-flex align-items-start gap-3">
                <i class="fas fa-file-alt fa-lg text-success mt-1"></i>
                <div>
                    <h5 class="fw-semibold mb-1">Pages</h5>
                    <p class="text-body-secondary small">
                        Write rich-text or Markdown pages with full editor support, tags, and categories.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="d-flex align-items-start gap-3">
                <i class="fas fa-project-diagram fa-lg text-warning mt-1"></i>
                <div>
                    <h5 class="fw-semibold mb-1">Diagrams</h5>
                    <p class="text-body-secondary small">
                        Create Mermaid flowcharts, mind maps, and sequence diagrams — all rendered in the browser.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="d-flex align-items-start gap-3">
                <i class="fas fa-lock fa-lg text-secondary mt-1"></i>
                <div>
                    <h5 class="fw-semibold mb-1">Secure by default</h5>
                    <p class="text-body-secondary small">
                        Built on Laravel Jetstream with team support, two-factor authentication, and API tokens.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
