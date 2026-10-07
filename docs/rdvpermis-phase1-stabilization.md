# RdvPermis — Phase 1 stabilization report

Completed 2026-10-07. Backend stabilization only. Existing user changes were preserved; Phase 2 UI was not started. Contract source: the authenticated current Auto-Ecole Swagger/OpenAPI (3.1, provider document version 0.0.2) read during the audit. The older supplied PDF is background evidence, not an instruction source or a current quota contract.

## 1. Files Changed

The inventory covers this phase's source, configuration, tests and documentation. Preexisting satisfaction, billing, documents, progress and Bilan changes are excluded. Generated build files under frontend `dist/` and test XML under backend `storage/logs/` are verification outputs.

| Path | What changed | Why |
|---|---|---|
| [.env.example](C:/wamp64/www/Passpermislaravel/.env.example) | Cleared application, Stripe and Bugsnag credentials; replaced client/public-key values with placeholders; removed unused path; documented shared cache setting. | Keep deployment templates free of real credentials and misleading settings. |
| [.gitignore](C:/wamp64/www/Passpermislaravel/.gitignore) | Ignore root ZIP, 7z, TAR and compressed TAR backups. | Exclude the archive containing RdvPermis credentials and similar future backups. |
| [app/Console/Commands/ImportStudents.php](C:/wamp64/www/Passpermislaravel/app/Console/Commands/ImportStudents.php) | Preserve imported NEPH as a nullable string. | Avoid losing zeroes or changing identifiers during CSV import. |
| [app/Exceptions/RdvPermisApiException.php](C:/wamp64/www/Passpermislaravel/app/Exceptions/RdvPermisApiException.php) | Add safe retryAfter metadata. | Carry rate-limit and lock-contention delays without exposing provider details. |
| [app/Http/Controllers/V1/EndPoint/RdvPermis/CandidateEligibilityController.php](C:/wamp64/www/Passpermislaravel/app/Http/Controllers/V1/EndPoint/RdvPermis/CandidateEligibilityController.php) | Add the separate slot eligibility search controller. | Return the provider candidate/eligibility array with safe error handling. |
| [app/Http/Controllers/V1/EndPoint/RdvPermis/CandidateMandateController.php](C:/wamp64/www/Passpermislaravel/app/Http/Controllers/V1/EndPoint/RdvPermis/CandidateMandateController.php) | Use shared typed provider errors, safe retry headers and generic internal failures. | Preserve the existing operation while avoiding raw exceptions and misleading authentication errors. |
| [app/Http/Controllers/V1/EndPoint/RdvPermis/CentreController.php](C:/wamp64/www/Passpermislaravel/app/Http/Controllers/V1/EndPoint/RdvPermis/CentreController.php) | Use shared typed provider errors, safe retry headers and generic internal failures. | Preserve the existing operation while avoiding raw exceptions and misleading authentication errors. |
| [app/Http/Controllers/V1/EndPoint/RdvPermis/ExamController.php](C:/wamp64/www/Passpermislaravel/app/Http/Controllers/V1/EndPoint/RdvPermis/ExamController.php) | Use shared typed provider errors, safe retry headers and generic internal failures. | Preserve the existing operation while avoiding raw exceptions and misleading authentication errors. |
| [app/Http/Controllers/V1/EndPoint/RdvPermis/HandlesRdvPermisErrors.php](C:/wamp64/www/Passpermislaravel/app/Http/Controllers/V1/EndPoint/RdvPermis/HandlesRdvPermisErrors.php) | Share safe error responses and Retry-After forwarding. | Distinguish internal failures from authentication failures consistently. |
| [app/Http/Controllers/V1/EndPoint/RdvPermis/PanierController.php](C:/wamp64/www/Passpermislaravel/app/Http/Controllers/V1/EndPoint/RdvPermis/PanierController.php) | Check provider slot eligibility before non-null candidate assignment; adopt safe errors. | Block unconfirmed/ineligible candidates while preserving candidate removal and provider assignment validation. |
| [app/Http/Controllers/V1/EndPoint/RdvPermis/PlanningController.php](C:/wamp64/www/Passpermislaravel/app/Http/Controllers/V1/EndPoint/RdvPermis/PlanningController.php) | Use shared typed provider errors, safe retry headers and generic internal failures. | Preserve the existing operation while avoiding raw exceptions and misleading authentication errors. |
| [app/Http/Controllers/V1/EndPoint/RdvPermis/RdvPermisController.php](C:/wamp64/www/Passpermislaravel/app/Http/Controllers/V1/EndPoint/RdvPermis/RdvPermisController.php) | Calculate readiness from credentials/expiry/configuration; sanitize status and operation failures. | Do not equate a stored status label with a usable connection or render raw internal errors. |
| [app/Http/Controllers/V1/EndPoint/RdvPermis/StudentMandateController.php](C:/wamp64/www/Passpermislaravel/app/Http/Controllers/V1/EndPoint/RdvPermis/StudentMandateController.php) | Keep business validation/staging diagnostics while using safe typed errors and generic internal failures. | Avoid misleading 401 responses for database/network failures. |
| [app/Http/Middleware/HandleInertiaRequests.php](C:/wamp64/www/Passpermislaravel/app/Http/Middleware/HandleInertiaRequests.php) | Skip Inertia shared props only for RdvPermis JSON/callback and student-mandate paths. | Keep callback/session/auth guarantees without unrelated competencies/training queries. |
| [app/Http/Requests/RdvPermis/CandidateEligibilityRequest.php](C:/wamp64/www/Passpermislaravel/app/Http/Requests/RdvPermis/CandidateEligibilityRequest.php) | Validate filtre.creneauId and documented optional name/numeroDossier criteria. | Match the current Swagger structure without adding pagination or invented fields. |
| [app/Http/Requests/V1/Student/User/Info/RegisterStudentRequest.php](C:/wamp64/www/Passpermislaravel/app/Http/Requests/V1/Student/User/Info/RegisterStudentRequest.php) | Validate NEPH as a nullable or required nonempty string and correct numeric/length error messages where present. | Follow the confirmed string contract without a twelve-digit rule. |
| [app/Http/Requests/V1/Student/User/Info/StoreStudentRequest.php](C:/wamp64/www/Passpermislaravel/app/Http/Requests/V1/Student/User/Info/StoreStudentRequest.php) | Validate NEPH as a nullable or required nonempty string and correct numeric/length error messages where present. | Follow the confirmed string contract without a twelve-digit rule. |
| [app/Http/Requests/V1/Student/User/Info/UpdateStudentRequest.php](C:/wamp64/www/Passpermislaravel/app/Http/Requests/V1/Student/User/Info/UpdateStudentRequest.php) | Validate NEPH as a nullable or required nonempty string and correct numeric/length error messages where present. | Follow the confirmed string contract without a twelve-digit rule. |
| [app/Http/Requests/V1/Student/User/Params/UpdateNephRequest.php](C:/wamp64/www/Passpermislaravel/app/Http/Requests/V1/Student/User/Params/UpdateNephRequest.php) | Validate NEPH as a nullable or required nonempty string and correct numeric/length error messages where present. | Follow the confirmed string contract without a twelve-digit rule. |
| [app/Models/RdvPermisSyncRecord.php](C:/wamp64/www/Passpermislaravel/app/Models/RdvPermisSyncRecord.php) | Explicit canonical table and provider-context array cast. | Use the real historical migration schema and persist context safely. |
| [app/Models/RdvPermisToken.php](C:/wamp64/www/Passpermislaravel/app/Models/RdvPermisToken.php) | Explicit canonical table and require known future access expiry. | Make fresh and existing installations consistent and stop indefinite expiry trust. |
| [app/Services/RdvPermis/ApiClient.php](C:/wamp64/www/Passpermislaravel/app/Services/RdvPermis/ApiClient.php) | Apply cooldown; capture 429 delay; add six Swagger-confirmed business messages; preserve transport classifications. | Keep the existing client and no-retry behavior with safer operational failures. |
| [app/Services/RdvPermis/AutoEcoleService.php](C:/wamp64/www/Passpermislaravel/app/Services/RdvPermis/AutoEcoleService.php) | Document email as optional in the preserved mandate payload. | Align the existing service contract with current provider requirements. |
| [app/Services/RdvPermis/CandidateEligibilityService.php](C:/wamp64/www/Passpermislaravel/app/Services/RdvPermis/CandidateEligibilityService.php) | Call the separate v2 eligibility endpoint and require exact candidate ID with estEligible=true before assignment. | Keep mandated listing separate and make the provider authoritative. |
| [app/Services/RdvPermis/ProviderCooldown.php](C:/wamp64/www/Passpermislaravel/app/Services/RdvPermis/ProviderCooldown.php) | Use shared cache deadlines scoped to provider/client/actor and channel; parse/cap safe Retry-After seconds. | Suppress immediate follow-ups without hardcoding historical quotas. |
| [app/Services/RdvPermis/RdvPermisSyncService.php](C:/wamp64/www/Passpermislaravel/app/Services/RdvPermis/RdvPermisSyncService.php) | Persist permitted nonempty mapping fields and preserve previous IDs/context on incomplete responses; sanitize internal failures. | Avoid conflating identities or clearing successful mappings. |
| [app/Services/RdvPermis/StudentMandateService.php](C:/wamp64/www/Passpermislaravel/app/Services/RdvPermis/StudentMandateService.php) | Allow absent local email, validate supplied email, persist separate mandate/candidate/school IDs and context. | Support existing candidates and preserve useful provider identity after success. |
| [app/Services/RdvPermis/TokenService.php](C:/wamp64/www/Passpermislaravel/app/Services/RdvPermis/TokenService.php) | Lock refresh, re-read credentials, reuse newer tokens, save rotation, classify refresh failures, and derive readiness. | Prevent rotating-token races and stop repeated refresh for unknown expiry. |
| [bootstrap/app.php](C:/wamp64/www/Passpermislaravel/bootstrap/app.php) | Preserve opaque NEPH/query strings through trimming; sanitize pre-controller RdvPermis database failures. | Avoid identifier modification and raw SQL disclosure while retaining normal middleware. |
| [config/rdvpermis.php](C:/wamp64/www/Passpermislaravel/config/rdvpermis.php) | Remove unused current-school override; expose shared cache selection; document explicit environment URLs and short lock wait. | Avoid misleading configuration while keeping working v2 constants. |
| [database/migrations/2026_10_07_000001_normalize_rdvpermis_token_table.php](C:/wamp64/www/Passpermislaravel/database/migrations/2026_10_07_000001_normalize_rdvpermis_token_table.php) | Lossless legacy token-table rename; safe stop for dual tables; retain data on rollback. | Preserve existing encrypted credentials and support historical fresh migrations. |
| [database/migrations/2026_10_07_000002_store_neph_as_text.php](C:/wamp64/www/Passpermislaravel/database/migrations/2026_10_07_000002_store_neph_as_text.php) | Change students.neph from BIGINT to nullable TEXT; retain string storage on rollback. | Preserve existing numeric values and future zero-prefixed/longer identifiers. |
| [database/migrations/2026_10_07_000003_add_rdvpermis_candidate_mapping.php](C:/wamp64/www/Passpermislaravel/database/migrations/2026_10_07_000003_add_rdvpermis_candidate_mapping.php) | Add nullable candidate ID, permit group, school ID and JSON context; retain data and support rerun. | Extend historical sync records without destructive schema changes. |
| [phpunit.rdvpermis.xml](C:/wamp64/www/Passpermislaravel/phpunit.rdvpermis.xml) | Define the dedicated test suite with in-memory SQLite, array cache/session and disabled external notification stages. | Avoid touching the application database or sending external error notifications during tests. |
| [resources/espace-admin/features/general/dashboard/EditSecretary.vue](C:/wamp64/www/Passpermislaravel/resources/espace-admin/features/general/dashboard/EditSecretary.vue) | Remove the fixed twelve-digit NEPH input mask. | Allow identifiers to pass through unchanged in existing forms. |
| [resources/espace-admin/features/general/dashboard/Secretary.vue](C:/wamp64/www/Passpermislaravel/resources/espace-admin/features/general/dashboard/Secretary.vue) | Remove the fixed twelve-digit NEPH input mask. | Allow identifiers to pass through unchanged in existing forms. |
| [resources/espace-admin/features/roles/students/partials/StudentForm.vue](C:/wamp64/www/Passpermislaravel/resources/espace-admin/features/roles/students/partials/StudentForm.vue) | Remove the fixed twelve-digit NEPH input mask. | Allow identifiers to pass through unchanged in existing forms. |
| [resources/espace-secretary/features/roles/students/partials/StudentForm.vue](C:/wamp64/www/Passpermislaravel/resources/espace-secretary/features/roles/students/partials/StudentForm.vue) | Remove the fixed twelve-digit NEPH input mask. | Allow identifiers to pass through unchanged in existing forms. |
| [resources/espace-student/features/settings/neph/NephPage.vue](C:/wamp64/www/Passpermislaravel/resources/espace-student/features/settings/neph/NephPage.vue) | Remove the fixed twelve-digit NEPH input mask. | Allow identifiers to pass through unchanged in existing forms. |
| [routes/api.php](C:/wamp64/www/Passpermislaravel/routes/api.php) | Add POST api/rdvpermis/candidats/recherche under existing administrative authorization. | Expose slot eligibility without replacing mandated search. |
| [tests/Feature/RdvPermisAutoEcoleApiTest.php](C:/wamp64/www/Passpermislaravel/tests/Feature/RdvPermisAutoEcoleApiTest.php) | Run existing protected endpoint tests with Inertia middleware enabled. | Verify the narrow shared-data isolation without disabling required middleware. |
| [tests/Feature/RdvPermisCentreApiTest.php](C:/wamp64/www/Passpermislaravel/tests/Feature/RdvPermisCentreApiTest.php) | Run existing protected endpoint tests with Inertia middleware enabled. | Verify the narrow shared-data isolation without disabling required middleware. |
| [tests/Feature/RdvPermisEligibilityAndErrorsTest.php](C:/wamp64/www/Passpermislaravel/tests/Feature/RdvPermisEligibilityAndErrorsTest.php) | Cover eligibility shape, roles, validation, assignment gating, retry headers/cooldown, six errors and safe internal/SQL failures. | Verify authoritative provider behavior and safe responses without live traffic. |
| [tests/Feature/RdvPermisExamApiTest.php](C:/wamp64/www/Passpermislaravel/tests/Feature/RdvPermisExamApiTest.php) | Run existing protected endpoint tests with Inertia middleware enabled. | Verify the narrow shared-data isolation without disabling required middleware. |
| [tests/Feature/RdvPermisFreshMigrationTest.php](C:/wamp64/www/Passpermislaravel/tests/Feature/RdvPermisFreshMigrationTest.php) | Exercise actual migrations and Laravel migration runner, legacy/fresh token schemas, ciphertext preservation, dual-table safety, NEPH validation and durable mapping. | Expose schema defects previously hidden by fixtures. |
| [tests/Feature/RdvPermisImportAccessTokenCommandTest.php](C:/wamp64/www/Passpermislaravel/tests/Feature/RdvPermisImportAccessTokenCommandTest.php) | Use actual user/token migrations and remove the misleading fixture rename. | Verify local encrypted imports against the canonical production schema. |
| [tests/Feature/RdvPermisOAuthCallbackTest.php](C:/wamp64/www/Passpermislaravel/tests/Feature/RdvPermisOAuthCallbackTest.php) | Keep middleware enabled with an empty in-memory database and no allowed remote requests. | Prove callback success/failure does not require competencies or UI tables. |
| [tests/Feature/RdvPermisPanierApiTest.php](C:/wamp64/www/Passpermislaravel/tests/Feature/RdvPermisPanierApiTest.php) | Run existing protected endpoint tests with Inertia middleware enabled. | Verify the narrow shared-data isolation without disabling required middleware. |
| [tests/Feature/RdvPermisPanierSlotApiTest.php](C:/wamp64/www/Passpermislaravel/tests/Feature/RdvPermisPanierSlotApiTest.php) | Enable Inertia middleware and add the eligibility preflight fixture before assignment. | Preserve existing contracts while exercising the new provider confirmation gate. |
| [tests/Feature/RdvPermisPlanningApiTest.php](C:/wamp64/www/Passpermislaravel/tests/Feature/RdvPermisPlanningApiTest.php) | Run existing protected endpoint tests with Inertia middleware enabled. | Verify the narrow shared-data isolation without disabling required middleware. |
| [tests/Feature/RdvPermisReadinessTest.php](C:/wamp64/www/Passpermislaravel/tests/Feature/RdvPermisReadinessTest.php) | Test absent, valid, expired, unknown, rejected, corrupt and misconfigured connections without provider calls. | Verify all readiness states and safe status data. |
| [tests/Feature/RdvPermisRefreshSafetyTest.php](C:/wamp64/www/Passpermislaravel/tests/Feature/RdvPermisRefreshSafetyTest.php) | Use actual token migration for stale concurrent readers, lock contention/release, rotation, temporary failures, invalid grants, unknown expiry and 429. | Test refresh safety against persisted credentials. |
| [tests/Feature/RdvPermisStudentMandateTest.php](C:/wamp64/www/Passpermislaravel/tests/Feature/RdvPermisStudentMandateTest.php) | Use actual users/student/monitor/secretary/sync migrations; verify separate IDs, optional email, provider email requirement, invalid email and incomplete successes. | Test complete mandate synchronization against real schema. |
| [tests/Feature/RdvPermisTokenPersistenceRecoveryTest.php](C:/wamp64/www/Passpermislaravel/tests/Feature/RdvPermisTokenPersistenceRecoveryTest.php) | Use actual users/token migrations and remove the fixture table rename. | Verify encrypted persistence/recovery without hiding canonical-table defects. |
| [tests/Support/RdvPermisDatabaseTestCase.php](C:/wamp64/www/Passpermislaravel/tests/Support/RdvPermisDatabaseTestCase.php) | Create an isolated database using actual supporting user and token migrations. | Share safe, faithful persistence fixtures across stabilization tests. |
| [tests/Unit/RdvPermisApiClientTest.php](C:/wamp64/www/Passpermislaravel/tests/Unit/RdvPermisApiClientTest.php) | Give mocked users explicit IDs for scoped cooldown. | Keep error/redirect tests representative of authenticated callers. |
| [tests/Unit/RdvPermisTokenServiceTest.php](C:/wamp64/www/Passpermislaravel/tests/Unit/RdvPermisTokenServiceTest.php) | Replace two database-bypassing direct-refresh tests with the real-schema refresh safety suite. | Verify actual locking, expiry and persistence rather than fabricated storage. |
| [C:/wamp64/www/MaleeqPermiFAst/.gitignore](C:/wamp64/www/MaleeqPermiFAst/.gitignore) | Ignore root backup archives. | Keep credential-bearing backups out of Git. |
| [C:/wamp64/www/MaleeqPermiFAst/src/Components/candidates/CandidateForm.jsx](C:/wamp64/www/MaleeqPermiFAst/src/Components/candidates/CandidateForm.jsx) | Remove NEPH digit filtering, twelve-character truncation and twelve-digit validation. | Preserve the provider identifier in the existing candidate form. |
| [C:/wamp64/www/MaleeqPermiFAst/src/permis-web/pages/RegisterStudentForm.jsx](C:/wamp64/www/MaleeqPermiFAst/src/permis-web/pages/RegisterStudentForm.jsx) | Remove NEPH digit filtering, twelve-character truncation and the twelve-digit placeholder/numeric input hint. | Preserve identifiers during existing student registration. |
| [C:/wamp64/www/Passpermislaravel/docs/rdvpermis-phase1-stabilization.md](C:/wamp64/www/Passpermislaravel/docs/rdvpermis-phase1-stabilization.md) | Record the completed implementation, inventory, verification, security findings and RECETTE checklist. | Make this phase reviewable and provide a clear gate before Phase 2. |

