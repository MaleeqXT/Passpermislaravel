<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;

Broadcast::channel('conversation.{conversationId}', function (User $user, string $conversationId) {
    $conversation = Conversation::find($conversationId);
    return $conversation && Gate::forUser($user)->allows('view', $conversation);
}, ['guards' => ['sanctum']]);

// Personal inbox notifications also cover new conversations and other tabs.
Broadcast::channel('chat.user.{userId}', function (User $user, string $userId) {
    return (string) $user->id === $userId && (int) $user->status === 1 && $user->deleted_at === null
        && $user->hasAnyRole(['student', 'monitor', 'admin', 'super-admin']);
}, ['guards' => ['sanctum']]);
