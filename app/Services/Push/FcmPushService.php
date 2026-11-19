<?php

namespace App\Services\Push;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FcmPushService
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    public function configured(): bool
    {
        return (bool) config('services.firebase.service_account_json');
    }

    /** @return array{invalid: bool} */
    public function send(string $token, array $data): array
    {
        $credentialsPath = (string) config('services.firebase.service_account_json');
        if (!$credentialsPath || !is_file($credentialsPath) || !is_readable($credentialsPath)) {
            throw new RuntimeException('Firebase service-account configuration is unavailable.');
        }
        $credentialsJson = json_decode((string) file_get_contents($credentialsPath), true, 512, JSON_THROW_ON_ERROR);
        $projectId = (string) (config('services.firebase.project_id') ?: ($credentialsJson['project_id'] ?? ''));
        if (!$projectId) throw new RuntimeException('Firebase project ID is unavailable.');

        $credentials = new ServiceAccountCredentials([self::SCOPE], $credentialsJson);
        $accessToken = $credentials->fetchAuthToken()['access_token'] ?? null;
        if (!$accessToken) throw new RuntimeException('Firebase access-token creation failed.');

        $response = Http::acceptJson()->withToken($accessToken)->timeout(12)->post(
            "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
            ['message' => [
                'token' => $token,
                'notification' => ['title' => 'PassPermisFacile – Nouveau message', 'body' => 'Vous avez reçu un nouveau message.'],
                'data' => array_map('strval', $data),
                'webpush' => ['fcm_options' => ['link' => $data['url'] ?? config('app.url')]],
            ]]
        );

        if ($response->successful()) return ['invalid' => false];
        $status = (string) data_get($response->json(), 'error.status', '');
        if (in_array($status, ['UNREGISTERED', 'INVALID_ARGUMENT'], true)) return ['invalid' => true];
        throw new RuntimeException('Firebase request failed with HTTP '.$response->status().'.');
    }
}