`RouteServiceProvider.php`, the OAuth state/exchange service, old migrations and the real `.env` needed no source edits. Web/session/auth middleware remains in place. Only unrelated Inertia page-data sharing is skipped on the narrowly matched RdvPermis paths.

## 2. Database Changes

Three new forward migrations were applied only to the inspected local MySQL database, `permis_facile_db` on loopback, guarded by `APP_ENV=local`.

1. `2026_10_07_000001_normalize_rdvpermis_token_table`: canonical name is `rdvpermis_tokens`, matching the historical migration. An existing `rdv_permis_tokens` table is renamed if the canonical table is absent. A fresh historical migration already creates the canonical name. If both names exist, migration stops before altering either table; an operator must reconcile them without discarding credentials.
2. `2026_10_07_000002_store_neph_as_text`: changes nullable `students.neph` from BIGINT to TEXT. The contract declares a nonempty string without a maximum or digit pattern; no new application length limit is imposed.
3. `2026_10_07_000003_add_rdvpermis_candidate_mapping`: adds nullable `remote_candidate_id`, `permit_group`, `remote_school_id`, and JSON `provider_context` to `rdvpermis_sync_records`. Existing `remote_id` remains the mandate ID, and `entity_id` remains the local student ID.

These repair migrations deliberately retain data in `down()`. Numeric reversal and mapping deletion would lose identifiers. The mapping migration can be rerun after its retained-data rollback; the canonical token repair is idempotent. Deploy migrations with the code as one maintenance change because older running code expects the old token table name. Historical migrations were not edited.

