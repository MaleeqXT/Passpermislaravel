<?php

namespace App\Notifications\V1\Monitor\Training\Validation;

use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ValidationTrainingMonitorNotification extends Notification implements ShouldQueue
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
    return (new MailMessage)
        ->subject("Confirmation de votre nouvelle réservation de leçon de conduite")
        ->greeting("Bonjour " . $this->user->name . ",")
        ->line("Nous sommes heureux de vous informer qu'une nouvelle réservation de leçon de conduite a été effectuée.")
        ->line("Voici les détails de cette réservation :")
        ->line("- *Date de la leçon* : " . Carbon::parse($this->reservation->date)->format('d/m/Y'))
        ->line("- *Horaire* : de " . Carbon::parse($this->reservation->start_at)->format('H:i') . " à " . Carbon::parse($this->reservation->end_at)->format('H:i'))
        ->line("- *Élève* : " . ($this->reservation->training?->student?->user?->name ?? 'Non défini'))
        ->line("- *Lieu de la leçon* : " . $this->reservation->lieu->zone->name . ", " . $this->reservation->lieu->name)
        ->line("En cas de changement de programme, vous pouvez facilement modifier ou annuler la leçon directement depuis votre espace.")
        ->line("Merci pour votre professionnalisme, et nous vous souhaitons une excellente leçon.")
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
