<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Workspace Invitation — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-body-tertiary">

<div class="d-flex align-items-center justify-content-center min-vh-100 py-5">
    <div class="card shadow-sm" style="max-width: 26rem; width: 100%;">
        <div class="card-body p-5 text-center">

            {{-- Workspace icon --}}
            <div class="d-inline-flex align-items-center justify-content-center rounded-3 text-white fw-bold mb-4"
                 style="width:3.5rem;height:3.5rem;background-color:{{ $invitation->workspace->color ?? '#6366f1' }};font-size:1.4rem;">
                {{ $invitation->workspace->icon ?? strtoupper(substr($invitation->workspace->name, 0, 1)) }}
            </div>

            <h1 class="h4 fw-bold mb-1">You've been invited</h1>
            <p class="text-body-secondary mb-4">
                <strong>{{ $invitation->invitedBy->name }}</strong> invited you to join
                <strong>{{ $invitation->workspace->name }}</strong>
                as a <span class="badge bg-primary-subtle text-primary-emphasis">{{ ucfirst($invitation->role) }}</span>.
            </p>

            <form method="POST" action="{{ route('invitations.accept', $invitation->token) }}">
                @csrf
                <button type="submit" class="btn btn-primary w-100 mb-3">
                    <i class="fas fa-check me-2"></i>Accept Invitation
                </button>
            </form>

            <p class="text-body-secondary mb-0" style="font-size:.8rem;">
                You'll be asked to sign in or create an account.<br>
                This invitation expires {{ $invitation->expires_at->diffForHumans() }}.
            </p>

            @if(session('info'))
                <div class="alert alert-info mt-3 py-2 small mb-0">{{ session('info') }}</div>
            @endif
        </div>
    </div>
</div>

</body>
</html>
