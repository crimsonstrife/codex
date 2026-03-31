import './bootstrap';
import './script-editor';
import './toc';
import './workspace-view-toggle';
import { initPageTreeSort, initPageTreeCollapse } from './page-tree-sort';
document.addEventListener('DOMContentLoaded', () => {
    initPageTreeCollapse(); // feat 2.5 — must run before sort so subtrees exist
    initPageTreeSort();     // feat 5.2
    initDrawioEmbeds();
});

// Bootstrap (CSS + JS)
// Using the ESM entry so Bootstrap's classes are available as window.bootstrap
// for inline scripts. @popperjs/core is available as a transitive dependency.
import 'bootstrap/dist/css/bootstrap.min.css';
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

// FontAwesome Free
import '@fortawesome/fontawesome-free/css/all.min.css';

import mermaid from 'mermaid';

// Auto-render any .mermaid elements on page load
mermaid.initialize({
    startOnLoad: true,
    theme: document.documentElement.classList.contains('dark') ? 'dark' : 'default',
    securityLevel: 'strict',
    flowchart: { useMaxWidth: true, htmlLabels: true },
    mindmap: { useMaxWidth: true },
});

// Expose globally so Blade views can call mermaid.render() for live previews
window.mermaid = mermaid;

function initDrawioEmbeds(root = document) {
    root.querySelectorAll('[data-codex-drawio]').forEach((container) => {
        if (container.dataset.codexDrawioReady === 'true') {
            return;
        }

        const xml = container.dataset.codexDrawio;
        const drawioUrl = container.dataset.codexDrawioUrl;

        if (!xml || !drawioUrl) {
            return;
        }

        const iframe = document.createElement('iframe');
        iframe.loading = 'lazy';
        iframe.style.width = '100%';
        iframe.style.height = '100%';
        iframe.style.border = 'none';
        iframe.src = `${drawioUrl}?embed=1&spin=1&xml=${encodeURIComponent(xml)}&toolbar=0&lightbox=1`;

        container.replaceChildren(iframe);
        container.dataset.codexDrawioReady = 'true';
    });
}

window.initDrawioEmbeds = initDrawioEmbeds;
