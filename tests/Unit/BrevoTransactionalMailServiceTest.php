<?php

namespace Tests\Unit;

use App\Mail\NewChatMessageMail;
use App\Models\Message;
use App\Models\User;
use App\Services\Mail\BrevoTransactionalMailException;
use App\Services\Mail\BrevoTransactionalMailService;
use Brevo\Brevo;
use GuzzleHttp\Psr7\Response;
use Mockery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tests\TestCase;

class BrevoTransactionalMailServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_laravel_can_resolve_the_queued_delivery_service(): void
    {
        $this->assertInstanceOf(BrevoTransactionalMailService::class, app(BrevoTransactionalMailService::class));
    }

    public function test_it_uses_the_brevo_https_transactional_endpoint_with_the_configured_sender(): void
    {
        config([
            'services.brevo.api_key' => 'test-key-only',
            'services.brevo.sender_email' => 'configured-sender@example.test',
            'services.brevo.sender_name' => 'PermiFacile Test',
        ]);

        $transport = new CapturingBrevoTransport(new Response(201, [], '{"messageId":"<test@example.test>"}'));
        $service = new BrevoTransactionalMailService(new Brevo(
            apiKey: 'test-key-only',
            options: ['client' => $transport, 'baseUrl' => 'https://api.brevo.com/v3', 'maxRetries' => 0],
        ));

        $sender = $this->sender('monitor');
        $recipient = new User(['first_name' => 'Student', 'last_name' => 'Recipient', 'email' => 'recipient@example.test']);
        $mail = new NewChatMessageMail(new Message(), $sender, $recipient, 'https://app.example.test/student-messages?conversation=abc');

        $service->send($mail, $recipient);

        $this->assertNotNull($transport->request);
        $this->assertSame('POST', $transport->request->getMethod());
        $this->assertSame('https://api.brevo.com/v3/smtp/email', (string) $transport->request->getUri());
        $payload = json_decode((string) $transport->request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('configured-sender@example.test', $payload['sender']['email']);
        $this->assertSame('PermiFacile Test', $payload['sender']['name']);
        $this->assertSame('recipient@example.test', $payload['to'][0]['email']);
        $this->assertStringContainsString('Nouveau message de votre moniteur', $payload['subject']);
    }

    public function test_sender_verification_rejection_is_exposed_only_as_a_safe_code(): void
    {
        config([
            'services.brevo.api_key' => 'test-key-only',
            'services.brevo.sender_email' => 'configured-sender@example.test',
            'services.brevo.sender_name' => 'PermiFacile Test',
        ]);

        $transport = new CapturingBrevoTransport(new Response(400, [], '{"message":"sender domain is not verified"}'));
        $service = new BrevoTransactionalMailService(new Brevo(
            apiKey: 'test-key-only',
            options: ['client' => $transport, 'baseUrl' => 'https://api.brevo.com/v3', 'maxRetries' => 0],
        ));
        $sender = $this->sender('admin');
        $recipient = new User(['email' => 'recipient@example.test']);

        try {
            $service->send(new NewChatMessageMail(new Message(), $sender, $recipient, 'https://app.example.test/admin/messages?conversation=abc'), $recipient);
            $this->fail('Expected a safe Brevo delivery exception.');
        } catch (BrevoTransactionalMailException $exception) {
            $this->assertSame('BREVO_SENDER_VERIFICATION_REQUIRED', $exception->deliveryErrorCode);
            $this->assertSame('Transactional email delivery could not be completed.', $exception->getMessage());
        }
    }

    public function test_blank_brevo_sender_values_fall_back_to_existing_laravel_mail_sender(): void
    {
        config([
            'services.brevo.api_key' => 'test-key-only',
            'services.brevo.sender_email' => '',
            'services.brevo.sender_name' => '',
            'mail.from.address' => 'existing-mail-from@example.test',
            'mail.from.name' => 'Existing Mail Sender',
        ]);
        $transport = new CapturingBrevoTransport(new Response(201, [], '{"messageId":"<test@example.test>"}'));
        $service = new BrevoTransactionalMailService(new Brevo(
            apiKey: 'test-key-only',
            options: ['client' => $transport, 'baseUrl' => 'https://api.brevo.com/v3', 'maxRetries' => 0],
        ));
        $sender = $this->sender('student');
        $recipient = new User(['email' => 'recipient@example.test']);

        $service->send(new NewChatMessageMail(new Message(), $sender, $recipient, 'https://app.example.test/student-messages?conversation=abc'), $recipient);

        $payload = json_decode((string) $transport->request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('existing-mail-from@example.test', $payload['sender']['email']);
        $this->assertSame('Existing Mail Sender', $payload['sender']['name']);
    }

    private function sender(string $role): User
    {
        $sender = Mockery::mock(User::class);
        $sender->shouldReceive('hasRole')->with('student')->zeroOrMoreTimes()->andReturn($role === 'student');
        $sender->shouldReceive('hasAnyRole')->with(['admin', 'super-admin'])->zeroOrMoreTimes()->andReturn($role === 'admin');
        $sender->shouldReceive('hasRole')->with('monitor')->zeroOrMoreTimes()->andReturn($role === 'monitor');
        $sender->shouldReceive('getAttribute')->with('first_name')->andReturn('Sender');
        $sender->shouldReceive('getAttribute')->with('last_name')->andReturn('Person');
        $sender->shouldReceive('getAttribute')->with('name')->andReturn('Sender Person');

        return $sender;
    }
}

class CapturingBrevoTransport implements ClientInterface
{
    public ?RequestInterface $request = null;

    public function __construct(private readonly ResponseInterface $response) {}

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->request = $request;

        return $this->response;
    }
}
