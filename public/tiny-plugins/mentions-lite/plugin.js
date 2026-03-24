/**
 * TinyMCE plugin: mentions-lite
 *
 * Provides @user autocompletion in the TinyMCE editor.
 * Adapted from the Forge project (resources/tiny-plugins/mentions-lite/plugin.js).
 *
 * Fetches user suggestions from GET /api/mentions/users?q=<query>.
 * Inserts an <a href="#" data-mention-type="user" data-mention-id="...">@Name</a> link.
 */
(function () {
    tinymce.PluginManager.add('mentions-lite', function (editor) {

        function termOf(pattern) {
            if (typeof pattern === 'string') return pattern;
            if (pattern && typeof pattern === 'object') {
                return pattern.term ?? pattern.query ?? pattern.text ?? '';
            }
            return '';
        }

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
            var res = await fetch(url, {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) {
                console.warn('mentions-lite request failed', res.status, url);
                return [];
            }
            return res.json();
        }

        function toObj(v) {
            try { return typeof v === 'string' ? JSON.parse(v) : (v || {}); }
            catch { return {}; }
        }

        function isApi(a)  { return a && typeof a === 'object' && (typeof a.hide === 'function' || typeof a.replace === 'function'); }
        function isRange(a){ return a && typeof a === 'object' && (a.startContainer || a.commonAncestorContainer || a.nativeRange); }

        function resolveActionArgs(a, b, c) {
            var api = null, rng = null, value = null, args = [a, b, c];
            for (var i = 0; i < 3; i++) {
                var x = args[i];
                if (typeof x === 'string' && value === null) { value = x; continue; }
                if (x && typeof x === 'object') {
                    if (isApi(x)) { api = x; continue; }
                    if (isRange(x)) { rng = x; continue; }
                    if (typeof x.value === 'string' && value === null) { value = x.value; continue; }
                }
            }
            return { api, rng, value };
        }

        function insertViaApiOrEditor(api, rng, html) {
            if (api && typeof api.replace === 'function') { api.replace(html); return; }
            if (rng && editor.selection && typeof editor.selection.setRng === 'function') {
                editor.selection.setRng(rng);
            }
            editor.insertContent(html);
            if (api && typeof api.hide === 'function') api.hide();
        }

        function buildMentionHTML(v, prefix, type) {
            var doc = editor.getDoc();
            var a = doc.createElement('a');
            a.setAttribute('href', v.url || '#');
            a.setAttribute('data-mention-type', type);
            if (v.id != null) a.setAttribute('data-mention-id', String(v.id));
            a.appendChild(doc.createTextNode(prefix + (v.label || '')));
            return a.outerHTML + '&nbsp;';
        }

        function escapeHtmlSafe(s) {
            var str = (s == null ? '' : String(s));
            var fn = tinymce && tinymce.util && tinymce.util.Tools && tinymce.util.Tools.escapeHtml;
            if (typeof fn === 'function') return fn(str);
            return str.replace(/[&<>"']/g, function (m) {
                return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]);
            });
        }

        function renderCard(_api, data) {
            var v = toObj(data && data.value);
            var el = document.createElement('div');
            el.className = 'mention-item';
            el.innerHTML =
                '<div><strong>' + escapeHtmlSafe(v.label || (data && data.text) || '') + '</strong>' +
                '<small style="display:block;color:#6b7280">' + escapeHtmlSafe(v.sublabel || '') + '</small></div>';
            return el;
        }

        // @user autocompleter
        editor.ui.registry.addAutocompleter('mentionsUsers', {
            trigger: '@',
            ch: '@',
            minChars: 1,
            columns: 1,
            fetch: async function (pattern) {
                var q = termOf(pattern);
                var list = await fetchJSON(buildUrl('/api/mentions/users', { q }));
                return list.map(function (u) {
                    return { type: 'menuitem', text: u.label, value: JSON.stringify(u) };
                });
            },
            onAction: function (api, rng, value) {
                var { api: a, rng: r } = resolveActionArgs(api, rng, value);
                var v = toObj(value);
                insertViaApiOrEditor(a, r, buildMentionHTML(v, '@', 'user'));
            },
            itemRenderer: renderCard,
        });

        // Toolbar button shortcut
        editor.ui.registry.addButton('mentionUser', {
            text: '@',
            tooltip: 'Mention a user',
            onAction: function () { editor.execCommand('mceInsertContent', false, '@'); },
        });
    });
})();
