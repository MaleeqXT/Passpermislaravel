<?php

namespace Tests\Feature;

use App\Notifications\V1\Student\Welcome\DocumentStatusUpdatedNotification;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class StudentStatusEmailLogoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'mail.default' => 'array',
            'mail.from.address' => 'sender@example.invalid',
            'mail.from.name' => 'PermiFacile',
        ]);
    }

    public function test_status_email_uses_the_passpermisfacile_text_brand_without_an_attachment(): void
    {
        Notification::sendNow($this->recipient(), new DocumentStatusUpdatedNotification('Identité', 'approved'));

        /** @var Email $email */
        $email = Mail::mailer('array')->getSymfonyTransport()->messages()->last()->getOriginalMessage();
        $html = $email->getHtmlBody();

        $this->assertStringContainsString('Passpermisfacile', $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertCount(0, $email->getAttachments());
    }

    private function recipient(): object
    {
        return new class {
            use Notifiable;

            public string $first_name = 'Logo';
            public string $name = 'Logo Test';
            public string $email = 'logo-test@example.invalid';
        };
    }
}
