# RdvPermis Documentation vs PassPermisFacile Gap Analysis

**Audit date:** 21 October 2026  
**Mode:** read-only source/documentation audit. The only file written for this request is this report. No Laravel/React code, database record, migration, `.env`, staging2, production, provider data, or RdvPermis endpoint was changed or called.

## Evidence terms used throughout

| Term | Meaning in this report |
| --- | --- |
| **IMPLEMENTED** | The current local source has the relevant route/service/UI. |
| **AUTOMATED TESTED** | A local mocked/unit/browser test asserts the behaviour. This is not a provider call. |
| **LIVE VERIFIED** | A real provider response can be independently attributed to the intended environment and action. |
| **PRODUCTION READY** | Code, live acceptance, current provider contract/data, target configuration and release gates are all evidenced. |

The official document is a 74-page historical functional/technical PDF. Its revision history ends on 14 September 2023 and it documents `/api/v1` examples. The code under review encodes many `/api/v2` paths. Therefore a literal v1-to-v2 string comparison is intentionally not used as a gap test.

Labels in every matrix:

- **A - Official business requirement:** what the PDF requires functionally.
- **B - Legacy PDF v1:** a historical route/example only.
- **C - Current project v2:** source implementation. It is marked **NEEDS CURRENT V2 SWAGGER CONFIRMATION** when no current versioned OpenAPI operation/schema was available in the supplied material.

## 1. Executive Summary

The core Permis-B auto-école booking chain is present in the current projects: staff OAuth connection, school/employees read, centre/planning lookup, canonical provider slot ID selection, local-student mandate submission, basket lifecycle, candidate eligibility/assignment, validation including HTTP 207 results, exam list/detail/cancellation/permutation/replacement UI, and mocked regression coverage.

The important boundary is evidence, not route count. The 74-page PDF is not a current v2 contract; it cannot establish the exact v2 body/schema/limits. Local source and tests establish implementation behaviour only. This audit did **not** obtain a fresh authenticated current v2 Swagger export and did **not** make a provider request. Consequently no mutation (mandate, basket, assignment, validation, cancellation, permutation or replacement) is classed as live successful.

The reported mandate response, HTTP 400 `CANDIDAT_INCONNU`, is consistent with a provider business rejection after a request reached RdvPermis. It is not a successful mandate and does not by itself prove OAuth works today. The supplied candidate/demande snapshot is dated 14 August 2025; it contains both named candidates and non-deleted B demandes in that old snapshot, but it cannot prove they exist or remain eligible in current RECETTE1.

### Headline result

| Measure | Evidence-based result |
| --- | --- |
| Official requirements consolidated into the master matrix | **42** |
| Local-complete requirements | **4** |
| Implemented, automated-tested, live test pending | **20** |
| Partial requirements | **8** |
| Truly missing capabilities | **6** |
| Version/contract confirmation required | **4** |
| Fresh focused Laravel tests | **209 tests, 1,039 assertions, pass** |
| Fresh frontend RdvPermis tests | **32 tests, pass** |
| Independently verified successful provider mutations in this audit | **0** |
| Safe code work that can be specified without current v2 documentation | **None identified** |

## 2. Official RdvPermis Requirements

### 2.1 Functional requirements found in the complete PDF

| PDF pages | A - Official business requirement | Business purpose | B - legacy v1 reference | Required for this auto-école booking scope? |
| --- | --- | --- | --- | --- |
| 7-14 | AE selects a centre/date/slot and nominatively reserves an exam for a mandated candidate. | Candidate-centred booking. | Workflow diagrams | Yes |
| 10-11, 20-22 | Mandate requires surname, NEPH and permit category; account/email conditions vary; candidate can revoke a mandate. | Authorise AE action for the candidate. | `POST /api/v1/auto-ecole/mandats` | Yes for taking a mandate; revocation is candidate-side/provider-owned. |
| 15, 17-19 | Declare trainers and consult departmental threshold; territorial priority and quota rules apply. | Equitable access to published exam capacity. | `POST .../nombre-de-formateurs`, `GET .../informations-seuil-departement` | Needs current scope/contract decision. |
| 15, 23-24 | Search planning, distinguish slot state, create an expiring basket, add slots, select eligible candidates, assign and validate. | Reserve only permitted, nominated places. | planning/paniers/candidats routes | Yes |
| 12, 24 | Basket is provider-owned, expires after 30 minutes, and has provider limits/categories/rules. | Temporary hold rather than a booking. | basket API | Yes |
| 15-16, 25-27 | List/detail/cancel exams; permutation/replacement with provider restrictions; respect presentation and failure delays. | Manage booked exams safely. | exams routes | Yes |
| 28 | Provider emails/notifications accompany account, mandate and exam events; partners cannot normally inspect those messages. | Inform candidates/AE. | N/A | Provider side effect; app must not claim delivery. |
| 31-32 | RECETTE1 contains synthetic data and historically resets; datasets describe candidates, demandes, mandates, staff, centres and slots. | Repeatable non-production testing. | N/A | Yes for RECETTE acceptance. |
| 34-42 | Swagger/API groups, appropriate OAuth flow, registered callback, backend-only client secret, logout URL. | Secure provider access. | `/api/v1/...` examples | Yes, except logout is optional unless required by current contract. |
| 44-61 | Authorization Code flow for an editor: employee consent, one-time code, access/refresh token persistence, `offline_access`, reconnection and least privilege. | Act for an AE user securely. | OIDC/OAuth2 examples | Yes for this editor architecture. |
| 62-69 | Client Credentials is described for the separate online-auto-école profile. | Alternative machine-to-machine model. | OIDC/OAuth2 examples | No, unless Ministry confirms this project is registered for that profile. |
| 70-72 | Historical production URLs and HTTP 429 quotas, including planning/slot limits. | Prevent provider overload and prepare production. | `/api/v1`, rate-limit table | Current values need confirmation. |
| 29-30, 74 | RdvPermis and Livret Numérique share platform concepts/accounts but API groups are separate. | Do not confuse local training features with LN API synchronisation. | LN group | LN integration is not required for the AE booking chain unless contractually promised. |

