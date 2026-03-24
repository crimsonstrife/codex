<?php

namespace App\Policies;

use App\Models\Diagram;
use App\Models\User;

class DiagramPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Diagram $diagram): bool
    {
        $workspace = $diagram->workspace;

        return $workspace->is_public
            || $diagram->author_id === $user->id
            || $workspace->owner_id === $user->id
            || $workspace->members()->where('user_id', $user->id)->exists()
            || $user->can('diagrams.view');
    }

    public function create(User $user): bool
    {
        return $user->can('diagrams.create');
    }

    public function update(User $user, Diagram $diagram): bool
    {
        return $diagram->author_id === $user->id || $user->can('diagrams.update');
    }

    public function delete(User $user, Diagram $diagram): bool
    {
        return $diagram->author_id === $user->id || $user->can('diagrams.delete');
    }
}
