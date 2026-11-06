# Livret Numérique provider contract audit

Last reviewed: 2026-11-06.

The available Livret PDF is reference material only. It expressly directs
integrators to Swagger for the authoritative request fields and does not prove
that a route or payload is current. The authenticated Swagger UI was not
available to this implementation session, so no Livret provider operation is
permitted to emit HTTP.

| Operation | Method | Provider path | Request / response schema | Scopes | Status |
| --- | --- | --- | --- | --- | --- |
| Create training contract | Unknown | Unknown | Unknown | `livret_numerique:write` (configured) | UNVERIFIED - blocked closed |
| Retrieve/delete training contract | Unknown | Unknown | Unknown | Unknown | UNVERIFIED - blocked closed |
| Create circulation lesson | Unknown | Unknown | Unknown | `livret_numerique:write` (configured) | UNVERIFIED - blocked closed |
| Create plateau lesson | Unknown | Unknown | Unknown | `livret_numerique:write` (configured) | UNVERIFIED - blocked closed |
| Create simulator lesson | Unknown | Unknown | Unknown | `livret_numerique:write` (configured) | UNVERIFIED - blocked closed |
| Create theoretical lesson | Unknown | Unknown | Unknown | `livret_numerique:write` (configured) | UNVERIFIED - blocked closed |
| Trainer lookup | Unknown | Unknown | Unknown | `livret_numerique:read` (configured) | UNVERIFIED - blocked closed |
| Simulator lookup | Unknown | Unknown | Unknown | `livret_numerique:read` (configured) | UNVERIFIED - blocked closed |

`App\Services\LivretNumerique\LivretProviderGateway` is the sole provider
boundary. Populate a row above from captured current Swagger before adding an
`ApiClient` call; include its exact method, path, request/response schemas,
and documented errors in the same change.
