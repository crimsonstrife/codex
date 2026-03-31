<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column gap-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('workspaces.show', $workspace) }}">{{ $workspace->name }}</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Categories</li>
                </ol>
            </nav>

            @include('workspaces._header_actions')
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container" style="max-width: 72rem;">
            @if(session('status') === 'category-created')
                <div class="alert alert-success alert-dismissible mb-4" role="alert">
                    <i class="fas fa-check-circle me-1"></i> Category saved.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('status') === 'categories-added')
                <div class="alert alert-success alert-dismissible mb-4" role="alert">
                    <i class="fas fa-check-circle me-1"></i> Suggested categories added.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('status') === 'category-updated')
                <div class="alert alert-success alert-dismissible mb-4" role="alert">
                    <i class="fas fa-check-circle me-1"></i> Category updated.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('status') === 'category-deleted')
                <div class="alert alert-info alert-dismissible mb-4" role="alert">
                    <i class="fas fa-trash me-1"></i> Category deleted.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="row g-4">
                <div class="col-xl-4">
                    <div class="card shadow-sm">
                        <div class="card-header py-3">
                            <h1 class="h5 mb-0">
                                <i class="fas fa-layer-group me-2 text-body-secondary"></i>Categories
                            </h1>
                        </div>
                        <div class="card-body p-4">
                            <p class="small text-body-secondary">
                                Create categories for development docs, architecture, runbooks, and other common Confluence-style collections.
                            </p>

                            <form method="POST" action="{{ route('workspaces.categories.store', $workspace) }}">
                                @csrf
                                <div class="mb-3">
                                    <x-label for="name" value="{{ __('Category Name') }}" />
                                    <x-input id="name" name="name" type="text" class="mt-1 block w-full"
                                             value="{{ old('name') }}" placeholder="e.g. Release Notes" />
                                    <x-input-error for="name" class="mt-1" />
                                </div>
                                <div class="mb-3">
                                    <x-label for="description" value="{{ __('Description') }}" />
                                    <textarea id="description" name="description" rows="3"
                                              class="form-control mt-1">{{ old('description') }}</textarea>
                                    <x-input-error for="description" class="mt-1" />
                                </div>
                                <div class="mb-3">
                                    <x-label for="color" value="{{ __('Color') }}" />
                                    <input type="color" id="color" name="color"
                                           value="{{ old('color', '#2563eb') }}"
                                           class="form-control form-control-color mt-1 @error('color') is-invalid @enderror"
                                           style="width:4rem;height:2.5rem;" />
                                    <x-input-error for="color" class="mt-1" />
                                </div>
                                <x-button>
                                    <i class="fas fa-plus me-1"></i> Create Category
                                </x-button>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow-sm mt-4">
                        <div class="card-header py-3 d-flex align-items-center justify-content-between gap-3">
                            <h2 class="h6 fw-semibold mb-0">
                                <i class="fas fa-wand-magic-sparkles me-2 text-body-secondary"></i>Suggested Defaults
                            </h2>
                            @if($suggestedCategories->isNotEmpty())
                                <form method="POST" action="{{ route('workspaces.categories.store', $workspace) }}">
                                    @csrf
                                    @foreach($suggestedCategories as $suggestion)
                                        <input type="hidden" name="built_in_keys[]" value="{{ $suggestion['key'] }}">
                                    @endforeach
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                                        Add Starter Set
                                    </button>
                                </form>
                            @endif
                        </div>
                        <div class="card-body p-4">
                            @if($suggestedCategories->isEmpty())
                                <p class="small text-body-secondary mb-0">
                                    The recommended starter categories are already available in this workspace.
                                </p>
                            @else
                                <div class="d-flex flex-column gap-3">
                                    @foreach($suggestedCategories as $suggestion)
                                        <div class="border rounded-3 p-3">
                                            <div class="d-flex align-items-start justify-content-between gap-3">
                                                <div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="rounded-circle d-inline-block"
                                                              style="width:0.8rem;height:0.8rem;background-color:{{ $suggestion['color'] }};"></span>
                                                        <span class="fw-medium">{{ $suggestion['name'] }}</span>
                                                    </div>
                                                    <p class="small text-body-secondary mb-0 mt-2">{{ $suggestion['description'] }}</p>
                                                </div>
                                                <form method="POST" action="{{ route('workspaces.categories.store', $workspace) }}">
                                                    @csrf
                                                    <input type="hidden" name="built_in_key" value="{{ $suggestion['key'] }}">
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Add</button>
                                                </form>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-xl-8">
                    <div class="card shadow-sm">
                        <div class="card-header py-3 d-flex align-items-center justify-content-between">
                            <h2 class="h6 fw-semibold mb-0">
                                <i class="fas fa-folder-open me-2 text-body-secondary"></i>Workspace Categories
                            </h2>
                            <span class="badge bg-secondary-subtle text-secondary-emphasis">
                                {{ $categories->count() }} {{ Str::plural('category', $categories->count()) }}
                            </span>
                        </div>
                        <div class="card-body p-4">
                            @if($categories->isEmpty())
                                <div class="text-center py-5 text-body-secondary">
                                    <i class="fas fa-layer-group fa-2x mb-3 d-block opacity-25"></i>
                                    <p class="mb-1">No workspace categories yet.</p>
                                    <p class="small mb-0">Create one manually or add the recommended starter set.</p>
                                </div>
                            @else
                                <div class="d-flex flex-column gap-3">
                                    @foreach($categories as $category)
                                        <div class="border rounded-3 p-3">
                                            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                    <span class="rounded-circle d-inline-block"
                                                          style="width:0.85rem;height:0.85rem;background-color:{{ $category->color ?? '#94a3b8' }};"></span>
                                                    <span class="fw-medium">{{ $category->name }}</span>
                                                </div>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <span class="badge bg-primary-subtle text-primary-emphasis">{{ $category->pages_count }} {{ Str::plural('page', $category->pages_count) }}</span>
                                                    <span class="badge bg-success-subtle text-success-emphasis">{{ $category->diagrams_count }} {{ Str::plural('diagram', $category->diagrams_count) }}</span>
                                                    <span class="badge bg-warning-subtle text-warning-emphasis">{{ $category->scripts_count }} {{ Str::plural('script', $category->scripts_count) }}</span>
                                                </div>
                                            </div>

                                            <form method="POST" action="{{ route('workspaces.categories.update', [$workspace, $category]) }}" id="category-update-{{ $category->id }}">
                                                @csrf
                                                @method('PUT')
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <x-label for="category_name_{{ $category->id }}" value="{{ __('Name') }}" />
                                                        <x-input id="category_name_{{ $category->id }}" name="name" type="text"
                                                                 class="mt-1 block w-full"
                                                                 value="{{ $category->name }}" />
                                                    </div>
                                                    <div class="col-md-6">
                                                        <x-label for="category_description_{{ $category->id }}" value="{{ __('Description') }}" />
                                                        <textarea id="category_description_{{ $category->id }}" name="description" rows="2"
                                                                  class="form-control mt-1">{{ $category->description }}</textarea>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <x-label for="category_color_{{ $category->id }}" value="{{ __('Color') }}" />
                                                        <input type="color" id="category_color_{{ $category->id }}" name="color"
                                                               value="{{ $category->color ?? '#2563eb' }}"
                                                               class="form-control form-control-color mt-1"
                                                               style="width:100%;height:2.5rem;" />
                                                    </div>
                                                </div>
                                            </form>
                                            <div class="d-flex justify-content-between align-items-center mt-3">
                                                <form method="POST" action="{{ route('workspaces.categories.destroy', [$workspace, $category]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                                            onclick="return confirm('Delete this category? It will be removed from any items using it.')">
                                                        <i class="fas fa-trash me-1"></i> Delete
                                                    </button>
                                                </form>
                                                <button type="submit" form="category-update-{{ $category->id }}" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-save me-1"></i> Save Changes
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
