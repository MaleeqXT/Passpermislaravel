<?php

namespace App\Notifications\V1\Student\Training\Cancellation;

use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CancellationRefusedStudentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $user;
    public $reservation;

    /**
     * Create a new notification instance.
     *
     */
    public function __construct(User $user, Reservation $reservation)
    {
        $this->user = $user;
        $this->reservation = $reservation;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable)
    {
        $monitorUser = $this->reservation->monitor->user;
        
        return (new MailMessage)
            ->subject("Demande d'annulation refusée")
            ->greeting('Cher élève, ' . $this->user->name . ',')
            ->line("Nous vous informons que votre demande d'annulation pour la leçon de conduite suivante a été refusée :")
            ->line("Détails de la leçon :")
            ->line('- Moniteur : ' . substr($monitorUser->last_name, 0, 1) . '. ' . $monitorUser->first_name)
            ->line('- Date : ' . Carbon::parse($this->reservation->date)->format('d/m/Y'))
            ->line('- Heure : de ' . Carbon::parse($this->reservation->start_at)->format('H:i') . ' à ' . Carbon::parse($this->reservation->end_at)->format('H:i'))
            ->line("La leçon est maintenue comme prévue. Veuillez vous présenter à l'heure convenue.")
            ->line("Si vous avez des questions concernant cette décision ou si vous rencontrez un problème, n'hésitez pas à nous contacter.")
            ->line("Merci de votre compréhension.")
            ->salutation('Bien cordialement');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
} 