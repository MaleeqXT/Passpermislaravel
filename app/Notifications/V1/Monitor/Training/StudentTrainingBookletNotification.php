<?php

namespace App\Notifications\V1\Monitor\Training;

use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Student\User\Student;
use App\Models\User;
use App\Services\Monitor\Training\StudentTrainingBookletPdfService;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Symfony\Component\Mime\Email;

class StudentTrainingBookletNotification extends Notification
{
    private const LOGO_CONTENT_ID = 'passpermis-booklet-logo@passpermisfacile';
    /** @param Collection<int, Reservation> $lessons */
    public function __construct(
        public readonly User $monitor,
        public readonly Student $student,
        public readonly Collection $lessons,
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $studentName = $this->student->user?->name ?? 'Élève';
        $booklet = app(StudentTrainingBookletPdfService::class);

        return (new MailMessage)
            ->subject('Livret de formation — '.$studentName)
            ->view([
                'html' => 'emails.monitor-training-booklet',
                'text' => 'emails.monitor-training-booklet-text',
            ], [
                'monitorName' => $this->monitor->name,
                'studentName' => $studentName,
                'generatedAt' => now()->format('d/m/Y à H:i'),
            ])
            ->attachData(
                $booklet->output($this->student, $this->lessons),
                $booklet->filename($this->student),
                ['mime' => 'application/pdf'],
            )
            ->withSymfonyMessage(function (Email $email): void {
                $email->embedFromPath(
                    public_path('assets/logo-permis-facile-email.png'),
                    'passpermis-booklet-logo.png',
                    'image/png',
                );

                $attachments = $email->getAttachments();
                $attachments[array_key_last($attachments)]->setContentId(self::LOGO_CONTENT_ID);
            });
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}