{{-- Recursive page tree partial — supports drag-and-drop reorder (feat 5.2) and collapse/expand (feat 2.5) --}}
{{--
    Params:
      pages       — nested collection (toTree() result)
      workspace   — Workspace model
      depth       — integer nesting depth (0 = root)
      parentId    — UUID of parent page, or null for root level
      sortable    — bool, default false; set true for users who have permission to reorder
      activePage  — Page model currently being viewed (optional)
--}}
@php $sortable = $sortable ?? false; @endphp

<div class="sortable-level" data-parent-id="{{ $parentId ?? '' }}">
@foreach($pages as $page)
@php $isActive = isset($activePage) && $activePage->id === $page->id; @endphp
<div class="sortable-item"
     data-page-id="{{ $page->id }}"
     data-move-url="{{ route('workspaces.pages.move', [$workspace, $page]) }}"
     style="padding-left: {{ $depth * 1 }}rem;">
    <div class="d-flex align-items-center">
        @if($sortable)
        <span class="drag-handle text-body-secondary px-1 flex-shrink-0"
              title="Drag to reorder"
              style="cursor:grab;font-size:0.65rem;opacity:0.35;">
            <i class="fas fa-grip-vertical"></i>
        </span>
        @endif

        @if($page->children->isNotEmpty())
            {{-- feat 2.5: toggle button — chevron rotates when subtree collapses --}}
            <button type="button"
                    class="tree-toggle btn btn-link p-0 border-0 flex-shrink-0 text-body-secondary"
                    data-subtree="subtree-{{ $page->id }}"
                    aria-expanded="true"
                    aria-label="Toggle {{ $page->title }} sub-pages"
                    style="width:1.1rem;line-height:1;">
                <i class="fas fa-chevron-down fa-xs tree-chevron" style="transition:transform .15s;"></i>
            </button>
        @else
            <span class="flex-shrink-0" style="width:1.1rem;line-height:1;text-align:center;">
                <i class="fas fa-file fa-xs text-body-secondary"></i>
            </span>
        @endif

        <a href="{{ route('workspaces.pages.show', [$workspace, $page]) }}"
           class="list-group-item list-group-item-action d-flex align-items-center gap-1 py-1 small flex-grow-1 border-0 {{ $isActive ? 'active' : '' }}">
            <span class="text-truncate">{{ $page->title }}</span>
        </a>
    </div>

    @if($page->children->isNotEmpty())
        <div id="subtree-{{ $page->id }}" class="tree-subtree">
            @include('pages._page_tree', [
                'pages'      => $page->children,
                'workspace'  => $workspace,
                'depth'      => $depth + 1,
                'parentId'   => $page->id,
                'sortable'   => $sortable,
                'activePage' => $activePage ?? null,
            ])
        </div>
    @endif
</div>
@endforeach
</div>
