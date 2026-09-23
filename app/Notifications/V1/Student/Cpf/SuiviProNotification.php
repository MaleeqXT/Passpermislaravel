<?php

namespace App\Notifications\V1\Student\Cpf;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SuiviProNotification extends Notification implements ShouldQueue
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
        return (new MailMessage)
            ->subject("Suivi de formation")
            ->greeting('Bonjour ' . $this->user->name . ',')
            ->line("J'espère que vous allez bien.")
            ->line('Cela fait maintenant 3 ou 6 mois que vous avez terminé votre formation. Nous aimerions savoir si celle-ci a contribué à sécuriser ou concrétiser votre parcours professionnel.')
            ->line("Pour cela, nous vous invitons à remplir le questionnaire « Suivi de formation » disponible sur votre espace élève.")
            ->action('Accéder à mon espace élève', 'https://eleve.ader-solutions.com/login')
            ->line("Nous vous remercions par avance pour le temps que vous nous consacrerez et restons à votre disposition pour tout complément d'information.")
            ->line('Nous vous souhaitons une excellente journée.')
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
