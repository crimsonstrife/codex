<?php

namespace App\Models;

use App\Traits\IsPermissible;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class ScriptProject extends Model
{
    use HasFactory;
    use HasSlug;
    use HasUuids;
    use IsPermissible;
    use LogsActivity;
    use SoftDeletes;

    public const TYPE_SCREENPLAY = 'screenplay';

    public const STATUS_DRAFT = 'draft';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'workspace_id',
        'author_id',
        'title',
        'slug',
        'type',
        'status',
        'logline',
        'synopsis',
        'document',
    ];

    protected function casts(): array
    {
        return [
            'document' => 'array',
            'id' => 'string',
        ];
    }

    public static function boot(): void
    {
        parent::boot();

        static::creating(static function ($model) {
            $model->id = Str::uuid();
            $model->type ??= self::TYPE_SCREENPLAY;
            $model->status ??= self::STATUS_DRAFT;
            $model->document ??= self::defaultDocument();
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('script')
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

    public static function defaultDocument(): array
    {
        return [
            'version' => 1,
            'blocks' => [
                [
                    'id' => Str::uuid()->toString(),
                    'type' => 'scene_heading',
                    'text' => '',
                    'position' => 1,
                    'character_entity_id' => null,
                    'location_entity_id' => null,
                    'modifiers' => null,
                    'meta' => [
                        'prefix' => 'INT.',
                        'time_of_day' => 'DAY',
                    ],
                ],
                [
                    'id' => Str::uuid()->toString(),
                    'type' => 'action',
                    'text' => '',
                    'position' => 2,
                    'character_entity_id' => null,
                    'location_entity_id' => null,
                    'modifiers' => null,
                    'meta' => [],
                ],
            ],
        ];
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
        return $this->hasMany(ScriptRevision::class)->orderByDesc('revision_number');
    }

    public function entities(): HasMany
    {
        return $this->hasMany(ScriptEntity::class);
    }

    public function characters(): HasMany
    {
        return $this->entities()->where('type', ScriptEntity::TYPE_CHARACTER);
    }

    public function locations(): HasMany
    {
        return $this->entities()->where('type', ScriptEntity::TYPE_LOCATION);
    }

    public function binderLinks(): HasMany
    {
        return $this->hasMany(ScriptProjectPageLink::class)->orderBy('position');
    }

    public function binderPages(): BelongsToMany
    {
        return $this->belongsToMany(Page::class, 'script_project_page_links')
            ->withPivot(['id', 'role', 'position'])
            ->withTimestamps()
            ->orderByPivot('position');
    }
}
