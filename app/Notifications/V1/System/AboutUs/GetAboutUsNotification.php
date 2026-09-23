<?php

namespace App\Notifications\V1\System\AboutUs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GetAboutUsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $data;

    /**
     * Create a new notification instance.
     *
     */
    public function __construct($data)
    {
        $this->data = $data;
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
            ->subject("Nouveau message de contact")
            ->greeting('Bonjour,')
            ->line('Un client a envoyé un nouveau message :')
            ->line('- Nom & Prénom: ' . $this->data['nom'] . ' ' . $this->data['prenom'])
            ->line('- Téléphone : ' . $this->data['phone'])
            ->line('- Email : ' . $this->data['email'])
            ->line('- Nature de la demande : ' . $this->data['subject'])
            ->line('- Message : ' . $this->data['message'])
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
