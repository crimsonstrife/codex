<x-app-layout>
    <x-slot name="header">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('workspaces.show', $workspace) }}">{{ $workspace->name }}</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('workspaces.pages.show', [$workspace, $page]) }}">{{ $page->title }}</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Edit</li>
            </ol>
        </nav>
    </x-slot>

    <div class="py-4">
        <div class="container" style="max-width: 56rem;">

            {{-- Sprint 14.1: edit lock warning --}}
            @if($lockedByOther)
            <div class="alert alert-warning d-flex align-items-center justify-content-between gap-3 mb-4 py-3" id="lock-banner" role="alert">
                <div>
                    <i class="fas fa-lock me-2"></i>
                    <strong>{{ $page->lockedBy->name }}</strong> is currently editing this page
                    <span class="text-body-secondary small">({{ $page->locked_at->diffForHumans() }})</span>.
                    Saving your changes may overwrite theirs.
                </div>
                <div class="d-flex gap-2 flex-shrink-0">
                    <button type="button" id="btn-take-over"
                            class="btn btn-sm btn-warning">
                        <i class="fas fa-user-edit me-1"></i>Take Over
                    </button>
                    <a href="{{ route('workspaces.pages.show', [$workspace, $page]) }}"
                       class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-eye me-1"></i>View Only
                    </a>
                </div>
            </div>
            @endif

            <div class="card shadow-sm">
                <div class="card-body p-4">
                    @if(session('status') === 'page-created')
                        <div class="alert alert-success alert-dismissible mb-4 py-2" role="alert">
                            <i class="fas fa-check-circle me-1"></i>
                            Page created! You can now upload attachments or embed images below — or
                            <a href="{{ route('workspaces.pages.show', [$workspace, $page]) }}" class="alert-link">view the page</a>.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @if(session('status') === 'page-restored')
                        <div class="alert alert-success alert-dismissible mb-4 py-2" role="alert">
                            <i class="fas fa-undo me-1"></i>
                            Page restored to a previous version. Review the content below and save to confirm.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    <form method="POST" action="{{ route('workspaces.pages.update', [$workspace, $page]) }}" id="page-form">
                        @csrf
                        @method('PUT')

                        {{-- Title --}}
                        <div class="mb-3">
                            <x-label for="title" value="{{ __('Title') }}" />
                            <x-input id="title" name="title" type="text" class="mt-1 block w-full fs-5"
                                     value="{{ old('title', $page->title) }}" required autofocus />
                            <x-input-error for="title" class="mt-1" />
                        </div>

                        {{-- Content --}}
                        <div class="mb-3">
                            <x-label for="content" value="{{ __('Content') }}" />

                            @if($page->content_type === 'richtext')
                            <textarea id="content-rich" name="_content_rich" rows="2"
                                class="form-control mt-1">{{ old('content', $page->content) }}</textarea>
                            <input type="hidden" id="content" name="content" value="{{ old('content', $page->content) }}" />
                            @else
                            <textarea id="content" name="content" rows="24"
                                class="form-control mt-1 font-monospace small">{{ old('content', $page->content) }}</textarea>
                            @endif

                            <x-input-error for="content" class="mt-1" />
                        </div>

                        {{-- Status --}}
                        <div class="mb-3">
                            <x-label for="status" value="{{ __('Status') }}" />
                            <select name="status" id="status" class="form-select">
                                <option value="draft"     {{ $page->status === 'draft'     ? 'selected' : '' }}>Draft</option>
                                <option value="published" {{ $page->status === 'published' ? 'selected' : '' }}>Published</option>
                                <option value="archived"  {{ $page->status === 'archived'  ? 'selected' : '' }}>Archived</option>
                            </select>
                        </div>

                        {{-- Categories --}}
                        @if($categories->isNotEmpty())
                        <div class="mb-3">
                            <x-label value="{{ __('Categories') }}" />
                            <div class="d-flex flex-wrap gap-2 mt-1">
                                @foreach($categories as $cat)
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="category_ids[]"
                                           id="cat_{{ $cat->id }}" value="{{ $cat->id }}"
                                           {{ in_array($cat->id, old('category_ids', $page->categories->pluck('id')->toArray())) ? 'checked' : '' }} />
                                    <label class="form-check-label" for="cat_{{ $cat->id }}">{{ $cat->name }}</label>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        {{-- Tags --}}
                        <div class="mb-3">
                            <x-label for="tags" value="{{ __('Tags') }}" />
                            <x-input id="tags" name="tags" type="text" class="mt-1 block w-full"
                                     value="{{ old('tags', $page->tags->pluck('name')->join(', ')) }}"
                                     placeholder="e.g. api, setup, tutorial (comma-separated)" />
                        </div>

                        {{-- Change summary --}}
                        <div class="mb-4">
                            <x-label for="change_summary" value="{{ __('Change Summary (optional)') }}" />
                            <x-input id="change_summary" name="change_summary" type="text" class="mt-1 block w-full"
                                     value="{{ old('change_summary') }}" placeholder="Briefly describe your changes" />
                        </div>

                        <div class="d-flex align-items-center justify-content-between">
                            <a href="{{ route('workspaces.pages.show', [$workspace, $page]) }}"
                               class="btn btn-link text-body-secondary text-decoration-none">
                                Cancel
                            </a>
                            <x-button>{{ __('Save Changes') }}</x-button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- feat 1.9: Attachments panel --}}
            <div class="card shadow-sm mt-4">
                <div class="card-header py-3 d-flex align-items-center justify-content-between">
                    <h2 class="h6 fw-semibold mb-0">
                        <i class="fas fa-paperclip me-2 text-body-secondary"></i>Attachments
                        @php $attachments = $page->getMedia('attachments'); @endphp
                        @if($attachments->isNotEmpty())
                            <span class="badge bg-secondary-subtle text-secondary-emphasis fw-normal ms-1">
                                {{ $attachments->count() }}
                            </span>
                        @endif
                    </h2>
                </div>

                {{-- Existing attachments --}}
                @if($attachments->isNotEmpty())
                <div class="list-group list-group-flush">
                    @foreach($attachments as $media)
                    <div class="list-group-item d-flex align-items-center gap-3 py-2 px-3">
                        @if(str_starts_with($media->mime_type ?? '', 'image/'))
                            <img src="{{ $media->getUrl('thumb') }}" alt=""
                                 class="rounded flex-shrink-0 object-fit-cover"
                                 style="width:40px;height:40px;">
                        @else
                            <div class="d-flex align-items-center justify-content-center rounded bg-secondary-subtle flex-shrink-0"
                                 style="width:40px;height:40px;">
                                <i class="fas fa-file text-secondary"></i>
                            </div>
                        @endif
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="small fw-medium text-truncate">{{ $media->name }}</div>
                            <div class="text-body-secondary" style="font-size:0.72rem;">
                                {{ strtoupper($media->extension) }} &middot; {{ \Illuminate\Support\Number::fileSize($media->size, precision: 1) }}
                            </div>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <a href="{{ $media->getUrl() }}" target="_blank" rel="noopener"
                               class="btn btn-sm btn-outline-secondary" title="Download">
                                <i class="fas fa-download"></i>
                            </a>
                            <form method="POST"
                                  action="{{ route('workspaces.pages.attachments.destroy', [$workspace, $page, $media]) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"
                                        onclick="return confirm('Delete {{ addslashes($media->name) }}?')">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

                {{-- Upload new attachment --}}
                <div class="card-body p-3">
                    @if(session('status') === 'attachment-uploaded')
                        <div class="alert alert-success alert-dismissible py-2 mb-3 small" role="alert">
                            <i class="fas fa-check-circle me-1"></i> File uploaded.
                            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @if(session('status') === 'attachment-deleted')
                        <div class="alert alert-info alert-dismissible py-2 mb-3 small" role="alert">
                            <i class="fas fa-trash-alt me-1"></i> Attachment deleted.
                            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @error('attachment')
                        <div class="alert alert-danger py-2 mb-3 small">{{ $message }}</div>
                    @enderror

                    <form method="POST"
                          action="{{ route('workspaces.pages.attachments.store', [$workspace, $page]) }}"
                          enctype="multipart/form-data">
                        @csrf
                        <div class="d-flex gap-2">
                            <input type="file" name="attachment" id="attachment-input"
                                   class="form-control form-control-sm"
                                   accept=".jpg,.jpeg,.png,.gif,.webp,.svg,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.md,.zip,.csv" />
                            <button type="submit" class="btn btn-sm btn-outline-primary flex-shrink-0">
                                <i class="fas fa-upload me-1"></i> Upload
                            </button>
                        </div>
                        <div class="text-body-secondary mt-1" style="font-size:0.72rem;">
                            Max 20 MB &middot; Images, PDFs, Office files, archives
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Sprint 14.1: lock heartbeat + take-over + release on unload --}}
    @push('scripts')
    <script>
    (function () {
        const CSRF     = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        const lockUrl  = '{{ route('workspaces.pages.lock', [$workspace, $page]) }}';
        const unlockUrl= '{{ route('workspaces.pages.unlock', [$workspace, $page]) }}';
        let   lockedByOther = {{ $lockedByOther ? 'true' : 'false' }};

        // Heartbeat every 60 s
        const heartbeat = setInterval(async () => {
            try {
                const res = await fetch(lockUrl, {
                    method: 'PATCH',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                });
                if (res.status === 409) {
                    const data = await res.json();
                    // Another user grabbed the lock while we were editing
                    const banner = document.getElementById('lock-banner');
                    if (!banner) {
                        const div = document.createElement('div');
                        div.id = 'lock-banner';
                        div.className = 'alert alert-warning py-2 mb-0 small';
                        div.innerHTML = `<i class="fas fa-lock me-2"></i><strong>${data.lockedBy}</strong> has taken over editing.`;
                        document.querySelector('.card')?.prepend(div);
                    }
                }
            } catch (_) {}
        }, 60_000);

        // Release lock when the user navigates away (best-effort)
        window.addEventListener('pagehide', () => {
            navigator.sendBeacon(unlockUrl + '?_method=DELETE&_token=' + encodeURIComponent(CSRF));
            clearInterval(heartbeat);
        });

        // Take Over button
        document.getElementById('btn-take-over')?.addEventListener('click', async () => {
            const res = await fetch(lockUrl, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            });
            if (res.ok) {
                document.getElementById('lock-banner')?.remove();
                lockedByOther = false;
            }
        });
    })();
    </script>
    @endpush

    @if($page->content_type === 'richtext')
    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const form          = document.getElementById('page-form');
        const contentHidden = document.getElementById('content');

        if (window.tinyEditor) {
            const editor = window.tinyEditor({
                elId: 'content-rich',
                height: 500,
                externalPlugins: {
                    'mentions-lite': '/tiny-plugins/mentions-lite/plugin.js',
                    'callouts':      '/tiny-plugins/callouts/plugin.js',
                    'wiki-links':    '/tiny-plugins/wiki-links/plugin.js',
                },
                plugins: 'link lists code image table blockquote autolink hr mentions-lite callouts wiki-links',
                toolbar: 'undo redo | styles | bold italic underline | link image | bullist numlist | blockquote | alignleft aligncenter alignright | table | callout | wikiLink | mentionUser | removeformat | code',
                imageUploadUrl: '{{ route('workspaces.pages.editor-images.store', [$workspace, $page]) }}',
                codexWorkspaceId: '{{ $workspace->id }}',
            });
            editor.init();

            form.addEventListener('submit', () => {
                const inst = window.tinymce && tinymce.get('content-rich');
                if (inst) contentHidden.value = inst.getContent();
            }, { capture: true });
        }
    });
    </script>
    @endpush
    @endif
</x-app-layout>
