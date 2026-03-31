/**
 * TinyMCE plugin: diagram-embeds
 *
 * Provides {{diagram:UUID}} autocomplete in the TinyMCE rich-text editor.
 * When the user types {{diagram:, a list of matching diagrams in the current
 * workspace appears. Selecting one inserts the token as plain text so it can
 * be rendered later by the page-content pipeline.
 *
 * Requires the editor option `codex_workspace_id` to be set so suggestions
 * stay scoped to the correct workspace.
 *
 * Fetches diagram suggestions from GET /api/diagrams/search?q=...&workspace_id=...
 */
(function () {
    tinymce.PluginManager.add('diagram-embeds', function (editor) {
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
                    console.warn('diagram-embeds: request failed', res.status, url);
                    return [];
                }
                return res.json();
            } catch (e) {
                console.warn('diagram-embeds: fetch error', e);
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

        editor.ui.registry.addAutocompleter('diagramEmbeds', {
            trigger: '{{diagram:',
            minChars: 1,
            columns: 1,
            fetch: async function (pattern) {
                var q = termOf(pattern);
                var list = await fetchJSON(buildUrl('/api/diagrams/search', {
                    q: q,
                    workspace_id: workspaceId,
                }));

                return list.map(function (diagram) {
                    var type = diagram.type === 'drawio' ? 'draw.io' : 'Mermaid';

                    return {
                        type: 'menuitem',
                        text: diagram.label + ' (' + type + ')',
                        value: JSON.stringify(diagram),
                    };
                });
            },
            onAction: function (api, rng, value) {
                var v = toObj(value);
                var diagramId = v.id || '';

                if (!diagramId) return;

                if (api && typeof api.replace === 'function') {
                    api.replace('{{diagram:' + diagramId + '}}');
                } else {
                    editor.insertContent('{{diagram:' + diagramId + '}}');
                    if (api && typeof api.hide === 'function') api.hide();
                }
            },
        });

        editor.ui.registry.addButton('diagramEmbed', {
            text: 'Diagram',
            tooltip: 'Embed workspace diagram  {{diagram:UUID}}',
            onAction: function () {
                editor.insertContent('{{diagram:');
            },
        });
    });
})();
