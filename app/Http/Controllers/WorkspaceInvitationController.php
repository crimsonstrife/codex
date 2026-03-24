<?php

namespace App\Http\Controllers;

use App\Mail\WorkspaceInvitationMail;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

/**
 * Workspace invitation management.
 *
 * Design principle: this controller NEVER creates a User record.
 * The invitation token is stored in the session after `accept()` and
 * consumed by AcceptPendingWorkspaceInvitations on the next Login event —
 * whether that login comes from Fortify, Forge SSO, or any future provider.
 */
class WorkspaceInvitationController extends Controller
{
    /**
     * Send (or create a link-only) invitation.
     *
     * POST /workspaces/{workspace}/invitations
     */
    public function store(Request $request, Workspace $workspace)
    {
        $this->authorize('update', $workspace);

        $validated = $request->validate([
            'email' => ['nullable', 'email', 'max:255'],
            'role'  => ['required', Rule::in(['member', 'editor', 'admin'])],
        ]);

        $email = $validated['email'] ?? null;

        // Prevent inviting existing members or the owner
        if ($email) {
            $alreadyMember = $workspace->members()
                ->where('email', $email)->exists()
                || $workspace->owner->email === $email;

            if ($alreadyMember) {
                return back()->withErrors(['email' => 'That person is already a member of this workspace.']);
            }

            // Cancel any existing pending invite for this email + workspace
            WorkspaceInvitation::where('workspace_id', $workspace->id)
                ->where('email', $email)
                ->whereNull('accepted_at')
                ->delete();
        }

        $invitation = WorkspaceInvitation::create([
            'workspace_id' => $workspace->id,
            'invited_by'   => auth()->id(),
            'email'        => $email,
            'role'         => $validated['role'],
            'expires_at'   => now()->addHours(72),
        ]);

        if ($email) {
            Mail::to($email)->queue(new WorkspaceInvitationMail($invitation));
            return back()->with('status', 'invitation-sent');
        }

        // Generic link: redirect back with the link URL as a flash
        return back()
            ->with('status', 'invitation-link-created')
            ->with('invitation_link', route('invitations.show', $invitation->token));
    }

    /**
     * Public landing page — no auth required.
     *
     * GET /invitations/{token}
     */
    public function show(string $token)
    {
        $invitation = WorkspaceInvitation::where('token', $token)->firstOrFail();

        if (! $invitation->isPending()) {
            return view('workspaces.invitation-invalid', ['reason' => $invitation->accepted_at ? 'accepted' : 'expired']);
        }

        return view('workspaces.invitation-show', compact('invitation'));
    }

    /**
     * Store the token in session and redirect to login.
     * No auth required — the Login listener will consume the token after auth.
     *
     * POST /invitations/{token}/accept
     */
    public function accept(string $token)
    {
        $invitation = WorkspaceInvitation::where('token', $token)->firstOrFail();

        if (! $invitation->isPending()) {
            return redirect()->route('login')
                ->with('error', 'This invitation has already been used or has expired.');
        }

        // If the user is already logged in, join immediately
        if (Auth::check()) {
            $this->joinWorkspace($invitation, Auth::user());
            return redirect()->route('workspaces.show', $invitation->workspace)
                ->with('status', 'invitation-accepted');
        }

        // Store token — consumed by AcceptPendingWorkspaceInvitations on Login
        session(['workspace_invitation_token' => $token]);

        return redirect()->route('login')
            ->with('info', 'Sign in (or create an account) to join ' . $invitation->workspace->name . '.');
    }

    /**
     * Revoke a pending invitation.
     *
     * DELETE /workspaces/{workspace}/invitations/{invitation}
     */
    public function destroy(Workspace $workspace, WorkspaceInvitation $invitation)
    {
        $this->authorize('update', $workspace);
        abort_if($invitation->workspace_id !== $workspace->id, 404);

        $invitation->delete();

        return back()->with('status', 'invitation-revoked');
    }

    /**
     * Add the invited user to the workspace and mark the invitation accepted.
     * Called by accept() when already logged in, and by the Login listener.
     */
    public static function joinWorkspace(WorkspaceInvitation $invitation, \App\Models\User $user): void
    {
        if (! $invitation->isPending()) {
            return;
        }

        // Don't add if already owner or member
        $alreadyIn = $invitation->workspace->owner_id === $user->id
            || $invitation->workspace->members()->where('user_id', $user->id)->exists();

        if (! $alreadyIn) {
            $invitation->workspace->members()->syncWithoutDetaching([
                $user->id => ['role' => $invitation->role],
            ]);
        }

        $invitation->update(['accepted_at' => now()]);
    }
}
