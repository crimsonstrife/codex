import './bootstrap';
import './script-editor';
import './toc';
import './workspace-view-toggle';
import { initPageTreeSort, initPageTreeCollapse } from './page-tree-sort';

let mermaidPromise;

function loadMermaid() {
    if (!mermaidPromise) {
        mermaidPromise = import('mermaid').then(({ default: mermaid }) => {
            mermaid.initialize({
                startOnLoad: false,
                theme: document.documentElement.classList.contains('dark') ? 'dark' : 'default',
                securityLevel: 'strict',
                flowchart: { useMaxWidth: true, htmlLabels: true },
                mindmap: { useMaxWidth: true },
            });

            window.mermaid = mermaid;

            return mermaid;
        });
    }

    return mermaidPromise;
}

async function initMermaidDiagrams(root = document) {
    const nodes = [...root.querySelectorAll('.mermaid')];
    if (!nodes.length) {
        return null;
    }

    const mermaid = await loadMermaid();
    await mermaid.run({ nodes });

    return mermaid;
}

document.addEventListener('DOMContentLoaded', async () => {
    initPageTreeCollapse(); // feat 2.5 — must run before sort so subtrees exist
    initDrawioEmbeds();
    await Promise.allSettled([
        initPageTreeSort(),    // feat 5.2
        initMermaidDiagrams(),
    ]);
});

// Bootstrap (CSS + JS)
// Using the ESM entry so Bootstrap's classes are available as window.bootstrap
// for inline scripts. @popperjs/core is available as a transitive dependency.
import 'bootstrap/dist/css/bootstrap.min.css';
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

// FontAwesome Free
import '@fortawesome/fontawesome-free/css/all.min.css';

window.loadMermaid = loadMermaid;
window.initMermaidDiagrams = initMermaidDiagrams;

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
