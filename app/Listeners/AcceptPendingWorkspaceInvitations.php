<?php

namespace App\Listeners;

use App\Http\Controllers\WorkspaceInvitationController;
use App\Models\WorkspaceInvitation;
use Illuminate\Auth\Events\Login;

/**
 * Automatically accept any pending workspace invitation after a successful login.
 *
 * Two acceptance paths are handled:
 *
 *  1. Session token  — set by WorkspaceInvitationController::accept() when the
 *                      user clicked an invite link before being authenticated.
 *                      Works for Fortify (email/password) and Forge SSO alike
 *                      because both end in Auth::login() which fires this event.
 *
 *  2. Email sweep    — matches *all* pending invitations addressed to the
 *                      user's email, so an SSO user who never clicked a link
 *                      (e.g. an admin pre-invited them) is still auto-joined.
 *
 * This listener intentionally never creates a User record.
 */
class AcceptPendingWorkspaceInvitations
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        $token = session()->pull('workspace_invitation_token');

        if ($token) {
            $invitation = WorkspaceInvitation::where('token', $token)->first();
            if ($invitation && $invitation->isPending()) {
                WorkspaceInvitationController::joinWorkspace($invitation, $user);
            }
        }

        if ($user->email) {
            WorkspaceInvitation::where('email', $user->email)
                ->whereNull('accepted_at')
                ->where('expires_at', '>', now())
                ->with('workspace')
                ->get()
                ->each(fn ($inv) => WorkspaceInvitationController::joinWorkspace($inv, $user));
        }
    }
}
