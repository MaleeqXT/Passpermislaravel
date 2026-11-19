# RdvPermis API contract (RECETTE)

Captured from the authenticated RECETTE Swagger UI on **2026-11-19**:

- UI: `https://api.integediteurs.rdv-permis.interieur.gouv.fr/swagger-ui/index.html` (HTTP Basic protected)
- Definition: `RdvPermis - Auto-École` → `/v3/api-docs/RdvPermis - Auto-École`
- `openapi: 3.1.0`, `info.title: permisdeconduire.gouv.fr : API RdvPermis pour auto-écoles`, `info.version: v0.0.2`
- Raw snapshot kept next to this file: [`rdvpermis-auto-ecole-openapi.json`](rdvpermis-auto-ecole-openapi.json) (48 operations). It is the source of truth; this file is a reading aid.
- Other Swagger groups: `Livret Numérique`, `RdvPermis - Candidat`, `Référentiels`, `ff4j`, `FF4J`. They were searched for trainer/threshold/declaration operations (see §1).

Capture method: read-only `GET` of the OpenAPI JSON only. **No RECETTE business endpoint was called.**

All operations below declare `security: [{"Access Token": []}, {"Formation": []}]`.

Provider error codes are listed exactly as Swagger writes them in the response descriptions. Swagger does not publish a structured error-body schema for these responses.

---

## 1. Trainer declaration / update

**There is no trainer declaration or update operation in the current Swagger.** All groups were searched for `formateur|seuil|declar|déclar`.

The trainer operations that do exist are read-only:

| Group | Method | Path | operationId |
|---|---|---|---|
| Auto-École | GET | `/api/v2/auto-ecole/affectations-formateurs` | `récupérerLesFormateurs` (tag `Formateur`) |
| Livret Numérique | GET | `/api/v2/livret-numerique/affectations-formateurs` | `récupérerLesFormateursDéclarés` |

`GET /api/v2/auto-ecole/affectations-formateurs`
- Parameters: none. Request body: none.
- 200 → `array<FormateurAffectationApi>`:
  - `id` string
  - `codeDepartement` string (e.g. `"034"`)
  - `formateur` → `FormateurApi { id: uuid, nom, prenom, autorisation }`
  - `etp` number(double)
  - `nbHeuresFormation` number(double)
  - `groupePermis` enum `A | B | CE`
  - `dateHeureSuppression` date-time (UTC)
- Error responses: none documented.

## 2. Trainer threshold (seuil) by department

`GET /api/v2/auto-ecole/informations-seuil-departement` — operationId `récupérerInformationsDuSeuilFormateur`, tag `Seuil formateur`

| Query param | Required | Type |
|---|---|---|
| `date` | yes | string(date) `yyyy-MM-dd`, any day of the month |
| `code-departement` | yes | string |
| `groupe-permis` | yes | enum `A | B` |

Swagger text: "if no group is given, group B is used". The parameter is still marked `required: true`.

- Request body: none.
- 200 → `OutputSeuilFormateur`:
  - `date` date
  - `nombreDeFormateurs` number(float)
  - `nombreExamensReservablesAvantSeuil` int32
  - `nombrePlacesReservees` int32
  - `seuilNombreExamensParMois` int32
  - `estSeuilAtteint` boolean
  - `estPlafondAtteint` boolean
  - `estPrioriteDepartementale` boolean
- 400 → `DATE_INVALIDE` or `PARAMETRE_INVALIDE` (bad date), `CODE_DEPARTEMENT_INVALIDE` (department invalid or inactive).

## 3. Heavy-vehicle candidate search

`POST /api/v2/auto-ecole/candidats-groupe-lourd/recherche` — operationId `récupérerCandidatsGroupeLourdParAutoEcole`, tag `Candidat`

- Parameters: none.
- Request body (required) → `RechercheCandidatEtPaginationApi`:
  - `filtre` → `FiltreCandidatApi` (optional; omit it to get the full paginated list)
    - `groupePermis` **required**, enum `A | B | CE`. Swagger text: it "must always be 'CE' here".
    - `nom` → `{ query: string, match: PARTIAL | EXACT }` (optional)
    - `numeroDossier` → `{ query: string, match: PARTIAL | EXACT }` (optional)
  - `page` int32, default 1
  - `parPage` int32, default 25, must be between 1 and 100
