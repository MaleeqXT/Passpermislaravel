# RdvPermis RECETTE: request a localhost OAuth callback

## Why

RdvPermis (Keycloak realm `formation`, RECETTE) only redirects the OAuth
authorization code to the callback registered on our client:

    https://staging2.passpermisfacile.fr/api/rdvpermis/callback

The local backend (`http://localhost:8000`) therefore never receives a code, so
`localhost:5173/rdvpermis` stays "not connected" (`localOAuthUnavailable: true`).
Adding a loopback callback to the **same RECETTE client** lets local development
connect exactly like staging2, with its own token and refresh token.

Everything else is already in place locally:
- Laravel route `GET /api/rdvpermis/callback` (`RdvPermisController::callback`).
- `OAuthService::localCallbackConfigurationError()` allows a loopback callback
  when `APP_URL` is loopback.
- `src/rdvpermis/oauthPopup.js` trusts `http://localhost:5173` and `http://127.0.0.1:5173`.

## Email to send (French)

> **Objet :** RECETTE RdvPermis — ajout d'une URI de redirection locale pour notre client OAuth
>
> Bonjour,
>
> Dans le cadre de notre intégration des API RdvPermis (environnement RECETTE,
> realm `formation`), pourriez-vous ajouter les URI de redirection suivantes au
> client OAuth **[client_id à compléter]**, en plus de l'URI déjà enregistrée
> (`https://staging2.passpermisfacile.fr/api/rdvpermis/callback`) :
>
> - `http://localhost:8000/api/rdvpermis/callback`
> - `http://127.0.0.1:8000/api/rdvpermis/callback`
>
> Ces URI pointent vers le poste de développement local de notre équipe (boucle
> locale uniquement) et servent à tester l'intégration avant déploiement. Les
> scopes demandés restent inchangés :
> `rdvpermis livret_numerique:read livret_numerique:write offline_access`.
>
> Merci de nous confirmer la prise en compte, ou de nous indiquer la procédure
> à suivre si une autre démarche est nécessaire.
>
> Cordialement,
> **[Nom, société PassPermisFacile, coordonnées]**

## After the provider confirms

Change only the **local** Laravel `.env` (never staging2/production):

```dotenv
RDVPERMIS_REDIRECT_URI=http://localhost:8000/api/rdvpermis/callback
RDVPERMIS_FRONTEND_CALLBACK_URL=http://localhost:5173/rdvpermis
```

Then:

```powershell
php artisan config:clear
```

Open `http://localhost:5173/rdvpermis` and click **Connecter RdvPermis**. The popup
logs in at RdvPermis, returns to `localhost:8000/api/rdvpermis/callback`, and the
page should show **Connecté**.

Do not change `.env` before the confirmation: an unregistered `redirect_uri` is
rejected by RdvPermis at login ("Invalid parameter: redirect_uri").
