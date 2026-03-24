<x-app-layout>
    <x-slot name="header">
        <h2 class="h5 mb-0">{{ __('Profile') }}</h2>
    </x-slot>

    <div class="py-4">
        <div class="container">
            @if (Laravel\Fortify\Features::canUpdateProfileInformation())
                @livewire('profile.update-profile-information-form')
                <x-section-border />
            @endif

            @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
                @livewire('profile.update-password-form')
                <x-section-border />
            @endif

            @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                @livewire('profile.two-factor-authentication-form')
                <x-section-border />
            @endif

            @livewire('profile.logout-other-browser-sessions-form')

            @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())
                <x-section-border />
                @livewire('profile.delete-user-form')
            @endif

            {{-- Sprint 12.3: Notification preferences --}}
            <x-section-border />
            <div class="mt-10 sm:mt-0">
                <x-form-section submit="">
                    <x-slot name="title">{{ __('Notification Preferences') }}</x-slot>
                    <x-slot name="description">
                        {{ __('Choose how you want to be notified when pages you are watching are updated.') }}
                    </x-slot>

                    <x-slot name="form">
                        @if(session('status') === 'preferences-saved')
                            <div class="col-span-6">
                                <div class="alert alert-success py-2 small" role="alert">
                                    <i class="fas fa-check-circle me-1"></i> Preferences saved.
                                </div>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('profile.preferences.update') }}" id="notification-prefs-form">
                            @csrf
                            @method('PATCH')

                            <div class="col-span-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           id="email_notifications" name="email_notifications" value="1"
                                           {{ auth()->user()->email_notifications ? 'checked' : '' }}
                                           onchange="document.getElementById('notification-prefs-form').submit()">
                                    <label class="form-check-label" for="email_notifications">
                                        <span class="fw-medium">Email notifications for watched pages</span>
                                        <span class="d-block small text-body-secondary">
                                            Receive an email whenever a page you are watching is updated.
                                            In-app bell notifications are always sent regardless of this setting.
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </form>
                    </x-slot>

                    <x-slot name="actions">{{-- no submit button — auto-submits on toggle --}}</x-slot>
                </x-form-section>
            </div>
        </div>
    </div>
</x-app-layout>
