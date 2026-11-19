<?php

namespace App\Mail;

use App\Models\Message;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewChatMessageMail extends Mailable
{
    use SerializesModels;

    public function __construct(public Message $message, public User $sender, public User $recipient, public string $conversationUrl) {}

    public function build(): self
    {
        return $this->subject('PassPermisFacile – Nouveau message de '.self::roleLabel($this->sender))
            ->view('emails.chat.new-message')
            ->with(['senderName' => $this->senderName()]);
    }

    public static function roleLabel(User $user): string
    {
        if ($user->hasRole('student')) return 'votre élève';
        if ($user->hasAnyRole(['admin', 'super-admin'])) return 'l’administration';
        if ($user->hasRole('monitor')) return 'votre moniteur';
        return 'votre interlocuteur';
    }

    public function senderName(): string
    {
        $name = trim((string) $this->sender->getAttribute('first_name').' '.(string) $this->sender->getAttribute('last_name'));
        return $name ?: (string) $this->sender->getAttribute('name');
    }
}
