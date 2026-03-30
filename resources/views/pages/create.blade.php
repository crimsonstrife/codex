<x-app-layout>
    <x-slot name="header">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('workspaces.show', $workspace) }}">{{ $workspace->name }}</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">New Page</li>
            </ol>
        </nav>
    </x-slot>

    {{-- Template Picker Modal --}}
    @if($templates->isNotEmpty())
        <div class="modal fade" id="template-picker-modal" tabindex="-1"
             data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="templatePickerLabel">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="templatePickerLabel">
                            <i class="fas fa-file-alt me-2 text-primary"></i>Choose a Template
                        </h5>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            {{-- Blank page option --}}
                            <div class="col-md-4">
                                <button type="button"
                                        class="btn w-100 h-100 text-start border rounded p-3 template-card"
                                        data-template-id="">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <i class="fas fa-file text-body-secondary"></i>
                                        <span class="fw-medium">Blank Page</span>
                                    </div>
                                    <p class="text-body-secondary small mb-0">Start with an empty page.</p>
                                </button>
                            </div>

                            {{-- System & workspace templates --}}
                            @foreach($templates as $tmpl)
                                <div class="col-md-4">
                                    <button type="button"
                                            class="btn w-100 h-100 text-start border rounded p-3 template-card"
                                            data-template-id="{{ $tmpl->id }}">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <i class="fas fa-file-alt text-primary"></i>
                                            <span class="fw-medium">{{ $tmpl->name }}</span>
                                        </div>
                                        @if($tmpl->description)
                                            <p class="text-body-secondary small mb-0">{{ $tmpl->description }}</p>
                                        @endif
                                        @if($tmpl->is_system)
                                            <span class="badge bg-secondary-subtle text-secondary-emphasis mt-1"
                                                  style="font-size:0.65rem;">System</span>
                                        @endif
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Encode template data for JS (avoids HTML attribute escaping issues) --}}
        @php
            $templateData = $templates->map(fn ($t) => [
                'id' => $t->id,
                'content_type' => $t->content_type,
                'content' => $t->content ?? '',
            ]);
        @endphp
        <script>
            window.codexTemplates = @json($templateData, JSON_THROW_ON_ERROR);
        </script>
    @endif

    <div class="py-4">
        <div class="container" style="max-width: 56rem;">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('workspaces.pages.store', $workspace) }}" id="page-form">
                        @csrf

                        {{-- Title --}}
                        <div class="mb-3">
                            <x-label for="title" value="{{ __('Title') }}"/>
                            <x-input id="title" name="title" type="text" class="mt-1 block w-full fs-5"
                                     value="{{ old('title') }}" required autofocus/>
                            <x-input-error for="title" class="mt-1"/>
                        </div>

                        {{-- Parent page --}}
                        @if($pages->isNotEmpty())
                            <div class="mb-3">
                                <x-label for="parent_id" value="{{ __('Parent Page (optional)') }}"/>
                                <select name="parent_id" id="parent_id" class="form-select">
                                    <option value="">— None (top-level page) —</option>
                                    @foreach($pages as $p)
                                        <option value="{{ $p->id }}" {{ old('parent_id') == $p->id ? 'selected' : '' }}>
                                            {{ $p->title }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error for="parent_id" class="mt-1"/>
                            </div>
                        @endif

                        {{-- Content type --}}
                        <div class="mb-3">
                            <x-label for="content_type" value="{{ __('Editor Type') }}"/>
                            <select name="content_type" id="content_type" class="form-select">
                                <option
                                    value="richtext" {{ old('content_type', $defaultContentType) === 'richtext' ? 'selected' : '' }}>
                                    Rich Text (WYSIWYG)
                                </option>
                                <option value="markdown" {{ old('content_type', $defaultContentType) === 'markdown' ? 'selected' : '' }}>
                                    Markdown
                                </option>
                            </select>
                        </div>

                        {{-- Content --}}
                        <div class="mb-3">
                            <x-label for="content" value="{{ __('Content') }}"/>

                            {{-- Rich text editor (TinyMCE) --}}
                            <div id="richtext-wrapper">
                                <textarea id="content-rich" name="_content_rich" rows="2"
                                          class="form-control mt-1">{{ old('content') }}</textarea>
                            </div>

                            {{-- Markdown textarea --}}
                            <div id="markdown-wrapper" class="d-none">
                                <textarea id="content-md" name="_content_md" rows="24"
                                          class="form-control mt-1 font-monospace small">{{ old('content') }}</textarea>
                            </div>

                            {{-- Hidden field that always holds the actual content to submit --}}
                            <input type="hidden" id="content" name="content" value="{{ old('content') }}"/>
                            <x-input-error for="content" class="mt-1"/>
                        </div>

                        {{-- Status --}}
                        <div class="mb-3">
                            <x-label for="status" value="{{ __('Status') }}"/>
                            <select name="status" id="status" class="form-select">
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                            </select>
                        </div>

                        {{-- Categories --}}
                        @if($categories->isNotEmpty())
                            <div class="mb-3">
                                <x-label value="{{ __('Categories') }}"/>
                                <div class="d-flex flex-wrap gap-2 mt-1">
                                    @foreach($categories as $cat)
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="category_ids[]"
                                                   id="cat_{{ $cat->id }}" value="{{ $cat->id }}"
                                                {{ in_array($cat->id, old('category_ids', [])) ? 'checked' : '' }} />
                                            <label class="form-check-label"
                                                   for="cat_{{ $cat->id }}">{{ $cat->name }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Tags --}}
                        <div class="mb-4">
                            <x-label for="tags" value="{{ __('Tags') }}"/>
                            <x-input id="tags" name="tags" type="text" class="mt-1 block w-full"
                                     value="{{ old('tags') }}"
                                     placeholder="e.g. api, setup, tutorial (comma-separated)"/>
                            <div class="form-text">Separate tags with commas.</div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between">
                            <a href="{{ route('workspaces.show', $workspace) }}"
                               class="btn btn-link text-body-secondary text-decoration-none">
                                Cancel
                            </a>
                            <x-button>{{ __('Create Page') }}</x-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const typeSelect = document.getElementById('content_type');
                const richWrapper = document.getElementById('richtext-wrapper');
                const mdWrapper = document.getElementById('markdown-wrapper');
                const contentHidden = document.getElementById('content');
                const richTextarea = document.getElementById('content-rich');
                const mdTextarea = document.getElementById('content-md');
                let editor = null;

                function initRichEditor() {
                    if (!window.tinyEditor) return;
                    editor = window.tinyEditor({
                        elId: 'content-rich',
                        height: 500,
                        externalPlugins: {
                            'mentions-lite': '/tiny-plugins/mentions-lite/plugin.js',
                            'callouts':      '/tiny-plugins/callouts/plugin.js',
                            'wiki-links':    '/tiny-plugins/wiki-links/plugin.js',
                        },
                        plugins: 'link lists code image table blockquote autolink hr mentions-lite callouts wiki-links',
                        toolbar: 'undo redo | styles | bold italic underline | link image | bullist numlist | blockquote | alignleft aligncenter alignright | table | callout | wikiLink | mentionUser | removeformat | code',
                        codexWorkspaceId: '{{ $workspace->id }}',
                    });
                    editor.init(richTextarea.value || '');
                }

                function destroyRichEditor() {
                    if (editor) {
                        editor.destroy();
                        editor = null;
                    }
                }

                function syncBeforeSubmit() {
                    if (typeSelect.value === 'richtext' && window.tinymce) {
                        const inst = tinymce.get('content-rich');
                        if (inst) contentHidden.value = inst.getContent();
                    } else {
                        contentHidden.value = mdTextarea.value;
                    }
                }

                function applyType(type) {
                    if (type === 'richtext') {
                        richWrapper.classList.remove('d-none');
                        mdWrapper.classList.add('d-none');
                        richTextarea.removeAttribute('name');
                        mdTextarea.removeAttribute('name');
                        initRichEditor();
                    } else {
                        destroyRichEditor();
                        richWrapper.classList.add('d-none');
                        mdWrapper.classList.remove('d-none');
                        richTextarea.removeAttribute('name');
                        mdTextarea.removeAttribute('name');
                    }
                }

                typeSelect.addEventListener('change', () => {
                    destroyRichEditor();
                    applyType(typeSelect.value);
                });

                document.getElementById('page-form').addEventListener('submit', (e) => {
                    syncBeforeSubmit();
                }, {capture: true});

                @if($templates->isNotEmpty())
                // Template picker: show modal, defer editor init until template is chosen
                const templateModal = new bootstrap.Modal(
                    document.getElementById('template-picker-modal'),
                    {backdrop: 'static', keyboard: false}
                );

                document.querySelectorAll('.template-card').forEach(function (card) {
                    card.addEventListener('click', function () {
                        const templateId = this.dataset.templateId;
                        if (templateId && window.codexTemplates) {
                            const tmpl = window.codexTemplates.find(t => t.id === templateId);
                            if (tmpl) {
                                typeSelect.value = tmpl.content_type;
                                richTextarea.value = tmpl.content_type === 'richtext' ? tmpl.content : '';
                                mdTextarea.value = tmpl.content_type === 'markdown' ? tmpl.content : '';
                                contentHidden.value = tmpl.content;
                            }
                        }
                        templateModal.hide();
                    });
                });

                document.getElementById('template-picker-modal').addEventListener('hidden.bs.modal', function () {
                    applyType(typeSelect.value);
                });

                templateModal.show();
                @else
                applyType(typeSelect.value);
                @endif
            });
        </script>
    @endpush
</x-app-layout>
