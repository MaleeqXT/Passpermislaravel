<?php

namespace App\Notifications\V1\Student\Welcome;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeStudentNotification extends Notification implements ShouldQueue
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
            ->subject("Votre compte EasyMonitor est prêt !")
            ->greeting('Bienvenue chez EasyMonitor !')
            ->line("Bonjour " . $this->user->name . ",")
            ->line("Nous avons le plaisir de vous informer que votre compte a été créé avec succès.")
            ->line("Afin de commencer à utiliser nos services, vous pouvez vous connecter à votre espace personnel en suivant le lien ci-dessous.")
            ->action('Accédez à votre espace', url(route('login')))
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
