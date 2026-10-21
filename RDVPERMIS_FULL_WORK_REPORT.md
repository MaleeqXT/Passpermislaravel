# PassPermisFacile – RdvPermis Full Work Report

Audit date: 20 October 2026. Scope: current local Laravel and React working copies, the supplied 74-page RdvPermis PDF, available dataset metadata, existing test artifacts, and safe local checks.

Backend: `C:\wamp64\www\Passpermislaravel`  
Frontend: `C:\wamp64\www\MaleeqPermiFAst`

Initial audit: read-only implementation/evidence review on 20 October 2026, not production certification. Existing modified/untracked work was preserved.

Scoped code-fix follow-up: 21 October 2026. The user authorized only RdvPermis fixes/tests, local production build and affected report updates; unrelated lint fixes were explicitly excluded. Affected sections below reflect this pass. No .env file, application database data, staging2/production deployment or real provider data was changed. Backend fixtures ran only in isolated in-memory SQLite; browser API traffic was fully mocked. Local dist output was regenerated, not deployed. Unchanged live/environment/schema observations remain the initial audit snapshot, not new remote verification.

Evidence vocabulary:

- **IMPLEMENTED:** the inspected source contains a route, handler, service and/or interface for the capability. This alone does not establish correct live behavior.
- **AUTOMATED TESTED:** a mocked/unit test or saved test artifact supports the stated behavior; date and rerun status are distinguished below.
- **LIVE READ-ONLY VERIFIED:** a real provider response is independently attributable to the intended environment. None was freshly established in this audit.
- **LIVE MUTATION VERIFIED:** a real, successful provider-changing operation and its resulting state are evidenced. None was established in this audit.
- **USER-REPORTED LIVE:** supplied in the current brief, consistent with source, but not independently re-executed or proven by a trustworthy local live trace.
- **COMPLETE** in module tables means implementation complete for the inspected feature, not a live/deployment sign-off. **PARTIAL**, **NOT IMPLEMENTED**, and **NEEDS LIVE TEST** identify the remaining boundaries.

## 1. Executive Summary

The project is a substantial working application, not an empty shell. Student/monitor administration, training, evaluation, progression, bilans, planning, documents, local exam information, chat, and a sizeable RdvPermis backend exist. Recent calendar/PDF/readiness/maneuver work is also present, partly as uncommitted or untracked local work.

The main RdvPermis category-B chain is implemented: staff OAuth -> school -> local-student mandate -> centres -> planning -> basket -> slot -> eligible candidate -> assignment -> validation -> provider exam reads. The React RdvPermis dashboard now exists and is mounted at `/rdvpermis`; older notes saying no such UI exists are obsolete.

The authorized local fix pass is now complete: permutation, validation/207 result preservation and confirmed-only messages, strict candidate/assignment state, expiry/reset safeguards, full local student paging and the requested mocked UI action tests. No additional identified fix remains inside that enumerated scope. Further provider-specific changes must follow current v2 documentation/acceptance; no unsupported route was invented. Trainer-count, department-threshold and employee-creation operations are absent and require a current contract/business-scope decision, not guessed v1-to-v2 substitutions. Official Livret Numérique synchronization is not implemented.

The reported mandate failure is HTTP 400 `CANDIDAT_INCONNU` (candidate unknown). That points to provider business validation for those attempts, not simply a missing React/Laravel route. The supplied dataset archive is dated **14 August 2025**; a stale or mismatched RECETTE dataset is a strong hypothesis, not a conclusively proven root cause. Current synchronized candidate/demande data and an approved, currently valid category-B candidate are required.

There is also a distinct **current local connection problem**: the inspected local database has one token row, but no future access expiry and no refresh token. Its stored `status=connected` is not proof of a usable current connection; source readiness logic would require reconnection. This does not determine the state of the separately hosted staging connection reported by the user.

Verification highlights:

| Check | Result | Evidence boundary |
| --- | --- | --- |
| Saved focused backend suite, 7 October 2026 | 201 tests / 1,006 assertions / 0 failures, errors or skips | Historical JUnit artifact; provider HTTP mocked; not a new full-suite run |
| Fresh full focused backend suite | **209 tests / 1,039 assertions, all passed** | Faked HTTP, in-memory SQLite only; no application database touched |
| Relevant PHP syntax | 46 files, 0 failures | Syntax only |
| Current React RdvPermis tests | **32/32 passed** | 14 helper/result checks + 18 real-component mocked browser workflows |
| Additional local student-exam helper tests | 4 tests passed | Local exam presentation, not provider exam creation |
| Focused RdvPermis frontend lint | **0 errors / 0 warnings** | Original three errors fixed without weakening lint rules |
| Whole frontend lint | **468 unrelated errors / 26 warnings** remain | 184 files with errors; 185 with errors and/or warnings; unrelated source left unchanged |
| Requested production build | **Passed; 2,382 modules transformed** | Local dist generated only; not deployed; existing large-chunk warning |
| Newly verified successful live RECETTE workflows | None | No provider business workflow invoked in this audit |

Decision: scoped local code fixes/checks are complete; obtain current provider data/contract, verify the intended target connection and run separately authorized controlled RECETTE acceptance before a production decision. Whole-project lint/CI and broader application issues remain outside this pass.

## 2. Full Project Work Completed

The inventory below traces the principal frontend/backend boundaries. Modules not executed end-to-end retain a live-test qualification. It does not certify every legacy controller or copied screen in these large repositories.

