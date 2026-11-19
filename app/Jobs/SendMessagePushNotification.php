<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\PushNotificationDelivery;
use App\Services\Push\FcmPushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendMessagePushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 180, 600];

    public function __construct(public int $messageId) {}

    public function handle(FcmPushService $push): void
    {
        if (!$push->configured()) return;

        $message = Message::query()->with(['conversation.participants.user.pushNotificationDevices'])->find($this->messageId);
        if (!$message) return;

        foreach ($message->conversation->participants as $participant) {
            $recipient = $participant->user;
            if (!$recipient || (int) $recipient->id === (int) $message->sender_id) continue;

            $route = $recipient->hasRole('student')
                ? '/student-messages?conversation='.rawurlencode((string) $message->conversation_id)
                : '/monitor-dashboard?conversation='.rawurlencode((string) $message->conversation_id);
            $payload = [
                'type' => 'chat.message',
                'conversation_id' => (string) $message->conversation_id,
                'message_id' => (string) $message->id,
                'url' => rtrim((string) config('app.url'), '/').$route,
            ];

            foreach ($recipient->pushNotificationDevices->where('enabled', true) as $device) {
                $delivery = PushNotificationDelivery::firstOrCreate([
                    'message_id' => $message->id,
                    'push_notification_device_id' => $device->id,
                ], ['status' => 'pending']);
                if ($delivery->status === 'sent') continue;

                try {
                    $result = $push->send($device->fcm_token, $payload);
                    if ($result['invalid']) {
                        $device->delete();
                        continue;
                    }
                    $now = now();
                    $delivery->update(['status' => 'sent', 'error_code' => null, 'attempted_at' => $now, 'sent_at' => $now]);
                    $device->update(['last_used_at' => $now]);
                } catch (\Throwable $exception) {
                    // No message body, token, or Firebase credential is logged.
                    $delivery->update(['status' => 'pending', 'error_code' => 'DELIVERY_FAILED', 'attempted_at' => now()]);
                    Log::warning('FCM message push delivery failed.', ['message_id' => $message->id, 'device_id' => $device->id, 'exception' => $exception::class]);
                    throw $exception;
                }
            }
        }
    }
}
