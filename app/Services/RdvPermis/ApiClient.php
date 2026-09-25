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

            $diagnostic = $this->safeProviderDiagnostic($response);

            Log::warning('RdvPermis API request failed', [
                'endpoint' => $path,
                'user_id' => $user->getKey(),
                'method' => $method,
                'http_status' => $response->status(),
                'rdvpermis_error' => $diagnostic['code'],
                'rdvpermis_message' => $diagnostic['message'],
                'rdvpermis_body' => $diagnostic['body'],
            ]);

            throw $this->responseException($response, $diagnostic['code']);
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

    private function responseException(Response $response, ?string $providerCode): RdvPermisApiException
    {
        $exceptionDetails = [null, $response->status(), $providerCode];

        if (($message = $this->businessMessage($providerCode)) !== null) {
            return new RdvPermisApiException(
                $message,
                $response->status(),
                ...$exceptionDetails,
            );
        }

        return match (true) {
            $response->status() === 400 => new RdvPermisApiException(
                'RdvPermis a refusé les données envoyées.',
                400,
                ...$exceptionDetails,
            ),
            $response->status() === 401 => new RdvPermisApiException(
                'La connexion Livret Numérique doit être renouvelée.',
                401,
                ...$exceptionDetails,
            ),
            $response->status() === 403 => new RdvPermisApiException(
                'Votre compte n’est pas autorisé à accéder à cette ressource RdvPermis.',
                403,
                ...$exceptionDetails,
            ),
            $response->status() === 404 => new RdvPermisApiException(
                'La ressource demandée est introuvable dans RdvPermis.',
                404,
                ...$exceptionDetails,
            ),
            $response->status() === 409 => new RdvPermisApiException(
                'RdvPermis signale un conflit avec les données du candidat.',
                409,
                ...$exceptionDetails,
            ),
            $response->status() === 422 => new RdvPermisApiException(
                'RdvPermis a refusé les données envoyées.',
                422,
                ...$exceptionDetails,
            ),
            $response->status() === 429 => new RdvPermisApiException(
                'RdvPermis reçoit trop de demandes. Réessayez plus tard.',
                429,
                ...$exceptionDetails,
            ),
            $response->serverError() => new RdvPermisApiException(
                'Le service RdvPermis rencontre une erreur temporaire. Réessayez plus tard.',
                502,
                ...$exceptionDetails,
            ),
            default => new RdvPermisApiException(
                'La requête RdvPermis n’a pas pu être traitée.',
                502,
                ...$exceptionDetails,
            ),
        };
    }

    private function businessMessage(?string $providerCode): ?string
    {
        return match ($providerCode) {
            'CANDIDAT_INCONNU' => 'Le candidat est inconnu de RdvPermis.',
            'CANDIDAT_SANS_DEMANDE_ACTIVE' => 'Le candidat ne dispose pas de demande active dans RdvPermis.',
            'EMAIL_MANQUANT' => 'Une adresse e-mail est requise pour ce candidat dans RdvPermis.',
            'EMAIL_DEJA_ATTRIBUE' => 'Cette adresse e-mail est déjà attribuée dans RdvPermis.',
            'CANDIDAT_DEJA_SOUS_MANDAT' => 'Le candidat est déjà sous mandat d’une autre auto-école.',
            'CANDIDAT_DEJA_SOUS_MON_MANDAT' => 'Le candidat est déjà sous mandat de cette auto-école.',
            'EMPLOYE_AUTO_ECOLE_INEXISTANT' => 'L’employé auto-école indiqué est introuvable dans RdvPermis.',
            'EMPLOYE_N_APPARTIENT_PAS_A_AUTO_ECOLE' => 'L’employé indiqué n’appartient pas à cette auto-école dans RdvPermis.',
            'PARAMETRE_INVALIDE' => 'Un paramètre envoyé à RdvPermis est invalide.',
            'CENTRE_INCONNU' => 'Le centre sélectionné est inconnu de RdvPermis.',
            'ACTION_INTERDITE' => 'Cette action n’est pas autorisée par RdvPermis.',
            'PANIER_INCONNU' => 'Le panier demandé est introuvable dans RdvPermis.',
            'CRENEAU_INCONNU' => 'Le créneau demandé est introuvable dans RdvPermis.',
            'CRENEAU_PAS_DANS_LE_PANIER' => 'Ce créneau ne fait pas partie du panier RdvPermis.',
            'CRENEAU_DEJA_RESERVE' => 'Ce créneau est déjà réservé ou présent dans un autre panier.',
            'CANDIDAT_DEJA_DANS_PANIER' => 'Ce candidat est déjà présent dans un panier RdvPermis.',
            'CANDIDAT_SOUS_PENALITE' => 'Ce candidat est sous pénalité dans RdvPermis.',
            'CANDIDAT_DEJA_INSCRIT' => 'Ce candidat est déjà inscrit à un autre examen RdvPermis.',
            'CANDIDAT_PAS_SOUS_MANDAT' => 'Ce candidat n’est pas sous mandat de cette auto-école dans RdvPermis.',
            'CANDIDAT_EN_ATTENTE_DE_RESULTAT' => 'Ce candidat est en attente de résultat dans RdvPermis.',
            'CANDIDAT_AYANT_DEJA_OBTENU_SON_EXAMEN' => 'Ce candidat a déjà obtenu son examen pour ce groupe de permis.',
            'EXAMEN_INCONNU' => 'L’examen demandé est introuvable dans RdvPermis.',
            'EXAMEN_DEJA_ANNULE' => 'Cet examen est déjà annulé dans RdvPermis.',
            'EXAMEN_NON_MODIFIABLE' => 'Cet examen ne peut plus être modifié dans RdvPermis.',
            'EXAMEN_NON_NOMINATIF' => 'Cet examen ne possède pas de candidat assigné.',
            'PERMUTATION_IMPOSSIBLE' => 'Ces examens ne peuvent pas être permutés dans RdvPermis.',
            'NOMBRE_DE_REMPLACEMENTS_AUTORISES_ATTEINT' => 'Le nombre de remplacements autorisés est atteint.',
            default => null,
        };
    }

    /**
     * @return array{code:?string, message:?string, body:?string}
     */
    private function safeProviderDiagnostic(Response $response): array
    {
        $payload = $response->json();
        $payload = is_array($payload) ? $this->redactSensitiveValues($payload) : null;
        $code = $this->safeProviderCode(is_array($payload) ? $payload['code'] ?? $payload['erreur'] ?? null : null);
        $message = $this->safeProviderMessage($payload);
        $body = $payload === null ? $this->safeText($response->body()) : $this->safeText(json_encode($payload));

        return compact('code', 'message', 'body');
    }

    private function redactSensitiveValues(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (preg_match('/(authorization|token|secret|password|api[_-]?key)/i', (string) $key)) {
                $payload[$key] = '[redacted]';
            } elseif (is_array($value)) {
                $payload[$key] = $this->redactSensitiveValues($value);
            }
        }

        return $payload;
    }

    private function safeProviderCode(mixed $code): ?string
    {
        if (! is_string($code) && ! is_int($code)) {
            return null;
        }

        $code = strtoupper(trim((string) $code));

        return $code === '' ? null : mb_substr($code, 0, 100);
    }

    private function safeProviderMessage(?array $payload): ?string
    {
        if ($payload === null) {
            return null;
        }

        foreach (['message', 'error_description', 'detail', 'error'] as $key) {
            if (is_string($payload[$key] ?? null) && $payload[$key] !== '') {
                return $this->safeText($payload[$key], 500);
            }
        }

        return null;
    }

    private function safeText(string|false $value, int $limit = 2000): ?string
    {
        if ($value === false || $value === '') {
            return null;
        }

        $value = preg_replace('/[\r\n]+/', ' ', $value) ?? '';
        $value = preg_replace('/\b(authorization\s*:\s*bearer\s+|bearer\s+)[^\s,&}]+/i', '$1[redacted]', $value) ?? '';
        $value = preg_replace('/\b(access_token|refresh_token|client_secret|authorization)\s*["\']?\s*[:=]\s*["\']?[^\s,&}"\']+/i', '$1=[redacted]', $value) ?? '';

        return mb_substr($value, 0, $limit);
    }
}