| Module | Status and actual implemented work | Main backend evidence | Main frontend evidence / remaining verification |
| --- | --- | --- | --- |
| Authentication | COMPLETE implementation; NEEDS LIVE TEST. Login issues Sanctum token; authenticated user/logout and password/account flows exist. | [AuthLogController](C:/wamp64/www/Passpermislaravel/app/Http/Controllers/V1/EndPoint/System/User/AuthLogController.php), [API routes](C:/wamp64/www/Passpermislaravel/routes/api.php) | [App](C:/wamp64/www/MaleeqPermiFAst/src/App.jsx), [shared HTTP](C:/wamp64/www/MaleeqPermiFAst/src/helpers/http.jsx). Full session/expiry/role acceptance not rerun. |
| Roles / permissions | COMPLETE foundation; PARTIAL release assurance. Sanctum + Spatie guards; admin/super-admin/secretary RdvPermis boundary exists. | [bootstrap middleware](C:/wamp64/www/Passpermislaravel/bootstrap/app.php), [role routes](C:/wamp64/www/Passpermislaravel/routes/admin/api.php) | [App role normalization/access rules](C:/wamp64/www/MaleeqPermiFAst/src/App.jsx). Super-admin normalizes to admin in the SPA. Verify role and agency isolation, not just menu visibility. |
| Admin / agencies | COMPLETE implementation breadth; NEEDS LIVE TEST. Dashboard, schools, user management, offers, orders, approvals, vehicles and settings areas exist. | [admin routes](C:/wamp64/www/Passpermislaravel/routes/admin/api.php), [RouteServiceProvider](C:/wamp64/www/Passpermislaravel/app/Providers/RouteServiceProvider.php) | [ActiveAgency](C:/wamp64/www/MaleeqPermiFAst/src/Components/activeagency/ActiveAgency.jsx), [Sidebar](C:/wamp64/www/MaleeqPermiFAst/src/Components/shared/Sidebar.jsx). Some general destinations remain placeholders. |
| Student management | COMPLETE CRUD/profile implementation; NEEDS LIVE TEST. Profiles, active/inactive lists, zones, documents, contracts and balances are wired. | [StudentUserController](C:/wamp64/www/Passpermislaravel/app/Http/Controllers/V1/Inertia/Student/User/StudentUserController.php), [student model](C:/wamp64/www/Passpermislaravel/app/Models/Roles/Student/User/Student.php) | [Candidates](C:/wamp64/www/MaleeqPermiFAst/src/Components/candidates/Candidates.jsx), [candidate progression](C:/wamp64/www/MaleeqPermiFAst/src/new-student-dashboard/CandidateProgressPage.jsx) |
| Monitor management | COMPLETE implementation; NEEDS LIVE TEST. Monitor profiles, planning, invoices and professional documents have routes/UI. | [monitor routes](C:/wamp64/www/Passpermislaravel/routes/monitor/api.php), [admin routes](C:/wamp64/www/Passpermislaravel/routes/admin/api.php) | [MonitorDashboard](C:/wamp64/www/MaleeqPermiFAst/src/monitordashboard/MonitorDashboard.jsx), [VehiclesAndDocumentsPage](C:/wamp64/www/MaleeqPermiFAst/src/monitordashboard/VehiclesAndDocumentsPage.jsx) |
| Secretariat | PARTIAL. Shared staff/admin API access and a secretary landing area exist; separate legacy secretary API file is not mounted by RouteServiceProvider. This does not mean all secretary functionality is absent. | [SecretaryController](C:/wamp64/www/Passpermislaravel/app/Http/Controllers/SecretaryController.php), [RouteServiceProvider](C:/wamp64/www/Passpermislaravel/app/Providers/RouteServiceProvider.php), [legacy secretary API](C:/wamp64/www/Passpermislaravel/routes/secretary/api.php) | [App shared staff routes](C:/wamp64/www/MaleeqPermiFAst/src/App.jsx), [SecretaryDashboardPage](C:/wamp64/www/MaleeqPermiFAst/src/Components/secretaries/SecretaryDashboardPage.jsx). Verify current shared routes, exports/preview permissions and intended secretary-only behavior. |
| Formation / lessons | COMPLETE core implementation; NEEDS LIVE TEST. Create/update/delete lessons, signed/ended/cancelled guards, monitor/student access and minute-based debit/refund exist. | [FormationController](C:/wamp64/www/Passpermislaravel/app/Http/Controllers/V1/EndPoint/Monitor/FormationController.php), [StudentFormationController](C:/wamp64/www/Passpermislaravel/app/Http/Controllers/V1/EndPoint/Student/StudentFormationController.php) | [FormationPage](C:/wamp64/www/MaleeqPermiFAst/src/monitordashboard/formation/FormationPage.jsx), [StudentFormationPage](C:/wamp64/www/MaleeqPermiFAst/src/new-student-dashboard/StudentFormationPage.jsx). Related tests exist; full current suite not rerun. |
| Initial evaluation | COMPLETE implementation; NEEDS LIVE TEST. Fiche, question answers and contract retrieval persist through monitor formation routes; signed lessons restrict edits. | [FormationController](C:/wamp64/www/Passpermislaravel/app/Http/Controllers/V1/EndPoint/Monitor/FormationController.php), [DocumentEvaluation](C:/wamp64/www/Passpermislaravel/app/Models/DocumentEvaluation.php) | [FormationInitialEvaluationFiche](C:/wamp64/www/MaleeqPermiFAst/src/monitordashboard/formation/FormationInitialEvaluationFiche.jsx), [FormationInitialEvaluationQuestions](C:/wamp64/www/MaleeqPermiFAst/src/monitordashboard/formation/FormationInitialEvaluationQuestions.jsx) |
| Progression / competencies | COMPLETE implementation; NEEDS LIVE TEST. Student history and C1-C4 levels read persisted ratings; monitor-side evaluations and student comments exist. | [StudentProgressController](C:/wamp64/www/Passpermislaravel/app/Http/Controllers/V1/EndPoint/Student/StudentProgressController.php), [Monitor StudentProgressController](C:/wamp64/www/Passpermislaravel/app/Http/Controllers/V1/EndPoint/Monitor/StudentProgressController.php) | [CandidateProgressPage](C:/wamp64/www/MaleeqPermiFAst/src/new-student-dashboard/CandidateProgressPage.jsx), [MonitorCompetence](C:/wamp64/www/MaleeqPermiFAst/src/monitordashboard/MonitorCompetence.jsx). Verify multiple-monitor/latest-rating and signed lesson behavior. |
| Bilans | COMPLETE implementation; NEEDS LIVE TEST. Monitor-scoped students, evaluation/hour persistence and student read view exist. | [BilanController](C:/wamp64/www/Passpermislaravel/app/Http/Controllers/V1/EndPoint/Bilan/BilanController.php), [StudentBilan](C:/wamp64/www/Passpermislaravel/app/Models/StudentBilan.php) | [BilansPage](C:/wamp64/www/MaleeqPermiFAst/src/monitordashboard/BilansPage.jsx), [CandidateBilansPage](C:/wamp64/www/MaleeqPermiFAst/src/new-student-dashboard/CandidateBilansPage.jsx) |
| Questions / fiche élève | COMPLETE implementation; NEEDS LIVE TEST. Local pedagogical questions, evaluation and candidate profile data exist. This is not official LN synchronization. | [FormationController](C:/wamp64/www/Passpermislaravel/app/Http/Controllers/V1/EndPoint/Monitor/FormationController.php) | [evaluation questions](C:/wamp64/www/MaleeqPermiFAst/src/monitordashboard/formation/FormationInitialEvaluationQuestions.jsx), [CandidateProflileDrawer](C:/wamp64/www/MaleeqPermiFAst/src/monitordashboard/CandidateProflileDrawer.jsx) |
| Schéma / signatures | COMPLETE implementation; NEEDS LIVE TEST. Lesson-linked drawing data and signed read-only restrictions exist for monitor/student paths. | [FormationController](C:/wamp64/www/Passpermislaravel/app/Http/Controllers/V1/EndPoint/Monitor/FormationController.php), [StudentFormationController](C:/wamp64/www/Passpermislaravel/app/Http/Controllers/V1/EndPoint/Student/StudentFormationController.php) | [FormationSchemaEditor](C:/wamp64/www/MaleeqPermiFAst/src/monitordashboard/formation/FormationSchemaEditor.jsx), [FormationPage](C:/wamp64/www/MaleeqPermiFAst/src/monitordashboard/formation/FormationPage.jsx) |
| Planning / sessions | COMPLETE core implementation; NEEDS LIVE TEST. Schedule/event reads, availability, reservations/proposals and cancellation paths exist. | [monitor routes](C:/wamp64/www/Passpermislaravel/routes/monitor/api.php), [student routes](C:/wamp64/www/Passpermislaravel/routes/student/api.php) | [MonitorSessions](C:/wamp64/www/MaleeqPermiFAst/src/monitordashboard/MonitorSessions.jsx), [staff Sessions](C:/wamp64/www/MaleeqPermiFAst/src/sessions/Sessions.jsx). Local lessons are not government exams. |
| Calendar / ICS sync | COMPLETE one-way subscription/export implementation; NEEDS LIVE TEST. Paris-time events, cancellation status, stable IDs, encrypted subscription token/hash, revoke/regenerate exist. Not bidirectional Google/Outlook editing. | [calendar controller](C:/wamp64/www/Passpermislaravel/app/Http/Controllers/V1/EndPoint/Monitor/Reservation/MonitorPlanningCalendarController.php), [calendar service](C:/wamp64/www/Passpermislaravel/app/Services/Monitor/Planning/MonitorPlanningCalendarService.php), [subscription service](C:/wamp64/www/Passpermislaravel/app/Services/Monitor/Planning/MonitorCalendarSubscriptionService.php) | [MonitorSessions export/subscription UI](C:/wamp64/www/MaleeqPermiFAst/src/monitordashboard/MonitorSessions.jsx). Tests exist in [MonitorPlanningCalendarExportTest](C:/wamp64/www/Passpermislaravel/tests/Feature/MonitorPlanningCalendarExportTest.php); real subscriber refresh not run. |
| PDF exports / local booklet | COMPLETE code implementation; NEEDS LIVE TEST. Planning PDF, local training booklet, contract/evaluation exports exist. | [planning PDF controller](C:/wamp64/www/Passpermislaravel/app/Http/Controllers/V1/EndPoint/Monitor/Reservation/MonitorPlanningPdfController.php), [planning PDF service](C:/wamp64/www/Passpermislaravel/app/Services/Monitor/Planning/MonitorPlanningPdfService.php), [booklet service](C:/wamp64/www/Passpermislaravel/app/Services/Monitor/Training/StudentTrainingBookletPdfService.php) | [MonitorSessions](C:/wamp64/www/MaleeqPermiFAst/src/monitordashboard/MonitorSessions.jsx), [FormationPage](C:/wamp64/www/MaleeqPermiFAst/src/monitordashboard/formation/FormationPage.jsx). Export tests exist; generated current PDFs were not requested/executed during this audit. |
| Wallet / booking / approvals | PARTIAL. Minute-aware Formation wallets coexist with legacy whole-hour requests/approval logic; exact-duration cancellation parity needs repair/acceptance. | [FormationWalletMinutesService](C:/wamp64/www/Passpermislaravel/app/Services/Student/Training/FormationWalletMinutesService.php), [CancellationService](C:/wamp64/www/Passpermislaravel/app/Services/Student/Training/Cancellation/CancellationService.php), [AdminCancellationController2](C:/wamp64/www/Passpermislaravel/app/Http/Controllers/V1/Inertia/Admin/AdminCancellationController2.php), [HourRequestController](C:/wamp64/www/Passpermislaravel/app/Http/Controllers/V1/Inertia/Student/Infrastructure/Training/HourRequestController.php) | [student reservations state](C:/wamp64/www/MaleeqPermiFAst/src/redux/reducers/reservationSlice.jsx), [MonitorSessions](C:/wamp64/www/MaleeqPermiFAst/src/monitordashboard/MonitorSessions.jsx). Approval casts hour amount to integer and credits hours rather than exact `duration_minutes`; e.g. non-whole-hour lessons need a focused regression. |
| Exam readiness | COMPLETE local implementation; NEEDS LIVE TEST. Monitor readiness state, requested extra hours/frequency/observation, deduplicated secretary records and queued mail exist. This does not book RdvPermis. | [MonitorExamReadinessService](C:/wamp64/www/Passpermislaravel/app/Services/Monitor/Student/MonitorExamReadinessService.php), [readiness model](C:/wamp64/www/Passpermislaravel/app/Models/MonitorExamReadiness.php), [notification](C:/wamp64/www/Passpermislaravel/app/Notifications/V1/Monitor/Exam/StudentReadyForExamNotification.php) | [CandidateProflileDrawer](C:/wamp64/www/MaleeqPermiFAst/src/monitordashboard/CandidateProflileDrawer.jsx). Verify recipients, queue delivery and agency scope; service currently selects all active secretaries without an agency filter. |
| Local exam attempts / results | COMPLETE local implementation; PARTIAL provider integration. Shared service combines local exam records and historical EXA reservations, handles upcoming/history/deduplication. | [StudentExamInformationService](C:/wamp64/www/Passpermislaravel/app/Services/Student/Exam/StudentExamInformationService.php), [StudentExamController](C:/wamp64/www/Passpermislaravel/app/Http/Controllers/V1/EndPoint/Student/Exam/StudentExamController.php), [AdminExamController](C:/wamp64/www/Passpermislaravel/app/Http/Controllers/V1/EndPoint/Admin/Exam/AdminExamController.php) | [CandidateExamPage](C:/wamp64/www/MaleeqPermiFAst/src/new-student-dashboard/CandidateExamPage.jsx), [staff Exam](C:/wamp64/www/MaleeqPermiFAst/src/Components/exams/Exam.jsx). These use local exam APIs, not automatic provider reconciliation. |
| Email / notifications | PARTIAL operational assurance. Welcome, documents, cancellation, satisfaction, booklet and readiness mail classes exist. | [notifications](C:/wamp64/www/Passpermislaravel/app/Notifications), [readiness notification](C:/wamp64/www/Passpermislaravel/app/Notifications/V1/Monitor/Exam/StudentReadyForExamNotification.php) | Frontend actions exist in the corresponding modules. Mail credentials present is not delivery proof; worker/SMTP/inbox acceptance remains. Provider convocation emails are separately provider-owned. |
| Documents | COMPLETE implementation; NEEDS LIVE TEST. Student/admin document upload, review/status/download, monitor documents exist. | [StudentDocumentController](C:/wamp64/www/Passpermislaravel/app/Http/Controllers/V1/EndPoint/Student/Document/StudentDocumentController.php), [AdminStudentDocumentController](C:/wamp64/www/Passpermislaravel/app/Http/Controllers/V1/EndPoint/Student/Document/AdminStudentDocumentController.php) | [CandidateDocumentsPage](C:/wamp64/www/MaleeqPermiFAst/src/new-student-dashboard/CandidateDocumentsPage.jsx), [AdminDocumentsPage](C:/wamp64/www/MaleeqPermiFAst/src/Components/candidates/AdminDocumentsPage.jsx) |
| Chat / messaging | PARTIAL release assurance. Conversations, participant checks, attachments, unread/read tracking, catch-up and Reverb integration exist. Authorized attachment routes are now present. | [ChatController](C:/wamp64/www/Passpermislaravel/app/Http/Controllers/Chat/ChatController.php), [ConversationPolicy](C:/wamp64/www/Passpermislaravel/app/Policies/ConversationPolicy.php), [chat routes](C:/wamp64/www/Passpermislaravel/routes/chat.php) | [chat state](C:/wamp64/www/MaleeqPermiFAst/src/chat/useChat.js), [Echo config](C:/wamp64/www/MaleeqPermiFAst/src/chat/echo.js). Confirm public-storage direct URL protection and deployed socket host/TLS; do not repeat obsolete claims that controller attachment authorization is absent. |
| Maneuver catalog/history | COMPLETE local source implementation; NEEDS LIVE TEST. Admin catalog, per-lesson occurrences and monitor/student views exist, with new local migration. | [ManeuverCatalogController](C:/wamp64/www/Passpermislaravel/app/Http/Controllers/V1/EndPoint/Admin/ManeuverCatalogController.php), [ManeuverOccurrenceService](C:/wamp64/www/Passpermislaravel/app/Services/Monitor/Training/ManeuverOccurrenceService.php) | [ManeuverCatalogPage](C:/wamp64/www/MaleeqPermiFAst/src/Components/maneuvers/ManeuverCatalogPage.jsx), [FormationManeuvers](C:/wamp64/www/MaleeqPermiFAst/src/monitordashboard/formation/FormationManeuvers.jsx). New files are not yet tracked in the inspected working copy. |
| Theory / code practice | PARTIAL. Import/deduplication, questions/series, access, attempts and scoring code exist, but required local theory tables are missing and their migrations pending. | [Theory controllers](C:/wamp64/www/Passpermislaravel/app/Http/Controllers/Theory), [Theory services](C:/wamp64/www/Passpermislaravel/app/Services/Theory), [theory model](C:/wamp64/www/Passpermislaravel/app/Models/Theory/TheoryQuestion.php) | Routed through [App](C:/wamp64/www/MaleeqPermiFAst/src/App.jsx). Not deployable locally solely because UI/source exists. |
| Satisfaction / CPF / commercial features | PARTIAL acceptance evidence. Surveys, triggers, CPF forms/document mail, offers, carts, sales/invoices and payment integration paths exist; payment/mail/live acceptance was not executed. | [SatisfactionController](C:/wamp64/www/Passpermislaravel/app/Http/Controllers/SatisfactionController.php), [admin routes](C:/wamp64/www/Passpermislaravel/routes/admin/api.php), [student routes](C:/wamp64/www/Passpermislaravel/routes/student/api.php) | [AdminSatisfactionPage](C:/wamp64/www/MaleeqPermiFAst/src/satisfaction/AdminSatisfactionPage.jsx), [Invoice](C:/wamp64/www/MaleeqPermiFAst/src/Orders/Invoice.jsx). These commerce baskets are not RdvPermis exam baskets. |
| RdvPermis | PARTIAL overall; substantial IMPLEMENTED + AUTOMATED TESTED backend, staff UI, user-reported successful reads; successful provider mutation chain unproven. | See section 3. | See section 4; confirmed permutation bug remains. |
| Official Livret Numérique | NOT IMPLEMENTED as a provider integration. OAuth scope strings and some reused LN wording are not LN API operations. Local training booklet features are separate. | [RdvPermis config](C:/wamp64/www/Passpermislaravel/config/rdvpermis.php); no dedicated LN client/routes found in the inspected application search. | No official LN synchronization dashboard identified. Implement only if agreed scope requires it. |
| Miscellaneous placeholder destinations | NOT IMPLEMENTED for those screens. Generic messaging/contact/subscriptions/bills/statistics/settings destinations still use placeholder components; this does not negate the separate implemented chat/billing modules. | No corresponding full workflow was certified here. | [App placeholder routes](C:/wamp64/www/MaleeqPermiFAst/src/App.jsx) |

