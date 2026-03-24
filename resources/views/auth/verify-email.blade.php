<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <p class="small text-body-secondary mb-3">
            {{ __('Before continuing, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
        </p>

        @if (session('status') == 'verification-link-sent')
            <div class="alert alert-success mb-3">
                {{ __('A new verification link has been sent to the email address you provided in your profile settings.') }}
            </div>
        @endif

        <div class="d-flex align-items-center justify-content-between gap-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <x-button type="submit">{{ __('Resend Verification Email') }}</x-button>
            </form>

            <div class="d-flex gap-3">
                <a href="{{ route('profile.show') }}" class="small text-body-secondary text-decoration-none">
                    {{ __('Edit Profile') }}
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-link btn-sm p-0 text-body-secondary text-decoration-none">
                        {{ __('Log Out') }}
                    </button>
                </form>
            </div>
        </div>
    </x-authentication-card>
</x-guest-layout>
