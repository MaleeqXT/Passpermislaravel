<?php

namespace App\Notifications\V1\Student\Cpf;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class AttestationQuestionnaireSatisfactionCpfNotification extends Notification implements ShouldQueue
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
        $message_1 = 'Ton attestation de fin de formation';
        $message_2 = 'Un questionnaire de satisfaction';
        $message_3 = 'Ton évaluation de sortie de formation';

        return (new MailMessage)
            ->subject("Fin de formation : Attestation et Évaluation")
            ->greeting('Bonjour ' . $this->user->name . ',')
            ->line('Ta formation est maintenant terminée. Félicitations !')
            ->line('Nous aimerions connaître ton avis sur notre prestation pour continuer à nous améliorer.')
            ->line('Dans ton espace élève, tu trouveras :')
            ->line(new HtmlString('<ul><li>' . $message_1 . '</li><li>' . $message_2 . '</li><li>' . $message_3 . '</li></ul>'))
            ->line(new HtmlString('Voici le lien pour accéder à ton espace élève : <a href="https://eleve.ader-solutions.fr/login">Mon espace élève</a>'))
            ->line('Nous t’invitons à signer et télécharger ton attestation et à répondre au questionnaire en prenant 5 minutes 😊.')
            ->line('Nous te contacterons dans 3 mois puis dans 6 mois pour faire le point sur ton projet. N’hésite pas à nous contacter avant si tu as besoin d’informations.')
            ->line("Merci d'avoir choisi ader-solutions.com !")
            ->line("Nous te remercions par avance pour ta collaboration.")
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
