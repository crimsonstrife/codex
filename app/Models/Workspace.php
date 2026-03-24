<?php

namespace App\Models;

use App\Models\PagePin;
use App\Traits\IsPermissible;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Tags\HasTags;

class Workspace extends Model
{
    use HasFactory;
    use HasSlug;
    use HasTags;
    use HasUuids;
    use IsPermissible;
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'forge_project_id',
        'forge_project_key',
        'owner_id',
        'is_public',
        'color',
        'icon',
        'home_page_id',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'id' => 'string',
        ];
    }

    public static function boot(): void
    {
        parent::boot();
        static::creating(static function ($model) {
            $model->id = Str::uuid();
        });
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    /**
     * Scope: workspaces visible to the given user.
     * Matches public workspaces, workspaces the user owns, and workspaces they are a member of.
     *
     * @param  Builder  $query
     * @param  \App\Models\User|null  $user
     */
    public function scopeAccessibleBy(Builder $query, ?User $user): Builder
    {
        return $query->where(function (Builder $q) use ($user) {
            $q->where('is_public', true);
            if ($user) {
                $q->orWhere('owner_id', $user->id)
                  ->orWhereHas('members', fn (Builder $m) => $m->where('user_id', $user->id));
            }
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_user', 'workspace_id', 'user_id')
            ->withPivot(['role'])
            ->withTimestamps();
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public function diagrams(): HasMany
    {
        return $this->hasMany(Diagram::class);
    }

    public function pinnedPages(): BelongsToMany
    {
        return $this->belongsToMany(Page::class, 'page_pins')
            ->withPivot('position')
            ->orderByPivot('position');
    }

    /**
     * The page designated as this workspace's home / landing page.
     * When set, visitors to the workspace URL are redirected here.
     */
    public function homePage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'home_page_id');
    }
}
