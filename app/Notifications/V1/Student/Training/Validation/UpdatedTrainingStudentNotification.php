<?php

namespace App\Notifications\V1\Student\Training\Validation;

use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UpdatedTrainingStudentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $user,
        public Reservation $reservation
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $monitorUser = $this->reservation->monitor->user;

        return (new MailMessage)
            ->subject('Modification de votre heure de conduite')
            ->greeting('cher élève, ' . $this->user->name . ',')
            ->line('Attention, votre heure de conduite a été modifiée.')
            ->line('Voici les informations importantes concernant votre leçon :')
            ->line('- **Date** : ' . Carbon::parse($this->reservation->date)->format('d/m/Y'))
            ->line('- **Heure** : de ' . Carbon::parse($this->reservation->start_at)->format('H:i') . ' à ' . Carbon::parse($this->reservation->end_at)->format('H:i'))
            ->line('- **Moniteur** : ' . $monitorUser->first_name . ' ' . substr($monitorUser->last_name, 0, 1))
            ->line('- **Lieu** : ' . $this->reservation->lieu->zone->name . ', ' . $this->reservation->lieu->name)
            ->line("Nous vous recommandons d'arriver 10 minutes avant le début de votre leçon pour vous assurer que tout se passe bien.")
            ->line("Si vous avez besoin de modifier ou d'annuler cette leçon, jusqu’à 48h maximum avant votre séance, sinon celle ci sera comptabilisée.")
            ->line("Pour toute question ou assistance supplémentaire, notre équipe reste à votre disposition.")
            ->line('Nous vous souhaitons une excellente leçon de conduite.')
            ->salutation('Bien cordialement');
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
