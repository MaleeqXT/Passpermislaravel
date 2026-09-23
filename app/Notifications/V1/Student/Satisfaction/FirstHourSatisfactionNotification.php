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
        return (new MailMessage)
            ->subject('Votre avis sur votre formation PassPermisFacile')
            ->greeting('Bonjour,')
            ->line('Votre avis nous aide à améliorer votre expérience.')
            ->action('Donner mon avis', rtrim(config('satisfaction.frontend_url'), '/') . '/satisfaction')
            ->line('Merci de prendre quelques minutes pour répondre à notre enquête de satisfaction.')
            ->salutation('L’équipe PassPermisFacile');
    }
}
