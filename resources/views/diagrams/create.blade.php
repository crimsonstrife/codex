<x-app-layout>
    <x-slot name="header">
        <h2 class="h5 mb-0">
            New Diagram in <span class="text-primary">{{ $workspace->name }}</span>
        </h2>
    </x-slot>

    <div class="py-4">
        <div class="container" x-data="{ diagramType: 'mermaid' }">
            <div class="row g-4">

                {{-- Sidebar: settings --}}
                <div class="col-lg-4">
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <form method="POST" action="{{ route('workspaces.diagrams.store', $workspace) }}" id="diagram-form">
                                @csrf
                                <input type="hidden" name="diagram_data" id="diagram_data_input" />

                                <div class="mb-3">
                                    <x-label for="title" value="{{ __('Title') }}" />
                                    <x-input id="title" name="title" type="text" class="mt-1 block w-full" required />
                                </div>
                                <div class="mb-3">
                                    <x-label for="description" value="{{ __('Description') }}" />
                                    <textarea name="description" rows="2" class="form-control"></textarea>
                                </div>
                                <div class="mb-3">
                                    <x-label for="diagram_type" value="{{ __('Editor Type') }}" />
                                    <select name="diagram_type" id="diagram_type"
                                            x-model="diagramType"
                                            class="form-select">
                                        <option value="mermaid">🧩 Mermaid — Native</option>
                                        <option value="mindmap">🧠 Mind Map — Native (Mermaid)</option>
                                        <option value="flowchart">🔀 Flowchart — Native (Mermaid)</option>
                                        <option value="drawio">🎨 draw.io — Visual editor</option>
                                    </select>
                                    <div class="form-text" x-show="diagramType !== 'drawio'">
                                        Native: runs entirely in the browser, no external service needed.
                                    </div>
                                    <div class="form-text text-warning" x-show="diagramType === 'drawio'">
                                        Requires access to <code>{{ $drawioUrl }}</code>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <x-checkbox id="is_published" name="is_published" value="1" class="form-check-input" />
                                        <label class="form-check-label" for="is_published">{{ __('Publish immediately') }}</label>
                                    </div>
                                </div>
                                @if($categories->isNotEmpty())
                                <div class="mb-3">
                                    <x-label value="{{ __('Categories') }}" />
                                    <div class="d-flex flex-wrap gap-2 mt-1">
                                        @foreach($categories as $cat)
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="category_ids[]"
                                                   id="cat_{{ $cat->id }}" value="{{ $cat->id }}"
                                                   {{ in_array($cat->id, old('category_ids', [])) ? 'checked' : '' }} />
                                            <label class="form-check-label" for="cat_{{ $cat->id }}">{{ $cat->name }}</label>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                                <div class="mb-3">
                                    <x-label for="tags" value="{{ __('Tags') }}" />
                                    <x-input id="tags" name="tags" type="text" class="mt-1 block w-full"
                                             value="{{ old('tags') }}" placeholder="comma-separated" />
                                </div>
                                <x-button id="save-btn" type="button" onclick="saveDiagram()">{{ __('Save Diagram') }}</x-button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Editor panel --}}
                <div class="col-lg-8">

                    {{-- Native Mermaid editor --}}
                    <div x-show="diagramType !== 'drawio'">
                        <div class="card shadow-sm overflow-hidden">
                            <div class="card-header p-0">
                                <ul class="nav nav-tabs card-header-tabs px-2">
                                    <li class="nav-item">
                                        <button type="button" onclick="switchTab('editor')" id="tab-editor"
                                                class="nav-link active">Edit</button>
                                    </li>
                                    <li class="nav-item">
                                        <button type="button" onclick="switchTab('preview')" id="tab-preview"
                                                class="nav-link">Preview</button>
                                    </li>
                                </ul>
                            </div>
                            <div id="pane-editor" class="card-body p-3">
                                <textarea id="mermaid-source" rows="22"
                                    class="form-control font-monospace small"
                                    placeholder="Type your Mermaid diagram here..."></textarea>
                            </div>
                            <div id="pane-preview" class="card-body p-4 d-none" style="min-height: 16rem;">
                                <div id="mermaid-preview" class="text-center"></div>
                            </div>
                        </div>
                        <p class="mt-2 text-body-secondary small">
                            Uses <a href="https://mermaid.js.org" target="_blank" class="text-primary">Mermaid.js</a> —
                            supports flowcharts, sequence diagrams, mind maps, and more.
                        </p>
                    </div>

                    {{-- draw.io editor --}}
                    <div x-show="diagramType === 'drawio'" style="display:none;">
                        <div class="card shadow-sm overflow-hidden" style="height: 560px;">
                            <iframe id="drawio-frame"
                                src="{{ $drawioUrl }}?embed=1&spin=1&proto=json&libraries=1"
                                style="width:100%;height:100%;border:none;"></iframe>
                        </div>
                        <p class="mt-2 text-warning small">
                            Visual editor powered by <a href="https://www.diagrams.net" target="_blank">draw.io</a>.
                        </p>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            const editor = document.getElementById('pane-editor');
            const preview = document.getElementById('pane-preview');
            const tabEditor = document.getElementById('tab-editor');
            const tabPreview = document.getElementById('tab-preview');
            if (tab === 'editor') {
                editor.classList.remove('d-none');
                preview.classList.add('d-none');
                tabEditor.classList.add('active');
                tabPreview.classList.remove('active');
            } else {
                editor.classList.add('d-none');
                preview.classList.remove('d-none');
                tabPreview.classList.add('active');
                tabEditor.classList.remove('active');
                renderMermaidPreview();
            }
        }

        async function renderMermaidPreview() {
            const source = document.getElementById('mermaid-source').value;
            const container = document.getElementById('mermaid-preview');
            if (!source.trim()) {
                container.innerHTML = '<p class="text-body-secondary small">Nothing to preview.</p>';
                return;
            }
            try {
                const { svg } = await window.mermaid.render('preview-graph', source);
                container.innerHTML = svg;
            } catch (e) {
                container.innerHTML = `<pre class="text-danger small text-start">${e.message}</pre>`;
            }
        }

        window.addEventListener('message', function (evt) {
            if (typeof evt.data !== 'string') return;
            try {
                const msg = JSON.parse(evt.data);
                if (msg.event === 'export') {
                    document.getElementById('diagram_data_input').value = msg.data ?? '';
                } else if (msg.event === 'save' || msg.event === 'autosave') {
                    document.getElementById('diagram_data_input').value = msg.xml ?? '';
                }
            } catch (e) {}
        });

        function saveDiagram() {
            const type = document.getElementById('diagram_type').value;
            if (type === 'drawio') {
                const frame = document.getElementById('drawio-frame');
                if (frame) {
                    frame.contentWindow.postMessage(JSON.stringify({ action: 'export', format: 'xml' }), '*');
                }
                setTimeout(() => document.getElementById('diagram-form').submit(), 400);
            } else {
                document.getElementById('diagram_data_input').value = document.getElementById('mermaid-source').value;
                document.getElementById('diagram-form').submit();
            }
        }
    </script>
</x-app-layout>