### 2.2 Important historical-document limits

- PDF p.12 says a basket cannot contain more than 12 *créneaux*; p.24 says a basket can contain 36 *places d'examen*. The document does not provide a current v2 schema that resolves whether these are separate units/rules. The application must remain provider-authoritative until confirmed.
- PDF p.19 describes a transitional trainer-count model and a future evolution to individual trainer declarations. It is not safe to infer a current v2 route/body from this historical text.
- PDF p.71 gives historical production v1 base addresses; it is evidence that production onboarding exists, not proof of today’s v2 host/base.
- PDF p.72 documents rate limits but not a current tenant/school quota agreement for this installation.

## 3. Current Architecture

### 3.1 Request path

```text
React /rdvpermis
  -> src/services/rdvPermisApi.js (application API only)
  -> Laravel /api/rdvpermis/* + staff mandate route
  -> Controller validation/error mapping
  -> RdvPermis service + ApiClient
  -> configured /api/v2/auto-ecole/* provider path
```

- React route: `C:\wamp64\www\MaleeqPermiFAst\src\App.jsx:852`; sidebar entry: `src\Components\shared\Sidebar.jsx:132`.
- Frontend API wrapper: `src\services\rdvPermisApi.js`. It calls Laravel only; no government host, client secret, provider access token, or `VITE_RDVPERMIS_*` variable was found in frontend source.
- Laravel mounted routes: `C:\wamp64\www\Passpermislaravel\routes\api.php:98-132`. General operations require Sanctum plus `admin|super-admin|secretary`; public callback protection is the persisted one-time OAuth state.
- Staff-local mandate route: `routes\admin\api.php:153`, implemented by `StudentMandateController` and `StudentMandateService`.
- Current v2 strings are held in `app\Services\RdvPermis\*.php`; `ApiClient` is the common authenticated/timeout/redaction/cooldown boundary.

### 3.2 Architecture conclusion

The source is an **editor Authorization Code** design, matching the PDF’s editor profile (pp.44-61), not the PDF’s separate online-auto-école Client Credentials example (pp.63-69). A Client Credentials rewrite would be an unsupported architecture change.

## 4. Backend Implementation Matrix

Source references below are exact local locations. “v2 confirmation” means current source has the route and mocked test, but a fresh authenticated v2 OpenAPI specification was not supplied/retrieved in this audit.

