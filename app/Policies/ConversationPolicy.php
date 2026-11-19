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

        $contactId = $conversation->participants()
            ->where('user_id', '!=', $user->id)->value('user_id');

        if (! $contactId) {
            return false;
        }

        $contacts = app(ChatContacts::class);
        if ($contacts->allows($user, (int) $contactId)) {
            return true;
        }

        // Keep the normal active-contact boundary for new contacts. A monitor
        // can nevertheless send a follow-up in an existing private thread with
        // a student they are linked to by a real reservation.
        return $contacts->allowsHistoricalStudentConversation($user, (int) $contactId);
    }
}
