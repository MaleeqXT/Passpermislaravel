<?php

namespace App\Notifications\V1\CPF;

use App\Models\CPFDocumentInfo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FinDocumentCpfNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $CPFDocumentInfo;


    /**
     * Create a new notification instance.
     *
     */
    public function __construct(CPFDocumentInfo $CPFDocumentInfo)
    {
        $this->CPFDocumentInfo = $CPFDocumentInfo;
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
            ->subject("Attestation de fin de formation CPF – PASSPERMISFACILE ")
            ->greeting('Bonjour,' . $this->CPFDocumentInfo->test_pro['name'] . ',')
            ->line('Veuillez trouver ci-joint votre attestation de fin de formation à la conduite de véhicules de la catégorie B, réalisée dans le cadre de votre financement via le Compte Personnel de Formation (CPF).')
            ->line('Ce document atteste de votre participation à la formation dispensée par notre auto-école PASSPERMISFACILE.')
            ->line("N’hésitez pas à revenir vers nous si vous avez besoin d’informations complémentaires ou d’un autre document.")
            ->line('En vous remerciant de votre confiance,')
            ->line("Bien cordialement.")
            ->action('Télécharger l’attestation', route('forms-cpf.attestation-fin-formation.pdf', $this->CPFDocumentInfo->id))
            ->salutation('ETIENNE Florian Représentant légal – PASSPERMISFACILE');
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
