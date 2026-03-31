/**
 * feat 2.5 — Page tree collapse / expand
 *
 * Each subtree container has id="subtree-{pageId}". Its toggle button carries
 * data-subtree="subtree-{pageId}". Collapsed state is persisted in localStorage
 * keyed by the workspace ID so the user's open/closed choices survive navigation.
 *
 * The active page's ancestors are always forced open on load so the current
 * page is never hidden inside a collapsed branch.
 */
export function initPageTreeCollapse() {
    const sidebar     = document.querySelector('[data-workspace-id]');
    const workspaceId = sidebar?.dataset.workspaceId ?? 'ws';

    function storageKey(subtreeId) {
        return `codex_tree_${workspaceId}_${subtreeId}`;
    }

    // Collect IDs of all subtrees that contain the active page so we can
    // force them open regardless of saved state.
    const activeItem = document.querySelector('.sortable-item .active');
    const ancestorSubtreeIds = new Set();
    if (activeItem) {
        let el = activeItem.closest('.tree-subtree');
        while (el) {
            ancestorSubtreeIds.add(el.id);
            el = el.parentElement?.closest('.tree-subtree');
        }
    }

    document.querySelectorAll('.tree-toggle').forEach(btn => {
        const subtreeId = btn.dataset.subtree;
        const subtree   = document.getElementById(subtreeId);
        if (!subtree) return;

        const isAncestor = ancestorSubtreeIds.has(subtreeId);
        const saved      = localStorage.getItem(storageKey(subtreeId));

        // Ancestors are always open; others restore saved state (default open)
        const isOpen = isAncestor ? true : (saved !== 'false');

        applyState(btn, subtree, isOpen);

        btn.addEventListener('click', () => {
            const nowOpen = btn.getAttribute('aria-expanded') === 'true';
            const next    = !nowOpen;
            applyState(btn, subtree, next);
            localStorage.setItem(storageKey(subtreeId), next);
        });
    });

    function applyState(btn, subtree, open) {
        btn.setAttribute('aria-expanded', open);
        subtree.style.display = open ? '' : 'none';
        const chevron = btn.querySelector('.tree-chevron');
        if (chevron) chevron.style.transform = open ? '' : 'rotate(-90deg)';
    }
}

/**
 * feat 5.2 — Page tree drag-and-drop reorder
 *
 * Initialises SortableJS on every `.sortable-level` container in the sidebar.
 * Each level shares the group name 'pages' so pages can be moved between
 * parent levels (reparenting) as well as reordered within a level.
 *
 * On drop the module sends a PATCH to the page's move URL with:
 *   { parent_id, before_id }
 *
 * A small status indicator ("Saving…" / "✓" / "✗") is shown in the sidebar
 * header while the request is in flight.
 */

let sortablePromise;

function loadSortable() {
    if (!sortablePromise) {
        sortablePromise = import('sortablejs').then((module) => module.default);
    }

    return sortablePromise;
}

export async function initPageTreeSort() {
    const levels = document.querySelectorAll('.sortable-level');
    if (!levels.length) return;

    const Sortable = await loadSortable();

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const indicator = document.getElementById('page-sort-indicator');

    function setIndicator(state) {
        if (!indicator) return;
        indicator.textContent = state === 'saving' ? 'Saving…'
                              : state === 'ok'     ? '✓'
                              : state === 'error'  ? '✗'
                              : '';
        indicator.className = 'page-sort-indicator ms-1 small '
            + (state === 'saving' ? 'text-body-secondary'
             : state === 'ok'     ? 'text-success'
             : state === 'error'  ? 'text-danger'
             : '');
        if (state === 'ok' || state === 'error') {
            setTimeout(() => setIndicator(''), 2000);
        }
    }

    levels.forEach(level => {
        Sortable.create(level, {
            group:     { name: 'pages', pull: true, put: true },
            handle:    '.drag-handle',
            animation: 150,
            fallbackOnBody: true,
            swapThreshold: 0.65,

            onEnd(evt) {
                const movedEl  = evt.item;
                const moveUrl  = movedEl.dataset.moveUrl;

                // Determine the new parent from the container the item landed in
                const newLevel = evt.to;
                const parentId = newLevel.dataset.parentId ?? null;

                // Determine the sibling that now sits immediately after the moved item
                const nextSibling = movedEl.nextElementSibling;
                const beforeId    = nextSibling?.dataset?.pageId ?? null;

                if (!moveUrl) return;

                setIndicator('saving');

                fetch(moveUrl, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type':     'application/json',
                        'Accept':           'application/json',
                        'X-CSRF-TOKEN':     csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        parent_id: parentId || null,
                        before_id: beforeId || null,
                    }),
                })
                .then(res => {
                    if (!res.ok) throw new Error('Server error ' + res.status);
                    setIndicator('ok');
                })
                .catch(() => {
                    setIndicator('error');
                    // Revert the DOM move on failure by reloading the tree
                    // (simplest reliable revert for a nested tree)
                    window.location.reload();
                });
            },
        });
    });
}
