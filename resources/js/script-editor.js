function uuid() {
    if (window.crypto?.randomUUID) {
        return window.crypto.randomUUID();
    }

    return `script-${Date.now()}-${Math.random().toString(16).slice(2)}`;
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

function defaultBlock(type = 'action') {
    return {
        id: uuid(),
        type,
        text: '',
        position: 1,
        character_entity_id: null,
        location_entity_id: null,
        modifiers: null,
        meta: type === 'scene_heading'
            ? { prefix: 'INT.', time_of_day: 'DAY' }
            : {},
    };
}

function normalizeBlock(raw = {}) {
    const block = defaultBlock(raw.type || 'action');

    block.id = raw.id || uuid();
    block.type = raw.type || 'action';
    block.text = raw.text || '';
    block.position = Number(raw.position || 1);
    block.character_entity_id = raw.character_entity_id || null;
    block.location_entity_id = raw.location_entity_id || null;
    block.modifiers = raw.modifiers || null;
    block.meta = raw.type === 'scene_heading'
        ? {
            prefix: raw.meta?.prefix || 'INT.',
            time_of_day: raw.meta?.time_of_day || 'DAY',
        }
        : {};

    return block;
}

function inferNextType(type) {
    if (type === 'character_cue' || type === 'parenthetical') {
        return 'dialogue';
    }

    if (type === 'dialogue') {
        return 'action';
    }

    return 'action';
}

window.codexScriptEditor = function codexScriptEditor(options) {
    const state = {
        root: document.getElementById(options.rootId),
        hiddenInput: document.getElementById(options.hiddenInputId),
        characterEntities: [...(options.characters || [])],
        locationEntities: [...(options.locations || [])],
        csrf: document.querySelector('meta[name="csrf-token"]')?.content || '',
        urls: options.urls || {},
        blocks: Array.isArray(options.document?.blocks)
            ? options.document.blocks.map(normalizeBlock)
            : [defaultBlock('scene_heading'), defaultBlock('action')],
    };

    if (!state.root || !state.hiddenInput) {
        return null;
    }

    function syncHiddenInput() {
        state.blocks = state.blocks.map((block, index) => ({
            ...block,
            position: index + 1,
        }));

        state.hiddenInput.value = JSON.stringify({
            version: 1,
            blocks: state.blocks,
        });
    }

    function entityOptions(type, selectedId) {
        const entities = type === 'character'
            ? state.characterEntities
            : state.locationEntities;

        return [
            '<option value="">Select one…</option>',
            ...entities.map((entity) => `<option value="${escapeHtml(entity.id)}" ${selectedId === entity.id ? 'selected' : ''}>${escapeHtml(entity.label)}</option>`),
        ].join('');
    }

    function blockControls(block, index) {
        const common = `
            <div class="script-block-toolbar d-flex flex-wrap align-items-center gap-2 mb-2">
                <span class="badge bg-secondary-subtle text-secondary-emphasis">#${index + 1}</span>
                <select class="form-select form-select-sm script-block-type" style="max-width: 14rem;" data-index="${index}">
                    <option value="scene_heading" ${block.type === 'scene_heading' ? 'selected' : ''}>Scene Heading</option>
                    <option value="action" ${block.type === 'action' ? 'selected' : ''}>Action</option>
                    <option value="character_cue" ${block.type === 'character_cue' ? 'selected' : ''}>Character Cue</option>
                    <option value="parenthetical" ${block.type === 'parenthetical' ? 'selected' : ''}>Parenthetical</option>
                    <option value="dialogue" ${block.type === 'dialogue' ? 'selected' : ''}>Dialogue</option>
                    <option value="transition" ${block.type === 'transition' ? 'selected' : ''}>Transition</option>
                </select>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-action="add-after" data-index="${index}">
                    <i class="fas fa-plus me-1"></i>Add After
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-action="move-up" data-index="${index}" ${index === 0 ? 'disabled' : ''}>
                    <i class="fas fa-arrow-up"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-action="move-down" data-index="${index}" ${index === state.blocks.length - 1 ? 'disabled' : ''}>
                    <i class="fas fa-arrow-down"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger ms-auto" data-action="remove" data-index="${index}">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        `;

        if (block.type === 'scene_heading') {
            return `
                ${common}
                <div class="row g-2">
                    <div class="col-md-2">
                        <label class="form-label small text-body-secondary mb-1">Prefix</label>
                        <select class="form-select form-select-sm script-block-field" data-index="${index}" data-field="meta.prefix">
                            <option value="INT." ${block.meta?.prefix === 'INT.' ? 'selected' : ''}>INT.</option>
                            <option value="EXT." ${block.meta?.prefix === 'EXT.' ? 'selected' : ''}>EXT.</option>
                            <option value="INT./EXT." ${block.meta?.prefix === 'INT./EXT.' ? 'selected' : ''}>INT./EXT.</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-body-secondary mb-1">Main Location</label>
                        <div class="d-flex gap-2">
                            <select class="form-select form-select-sm script-block-field" data-index="${index}" data-field="location_entity_id">
                                ${entityOptions('location', block.location_entity_id)}
                            </select>
                            ${block.text && !block.location_entity_id ? `<button type="button" class="btn btn-sm btn-outline-primary flex-shrink-0" data-action="create-location" data-index="${index}">Create</button>` : ''}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-body-secondary mb-1">Micro-location</label>
                        <input type="text" class="form-control form-control-sm script-block-field" data-index="${index}" data-field="text" value="${escapeHtml(block.text)}" placeholder="Bathroom">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-body-secondary mb-1">Time of Day</label>
                        <input type="text" class="form-control form-control-sm script-block-field" data-index="${index}" data-field="meta.time_of_day" value="${escapeHtml(block.meta?.time_of_day || '')}" placeholder="DAY">
                    </div>
                </div>
            `;
        }

        if (block.type === 'character_cue') {
            return `
                ${common}
                <div class="row g-2">
                    <div class="col-md-5">
                        <label class="form-label small text-body-secondary mb-1">Typed Cue</label>
                        <input type="text" class="form-control form-control-sm script-block-field" data-index="${index}" data-field="text" value="${escapeHtml(block.text)}" placeholder="MARA">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small text-body-secondary mb-1">Character</label>
                        <div class="d-flex gap-2">
                            <select class="form-select form-select-sm script-block-field" data-index="${index}" data-field="character_entity_id">
                                ${entityOptions('character', block.character_entity_id)}
                            </select>
                            ${block.text && !block.character_entity_id ? `<button type="button" class="btn btn-sm btn-outline-primary flex-shrink-0" data-action="create-character" data-index="${index}">Create</button>` : ''}
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-body-secondary mb-1">Modifier</label>
                        <select class="form-select form-select-sm script-block-field" data-index="${index}" data-field="modifiers">
                            <option value="">None</option>
                            <option value="O.S." ${block.modifiers === 'O.S.' ? 'selected' : ''}>O.S.</option>
                            <option value="V.O." ${block.modifiers === 'V.O.' ? 'selected' : ''}>V.O.</option>
                            <option value="CONT'D" ${block.modifiers === "CONT'D" ? 'selected' : ''}>CONT'D</option>
                        </select>
                    </div>
                </div>
            `;
        }

        const rows = block.type === 'dialogue' || block.type === 'action' ? 4 : 2;
        const placeholder = block.type === 'transition' ? 'CUT TO:' : 'Write screenplay text…';

        return `
            ${common}
            ${block.type === 'transition'
                ? `<input type="text" class="form-control form-control-sm script-block-field" data-index="${index}" data-field="text" value="${escapeHtml(block.text)}" placeholder="${placeholder}">`
                : `<textarea class="form-control script-block-field" rows="${rows}" data-index="${index}" data-field="text" placeholder="${placeholder}">${escapeHtml(block.text)}</textarea>`
            }
        `;
    }

    function render() {
        state.root.innerHTML = state.blocks.map((block, index) => `
            <div class="card shadow-sm mb-3 script-block script-block-${escapeHtml(block.type)}" data-block-id="${escapeHtml(block.id)}">
                <div class="card-body">
                    ${blockControls(block, index)}
                </div>
            </div>
        `).join('');

        syncHiddenInput();
    }

    function updateField(index, field, value) {
        const block = state.blocks[index];
        if (!block) return;

        if (field.startsWith('meta.')) {
            const key = field.replace('meta.', '');
            block.meta = block.meta || {};
            block.meta[key] = value || '';
        } else {
            block[field] = value || null;
            if (field === 'text') {
                block[field] = value ?? '';
            }
        }

        syncHiddenInput();
    }

    async function createEntity(index, type) {
        const block = state.blocks[index];
        if (!block) return;

        const payload = new FormData();
        payload.append('type', type);
        payload.append('name', block.text || '');
        payload.append('display_name', block.text || '');

        const response = await fetch(state.urls.createEntity, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': state.csrf,
                'Accept': 'application/json',
            },
            body: payload,
        });

        if (!response.ok) {
            const body = await response.text();
            window.alert(body || 'Could not create screenplay entity.');
            return;
        }

        const entity = await response.json();

        if (type === 'character') {
            state.characterEntities.push(entity);
            block.character_entity_id = entity.id;
        } else {
            state.locationEntities.push(entity);
            block.location_entity_id = entity.id;
        }

        render();
    }

    function focusField(index, selector = '.script-block-field') {
        const card = state.root.querySelectorAll('.script-block')[index];
        const input = card?.querySelector(selector);
        input?.focus();
    }

    function insertBlock(index, type = 'action') {
        state.blocks.splice(index, 0, defaultBlock(type));
        render();
        focusField(index);
    }

    state.root.addEventListener('input', (event) => {
        const target = event.target;
        if (!target.classList.contains('script-block-field')) {
            return;
        }

        updateField(Number(target.dataset.index), target.dataset.field, target.value);
    });

    state.root.addEventListener('change', (event) => {
        const target = event.target;

        if (target.classList.contains('script-block-type')) {
            const index = Number(target.dataset.index);
            const existing = state.blocks[index];
            const replacement = defaultBlock(target.value);
            replacement.id = existing.id;
            replacement.text = existing.text;
            replacement.character_entity_id = existing.character_entity_id;
            replacement.location_entity_id = existing.location_entity_id;
            replacement.modifiers = existing.modifiers;
            state.blocks[index] = replacement;
            render();
            focusField(index);
        }
    });

    state.root.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-action]');
        if (!button) return;

        const index = Number(button.dataset.index);
        const action = button.dataset.action;

        if (action === 'add-after') {
            insertBlock(index + 1, inferNextType(state.blocks[index]?.type));
            return;
        }

        if (action === 'remove') {
            state.blocks.splice(index, 1);
            if (state.blocks.length === 0) {
                state.blocks.push(defaultBlock('scene_heading'));
                state.blocks.push(defaultBlock('action'));
            }
            render();
            return;
        }

        if (action === 'move-up' && index > 0) {
            [state.blocks[index - 1], state.blocks[index]] = [state.blocks[index], state.blocks[index - 1]];
            render();
            focusField(index - 1);
            return;
        }

        if (action === 'move-down' && index < state.blocks.length - 1) {
            [state.blocks[index + 1], state.blocks[index]] = [state.blocks[index], state.blocks[index + 1]];
            render();
            focusField(index + 1);
            return;
        }

        if (action === 'create-character') {
            await createEntity(index, 'character');
            return;
        }

        if (action === 'create-location') {
            await createEntity(index, 'location');
        }
    });

    state.root.addEventListener('keydown', (event) => {
        const field = event.target.closest('.script-block-field');
        if (!field) return;

        const index = Number(field.dataset.index);
        const block = state.blocks[index];
        if (!block) return;

        if ((event.metaKey || event.ctrlKey) && !event.shiftKey) {
            const map = {
                '1': 'scene_heading',
                '2': 'action',
                '3': 'character_cue',
                '4': 'dialogue',
                '5': 'parenthetical',
                '6': 'transition',
            };

            if (map[event.key]) {
                event.preventDefault();
                const replacement = defaultBlock(map[event.key]);
                replacement.id = block.id;
                replacement.text = block.text;
                replacement.character_entity_id = block.character_entity_id;
                replacement.location_entity_id = block.location_entity_id;
                replacement.modifiers = block.modifiers;
                state.blocks[index] = replacement;
                render();
                focusField(index);
            }
        }

        if ((event.metaKey || event.ctrlKey) && event.shiftKey && event.key.toLowerCase() === 'p') {
            event.preventDefault();
            insertBlock(index + 1, 'parenthetical');
        }

        if (event.key === 'Enter' && block.type === 'character_cue' && !event.shiftKey) {
            event.preventDefault();
            insertBlock(index + 1, 'dialogue');
        }

        if (event.altKey && event.key === 'ArrowUp') {
            event.preventDefault();
            focusField(Math.max(index - 1, 0));
        }

        if (event.altKey && event.key === 'ArrowDown') {
            event.preventDefault();
            focusField(Math.min(index + 1, state.blocks.length - 1));
        }
    });

    render();

    return {
        getValue() {
            syncHiddenInput();
            return state.hiddenInput.value;
        },
    };
};
