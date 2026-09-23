# RDVPermis RECETTE1 implementation status

Audit date: 2026-09-15. Scope: RDVPermis Auto-Ecole only.

## Sources and verification limits

- Supplied `APIs RdvPermis - Doc fonctionnelle & technique.pdf`, particularly pages 15-28 (exam workflow), 44-52 (Publisher OAuth), and 72 (rate limits).
- Current official documentation: https://api.integediteurs.rdv-permis.interieur.gouv.fr/swagger-ui/index.html
- A TLS-verified, unauthenticated GET of that URL returned HTTP 401 in this session. No current OpenAPI JSON/YAML export was found in the supplied documentation directory or application source.
- The two v2 paths below were supplied as confirmed by the project owner and already existed in AutoEcoleService. Local tests verify transport and Laravel behavior. They do not independently reverify the unavailable current Swagger schemas.
- The PDF is legacy v1. No v2 endpoint, payload field, enum, operationId, or provider response schema has been derived by renaming its v1 paths.

## Existing operations, verified locally

| Operation | Laravel route | Government route | Implementation |
| --- | --- | --- | --- |
| Current school | GET /api/rdvpermis/current-school | GET /api/v2/auto-ecole/moi | RdvPermisController::currentSchool -> AutoEcoleService::currentSchool -> ApiClient |
| Employees | GET /api/rdvpermis/employees | GET /api/v2/auto-ecole/employes | RdvPermisController::employees -> AutoEcoleService::employees -> ApiClient |

Both routes use `auth:sanctum` and `role:admin|super-admin|secretary`.
The services send GET requests with no request body, Accept: application/json, and the current user's stored bearer token.
Controller success responses forward provider JSON. Test fixtures are deliberately synthetic and are not government response schemas.

Existing integration routes also include GET /api/rdvpermis/status and POST /api/rdvpermis/connect with the same protection, plus the existing public GET /api/rdvpermis/callback.
No routes were added, and no OAuth service, callback, token service, configuration, credentials, migration, or database record was changed in this pass.

## Remaining operations: BLOCKED by unavailable current Swagger

For every row below, the current HTTP method/path, operationId, OAuth security/scopes, path/query parameters, body schema, required and optional fields, enums, success response schema, and documented errors remain unverified. No Laravel handler or fake provider contract was created for these operations.

The legacy references are for audit traceability only, not implementation instructions.

| Operation | Legacy PDF reference | Current implementation |
| --- | --- | --- |
| Take candidate under mandate | POST /api/v1/auto-ecole/mandats | BLOCKED |
| Search candidates | POST /api/v1/auto-ecole/candidats/recherche | BLOCKED |
| Heavy-group candidate search | No complete contract in supplied PDF | BLOCKED |
| Search centres / get centre | No complete contract in supplied PDF | BLOCKED |
| Read / save favourite centres | No complete contract in supplied PDF | BLOCKED |
| Search planning / slots | POST /api/v1/auto-ecole/planning/recherche | BLOCKED |
| Available exam slots | Page 72 mentions /crenodispo without a full contract | BLOCKED |
| First available slot date | No complete contract in supplied PDF | BLOCKED |
| Create basket | POST /api/v1/auto-ecole/paniers | BLOCKED |
| Read baskets | GET /api/v1/auto-ecole/paniers | BLOCKED |
| Add slot to basket | POST /api/v1/auto-ecole/paniers/{panierId}/creneaux | BLOCKED |
| Assign/remove candidate in basket slot | PUT /api/v1/auto-ecole/paniers/{panierId}/creneaux/{creneauId}/candidat | BLOCKED |
| Validate basket | POST /api/v1/auto-ecole/paniers/{panierId}/valider | BLOCKED |
| Scheduled exams | GET /api/v1/auto-ecole/examens | BLOCKED |
| Exam details | GET /api/v1/auto-ecole/examens/{id} | BLOCKED |
| Cancel exam | DELETE /api/v1/auto-ecole/examens/{examenId} | BLOCKED |
| Exams eligible for permutation | GET /api/v1/auto-ecole/examens/permutables | BLOCKED |
| Permute exams | POST /api/v1/auto-ecole/examens/permutation | BLOCKED |
| Replace exam candidate | POST /api/v1/auto-ecole/examens/remplacement | BLOCKED |
| Department threshold | GET /api/v1/auto-ecole/informations-seuil-departement | BLOCKED |
| Instructor-count declaration | POST /api/v1/auto-ecole/nombre-de-formateurs | BLOCKED; confirm it is still required before implementing |

## Business rules requiring current confirmation

The PDF describes mandate eligibility, permit-category restrictions, presentation delays, departmental quotas, basket expiry, and notifications on validation/cancellation/permutation/replacement. These rules are enforced by the government service and must not be recreated from legacy text.
The PDF even differs internally on basket capacity (12 slots on page 12; 36 exam places on page 24). No such limit was hardcoded.
Local training reservations are not automatically government exam bookings; no local reservation/training lifecycle was changed.

## Changes and local test coverage

- ApiClient explicitly rejects redirects through its existing upstream-error mapping. A caller cannot override that by passing allow_redirects=true.
- Connection failures produce the existing sanitized 503 exception without chaining a provider exception that may contain request credentials.
- Both existing operations now have service assertions for exact GET URL, empty body, bearer authentication, Accept header, and request count.
- Feature tests exercise the real Laravel routes and Sanctum/Spatie role middleware. Only unrelated Inertia shared-prop middleware is excluded.
- All three allowed roles succeed. Students, monitors, users without a role, and guests are rejected before provider calls.
- Each operation is tested with upstream 401, 403, 404, 422, 429, 500, 503, and 302. These test the application's existing mapping, not an asserted Swagger list of provider responses.
- Expired access tokens are refreshed before each operation. Refresh rotation is retained. Rejected refresh marks reconnect_required and prevents the API call.
- Provider errors and connection exceptions are checked for credential leakage.
- Existing token-encryption assertions now inspect the actual raw encrypted attributes instead of unset original attributes.
- HTTP-focused tests reject stray requests. Logging is mocked. New feature tests use in-memory authentication and token persistence; no live DB records, provider endpoints, real OAuth callback, or production success is faked outside tests.

Focused command from the backend directory:

```powershell
php -d xdebug.mode=off vendor/bin/phpunit --filter RdvPermis --do-not-cache-result
```

Result: **49 tests, 296 assertions passed; 0 failures, 0 skipped.**

## Blockers are independent

1. **Real OAuth only:** RDVPermis rejects the configured redirect_uri. This prevents end-to-end browser authorization and subsequent live requests using the resulting employee token. The existing local authorization-code and refresh tests run without registering a real callback.
2. **Current contract access:** Swagger HTTP 401 prevents implementing additional operations without guessing. A current authenticated OpenAPI export would resolve this documentation blocker independently of callback registration.

No external party was contacted. No Client Credentials flow was used. No Livret Numerique operation was implemented.

## Order once the current specification is available

1. Resolve and verify each operation's complete schema, security requirements, and errors from the export; record its version/date.
2. Add candidate, centre, planning, and slot lookup operations with their exact schemas and Http::fake route/authorization tests.
3. Add mandate, basket creation/read, slot addition, candidate assignment, and validation. Confirm retry/idempotency behavior before retrying writes.
4. Add scheduled-exam/detail reads, cancellation, permutation, and replacement with role checks and tests.
5. After the real redirect_uri is accepted, perform controlled RECETTE smoke tests of the same operations using the existing Authorization Code flow.
