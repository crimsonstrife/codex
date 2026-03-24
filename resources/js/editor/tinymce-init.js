/**
 * TinyMCE initialiser for Codex page editor.
 * Adapted from the Forge project (resources/js/editor/tinymce-init.js).
 *
 * Usage (in a Blade view):
 *   <script>
 *     document.addEventListener('DOMContentLoaded', () => {
 *       const editor = window.tinyEditor({ elId: 'content', height: 600 });
 *       editor.init();
 *     });
 *   </script>
 */
window.tinyEditor = function tinyEditor(opts) {
    const st = {
        id: opts.elId,
        height: Number(opts.height ?? 500),
        baseUrl: String(opts.baseUrl ?? '/vendor/tinymce'),
        toolbar: String(
            opts.toolbar ??
                'undo redo | styles | bold italic underline strikethrough | ' +
                'link image | bullist numlist | blockquote hr | ' +
                'alignleft aligncenter alignright | table | removeformat | code'
        ),
        pluginsStr: String(opts.plugins ?? 'link lists code image table blockquote autolink hr'),
        skin: opts.skin ?? 'oxide',
        contentCss: opts.contentCss ?? 'default',
        externalPlugins: opts.externalPlugins ?? {},
        suffix: '.min',
        initial: opts.initial ?? null,
        // feat 1.9: optional URL for uploading images directly from the editor.
        // When set, TinyMCE will POST the image here and embed the returned URL.
        imageUploadUrl: opts.imageUploadUrl ?? null,
        calloutPluginUrl: opts.calloutPluginUrl ?? null,
        // feat 3.4: workspace UUID forwarded to wiki-links plugin for scoped search.
        codexWorkspaceId: opts.codexWorkspaceId ?? null,
    };

    const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

    const load = (src) =>
        new Promise((res, rej) => {
            const { href } = new URL(src, location.origin);
            if ([...document.scripts].some((s) => s.src === href)) {
                return res();
            }
            const s = document.createElement('script');
            s.src = src;
            s.onload = res;
            s.onerror = () => rej(new Error(src));
            document.head.appendChild(s);
        });

    const ensureCore = async () => {
        try {
            await load(`${st.baseUrl}/tinymce.min.js`);
            st.suffix = '.min';
        } catch {
            await load(`${st.baseUrl}/tinymce.js`);
            st.suffix = '';
        }
        if (!window.tinymce) {
            await sleep(0);
        }
    };

    // Shim: accept legacy { ch: '@' } by converting to { trigger: '@' } before plugins run
    const patchAutocompleter = () => {
        const reg = window.tinymce?.ui?.registry;
        if (!reg || reg.__codexPatched) {
            return;
        }
        const orig = reg.addAutocompleter.bind(reg);
        reg.addAutocompleter = (id, spec) => {
            if (spec && !('trigger' in spec) && 'ch' in spec) {
                spec.trigger = spec.ch;
            }
            return orig(id, spec);
        };
        reg.__codexPatched = true;
    };

    const ensureExternal = async () => {
        for (const [pid, url] of Object.entries(st.externalPlugins)) {
            if (!url) continue;
            if (window.tinymce?.PluginManager?.lookup?.[pid]) continue;
            await load(url);
        }
    };

    const normalizePlugins = () => {
        const want = new Set(st.pluginsStr.split(/\s+/).filter(Boolean));
        Object.keys(st.externalPlugins).forEach((id) => want.add(id));
        return [...want].join(' ');
    };

    return {
        setTheme({ skin, contentCss }) {
            if (skin) st.skin = skin;
            if (contentCss) st.contentCss = contentCss;
        },

        async init(initialHtml = undefined) {
            const node = document.getElementById(st.id);
            if (!node) return;

            await ensureCore();
            if (!window.tinymce) {
                console.warn('TinyMCE not found at', st.baseUrl);
                return;
            }

            patchAutocompleter();
            await ensureExternal();

            try { tinymce.remove('#' + st.id); } catch (_) {}

            // feat 1.9: image upload handler — uses fetch so we can inject
            // the CSRF token header (images_upload_url can't do this natively).
            const imageUploadHandler = st.imageUploadUrl
                ? async (blobInfo) => {
                    const form = new FormData();
                    form.append('file', blobInfo.blob(), blobInfo.filename());
                    const res = await fetch(st.imageUploadUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                            'Accept': 'application/json',
                        },
                        body: form,
                    });
                    if (!res.ok) {
                        const msg = await res.text().catch(() => 'Upload failed');
                        throw new Error(msg);
                    }
                    const data = await res.json();
                    return data.location;
                }
                : undefined;

            tinymce.init({
                selector: `#${st.id}`,
                base_url: st.baseUrl,
                suffix: st.suffix,
                license_key: 'gpl',
                menubar: false,
                branding: false,
                height: st.height,
                plugins: normalizePlugins(),
                toolbar: st.toolbar,
                skin: st.skin,
                content_css: st.contentCss,
                // feat 3.4: workspace ID forwarded to wiki-links plugin via TinyMCE init options
                ...(st.codexWorkspaceId ? { codex_workspace_id: st.codexWorkspaceId } : {}),
                content_style: `
                    .callout { border-left: 4px solid; padding: 0.75rem 1rem; margin: 1rem 0; border-radius: 0 0.375rem 0.375rem 0; }
                    .callout p:last-child { margin-bottom: 0; }
                    .callout::before { display: block; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.35rem; opacity: 0.75; }
                    .callout-note { border-color: #0d6efd; background: rgba(13,110,253,0.08); }
                    .callout-note::before { content: "Note"; color: #0d6efd; }
                    .callout-tip { border-color: #198754; background: rgba(25,135,84,0.08); }
                    .callout-tip::before { content: "Tip"; color: #198754; }
                    .callout-warning { border-color: #fd7e14; background: rgba(253,126,20,0.08); }
                    .callout-warning::before { content: "Warning"; color: #fd7e14; }
                    .callout-danger { border-color: #dc3545; background: rgba(220,53,69,0.08); }
                    .callout-danger::before { content: "Danger"; color: #dc3545; }
                    .callout-success { border-color: #20c997; background: rgba(32,201,151,0.08); }
                    .callout-success::before { content: "Success"; color: #20c997; }
                    .callout-design-decision { border-color: #6f42c1; background: rgba(111,66,193,0.08); }
                    .callout-design-decision::before { content: "Design Decision"; color: #6f42c1; }
                    .wiki-link { color: #0d6efd; text-decoration: underline; }
                    .wiki-link-broken { color: #dc3545; text-decoration: underline dotted; cursor: help; }
                `,
                ...(imageUploadHandler ? {
                    images_upload_handler: imageUploadHandler,
                    automatic_uploads: true,
                    images_reuse_filename: false,
                } : {}),
                setup: (ed) => {
                    ed.on('init', () => {
                        const val = initialHtml !== undefined ? initialHtml : st.initial;
                        if (val != null) ed.setContent(val);
                    });

                    // Sync TinyMCE content back to the hidden textarea before form submit
                    const form = node.closest('form');
                    if (form) {
                        form.addEventListener('submit', () => {
                            node.value = ed.getContent();
                        }, { capture: true });
                    }
                },
            });
        },

        destroy() {
            try {
                if (window.tinymce) tinymce.remove('#' + st.id);
            } catch (_) {}
        },
    };
};
