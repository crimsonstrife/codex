<x-app-layout>
    <x-slot name="header">
        <h2 class="h5 mb-0">{{ __('API Tokens') }}</h2>
    </x-slot>

    <div class="py-4">
        <div class="container">
            @livewire('api.api-token-manager')
        </div>
    </div>
</x-app-layout>
