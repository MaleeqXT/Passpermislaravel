<?php

namespace App\Notifications\V1\CPF;

use App\Models\CPFDocumentInfo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EnvoiDocumentCpfNotification extends Notification implements ShouldQueue
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
            ->subject("Démarches administratives CPF pour votre formation au permis de conduire")
            ->greeting('Cher(e) ' . $this->CPFDocumentInfo->test_pro['name'] . ',')
            ->line("Nous vous remercions d’avoir choisi notre auto-école pour votre formation au permis de conduire via le Compte Personnel de Formation (CPF).")
            ->line("Afin de finaliser votre inscription et de garantir la prise en charge de votre formation, nous vous invitons à compléter les démarches administratives suivantes :")
            ->line("1. **Test de positionnement** : ce test estime théoriquement votre niveau de conduite afin de définir le nombre d’heures appropriées à votre formation.")
            ->line("→ Accéder au test : " . route('forms-cpf.test.positionnement.pdf', $this->CPFDocumentInfo->id))
            ->line("2. **Signature du contrat de formation** : ce contrat formalise votre engagement dans la formation et précise les modalités pédagogiques et financières.")
            ->line("→ Télécharger le contrat : " . route('forms-cpf.contact.formation.pdf', $this->CPFDocumentInfo->id))
            ->line("3. **Attestation sur l’honneur** : ce document certifie que le permis s’inscrit dans votre projet professionnel et que vous n’êtes pas sous le coup d’une interdiction de le passer.")
            ->line("→ Télécharger l’attestation : " . route('forms-cpf.attestation.honneur.pdf', $this->CPFDocumentInfo->id))
            ->line("🔗 Vous pouvez également accéder à vos réservations : " . route('forms-cpf.reservations.pdf', $this->CPFDocumentInfo->id))
            ->line("⚠️ Merci de compléter ces démarches dans les plus brefs délais afin de débuter votre formation dans les meilleures conditions.")
            ->line("📞 Pour toute question, notre secrétariat est joignable 6j/7 de 9h à 18h au 09.70.70.16.16.")
            ->salutation("Bien cordialement,\n\nEtienne Florian\nGérant et responsable pédagogique\nAuto-école PASSPERMISFACILE\n139 boulevard Déodat de Séverac, 31400 Toulouse");

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
