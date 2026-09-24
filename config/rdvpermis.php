<?php

return [
    'environment' => env('RDVPERMIS_ENV', 'recette1'),
    'client_id' => env('RDVPERMIS_CLIENT_ID'),
    'client_secret' => env('RDVPERMIS_CLIENT_SECRET'),
    'authorization_url' => env(
        'RDVPERMIS_AUTH_URL',
        'https://recette.moncompte.permisdeconduire.gouv.fr/auth/realms/formation/protocol/openid-connect/auth'
    ),
    'token_url' => env(
        'RDVPERMIS_TOKEN_URL',
        'https://recette.moncompte.permisdeconduire.gouv.fr/auth/realms/formation/protocol/openid-connect/token'
    ),
    'api_url' => env(
        'RDVPERMIS_API_URL',
        'https://api.integediteurs.rdv-permis.interieur.gouv.fr'
    ),
    'current_school_path' => env('RDVPERMIS_CURRENT_SCHOOL_PATH', '/api/v2/auto-ecole/moi'),
    'redirect_uri' => env('RDVPERMIS_REDIRECT_URI'),
    'frontend_callback_url' => env('RDVPERMIS_FRONTEND_CALLBACK_URL'),
    'scopes' => array_values(array_filter(explode(' ', env(
        'RDVPERMIS_SCOPES',
        'rdvpermis livret_numerique:read livret_numerique:write offline_access'
    )))),
    'state_ttl_minutes' => (int) env('RDVPERMIS_STATE_TTL_MINUTES', 10),
    'timeout' => (int) env('RDVPERMIS_TIMEOUT', 20),
];
