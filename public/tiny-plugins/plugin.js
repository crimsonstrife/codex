/**
 * TinyMCE plugin: wiki-links
 *
 * Provides [[Page Title]] autocomplete in the TinyMCE rich-text editor.
 * When the user types [[, a list of matching pages in the current workspace
 * appears. Selecting one inserts [[Page Title]] as plain text — which is then
 * resolved to a hyperlink when the page is rendered.
 *
 * Requires the editor option `codex_workspace_id` to be set so suggestions
 * stay scoped to the correct workspace.
 *
 * Fetches page suggestions from GET /api/pages/search?q=...&workspace_id=...
 */
(function () {
    tinymce.PluginManager.add('wiki-links', function (editor) {

        var workspaceId = editor.getParam('codex_workspace_id', null);

        function buildUrl(path, params) {
            var u = new URL(path, location.origin);
            params = params || {};
            for (var k in params) {
                var v = params[k];
                if (v !== undefined && v !== null && String(v) !== '') {
                    u.searchParams.set(k, String(v));
                }
            }
            return u.toString();
        }

        async function fetchJSON(url) {
            try {
                var res = await fetch(url, {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                if (!res.ok) {
                    console.warn('wiki-links: request failed', res.status, url);
                    return [];
                }
                return res.json();
            } catch (e) {
                console.warn('wiki-links: fetch error', e);
                return [];
            }
        }

        function termOf(pattern) {
            if (typeof pattern === 'string') return pattern;
            if (pattern && typeof pattern === 'object') {
                return pattern.term ?? pattern.query ?? pattern.text ?? '';
            }
            return '';
        }

        function toObj(v) {
            try { return typeof v === 'string' ? JSON.parse(v) : (v || {}); }
            catch { return {}; }
        }

        // Autocompleter: trigger on [[ (double open-bracket)
        editor.ui.registry.addAutocompleter('wikiLinks', {
            trigger: '[[',
            minChars: 1,
            columns: 1,
            fetch: async function (pattern) {
                var q = termOf(pattern);
                var list = await fetchJSON(buildUrl('/api/pages/search', {
                    q: q,
                    workspace_id: workspaceId,
                }));
                return list.map(function (p) {
                    return {
                        type: 'menuitem',
                        text: p.label,
                        value: JSON.stringify(p),
                    };
                });
            },
            onAction: function (api, rng, value) {
                var v = toObj(value);
                var title = v.label || '';

                // Replace the typed [[<query> text with [[Full Title]]
                // api.replace() replaces the trigger + typed text.
                if (api && typeof api.replace === 'function') {
                    api.replace('[[' + title + ']]');
                } else {
                    editor.insertContent('[[' + title + ']]');
                    if (api && typeof api.hide === 'function') api.hide();
                }
            },
        });

        // Toolbar button: insert the opening [[ to trigger the autocomplete
        editor.ui.registry.addButton('wikiLink', {
            icon: 'link',
            tooltip: 'Insert wiki link  [[Page Title]]',
            onAction: function () {
                editor.insertContent('[[');
            },
        });
    });
})();