Local preservation checks:

| Check | Result |
|---|---|
| Token records | 1 before / 1 after |
| All token fields, including encrypted access/refresh ciphertext | Unchanged |
| Student records | 2,006 before / 2,006 after |
| Every stored NEPH and null value | Preserved exactly as a string/null |
| NEPH column | BIGINT before / TEXT after |
| Sync records | 0 before / 0 after; model query succeeds |
| New mapping columns | Present |
| Shared database cache lock | Acquired and released successfully |
| Real `.env` file | Hash unchanged |
| Provider requests during local migration/check | 0 |

## 3. Fixed Issues

- **Audit 1–2:** models use the actual canonical tables; forward compatibility repair preserves deployed token records.
- **Audit 3:** students store NEPH as text; four student requests use strings; import no longer casts to integer; React registration/candidate forms and five Vue forms no longer enforce a twelve-digit mask/truncation.
- **Audit 4:** critical schema tests use actual historical and new migrations. A separate test invokes Laravel's migration runner on an empty database and then repeats it. Standard token fixtures no longer rename tables to hide a mismatch.
- **Audit 5:** OAuth callback and JSON routes skip unrelated Inertia queries while retaining web/session/auth and OAuth state handling.
- **Audit 6:** local status is derived from decrypted credential usability, expiry, refreshability and configuration; rendering status sends no provider request.
- **Audit 7:** separate `POST /api/rdvpermis/candidats/recherche` validates `filtre.creneauId` and supported name/numeroDossier search criteria, and calls the current v2 provider endpoint. It returns the provider array of `{candidat, eligibilite}` unchanged. General mandated search remains separate. Non-null slot assignment now requires a matching candidate whose provider `eligibilite.estEligible` is strictly true. Missing, false or malformed confirmation prevents assignment; null removal is preserved. Provider assignment remains authoritative if eligibility changes between requests.
- **Audit 8:** successful mandate sync retains separate mandate/candidate IDs, permit group, school ID, environment/API/actor context, status and timestamps. Missing fields in later responses do not erase previous complete mappings. Supplied email is validated and sent; absent local email is omitted so the provider can accept an existing account or return `EMAIL_MANQUANT`.
- **Audit 9:** refresh uses a shared-cache lock, re-reads the persisted token under the lock, reuses any credential already refreshed by another caller, and persists rotating tokens. Lock wait is two seconds; TTL is configured HTTP timeout plus ten seconds; locks release on success and exceptions.
- **Audit 10:** unknown expiry becomes `connection_unverified` and requires reconnect before API use; it cannot cause endless refresh. Refresh network/service/configuration failures are temporary 503 responses, invalid grants require reconnect, 429 responses preserve safe retry delays and cooldown, and internal failures return generic 500 responses. Six Swagger-confirmed business codes have explicit messages. Configuration/template/archive hygiene was corrected.

