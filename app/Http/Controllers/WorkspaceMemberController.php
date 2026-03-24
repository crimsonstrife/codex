<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WorkspaceMemberController extends Controller
{
    /**
     * feat 2.4 — Show the members management page.
     */
    public function index(Workspace $workspace)
    {
        $this->authorize('update', $workspace);

        $members = $workspace->members()->orderBy('name')->get();
        $owner   = $workspace->owner;

        $pendingInvitations = WorkspaceInvitation::where('workspace_id', $workspace->id)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->with('invitedBy')
            ->latest()
            ->get();

        return view('workspaces.members', compact('workspace', 'members', 'owner', 'pendingInvitations'));
    }

    /**
     * Add an existing user to the workspace.
     */
    public function store(Request $request, Workspace $workspace)
    {
        $this->authorize('update', $workspace);

        $validated = $request->validate([
            'email' => ['required', 'email', Rule::exists('users', 'email')],
            'role'  => ['required', Rule::in(['member', 'editor', 'admin'])],
        ]);

        $user = User::where('email', $validated['email'])->first();

        // Prevent adding the owner as a member or duplicate membership
        if ($user->id === $workspace->owner_id) {
            return back()->withErrors(['email' => 'This user is the workspace owner.']);
        }

        $workspace->members()->syncWithoutDetaching([
            $user->id => ['role' => $validated['role']],
        ]);

        return back()->with('status', 'member-added');
    }

    /**
     * Change a member's role.
     */
    public function update(Request $request, Workspace $workspace, User $member)
    {
        $this->authorize('update', $workspace);

        abort_if($member->id === $workspace->owner_id, 403, 'Cannot change the owner\'s role.');

        $validated = $request->validate([
            'role' => ['required', Rule::in(['member', 'editor', 'admin'])],
        ]);

        $workspace->members()->updateExistingPivot($member->id, ['role' => $validated['role']]);

        return back()->with('status', 'member-updated');
    }

    /**
     * Remove a member from the workspace.
     */
    public function destroy(Workspace $workspace, User $member)
    {
        $this->authorize('update', $workspace);

        abort_if($member->id === $workspace->owner_id, 403, 'Cannot remove the workspace owner.');

        $workspace->members()->detach($member->id);

        return back()->with('status', 'member-removed');
    }

    /**
     * AJAX — search users by name/email for the add-member typeahead.
     */
    public function search(Request $request, Workspace $workspace)
    {
        $this->authorize('update', $workspace);

        $q = trim($request->get('q', ''));

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $existingIds = $workspace->members()->pluck('users.id')
            ->push($workspace->owner_id);

        $users = User::where(function ($query) use ($q) {
            $query->where('name', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%");
        })
        ->whereNotIn('id', $existingIds)
        ->limit(8)
        ->get(['id', 'name', 'email']);

        return response()->json($users);
    }
}