- Search behaviour (from Swagger): case- and accent-insensitive; no minimum length; a partial match is a prefix match.
- 200 → `PaginationApiAutoEcoleCandidatApi`:
  - `data[]` → `AutoEcoleCandidatApi`:
    - `candidat` → `{ id, nom, prenom, numeroDossier, dateNaissance, email }`
    - `mandat` → `{ id, candidatId, autoEcole{id, raisonSociale}, groupePermis }`
    - `penalites[]` → `{ id, motif: ABSENCE|ANNULATION|ECHEC_EPREUVE_PRATIQUE, dateExamen, dateDeFin, groupePermis, typeEpreuvePratique: PLATEAU|CIRCULATION, dureeEnJour, statut: LEVÉE|ACTIVE, dateDeLevee }`
    - `detailsResultats[]` → `{ categoriePermis: A|A1|A2|B|BE|B1|C|CE|C1|C1E|D|DE|D1|D1E, derniereDateExamenTheorique, derniereDateExamenPlateau, statutExamenPlateau, derniereDateExamenCirculation, statutExamenCirculation, nombreEchecsCirculation, nombreEchecsTotal, seuilCritiqueNombreEchecsAtteint, examenJourJ, envoyeAuSnpc, resultatCirculationSupprime, resultatPlateauSupprime }`
    - Exam status enum: `EXAMEN_REUSSI | EXAMEN_ECHOUE | CANDIDAT_ABSENT | EXAMEN_ANNULE | CANDIDAT_NON_RECEVABLE | CANDIDAT_NON_EXAMINABLE`
  - `pagination` → `{ nombreTotalPages, pageCourante, parPage, nombreTotalElements, nombreElementsPageCourante }`
- 400 → `PARAMETRE_INVALIDE`.

## 4. Add several slots to a basket

`POST /api/v2/auto-ecole/paniers/{panierId}/creneaux-multiples` — operationId `ajouterCreneauAUnPanierApi_1`, tag `Nouveau Panier`

The deprecated v2 single-basket route `POST /api/v2/auto-ecole/panier/creneaux-multiples` must not be used.

- Path: `panierId` (required, string).
- Query: `inclureEstCandidatObligatoire` (optional boolean, default `false`).
- Request body (required) → `AjouterCreneauxMultiplesApi`:
  - `creneauxId` **required**, `array<string>`
- Slots are added one by one. A slot that fails is skipped and the next one is tried.
- 200 and 207 → `CreneauxMultiplesNouveauPanierApi`:
  - `crenauxIdsAjoutesAuPanierAvecSucces[]` and `crenauxIdsAjoutesAuPanierEnEchec[]`. The provider misspells these as `crenaux…`, so keep that spelling exactly.
  - `panier` → `PanierApi`:
    - `id`, `autoEcoleId`, `employeAutoEcoleId`
    - `dateHeureCreation`, `dateHeureExpiration` (+30 min)
    - `elementsDuPanier[]` → `{ creneau: CreneauNouveauApi{id, centreId, numeroInspecteur, dateHeureDebut, dateHeureFin, groupePermis, typeEpreuvePratique: PLATEAU|CIRCULATION|null}, candidat: CandidatApi, estCandidatObligatoire: boolean|null }`
  - 207 means some slots were added and some failed.
- 400 → nothing added, because one of these was already true: the basket is full (36 slots), the trainer threshold was reached, or the department maximum was reached.
- 403 → the feature is disabled.
- 404 → the ID list is empty or longer than **12**, or the basket does not exist.
- ⚠ Swagger contradicts itself on the batch size. The description says "36 max" (that is the basket capacity), while the 404 text says the list must not exceed 12. Treat 12 as the per-call limit.

## 5. Basket validation

`POST /api/v2/auto-ecole/paniers/{panierId}/valider` — operationId `validerLePanier`, tag `Nouveau Panier`

The deprecated route `POST /api/v2/auto-ecole/panier/validation` must not be used.

- Path: `panierId` (required). No query parameters. No request body.
- Precondition: at least one slot must have a candidate assigned. Each reserved exam sends a summons email.
- 200 and 207 → `ValidationPanierApi`:
  - `panierId`
  - `codeMessage` enum `PANIER_VALIDE_ET_SUPPRIME | PANIER_EN_ERREUR`
  - `message`
  - `contientAuMoinsUneErreur` boolean
  - `data[]` → `ValidationPanierCreneauApi { creneauId, examenId, codeMessage, message, estReserve }`