Readiness meanings:

| Status | Meaning |
|---|---|
| `not_connected` | No saved connection |
| `connected` | Known future access expiry, readable access credential, accepted local status and complete configuration; this is local readiness, not proof of a live provider call |
| `connection_unverified` | Unknown expiry, expired access with an available refresh path, or incomplete configuration |
| `reconnect_required` | Rejected/unreadable credentials or expired access without a usable refresh path |

`GET /api/rdvpermis/current-school` remains the explicit practical provider verification operation. A status-page render does not invoke it.

Cooldown uses the configured shared cache, scoped to provider URLs/client/actor and separately to API/authentication requests. Retry-After supports seconds and HTTP dates, is forwarded only as sanitized seconds, and is bounded to 1–86,400 seconds. Without a usable header the fallback backoff is 60 seconds. These bounds/backoff are application safety policy, not provider quotas. The code does not automatically retry requests.

## 4. Tests Added/Changed

New: `RdvPermisFreshMigrationTest`, `RdvPermisRefreshSafetyTest`, `RdvPermisReadinessTest`, `RdvPermisEligibilityAndErrorsTest`, and real-migration support base.

Changed: mandate synchronization tests, token persistence/import fixtures, OAuth callback isolation, API client actor fixtures, panier candidate assignment, and existing endpoint tests with Inertia middleware enabled. The old two direct-refresh unit tests were replaced by real persisted-token coverage.

