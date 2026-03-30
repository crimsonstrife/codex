<x-app-layout>
    <x-slot name="header">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('workspaces.show', $workspace) }}">{{ $workspace->name }}</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Settings</li>
            </ol>
        </nav>
    </x-slot>

    <div class="py-4">
        <div class="container" style="max-width: 44rem;">

            @if(session('status') === 'workspace-updated')
                <div class="alert alert-success alert-dismissible mb-4" role="alert">
                    <i class="fas fa-check-circle me-1"></i> Workspace settings saved.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form method="POST" action="{{ route('workspaces.update', $workspace) }}">
                @csrf
                @method('PUT')

                {{-- Identity --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header py-3">
                        <h2 class="h6 fw-semibold mb-0">
                            <i class="fas fa-pencil-alt me-2 text-body-secondary"></i>Identity
                        </h2>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <x-label for="name" value="{{ __('Workspace Name') }}" />
                            <x-input id="name" name="name" type="text" class="mt-1 block w-full"
                                     value="{{ old('name', $workspace->name) }}" required autofocus />
                            <x-input-error for="name" class="mt-1" />
                        </div>
                        <div class="mb-3">
                            <x-label for="description" value="{{ __('Description') }}" />
                            <textarea id="description" name="description" rows="3"
                                      class="form-control @error('description') is-invalid @enderror mt-1">{{ old('description', $workspace->description) }}</textarea>
                            <x-input-error for="description" class="mt-1" />
                        </div>
                    </div>
                </div>

                {{-- Appearance --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header py-3">
                        <h2 class="h6 fw-semibold mb-0">
                            <i class="fas fa-palette me-2 text-body-secondary"></i>Appearance
                        </h2>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4 align-items-start">
                            <div class="col-sm-6">
                                <x-label for="color" value="{{ __('Accent Color') }}" />
                                <div class="d-flex align-items-center gap-3 mt-1">
                                    <input type="color" id="color" name="color"
                                           value="{{ old('color', $workspace->color ?? '#6366f1') }}"
                                           class="form-control form-control-color @error('color') is-invalid @enderror"
                                           style="width: 4rem; height: 2.25rem;" />
                                    <span class="small text-body-secondary">Used in the workspace avatar and accents.</span>
                                </div>
                                <x-input-error for="color" class="mt-1" />
                            </div>
                            <div class="col-sm-6">
                                <x-label for="icon" value="{{ __('Icon (emoji)') }}" />
                                <div class="d-flex align-items-center gap-3 mt-1">
                                    <x-input id="icon" name="icon" type="text" class="block"
                                             style="width: 4rem; font-size: 1.25rem; text-align: center;"
                                             value="{{ old('icon', $workspace->icon) }}"
                                             placeholder="🎮" maxlength="10" />
                                    <span class="small text-body-secondary">Optional emoji shown alongside the workspace name.</span>
                                </div>
                                <x-input-error for="icon" class="mt-1" />
                            </div>
                        </div>

                        {{-- Live preview --}}
                        <div class="mt-4 p-3 rounded-3 border bg-body-tertiary">
                            <span class="small text-body-secondary d-block mb-2">Preview</span>
                            <div class="d-flex align-items-center gap-2">
                                <div id="preview-avatar"
                                     class="d-flex align-items-center justify-content-center rounded-3 text-white fw-bold flex-shrink-0"
                                     style="width:2rem;height:2rem;background-color:{{ $workspace->color ?? '#6366f1' }};font-size:0.85rem;">
                                    {{ $workspace->icon ?? strtoupper(substr($workspace->name, 0, 1)) }}
                                </div>
                                <span id="preview-name" class="fw-semibold">{{ $workspace->name }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Visibility --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header py-3">
                        <h2 class="h6 fw-semibold mb-0">
                            <i class="fas fa-lock me-2 text-body-secondary"></i>Visibility
                        </h2>
                    </div>
                    <div class="card-body p-4">
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_public" value="0">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="is_public" name="is_public" value="1"
                                   {{ old('is_public', $workspace->is_public) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_public">
                                <span class="fw-medium">Public workspace</span>
                                <span class="d-block small text-body-secondary">
                                    Anyone can view this workspace's pages without being a member.
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <a href="{{ route('workspaces.show', $workspace) }}"
                       class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Cancel
                    </a>
                    <x-button>
                        <i class="fas fa-save me-1"></i> {{ __('Save Settings') }}
                    </x-button>
                </div>
            </form>

            {{-- Forge Integration card --}}
            @if(\App\Support\CodexRuntimeConfig::forgeEnabled() && filled(\App\Support\CodexRuntimeConfig::forgeUrl()))
            <div class="card shadow-sm mt-4">
                <div class="card-header py-3 d-flex align-items-center justify-content-between">
                    <h2 class="h6 fw-semibold mb-0">
                        <i class="fas fa-link me-2 text-body-secondary"></i>Forge Integration
                    </h2>
                    @if($workspace->forge_project_id)
                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                            <i class="fas fa-link me-1"></i>Linked
                        </span>
                    @endif
                </div>
                <div class="card-body p-4">
                    @if($workspace->forge_project_id)
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-1 fw-semibold">
                                    <i class="fas fa-external-link-alt me-1 text-primary"></i>
                                    Forge Project:
                                    <span class="font-monospace">{{ $workspace->forge_project_key ?? $workspace->forge_project_id }}</span>
                                </p>
                                <p class="mb-0 small text-body-secondary">
                                    <a href="{{ \App\Support\CodexRuntimeConfig::forgeUrl() }}/projects/{{ $workspace->forge_project_id }}"
                                       target="_blank" rel="noopener">
                                        Open in Forge <i class="fas fa-external-link-alt ms-1" style="font-size:0.7rem;"></i>
                                    </a>
                                </p>
                            </div>
                            <form method="POST" action="{{ route('workspaces.update', $workspace) }}">
                                @csrf @method('PUT')
                                <input type="hidden" name="name" value="{{ $workspace->name }}">
                                <input type="hidden" name="forge_project_id" value="">
                                <input type="hidden" name="forge_project_key" value="">
                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Unlink this Forge project?')">
                                    <i class="fas fa-unlink me-1"></i>Unlink
                                </button>
                            </form>
                        </div>
                    @else
                        <p class="small text-body-secondary mb-3">
                            Link this workspace to a Forge project so team members can navigate directly between documentation and issues.
                        </p>
                        @if(count($forgeProjects) > 0)
                            <form method="POST" action="{{ route('workspaces.update', $workspace) }}"
                                  class="d-flex gap-2 align-items-end">
                                @csrf @method('PUT')
                                <input type="hidden" name="name" value="{{ $workspace->name }}">
                                <div class="flex-grow-1">
                                    <label class="form-label small fw-medium mb-1" for="forge_project_id">
                                        Select Forge Project
                                    </label>
                                    <select name="forge_project_id" id="forge_project_id" class="form-select form-select-sm">
                                        <option value="">— Select a project —</option>
                                        @foreach($forgeProjects as $fp)
                                            <option value="{{ $fp['id'] ?? '' }}"
                                                    data-key="{{ $fp['key'] ?? '' }}">
                                                {{ $fp['name'] ?? $fp['id'] }}
                                                @if(!empty($fp['key'])) ({{ $fp['key'] }}) @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="forge_project_key" id="forge_project_key_input" value="">
                                </div>
                                <button type="submit" class="btn btn-sm btn-primary flex-shrink-0">
                                    <i class="fas fa-link me-1"></i>Link
                                </button>
                            </form>
                            <script>
                                document.getElementById('forge_project_id')?.addEventListener('change', function() {
                                    const opt = this.options[this.selectedIndex];
                                    document.getElementById('forge_project_key_input').value = opt.dataset.key ?? '';
                                });
                            </script>
                        @else
                            <p class="small text-body-secondary mb-0">
                                No Forge projects found. Ensure your Forge account is linked and the Forge client credentials are configured.
                            </p>
                        @endif
                    @endif
                </div>
            </div>
            @endif

            {{-- Templates card --}}
            <div class="card shadow-sm mt-4">
                <div class="card-header py-3">
                    <h2 class="h6 fw-semibold mb-0">
                        <i class="fas fa-file-alt me-2 text-body-secondary"></i>Page Templates
                    </h2>
                </div>
                <div class="card-body p-4">
                    <p class="small text-body-secondary mb-3">
                        Page templates pre-fill the editor when creating new pages. Save any page as a template
                        using the <strong>Save as Template</strong> button on the page view.
                    </p>
                    <a href="{{ route('workspaces.templates.index', $workspace) }}"
                       class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-folder-open me-1"></i> Manage Templates
                    </a>
                </div>
            </div>

            {{-- Analytics card --}}
            <div class="card shadow-sm mt-4">
                <div class="card-header py-3">
                    <h2 class="h6 fw-semibold mb-0">
                        <i class="fas fa-chart-bar me-2 text-body-secondary"></i>Analytics
                    </h2>
                </div>
                <div class="card-body p-4">
                    <p class="small text-body-secondary mb-3">
                        View page view counts, edit activity, top contributors, and the most-referenced pages
                        in this workspace.
                    </p>
                    <a href="{{ route('workspaces.analytics', $workspace) }}"
                       class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-chart-bar me-1"></i> View Analytics
                    </a>
                </div>
            </div>

            {{-- Export card --}}
            <div class="card shadow-sm mt-4">
                <div class="card-header py-3">
                    <h2 class="h6 fw-semibold mb-0">
                        <i class="fas fa-file-archive me-2 text-body-secondary"></i>Export
                    </h2>
                </div>
                <div class="card-body p-4">
                    <p class="small text-body-secondary mb-3">
                        Download all pages in this workspace as a self-contained ZIP archive. Each page is
                        exported as a standalone HTML file with internal links rewritten for offline navigation.
                        Attachments are linked back to the live site.
                    </p>
                    <a href="{{ route('workspaces.export', $workspace) }}"
                       class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-download me-1"></i> Download ZIP Export
                    </a>
                </div>
            </div>

            {{-- Members preview card (outside the settings form) --}}
            <div class="card shadow-sm mt-4">
                <div class="card-header py-3 d-flex align-items-center justify-content-between">
                    <h2 class="h6 fw-semibold mb-0">
                        <i class="fas fa-users me-2 text-body-secondary"></i>Members
                        <span class="badge bg-secondary-subtle text-secondary-emphasis fw-normal ms-1">
                            {{ $memberCount + 1 }}
                        </span>
                    </h2>
                    <a href="{{ route('workspaces.members.index', $workspace) }}"
                       class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-user-cog me-1"></i> Manage
                    </a>
                </div>
                <div class="list-group list-group-flush">
                    {{-- Owner row --}}
                    <div class="list-group-item d-flex align-items-center gap-3 py-2 px-4">
                        <div class="rounded-circle bg-primary-subtle text-primary-emphasis fw-bold d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width:32px;height:32px;font-size:0.7rem;">
                            {{ strtoupper(substr($workspace->owner->name, 0, 2)) }}
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="small fw-medium text-truncate">{{ $workspace->owner->name }}</div>
                            <div class="text-body-secondary" style="font-size:0.72rem;">{{ $workspace->owner->email }}</div>
                        </div>
                        <span class="badge bg-primary-subtle text-primary-emphasis flex-shrink-0">Owner</span>
                    </div>

                    {{-- Up to 5 member rows --}}
                    @foreach($membersPreview as $member)
                        <div class="list-group-item d-flex align-items-center gap-3 py-2 px-4">
                            <div class="rounded-circle bg-secondary-subtle text-secondary-emphasis fw-bold d-flex align-items-center justify-content-center flex-shrink-0"
                                 style="width:32px;height:32px;font-size:0.7rem;">
                                {{ strtoupper(substr($member->name, 0, 2)) }}
                            </div>
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="small fw-medium text-truncate">{{ $member->name }}</div>
                                <div class="text-body-secondary" style="font-size:0.72rem;">{{ $member->email }}</div>
                            </div>
                            <span class="badge bg-secondary-subtle text-secondary-emphasis flex-shrink-0">
                                {{ ucfirst($member->pivot->role) }}
                            </span>
                        </div>
                    @endforeach

                    @if($memberCount === 0)
                        <div class="list-group-item px-4 py-3 text-body-secondary small">
                            No members yet.
                            <a href="{{ route('workspaces.members.index', $workspace) }}" class="ms-1">Add one →</a>
                        </div>
                    @elseif($memberCount > 5)
                        <a href="{{ route('workspaces.members.index', $workspace) }}"
                           class="list-group-item list-group-item-action px-4 py-2 small text-body-secondary">
                            View all {{ $memberCount + 1 }} members →
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Live preview: color swatch + name
        const colorInput  = document.getElementById('color');
        const iconInput   = document.getElementById('icon');
        const nameInput   = document.getElementById('name');
        const avatar      = document.getElementById('preview-avatar');
        const previewName = document.getElementById('preview-name');

        function updatePreview() {
            avatar.style.backgroundColor = colorInput.value;
            const iconVal = iconInput.value.trim();
            avatar.textContent = iconVal || (nameInput.value.trim().charAt(0).toUpperCase() || '?');
            previewName.textContent = nameInput.value.trim() || 'Workspace Name';
        }

        colorInput.addEventListener('input', updatePreview);
        iconInput.addEventListener('input', updatePreview);
        nameInput.addEventListener('input', updatePreview);
    </script>
    @endpush
</x-app-layout>