- Per-slot `codeMessage` enum (schema): `CRENEAU_RESERVE, CANDIDAT_SOUS_PENALITE, CANDIDAT_PAS_SOUS_MANDAT, VALIDATION_PANIER_TARDIVE, CRENEAU_DU_PANIER_SANS_CANDIDAT, CANDIDAT_EN_ATTENTE_DE_RESULTAT, EXAMEN_DEJA_PREVU_POUR_CANDIDAT, INSCRIPTION_EXAMEN_CIRCULATION_IMPOSSIBLE, ERREUR_INTERNE, VALIDATION_PANIER_CRENEAU_NON_DISPONIBLE, CANDIDAT_SANS_MANDAT_POUR_GROUPE_DEMANDE`.
- ⚠ The 207 description also lists `CANDIDAT_AYANT_DEJA_OBTENU_SON_EXAMEN` and `CANDIDAT_SANS_DEMANDE_ACTIVE`, which are **not** in the schema enum. Treat `codeMessage` as an open string.
- 403 → `ACTION_INTERDITE` (the basket belongs to another driving school).
- 404 → `PANIER_INCONNU`.

## 6. Exam dates

`GET /api/v2/auto-ecole/examens/dates` — operationId `listerLesDatesDesProchainsExamensProgrammés`, tag `Examen`

| Query param | Required | Type |
|---|---|---|
| `statut` | yes | enum `PROGRAMME` |
| `date-debut` | yes | string(date) `yyyy-MM-dd`, not in the past |
| `groupe-permis` | yes | enum `A | B | CE` |
| `creneauSansCandidat` | no | boolean, default `false` |

- Request body: none.
- 200 → a list of `yyyy-MM-dd` dates: at most 50, in ascending order.
  - ⚠ The schema says `type: string`, but the example is `["2021-08-30"]` and the description says "list". Treat it as `array<string(date)>`.
- 400 → `STATUT_EXAMEN_NON_AUTORISE`, `DATE_DANS_LE_PASSE`.

## 7. Paginated exams

`GET /api/v2/auto-ecole/examens-pagines` — operationId `récupérerExamensPaginés`, tag `Examen`

| Query param | Required | Type |
|---|---|---|
| `groupe-permis` | yes | enum `CE` only |
| `statut` | yes | enum `PROGRAMME` only |
| `date-debut` | no | string(date) |
| `page` | no | int32, default 1 |
| `par-page` | no | int32, default 20 (no maximum published) |

- Request body: none.
- 200 → `PaginationApiExamenApi`:
  - `data[]` → `ExamenApi`:
    - `id`
    - `creneau` → `CreneauApi{ centre{id, nom}, numeroInspecteur, id, dateHeureDebut, dateHeureFin, groupePermis, statutDeReservation: DISPONIBLE|RÉSERVÉ_PAR_MON_AUTO_ÉCOLE|RÉSERVÉ_PAR_AUTRE_AUTO_ÉCOLE|SEUIL_ATTEINT, typeEpreuvePratique: PLATEAU|CIRCULATION|null }`
    - `candidat` → `CandidatApi`
    - `estModifiable` boolean
    - `estAnnule` boolean
  - `pagination` → the same `Pagination` object as §3
- 400 → `STATUT_EXAMEN_NON_AUTORISE`, `GROUPE_PERMIS_NON_GERE`.

## 8. Exam candidate validation (register a candidate on a scheduled exam)

`PUT /api/v1/auto-ecole/examen-programme/{examenId}/candidat/{candidatId}/validation` — operationId `inscrireUnCandidatAUnExamenProgrammé`, tag `Examen`

**This is a v1 path.** It is the only operation of this kind in the current Swagger.

