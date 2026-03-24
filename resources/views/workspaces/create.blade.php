<x-app-layout>
    <x-slot name="header">
        <h2 class="h5 mb-0">{{ __('Create Workspace') }}</h2>
    </x-slot>

    <div class="py-4">
        <div class="container" style="max-width: 40rem;">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('workspaces.store') }}">
                        @csrf
                        <div class="mb-3">
                            <x-label for="name" value="{{ __('Name') }}" />
                            <x-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name') }}" required autofocus />
                            <x-input-error for="name" class="mt-1" />
                        </div>
                        <div class="mb-3">
                            <x-label for="description" value="{{ __('Description') }}" />
                            <textarea id="description" name="description" rows="3" class="form-control">{{ old('description') }}</textarea>
                        </div>
                        <div class="mb-3">
                            <x-label for="color" value="{{ __('Accent Color') }}" />
                            <input type="color" id="color" name="color" value="{{ old('color', '#6366f1') }}"
                                   class="form-control form-control-color mt-1" style="width: 5rem;" />
                        </div>
                        <div class="mb-4">
                            <div class="form-check">
                                <x-checkbox id="is_public" name="is_public" class="form-check-input" :checked="old('is_public')" />
                                <label class="form-check-label" for="is_public">{{ __('Make this workspace public') }}</label>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <x-button>{{ __('Create Workspace') }}</x-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
