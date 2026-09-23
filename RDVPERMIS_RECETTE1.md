# Intégration RdvPermis / Livret Numérique — RECETTE1

## État de l’intégration

Le backend implémente le flux OAuth 2.0 Authorization Code pour un compte administrateur, super-administrateur ou secrétaire. Le secret client et les jetons restent exclusivement côté Laravel. Les jetons d’accès et de rafraîchissement sont chiffrés par les casts Eloquent avant stockage.

Le test fonctionnel complet est bloqué tant que l’équipe RdvPermis n’a pas confirmé l’URI de redirection enregistrée. `RDVPERMIS_REDIRECT_URI` et `RDVPERMIS_FRONTEND_CALLBACK_URL` doivent donc rester vides jusqu’à cette confirmation.

## Configuration

```dotenv
RDVPERMIS_ENV=recette1
RDVPERMIS_CLIENT_ID=ef4a4dd3-ff91-4ff1-9cdf-961ad464c655
RDVPERMIS_CLIENT_SECRET=<SECRET_FOURNI_PAR_RDVPERMIS>
RDVPERMIS_AUTH_URL=https://auth.integediteurs.rdv-permis.interieur.gouv.fr/realms/formation/protocol/openid-connect/auth
RDVPERMIS_TOKEN_URL=https://auth.integediteurs.rdv-permis.interieur.gouv.fr/realms/formation/protocol/openid-connect/token
RDVPERMIS_API_URL=https://api.integediteurs.rdv-permis.interieur.gouv.fr
RDVPERMIS_CURRENT_SCHOOL_PATH=/api/v1/auto-ecole/moi
RDVPERMIS_REDIRECT_URI=<URI_BACKEND_ENREGISTREE_PAR_RDVPERMIS>
RDVPERMIS_FRONTEND_CALLBACK_URL=<PAGE_FRONTEND_EXISTANTE>
RDVPERMIS_SCOPES="rdvpermis livret_numerique:read livret_numerique:write offline_access"
RDVPERMIS_TIMEOUT=20
```

Ne jamais ajouter `RDVPERMIS_CLIENT_SECRET` à une variable `VITE_*`, au dépôt Git, à Postman partagé ou aux logs.

## Routes locales

Toutes les routes sauf le callback exigent une authentification Sanctum et l’un des rôles `admin`, `super-admin` ou `secretary`.

- `GET /api/rdvpermis/status` : état de la configuration et de la connexion, sans jeton ni secret.
- `POST /api/rdvpermis/connect` : crée un `state` temporaire et renvoie l’URL d’autorisation.
- `GET /api/rdvpermis/callback` : callback OAuth public, valide et consomme le `state`, échange le code, puis redirige vers le frontend.
- `GET /api/rdvpermis/current-school` : appelle `/api/v1/auto-ecole/moi` avec le jeton de l’utilisateur connecté.

## Test avec Postman ou curl

Utiliser un jeton Sanctum d’un utilisateur autorisé. Ne jamais saisir le secret RdvPermis dans Postman.

```bash
curl -H "Authorization: Bearer <SANCTUM_TOKEN>" \
  -H "Accept: application/json" \
  http://localhost:8000/api/rdvpermis/status

curl -X POST \
  -H "Authorization: Bearer <SANCTUM_TOKEN>" \
  -H "Accept: application/json" \
  http://localhost:8000/api/rdvpermis/connect

curl -H "Authorization: Bearer <SANCTUM_TOKEN>" \
  -H "Accept: application/json" \
  http://localhost:8000/api/rdvpermis/current-school
```

Après `connect`, ouvrir `data.authorization_url` dans le navigateur et terminer l’authentification sur le site du ministère. Le callback ne peut réussir que si son URI correspond exactement à celle enregistrée par RdvPermis.

## Rafraîchissement et erreurs

Le client réutilise le jeton d’accès tant qu’il est valide. À expiration, il utilise le `refresh_token` et enregistre le nouveau couple de jetons retourné (rotation comprise). Un refus HTTP 401 force une nouvelle authentification. Les erreurs 403, 404, 422, 429, 5xx et les erreurs réseau sont converties en messages locaux sûrs; les réponses distantes et les jetons ne sont pas journalisés.

## Validation avant recette

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:list --path=rdvpermis
php artisan test --filter=RdvPermis
php artisan migrate:status
```

Vérifier ensuite `GET /api/rdvpermis/status`. Tant que les deux URI de callback sont absentes, `configured` doit être `false` et `missing_configuration` doit les lister sans divulguer le secret.
