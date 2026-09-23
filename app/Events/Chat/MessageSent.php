<?php

namespace App\Events\Chat;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class MessageSent implements ShouldBroadcastNow
{
    public function __construct(public Message $message, private Conversation $conversation) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('conversation.'.$this->conversation->id),
            ...$this->conversation->participants()->pluck('user_id')->map(fn ($id) => new PrivateChannel('chat.user.'.$id))->all()];
    }

    public function broadcastAs(): string
    {
        return 'MessageSent';
    }

    public function broadcastWith(): array
    {
        return ['message' => $this->message->toArray()];
    }
}