| Capability | Laravel route / method | Controller -> service / validation | Model/table | Backend | Automated test | Live RECETTE | Remaining action |
| --- | --- | --- | --- | --- | --- | --- | --- |
| OAuth connect | `POST /api/rdvpermis/connect` | `RdvPermisController::connect` -> `OAuthService::authorizationUrl` | `rdvpermis_oauth_states` | YES | Yes | Not independently verified | Confirm registered target callback and current scope grant. |
| OAuth callback | `GET /api/rdvpermis/callback` | `callback` -> `exchangeAuthorizationCode` | states/tokens | YES | Yes | Not independently verified | Same deployment/database must complete both legs. |
| State validation | callback | SHA-256 state, TTL, single-use conditional update | `rdvpermis_oauth_states` | YES | Yes | N/A locally | Operational cleanup/retention policy only. |
| Access-token storage | internal | `TokenService::store` | `rdvpermis_tokens` | YES | Yes | Not independently verified | Verify deployed table/key. |
| Refresh-token storage | internal | `TokenService::store` | `rdvpermis_tokens` | YES | Yes | Not independently verified | Verify actual `offline_access` grant. |
| Token encryption | internal | encrypted Eloquent casts | token table | YES | Yes | N/A locally | Preserve APP_KEY/backups securely. |
| Automatic refresh | provider call preflight | `TokenService::accessToken/refresh` | token table/cache lock | YES | Yes | Not independently verified | Test against current provider token policy. |
| Reconnect-required state | status/API 401 | `markNeedsReauthentication`, `readiness` | token table | YES | Yes | Not independently verified | UI acceptance with expired session. |
| `offline_access` request | OAuth URL/config | `config/rdvpermis.php` -> `OAuthService` | token scopes | PARTIAL | Config/test | Not independently verified | Confirm it is granted and current maximum session rule. |
| Current school | `GET /api/rdvpermis/current-school` | `RdvPermisController::currentSchool` -> `AutoEcoleService` | token | YES | Yes | User-reported read only | Needs current v2 Swagger confirmation/read trace. |
| Employee list | `GET /api/rdvpermis/employees` | `employees` -> `AutoEcoleService` | token | YES | Yes | No independent trace | Obtain current provider evidence if release needs it. |
| Employee creation | None | None | None | NO | No | No | Needs current v2 contract and scope decision. |
| Trainer declaration/count | None | None | None | NO | No | No | Needs current v2 contract; do not implement from p.15/p.19 alone. |
| Department threshold | None | None | None | NO | No | No | Needs current v2 contract/scope decision. |
| Centre search | `POST /centres/recherche` | `CentreController::search` -> `CentreService::search` | token | YES | Yes | User-reported read only | Needs current v2 schema confirmation. |
| Centre detail | `GET /centres/{centreId}` | `show` -> `CentreService::find` | token | YES | Yes | User-reported read only | Needs current v2 schema confirmation. |
| Planning | `POST /planning/recherche` | `PlanningController::search` -> `PlanningService` | token | YES | Yes | User-reported read only | Needs current v2 schema confirmation. |
| Slot normalization | planning result only | frontend canonical ID uses `creneauDuPlanning.id` | none | YES | Yes | Not independently verified | Provider response shape still needs current confirmation. |
| Candidate mandate | `POST /api/admin/students/{student}/rdvpermis/mandate` | `StudentMandateController` -> `StudentMandateService` -> `AutoEcoleService::createMandate` | sync records/mapping | YES | Yes | Reported provider 400 only | Need approved current candidate/demande; no successful mandate evidence. |
| Existing mandate lookup | `POST /candidats-mandats/recherche` | `CandidateMandateController` -> service | token | YES | Yes | No independent trace | Confirm v2 response/pagination. |
| Local/provider ID mapping | internal on mandate success | `RdvPermisSyncService` | `rdvpermis_sync_records` | YES | Yes | No successful mandate | Mapping cannot be proven until success. |
| Candidate eligibility | `POST /candidats/recherche` | request class -> `CandidateEligibilityService` | token | YES | Yes | User-reported response only | Confirm current response/body and eligible candidate. |
| Create basket | `POST /paniers` | `PanierController::store` -> service | provider-owned | YES | Yes | No | Confirm current create response/Location behaviour. |
| List/detail/delete basket | GET/GET/DELETE `/paniers` | `PanierController` -> service | provider-owned | YES | Yes | No | Controlled RECETTE test needed. |
| Add/remove slot | POST/DELETE basket slot routes | `PanierController` -> service | provider-owned | YES | Yes | No | Confirm current limits/idempotency. |
| Candidate assign/remove | PUT basket slot candidate | controller prechecks eligibility -> service | provider-owned | YES | Yes | No | Controlled RECETTE test needed. |
| Basket validation | `POST /paniers/{id}/valider` | `PanierController::validatePanier` -> service | provider-owned | YES | Yes | No | Verify final exam/read-back. |
| HTTP 207/partial | same validation route | provider response forwarded unchanged | provider-owned | YES | Yes | No | Obtain v2 reconciliation/idempotency rules. |
| Exam list/detail | GET exams / `{id}` | `ExamController` -> `ExamService` | token | YES | Yes | User-reported list only | Current response/query confirmation. |
| Exam cancellation | DELETE `/examens/{id}` | `destroy` -> `cancel` | provider-owned | YES | Yes | No | Separate approved mutation/read-back. |
| Permutable lookup | GET `/examens/permutables` | `permutables` -> service | provider-owned | YES | Yes | No | Current query/restrictions confirmation. |
| Permutation | POST `/examens/permutation` | exact `examenId1/examenId2` validation -> service | provider-owned | YES | Yes | No | Separately approved live test. |
| Replacement allowance | GET `/nombre-de-remplacement` | `replacementAllowance` -> service | provider-owned | YES | Yes | No | Current rule/schema confirmation. |
| Candidate replacement | POST `/examens/remplacement` | exact `examenId/candidatRemplacantId` -> service | provider-owned | YES | Yes | No | Need valid replacement candidate/discovery rule. |
| Provider business errors | all provider routes | `ApiClient`, `HandlesRdvPermisErrors` | none | YES | Yes | Not independently verified | Keep current v2 error codes current. |
| HTTP 429 | all provider routes | `ProviderCooldown` / Retry-After | cache | YES | Yes | No live throttling | Confirm provider quota scope; never stress test. |
| Network/TLS failure | all provider routes | timeout + exception mapping | none | YES | Yes | No | No `verify=false`/`withoutVerifying` match found. |
| Logging/redaction | all provider routes | sanitized diagnostics/callback logging | logs | YES | Yes | N/A locally | Remote log access/retention not assessed. |
| Rate-limit awareness | provider client | reactive per-user/channel cooldown | cache | PARTIAL | Yes | No | Not a school-wide quota budget; confirm current policy. |
| Provider emails | indirect provider side effect | no delivery API/local assertion | none | Correctly not simulated | No | Provider acceptance/inbox evidence only. |

**Backend source conclusion:** core booking code is implemented and automated-tested. Missing code is deliberately limited to operations for which no current v2 contract is available: employee creation, trainer declaration/count and department threshold. It would be unsafe to invent v2 routes from the PDF.

## 5. Frontend Implementation Matrix

Primary UI: `C:\wamp64\www\MaleeqPermiFAst\src\rdvpermis\RdvPermisPage.jsx`; API wrapper: `src\services\rdvPermisApi.js`; helper modules: `src\rdvpermis\slotSelection.js` and `validationResult.js`.

