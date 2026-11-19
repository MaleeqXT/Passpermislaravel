<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessageEmailDelivery extends Model
{
    protected $fillable = ['message_id', 'recipient_user_id', 'status', 'error_code', 'attempted_at', 'sent_at'];
    protected $casts = ['attempted_at' => 'datetime', 'sent_at' => 'datetime'];

    public function message(): BelongsTo { return $this->belongsTo(Message::class); }
    public function recipient(): BelongsTo { return $this->belongsTo(User::class, 'recipient_user_id'); }
}
