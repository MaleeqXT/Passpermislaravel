<?php

namespace App\Notifications\V1\Student\Satisfaction;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FirstHourSatisfactionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $stage = 'during_training') {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $message = match ($this->stage) {
            'before_training' => 'Votre inscription est confirmée. Donnez-nous votre avis sur votre accueil chez PassPermisFacile.',
            'after_training' => 'Votre date d’examen est confirmée. Partagez votre expérience avec PassPermisFacile.',
            default => 'Votre formation avance. Donnez-nous votre avis sur votre expérience chez PassPermisFacile.',
        };

        return (new MailMessage)
            ->subject('Votre avis sur votre formation PassPermisFacile')
            ->greeting('Bonjour,')
            ->line($message)
            ->action('Donner mon avis', rtrim(config('satisfaction.frontend_url'), '/') . '/satisfaction')
            ->line('Merci de prendre quelques minutes pour répondre à notre enquête de satisfaction.')
            ->salutation('L’équipe PassPermisFacile');
    }
}