| UI requirement | Status | Exact source / evidence | Gap |
| --- | --- | --- | --- |
| Protected route and sidebar entry | COMPLETE | `src/App.jsx:100,157,852`; `src/Components/shared/Sidebar.jsx:132` | Role/session acceptance remains live-app work. |
| Connection status / Connect / Reconnect | COMPLETE | `RdvPermisPage.jsx` connection section and `startConnection` | Actual redirect/callback needs target verification. |
| Current school | COMPLETE | `RdvPermisPage.jsx` school card; API `currentSchool` | Live response not independently captured. |
| Employee list | COMPLETE | employees card; API `employees` | Read-only only; no create UI. |
| Employee creation | MISSING | No UI/API wrapper | Needs current v2 contract and product decision. |
| Centre search | COMPLETE | centre form/card; `searchCentres` | Current provider schema confirmation needed. |
| Centre detail | COMPLETE | `loadCentreDetail`, `JsonDetails` | Current provider schema confirmation needed. |
| Planning search | COMPLETE | `searchPlanning` | Current provider schema confirmation needed. |
| Available/non-available slot semantics | PARTIAL | `slotSelection.js` preserves canonical UUID and UI displays returned slots | It does not clearly explain every provider slot state/restriction before selection. |
| Selected canonical provider UUID | COMPLETE | `getSlotId`, `normalizePlanningSlot`, `eligibilityPayload` | Mocked helper regression passes. |
| Local-student loading/search | COMPLETE | `listAllLocalStudents` pages all results; mandate card | Mocked second-page test passes. |
| Mandate preview | COMPLETE | selected student preview includes name/NEPH/email/B | Provider account/email rule needs current contract. |
| Existing mandate lookup | COMPLETE | `searchMandatesForSelectedStudent` | No live evidence. |
| Create mandate confirmation | COMPLETE | common `ConfirmationDialog`, `createMandate` | Successful provider mandate unproven. |
| Basket list/detail/expiry | COMPLETE | panier card, expiry clock, `isPanierExpired` | Provider lifetime/limits remain authoritative. |
| Create basket | COMPLETE | `createPanier` confirmation workflow | No live creation evidence. |
| Add/remove slot | COMPLETE | `addSlotToPanier`, `removePanierSlot` | No live mutation evidence. |
| Candidate eligibility | COMPLETE | eligibility form/card | Current v2 data shape needs confirmation. |
| Eligible-only candidate selection | COMPLETE | requires literal `estEligible === true` | Mocked unknown/refused tests pass. |
| Candidate assignment/removal | COMPLETE | `assignCandidate`, `unassignPanierCandidate` | No live mutation evidence. |
| Basket validation | COMPLETE | `validatePanier` then exam refresh | No live booking evidence. |
| HTTP 207/partial details | COMPLETE | `validationResult.js`, `PanierValidationResult.jsx` | Current provider reconciliation rule still required. |
| No false booking success | COMPLETE | positive notice only on explicit per-slot `estReserve === true` | Mocked empty/unknown/refused tests pass. |
| Exam list/detail | COMPLETE | exam card, `loadExamDetail` | No independent current provider trace. |
| Cancellation confirmation | COMPLETE | `cancelExam` plus dialog | No live cancellation/read-back. |
| Permutable list/permutation | COMPLETE | `loadExamSupport`, `permuteExam` | No live permutation. |
| Replacement allowance | COMPLETE | `replacementAllowance` display | Schema/rules need current confirmation. |
| Candidate replacement | PARTIAL | `replaceExamCandidate` uses selected eligibility candidate | No dedicated exam-bound replacement-candidate discovery. |
| Loading/error/empty states | COMPLETE | shared `runRead`, error/empty components | Whole SPA UX not assessed. |
| Confirmation / double-submit prevention | COMPLETE | dialog + `mutationInFlight` | Browser workflow test covers permutation double click. |
| Refresh after mutation | COMPLETE | refresh/reset after validation, replace/permutation | Mocked only. |
| Trainer/threshold UI | MISSING | No UI | Needs current v2 contract and scope decision. |
| Candidate mandate revocation UI | NOT REQUIRED FOR AE ACTION / MISSING | PDF makes it candidate-side | Display/reconciliation after revocation is contract dependent. |
| Provider notification UI | NOT REQUIRED | PDF says partner access to emails is unavailable | Do not fabricate delivery status. |

## 6. Automated Test Coverage

### 6.1 Fresh safe runs

| Suite | Command | Result | Isolation |
| --- | --- | --- | --- |
| Laravel RdvPermis focused suite | `php -d xdebug.mode=off vendor/bin/phpunit -c phpunit.rdvpermis.xml --do-not-cache-result --no-progress` | **209 tests, 1,039 assertions, pass** | SQLite `:memory:`, faked provider HTTP, null logging; no application database/provider call. |
| Frontend RdvPermis suite | `npm.cmd run test:rdvpermis` | **32 tests, pass** | Browser workflow tests use mocked API traffic and ephemeral loopback Vite. |

### 6.2 What is covered

| Subject | Backend unit/feature | Frontend helper/browser | Status |
| --- | --- | --- | --- |
| OAuth, state, token storage/encryption/refresh/reconnect | Yes | Connection UI mocked | Automated tested; live pending |
| Mandate payload, NEPH string preservation/errors | Yes | confirmation/refusal/local student paging | Automated tested; live success missing |
| Centres/planning/canonical slot ID | Yes | slot selection helper/component | Automated tested; live pending |
| Basket create/list/detail/delete/add/remove | Yes | mocked booking transitions | Automated tested; live pending |
| Eligibility/assignment/removal | Yes | eligible-only/refusal/removal flows | Automated tested; live pending |
| Validation and HTTP 207 | Yes | full/partial/empty/refused displays | Automated tested; live pending |
| Exam list/detail/cancel | Yes | cancellation confirmation/refusal | Automated tested; live pending |
| Permutation/replacement | Yes | source/target payload, busy, refresh, refusal | Automated tested; live pending |
| HTTP 429/network/TLS/redaction | Yes | UI status/error mappings | Automated tested; no live quota test |
| Provider notification receipt | No possible local proof | No | Provider-dependent |
| Employee create/trainer/threshold | No, because no current operation is implemented | No | Missing/version dependent |

Backend focused files are under `C:\wamp64\www\Passpermislaravel\tests\Feature\RdvPermis*.php` and `tests\Unit\RdvPermis*.php` (20 focused files, 109 named methods expanded by data providers). Frontend files are `C:\wamp64\www\MaleeqPermiFAst\tests\rdvpermis-slotSelection.test.js`, `rdvpermis-validation.test.js`, and `rdvpermis-workflows.test.js`.

## 7. Live RECETTE Verification

This is deliberately separate from code/test evidence. No live request was executed in this audit.