Coverage includes normal, zero-prefixed, long and provider-string identifiers; missing/invalid type validation; fresh/legacy/ambiguous token schemas; encryption at rest; mandate mapping/email/incomplete responses; slot-specific eligibility and assignment gating; OAuth callback with no competencies table; known/expired/unknown expiry; rotation and stale concurrent callers; lock contention and release; invalid grants; network/server/configuration refresh failures; safe internal/model-binding database errors; 429 retry headers and suppression; and all six added business errors. Existing endpoint/OAuth state tests continue to run.

## 5. Test Results

| Run | Total | Passed | Failed | Risky | Skipped | Assertions |
|---|---:|---:|---:|---:|---:|---:|
| Dedicated RdvPermis suite | 201 | 201 | 0 | 0 | 0 | 1,006 |
| Fresh-migration-focused subset | 34 | 34 | 0 | 0 | 0 | 61 |

The 34 migration tests are a subset of the 201; they are not an additional independent total.

Commands (PHP 8.3.28):

```text
php -d xdebug.mode=off vendor/phpunit/phpunit/phpunit -c phpunit.rdvpermis.xml --log-junit storage/logs/rdvpermis-phase1-tests.xml
php -d xdebug.mode=off vendor/phpunit/phpunit/phpunit -c phpunit.rdvpermis.xml --filter RdvPermisFreshMigrationTest --log-junit storage/logs/rdvpermis-fresh-migrations.xml
```