## 3. RdvPermis Backend Completed

### Architecture and evidence index

The mounted general routes are in [API routes](C:/wamp64/www/Passpermislaravel/routes/api.php:98). The student mandate route is in [admin API routes](C:/wamp64/www/Passpermislaravel/routes/admin/api.php:153). Both are mounted by [RouteServiceProvider](C:/wamp64/www/Passpermislaravel/app/Providers/RouteServiceProvider.php), using the `web` middleware group, not automatically the conventional Laravel API throttle group. General RdvPermis routes require Sanctum plus `admin|super-admin|secretary`; the server-side OAuth callback is public and secured through persisted one-time state. Mandate is additionally inside the existing verified/session/slow staff group.

Controller root: [RdvPermis controllers](C:/wamp64/www/Passpermislaravel/app/Http/Controllers/V1/EndPoint/RdvPermis). Service evidence:

- [OAuthService](C:/wamp64/www/Passpermislaravel/app/Services/RdvPermis/OAuthService.php): authorization-code URL, random state, SHA-256 storage, expiry, atomic consume, code exchange and token persistence.
- [TokenService](C:/wamp64/www/Passpermislaravel/app/Services/RdvPermis/TokenService.php), [RdvPermisToken](C:/wamp64/www/Passpermislaravel/app/Models/RdvPermisToken.php): encrypted access/refresh tokens and scopes, expiry/readiness, refresh rotation, shared-cache lock, invalid-grant reconnect, scoped encrypted-row recovery.
- [ApiClient](C:/wamp64/www/Passpermislaravel/app/Services/RdvPermis/ApiClient.php), [ProviderCooldown](C:/wamp64/www/Passpermislaravel/app/Services/RdvPermis/ProviderCooldown.php), [error trait](C:/wamp64/www/Passpermislaravel/app/Http/Controllers/V1/EndPoint/RdvPermis/HandlesRdvPermisErrors.php): bearer transport, timeout, no redirects, safe errors, Retry-After and logging.
- [AutoEcoleService](C:/wamp64/www/Passpermislaravel/app/Services/RdvPermis/AutoEcoleService.php), [StudentMandateService](C:/wamp64/www/Passpermislaravel/app/Services/RdvPermis/StudentMandateService.php), [CandidateMandateService](C:/wamp64/www/Passpermislaravel/app/Services/RdvPermis/CandidateMandateService.php), [CandidateEligibilityService](C:/wamp64/www/Passpermislaravel/app/Services/RdvPermis/CandidateEligibilityService.php).
- [CentreService](C:/wamp64/www/Passpermislaravel/app/Services/RdvPermis/CentreService.php), [PlanningService](C:/wamp64/www/Passpermislaravel/app/Services/RdvPermis/PlanningService.php), [PanierService](C:/wamp64/www/Passpermislaravel/app/Services/RdvPermis/PanierService.php), [ExamService](C:/wamp64/www/Passpermislaravel/app/Services/RdvPermis/ExamService.php).
- [RdvPermisSyncService](C:/wamp64/www/Passpermislaravel/app/Services/RdvPermis/RdvPermisSyncService.php), [RdvPermisSyncRecord](C:/wamp64/www/Passpermislaravel/app/Models/RdvPermisSyncRecord.php), [OAuth state model](C:/wamp64/www/Passpermislaravel/app/Models/RdvPermisOAuthState.php), [configuration](C:/wamp64/www/Passpermislaravel/config/rdvpermis.php).

In the matrix, `R` means `/api/rdvpermis`; it abbreviates HTTP routes, not filesystem paths. `A` now includes the fresh 209-test isolated mocked-suite pass, not live evidence. `A+` additionally identifies the safe unit checks from the initial audit. Test names resolve under the full test directories listed in section 13. `U` means user-reported live success, not independently verified here; `N` means successful live evidence not established.

