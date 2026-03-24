<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invalid Invitation — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-body-tertiary">

<div class="d-flex align-items-center justify-content-center min-vh-100 py-5">
    <div class="card shadow-sm text-center" style="max-width: 26rem; width: 100%;">
        <div class="card-body p-5">
            <div class="display-4 mb-3">{{ $reason === 'accepted' ? '✅' : '⏰' }}</div>
            <h1 class="h4 fw-bold mb-2">
                {{ $reason === 'accepted' ? 'Invitation already used' : 'Invitation expired' }}
            </h1>
            <p class="text-body-secondary mb-4">
                @if($reason === 'accepted')
                    This invitation has already been accepted. If you're not yet a member,
                    please ask the workspace owner for a new link.
                @else
                    This invitation link has expired (links are valid for 72 hours).
                    Please ask the workspace owner to send a new one.
                @endif
            </p>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-home me-1"></i> Go to Dashboard
            </a>
        </div>
    </div>
</div>

</body>
</html>