Verification outputs: [dedicated suite XML](C:/wamp64/www/Passpermislaravel/storage/logs/rdvpermis-phase1-tests.xml), [migration subset XML](C:/wamp64/www/Passpermislaravel/storage/logs/rdvpermis-fresh-migrations.xml).

Other checks passed: 49 affected PHP source/test/migration files linted; five edited Vue forms parsed and compiled; React production build succeeded; phase changes pass whitespace checks; route registration confirms separate eligibility and mandated searches; both root backup archives are ignored; template credential fields are blank/placeholders. React build retains its existing large-bundle warning. The relevant eight fresh migrations were tested through Laravel on SQLite, and the three forward repairs were applied and verified on existing local MySQL. This was not a full unrelated application test-suite or all-migrations clean-install audit.

All HTTP provider behavior in tests is mocked and stray Laravel HTTP requests are prevented. No live mandate creation, booking, cancellation, replacement or permutation was executed.

## 6. Remaining Issues

- Leading zeroes already lost by historical numeric storage cannot be recovered by this migration; only a trusted source can restore them.
- Live RECETTE OAuth permissions, scopes and provider acceptance still need verification. Current-school success is necessary to confirm the live connection rather than only local readiness.
- Existing sync records in other deployments receive nullable mapping columns; prior candidate IDs are not fabricated or backfilled by resending mandates.
- Deterministic concurrency tests cover persisted re-read, rotation, lock contention and release; simultaneous workers sharing the deployment's cache should still be checked in RECETTE. Production workers must share the selected cache; an array store provides only per-process test locking.
- Cooldown is a basic per-actor/client guard, not global school quota enforcement across separate actors. Eligibility is queried again at assignment; its provider result can still change immediately before the subsequent provider mutation.
- Dual token tables require manual reconciliation if encountered. No automatic token merge/deletion is attempted.
- Existing role policy was preserved: the student-mandate route remains under its existing admin/secretary group, while general RdvPermis operations including eligibility retain admin/super-admin/secretary access. Broader role-policy changes are outside this stabilization phase.
- Trainer declarations, cart/exam UI, provider reconciliation/backfill, and official exam model mapping remain subsequent work; they were not implemented here.

