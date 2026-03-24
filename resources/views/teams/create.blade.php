<x-app-layout>
    <x-slot name="header">
        <h2 class="h5 mb-0">{{ __('Create Team') }}</h2>
    </x-slot>

    <div class="py-4">
        <div class="container">
            @livewire('teams.create-team-form')
        </div>
    </div>
</x-app-layout>
