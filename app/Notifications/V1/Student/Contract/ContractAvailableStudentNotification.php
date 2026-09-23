<?php

namespace App\Notifications\V1\Student\Contract;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractAvailableStudentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly User $user)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre contrat de formation est disponible')
            ->greeting('Bonjour ' . $this->user->name . ',')
            ->line("Votre contrat de formation est maintenant disponible dans votre espace élève.")
            ->line("Vous pouvez le consulter dès maintenant depuis votre dashboard étudiant.")
            ->action('Voir mon contrat', url('/student/settings/contrat-de-formation'))
            ->line("Merci de vous connecter à votre dashboard pour le consulter.");
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