| # | Capability / implementation status | Laravel route | Handler / service | Automated test evidence | Live RECETTE | Remaining action |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | Authorization Code: IMPLEMENTED | POST R/connect | RdvPermisController::connect -> OAuthService::authorizationUrl | A: OAuthConfiguration, OAuthCallback | U | Verify deployed employee flow/state database; current local token needs reconnect. |
| 2 | Server OAuth callback: IMPLEMENTED | GET R/callback | RdvPermisController::callback -> OAuthService::exchangeAuthorizationCode | A: OAuthCallback, OAuthConfiguration | U, complete callback trace unavailable | Connect and callback must hit the same deployment/database. |
| 3 | Access token storage: IMPLEMENTED | Internal callback/import persistence | TokenService::store; RdvPermisToken | A+: TokenService; A: TokenPersistenceRecovery, FreshMigration | N for newly observed usable session | Preserve APP_KEY, verify deployment table/state; do not infer connection from row status. |
| 4 | Refresh / rotation / lock: IMPLEMENTED | Internal before provider request | TokenService::refresh/refreshLocked | A+: TokenService; A: RefreshSafety | N | Live expiry/rotation/shared-worker acceptance; no local refresh token currently. |
| 5 | offline_access: IMPLEMENTED request | POST R/connect -> OAuth scopes | config + OAuthService | A: OAuthConfiguration | Granted live scope not established | Verify actual returned refresh capability and absolute session behavior. |
| 6 | Token encryption: IMPLEMENTED | Internal model casts | RdvPermisToken encrypted casts | A+: TokenService; A: TokenPersistenceRecovery | Local schema inspected; live not required to prove cast source | Backup/key access controls; do not decrypt tokens into reports. |
| 7 | Reconnect/readiness: IMPLEMENTED | GET R/status; POST R/connect | RdvPermisController; TokenService::readiness | A: Readiness, RefreshSafety | N current local provider check | Local metadata now requires reconnect; `status` is not a provider probe. |
| 8 | Current school: IMPLEMENTED | GET R/current-school | RdvPermisController -> AutoEcoleService::currentSchool | A+: AutoEcoleService; A: AutoEcoleApi | U | Preserve a sanitized live result/environment/date. |
| 9 | Employees list: IMPLEMENTED | GET R/employees | RdvPermisController -> AutoEcoleService::employees | A+: AutoEcoleService; A: AutoEcoleApi | U | Same live evidence capture. |
| 10 | Centre search: IMPLEMENTED | POST R/centres/recherche | CentreController::search -> CentreService::search | A: CentreApi | U | Verify returned schemas/groups with current export. |
| 11 | Centre detail: IMPLEMENTED | GET R/centres/{centreId} | CentreController::show -> CentreService::find | A: CentreApi | U | Retain successful centre-specific live evidence. |
| 12 | Planning/slots: IMPLEMENTED | POST R/planning/recherche | PlanningController::search -> PlanningService::search | A+: PanierService; A: PlanningApi | U | Nested filtre and provider slot UUID preserved; separate crenodispo operations absent. |
| 13 | Category-B mandate: IMPLEMENTED | POST /api/admin/students/{student}/rdvpermis/mandate | StudentMandateController::store -> StudentMandateService -> AutoEcoleService::createMandate | A: StudentMandate | Failure reported; no successful mandate proof | Obtain provider-valid current candidate; exact surname/NEPH, active B demande, account/email conditions; one approved attempt. |
| 14 | Existing mandate lookup: IMPLEMENTED | POST R/candidats-mandats/recherche | CandidateMandateController::search -> CandidateMandateService | A: StudentMandate | N | Live lookup of the newly approved mandate; account for pagination. |
| 15 | Slot-specific eligibility: IMPLEMENTED | POST R/candidats/recherche | CandidateEligibilityController + CandidateEligibilityRequest -> CandidateEligibilityService | A: EligibilityAndErrors, PlanningApi, PanierSlotApi | U for search, not assignment | Verify actual eligible candidate for chosen slot and mandate. |
| 16 | Create basket: IMPLEMENTED | POST R/paniers | PanierController::store -> PanierService::create | A: PanierApi | N | Live response ID/Location/CORS acceptance; no automatic write retry. |
| 17 | List baskets: IMPLEMENTED | GET R/paniers | PanierController::index -> PanierService::all | A: PanierApi | N | Live empty/nonempty result. |
| 18 | Basket detail: IMPLEMENTED | GET R/paniers/{panierId} | PanierController::show -> PanierService::find | A: PanierApi | N | Real elements/expiry/state schema. |
| 19 | Delete basket: IMPLEMENTED | DELETE R/paniers/{panierId} | PanierController::destroy -> PanierService::delete | A: PanierApi | N | Only approved disposable basket. |
| 20 | Add slot: IMPLEMENTED | POST R/paniers/{panierId}/creneaux | PanierController::addSlot -> PanierService::addSlot | A: PanierSlotApi | N | Provider UUID, slot capacity and optional candidate-required behavior. |
| 21 | Remove slot: IMPLEMENTED | DELETE R/paniers/{panierId}/creneaux/{creneauId} | PanierController::removeSlot -> PanierService::removeSlot | A: PanierSlotApi | N | Approved disposable basket. |
| 22 | Assign candidate: IMPLEMENTED | PUT R/paniers/{panierId}/creneaux/{creneauId}/candidat | PanierController::assignCandidate -> eligibility recheck -> PanierService | A+: PanierService; A: PanierSlotApi, EligibilityAndErrors | N | Candidate must appear with `eligibilite.estEligible === true`; provider remains final authority. |
| 23 | Remove candidate: IMPLEMENTED | Same PUT, `{candidatId:null}` | PanierController::assignCandidate -> PanierService | A+: PanierService; A: PanierSlotApi | N | Verify null-removal live. |
| 24 | Validate basket: IMPLEMENTED | POST R/paniers/{panierId}/valider | PanierController::validatePanier -> PanierService::validate | A: PanierApi | N | Prove real exam creation after validation, not just 2xx. |
| 25 | HTTP 207: IMPLEMENTED transport | Same validation route | ApiClient accepts 2xx; controller preserves status/body | A: PanierApi complete and partial fixtures | N | Per-slot/nested results are now displayed and tested; approved real partial-result/reconciliation acceptance remains. |
| 26 | Exams list: IMPLEMENTED | GET R/examens | ExamController::index -> ExamService::all | A: ExamApi | U | Verify created exam appears, not just a valid empty list. |
| 27 | Exam detail: IMPLEMENTED | GET R/examens/{examenId} | ExamController::show -> ExamService::find | A: ExamApi | N | Prove selected candidate/date/centre match booking. |
| 28 | Cancel exam: IMPLEMENTED | DELETE R/examens/{examenId} | ExamController::destroy -> ExamService::cancel | A: ExamApi | N | Approved disposable exam; respect provider penalties/cutoffs. |
| 29 | Permutable exams: IMPLEMENTED | GET R/examens/permutables | ExamController::permutables -> ExamService | A: ExamApi | N | Read actual eligible pairs before changing anything. |
| 30 | Permute exams: backend IMPLEMENTED | POST R/examens/permutation | ExamController::permute -> ExamService::permute | A: ExamApi | N | React explicit source/target mapping is fixed; confirmation/loading/error/refresh regressions pass. Approved real acceptance remains. |
| 31 | Replacement allowance: IMPLEMENTED | GET R/examens/nombre-de-remplacement | ExamController::replacementAllowance -> ExamService | A: ExamApi | N | Confirm current monthly allowance and response. |
| 32 | Replace candidate: IMPLEMENTED backend | POST R/examens/remplacement | ExamController::replace -> ExamService::replace | A: ExamApi | N | Validate replacement candidate for that exam; approved mutation only. |
| 33 | Trainer-count declaration: NOT IMPLEMENTED | None | No provider operation found | No targeted implementation test | N | Confirm whether legacy operation survives/current replacement workflow; implement only if required. |
| 34 | Department threshold: NOT IMPLEMENTED | None | No provider operation found | No targeted implementation test | N | Obtain current contract; do not invent route/coefficient rules from PDF. |
| 35 | Employee creation: NOT IMPLEMENTED | None | Employees GET only | No creation test | N | Confirm supported v2 operation and whether portal-managed employees are sufficient. |
| 36 | 429/cooldown: IMPLEMENTED reactive handling | Shared across R routes | ProviderCooldown + ApiClient + TokenService | A: EligibilityAndErrors, RefreshSafety | N | Shared-cache operational test; actor-based cooldown is not school-wide quota accounting. |
| 37 | Provider 400/401/403/404/409/422/429/5xx: IMPLEMENTED | Shared routes | ApiClient::responseException/businessMessage; error trait | A+: ApiClient; A: AutoEcoleApi, EligibilityAndErrors, StudentMandate | Only reported 400 business failure | Confirm real error envelopes/statuses; no raw upstream payload disclosure. |
| 38 | Network/TLS errors: IMPLEMENTED safety handling | Shared transport | ApiClient; OAuth/TokenService connection error paths | A+: ApiClient; A: RefreshSafety | N | Validate actual CA/network setup without disabling verification; UI 503 is not recovery proof. |
| 39 | Logging/redaction: IMPLEMENTED RdvPermis safeguards | Shared backend paths | ApiClient diagnostics; OAuthService; error trait; sync sanitizer | A+: ApiClient, SyncService | N deployed-log inspection | Review deployment logs/access; do not assume regex covers every possible unknown PII field. |
| 40 | Local/provider mappings: PARTIAL overall | Student mandate synchronization | RdvPermisSyncService + RdvPermisSyncRecord | A+: SyncService; A: StudentMandate, FreshMigration | Local mapping table has 0 rows | Mandate/candidate/school/group/context fields exist; complete provider-exam -> local-exam reconciliation/backfill is absent. |

Additional implemented backend-only operations: GET/POST `R/centres-favoris`, POST `R/paniers/{panierId}/creneaux-multiples`, GET `R/examens/dates`, GET `R/examens/pagines` (provider `/api/v2/auto-ecole/examens-pagines`, locally constrained to CE). These have controller/service/test paths, but no equivalent complete dashboard controls or successful live evidence established here.

All active provider operation constants inspected use `/api/v2/auto-ecole/...`, including **plural `paniers`**. The PDF's v1 routes are historical references, not missing-code findings in themselves.

## 4. RdvPermis Frontend Completed

Primary files: [RdvPermisPage.jsx](C:/wamp64/www/MaleeqPermiFAst/src/rdvpermis/RdvPermisPage.jsx), [styles](C:/wamp64/www/MaleeqPermiFAst/src/rdvpermis/RdvPermisPage.css), [slotSelection.js](C:/wamp64/www/MaleeqPermiFAst/src/rdvpermis/slotSelection.js), [rdvPermisApi.js](C:/wamp64/www/MaleeqPermiFAst/src/services/rdvPermisApi.js), [App routes](C:/wamp64/www/MaleeqPermiFAst/src/App.jsx:852), [shared Sidebar](C:/wamp64/www/MaleeqPermiFAst/src/Components/shared/Sidebar.jsx:132), [HTTP configuration](C:/wamp64/www/MaleeqPermiFAst/src/helpers/http.jsx), [API base configuration](C:/wamp64/www/MaleeqPermiFAst/src/config/api.js).

This is a real API-backed staff interface. Browser requests go to Laravel; provider secrets/tokens are not embedded in this UI. The app's own Sanctum token is a separate browser credential held in localStorage.

| # | UI capability | Status | Evidence / limitation |
| --- | --- | --- | --- |
| 1 | Connection status | COMPLETE implementation | Status, environment, configured state, expiry and reconnect message rendered; status endpoint is local metadata only. |
| 2 | Connect/reconnect | COMPLETE implementation | Starts backend connect then redirects browser; in-flight ref blocks duplicate initiation. Live deployment callback remains to be evidenced. |
| 3 | Current school | COMPLETE implementation | Manual read and details display. |
| 4 | Employees | COMPLETE implementation | Manual read/list; no creation UI. |
| 5 | Centre search | COMPLETE implementation | Department/group/closed-centre fields; provider-backed read. |
| 6 | Centre detail | COMPLETE basic rendering | Nested objects/arrays now preserved as readable JSON; actual provider schema still needs acceptance. |
| 7 | Planning | COMPLETE implementation | Date + selected centre + group sent to backend. Test actual schema and stale-filter transitions. |
| 8 | Slot selection | COMPLETE helper implementation; AUTOMATED TESTED | Nested `creneauDuPlanning.id` retained and passed unchanged to eligibility; one helper test passes. |
| 9 | Mandate candidate | COMPLETE code path; NEEDS LIVE TEST | Selected local student -> confirmation -> student mandate route; B only. Reported provider rejection is visible. |
| 10 | Selected student preview | COMPLETE implementation | Name, NEPH, email and B preview before confirmation; server rebuilds payload from database. |
| 11 | Student search | FIXED / AUTOMATED TESTED | Loads every local pagination page before client filtering, not just the first 500 records. Second-page selection regression passes. |
| 12 | Existing mandate lookup | COMPLETE basic wiring; PARTIAL pagination | Exact NEPH + B search for selected student; no complete pagination UI. |
| 13 | Basket list/detail | COMPLETE basic wiring | List/select/detail, elements, candidate removal and slot removal; actual provider schemas/live state unverified. |
| 14 | Basket expiry/countdown | FIXED / AUTOMATED TESTED | Timer covers listed/active baskets; known expiry disables booking writes and is rechecked when a confirmed action executes. Unknown expiry remains provider-authoritative. |
| 15 | Create basket | COMPLETE basic wiring; NEEDS LIVE TEST | Accepts response ID or Location header fallback. Cross-origin header fallback needs CORS confirmation. |
| 16 | Add/remove slot | COMPLETE implementation | Explicit confirmation, proper selected slot ID and API calls. No bulk-add dashboard control. |
| 17 | Eligibility | COMPLETE API wiring | Selected slot filter + optional name/NEPH, PARTIAL/EXACT match. Provider test result can legitimately be empty. |
| 18 | Candidate selection | FIXED / AUTOMATED TESTED | Only eligibilite.estEligible===true can be selected. Changing candidate invalidates prior assignment readiness. |
| 19 | Candidate assignment/removal | COMPLETE scoped wiring / AUTOMATED TESTED | Confirmation, exact slot/candidate payload, refusal and null-removal state reset tested. Backend still rechecks eligibility. |
| 20 | Validation / HTTP 207 | FIXED / AUTOMATED TESTED | Raw envelope, each confirmed/refused/unknown slot and nested diagnostics retained. Empty HTTP 200 or explicit refusal never implies booking success. |
| 21 | Confirmation / next basket | FIXED scoped UI state | Fully confirmed basket releases local active context for a new booking. Partial/unknown results lock repeat validation pending reconciliation; returned exam IDs are shown and exam list refreshed. Automatic provider/local-model sync remains outside scope. |
| 22 | Exam list | COMPLETE basic implementation | A/B date filter/list read; CE paginated backend view not exposed here. |
| 23 | Exam detail | COMPLETE basic rendering | Nested provider detail values no longer silently discarded. |
| 24 | Cancellation | COMPLETE wiring / AUTOMATED TESTED | Explicit confirmation/cancel, success/removal and refusal paths tested. No real cancellation. |
| 25 | Permutation | FIXED / AUTOMATED TESTED | Explicit source/target ID mapping, confirmation, busy/double-submit/error and successful awaited refresh tested; equal IDs rejected locally. |
| 26 | Replacement | COMPLETE current transport / AUTOMATED TESTED | Confirmation, selected exam/candidate payload, refusal and awaited refresh tested. Dedicated exam-bound replacement discovery remains a current-contract question. |
| 27 | Loading | AUTOMATED TESTED | Mutation remains busy through request and awaited detail/list/support refresh; failure releases the guard. |
| 28 | Empty states | COMPLETE basic implementation | Null/not-loaded distinguished from returned empty arrays; no invented demo exam data. |
| 29 | Errors | AUTOMATED TESTED scoped actions | New action clears stale success notices; refusal does not advance to success. No automatic mutation retries added. |
| 30 | Confirmation dialogs | COMPLETE basic implementation | All changing actions require explicit confirmation and disabled confirm/cancel while busy. Keyboard/focus accessibility acceptance not run. |
| 31 | Double-submit protection | AUTOMATED TESTED within mounted page | Double click produces one mocked permutation; cross-tab/provider idempotency is not claimed. |

