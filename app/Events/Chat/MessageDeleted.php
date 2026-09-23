<?php

namespace App\Events\Chat;

use App\Models\Conversation;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class MessageDeleted implements ShouldBroadcastNow
{
    public function __construct(private Conversation $conversation, private int $messageId) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('conversation.'.$this->conversation->id),
            ...$this->conversation->participants()->pluck('user_id')->map(fn ($id) => new PrivateChannel('chat.user.'.$id))->all()];
    }

    public function broadcastAs(): string { return 'MessageDeleted'; }

    public function broadcastWith(): array { return ['conversation_id' => $this->conversation->id, 'message_id' => $this->messageId]; }
}
