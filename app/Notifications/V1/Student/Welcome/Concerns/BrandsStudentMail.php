<?php

namespace App\Notifications\V1\Student\Welcome\Concerns;

use Illuminate\Notifications\Messages\MailMessage;

trait BrandsStudentMail
{
    private function withPermiFacileBranding(MailMessage $message): MailMessage
    {
        // The view embeds the PNG so rendering and sending use the same image
        // attachment, with a fresh content ID for each message.
        return $message->view([
            'html' => 'emails.student-status',
            'text' => 'emails.student-status-text',
        ], $message->viewData);
    }
}
