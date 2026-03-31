<?php

namespace App\Policies;

use App\Models\ScriptProject;
use App\Models\User;

class ScriptProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ScriptProject $scriptProject): bool
    {
        $workspace = $scriptProject->workspace;

        return $workspace->is_public
            || $scriptProject->author_id === $user->id
            || $workspace->owner_id === $user->id
            || $workspace->members()->where('user_id', $user->id)->exists()
            || $user->can('scripts.view')
            || $user->can('pages.view');
    }

    public function create(User $user): bool
    {
        return $user->can('scripts.create') || $user->can('pages.create') || $user->can('workspaces.update');
    }

    public function update(User $user, ScriptProject $scriptProject): bool
    {
        $workspace = $scriptProject->workspace;

        return $scriptProject->author_id === $user->id
            || $workspace->owner_id === $user->id
            || $workspace->members()
                ->where('user_id', $user->id)
                ->whereIn('role', ['editor', 'admin'])
                ->exists()
            || $user->can('scripts.update')
            || $user->can('pages.update');
    }

    public function delete(User $user, ScriptProject $scriptProject): bool
    {
        $workspace = $scriptProject->workspace;

        return $scriptProject->author_id === $user->id
            || $workspace->owner_id === $user->id
            || $user->can('scripts.delete')
            || $user->can('pages.delete');
    }
}
