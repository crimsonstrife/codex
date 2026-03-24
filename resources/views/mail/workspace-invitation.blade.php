<x-mail::message>
# You've been invited to join {{ $invitation->workspace->name }}

**{{ $invitation->invitedBy->name }}** has invited you to collaborate on the
**{{ $invitation->workspace->name }}** workspace on Codex, as a **{{ ucfirst($invitation->role) }}**.

<x-mail::panel>
**Workspace:** {{ $invitation->workspace->name }}
**Role:** {{ ucfirst($invitation->role) }}
**Invited by:** {{ $invitation->invitedBy->name }}
**Expires:** {{ $invitation->expires_at->toFormattedDateString() }}
</x-mail::panel>

Click the button below to accept your invitation. You'll be asked to sign in
(or create an account) if you aren't already logged in.

<x-mail::button :url="route('invitations.show', $invitation->token)">
Accept Invitation
</x-mail::button>

If you weren't expecting this invitation you can safely ignore this email.
The link expires in 72 hours.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
