<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ScriptRevision extends Model
{
    use HasFactory;
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'script_project_id',
        'user_id',
        'title',
        'status',
        'logline',
        'synopsis',
        'document',
        'revision_number',
        'change_summary',
    ];

    protected function casts(): array
    {
        return [
            'document' => 'array',
            'id' => 'string',
            'revision_number' => 'integer',
        ];
    }

    public static function boot(): void
    {
        parent::boot();

        static::creating(static function ($model) {
            $model->id = Str::uuid();
        });
    }

    public function scriptProject(): BelongsTo
    {
        return $this->belongsTo(ScriptProject::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
