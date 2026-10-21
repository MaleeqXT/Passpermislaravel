<?php

namespace App\Notifications\V1\Monitor\Exam;

use App\Models\MonitorExamReadiness;
use App\Models\Roles\Student\User\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentReadyForExamNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Student $student,
        private readonly MonitorExamReadiness $readiness,
        private readonly string $bookingPath,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = trim(implode(' ', array_filter([
            $this->student->user?->first_name,
            $this->student->user?->last_name,
        ]))) ?: ($this->student->user?->name ?: 'Élève');

        $message = (new MailMessage)
            ->subject('Élève prêt pour l’examen')
            ->greeting('Bonjour,')
            ->line("Élève prêt pour l’examen : {$name}.");

        if ($this->readiness->additional_hours !== null) {
            $message->line('Heures complémentaires demandées : '.$this->readiness->additional_hours.' h.');
        }

        if ($this->readiness->practice_frequency) {
            $message->line('Fréquence d’entraînement : '.$this->readiness->practice_frequency.'.');
        }

        if ($this->readiness->observation) {
            $message->line('Observation : '.$this->readiness->observation);
        }

        return $message
            ->action('Ouvrir la réservation de l’examen', url($this->bookingPath))
            ->line('Cette notification est envoyée par le moniteur du candidat.');
    }
}
