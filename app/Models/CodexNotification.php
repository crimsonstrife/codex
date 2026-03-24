<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CodexNotification extends Model
{
    use HasUuids;

    protected $table = 'codex_notifications';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['user_id', 'type', 'page_id', 'message', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function page(): BelongsTo { return $this->belongsTo(Page::class); }

    public function isRead(): bool { return $this->read_at !== null; }
}
