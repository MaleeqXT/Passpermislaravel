# RdvPermis: connect localhost through the staging2 callback

No new redirect URI is needed at RdvPermis. Local development keeps using the
registered staging2 callback, and staging2 forwards local logins back to the
developer's machine.

## How it works

1. Local (`RDVPERMIS_LOCAL_HANDOFF=true`) starts OAuth with
   `redirect_uri=https://staging2.passpermisfacile.fr/api/rdvpermis/callback`
   and a state prefixed `local.` (stored in the **local** database).
2. RdvPermis redirects the browser to the staging2 callback.
3. Staging2 sees the `local.` state and, because `RDVPERMIS_LOCAL_HANDOFF_URL`
   is set, redirects the browser to that loopback URL with `code`, `state`
   (and `error`, `error_description`). Staging2 does not use the code or touch its
   own state table.
4. The local callback validates the state in the local database, exchanges the
   code with the same staging2 `redirect_uri`, and stores its own access and
   refresh tokens. Then it redirects to `http://localhost:5173/rdvpermis?rdvpermis=connected`.

Safety rules in code (`OAuthService::localHandoffTarget()`):
- Only states starting with `local.` are forwarded.
- The target comes only from configuration and must be an `http(s)` loopback
  URL (`localhost`, `127.0.0.1`, `::1`). Any other host is ignored.
- A process with handoff enabled never forwards (no loop).
- Handoff is enabled only when `APP_ENV=local` and `APP_URL` is loopback.

## Staging2 setup (needed once)

1. Deploy the commit that adds `RdvPermisController::callback` handoff and
   `OAuthService::localHandoffTarget()`.
2. In the staging2 `.env`, add:

   ```dotenv
   RDVPERMIS_LOCAL_HANDOFF_URL=http://127.0.0.1:8000/api/rdvpermis/callback
   ```

3. Run `php artisan config:clear` (or `config:cache` if staging2 caches config).

Staging2's own connection is unchanged: its states have no `local.` prefix.

## Local setup (done on this machine)

```dotenv
RDVPERMIS_REDIRECT_URI=https://staging2.passpermisfacile.fr/api/rdvpermis/callback
RDVPERMIS_FRONTEND_CALLBACK_URL=http://localhost:5173/rdvpermis
RDVPERMIS_LOCAL_HANDOFF=true
```

The local Laravel server must listen on `127.0.0.1:8000`, the same address as the Vite proxy target.
Then open `http://localhost:5173/rdvpermis` → **Connecter RdvPermis**.
