<?php

namespace App\Models;

use App\Traits\IsPermissible;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Kalnoy\Nestedset\NodeTrait;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Tags\HasTags;

class Page extends Model implements HasMedia
{
    use HasFactory;
    use HasSlug;
    use HasTags;
    use HasUuids;
    use InteractsWithMedia;
    use IsPermissible;
    use LogsActivity;

    // NodeTrait and Searchable both define usesSoftDelete(); keep NodeTrait's
    // version since the nestedset tree operations depend on its implementation.
    use NodeTrait, Searchable {
        NodeTrait::usesSoftDelete insteadof Searchable;
    }
    use SoftDeletes;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'content_type',
        'workspace_id',
        'author_id',
        'parent_id',
        'is_published',
        'published_at',
        'excerpt',
        'status',
        'locked_by',
        'locked_at',
        '_lft',
        '_rgt',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'locked_at' => 'datetime',
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('page')
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => 'created',
                'updated' => 'updated',
                'deleted' => 'deleted',
                default => $eventName,
            });
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(PageRevision::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Page::class, 'parent_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(PageComment::class)->whereNull('parent_id')->with('user', 'replies')->oldest();
    }

    public function categories(): MorphToMany
    {
        return $this->morphToMany(Category::class, 'categorizable');
    }

    public function stars(): HasMany
    {
        return $this->hasMany(PageStar::class);
    }

    public function isStarredBy(User $user): bool
    {
        return $this->stars()->where('user_id', $user->id)->exists();
    }

    public function watches(): HasMany
    {
        return $this->hasMany(PageWatch::class);
    }

    /**
     * Pages this page links TO via [[Page Title]] syntax.
     */
    public function outgoingLinks(): HasMany
    {
        return $this->hasMany(PageLink::class, 'source_page_id');
    }

    /**
     * Pages that link TO this page via [[Page Title]] syntax.
     */
    public function incomingLinks(): HasMany
    {
        return $this->hasMany(PageLink::class, 'target_page_id');
    }

    public function scriptProjectLinks(): HasMany
    {
        return $this->hasMany(ScriptProjectPageLink::class)->orderBy('position');
    }

    public function scriptProjects(): BelongsToMany
    {
        return $this->belongsToMany(ScriptProject::class, 'script_project_page_links')
            ->withPivot(['id', 'role', 'position'])
            ->withTimestamps()
            ->orderByPivot('position');
    }

    public function diagramEmbeds(): HasMany
    {
        return $this->hasMany(PageDiagramEmbed::class);
    }

    public function embeddedDiagrams(): BelongsToMany
    {
        return $this->belongsToMany(Diagram::class, 'page_diagram_embeds')
            ->withTimestamps();
    }

    public function isWatchedBy(User $user): bool
    {
        return $this->watches()->where('user_id', $user->id)->exists();
    }

    /** The user currently holding the edit lock (if any). */
    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    /** Lock TTL in seconds (15 minutes). */
    public const LOCK_TTL = 900;

    /**
     * True if the page is locked by a *different* user with a non-expired lock.
     */
    public function isLockedByAnother(?User $viewer = null): bool
    {
        if (! $this->locked_by || ! $this->locked_at) {
            return false;
        }
        if ($this->locked_at->diffInSeconds(now()) >= self::LOCK_TTL) {
            return false; // expired
        }
        $viewerId = $viewer?->id ?? auth()->id();

        return $this->locked_by !== $viewerId;
    }

    /**
     * Acquire (or refresh) the edit lock for the given user.
     */
    public function acquireLock(User $user): void
    {
        $this->updateQuietly(['locked_by' => $user->id, 'locked_at' => now()]);
    }

    /**
     * Release the lock unconditionally.
     */
    public function releaseLock(): void
    {
        $this->updateQuietly(['locked_by' => null, 'locked_at' => null]);
    }

    /**
     * Estimated reading time in minutes (200 wpm average, minimum 1 minute).
     */
    public function getReadingTimeAttribute(): int
    {
        $words = str_word_count(strip_tags($this->content ?? ''));

        return max(1, (int) ceil($words / 200));
    }

    public function registerMediaCollections(): void
    {
        // General file attachments (any type)
        $this->addMediaCollection('attachments');

        // Images uploaded directly via the TinyMCE editor
        $this->addMediaCollection('editor-images')
            ->acceptsMimeTypes([
                'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            ]);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // Small thumbnail used in the attachment panel
        $this->addMediaConversion('thumb')
            ->width(160)
            ->height(160)
            ->nonQueued()
            ->performOnCollections('attachments', 'editor-images');
    }

    /**
     * Fields indexed by Scout.
     * Title is included twice so word-split matches on the title score higher
     * (the database driver counts occurrences across the array values).
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title.' '.$this->title, // weight title 2×
            'excerpt' => $this->excerpt ?? '',
            'content' => strip_tags($this->content ?? ''),
        ];
    }

    /**
     * @deprecated Use Scout's Page::search() instead.
     * Kept as a fallback scope; SearchController no longer calls this.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
                ->orWhere('content', 'like', "%{$term}%");
        });
    }
}
