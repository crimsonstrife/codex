<?php

namespace App\Models;

use App\Traits\HasPermissionSets;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use HasApiTokens;
    use HasFactory;
    use HasPermissions;
    use HasPermissionSets;
    use HasProfilePhoto;
    use HasRoles;
    use HasTeams;
    use HasUuids;
    use Notifiable;
    use TwoFactorAuthenticatable;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'email',
        'password',
        'email_notifications', // sprint 12.3
        'forge_user_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected $appends = [
        'profile_photo_url',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'id' => 'string',
            'email_notifications' => 'boolean',
        ];
    }

    public static function boot(): void
    {
        parent::boot();
        static::creating(static function ($model) {
            $model->id = Str::uuid();
        });
    }

    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'workspace_user', 'user_id', 'workspace_id')
            ->withPivot(['role'])
            ->withTimestamps();
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class, 'author_id');
    }

    public function scriptProjects(): HasMany
    {
        return $this->hasMany(ScriptProject::class, 'author_id');
    }

    public function starredPages(): BelongsToMany
    {
        return $this->belongsToMany(Page::class, 'page_stars')
            ->with('workspace')
            ->latest('page_stars.created_at')
            ->withTimestamps();
    }

    public function watchedPages(): BelongsToMany
    {
        return $this->belongsToMany(Page::class, 'page_watches')
            ->withTimestamps();
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(CodexNotification::class, 'user_id');
    }

    public function ownedWorkspaces(): HasMany
    {
        return $this->hasMany(Workspace::class, 'owner_id');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyPermission(
            ['is-super-admin', 'filament.access', 'is-admin', 'is-panel-user', 'admin.panel.access'],
            filament()->getAuthGuard()
        );
    }
}
