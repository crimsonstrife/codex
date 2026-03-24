/**
 * Sprint 11.2 — Workspace Page Table/List View toggle
 *
 * Reads the current view preference from localStorage and switches between:
 *   - "tree"  (default) — home page content shown in main column
 *   - "table" — flat sortable page table shown in main column
 *
 * State key: codex_view_{workspaceId}
 */
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('[data-workspace-id]');
    if (!sidebar) return;

    const workspaceId  = sidebar.dataset.workspaceId;
    const defaultView  = document.getElementById('workspace-main-default');
    const tableView    = document.getElementById('workspace-main-table');
    const tocWrapper   = document.getElementById('toc-wrapper');
    const btnTree      = document.getElementById('view-btn-tree');
    const btnTable     = document.getElementById('view-btn-table');

    if (!defaultView || !tableView || !btnTree || !btnTable) return;

    const STORAGE_KEY = 'codex_view_' + workspaceId;

    function activateTree() {
        defaultView.classList.remove('d-none');
        tableView.classList.add('d-none');
        if (tocWrapper) tocWrapper.classList.remove('view-force-hidden');
        btnTree.classList.add('active');
        btnTable.classList.remove('active');
        btnTree.setAttribute('aria-pressed', 'true');
        btnTable.setAttribute('aria-pressed', 'false');
        localStorage.setItem(STORAGE_KEY, 'tree');
    }

    function activateTable() {
        defaultView.classList.add('d-none');
        tableView.classList.remove('d-none');
        if (tocWrapper) tocWrapper.classList.add('view-force-hidden');
        btnTree.classList.remove('active');
        btnTable.classList.add('active');
        btnTree.setAttribute('aria-pressed', 'false');
        btnTable.setAttribute('aria-pressed', 'true');
        localStorage.setItem(STORAGE_KEY, 'table');
    }

    btnTree.addEventListener('click', activateTree);
    btnTable.addEventListener('click', activateTable);

    // Restore persisted view preference
    if (localStorage.getItem(STORAGE_KEY) === 'table') {
        activateTable();
    }

    // ── Column sorting for the page table ─────────────────────────────────────
    const table = document.getElementById('pages-table');
    if (!table) return;

    let sortCol = 'updated';
    let sortDir = 'desc';

    // Render initial sort indicator
    renderSortIndicators();

    table.querySelectorAll('th[data-sort]').forEach(th => {
        th.style.cursor = 'pointer';
        th.addEventListener('click', () => {
            const col = th.dataset.sort;
            if (sortCol === col) {
                sortDir = sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                sortCol = col;
                // Dates default desc (newest first); everything else asc
                sortDir = (col === 'updated') ? 'desc' : 'asc';
            }
            sortTable();
            renderSortIndicators();
        });
    });

    function sortTable() {
        const tbody = table.querySelector('tbody');
        const rows  = Array.from(tbody.querySelectorAll('tr'));

        rows.sort((a, b) => {
            const key  = 'sort' + sortCol.charAt(0).toUpperCase() + sortCol.slice(1);
            const aVal = a.dataset[key] ?? '';
            const bVal = b.dataset[key] ?? '';
            const cmp  = aVal.localeCompare(bVal, undefined, { numeric: true, sensitivity: 'base' });
            return sortDir === 'asc' ? cmp : -cmp;
        });

        rows.forEach(r => tbody.appendChild(r));
    }

    function renderSortIndicators() {
        table.querySelectorAll('th[data-sort]').forEach(th => {
            // Remove old icon
            const old = th.querySelector('.sort-icon');
            if (old) old.remove();

            const icon = document.createElement('i');
            if (th.dataset.sort === sortCol) {
                icon.className = 'fas fa-sort-' + (sortDir === 'asc' ? 'up' : 'down')
                    + ' ms-1 text-primary small sort-icon';
            } else {
                icon.className = 'fas fa-sort ms-1 text-body-secondary small sort-icon opacity-50';
            }
            th.appendChild(icon);
        });
    }
});
