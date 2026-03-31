<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column gap-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('workspaces.show', $workspace) }}">{{ $workspace->name }}</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $script->title }}</li>
                </ol>
            </nav>

            @include('workspaces._header_actions')
        </div>
    </x-slot>

    <style>
        .screenplay-sheet {
            background: linear-gradient(180deg, #fffef8 0%, #fff 100%);
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 1rem;
            padding: 2rem 1.5rem;
            font-family: "Courier New", Courier, monospace;
            font-size: 12pt;
            line-height: 1.5;
            white-space: normal;
        }
        .screenplay-block { margin-bottom: 0.7rem; white-space: pre-wrap; }
        .screenplay-scene-heading,
        .screenplay-transition,
        .screenplay-character { text-transform: uppercase; }
        .screenplay-character { margin-left: 22%; width: 40%; }
        .screenplay-parenthetical { margin-left: 18%; width: 30%; }
        .screenplay-dialogue { margin-left: 14%; width: 52%; }
        .screenplay-transition { margin-left: auto; width: 34%; text-align: right; }
        .script-side-card form:last-child { margin-bottom: 0; }
    </style>

    @php
        $characters = $script->entities->where('type', 'character')->values();
        $locations = $script->entities->where('type', 'location')->values();
    @endphp

    <div class="py-4">
        <div class="container">
            <div class="row g-4">
                <div class="col-xl-8">
                    <div class="card shadow-sm">
                        <div class="card-header p-4">
                            <div class="d-flex align-items-start justify-content-between gap-3">
                                <div>
                                    <h1 class="h3 mb-1">{{ $script->title }}</h1>
                                    @if($script->logline)
                                        <p class="text-body-secondary mb-2">{{ $script->logline }}</p>
                                    @endif
                                    <div class="d-flex flex-wrap gap-3 small text-body-secondary">
                                        <span><i class="fas fa-user me-1"></i>{{ $script->author?->name }}</span>
                                        <span><i class="fas fa-clock me-1"></i>{{ $script->updated_at->diffForHumans() }}</span>
                                        <span class="badge bg-secondary-subtle text-secondary-emphasis">{{ ucfirst($script->status) }}</span>
                                    </div>
                                    @if($script->categories->isNotEmpty())
                                        <div class="d-flex flex-wrap gap-2 mt-2">
                                            @foreach($script->categories as $category)
                                                <span class="badge bg-primary-subtle text-primary-emphasis">{{ $category->name }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <div class="d-flex flex-wrap gap-2 justify-content-end">
                                    <a href="{{ route('workspaces.scripts.edit', [$workspace, $script]) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-edit me-1"></i>Edit
                                    </a>
                                    <a href="{{ route('workspaces.scripts.history', [$workspace, $script]) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-history me-1"></i>History
                                    </a>
                                    <a href="{{ route('workspaces.scripts.print', [$workspace, $script]) }}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">
                                        <i class="fas fa-print me-1"></i>Print
                                    </a>
                                    <a href="{{ route('workspaces.scripts.export.fountain', [$workspace, $script]) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-file-export me-1"></i>Fountain
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            @if(session('status') === 'script-updated')
                                <div class="alert alert-success py-2">
                                    <i class="fas fa-check-circle me-1"></i> Draft updated.
                                </div>
                            @endif
                            @if(in_array(session('status'), ['script-binder-page-attached', 'script-binder-page-created', 'script-binder-page-updated', 'script-binder-page-detached', 'script-entity-saved', 'script-entity-deleted'], true))
                                <div class="alert alert-success py-2">
                                    <i class="fas fa-check-circle me-1"></i> Script workspace details updated.
                                </div>
                            @endif
                            <div class="screenplay-sheet">
                                {!! $renderedScript !!}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="card shadow-sm script-side-card">
                        <div class="card-header py-3">
                            <h2 class="h6 fw-semibold mb-0">
                                <i class="fas fa-folder-tree me-2 text-body-secondary"></i>Binder Pages
                            </h2>
                        </div>
                        <div class="card-body">
                            @forelse($script->binderLinks as $link)
                                <div class="border rounded-3 p-3 mb-3">
                                    <div class="d-flex align-items-start justify-content-between gap-2">
                                        <div>
                                            <a href="{{ route('workspaces.pages.show', [$workspace, $link->page]) }}" class="fw-medium text-decoration-none">
                                                {{ $link->page->title }}
                                            </a>
                                            <div class="small text-body-secondary">{{ ucfirst(str_replace('_', ' ', $link->role)) }}</div>
                                        </div>
                                        <form method="POST" action="{{ route('workspaces.scripts.binder-pages.destroy', [$workspace, $script, $link]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-unlink"></i>
                                            </button>
                                        </form>
                                    </div>
                                    <form method="POST" action="{{ route('workspaces.scripts.binder-pages.update', [$workspace, $script, $link]) }}" class="mt-3">
                                        @csrf
                                        @method('PATCH')
                                        <div class="row g-2">
                                            <div class="col-7">
                                                <select name="role" class="form-select form-select-sm">
                                                    @foreach(['research', 'outline', 'notes', 'scene_card'] as $role)
                                                        <option value="{{ $role }}" {{ $link->role === $role ? 'selected' : '' }}>
                                                            {{ ucfirst(str_replace('_', ' ', $role)) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-3">
                                                <input type="number" name="position" min="1" value="{{ $link->position }}" class="form-control form-control-sm">
                                            </div>
                                            <div class="col-2">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary w-100">Save</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            @empty
                                <p class="small text-body-secondary">No binder pages yet.</p>
                            @endforelse

                            <form method="POST" action="{{ route('workspaces.scripts.binder-pages.store', [$workspace, $script]) }}" class="border-top pt-3 mt-3">
                                @csrf
                                <h3 class="h6">Attach Existing Page</h3>
                                <div class="mb-2">
                                    <select name="page_id" class="form-select form-select-sm">
                                        <option value="">Choose a page…</option>
                                        @foreach($availablePages as $page)
                                            <option value="{{ $page->id }}">{{ $page->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="d-flex gap-2">
                                    <select name="role" class="form-select form-select-sm">
                                        @foreach(['research', 'outline', 'notes', 'scene_card'] as $role)
                                            <option value="{{ $role }}">{{ ucfirst(str_replace('_', ' ', $role)) }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary flex-shrink-0">Attach</button>
                                </div>
                            </form>

                            <form method="POST" action="{{ route('workspaces.scripts.binder-pages.create', [$workspace, $script]) }}" class="border-top pt-3 mt-3">
                                @csrf
                                <h3 class="h6">Create Binder Page</h3>
                                <div class="mb-2">
                                    <input type="text" name="title" class="form-control form-control-sm" placeholder="New page title" required>
                                </div>
                                <div class="row g-2">
                                    <div class="col-7">
                                        <select name="template_id" class="form-select form-select-sm">
                                            <option value="">Blank page</option>
                                            @foreach($templates as $template)
                                                <option value="{{ $template->id }}">{{ $template->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-5">
                                        <select name="role" class="form-select form-select-sm">
                                            @foreach(['research', 'outline', 'notes', 'scene_card'] as $role)
                                                <option value="{{ $role }}">{{ ucfirst(str_replace('_', ' ', $role)) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-sm btn-outline-primary mt-2">Create & Attach</button>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow-sm mt-4 script-side-card">
                        <div class="card-header py-3">
                            <h2 class="h6 fw-semibold mb-0">
                                <i class="fas fa-users me-2 text-body-secondary"></i>Characters
                            </h2>
                        </div>
                        <div class="card-body">
                            @foreach($characters as $character)
                                <div class="border rounded-3 p-3 mb-3">
                                    <form method="POST" action="{{ route('workspaces.scripts.entities.update', [$workspace, $script, $character]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <div class="mb-2">
                                            <input type="text" name="name" class="form-control form-control-sm" value="{{ $character->name }}" placeholder="Name">
                                        </div>
                                        <div class="mb-2">
                                            <input type="text" name="display_name" class="form-control form-control-sm" value="{{ $character->display_name }}" placeholder="Display name">
                                        </div>
                                        <div class="mb-2">
                                            <input type="text" name="aliases" class="form-control form-control-sm" value="{{ collect($character->aliases)->join(', ') }}" placeholder="Aliases">
                                        </div>
                                        <div class="mb-2">
                                            <textarea name="notes" rows="2" class="form-control form-control-sm" placeholder="Notes">{{ $character->notes }}</textarea>
                                        </div>
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">Save</button>
                                    </form>
                                    <form method="POST" action="{{ route('workspaces.scripts.entities.destroy', [$workspace, $script, $character]) }}" class="mt-2">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </div>
                            @endforeach

                            <form method="POST" action="{{ route('workspaces.scripts.entities.store', [$workspace, $script]) }}" class="border-top pt-3">
                                @csrf
                                <input type="hidden" name="type" value="character">
                                <h3 class="h6">New Character</h3>
                                <div class="mb-2">
                                    <input type="text" name="name" class="form-control form-control-sm" placeholder="Name" required>
                                </div>
                                <div class="mb-2">
                                    <input type="text" name="display_name" class="form-control form-control-sm" placeholder="Display name">
                                </div>
                                <button type="submit" class="btn btn-sm btn-primary">Add Character</button>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow-sm mt-4 script-side-card">
                        <div class="card-header py-3">
                            <h2 class="h6 fw-semibold mb-0">
                                <i class="fas fa-map-marker-alt me-2 text-body-secondary"></i>Locations
                            </h2>
                        </div>
                        <div class="card-body">
                            @foreach($locations as $location)
                                <div class="border rounded-3 p-3 mb-3">
                                    <form method="POST" action="{{ route('workspaces.scripts.entities.update', [$workspace, $script, $location]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <div class="mb-2">
                                            <input type="text" name="name" class="form-control form-control-sm" value="{{ $location->name }}" placeholder="Name">
                                        </div>
                                        <div class="mb-2">
                                            <input type="text" name="display_name" class="form-control form-control-sm" value="{{ $location->display_name }}" placeholder="Display name">
                                        </div>
                                        <div class="mb-2">
                                            <input type="text" name="hierarchy_text" class="form-control form-control-sm" value="{{ $location->hierarchy_text }}" placeholder="Hierarchy text">
                                        </div>
                                        <div class="mb-2">
                                            <textarea name="notes" rows="2" class="form-control form-control-sm" placeholder="Notes">{{ $location->notes }}</textarea>
                                        </div>
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">Save</button>
                                    </form>
                                    <form method="POST" action="{{ route('workspaces.scripts.entities.destroy', [$workspace, $script, $location]) }}" class="mt-2">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </div>
                            @endforeach

                            <form method="POST" action="{{ route('workspaces.scripts.entities.store', [$workspace, $script]) }}" class="border-top pt-3">
                                @csrf
                                <input type="hidden" name="type" value="location">
                                <h3 class="h6">New Location</h3>
                                <div class="mb-2">
                                    <input type="text" name="name" class="form-control form-control-sm" placeholder="Name" required>
                                </div>
                                <div class="mb-2">
                                    <input type="text" name="display_name" class="form-control form-control-sm" placeholder="Display name">
                                </div>
                                <div class="mb-2">
                                    <input type="text" name="hierarchy_text" class="form-control form-control-sm" placeholder="Hierarchy text">
                                </div>
                                <button type="submit" class="btn btn-sm btn-primary">Add Location</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
