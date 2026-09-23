<?php

namespace App\Notifications\V1\Student\Welcome;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Notifications\V1\Student\Welcome\Concerns\BrandsStudentMail;

class DocumentStatusUpdatedNotification extends Notification
{
    use Queueable, BrandsStudentMail;

    public function __construct(
        private readonly string $documentType,
        private readonly string $status,
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $label = match ($this->status) {
            'approved' => 'approuvé',
            'rejected' => 'refusé',
            default => 'en attente',
        };

        return $this->withPermiFacileBranding((new MailMessage)
            ->subject('Mise à jour de votre document')
            ->view('emails.student-status', [
                'heading' => 'Bonjour ' . ($notifiable->first_name ?: $notifiable->name) . ',',
                'lines' => [
                    "Le statut de votre document « {$this->documentType} » est maintenant : {$label}.",
                    $this->status === 'rejected'
                        ? 'Veuillez contacter l’auto-école ou transmettre un nouveau document si nécessaire.'
                        : 'Vous pouvez consulter votre dossier dans votre espace élève.',
                ],
            ]));
    }
}
