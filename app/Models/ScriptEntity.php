<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ScriptEntity extends Model
{
    use HasFactory;
    use HasUuids;

    public const TYPE_CHARACTER = 'character';

    public const TYPE_LOCATION = 'location';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'script_project_id',
        'created_by',
        'type',
        'name',
        'display_name',
        'aliases',
        'notes',
        'hierarchy_text',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'aliases' => 'array',
            'meta' => 'array',
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

    public function scriptProject(): BelongsTo
    {
        return $this->belongsTo(ScriptProject::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function label(): string
    {
        return $this->display_name ?: $this->name;
    }
}
