<?php

namespace App\Notifications\V1\Monitor\Welcome;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Password;

class WelcomeMonitorNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $user;
    public $token;

    /**
     * Create a new notification instance.
     *
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->token = Password::getRepository()->create($this->user);
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
        // markEmailAsVerified()
        $params = [
            'user' => $this->user->id,
            'token' => $this->token,
        ];
        return (new MailMessage)
            ->subject("Votre inscription chez EasyMonitor a été confirmée")
            ->greeting('Bienvenue chez EasyMonitor !')
            ->line("Bonjour " . $this->user->name . ",")
            ->line("Félicitations, vous avez maintenant un compte sur notre plateforme.")
            ->line("Pour commencer, vous devez personnaliser votre profil et définir un mot de passe sécurisé.")
            ->line("Votre identifiant est : " . $this->user->email)
            ->line("Cliquez sur le bouton ci-dessous pour configurer votre mot de passe et finaliser votre inscription.")
            ->action('Configurer mon mot de passe', route('register.monitor.setup-password', $params))
            ->line("Pour toute question, notre équipe est disponible pour vous aider à chaque étape.")
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
