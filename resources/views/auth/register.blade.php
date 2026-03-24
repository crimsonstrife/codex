<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <x-validation-errors class="mb-3" />

        @if (config('codex.forge.enabled') && config('codex.forge.url') && config('codex.forge.client_id') && config('codex.forge.client_secret'))
            <div class="mb-4">
                <a href="{{ route('forge.redirect') }}" class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/>
                    </svg>
                    {{ __('Sign up with Forge') }}
                </a>
            </div>

            <div class="d-flex align-items-center gap-3 mb-4">
                <hr class="flex-grow-1 m-0">
                <span class="text-body-secondary small">{{ __('or') }}</span>
                <hr class="flex-grow-1 m-0">
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <div class="mb-3">
                <x-label for="name" value="{{ __('Name') }}" />
                <x-input id="name" class="mt-1" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            </div>

            <div class="mb-3">
                <x-label for="email" value="{{ __('Email') }}" />
                <x-input id="email" class="mt-1" type="email" name="email" :value="old('email')" required autocomplete="username" />
            </div>

            <div class="mb-3">
                <x-label for="password" value="{{ __('Password') }}" />
                <x-input id="password" class="mt-1" type="password" name="password" required autocomplete="new-password" />
            </div>

            <div class="mb-3">
                <x-label for="password_confirmation" value="{{ __('Confirm Password') }}" />
                <x-input id="password_confirmation" class="mt-1" type="password" name="password_confirmation" required autocomplete="new-password" />
            </div>

            @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                <div class="mb-3">
                    <div class="form-check">
                        <x-checkbox name="terms" id="terms" class="form-check-input" required />
                        <label class="form-check-label" for="terms">
                            {!! __('I agree to the :terms_of_service and :privacy_policy', [
                                    'terms_of_service' => '<a target="_blank" href="'.route('terms.show').'" class="text-decoration-none">'.__('Terms of Service').'</a>',
                                    'privacy_policy' => '<a target="_blank" href="'.route('policy.show').'" class="text-decoration-none">'.__('Privacy Policy').'</a>',
                            ]) !!}
                        </label>
                    </div>
                </div>
            @endif

            <div class="d-flex align-items-center justify-content-end gap-3">
                <a class="small text-body-secondary text-decoration-none" href="{{ route('login') }}">
                    {{ __('Already registered?') }}
                </a>
                <x-button>{{ __('Register') }}</x-button>
            </div>
        </form>
    </x-authentication-card>
</x-guest-layout>
