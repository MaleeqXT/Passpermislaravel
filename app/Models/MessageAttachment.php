<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MessageAttachment extends Model
{
    protected $fillable = ['path', 'name', 'mime', 'size'];
    protected $appends = ['url'];

    public function message(): BelongsTo { return $this->belongsTo(Message::class); }
    public function getUrlAttribute(): string { return '/api/chat/attachments/'.$this->id; }
}
