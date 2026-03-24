<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagePin extends Model
{
    protected $fillable = ['workspace_id', 'page_id', 'position'];

    public function workspace(): BelongsTo { return $this->belongsTo(Workspace::class); }
    public function page(): BelongsTo { return $this->belongsTo(Page::class); }
}