### Fix results and exact root cause

Permutation formerly declared examId1/examId2 but used undefined examenId1/examenId2 as object shorthand, throwing before HTTP. It now explicitly maps **examenId1: examId1, examenId2: examId2**.

The complete traced chain is React confirmation execute -> [rdvPermisApi](C:/wamp64/www/MaleeqPermiFAst/src/services/rdvPermisApi.js) -> POST /api/rdvpermis/examens/permutation -> [ExamController](C:/wamp64/www/Passpermislaravel/app/Http/Controllers/V1/EndPoint/RdvPermis/ExamController.php) -> [ExamService](C:/wamp64/www/Passpermislaravel/app/Services/RdvPermis/ExamService.php) -> POST /api/v2/auto-ecole/examens/permutation, unchanged French keys. Backend production source was already correct and was not edited.

Success awaits source detail, exam list, permutable options and allowance reads. Failure releases loading and shows the safe error without a success notice. Both selected IDs and one-request/double-submit behavior are tested.

Validation had two causes: the general unwrap helper mistook the provider's per-slot data array for an application envelope, and scalar-only JsonDetails dropped nested content. [validationResult.js](C:/wamp64/www/MaleeqPermiFAst/src/rdvpermis/validationResult.js) now reads the raw forwarded body; [PanierValidationResult.jsx](C:/wamp64/www/MaleeqPermiFAst/src/rdvpermis/PanierValidationResult.jsx) renders every slot plus the full response. Only estReserve===true is individually confirmed; complete success also requires all returned items confirmed with no 207/error flag. Unknown/empty responses are not confirmations.

Complete local student paging, known-expiry guards, assignment-selection/removal readiness and post-confirmed-validation reset are also fixed. The original render-purity error uses a lazy clock initializer. No lint rule was weakened.

**0 scoped lint errors / 0 warnings.** The separate [unrelated lint inventory](C:/wamp64/www/MaleeqPermiFAst/docs/RDVPERMIS_UNRELATED_LINT_REPORT.md) lists all 184 error-containing files and 468 unchanged errors.

Conditional contract items remain: an actual Location-only creation response/CORS requirement, dedicated exam-bound replacement discovery and real partial-basket reconciliation semantics. No guessed provider route/schema was added.

## 5. Successfully Tested in RECETTE1

### Confirmed RECETTE Working — evidence-qualified

The following successes were supplied in the current user brief and agree with implemented routes. They are recorded as **USER-REPORTED LIVE**, not freshly independently verified successes:

| Reported working item | What source confirms | What still prevents independent sign-off |
| --- | --- | --- |
| OAuth connection | Authorization-code connect/callback/encrypted persistence exist. | No fresh accepted callback/token/school trace from the same deployment was collected. |
| Current school: Alliance du 089 | Correct v2 `GET .../moi` path. | School identity is supplied by the user, not proven by token contents or local row status. |
| Centre search | v2 centre search handler/service. | No new provider response captured. |
| Centre detail | v2 centre detail handler/service. | Same limitation. |
| Planning / slots | Nested provider filter and slot UUID handling. | Same limitation; displayed slots do not prove reservation success. |
| Candidate eligibility | Slot-specific search now exists. | A returned empty list can be a successful read, not proof that a usable candidate exists. |
| Exam list | Correct v2 programmed-exams read. | An empty/old list does not prove this application's validation created an exam. |
| Employees read | Correct v2 employees GET. | No fresh response captured. |

No successful full mandate -> assigned basket -> validated booking -> matching exam read-back chain is evidenced by the supplied files reviewed in this audit.

The local Laravel log contains 18 `RdvPermis API request` entries, in two matching groups on 19 October 2026 at 11:50:34 and 11:52:03. They include paths with synthetic `exam-1`/`exam-2` identifiers and exactly match the mocked ExamApi test flow, including cancellation/replacement/permutation. **These entries must not be counted as live government mutation successes.** Those earlier test runs did not all mock logging. The fix pass adds log spies to the touched exam/basket tests and uses a null log channel for the isolated focused suite; the existing application log was unchanged.

This audit made no authenticated provider business request. Documentation retrieval attempts did not obtain a current contract: the web tool could not access the Swagger URL and the terminal GET failed at transport level without an HTTP response. That does not establish a current HTTP 401, invalid credentials, Ministry outage, or a specific TLS root cause.

## 6. Features Implemented but Awaiting Live Test

Successful live acceptance remains outstanding for:

- Current usable employee token, refresh rotation/expiry/reconnect, granted scopes and shared-cache lock behavior on the target deployment.
- Successful mandate with a currently valid B candidate; account/email variations; retained separate mandate/candidate/school IDs; existing-mandate read-back.
- Basket create/list/detail/delete; real expiry; add/remove slot; candidate eligibility, assignment and null-removal.
- Basket validation with full success and partial HTTP 207; exact created-exam read-back; expiry/conflict handling; safe reconciliation after an uncertain response.
- Exam detail, permutable pairs, replacement allowance, cancellation, permutation and replacement. The frontend permutation is now fixed and mocked regressions pass; live acceptance still needs separate approval.
- Favourite centres, bulk slot addition, exam dates and CE paginated reads if these are in the accepted release scope.
- Integration into local student exam/attempt records if that synchronization is part of the product promise. The current provider proxy does not perform it automatically.

Live mutation tests require separate approval and designated disposable RECETTE data. Do not use ordinary real/live student identities against RECETTE or repeatedly create mandates to backfill missing mappings.

## 7. Current Blocker

### Reported RECETTE mandate situation

Environment reported: RECETTE1. School reported: Alliance du 089. For the named test candidates in the user brief and ordinary local/live student attempts, the reported response is HTTP 400: candidate unknown (`CANDIDAT_INCONNU`). Candidate names, NEPHs and credentials are intentionally not reproduced here.

Confirmed source path:

`/rdvpermis` staff page -> shared Laravel HTTP client -> POST `/api/admin/students/{student}/rdvpermis/mandate` -> StudentMandateController -> StudentMandateService -> AutoEcoleService -> ApiClient -> POST `/api/v2/auto-ecole/mandats`.

The server builds the payload from local persisted data, not arbitrary browser fields:

- `nom`: trimmed `users.last_name`.
- `numeroDossier`: `students.neph`, preserved as an opaque string, including leading zeros.
- `groupePermis`: B. `boite_type`/manual-automatic transmission does not determine the permit group.
- `email`: included only when local email is present and valid. Current code does not automatically discover provider account-existence before deciding whether to include it; confirm account-dependent rules in current Swagger, since the legacy PDF describes email differences for existing/new accounts.

| Possible cause | Evidence-based conclusion |
| --- | --- |
| Frontend route missing | Not supported: the interface/API wrapper/server route exist. The reported specific upstream business error is inconsistent with a simple missing-UI-route explanation. |
| Laravel route absent | Not supported by current source. Source/tests prove the path, not the deployed revision. |
| OAuth caused those specific 400 responses | Not the leading explanation if the response truly came from authenticated RdvPermis business validation. It does not prove today's local/staging connection is still valid. |
| Provider business validation | Strongly supported by the reported HTTP 400 and code. It is a rejection, not a provider transport success or successful mandate. |
| Stale RECETTE candidate dataset | Strong suspect: ZIP filename export timestamp is 2025-08-14 10:59, and all ten ZIP member timestamps are 2025-08-14 08:59:36. This is not current October 2026 data. |
| Wrong environment, surname/NEPH pair, removed/missing B demande, account restrictions | Still possible; no current synchronized provider data or full live request trace was available to eliminate them. |

The PDF says RECETTE data is reset on the first Sunday of each month. Treat that as a historical explanation of why old datasets can stop matching; ask the provider for the current reset/data policy. Do not declare the current policy verified solely from the 2023 PDF.

### Separate local connection/configuration issue

A direct **read-only local MySQL** check found one token row with an access-token field present, no refresh-token field value, and zero future access-token expiries. No tokens were decrypted or displayed. TokenService readiness therefore cannot treat this as a usable connected session merely because the stored status says `connected`.

The local source configuration sends OAuth callbacks to staging2, while local `/connect` would store its one-time state in the local database. If connect starts locally and callback finishes on staging using a different database, state validation will fail. This is an architectural deployment condition, not proof that the existing hosted flow is broken. Initiate and complete OAuth on one deployment/database; do not change registered callback URLs casually.

Conclusion: the **reported mandate blocker is provider/data-validation related**, with stale RECETTE data a leading hypothesis; **local reconnection/configuration**, **remaining frontend coding**, and **production onboarding** are independent issues.

## 8. What We Have From RdvPermis

### Available RdvPermis Resources

Only non-secret environment information is included. “Configured” records presence, not correctness, authorization or production entitlement.