| Operation | Evidence available now | Classification |
| --- | --- | --- |
| OAuth connection | Previously reported successful; no current trace re-run or captured in this audit | USER-REPORTED, not independently LIVE VERIFIED |
| Current school | Previously reported successful read | USER-REPORTED, not independently LIVE VERIFIED |
| Centre search/detail | Previously reported successful reads | USER-REPORTED, not independently LIVE VERIFIED |
| Planning / slots | Previously reported successful read | USER-REPORTED, not independently LIVE VERIFIED |
| Candidate eligibility response | Previously reported response | USER-REPORTED, not independently LIVE VERIFIED |
| Exam list | Previously reported successful read | USER-REPORTED, not independently LIVE VERIFIED |
| Employees read | Source/test exists; no independent live response artifact reviewed | UNVERIFIED |
| Candidate mandate | Reported HTTP 400 `CANDIDAT_INCONNU` | Provider reached/business rejection reported; **not** successful mandate |
| Create basket / add slot / assignment / validation | No success artifact | UNVERIFIED |
| Exam created/read-back | No success artifact | UNVERIFIED |
| Cancellation / permutation / replacement | No success artifact | UNVERIFIED |

### Mandate result classified correctly

| Question | Answer |
| --- | --- |
| Provider reached? | **Yes, if the reported HTTP 400 response is authentic**: it is a provider business response rather than a local validation failure. |
| OAuth working? | **Not conclusively proven today.** A historical authenticated business response suggests authorization worked for that attempt, but not current connection health. |
| Local validation passed? | **Likely yes for the reported request**; otherwise Laravel would reject before the provider. Exact historical input was not replayed. |
| Provider business rejection? | **Yes.** `CANDIDAT_INCONNU` means the provider did not recognise the candidate in that context. |
| Successful mandate? | **No.** |

## 8. Requirement Traceability Matrix

Counts use exactly these 42 consolidated rows. “Complete” means the local requirement is fully implemented/tested; it does not turn a provider mutation into live evidence.

| # | PDF page(s) | A - Official requirement | B - legacy v1 implementation | C - current project v2 / UI | Backend | Frontend | Automated test | Live RECETTE | Status | Remaining action |
| ---: | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | 7-16 | Staff AE booking workflow | workflow | v2 component chain | Yes | Yes | Yes | No | 🟡 IMPLEMENTED - LIVE TEST PENDING | Controlled end-to-end acceptance. |
| 2 | 20-21 | Take mandate | `POST /mandats` | v2 mandate service | Yes | Yes | Yes | 400 only | 🟡 | Current eligible candidate/demande. |
| 3 | 22 | Candidate revokes mandate | candidate portal | none | No | No | No | No | 🟠 PARTIAL | Confirm whether AE needs reconciliation/notification read. |
| 4 | 20-21 | Account/email mandate rule | mandate body | optional email | Yes | Yes | Yes | No | 🟠 | Current account/email schema. |
| 5 | 15 | Search mandated candidate | `POST /candidats/recherche` | v2 candidates-mandats search | Yes | Yes | Yes | No | 🟡 | Current v2 confirmation. |
| 6 | 20 | Preserve candidate/provider identity | implicit | sync mapping after success | Yes | Indirect | Yes | No | 🟡 | Successful mandate. |
| 7 | 15,19 | Declare trainer count | `POST /nombre-de-formateurs` | none | No | No | No | No | 🔴 MISSING | Current v2 operation and scope decision. |
| 8 | 15,17 | Department threshold | `GET /informations-seuil-departement` | none | No | No | No | No | 🔴 MISSING | Current v2 operation and scope decision. |
| 9 | 17-18 | Threshold/territorial priority rule | provider rule | provider-authoritative only | N/A | Partial display | No | No | ⚪ LEGACY / VERSION CONFIRMATION REQUIRED | Current rule/response fields. |
| 10 | 7,15 | Search exam centres | implied/provider APIs | v2 centres search | Yes | Yes | Yes | Reported only | 🟡 | Swagger/read evidence. |
| 11 | 7 | Centre detail | implied | v2 centre detail | Yes | Yes | Yes | Reported only | 🟡 | Swagger/read evidence. |
| 12 | 15,23 | Planning lookup | `POST /planning/recherche` | v2 planning | Yes | Yes | Yes | Reported only | 🟡 | Swagger/read evidence. |
| 13 | 23 | Slot state/UUID | planning result | canonical UUID; state detail partial | Yes | Partial | Yes | No | 🟠 PARTIAL | Current status fields/UI interpretation. |
| 14 | 72 | Dedicated available-slot/first-date availability | `/crenodispo` mentioned | none | No | No | No | No | 🔴 MISSING | Confirm whether needed and v2 contract. |
| 15 | 12,24 | Create/list/detail/delete basket | paniers routes | v2 basket APIs | Yes | Yes | Yes | No | 🟡 | Controlled live chain. |
| 16 | 12,24 | Basket expiry | provider 30 min | display/action guard | Yes | Yes | Yes | No | 🟡 | Current provider policy. |
| 17 | 12,24 | Basket capacity/category constraints | provider limits | provider-authoritative | Partial | Partial | Partial | No | ⚪ | Resolve 12/36/current v2 limits. |
| 18 | 15 | Add/remove basket slot | paniers slots | v2 APIs | Yes | Yes | Yes | No | 🟡 | Controlled live chain. |
| 19 | 24 | Candidate eligibility | candidates search | v2 eligibility | Yes | Yes | Yes | Reported response only | 🟡 | Current schema/live candidate. |
| 20 | 15,24 | Assign/remove candidate | PUT slot candidate | v2 assignment | Yes | Yes | Yes | No | 🟡 | Controlled live chain. |
| 21 | 15,24 | Validate basket / reserve exam | POST validate | v2 validation | Yes | Yes | Yes | No | 🟡 | Confirmed exam read-back. |
| 22 | current provider behaviour | Partial reservation result | no PDF schema | raw HTTP 207 preserved | Yes | Yes | Yes | No | 🟡 | Reconciliation/idempotency contract. |
| 23 | 24 | Do not claim success absent confirmation | provider response | explicit item-only success | Yes | Yes | Yes | No | ✅ COMPLETE | Keep provider response authoritative. |
| 24 | 16,25 | List programmed exams | `GET /examens` | v2 exam list | Yes | Yes | Yes | Reported only | 🟡 | Current schema/read. |
| 25 | 16 | Exam detail | `GET /examens/{id}` | v2 detail | Yes | Yes | Yes | No | 🟡 | Read evidence. |
| 26 | 16,26 | Cancel exam | DELETE exam | v2 cancellation | Yes | Yes | Yes | No | 🟡 | Approved live cancellation/read-back. |
| 27 | 26 | Presentation-delay effects | provider rule | no local reconstruction | Partial | Partial | No | No | 🟠 PARTIAL | Current provider explanation/error contract. |
| 28 | 27 | Failure-delay effects | provider rule | no local reconstruction | Partial | Partial | No | No | 🟠 PARTIAL | Current provider explanation/error contract. |
| 29 | 16,25 | Permutable lookup/permutation | endpoints | v2 path/fields | Yes | Yes | Yes | No | 🟡 | Approved live test. |
| 30 | 25 | Replacement allowance | endpoint | v2 query/display | Yes | Yes | Yes | No | 🟡 | Current rule/schema. |
| 31 | 16,25 | Replace candidate | endpoint | v2 action; selected candidate reuse | Yes | Partial | Yes | No | 🟠 PARTIAL | Exam-bound candidate discovery/eligibility contract. |
| 32 | 28 | Provider notifications | provider email | no false delivery claim | N/A | N/A | N/A | No | ✅ COMPLETE | Provider confirms event/delivery policy. |
| 33 | 46 | Employee list | current not shown | v2 employees read | Yes | Yes | Yes | No | 🟡 | Live/current contract evidence. |
| 34 | 46 | Employee creation | `POST /employes` | none | No | No | No | No | 🔴 MISSING | Current v2 route/scope. |
| 35 | 42,44-56 | Backend-only Authorization Code flow | OIDC auth/token | implemented | Yes | Yes | Yes | No | ✅ COMPLETE | Target registration/live acceptance. |
| 36 | 52-55 | Callback, one-time code, state | OIDC | hashed one-time state | Yes | Indirect | Yes | No | ✅ COMPLETE | Same deployment/database validation. |
| 37 | 45,57-60 | Store/refresh/reconnect tokens | OIDC | encrypted tokens + lock | Yes | Indirect | Yes | No | ✅ COMPLETE | Actual current grant/expiry test. |
| 38 | 45,52,58,61 | offline_access/session limit/least privilege | scopes | requested scopes, no session reminder | Partial | Partial | Yes | No | 🟠 PARTIAL | Confirm granted scopes/maximum session. |
| 39 | 41 | Provider logout | OIDC logout URL | no dedicated provider logout | No | No | No | No | 🔴 MISSING | Confirm requirement before adding. |
| 40 | 72 | Rate limits/429 | historical quotas | retry-after cooldown | Partial | Error UI | Yes | No | 🟠 PARTIAL | Current quotas/school-wide governance. |
| 41 | 31-32 | Current RECETTE datasets/reset | data procedure | old files only | N/A | N/A | N/A | No | ⚪ | Obtain current package/reset policy. |
| 42 | 29-30,74 | Livret Numérique relationship | separate API group | no LN integration | No | No | No | No | 🔴 MISSING (OUT OF SCOPE) | Build only if agreed product scope requires it. |

