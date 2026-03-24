<?php

namespace App\Policies;

use App\Models\Page;
use App\Models\User;

class PagePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Page $page): bool
    {
        $workspace = $page->workspace;

        return $workspace->is_public
            || $page->author_id === $user->id
            || $workspace->owner_id === $user->id
            || $workspace->members()->where('user_id', $user->id)->exists()
            || $user->can('pages.view');
    }

    public function create(User $user): bool
    {
        return $user->can('pages.create');
    }

    public function duplicate(User $user, Page $page): bool
    {
        $workspace = $page->workspace;

        return $workspace->owner_id === $user->id
            || $workspace->members()
                ->where('user_id', $user->id)
                ->whereIn('role', ['editor', 'admin'])
                ->exists()
            || $user->can('pages.create');
    }

    public function update(User $user, Page $page): bool
    {
        return $page->author_id === $user->id || $user->can('pages.update');
    }

    public function delete(User $user, Page $page): bool
    {
        return $page->author_id === $user->id || $user->can('pages.delete');
    }
}
