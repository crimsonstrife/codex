<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PageDiagramEmbed extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'page_id',
        'diagram_id',
    ];

    public static function boot(): void
    {
        parent::boot();
        static::creating(static function ($model) {
            $model->id = Str::uuid();
        });
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function diagram(): BelongsTo
    {
        return $this->belongsTo(Diagram::class);
    }
}