## 9. Current Mandate Blocker

### Snapshot inspection

| Item | Evidence |
| --- | --- |
| Candidate dataset | `C:\Users\123\Downloads\api passpermis docs\files\candidat.csv` |
| Demande dataset | `...\demande.csv` |
| Mandats dataset | **Not supplied.** It is absent from the directory and from the supplied ZIP archive. |
| Snapshot date | 14 August 2025 (CSV timestamps and ZIP filename/member dates). |
| SIMON LUCIE | Present in the candidate CSV. |
| CLEMENT ZACHARIS | Present in the candidate CSV. |
| Active B demande in that snapshot | Each has a B demande row with empty `dateHeureSuppression`. |

This is **historical snapshot evidence only**. It does not prove either candidate exists in the current monthly-reset RECETTE1 generation, belongs to the connected school context, has the same surname/NEPH representation, can receive the supplied email, or still has an active B demande. Stale/reset data is a strong hypothesis for `CANDIDAT_INCONNU`, not a proven cause. The provider must supply/reconfirm current data.

## 10. Available RdvPermis Resources

No secret values are printed. “Configured” means a non-secret local configuration-presence check found a value; it does not prove it is valid or authorised.

| Resource | Have? | Status | Notes |
| --- | --- | --- | --- |
| RECETTE1 API environment | Yes | CONFIGURED / NEEDS LIVE CONFIRMATION | Code has RECETTE-oriented config and v2 source paths. |
| OAuth environment | Yes | CONFIGURED / NEEDS LIVE CONFIRMATION | Authorization/token variables are present locally. |
| Client ID | Yes | CONFIGURED | Withheld. |
| Client secret | Yes | CONFIGURED | Withheld; backend only. |
| Callback registration | Unknown | NEEDS CONFIRMATION | URL presence is not Ministry registration proof. |
| Requested scopes | Yes | CONFIGURED | Actual granted scopes are unverified. |
| RECETTE account/employee | Unknown | NEEDS CONFIRMATION | No current login evidence produced in this audit. |
| Connected school | Reported | USER-REPORTED | No fresh independent read captured. |
| Official PDF | Yes | AVAILABLE | 74 pages; legacy-v1-era baseline. |
| Candidate/demande dataset | Yes | AVAILABLE BUT STALE | August 2025 snapshot. |
| Auto-école/employee/trainer data | Yes | AVAILABLE BUT SENSITIVE/STALE | Do not expose employee credentials in supplied CSV. |
| Mandats dataset | No | MISSING | Not in directory or archive. |
| Centre data / planning access | Source plus report | NEEDS LIVE CONFIRMATION | No CSV centre/slot dataset supplied. |
| Current authenticated v2 Swagger/OpenAPI export | No current snapshot | MISSING | Required for precise v2 implementation validation. |
| Production credentials | No verified evidence | MISSING / NEEDS CONFIRMATION | Never infer from RECETTE values. |
| Production API/OAuth information | Historical PDF only | NEEDS CONFIRMATION | PDF p.71 is not a current v2 contract. |