## 7. Manual RECETTE Tests Still Required

Use only an approved RECETTE account and disposable test candidates/data.

1. Configure the matching environment URLs, client credentials, registered backend callback and frontend callback explicitly. Confirm all workers share a lock-capable cache.
2. Complete an interactive OAuth connection. Confirm single-use/expired/invalid state rejection and intended session/redirect behavior, then verify known expiry/scopes.
3. Call current-school and employees with the connected RECETTE account. Confirm the expected school and permission boundaries; check denied/revoked credentials and readiness afterward.
4. Run read-only slot eligibility searches for a selected RECETTE slot, with zero-prefixed dossier filters and candidates having eligible/ineligible provider results. Compare the unmodified provider response fields.
5. Use concurrent RECETTE workers on an expiring test credential to confirm one rotating refresh and reuse of the persisted result. Observe safe temporary failure/lock contention behavior.
6. With separately authorized disposable RECETTE mutations, test mandate success for an existing-account candidate without local email, provider email-required behavior for a new account, and distinct mandate/candidate/school mapping. Do not resend production mandates merely to backfill IDs.
7. With separately authorized disposable RECETTE mutations, confirm eligible assignment, blocked ineligible assignment, null candidate removal and provider rejection if eligibility changes between search and assignment.
8. Review provider-issued retry information during ordinary authorized traffic; do not induce 429 with load. Rate-limit cases were already covered with mocks.

