<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Category extends Model
{
    use HasFactory;
    use HasSlug;
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'slug',
        'color',
        'workspace_id',
    ];

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

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function pages(): MorphToMany
    {
        return $this->morphedByMany(Page::class, 'categorizable');
    }

    public function diagrams(): MorphToMany
    {
        return $this->morphedByMany(Diagram::class, 'categorizable');
    }

    public function scripts(): MorphToMany
    {
        return $this->morphedByMany(ScriptProject::class, 'categorizable');
    }
}