- Path: `examenId` (required string), `candidatId` (required string). No query parameters. **No request body.**
- Scope: registers an eligible candidate on an already-reserved exam of **group A** only.
- 200 → candidate registered. **No response body is documented.**
- 403 → `FONCTIONNALITE_DESACTIVEE`.
- 422 → one of:
  - Candidate: `CANDIDAT_INCONNU, CANDIDAT_SOUS_PENALITE, CANDIDAT_DEJA_INSCRIT, CANDIDAT_EN_ATTENTE_DE_RESULTAT, CANDIDAT_DEJA_DANS_PANIER, CANDIDAT_NON_ELIGIBLE, CANDIDAT_SANS_DEMANDE_ACTIVE, CANDIDAT_EN_DISPENSE_PRATIQUE, CANDIDAT_A_DEJA_OBTENU_SON_PERMIS, CANDIDAT_SANS_MANDAT_POUR_GROUPE_DEMANDE`
  - Exam: `EXAMEN_INCONNU, EXAMEN_AYANT_DEJA_UN_CANDIDAT_INSCRIT, EXAMEN_DEJA_ANNULE, EXAMEN_PASSE, EXAMEN_PLATEAU_DEJA_REUSSI`
  - Other: `INSCRIPTION_EXAMEN_INTERDIT_PAR_AUTO_ECOLE, INSCRIPTION_EXAMEN_CIRCULATION_IMPOSSIBLE, CATEGORIE_PERMIS_NON_GEREE`

## 9. Permutation

`POST /api/v2/auto-ecole/examens/permutation` — operationId `permuterExamens`, tag `Examen`

- Request body (required) → `ExamenIdsAPermuterApi { examenId1 (required string), examenId2 (required string) }`.
- Rules: both exams must be scheduled and in the future. Today's exams cannot be swapped, nor tomorrow's once it is past noon.
- 204 → swapped, no body. Both candidates receive a new summons email.
- 400 → `EXAMEN_INCONNU`.
- 403 → `ACTION_INTERDITE`.
- 422 → `PERMUTATION_IMPOSSIBLE`.

Companion read: `GET /api/v2/auto-ecole/examens/permutables`
- Query: `examen-permute-id` (required), `date` (required, date).
- 200 → `array<ExamenApi>`.
- 400 → `EXAMEN_INCONNU`. 403 → `ACTION_INTERDITE`. 422 → `EXAMEN_NON_MODIFIABLE`, `EXAMEN_NON_NOMINATIF`.

## 10. Replacement

`POST /api/v2/auto-ecole/examens/remplacement` — operationId `remplacerExamen`, tag `Examen`

- Request body (required) → `RemplacementExamenApi { candidatRemplacantId (required string), examenId (required string) }`.
- Rules: the same timing restriction as permutation.
- 204 → replaced, no body. The previous candidate receives a cancellation email; the new one receives a summons.
- 400 → `EXAMEN_INCONNU`, `CANDIDAT_INCONNU`.
- 403 → `ACTION_INTERDITE`.
- 422 → `CANDIDAT_SOUS_PENALITE, CANDIDAT_DEJA_INSCRIT, CANDIDAT_DEJA_DANS_PANIER, CANDIDAT_EN_ATTENTE_DE_RESULTAT, NOMBRE_DE_REMPLACEMENTS_AUTORISES_ATTEINT, CANDIDAT_PAS_SOUS_MANDAT, EXAMEN_NON_MODIFIABLE, CANDIDAT_AYANT_DEJA_OBTENU_SON_EXAMEN, CANDIDAT_SANS_DEMANDE_ACTIVE`.

Companion read: `GET /api/v2/auto-ecole/examens/nombre-de-remplacement`
- Query: `date` (required, date), `groupe-permis` (required, enum `A | B`).
- 200 → `RemplacementCandidatParAutoEcoleApi { remplacementsParMois: int32, remplacementsRestants: int32 }`.
- 400 → invalid `groupePermis`.

---

## Comparison with the Laravel implementation