## 11. Required From RdvPermis / Ministry

| Requirement | Status | Exact request |
| --- | --- | --- |
| Latest RECETTE1 candidate dataset | NEED | Synchronized export date and reset generation. |
| Latest demandes dataset | NEED | Same generation; exact active B demande status. |
| Approved current Permis-B test candidate | NEED | Disposable candidate, exact surname/NEPH/category, account/email conditions and intended school compatibility. |
| Current authenticated v2 Swagger/OpenAPI | NEED | Version/date, operations, request/response schemas, errors, scopes and rate-limit semantics. |
| Trainer/count v2 docs | NEED | Whether mandatory for this school and exact operation/workflow. |
| Threshold v2 docs | NEED | Exact operation and UI/business expectation. |
| Employee-create v2 docs | NEED | Whether programmatic create is allowed/required. |
| Production API URL | HAVE HISTORICAL REFERENCE / NEED CONFIRMATION | Do not derive current v2 base from the PDF v1 URL. |
| Production auth/token/logout URLs | HAVE HISTORICAL REFERENCE / NEED CONFIRMATION | Require current registered endpoints. |
| Production client ID/secret | NEED | Secure provisioning/rotation process. |
| Production callback registration | NEED | Exact final HTTPS callback accepted by Ministry. |
| Production scopes | NEED CONFIRMATION | Least privilege; confirm whether LN scopes are necessary. |
| IP whitelist / fixed egress | UNKNOWN / NEED CONFIRMATION | If required, provide verified production egress address separately. |
| Production approval/onboarding | NEED | School/employee relationship and acceptance/recette checklist. |
| Certification/homologation requirement | UNKNOWN / NEED CONFIRMATION | Do not invent one; request provider’s official release criteria. |

## 12. Legacy v1 vs Current v2 Differences

| Topic | Legacy PDF | Current project | Audit conclusion |
| --- | --- | --- | --- |
| Operation paths | `/api/v1/auto-ecole/...` examples | many `/api/v2/auto-ecole/...` constants | v2 is not a gap merely because PDF says v1. |
| Core booking operations | explicit v1 list pp.15-16 | v2 services/routes for mandate, planning, baskets, exams | Conceptually aligned; current schemas need Swagger confirmation. |
| Trainer/threshold/employee create | legacy examples documented | no project implementation | True code gaps only if current contract/release scope confirms them. |
| OAuth profile | editor Authorization Code vs online-AE Client Credentials | Authorization Code | Correct architectural choice for this editor project. |
| Production URLs/rates | historical values pp.71-72 | configurable values/reactive cooldown | Current production values/policy must be obtained, not copied. |

## 13. Security Review

| Check | Status | Evidence / boundary |
| --- | --- | --- |
| Client secret backend-only | PASS | No direct provider/secret host match in React source; Laravel config/service owns it. |
| Provider token not returned to frontend | PASS | Token model hides credentials; status response exposes readiness/expiry only. |
| Tokens encrypted at rest | PASS | `RdvPermisToken` encrypted casts; focused tests pass. |
| OAuth state protection | PASS | random, SHA-256 stored, TTL, user bound, single-use conditional consume. |
| Refresh rotation/concurrency | PASS | lock/re-read/rotation tests pass. |
| Authorization header and PII redaction | PASS | `ApiClient` sanitises diagnostics; focused redaction tests pass. |
| TLS verification | PASS (source) | No `withoutVerifying` or `verify => false` match in relevant backend source. No network-security penetration test was performed. |
| Provider errors exposed safely | PASS | sanitized local messages; raw remote body not returned. |
| Least privilege/current scopes | PARTIAL | Requested scopes exist; actual granted scope set and need for LN scopes are unverified. |
| Aggregate provider quota control | PARTIAL | Cooldown is reactive/per-user/channel, not known school-wide quota budgeting. |
| Logout/disconnect | PARTIAL | Local app logout exists; no dedicated provider logout/disconnect operation. Confirm requirement. |
| Token store environment isolation | PARTIAL | Tokens are per local user; separate RECETTE/production deployments/stores are an operational requirement. |

## 14. Database & Migration Review

The following local `php artisan migrate:status` inspection was read-only. No migration was run.

| Item | Source/schema evidence | Local migration ledger | Target environment |
| --- | --- | --- | --- |
| `rdvpermis_tokens` | `2026_08_22_000000_create_rdvpermis_tokens_table.php`; encrypted model | Ran, batch 21 | Unknown |
| `rdvpermis_sync_records` | `2026_08_22_000001_create_rdvpermis_sync_records_table.php` | Ran, batch 21 | Unknown |
| `rdvpermis_oauth_states` | `2026_09_24_000000_create_rdvpermis_oauth_states_table.php` | Ran, batch 29 | Unknown |
| canonical token-table repair | `2026_10_07_000001_normalize_rdvpermis_token_table.php` | Ran, batch 37 | Unknown |
| `students.neph` text preservation | `2026_10_07_000002_store_neph_as_text.php` | Ran, batch 37 | Unknown |
| candidate mapping columns | `2026_10_07_000003_add_rdvpermis_candidate_mapping.php` | Ran, batch 37 | Unknown |

