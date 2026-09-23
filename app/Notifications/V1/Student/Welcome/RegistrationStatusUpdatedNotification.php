<?php

namespace App\Notifications\V1\Student\Welcome;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Notifications\V1\Student\Welcome\Concerns\BrandsStudentMail;

class RegistrationStatusUpdatedNotification extends Notification
{
    use Queueable, BrandsStudentMail;

    public function __construct(private readonly string $status)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $label = $this->status === 'rejected' ? 'refusé' : 'en attente';

        return $this->withPermiFacileBranding((new MailMessage)
            ->subject('Mise à jour de votre inscription')
            ->view('emails.student-status', [
                'heading' => 'Bonjour ' . ($notifiable->first_name ?: $notifiable->name) . ',',
                'lines' => [
                    "Le statut de votre inscription est maintenant : {$label}.",
                    $this->status === 'rejected'
                        ? 'Veuillez contacter l’auto-école pour connaître les prochaines étapes.'
                        : 'Votre dossier est en cours de vérification. Nous vous informerons dès qu’il sera traité.',
                ],
            ]));
    }
}
