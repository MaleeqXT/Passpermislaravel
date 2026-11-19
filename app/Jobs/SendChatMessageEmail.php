<?php

namespace App\Jobs;

use App\Mail\NewChatMessageMail;
use App\Models\ChatMessageEmailDelivery;
use App\Models\Message;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendChatMessageEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 180, 600];

    public function __construct(public int $messageId) {}

    public function handle(): void
    {
        $message = Message::query()->with(['sender', 'conversation.participants.user'])->find($this->messageId);
        if (!$message || !$message->sender) return;

        foreach ($this->eligibleRecipients($message) as $recipient) {
            $delivery = $this->deliveryFor($message, $recipient);
            if ($delivery->status === 'sent') continue;

            if (!filter_var($recipient->email, FILTER_VALIDATE_EMAIL)) {
                $delivery->update(['status' => 'skipped', 'error_code' => 'MISSING_RECIPIENT_EMAIL', 'attempted_at' => now()]);
                continue;
            }

            try {
                Mail::to($recipient)->send(new NewChatMessageMail(
                    $message,
                    $message->sender,
                    $recipient,
                    $this->conversationUrl($recipient, (string) $message->conversation_id),
                ));
                $delivery->update(['status' => 'sent', 'error_code' => null, 'attempted_at' => now(), 'sent_at' => now()]);
            } catch (\Throwable $exception) {
                // Do not log recipients, message text, URLs, API keys, or provider payloads.
                $delivery->update([
                    'status' => 'pending',
                    'error_code' => 'EMAIL_DELIVERY_RETRYING',
                    'attempted_at' => now(),
                ]);
                Log::warning('Chat message email delivery failed.', ['message_id' => $message->id, 'recipient_user_id' => $recipient->id, 'exception' => $exception::class]);
                throw $exception;
            }
        }
    }

    /**
     * Laravel calls this only after the configured queue attempts are exhausted.
     * Sent and intentionally skipped recipients are never changed.
     */
    public function failed(?Throwable $exception): void
    {
        $message = Message::query()->with(['sender', 'conversation.participants.user'])->find($this->messageId);
        if (!$message || !$message->sender) return;

        foreach ($this->eligibleRecipients($message) as $recipient) {
            $delivery = $this->deliveryFor($message, $recipient);

            if ($delivery->status !== 'pending') continue;

            if (!filter_var($recipient->email, FILTER_VALIDATE_EMAIL)) {
                $delivery->update(['status' => 'skipped', 'error_code' => 'MISSING_RECIPIENT_EMAIL', 'attempted_at' => now()]);
                continue;
            }

            $delivery->update([
                'status' => 'EMAIL_DELIVERY_FAILED',
                'error_code' => 'EMAIL_DELIVERY_FAILED',
                'attempted_at' => now(),
            ]);
        }

        Log::warning('Chat message email delivery exhausted all retries.', ['message_id' => $this->messageId, 'exception' => $exception ? $exception::class : null]);
    }

    private function deliveryFor(Message $message, User $recipient): ChatMessageEmailDelivery
    {
        return ChatMessageEmailDelivery::firstOrCreate([
            'message_id' => $message->id,
            'recipient_user_id' => $recipient->id,
        ], ['status' => 'pending']);
    }

    /** @return \Illuminate\Support\Collection<int, User> */
    private function eligibleRecipients(Message $message): \Illuminate\Support\Collection
    {
        return $message->conversation->participants
            ->map(fn ($participant) => $participant->user)
            ->filter(fn ($recipient) => $recipient instanceof User && (int) $recipient->id !== (int) $message->sender_id)
            ->unique('id')
            ->values();
    }

    private function conversationUrl($recipient, string $conversationId): string
    {
        $route = $recipient->hasRole('student')
            ? '/student-messages'
            : ($recipient->hasAnyRole(['admin', 'super-admin']) ? '/admin/messages' : '/monitor-dashboard');
        return rtrim((string) config('app.frontend_url'), '/').$route.'?conversation='.rawurlencode($conversationId);
    }
}