The migration source deliberately refuses an ambiguous dual old/new token table rather than deleting encrypted credentials. This is good defensive behaviour. It does **not** prove a staging/production ledger or schema: target environments must be inspected separately before deployment. The local ledger also contains unrelated pending migrations; those are a general release risk and must not be blindly run as part of RdvPermis work.

## 15. External / Unrelated Issues

| Issue | Effect on RdvPermis | Effect elsewhere | Release implication |
| --- | --- | --- | --- |
| Whole frontend lint inventory | The focused RdvPermis paths are clean, but whole-project lint remains **468 errors / 26 warnings** from the latest scoped work inventory. | General SPA/CI quality issue. | Does not block the isolated mocked RdvPermis module; can block a policy requiring global clean CI. Detailed list: `C:\wamp64\www\MaleeqPermiFAst\docs\RDVPERMIS_UNRELATED_LINT_REPORT.md`. |
| Reverb configuration using loopback in local-style frontend configuration | Does not explain `CANDIDAT_INCONNU` or directly affect HTTPS RdvPermis calls. | May affect chat/realtime on a deployed bundle if configuration is wrong. | Verify published bundle/proxy/WSS separately. |
| Official Livret Numérique sync | Separate from booking module. | Local training/booklet features are not LN API sync. | Scope/product decision, not a concealed RdvPermis defect. |

## 16. Remaining Coding Work

### No safe unblocked code task

There is no additional confirmed code change that should be made from the legacy PDF alone. The local action-level fixes and tests are complete.

### Contract-dependent coding, only after current v2 confirmation

1. Employee creation, if provider confirms it is required and supplies current schema/authorisation.
2. Trainer declaration/count and departmental threshold, if mandatory for the school and supported by current v2.
3. Dedicated availability/first-date or richer provider slot-state UI, if current contract exposes/needs it.
4. Dedicated replacement-candidate discovery for a selected exam, if a generic currently selected eligible candidate is insufficient under v2.
5. Provider-to-local exam reconciliation/Livret Numérique synchronization only if those are an agreed product requirement.

## 17. Remaining Testing Work

1. Approved RECETTE OAuth/callback in the same deployment/database.
2. Successful mandate with a Ministry-approved current B candidate, followed by mandated-candidate lookup.
3. One controlled basket chain: create -> add slot -> eligibility -> assign -> validate -> exact exam read-back.
4. One controlled partial/207 reconciliation scenario only if provider documentation/test data supports it; never blindly retry.
5. Separately approved cancellation, permutation and replacement, each with final read-back and provider notification expectation.
6. Validate live 401 refresh/reconnect, granted scopes and 429 `Retry-After` behaviour without quota stress.
7. Target deployment config/cache/shared token-store/callback registration and role navigation acceptance.

## 18. External Dependencies

- Current authenticated/versioned v2 OpenAPI/Swagger export.
- Current synchronized RECETTE candidate/demande package plus a named approved B candidate.
- Provider confirmation of current reset policy, quota, basket capacity/category rule and partial-result reconciliation.
- Confirmation of trainer/threshold/employee operations and replacement-candidate rule.
- Production onboarding: approved school/account, credentials, URLs, registered callback, scopes, conditional network/egress requirements and any acceptance process.

## 19. Completion Percentages

These are planning estimates, not line coverage, provider certification or production sign-off.

| Measure | Percentage | Calculation / reason |
| --- | ---: | --- |
| Official functional requirements implemented | **67%** | 42-row matrix: 4 local complete + 20 implemented + half credit for 8 partial rows; missing/version rows receive no implementation credit. |
| Backend code completion, B-first booking scope | **~92%** | Core workflow and safety/error handling exist; current-contract operations are absent. |
| Frontend code completion, B-first booking scope | **~88%** | Main workflow/UI exists; slot-state explanation and dedicated exam-bound replacement discovery are partial; missing optional/provider operations remain. |
| Automated test completion, B-first scope | **~90%** | 209 Laravel and 32 frontend mocked tests cover requested chain/error cases, not every current provider schema/live state. |
| RECETTE read-only verification | **0% independently verified in this audit; ~29% user-reported checkpoint coverage** | Eight prior read checkpoints were reported, but not re-run/captured here. |
| RECETTE mutation verification | **0%** | No successful provider mutation evidence. |
| Overall RdvPermis development completion | **~70%** | Conservative full-PDF alignment measure; do not substitute the narrower ~95% completed local B-first fix pass. |
| Production readiness | **~35%** | Current v2 contract/data, successful live chain, production onboarding/configuration and global release gates remain open. |

## 20. Critical Path to 100%

1. Obtain the current authenticated v2 OpenAPI export and latest RECETTE data/reset policy.
2. Obtain one approved current B candidate with exact current identity/NEPH/demande/account conditions.
3. Verify OAuth connect/callback from one intended deployment/database and read back current school/scopes.
4. Run one explicitly approved, disposable RECETTE mandate and confirm it by mandated-candidate lookup.
5. Run one controlled booking chain and reconcile all response items before retrying anything.
6. Verify exam read-back, then separately test cancellation, permutation and replacement with provider-approved data.
7. Resolve only the v2 operations confirmed as required (trainer/threshold/employee/discovery).
8. Complete production onboarding, current endpoints/credentials/callback/scopes/network requirements and release acceptance.

## 21. Final Conclusion

The project has a substantial, tested local RdvPermis implementation. It is **not** production-ready yet because code/test success is not the same as a current provider contract, valid current test data, successful live mutation chain or production onboarding.

The biggest immediate blocker is the absence of a current approved RECETTE candidate/demande set together with a current authenticated v2 contract. The observed `CANDIDAT_INCONNU` is a provider business rejection, not evidence that mandate code needs a speculative rewrite. Do not add v1 routes, call real mutations, or mark booking/exam operations successful until the controlled RECETTE path has been approved and evidenced.
