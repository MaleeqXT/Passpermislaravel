<?php

namespace Tests\Unit;

use App\Mail\NewChatMessageMail;
use App\Models\Message;
use App\Models\User;
use Mockery;
use Tests\TestCase;

class NewChatMessageMailTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_mail_has_a_safe_subject_and_conversation_link(): void
    {
        $sender = Mockery::mock(User::class);
        $sender->shouldReceive('hasRole')->with('student')->atLeast()->once()->andReturn(false);
        $sender->shouldReceive('hasAnyRole')->with(['admin', 'super-admin'])->atLeast()->once()->andReturn(false);
        $sender->shouldReceive('hasRole')->with('monitor')->atLeast()->once()->andReturn(true);
        $sender->shouldReceive('getAttribute')->with('first_name')->andReturn('Mohamed');
        $sender->shouldReceive('getAttribute')->with('last_name')->andReturn('Amine');
        $sender->shouldReceive('getAttribute')->with('name')->andReturn('Mohamed Amine');
        $recipient = Mockery::mock(User::class);

        $mail = new NewChatMessageMail(new Message(), $sender, $recipient, 'https://app.example.test/student-messages?conversation=abc');
        $this->assertStringContainsString('Nouveau message de votre moniteur', $mail->build()->subject);
        $rendered = $mail->render();
        $this->assertStringContainsString('Consulter le message', $rendered);
        $this->assertStringContainsString('conversation=abc', $rendered);
        $this->assertStringNotContainsString('Message :', $rendered);
    }
}
