<x-app-layout>
    <x-slot name="header">
        <h2 class="h5 mb-0">{{ __('Team Settings') }}</h2>
    </x-slot>

    <div class="py-4">
        <div class="container">
            @livewire('teams.update-team-name-form', ['team' => $team])

            <x-section-border />

            @livewire('teams.team-member-manager', ['team' => $team])

            @if (Gate::check('delete', $team) && ! $team->personal_team)
                <x-section-border />
                @livewire('teams.delete-team-form', ['team' => $team])
            @endif
        </div>
    </div>
</x-app-layout>
