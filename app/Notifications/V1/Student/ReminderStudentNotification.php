<?php

namespace App\Notifications\V1\Student;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReminderStudentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $user;

    /**
     * Create a new notification instance.
     *
     */
    public function __construct(User $user)
    {
        $this->user = $user;
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
            ->subject("Planifie tes prochaines heures de conduite 🚗")
            ->greeting('Bienvenue chez EasyMonitor !')
            ->line("Bonjour " . $this->user->name . ",")
            ->line('Suite à ton évaluation réalisée le [Date de l’évaluation], tu as été estimé(e) à [Nombre d’heures estimées] heures de conduite.')
            ->line('Il est temps de planifier tes heures restantes pour finaliser ta formation et te préparer au mieux pour l’examen du permis de conduire 🎯🚦')
            ->line('👉 Connecte-toi dès maintenant à ton espace pour réserver tes prochaines leçons 📅.
Nous sommes là pour t’accompagner jusqu’à la réussite 🚀')
            ->line("Si vous avez besoin d'aide ou de renseignements supplémentaires, notre équipe se tient à votre disposition pour vous accompagner.")
            ->salutation("Bien cordialement,");
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
