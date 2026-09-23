<?php

namespace App\Events\Chat;

use App\Models\Conversation;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class MessagesRead implements ShouldBroadcastNow
{
    public function __construct(private Conversation $conversation, private int $userId, private int $throughId, private string $readAt) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('conversation.'.$this->conversation->id),
            ...$this->conversation->participants()->pluck('user_id')->map(fn ($id) => new PrivateChannel('chat.user.'.$id))->all()];
    }

    public function broadcastAs(): string
    {
        return 'MessagesRead';
    }

    public function broadcastWith(): array
    {
        return ['conversation_id' => $this->conversation->id, 'user_id' => $this->userId,
            'through_id' => $this->throughId, 'read_at' => $this->readAt];
    }
}
