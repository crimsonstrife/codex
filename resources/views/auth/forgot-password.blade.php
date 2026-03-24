<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <p class="small text-body-secondary mb-3">
            {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
        </p>

        @session('status')
            <div class="alert alert-success mb-3">{{ $value }}</div>
        @endsession

        <x-validation-errors class="mb-3" />

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="mb-3">
                <x-label for="email" value="{{ __('Email') }}" />
                <x-input id="email" class="mt-1" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            </div>

            <div class="d-flex justify-content-end">
                <x-button>{{ __('Email Password Reset Link') }}</x-button>
            </div>
        </form>
    </x-authentication-card>
</x-guest-layout>
