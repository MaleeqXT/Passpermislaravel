<?php

namespace App\Notifications\V1\Student\Welcome;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Notifications\V1\Student\Welcome\Concerns\BrandsStudentMail;

class RegistrationConfirmedNotification extends Notification
{
    use Queueable, BrandsStudentMail;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return $this->withPermiFacileBranding((new MailMessage)
            ->subject('Votre inscription est confirmée')
            ->view('emails.student-status', [
                'heading' => 'Bonjour ' . ($notifiable->first_name ?: $notifiable->name) . ',',
                'lines' => [
                    'Tous les documents obligatoires de votre dossier ont été validés.',
                    'Votre inscription est maintenant confirmée.',
                ],
            ]));
    }
}
