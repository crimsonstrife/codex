<?php

namespace App\Models;

use App\Traits\IsPermissible;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Tags\HasTags;

class Diagram extends Model
{
    use HasFactory;
    use HasSlug;
    use HasTags;
    use HasUuids;
    use IsPermissible;
    use Searchable;
    use SoftDeletes;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'workspace_id',
        'author_id',
        'diagram_data',
        'diagram_type',
        'is_published',
        'thumbnail_url',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
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

    public function categories(): MorphToMany
    {
        return $this->morphToMany(Category::class, 'categorizable');
    }

    public function pageEmbeds(): HasMany
    {
        return $this->hasMany(PageDiagramEmbed::class);
    }

    public function embeddedPages(): BelongsToMany
    {
        return $this->belongsToMany(Page::class, 'page_diagram_embeds')
            ->withTimestamps();
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title.' '.$this->title, // weight title 2×
            'description' => $this->description ?? '',
        ];
    }
}
