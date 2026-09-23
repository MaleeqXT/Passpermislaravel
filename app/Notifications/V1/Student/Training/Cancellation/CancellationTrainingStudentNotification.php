<?php

namespace App\Notifications\V1\Student\Training\Cancellation;

use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CancellationTrainingStudentNotification extends Notification implements ShouldQueue
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
        $user = $this->reservation->monitor->user;
        // welcome new User
        return (new MailMessage)
            ->subject("Annulation de votre leçon de conduite")
            ->greeting('cher élève, ' . $this->user->name . ',')
            ->line("Nous souhaitons vous informer que votre leçon de conduite prévue avec " . substr($user->last_name, 0, 1) . '. '. $user->first_name .  " a été annulée.")
            ->line("Détails de la leçon annulée :")
            ->line('- Date : ' . Carbon::parse($this->reservation->date)->format('d/m/Y'))
            ->line('- Heure : de ' . Carbon::parse($this->reservation->start_at)->format('H:i') . ' à ' . Carbon::parse($this->reservation->end_at)->format('H:i'))
            ->line("Nous comprenons que cela puisse être un inconvénient et nous nous excusons pour la gêne occasionnée.")
            ->line("Vous pouvez reprogrammer une nouvelle leçon en vous connectant à votre compte.")
            ->line("Si vous avez des questions ou besoin d’assistance, n’hésitez pas à nous contacter.")
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
