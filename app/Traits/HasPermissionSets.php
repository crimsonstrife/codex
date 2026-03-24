<?php

namespace App\Traits;

use App\Models\PermissionSet;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Trait HasPermissionSets
 *
 * Provides functionality to associate and manage permission sets with a model.
 */
trait HasPermissionSets
{
    /**
     * Define a many-to-many relationship between the current model and the PermissionSet model.
     *
     * @return BelongsToMany
     */
    public function permissionSets(): BelongsToMany
    {
        return $this->belongsToMany(PermissionSet::class, 'user_permission_sets');
    }
}
