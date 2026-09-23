<?php

namespace App\Notifications\V1\System\User;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomResetPasswordNotification extends Notification
{
    use Queueable;

    public string $token;
    public string $email;

    /**
     * Create a new notification instance.
     */
    public function __construct($token, $email)
    {
        $this->token = $token;
        $this->email = $email;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Demande de réinitialisation de mot de passe')
            ->greeting('Bonjour,')
            ->line("Nous avons reçu une demande pour réinitialiser le mot de passe de votre compte.")
            ->line("Si vous êtes à l'origine de cette demande, vous pouvez réinitialiser votre mot de passe en cliquant sur le lien ci-dessous.")
            ->action('Réinitialiser mon mot de passe', url(route('password.reset', ['token' => $this->token, 'email' => $this->email])))
            ->line("Attention, ce lien expirera dans 60 minutes.")
            ->line("Si vous n'avez pas demandé cette réinitialisation, ignorez simplement cet email. Votre mot de passe restera inchangé.")
            ->line("Si vous avez des questions ou avez besoin d'aide, contactez notre support.")
            ->salutation('Bien cordialement');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
