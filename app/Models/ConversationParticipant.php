<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationParticipant extends Model
{
    protected $fillable = ['user_id', 'joined_at', 'last_read_message_id', 'cleared_at', 'hidden_at'];
    protected $casts = ['joined_at' => 'datetime', 'last_read_message_id' => 'integer', 'cleared_at' => 'datetime', 'hidden_at' => 'datetime'];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
