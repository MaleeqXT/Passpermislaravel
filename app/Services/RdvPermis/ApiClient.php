<?php

namespace App\Services\RdvPermis;

use App\Exceptions\RdvPermisApiException;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiClient
{
    public function __construct(private readonly TokenService $tokens) {}

    public function request(User $user, string $method, string $path, array $options = []): Response
    {
        $baseUrl = rtrim((string) config('rdvpermis.api_url'), '/');
        if ($baseUrl === '') {
            throw new RdvPermisApiException(
                'La configuration RECETTE1 RdvPermis est incomplète : RDVPERMIS_API_URL.',
                503,
            );
        }

        $method = strtoupper($method);

        try {
            $response = Http::acceptJson()
                ->timeout(config('rdvpermis.timeout'))
                ->withToken($this->tokens->accessToken($user))
                // Government API redirects are errors, not a new authentication flow.
                ->send($method, $baseUrl.'/'.ltrim($path, '/'), array_replace($options, [
                    'allow_redirects' => false,
                ]));
        } catch (ConnectionException $exception) {
            Log::warning('RdvPermis API connection failure', [
                'endpoint' => $path,
                'user_id' => $user->getKey(),
                'method' => $method,
                'exception' => $exception::class,
            ]);

            throw new RdvPermisApiException(
                'Le service RdvPermis est temporairement inaccessible. Réessayez plus tard.',
                503,
            );
        }

        Log::info('RdvPermis API request', [
            'endpoint' => $path,
            'user_id' => $user->getKey(),
            'method' => $method,
            'status' => $response->status(),
        ]);

        if (! $response->successful()) {
            if ($response->status() === 401) {
                $this->tokens->markNeedsReauthentication($user);
            }

            throw $this->responseException($response);
        }

        return $response;
    }

    public function get(User $user, string $path, array $query = []): Response
    {
        return $this->request($user, 'GET', $path, $query === [] ? [] : ['query' => $query]);
    }

    public function post(User $user, string $path, array $data = []): Response
    {
        return $this->request($user, 'POST', $path, ['json' => $data]);
    }

    public function put(User $user, string $path, array $data = []): Response
    {
        return $this->request($user, 'PUT', $path, ['json' => $data]);
    }

    public function delete(User $user, string $path, array $data = []): Response
    {
        return $this->request($user, 'DELETE', $path, $data === [] ? [] : ['json' => $data]);
    }

    private function responseException(Response $response): RdvPermisApiException
    {
        $providerCode = strtoupper((string) $response->json('code'));

        return match (true) {
            $response->status() === 400 && $providerCode === 'CANDIDAT_INCONNU' => new RdvPermisApiException(
                'Le candidat est inconnu de RdvPermis.',
                400,
            ),
            $response->status() === 400 => new RdvPermisApiException(
                'RdvPermis a refusé les données envoyées.',
                400,
            ),
            $response->status() === 401 => new RdvPermisApiException(
                'La connexion Livret Numérique doit être renouvelée.',
                401,
            ),
            $response->status() === 403 => new RdvPermisApiException(
                'Votre compte n’est pas autorisé à accéder à cette ressource RdvPermis.',
                403,
            ),
            $response->status() === 404 => new RdvPermisApiException(
                'La ressource demandée est introuvable dans RdvPermis.',
                404,
            ),
            $response->status() === 409 && $providerCode === 'EMAIL_DEJA_ATTRIBUE' => new RdvPermisApiException(
                'Cette adresse e-mail est déjà attribuée dans RdvPermis.',
                409,
            ),
            $response->status() === 409 => new RdvPermisApiException(
                'RdvPermis signale un conflit avec les données du candidat.',
                409,
            ),
            $response->status() === 422 => new RdvPermisApiException(
                'RdvPermis a refusé les données envoyées.',
                422,
            ),
            $response->status() === 429 => new RdvPermisApiException(
                'RdvPermis reçoit trop de demandes. Réessayez plus tard.',
                429,
            ),
            $response->serverError() => new RdvPermisApiException(
                'Le service RdvPermis rencontre une erreur temporaire. Réessayez plus tard.',
                502,
            ),
            default => new RdvPermisApiException(
                'La requête RdvPermis n’a pas pu être traitée.',
                502,
            ),
        };
    }
}
