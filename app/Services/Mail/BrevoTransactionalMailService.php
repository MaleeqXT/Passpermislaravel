<?php

namespace App\Services\Mail;

use App\Mail\NewChatMessageMail;
use App\Models\User;
use Brevo\Brevo;
use Brevo\BrevoInterface;
use Brevo\Exceptions\BrevoApiException;
use Brevo\TransactionalEmails\Requests\SendTransacEmailRequest;
use Brevo\TransactionalEmails\Types\SendTransacEmailRequestSender;
use Brevo\TransactionalEmails\Types\SendTransacEmailRequestToItem;
use GuzzleHttp\Client as GuzzleClient;
use Throwable;

class BrevoTransactionalMailService
{
    private ?BrevoInterface $client;

    public function __construct(?BrevoInterface $client = null)
    {
        $this->client = $client;
    }

    public function send(NewChatMessageMail $mail, User $recipient): void
    {
        $senderEmail = trim((string) config('services.brevo.sender_email'));
        $senderName = trim((string) config('services.brevo.sender_name'));

        // env() only applies its second argument when the first value is
        // absent. A deliberately blank BREVO_SENDER_* must also preserve the
        // existing Laravel sender configuration.
        $senderEmail = $senderEmail !== '' ? $senderEmail : trim((string) config('mail.from.address'));
        $senderName = $senderName !== '' ? $senderName : trim((string) config('mail.from.name'));

        if (! filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) {
            throw new BrevoTransactionalMailException('BREVO_SENDER_NOT_CONFIGURED');
        }

        try {
            // Rendering keeps the existing Laravel email template and never
            // includes the chat message body in logs or exception messages.
            $html = $mail->render();

            $this->client()->getTransactionalEmails()->sendTransacEmail(
                new SendTransacEmailRequest([
                    'sender' => new SendTransacEmailRequestSender([
                        'email' => $senderEmail,
                        'name' => $senderName ?: null,
                    ]),
                    'to' => [new SendTransacEmailRequestToItem([
                        'email' => $recipient->email,
                        'name' => $this->recipientName($recipient),
                    ])],
                    'subject' => (string) $mail->subject,
                    'htmlContent' => $html,
                    'tags' => ['chat-message'],
                ]),
                ['baseUrl' => 'https://api.brevo.com/v3', 'maxRetries' => 0],
            );
        } catch (BrevoApiException $exception) {
            throw new BrevoTransactionalMailException($this->safeApiErrorCode($exception));
        } catch (BrevoTransactionalMailException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw new BrevoTransactionalMailException('BREVO_DELIVERY_REQUEST_FAILED');
        }
    }

    private function client(): BrevoInterface
    {
        if ($this->client instanceof BrevoInterface) {
            return $this->client;
        }

        $apiKey = trim((string) config('services.brevo.api_key'));
        if ($apiKey === '') {
            throw new BrevoTransactionalMailException('BREVO_API_KEY_NOT_CONFIGURED');
        }

        return $this->client = new Brevo(
            apiKey: $apiKey,
            options: [
                'baseUrl' => 'https://api.brevo.com/v3',
                'client' => new GuzzleClient(['timeout' => 20]),
                // The Laravel job owns the three configured retry attempts.
                'maxRetries' => 0,
            ],
        );
    }

    private function recipientName(User $recipient): ?string
    {
        $name = trim((string) $recipient->first_name.' '.(string) $recipient->last_name);

        return $name !== '' ? $name : null;
    }

    private function safeApiErrorCode(BrevoApiException $exception): string
    {
        // Brevo does not expose a stable machine-readable validation code for
        // all sender checks. Inspect it only to classify the state, never log
        // or expose the provider response body.
        $body = strtolower((string) $exception->getBody());

        if (str_contains($body, 'sender') || str_contains($body, 'domain')) {
            return 'BREVO_SENDER_VERIFICATION_REQUIRED';
        }

        return 'BREVO_API_REJECTED';
    }
}
