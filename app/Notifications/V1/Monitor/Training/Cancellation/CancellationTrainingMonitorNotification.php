<?php

namespace App\Notifications\V1\Monitor\Training\Cancellation;

use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CancellationTrainingMonitorNotification extends Notification implements ShouldQueue
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

        // welcome new User
        return (new MailMessage)
            ->subject("Annulation de votre leçon de conduite")
            ->greeting('cher moniteur,' . $this->user?->name . ',')
            ->line("Nous souhaitons vous informer que la leçon de conduite prévue avec " . $this->reservation?->training?->student?->user?->name . " le " . Carbon::parse($this->reservation?->date)->format('d/m/Y') . " à " . Carbon::parse($this->reservation?->start_at)->format('H:i') . " - " . Carbon::parse($this->reservation?->end_at)->format('H:i') . " a été annulée.")
            ->line("Cette annulation a été effectuée via notre plateforme EasyMonitor.")
            ->line("Nous comprenons que cela puisse être un désagrément et nous nous excusons pour la gêne occasionnée.")
            ->line("Si vous souhaitez reprogrammer cette leçon ou si vous avez des questions, nous vous invitons à vous connecter à votre compte sur notre plateforme.")
            ->line("Nous restons à votre disposition pour toute demande supplémentaire.")
            ->line("Merci pour votre compréhension et votre patience.")
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