| Resource | Current available evidence |
| --- | --- |
| RECETTE API base | `https://api.integediteurs.rdv-permis.interieur.gouv.fr`; inspected services append `/api/v2/auto-ecole/...`. |
| OAuth authorization URL | `https://recette.moncompte.permisdeconduire.gouv.fr/auth/realms/formation/protocol/openid-connect/auth` |
| OAuth token URL | `https://recette.moncompte.permisdeconduire.gouv.fr/auth/realms/formation/protocol/openid-connect/token` |
| Backend callback configured locally | `https://staging2.passpermisfacile.fr/api/rdvpermis/callback` |
| Frontend return configured locally | `https://reactfast.passpermisfacile.fr/` |
| Client ID | Configured. Value withheld. |
| Client secret | Configured. Value withheld. |
| Application encryption key | Configured. Value withheld. |
| Configured scopes | `rdvpermis livret_numerique:read livret_numerique:write offline_access`; actual granted scopes not established here. LN scopes do not imply LN integration. |
| School | Alliance du 089, USER-REPORTED LIVE. Not independently read in this audit. |
| Main functional/technical PDF | [74-page source PDF](<C:/Users/123/Downloads/api passpermis docs/APIs RdvPermis - Doc fonctionnelle & technique.pdf>); revision history ends 14 September 2023. |
| Separate LN reference | [Livret Numérique PDF](<C:/Users/123/Downloads/api passpermis docs/LivretNumérique_Documentation  fonctionnelle et technique.pdf>) is available. Its existence does not establish implemented LN operations; this report's detailed page comparison is for the requested RdvPermis PDF. |
| Candidate/demande data | [dataset directory](<C:/Users/123/Downloads/api passpermis docs/files>) contains `candidat.csv` and `demande.csv`, in the August 2025 export set. No candidate values imported or rewritten. |
| School/employee data | `auto_ecole.csv`, `employe_ae.csv` in that directory. Employee CSV includes a password column: treat the file as sensitive; no values reproduced. |
| Other historical data | Trainer records/affectations, simulators/affectations, practical/theoretical exam results are present. No centre/inspector/slot dataset file was found in the supplied ten-file export, although such datasets are discussed by the PDF. |
| Current centre/slot access | Reported working through the API; source/test paths exist. No newly captured live response. |
| Swagger URL | [official integration Swagger](https://api.integediteurs.rdv-permis.interieur.gouv.fr/swagger-ui/index.html) is known. Prior project history records authenticated inspection; current authenticated v2 contract was not retrieved in this audit. Access values are withheld. |
| Local token / mappings | Canonical token/state/mapping tables exist. Current local token is not usable by expiry/refresh metadata; mapping table is empty. Not evidence of staging state. |

The supplied PDF contains documentation-access credentials and example token material. These were treated as sensitive source data, not instructions to expose credentials or repeat unsafe sample commands.

## 9. What We Need From RdvPermis / Ministry

### Required From RdvPermis / Ministry

| # | Item | Classification | Exact need / why |
| --- | --- | --- | --- |
| 1 | Current RECETTE1 candidates | Need from provider | Synchronized current dataset/export date and environment; August 2025 file is insufficient. |
| 2 | Current demandes | Need from provider | Same export/reset generation as candidates; verify active B demande and no deletion. |
| 3 | Suitable category-B mandate candidate | Need from provider | Approved disposable candidate with exact surname/NEPH, account status and permitted email behavior. Deliver securely, not in a public report. |
| 4 | Candidate belongs to current RECETTE1 data | Need from provider | Explicit confirmation of environment/reset generation and compatibility with the intended school/mandate test. |
| 5 | Current authenticated v2 OpenAPI/Swagger | Need from provider or working authorized access | An export with version/date/security/schemas/errors is preferable for reproducible contract tests. Prior access is not a current frozen contract. |
| 6 | Trainer/threshold API contracts | Needs confirmation | Confirm still supported/required, exact current operations and management workflow. Legacy PDF alone cannot justify implementing v1 paths. |
| 7 | Employee-creation v2 contract | Needs confirmation | Confirm programmatic creation versus official portal administration and whether needed for this release. GET employees is already implemented. |
| 8 | Production API URL | Already have historical reference; needs confirmation | PDF p.71 lists `https://api.permisdeconduire.gouv.fr/api/v1`; do not use that v1 suffix as the current v2 contract. Confirm current host/base/version. |
| 9 | Production OAuth URLs | Already have historical references; needs confirmation | PDF p.71 gives auth/token/logout under `https://auth.permisdeconduire.gouv.fr/realms/formation/protocol/openid-connect/`. Confirm current URLs rather than claiming none were supplied. |
| 10 | Production Client ID / Secret | Need provider confirmation/provisioning | RECETTE credential presence does not prove production credentials/entitlement. No production credential validation performed. |
| 11 | Production callback registration | Need from provider, after owner chooses final deployment URL | Obtain accepted exact HTTPS callback; keep server-side state/token exchange and same deployment DB. |
| 12 | Production scopes | Needs confirmation | Confirm RdvPermis/offline_access and least-privilege LN scopes only if LN is actually in scope. |
| 13 | Production IP whitelist | Needs confirmation, conditional | Ask if required; if yes, supply real deployment egress evidence. No separate ANTS/VABF or Resana access was established here. |
| 14 | Production onboarding/approval | Need confirmation / completion | Provider approval status and authorized school/employee relationship must be evidenced. |
| 15 | Certification / validation requirements | Needs confirmation | Obtain provider's release/recette acceptance or homologation checklist; do not invent a certification requirement or assume exemption. |

Additional useful confirmations: current dataset reset schedule, basket capacity units/expiry, group-specific operations, quota scopes/windows, safe retry/reconciliation rules, notification behavior and client session maximum. These are contract questions, not automatic coding requirements.

Not required merely to unblock a normal editor authorization-code integration: implementing Client Credentials, downgrading all services to v1, a separate guessed ANTS client, or automatic mandate retries. The PDF describes Client Credentials for the separate online-school use case; the current registered-editor architecture uses Authorization Code.

## 10. Missing / Version-Dependent Features

### Full PDF analysis and current implementation comparison

PDF source: [APIs RdvPermis – functional and technical documentation](<C:/Users/123/Downloads/api passpermis docs/APIs RdvPermis - Doc fonctionnelle & technique.pdf>). All 74 pages were reviewed for functional/technical requirements; basket-rule and refresh-timeline diagrams were also inspected visually. The document is a historical functional baseline, not a verified October 2026 contract or statement of current law.

| PDF pages / subject | Legacy document meaning | Current v2 implementation / remaining boundary |
| --- | --- | --- |
| 1–5: purpose, revisions, actors | Evolving integration documentation; last listed revision 14 September 2023. | Date/version-limit all conclusions. Request current v2 contract. |
| 6–11: school/candidate/inspector journey | School takes mandate, chooses places/candidates, validates and manages examinations; provider sends candidate communications. | Main school/staff workflow exists. Candidate portal/inspector APIs are not the same scope as staff Auto-école APIs. |
| 12–16: basket and operation sequence | About 30-minute basket validity; legacy v1 trainer/threshold/mandate/planning/basket/exam endpoints. | Core chain implemented with v2; do not downgrade. Trainer/threshold absent. Expiry is displayed, not locally recreated as the authority. |
| 17–19: quotas, BER/formateur declarations | Monthly departmental/category entitlement and restrictions based on trainer information; transitional trainer declaration rules. | Not implemented as dedicated current API workflows. Confirm current provider rules/contracts; do not hardcode historical BER coefficients or 48/72-hour rules. |
| 20–22: candidate mandate | Surname + NEPH, qualifying demande/category; account-dependent email and mandate conditions; candidate revocation. | Local B payload and provider error handling exist. No automatic candidate import or candidate self-revocation flow. Current B data/account/email conditions require live verification. |
| 23–24: reservation rules | Provider cutoff times, basket expiry, group/candidate constraints, maximum places and slot reuse. | Provider is authoritative. PDF p.12 says 12 créneaux; p.24 says 36 exam places. These are different units and may be compatible, not automatically a contradiction. Confirm current limits before enforcing new local caps. Backend bulk-add currently caps input at 12 slot IDs; verify that specific batch limit against current Swagger. |
| 25: permutation/replacement | Category/week/department/time restrictions and monthly replacement availability. | Backend operations implemented; UI permutation fixed and mocked tests pass; dedicated replacement discovery/real allowances need current-contract acceptance. No local reconstruction of legacy policy. |
| 26–27: cancellation, failure/presentation delays | Late-cancellation penalties, republishing and result-based retake delays described historically. | Provider DELETE/error responses govern. No current-law or exact current-penalty claim is made; verify present provider rules and display them correctly. |
| 28: notifications | Provider emails follow mandate/exam/cancel/permutation/replacement events; partners do not control test inboxes by default. | Local mail classes do not prove provider convocation email delivery. Provider email/event acceptance remains. |
| 29–35: groups, RECETTE data, Swagger | LN/RdvPermis related platform but distinct API groups; recurring reset/dataset distribution; documentation access. | Separate LN integration absent. Available CSV export is old and lacks separate centre/slot files. Current authenticated contract not refreshed. Credentials in source must remain private. |
| 36–42: OAuth choice and secret custody | Editor uses Authorization Code; online-school case may use Client Credentials; logout and server-secret requirements. | Editor flow implemented. No Client Credentials service or dedicated provider logout/disconnect endpoint. Logout is optional/conditional scope, not a mandate blocker. |
| 43–57: authorization/code/token/refresh | Employee resource-owner consent, registered callback, one-time code, requested offline scope, short access token and refresh. | DB-hashed one-time state, encrypted token persistence and refresh/rotation exist; callback is server-side. Actual granted scopes/live refresh unproven here. |
| 58–61: absolute session ceiling/scopes | PDF describes a roughly 60-day maximum authorization session; refresh should not extend it indefinitely; least-privilege scopes. | Invalid-grant/reconnect and returned refresh expiry handled. No distinct initial-session-60-day tracking/reminder was identified. Confirm current policy; test eventual reconnect and whether a proactive warning is required. Do not copy the PDF's TLS-bypassing sample command. |
| 62–69: Client Credentials example | Alternate machine-to-machine flow for a different registered-client case. | Not implemented; not automatically needed for this editor project. Do not redesign functioning Authorization Code on this basis. |
| 70–72: production addresses/rate limits | Historical production URLs and school-size/planning endpoint quota windows. | Production config/registration unverified. Reactive per-actor cooldown exists, not full school-level quota budgeting. Current URL/quota definitions need provider confirmation. |
| 73–74: further platform/LN context | Related information does not equate all services with one implementation. | Local booklet != official Livret Numérique provider synchronization. |

The implemented chain aligns conceptually with the official school workflow, but passing a search or obtaining a basket identifier is not proof of final booking. Acceptance must finish with a provider exam linked to the correct candidate/centre/date and account for partial results.

### Actual missing/conditional code

- **Completed in the authorized fix pass:** permutation mapping, raw/per-slot validation handling, all-page local student lookup, strict candidate/assignment readiness, expiry/removal/reset safety, nested detail preservation and the requested action-level tests. No enumerated local fix remains open.
- **Provider-contract/acceptance dependent:** actual response shapes, header-only basket creation if applicable, dedicated exam-bound replacement discovery and partial-basket reconciliation rules. Known local expiry/reset/nested-detail gaps are fixed; no unsupported contract was invented.
- **Missing provider capabilities with scope/version confirmation:** trainer declaration, departmental threshold, employee creation, dedicated available-slot discovery/first-date operations (`crenodispo` / `crenodispo/date` were identified in prior authenticated contract work, not freshly reconfirmed here). Planning search is already implemented and may satisfy a limited booking UI.
- **Missing broader integration:** provider exam/attempt synchronization into local exam records, reconciliation/backfill/jobs and official LN operations. Required only if promised in the agreed product scope; they cannot be represented as complete today.
- **Existing backend features not exposed in this dashboard:** favourite centres, bulk-add, exam dates and CE paginated list. Not every provider endpoint needs a UI, but release scope should say which ones do.

Historical figures such as “27 of 38 endpoints” and “no React dashboard” are not reused as current facts. Eligibility and the dashboard have since been added, and the current contract was not downloaded to recompute full provider coverage.

## 11. Database & Migration Status

Method: parsed local configuration privately, confirmed the target is loopback/local MySQL, opened a direct read-only transaction, inspected schema/migration ledger and aggregate counts, then rolled back. Laravel application routes, schema mutations, token decryption and migrations were not executed. No staging/production database was accessed.

| Item | Verified current local result | Deployment consequence |
| --- | --- | --- |
| `rdvpermis_tokens` | Present, 1 row; UUID primary key, unique user relationship; text access/refresh/scopes and expiry/status fields. | Model encryption source exists; exact production encryption/key/table state must be verified separately. |
| Legacy `rdv_permis_tokens` | Absent locally. | Normalization migration is designed to stop if both tables coexist, not silently discard credentials. Verify before deployment. |
| `rdvpermis_oauth_states` | Present, 3 rows; state hash, expiry and consumed fields. | Connect/callback must share this store. Expired/consumed-state cleanup/retention needs operational policy. |
| `rdvpermis_sync_records` | Present, 0 rows; separate remote mandate/candidate/school/group/context/timestamps/attempt fields. | No local successful mapping evidence. This does not prove staging has no records. |
| `students.neph` | `TEXT`, nullable; local students count 2,006. | String identifiers/leading zeros preserved going forward. Text conversion cannot restore digits already lost historically. |
| RdvPermis migrations | Base tokens, base sync, OAuth states, normalization, text-NEPH repair and mapping-column repair all applied locally. | Does not establish staging ledger or a clean install of every unrelated migration. |
| Recent feature schema | Calendar subscriptions, `monitor_exam_readinesses` (plural), readiness notifications, maneuver catalogs/occurrences present; corresponding October 19/20 migrations applied locally. | Source still contains untracked new files; deployment must package source and schema together. |
| Wallet precision | `wallets.balance` integer and `wallets.balance_minutes` unsigned integer both present. | Audit all debit/refund paths for unit parity; legacy approval still passes whole hours. |
| Whole-project migration ledger | **19 migration files pending locally**, including historical cancellation/reservation/secretary changes and theory migrations. Several historical tables already exist despite missing ledger entries. | Schema/ledger drift is real. Do not blindly run all migrations: inspect duplicates/compatibility and plan backups/reconciliation first. |
| Theory tables | No `theory_*` tables found locally. Theory question/series/manual-access migrations pending. | Theory feature source cannot establish a functioning local deployment. |

Relevant migration files:

- [create tokens](C:/wamp64/www/Passpermislaravel/database/migrations/2026_08_22_000000_create_rdvpermis_tokens_table.php)
- [create sync records](C:/wamp64/www/Passpermislaravel/database/migrations/2026_08_22_000001_create_rdvpermis_sync_records_table.php)
- [create OAuth states](C:/wamp64/www/Passpermislaravel/database/migrations/2026_09_24_000000_create_rdvpermis_oauth_states_table.php)
- [normalize token table](C:/wamp64/www/Passpermislaravel/database/migrations/2026_10_07_000001_normalize_rdvpermis_token_table.php)
- [NEPH text repair](C:/wamp64/www/Passpermislaravel/database/migrations/2026_10_07_000002_store_neph_as_text.php)
- [candidate mappings](C:/wamp64/www/Passpermislaravel/database/migrations/2026_10_07_000003_add_rdvpermis_candidate_mapping.php)
- [calendar subscriptions](C:/wamp64/www/Passpermislaravel/database/migrations/2026_10_19_000000_create_monitor_calendar_subscriptions_table.php)
- [readiness tables](C:/wamp64/www/Passpermislaravel/database/migrations/2026_10_19_000001_create_monitor_exam_readiness_tables.php)
- [maneuver tables](C:/wamp64/www/Passpermislaravel/database/migrations/2026_10_20_000000_create_maneuver_catalog_and_occurrences_tables.php)

The saved 34-test subset is historical SQLite artifact evidence. The subsequently authorized full focused suite included these isolated in-memory schema fixtures, not application database migrations. Neither result proves unrelated MySQL migrations safe to replay. The application DB snapshot above was not changed.

## 12. Security Status

Implemented safeguards:

- Provider client secret stays on Laravel; React calls only the application API. Provider tokens are encrypted at rest and hidden on model serialization.
- OAuth state is high-entropy, hashed in the database, time-limited, single-use and bound to the originating user. Callback does not depend on carrying a browser Sanctum token through the provider redirect.
- API transport uses bearer tokens, JSON acceptance, timeouts and explicitly disabled redirects. No TLS-verification bypass was added. Network exceptions and upstream failures are mapped to sanitized client responses.
- Refresh locking re-reads persisted token state; rotation, lock contention, invalid grants and cooldown have tests. Normal mutations are not automatically retried.
- RdvPermis logs use endpoint/method/status and sanitized diagnostics rather than raw token bodies. Sync failures retain safe messages; test coverage checks redaction.
- Candidate assignment performs a server-side eligibility read before the mutation. Local NEPH values are not coerced into numbers.
- Staff roles protect provider operations. Local monitor/student formation access and chat participant gates are visible in source.

Remaining security/operational checks:

1. Verify actual granted scopes, minimize unused LN write/read scopes if not needed, and protect APP_KEY/backups. Scope-string presence is not entitlement.
2. Token rows are keyed by local user, not by environment/school. Mapping uniqueness is local entity type/ID, while provider environment is contextual metadata. Isolate RECETTE/production deployments and token stores; switching only `RDVPERMIS_ENV` does not select safe URLs or separate credentials.
3. `RDVPERMIS_ENV` is only a label. API/auth/token/callback URLs must be configured consistently. A fresh production connection/registration is required; do not reuse a RECETTE token against production.
4. Confirm shared cache/locks across deployed workers. Current local default cache is database; an array/process-only store would not coordinate production workers.
5. Reactive cooldown is scoped to actor and channel, not automatically to the provider's school-wide quota. General RdvPermis routes have no explicit dedicated throttle in the inspected route group. Assess aggregate quota control; do not stress-test a live provider.
6. App API routes use `web` middleware and a broad `api/*` CSRF exemption. Bearer-authenticated SPA use is supported, but mixed cookie/bearer authentication and CORS/CSRF deployment behavior need security acceptance. Configured credentialed CORS alone is not proof of safe working sessions.
7. The app's Sanctum credential is stored in browser localStorage. This is distinct from provider tokens; protect against XSS and verify logout/revocation behavior.
8. Chat attachment controllers now authorize conversation access. However files are still stored on the public disk and path fields are serialized; verify private-storage/direct-URL behavior on the deployed web server. Route authorization alone does not prove a `/storage/...` bypass is blocked.
9. Readiness notification code selects all active secretaries without an agency filter. Confirm this global recipient policy is intended before sharing student readiness/observations across agencies.
10. Preserve the supplied documents/datasets securely. The employee CSV has password data and the PDF includes access/example token material. No such values belong in this report, frontend bundles, tickets or public repositories.
11. Some RdvPermis-facing messages still say “Livret Numérique”; this is misleading UI wording, not evidence of an LN API implementation.

No production penetration test, dependency vulnerability assessment, remote log review or secret rotation was performed. No claim of complete security clearance is made.

## 13. Test Results

### Backend — fresh full focused suite

**209 tests / 1,039 assertions; 0 failures/errors/skips.** Command from the backend root: php -d xdebug.mode=off vendor/bin/phpunit -c phpunit.rdvpermis.xml --do-not-cache-result.

[Focused configuration](C:/wamp64/www/Passpermislaravel/phpunit.rdvpermis.xml) forces testing, SQLite :memory:, empty DB_URL override, array cache/session/mail and null logging. Named test connections also use SQLite :memory:. Provider HTTP is faked and protected against stray requests. No application/staging/production DB or real provider mutation was used. Ephemeral fixture migrations are not deployment migrations.

Twenty focused test files, 109 named methods expand through data providers into 209 executed cases. The migration subset is included, not added again. Older [201-case XML](C:/wamp64/www/Passpermislaravel/storage/logs/rdvpermis-phase1-tests.xml) and [34-case XML](C:/wamp64/www/Passpermislaravel/storage/logs/rdvpermis-fresh-migrations.xml) remain unchanged historical artifacts, not representations of this new run.

Added/expanded backend regressions:

- [RdvPermisExamApiTest.php](C:/wamp64/www/Passpermislaravel/tests/Feature/RdvPermisExamApiTest.php): distinct source/target IDs, exact French keys, one upstream request; rejects English/missing fields; failed permutation sanitized/not retried/not success; log spy added.
- [RdvPermisPanierApiTest.php](C:/wamp64/www/Passpermislaravel/tests/Feature/RdvPermisPanierApiTest.php): mixed 207 with nested diagnostics, 200 business refusal and unknown body forwarded unchanged; log spy added.
- Existing mandate/eligibility/assignment/OAuth/refresh/encryption/mapping/transport tests all passed. Backend production route/controller/service code did not need alteration.

### Frontend — fresh complete RdvPermis suite

**npm.cmd run test:rdvpermis: 32 tests / 32 passed / 0 failures or skips.**

| Layer / file | Coverage |
| --- | --- |
| [Existing slot test](C:/wamp64/www/MaleeqPermiFAst/tests/rdvpermis-slotSelection.test.js) | 1 provider slot-ID selection/payload test; run, not modified |
| [New validation tests](C:/wamp64/www/MaleeqPermiFAst/tests/rdvpermis-validation.test.js) | 13 checks: raw envelope, confirmed/mixed 207/refused/empty/unknown responses, strict booleans, expiry and repeat-validation guard |
| [New browser workflows](C:/wamp64/www/MaleeqPermiFAst/tests/rdvpermis-workflows.test.js) | 18 actual-component tests: mandate confirm/cancel/refusal; second-page student; basket transitions; assignment/refusal/null-removal; unknown eligibility; full/207/empty/failed validation; expiry; cancellation success/refusal; permutation source/target/confirmation/busy/double-submit/refresh/refusal; replacement success/refusal/refresh |

The runner starts a separate ephemeral loopback Vite server with application config/.env loading disabled. API base is rdvpermis-mocked.invalid; all API requests are intercepted and unexpected external requests fail. It mounts the actual component/API wrapper via [HTML fixture](C:/wamp64/www/MaleeqPermiFAst/tests/rdvpermis-browser.html) and [fixture entry](C:/wamp64/www/MaleeqPermiFAst/tests/rdvpermis-browser.js), not an imitation UI. No running Laravel/provider credentials/live access required. Fixtures are not imported by the production entry.

Playwright is a dev dependency in [package.json](C:/wamp64/www/MaleeqPermiFAst/package.json) and [package-lock.json](C:/wamp64/www/MaleeqPermiFAst/package-lock.json). Tests use installed headless Chrome; another installed Playwright-supported channel can be chosen with RDVPERMIS_TEST_BROWSER. No .env edits are needed.

### Lint / production build

- Focused ESLint includes src/rdvpermis, RdvPermis API service, shared HTTP/API-base and new test files: **0 errors / 0 warnings**.
- Whole npm.cmd run lint inventory: **468 unrelated errors / 26 warnings**, 184 error-containing files, 185 error/warning-containing files. No rule suppression/weakening, blanket ignores or unrelated cleanup.
- npm.cmd run build: **passed**, 2,382 modules, local production dist generated; existing large-chunk warning remains. Nothing deployed.
- Complete unrelated file/line/rule list: [separate lint report](C:/wamp64/www/MaleeqPermiFAst/docs/RDVPERMIS_UNRELATED_LINT_REPORT.md).

### Exact changed-file manifest

Frontend runtime:

- [RdvPermisPage.jsx](C:/wamp64/www/MaleeqPermiFAst/src/rdvpermis/RdvPermisPage.jsx)
- [RdvPermisPage.css](C:/wamp64/www/MaleeqPermiFAst/src/rdvpermis/RdvPermisPage.css)
- [validationResult.js](C:/wamp64/www/MaleeqPermiFAst/src/rdvpermis/validationResult.js)
- [PanierValidationResult.jsx](C:/wamp64/www/MaleeqPermiFAst/src/rdvpermis/PanierValidationResult.jsx)
- [rdvPermisApi.js](C:/wamp64/www/MaleeqPermiFAst/src/services/rdvPermisApi.js)

Frontend support: the four new test/fixture files linked above, package.json, package-lock.json and the separate lint report. Existing slot-selection test was not edited.

Backend: the two feature tests and isolated PHPUnit configuration linked above, plus this report. No backend production application source or migration file changed. Unrelated pre-existing work was preserved.

### Evidence separation

AUTOMATED: fresh 209 backend and 32 frontend passes. LIVE: previous eight successes remain user-reported; no newly verified read/connection or real mutation. NOT CERTIFIED: protected whole-app login/role/agency/navigation, actual provider booking acceptance, deployment operations and production approval. Mocks cannot increase the live-success percentage.

## 14. Deployment Status

### React

- [vite.config.js](C:/wamp64/www/MaleeqPermiFAst/vite.config.js) enforces a configured production `VITE_API_URL` and currently restricts production builds to `https://staging2.passpermisfacile.fr`. That is a staging deployment target, not evidence of real production readiness. A separate final production API host would require an explicitly approved config change.
- [API-base module](C:/wamp64/www/MaleeqPermiFAst/src/config/api.js) requires an absolute configured HTTP(S) URL and has no silent local/staging fallback. [shared Axios client](C:/wamp64/www/MaleeqPermiFAst/src/helpers/http.jsx) appends `/api`, sends credentials and the app Sanctum bearer token.
- Current local environment points to `https://localhost:8000`; production-mode environment points to staging2. No local server/TLS response was verified. If the local server is plain HTTP, that HTTPS setting would need correction through a separately approved minimal configuration change; do not disable TLS verification.
- The requested fix-pass production build regenerated local dist output and passed (2,382 modules). No deployment/upload, staging/production write or published-bundle inspection occurred.
- New React RdvPermis source/API/test/config files and production environment file are currently untracked. They must be intentionally reviewed and packaged, not assumed to exist on staging because they exist locally.
- [frontend Apache rewrite asset](C:/wamp64/www/MaleeqPermiFAst/public/htaccess) is named `htaccess`, not `.htaccess`. Verify the actual deployment renames/configures SPA history fallback; an ordinary file with that name is not automatically Apache's default per-directory configuration. No server configuration was changed.

### Laravel

- Local application environment is `local` and APP_URL is `http://localhost`. OAuth redirect is hosted staging2, frontend return is reactfast, and API/auth/token targets are RECETTE1. This can be valid for the hosted flow but does not by itself make local-started OAuth callbacks safe across separate databases.
- [CORS configuration](C:/wamp64/www/Passpermislaravel/config/cors.php) includes localhost:5173, reactfast and staging2, credential support, API paths, and no exposed response headers. [Sanctum configuration](C:/wamp64/www/Passpermislaravel/config/sanctum.php) is environment-driven; local configured stateful domains include the frontend/staging hosts. Actual server response headers/cookie/token behavior were not checked remotely.
- Shared database cache and database queue are configured locally. Worker uptime, scheduled tasks, shared deployment cache, mail delivery, config/route caches, remote migration state and current deployed commit are unverified.
- Current local token is expired/non-refreshable by metadata. Local `status=connected` is insufficient. Hosted school/connection in the brief remains a separate user-reported observation.
- Both working copies contain pre-existing modified/untracked work. The original audit counted 11 backend and 31 frontend tracked modified files before this pass; these are historical working-copy counts, not deployed-version evidence. Section 13 lists this pass's files; no unrelated edits were reverted.
- Older local RECETTE status/checklist/stabilization documents have stale statements (v1 examples, old callback/Swagger blockers, no React dashboard). Use this evidence-qualified report/current source, not unchecked checklist boxes, as the current audit baseline. Do not interpret the old checklist as completed deployment actions.

### Reverb / unrelated live issue boundary

Current local frontend Reverb host is `127.0.0.1`, port 8080, HTTP. [Echo config](C:/wamp64/www/MaleeqPermiFAst/src/chat/echo.js) consumes Vite variables and can inherit local environment values during a production-mode build when not overridden by the production file. That creates a **build/deployment risk** of browsers trying their own loopback host. Backend local Reverb loopback can itself be intentional behind a proxy; it is not automatically wrong.

No current remote browser/socket failure was independently observed here. Confirm published bundle host/scheme, reverse proxy and wss connectivity before release. Reverb carries chat/realtime events, not the RdvPermis HTTPS mandate request, so it does **not explain `CANDIDAT_INCONNU`**.

## 15. Remaining Work

### Completed local scope

Permutation, the original three scoped lint errors, raw/per-slot validation/207 rendering and confirmed-only messages, all-page local student lookup, candidate/expiry/removal/reset state, requested mocked action tests, fresh full isolated backend tests, production build and affected reports are complete. **No enumerated local fix remains open.**

### Provider / acceptance dependencies

| Priority | Item | Required evidence |
| --- | --- | --- |
| P1 | Current synchronized RECETTE candidate/demande package and approved B candidate | Environment/export generation, exact surname/NEPH, active B demande, account/email suitability |
| P1 | Current authenticated v2 contract | Actual create/validation/207/error schemas; replacement discovery; any required trainer/threshold/employee/crenodispo operations |
| P1 | Target deployment connection | Same-deployment state/callback DB, granted scopes, usable token and school read-back |
| P1 | Approved live mandate-to-exam acceptance | Mandate -> basket -> eligible assignment -> validation -> matching created exam; partial/uncertain results reconciled before retry |
| P1 | Approved management acceptance | Permutable pairs/allowance reads, then controlled cancellation/permutation/replacement and final state |
| P2 | Production onboarding/release approval | Current URLs, credentials, callback registration, scopes, conditional IP/egress policy, provider approval and target operations |

No unsupported provider route or automatic mutation retry is planned. Header-only creation/CORS and dedicated exam-bound replacement discovery remain conditional contract questions.

### Intentionally outside this pass

Unrelated 468-error frontend backlog, exact-minute administrative lesson cancellation parity, broader schema/theory setup, optional official LN/full provider-local-exam synchronization and unrelated release operations were not authorized for implementation. They are not fixed, and not falsely relabelled as Ministry data problems. Whole-project lint/CI remains red despite green scoped RdvPermis checks.

## 16. Recommended Next Steps

1. Obtain current synchronized RECETTE1 candidate/demande data, one approved B candidate and a versioned authenticated v2 OpenAPI export, including account/email/reset-generation conditions.
2. Confirm provider-contract-dependent response shapes, partial-basket reconciliation, replacement discovery and whether optional trainer/threshold/employee/slot operations are required before more provider-specific coding.
3. Separately authorize target connection checks: same deployment/database for connect/callback, granted scopes/shared cache/refresh and provider school read-back.
4. With explicit approval and disposable RECETTE data, run mandate -> basket -> slot -> eligible assignment -> validation -> matching exam read-back. Never blindly retry an uncertain/partial response.
5. Independently approve/test cancellation/permutation/replacement, their final states and provider notifications.
6. Confirm production onboarding/URLs/credentials/callback/scopes/conditional IP policy and target operations. Decide separately whether the unchanged whole-project lint/CI backlog must be resolved before releasing the complete SPA.
7. Keep this local fix-pass scope closed unless current documentation or a failing acceptance check establishes a concrete additional requirement. Do not downgrade v2, redesign OAuth or invent operations to inflate a completion number.

## 17. Completion Percentages

Planning estimates, not measured coverage, all-provider-operation completeness or production certification.

| Measure | Revised estimate | Basis |
| --- | --- | --- |
| Overall project | **~70%**, unchanged | Unrelated scope/schema/application/CI issues are outside this narrow pass. |
| RdvPermis B-first code | **~95%**, previously ~85% | Enumerated local fixes complete and known workflow mocked checks pass. Further provider-specific changes depend on current v2 contract/acceptance. Optional LN/full local-exam sync are not counted as implemented. |
| Authorized local fix-pass checklist | **100% complete** | Correct permutation, zero scoped lint, safe detailed validation, requested UI tests, full isolated backend/frontend checks, build and reports. Not every provider capability. |
| Automated-testing maturity | **~90%**, previously ~75% | Fresh 209 tests/1,039 assertions and 32 frontend checks including 18 real-component workflows. Not a line/branch coverage measurement; protected full-app/live acceptance remains separate. |
| Live RECETTE verification | **~30% user-reported; 0 newly independently verified** | Same eight reported checkpoints out of the initial 27-checkpoint planning sample. Mocks add no live proof. |
| Production readiness | **~35%**, cautious external-gate judgment | Current contract/data, live mutation acceptance, production entitlement/configuration and target operations remain unverified; whole-project lint/CI remains red. |

No identified code fix remains in the authorized local list. These estimates do not certify optional integrations or authorize deployment.

## 18. Final Conclusion

**Permutation fixed:** defined selected source/target values explicitly mapped to examenId1/examenId2, preserving the existing Laravel/provider contract. Confirmation, busy/double-submit, failure and post-success refresh have mocked UI/backend regressions.

**Validation fixed:** envelope, slot outcomes and nested diagnostics retained. HTTP 207/refusal/unknown states are distinct; no full booking success without explicit positive per-item confirmation. Student paging, strict candidate selection, expiry, assignment removal and next-basket state are covered.

**Final local results:** backend **209 tests / 1,039 assertions passed**; frontend **32/32 passed**; scoped lint **0 errors / 0 warnings**; requested production build **passed**. Full-project unrelated lint remains **468 errors / 26 warnings**, listed separately.

**Code estimate:** ~95% of B-first integration scope; 100% of this enumerated local fix pass. No further local fix is planned within that authorized list. A current v2 contract or failing acceptance test may establish a concrete provider-specific change; no unsupported routes were invented.

**Still required from provider/Ministry:** current RECETTE candidates/demandes and an approved B candidate; current authenticated v2 schemas/rules; confirmation of any optional operations; target connection acceptance and separately approved successful booking/management tests; production URLs/credentials/callback/scopes/conditional IP policy/onboarding. CANDIDAT_INCONNU remains a reported business rejection, with stale data a hypothesis rather than established certainty.

**Deployment boundary:** no known remaining scoped RdvPermis compile/lint/mock-test blocker. Whole-SPA/production approval is not established: unrelated lint/CI, protected app/role/environment checks and provider live acceptance remain open. Broader optional features were not silently declared complete.

No .env change, application DB-data change, staging2/production write, real provider mutation or deployment occurred. Only scoped source/tests/reports and requested local build output changed.

[Main report](C:/wamp64/www/Passpermislaravel/RDVPERMIS_FULL_WORK_REPORT.md)  
[Unrelated lint files/counts](C:/wamp64/www/MaleeqPermiFAst/docs/RDVPERMIS_UNRELATED_LINT_REPORT.md)
