<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column gap-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('workspaces.show', $workspace) }}">{{ $workspace->name }}</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">New Script</li>
                </ol>
            </nav>

            @include('workspaces._header_actions')
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container" style="max-width: 56rem;">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('workspaces.scripts.store', $workspace) }}">
                        @csrf

                        <div class="mb-3">
                            <x-label for="title" value="{{ __('Title') }}" />
                            <x-input id="title" name="title" type="text" class="mt-1 block w-full fs-5"
                                     value="{{ old('title') }}" required autofocus />
                            <x-input-error for="title" class="mt-1" />
                        </div>

                        <div class="mb-3">
                            <x-label for="logline" value="{{ __('Logline') }}" />
                            <x-input id="logline" name="logline" type="text" class="mt-1 block w-full"
                                     value="{{ old('logline') }}" />
                            <x-input-error for="logline" class="mt-1" />
                        </div>

                        <div class="mb-3">
                            <x-label for="synopsis" value="{{ __('Synopsis') }}" />
                            <textarea id="synopsis" name="synopsis" rows="6"
                                      class="form-control mt-1">{{ old('synopsis') }}</textarea>
                            <x-input-error for="synopsis" class="mt-1" />
                        </div>

                        <div class="mb-4">
                            <x-label for="status" value="{{ __('Status') }}" />
                            <select name="status" id="status" class="form-select mt-1">
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>

                        <div class="d-flex align-items-center justify-content-between">
                            <a href="{{ route('workspaces.show', $workspace) }}"
                               class="btn btn-link text-body-secondary text-decoration-none">
                                Cancel
                            </a>
                            <x-button>{{ __('Create Script') }}</x-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
