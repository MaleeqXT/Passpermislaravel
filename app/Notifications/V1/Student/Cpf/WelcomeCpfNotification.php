<?php

namespace App\Notifications\V1\Student\Cpf;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class WelcomeCpfNotification extends Notification implements ShouldQueue
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
            ->subject("Bienvenue sur notre site")
            ->greeting('Bienvenue sur notre auto êcole EasyMonitor')
            ->line("Cher(e) " . $this->user->name . ",")
            ->line("Nous sommes ravis de t'accueillir chez ader-solutions.com !")
            ->line("Nous avons hâte de démarrer cette formation.")
            ->line(new HtmlString("Mais avant cela nous allons te demander de prendre connaissance de nos<a href='#'> conditions generales de vente </a>"))
            ->line("Par ailleurs, voici tes codes d'accès vers ton espace élève :")
            //  url(route('conditions-generales-de-vente'))
            ->line("Votre identifiant est : " . $this->user->email)
            ->action('Se connecter', url(route('login')))
            ->line("Comme indiqué lors de l'entretien téléphonique, il est impératif de se présenter au moins 5 minutes avant l'heure de tes leçons de conduite. ")
            ->line("En cas d'empêchement, et conformément aux C.G.V., il faudra nous prévenir au minimum 48 heures en avance afin de pouvoir remplacer ton heure de conduite.")
            ->line("Nous serons à ta disposition tout au long de ta formation.")
            ->line("Excellente formation")
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
