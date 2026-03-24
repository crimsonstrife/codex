/**
 * TinyMCE plugin: callouts
 *
 * Inserts styled callout / admonition blocks.
 * Blocks are stored as <div class="callout callout-{type}"> so they render
 * on the public-facing page view without any JavaScript.
 *
 * Supported types: note, tip, warning, danger, success, design-decision
 */
(function () {
    tinymce.PluginManager.add('callouts', function (editor) {

        var TYPES = [
            { value: 'note',            text: 'Note',            icon: 'info' },
            { value: 'tip',             text: 'Tip',             icon: 'checkmark' },
            { value: 'warning',         text: 'Warning',         icon: 'warning' },
            { value: 'danger',          text: 'Danger',          icon: 'remove' },
            { value: 'success',         text: 'Success',         icon: 'checkmark' },
            { value: 'design-decision', text: 'Design Decision', icon: 'edit-block' },
        ];

        function insertOrChangeCallout(type) {
            var node = editor.selection.getStart(true);
            var existing = editor.dom.getParent(node, '.callout');

            if (existing) {
                // Change type of existing callout — remove all known type classes first
                TYPES.forEach(function (t) {
                    editor.dom.removeClass(existing, 'callout-' + t.value);
                });
                editor.dom.addClass(existing, 'callout-' + type);
                editor.undoManager.add();
            } else {
                editor.insertContent(
                    '<div class="callout callout-' + type + '"><p>Type your note here.</p></div><p></p>'
                );
            }
        }

        editor.ui.registry.addSplitButton('callout', {
            text: 'Callout',
            tooltip: 'Insert a callout block',
            onAction: function () {
                insertOrChangeCallout('note');
            },
            onItemAction: function (api, value) {
                insertOrChangeCallout(value);
            },
            fetch: function (callback) {
                callback(TYPES.map(function (t) {
                    return { type: 'choiceitem', value: t.value, text: t.text };
                }));
            },
        });
    });
})();
