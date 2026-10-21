<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;
use App\Services\Chat\ChatContacts;

class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        return (int) $user->status === 1 && $user->deleted_at === null
            && $user->hasAnyRole(['student', 'monitor', 'admin', 'super-admin'])
            && $conversation->hasParticipant($user);
    }

    public function send(User $user, Conversation $conversation): bool
    {
        if ($conversation->type !== 'private' || ! $this->view($user, $conversation)) {
            return false;
        }

        // The historical thread stays readable by its participants after the
        // final lesson. Sending remains subject to the existing active-contact
        // rule, so an expired relationship cannot be reopened through chat.
        $contactId = $conversation->participants()
            ->where('user_id', '!=', $user->id)->value('user_id');

        return $contactId && app(ChatContacts::class)->allows($user, (int) $contactId);
    }
}
