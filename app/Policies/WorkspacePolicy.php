<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;

class WorkspacePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Workspace $workspace): bool
    {
        return $workspace->is_public
            || $workspace->owner_id === $user->id
            || $workspace->members()->where('user_id', $user->id)->exists()
            || $user->can('workspaces.view');
    }

    public function create(User $user): bool
    {
        return $user->can('workspaces.create');
    }

    public function update(User $user, Workspace $workspace): bool
    {
        return $workspace->owner_id === $user->id || $user->can('workspaces.update');
    }

    public function delete(User $user, Workspace $workspace): bool
    {
        return $workspace->owner_id === $user->id || $user->can('workspaces.delete');
    }
}
