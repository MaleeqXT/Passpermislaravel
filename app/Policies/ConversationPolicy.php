<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;
use App\Services\Chat\ChatContacts;

class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        if (! ((int) $user->status === 1 && $user->deleted_at === null
            && $user->hasAnyRole(['student', 'monitor', 'admin', 'super-admin'])
            && $conversation->hasParticipant($user))) {
            return false;
        }

        // A private thread stops being accessible once the reservation is no
        // longer upcoming. This also protects conversations created before the
        // reservation-based rule was introduced.
        $contactId = $conversation->participants()
            ->where('user_id', '!=', $user->id)->value('user_id');

        return $contactId && app(ChatContacts::class)->allows($user, (int) $contactId);
    }

    public function send(User $user, Conversation $conversation): bool
    {
        return $conversation->type === 'private' && $this->view($user, $conversation);
    }
}
