@php
    $selectorIdPrefix = $selectorIdPrefix ?? 'category-selector';
    $selectedCategoryIds = collect(old('category_ids', $selectedCategoryIds ?? []))
        ->map(fn ($id) => (string) $id)
        ->all();
@endphp

<div class="mb-4">
    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
        <div>
            <x-label value="{{ __('Categories') }}" />
            <p class="small text-body-secondary mb-0 mt-1">
                Organize this content with existing categories, or create a new workspace category inline.
            </p>
        </div>
        <a href="{{ route('workspaces.categories.index', $workspace) }}"
           class="small text-decoration-none">
            Manage categories
        </a>
    </div>

    @if($categories->isNotEmpty())
        <div class="row g-2 mt-2">
            @foreach($categories as $category)
                @php $categoryId = (string) $category->id; @endphp
                <div class="col-md-6">
                    <label for="{{ $selectorIdPrefix }}_{{ $categoryId }}"
                           class="border rounded-3 p-3 d-flex gap-2 h-100">
                        <input class="form-check-input mt-1" type="checkbox" name="category_ids[]"
                               id="{{ $selectorIdPrefix }}_{{ $categoryId }}" value="{{ $categoryId }}"
                               {{ in_array($categoryId, $selectedCategoryIds, true) ? 'checked' : '' }} />
                        <span class="d-block">
                            <span class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="rounded-circle d-inline-block"
                                      style="width:0.7rem;height:0.7rem;background-color:{{ $category->color ?? '#94a3b8' }};"></span>
                                <span class="fw-medium">{{ $category->name }}</span>
                                @if(is_null($category->workspace_id))
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis">Shared</span>
                                @endif
                            </span>
                            @if($category->description)
                                <span class="small text-body-secondary d-block mt-1">{{ $category->description }}</span>
                            @endif
                        </span>
                    </label>
                </div>
            @endforeach
        </div>
    @else
        <div class="border rounded-3 px-3 py-3 mt-2 text-body-secondary small">
            No categories exist for this workspace yet. You can create one here or add a starter set from category management.
        </div>
    @endif

    <div class="border rounded-3 p-3 mt-3 bg-body-tertiary">
        <div class="small fw-semibold text-uppercase text-body-secondary">Create a New Category</div>
        <p class="small text-body-secondary mb-0 mt-1">
            If the name already exists in this workspace, we’ll reuse it and assign it automatically.
        </p>

        <div class="row g-3 mt-1">
            <div class="col-md-5">
                <x-label for="{{ $selectorIdPrefix }}_new_category_name" value="{{ __('Name') }}" />
                <x-input id="{{ $selectorIdPrefix }}_new_category_name" name="new_category_name" type="text"
                         class="mt-1 block w-full" value="{{ old('new_category_name') }}"
                         placeholder="e.g. Architecture" />
                <x-input-error for="new_category_name" class="mt-1" />
            </div>
            <div class="col-md-5">
                <x-label for="{{ $selectorIdPrefix }}_new_category_description" value="{{ __('Description') }}" />
                <x-input id="{{ $selectorIdPrefix }}_new_category_description" name="new_category_description" type="text"
                         class="mt-1 block w-full" value="{{ old('new_category_description') }}"
                         placeholder="Optional short summary" />
                <x-input-error for="new_category_description" class="mt-1" />
            </div>
            <div class="col-md-2">
                <x-label for="{{ $selectorIdPrefix }}_new_category_color" value="{{ __('Color') }}" />
                <input type="color" id="{{ $selectorIdPrefix }}_new_category_color" name="new_category_color"
                       value="{{ old('new_category_color', '#2563eb') }}"
                       class="form-control form-control-color mt-1 @error('new_category_color') is-invalid @enderror"
                       style="width: 100%; height: 2.5rem;" />
                <x-input-error for="new_category_color" class="mt-1" />
            </div>
        </div>
    </div>
</div>
