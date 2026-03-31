<x-app-layout>
    <x-slot name="header">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('workspaces.show', $workspace) }}">{{ $workspace->name }}</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('workspaces.scripts.show', [$workspace, $script]) }}">{{ $script->title }}</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Edit Draft</li>
            </ol>
        </nav>
    </x-slot>

    <style>
        .script-editor-shell {
            background: linear-gradient(180deg, #fffef8 0%, #fff 100%);
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 1rem;
            padding: 1rem;
        }
        .script-shortcut {
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.05);
            color: #475569;
            font-size: 0.75rem;
            padding: 0.25rem 0.6rem;
        }
        .script-block-character_cue { border-left: 4px solid #1d4ed8; }
        .script-block-dialogue { border-left: 4px solid #0f766e; }
        .script-block-scene_heading { border-left: 4px solid #7c3aed; }
        .script-block-transition { border-left: 4px solid #b45309; }
    </style>

    <div class="py-4">
        <div class="container" style="max-width: 78rem;">
            <div class="row g-4">
                <div class="col-xl-8">
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <form method="POST" action="{{ route('workspaces.scripts.update', [$workspace, $script]) }}" id="script-form">
                                @csrf
                                @method('PUT')

                                <div class="row g-3 mb-3">
                                    <div class="col-md-7">
                                        <x-label for="title" value="{{ __('Title') }}" />
                                        <x-input id="title" name="title" type="text" class="mt-1 block w-full fs-5"
                                                 value="{{ old('title', $script->title) }}" required autofocus />
                                        <x-input-error for="title" class="mt-1" />
                                    </div>
                                    <div class="col-md-5">
                                        <x-label for="status" value="{{ __('Status') }}" />
                                        <select name="status" id="status" class="form-select mt-1">
                                            @foreach(['draft', 'published', 'archived'] as $status)
                                                <option value="{{ $status }}" {{ old('status', $script->status) === $status ? 'selected' : '' }}>
                                                    {{ ucfirst($status) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <x-label for="logline" value="{{ __('Logline') }}" />
                                    <x-input id="logline" name="logline" type="text" class="mt-1 block w-full"
                                             value="{{ old('logline', $script->logline) }}" />
                                </div>

                                <div class="mb-3">
                                    <x-label for="synopsis" value="{{ __('Synopsis') }}" />
                                    <textarea id="synopsis" name="synopsis" rows="4"
                                              class="form-control mt-1">{{ old('synopsis', $script->synopsis) }}</textarea>
                                </div>

                                <div class="mb-3">
                                    <x-label for="document" value="{{ __('Screenplay Draft') }}" />
                                    <input type="hidden" id="document" name="document" value="{{ old('document', json_encode($script->document, JSON_THROW_ON_ERROR)) }}">
                                    <div class="script-editor-shell mt-2">
                                        <div id="script-editor-root"></div>
                                    </div>
                                    <x-input-error for="document" class="mt-1" />
                                </div>

                                <div class="mb-4">
                                    <x-label for="change_summary" value="{{ __('Change Summary (optional)') }}" />
                                    <x-input id="change_summary" name="change_summary" type="text" class="mt-1 block w-full"
                                             value="{{ old('change_summary') }}" placeholder="Briefly describe your changes" />
                                </div>

                                <div class="d-flex align-items-center justify-content-between">
                                    <a href="{{ route('workspaces.scripts.show', [$workspace, $script]) }}"
                                       class="btn btn-link text-body-secondary text-decoration-none">
                                        Cancel
                                    </a>
                                    <x-button>{{ __('Save Draft') }}</x-button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="card shadow-sm">
                        <div class="card-header py-3">
                            <h2 class="h6 fw-semibold mb-0">
                                <i class="fas fa-keyboard me-2 text-body-secondary"></i>Editor Shortcuts
                            </h2>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-wrap gap-2">
                                <span class="script-shortcut">Ctrl/Cmd + 1 Scene</span>
                                <span class="script-shortcut">Ctrl/Cmd + 2 Action</span>
                                <span class="script-shortcut">Ctrl/Cmd + 3 Character</span>
                                <span class="script-shortcut">Ctrl/Cmd + 4 Dialogue</span>
                                <span class="script-shortcut">Ctrl/Cmd + 5 Parenthetical</span>
                                <span class="script-shortcut">Ctrl/Cmd + 6 Transition</span>
                                <span class="script-shortcut">Enter after character cue inserts dialogue</span>
                                <span class="script-shortcut">Ctrl/Cmd + Shift + P inserts parenthetical</span>
                                <span class="script-shortcut">Alt + Arrow Up/Down jumps between blocks</span>
                            </div>
                            <p class="small text-body-secondary mb-0 mt-3">
                                Character cues must be linked to saved characters before the draft can be saved.
                                If you type a new cue, use the inline <strong>Create</strong> button to turn it into a reusable character.
                            </p>
                        </div>
                    </div>

                    <div class="card shadow-sm mt-4">
                        <div class="card-header py-3">
                            <h2 class="h6 fw-semibold mb-0">
                                <i class="fas fa-users me-2 text-body-secondary"></i>Saved Entities
                            </h2>
                        </div>
                        <div class="card-body small">
                            <p class="text-body-secondary">Characters: {{ $script->characters->count() }}</p>
                            <p class="text-body-secondary mb-0">Locations: {{ $script->locations->count() }}</p>
                            <a href="{{ route('workspaces.scripts.show', [$workspace, $script]) }}" class="btn btn-sm btn-outline-secondary mt-3">
                                Manage Binder & Entities
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const editor = window.codexScriptEditor?.({
                    rootId: 'script-editor-root',
                    hiddenInputId: 'document',
                    document: @json($script->document, JSON_THROW_ON_ERROR),
                    characters: @json($script->characters->map(fn ($entity) => ['id' => $entity->id, 'label' => $entity->label()])->values(), JSON_THROW_ON_ERROR),
                    locations: @json($script->locations->map(fn ($entity) => ['id' => $entity->id, 'label' => $entity->label()])->values(), JSON_THROW_ON_ERROR),
                    urls: {
                        createEntity: @json(route('workspaces.scripts.entities.store', [$workspace, $script]), JSON_THROW_ON_ERROR),
                    },
                });

                document.getElementById('script-form')?.addEventListener('submit', () => {
                    editor?.getValue();
                }, { capture: true });
            });
        </script>
    @endpush
</x-app-layout>
