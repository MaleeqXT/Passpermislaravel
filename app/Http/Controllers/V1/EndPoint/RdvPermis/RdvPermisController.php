<?php

namespace App\Http\Controllers\V1\EndPoint\RdvPermis;

use App\Exceptions\RdvPermisApiException;
use App\Exceptions\RdvPermisOAuthException;
use App\Http\Controllers\Controller;
use App\Services\RdvPermis\AutoEcoleService;
use App\Services\RdvPermis\OAuthService;
use App\Services\RdvPermis\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class RdvPermisController extends Controller
{
    public function status(Request $request, TokenService $tokens): JsonResponse
    {
        $token = $tokens->forUser($request->user());

        return response()->json(['data' => [
            'environment' => config('rdvpermis.environment'),
            'configured' => $this->missingConfiguration() === [],
            'missing_configuration' => $this->missingConfiguration(),
            'status' => $token?->status ?? 'not_connected',
            'connected' => $token?->status === 'connected',
            'expires_at' => $token?->access_token_expires_at?->toIso8601String(),
            'last_error' => in_array($token?->status, ['reconnect_required', 'needs_reauthentication'], true)
                ? $token->last_error
                : null,
        ]]);
    }

    public function connect(Request $request, OAuthService $oauth): JsonResponse
    {
        if ($this->missingConfiguration() !== []) {
            return response()->json([
                'message' => 'La configuration RdvPermis est incomplète.',
                'missing_configuration' => $this->missingConfiguration(),
            ], 503);
        }

        try {
            return response()->json(['data' => ['authorization_url' => $oauth->authorizationUrl($request->user())]]);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }
    }

    public function callback(Request $request, OAuthService $oauth): RedirectResponse|JsonResponse
    {
        if ($request->filled('error')) {
            Log::warning('RdvPermis OAuth authorization callback rejected', [
                'stage' => 'authorization_callback',
                'provider_error' => $this->safeProviderError($request),
            ]);

            try {
                $oauth->consumeState($request->string('state')->toString());
            } catch (Throwable $exception) {
                Log::warning('RdvPermis OAuth rejected callback state could not be consumed', [
                    'stage' => $exception instanceof RdvPermisOAuthException ? $exception->stage : 'state_validation',
                    'exception' => $exception::class,
                ]);
            }

            return $this->redirectToFrontend('error');
        }

        try {
            $oauth->exchangeAuthorizationCode(
                $request->string('code')->toString(),
                $request->string('state')->toString(),
            );

            return $this->redirectToFrontend('connected');
        } catch (RdvPermisOAuthException $exception) {
            Log::warning('RdvPermis OAuth callback failed', [
                'stage' => $exception->stage,
                'exception' => $exception::class,
            ]);

            return $this->redirectToFrontend('error');
        } catch (Throwable $exception) {
            Log::error('RdvPermis OAuth callback failed unexpectedly', [
                'stage' => 'authorization_callback',
                'exception' => $exception::class,
            ]);

            return $this->redirectToFrontend('error');
        }
    }

    public function currentSchool(Request $request, AutoEcoleService $autoEcole): JsonResponse
    {
        try {
            $response = $autoEcole->currentSchool($request->user());

            return response()->json($response->json(), $response->status());
        } catch (RdvPermisApiException $exception) {
            return response()->json(['message' => $exception->userMessage], $exception->responseStatus);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 401);
        }
    }

    public function employees(Request $request, AutoEcoleService $autoEcole): JsonResponse
    {
        try {
            $response = $autoEcole->employees($request->user());

            return response()->json($response->json(), $response->status());
        } catch (RdvPermisApiException $exception) {
            return response()->json(['message' => $exception->userMessage], $exception->responseStatus);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 401);
        }
    }

    private function frontendCallback(string $result): string
    {
        $url = config('rdvpermis.frontend_callback_url');
        if (! filled($url)) {
            throw new RdvPermisOAuthException('frontend_redirect', 'RDVPERMIS_FRONTEND_CALLBACK_URL is not configured.');
        }

        return $url.(str_contains($url, '?') ? '&' : '?').'rdvpermis='.$result;
    }

    private function redirectToFrontend(string $result): RedirectResponse|JsonResponse
    {
        try {
            return redirect()->away($this->frontendCallback($result));
        } catch (RdvPermisOAuthException $exception) {
            Log::error('RdvPermis OAuth frontend redirect failed', [
                'stage' => $exception->stage,
                'exception' => $exception::class,
            ]);

            return response()->json([
                'message' => 'La redirection de fin de connexion RdvPermis est indisponible.',
                'stage' => $exception->stage,
            ], 503);
        }
    }

    private function safeProviderError(Request $request): ?string
    {
        $error = $request->input('error_description') ?? $request->input('error');

        if (! is_string($error) || $error === '') {
            return null;
        }

        $sanitized = preg_replace('/[\r\n]+/', ' ', $error) ?? '';
        $sanitized = preg_replace(
            '/\b(access_token|refresh_token|client_secret|code)\s*[:=]\s*[^\s&]+/i',
            '$1=[redacted]',
            $sanitized,
        ) ?? '';

        return mb_substr($sanitized, 0, 300);
    }

    private function missingConfiguration(): array
    {
        $keys = [
            'client_id' => 'RDVPERMIS_CLIENT_ID',
            'client_secret' => 'RDVPERMIS_CLIENT_SECRET',
            'authorization_url' => 'RDVPERMIS_AUTH_URL',
            'token_url' => 'RDVPERMIS_TOKEN_URL',
            'api_url' => 'RDVPERMIS_API_URL',
            'current_school_path' => 'RDVPERMIS_CURRENT_SCHOOL_PATH',
            'redirect_uri' => 'RDVPERMIS_REDIRECT_URI',
            'frontend_callback_url' => 'RDVPERMIS_FRONTEND_CALLBACK_URL',
        ];

        return collect($keys)
            ->reject(fn (string $environmentName, string $configKey) => filled(config("rdvpermis.$configKey")))
            ->values()
            ->all();
    }
}