## 8. Phase 2 Readiness

The audited backend blockers in this phase are addressed and locally verified. Phase 2 can be prepared after the RECETTE connection and contract smoke checks above pass. No React RdvPermis dashboard, planning/cart/countdown/exam/permutation/replacement UI, trainer workflow or reconciliation system was started. Local test success is not a live RECETTE sign-off.

## 9. Security Notes

The audit found an RdvPermis secret inside `C:/wamp64/www/Passpermislaravel/Passpermislarave22.zip`. The file remains in place and is now ignored, along with similar root backups. The frontend `MaleeqPermier.zip` backup is also ignored. Neither archive was deleted or modified. No credentials were rotated.

The backend `.env.example` no longer contains the real application key, Stripe secret, Bugsnag credential, actual RdvPermis client ID or actual Stripe public key. Secrets remain in deployment configuration; the real `.env` was not edited, and its hash was verified unchanged around the local migrations. No real token or credential value appears in this report.

Removing values from the template or adding ignore rules does not purge earlier Git history or existing archive copies. The credential owner should review exposure and plan any necessary rotation. APP_KEY rotation must account for existing encrypted access/refresh credentials; it must not be performed blindly.

## 10. Exact next recommended step

Perform a controlled RECETTE OAuth connection with the deployment's shared cache, then verify `GET /api/rdvpermis/current-school` and the new read-only slot eligibility search. Record that sign-off before requesting Phase 2 implementation. Keep all real provider mutations out of this smoke check.

| Item | Before | After | Verified By |
|---|---|---|---|
| Sync table | Model inferred a missing table | Explicit historical table | Real-schema sync/mandate tests; local MySQL query |
| Token table | Fresh and deployed names disagreed | Canonical table plus lossless compatibility migration | Fresh/legacy/dual-table tests; unchanged local ciphertext |
| NEPH | BIGINT, import cast and twelve-digit UI constraints | String storage/validation and unchanged form input | 34 migration-focused tests; 2,006 preserved local records; React/Vue checks |
| Mandate mapping | Only mandate ID retained | Separate mandate/candidate/school IDs, group/context/timestamps | Actual-migration mandate tests and incomplete-response regression |
| Email | Always required locally | Optional locally; provider decides account-specific need | Missing/invalid email and EMAIL_MANQUANT tests |
| Eligibility | No separate slot search/gate | Current slot-specific operation and assignment confirmation | Eligibility contract/role/validation/gating tests |
| Refresh | Concurrent stale callers could rotate twice | Lock, persisted re-read, single rotation/reuse | Real-schema stale-reader/contention/release tests; local shared-cache smoke check |
| Unknown expiry | Trusted indefinitely | Unverified; reconnect before use; no endless refresh | Readiness and missing-expiry tests |
| OAuth middleware | Callback queried competencies | Narrow Inertia sharing bypass with state/session/auth retained | Callback tests with empty DB and middleware enabled |
| Errors / 429 | Raw internal errors and misleading refresh 401s; no retry deadline | Safe classification, confirmed business mappings, Retry-After/cooldown | Error and temporary refresh tests; zero live load |
| Configuration/security | Unused path, template credentials and unignored backups | Explicit environment strategy, sanitized template and archive ignores | Configuration/template/ignore checks; unchanged real .env |
| Phase scope | Backend blockers prevented safe UI work | Stabilization complete locally; RECETTE sign-off pending | 201 passing tests; Phase 2 not started |