| # | Operation | Laravel | Status |
|---|---|---|---|
| 1 | Trainer declaration/update | Nothing; read-only `AutoEcoleService::trainerAssignments()` → `GET /api/v2/auto-ecole/affectations-formateurs` | ✅ Match. There is no write operation in Swagger, so there is nothing to implement. The read path matches. |
| 2 | Threshold by department | `AutoEcoleService::trainerThreshold()`, `RdvPermisController::trainerThreshold()`, local route `GET /api/rdvpermis/seuil-formateur?date&codeDepartement&groupePermis` | ✅ Implemented 2026-11-19. All three parameters are required; `groupePermis` must be A or B. |
| 3 | Heavy-vehicle search | `CandidateEligibilityService::searchHeavyVehicle()` + `HeavyVehicleCandidateController::search()` | ✅ Validated since 2026-11-19. When `filtre` is present, `filtre.groupePermis` must be CE; `nom` and `numeroDossier` take `{query, match: PARTIAL\|EXACT}`; `page` ≥ 1; `parPage` 1..100. Only validated fields are forwarded, and an empty search is sent as `{}`. |
| 4 | Add several slots | `PanierService::addSlots()`, `PanierController::addSlots()` | ✅ The path, the `inclureEstCandidatObligatoire` query, the `{creneauxId: []}` body, the `max:12` limit and the 207 pass-through all match. |
| 5 | Basket validation | `PanierService::validate()` | ✅ The path matches, with no body and 200/207 passed through. |
| 6 | Exam dates | `ExamService::dates()`, `ExamController::dates()` | ✅ The query names (`statut=PROGRAMME`, `groupe-permis`, `date-debut`, `creneauSansCandidat`) and the `A,B,CE` enum match. Minor: `dateDebut` is not checked against "not in the past" locally; the provider returns `DATE_DANS_LE_PASSE`. |
| 7 | Paginated exams | `ExamService::paginated()`, `ExamController::paginated()` | ✅ `groupe-permis=CE`, `statut=PROGRAMME`, `date-debut`, `page` and `par-page` all match. |
| 8 | Exam candidate validation | `ExamService::registerCandidate()`, `ExamController::registerCandidate()`, local route `PUT /api/rdvpermis/examens/{examenId}/candidat/{candidatId}/validation` | ✅ Implemented 2026-11-19. It calls the v1 PUT with no body; the 200 response has no documented body. |
| 9 | Permutation | `ExamService::permute()` (+ `permutables()`) | ✅ The body `{examenId1, examenId2}` and the query `examen-permute-id`, `date` match. |
| 10 | Replacement | `ExamService::replace()` (+ `replacementAllowance()`) | ✅ The body `{examenId, candidatRemplacantId}` and the allowance query `groupe-permis` (A,B), `date` match. |

Error-code mapping (`ApiClient::businessMessage()`): since 2026-11-19 every 400 and 422 code listed above has a dedicated French message. `FONCTIONNALITE_DESACTIVEE` is a 403, and `ApiClient` deliberately keeps the generic 403 message for all 403 responses.

Frontend (`MaleeqPermiFAst/src/services/rdvPermisApi.js`) exposes `trainerThreshold(params)` and `registerExamCandidate(examId, candidatId)`. No screen uses them yet.

## Mandate (`POST /api/v2/auto-ecole/mandats`) — local field mapping

Swagger body `CreerMandatApi` has four fields:
- `nom`, `numeroDossier` and `groupePermis` are required. The two strings have `minLength` 1; `groupePermis` is `A | B`.
- `email` is required only when the candidate has no RdvPermis account. PassPermis always requires it locally.

| Provider field | Local source | Rule |
|---|---|---|
| `nom` | `users.last_name` | Surname only. Never the first name or the full name. |
| `numeroDossier` | `students.neph` | Sent as a string. Only whitespace is trimmed and collapsed; the format is otherwise kept. |
| `email` | `users.email` | Trimmed and must be a valid address. |
| `groupePermis` | constant `B` | There is no local permit column. `students.boite_type` is never used. |

- The mapping is implemented in `StudentMandateService::payloadFor()`. Nothing else is sent: not `students.id`, `first_name`, `name` or `boite_type`.
- The React mirror is `src/rdvpermis/mandatePayload.js`.
- In local development (`APP_ENV` local or testing), `GET /api/admin/students/{student}/rdvpermis/mandate/preview` returns `{payload}` from the same builder. It returns 404 in every other environment.
- Reference student LUCIE SIMON → `{"nom":"SIMON","numeroDossier":"8710857 7602","email":"simon.lucie.83@exemple.fr","groupePermis":"B"}`.

## Swagger inconsistencies to keep in mind

1. `examens/dates` 200: the schema says `type: string`, but the example and description say it is a list.
2. `creneaux-multiples`: the description says "36 max", while the 404 text says "maximum de 12".
3. `paniers/{id}/valider` 207: the description lists two codes that are absent from the `codeMessage` enum.
4. `informations-seuil-departement`: `groupe-permis` is required, but the text describes a B default.
5. Exam candidate validation is still a **v1** path, while everything else is v2.
