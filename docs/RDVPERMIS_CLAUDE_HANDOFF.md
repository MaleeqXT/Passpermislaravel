# RdvPermis — Handoff pour Claude

Date du bilan : 17 novembre 2026  
Périmètre : intégration RdvPermis dans Laravel (`Passpermislaravel`) et React (`MaleeqPermiFAst`).

## Objectif et architecture à conserver

Toutes les communications RdvPermis restent côté Laravel :

```text
React -> Laravel /api/rdvpermis/* -> RdvPermis RECETTE v2
```

Ne jamais exposer au navigateur :

- le `client_secret`;
- les access tokens;
- les refresh tokens;
- les codes OAuth.

Le frontend ne doit appeler que les routes Laravel. Il ne doit jamais appeler le ministère directement.

## État synthétique

| Domaine | État | Notes |
| --- | --- | --- |
| OAuth Laravel | Implémenté | State stocké en DB, validation single-use, échange de code côté serveur, refresh automatique et protection 401/429. |
| OAuth local | Bloqué par le fournisseur | Le callback RECETTE enregistré pointe vers `staging2`; le WAF du ministère refuse un callback localhost. Ce n'est pas un bug React. |
| Callback staging/RECETTE | Implémenté | Le callback autorisé doit rester sur l'environnement enregistré. |
| Auto-école courante | Vérifié / implémenté | `GET /api/v2/auto-ecole/moi`; la réponse `formateursParDepartement` est affichée. |
| Affectations formateurs | Implémenté, schéma réponse à confirmer | Lecture via le chemin v2 implémenté; conserver le pass-through de réponse tant que Swagger complet n'est pas recapturé. |
| Recherche candidats | Implémenté | Standard et groupe lourd. |
| Centres | Implémenté | Recherche et détail; ne pas inventer le chemin de recherche tronqué dans les captures Swagger. |
| Planning / créneaux disponibles | Implémenté | Recherche planning v2 et sélection de créneaux disponibles. |
| Paniers | Implémenté | Création, détail, ajout/retrait de créneau, affectation candidat, validation, suppression. |
| Examens | Implémenté | Liste, détail, annulation, permutation, remplacement, nombre de remplacements. |
| Mandats | Implémenté | Liste/recherche et création à partir d'un élève local; une confirmation explicite est requise avant mutation. |
| Déclaration du nombre de formateurs | Non vérifié / non implémenter sans Swagger | L'ancien endpoint évoqué ne doit pas être deviné. |
| Informations seuil département | Non vérifié / non implémenter sans Swagger | Ne pas deviner la route ni les schémas. |

Évaluation historique de l'implémentation source : environ 28/30 opérations connues, soit environ 93 %. Ce nombre ne vaut pas une preuve de réussite RECETTE live.

## Endpoints v2 actuellement vérifiés dans le code / les captures

- `GET /api/v2/auto-ecole/moi`
- `POST /api/v2/auto-ecole/candidats/recherche`
- `POST /api/v2/auto-ecole/candidats-groupe-lourd/recherche`
- `GET /api/v2/auto-ecole/centres/{centreId}`
- `POST /api/v2/auto-ecole/planning/recherche`
- `GET /api/v2/auto-ecole/employes`
- `GET /api/v2/auto-ecole/affectations-formateurs`

`/api/v2/auto-ecole/moi` fournit notamment :

- `id`, `nom`, `emailNotification`, `codePostal`, `estPlateforme`, `ville`, `aurigeId`, `estBloquee`;
- `formateursParDepartement[]` avec `codeDepartement`, `nombreDeFormateursPermisB`, `nombreDeFormateursPermisA`.

Cela résout la lecture des nombres de formateurs déclarés par département. Cela ne prouve pas une capacité de déclaration/mise à jour.

## Composants principaux

Backend :

- `app/Services/RdvPermis/OAuthService.php` : authorization URL, state DB, échange code, protection de callback local incompatible.
- `app/Services/RdvPermis/TokenService.php` : stockage chiffré, refresh, verrou de refresh, erreurs 401/429, état de disponibilité.
- `app/Services/RdvPermis/ApiClient.php` : appels fournisseur Laravel uniquement.
- `app/Services/RdvPermis/AutoEcoleService.php`
- `app/Services/RdvPermis/PlanningService.php`
- `app/Services/RdvPermis/CentreService.php`
- `app/Services/RdvPermis/CandidateEligibilityService.php`
- `app/Services/RdvPermis/CandidateMandateService.php`
- `app/Services/RdvPermis/PanierService.php`
- `app/Services/RdvPermis/ExamService.php`
- `app/Http/Controllers/V1/EndPoint/RdvPermis/`
- `routes/api.php` : groupe `/api/rdvpermis/*` protégé par Sanctum et rôles admin/super-admin/secretary.

Frontend :

- `src/rdvpermis/RdvPermisPage.jsx`
- `src/services/rdvPermisApi.js`
- `src/rdvpermis/RdvPermisPage.css`
- `tests/rdvpermis-workflows.test.js`

## Problème localhost — analyse exacte

La configuration RECETTE utilise un callback approuvé sur l'environnement staging2. Le flux est donc :

```text
localhost crée OAuth state dans la DB locale
  -> RdvPermis RECETTE
  -> callback configuré vers staging2
  -> staging2 ne possède pas le state local
```

Si un callback loopback localhost est forcé, le WAF du ministère répond `403 Forbidden` avant la connexion OAuth. Il ne faut pas contourner ce WAF ni modifier la sécurité du fournisseur.

Comportement local déjà livré :

- un jeton local expiré et non renouvelable est supprimé seulement de la base locale;
- `/api/rdvpermis/status` renvoie une connexion non connectée avec `localOAuthUnavailable: true`;
- l'UI affiche une notice neutre, `Non connecté`, `Configuration complète`, `Callback non autorisé`;
- les actions dépendantes du fournisseur sont désactivées;
- les élèves locaux restent chargeables;
- un lien ouvre l'environnement autorisé pour la connexion, sans transférer de jeton local.

### Pour rendre OAuth réellement fonctionnel sur localhost

Une action externe est obligatoire : enregistrer un second callback HTTPS public dans le client RECETTE, pointant vers un tunnel contrôlé qui redirige vers le Laravel local. Exemple conceptuel :

```text
https://<domaine-tunnel-approuve>/api/rdvpermis/callback
```

Puis, seulement après l'enregistrement officiel, configurer ce même callback sur la machine locale et tester le flux complet. Ne pas remplacer le callback staging/prod existant.

## Travaux restants pour Claude

1. Obtenir une capture/export **actuel** Swagger/OpenAPI v2 authentifié de RECETTE.
2. Vérifier avec cette source l'existence et les schémas exacts de :
   - déclaration du nombre de formateurs;
   - informations de seuil départemental;
   - tout endpoint historique renommé/remplacé pour les créneaux disponibles.
3. Implémenter les deux opérations formateurs seulement après cette vérification, avec requêtes/réponses strictement conformes.
4. Ajouter des tests Laravel HTTP fake dédiés aux deux nouvelles opérations.
5. Réconcilier le planning/créneaux avec le Swagger v2 final sans réécrire le flux fonctionnel qui existe déjà.
6. Faire les vérifications RECETTE contrôlées uniquement avec un candidat/test resource approuvé et jetable :
   - OAuth callback, token, refresh;
   - mandat;
   - planning/créneaux;
   - panier, affectation, validation;
   - examens, annulation, permutation, remplacement;
   - 401 et 429.
7. Pour un vrai OAuth local, obtenir le callback RECETTE HTTPS supplémentaire approuvé. Sans lui, considérer le blocage local comme attendu.

## Tests déjà exécutés avec succès

Dernière vérification locale :

- Laravel OAuth/readiness ciblé : `23` tests, `103` assertions réussies avant le dernier ajustement de lien; ensuite readiness : `12` tests, `73` assertions réussies.
- React RdvPermis : `47` tests réussis.
- Build production React : `npm run build` réussi.

Les avertissements Bugsnag sans connectivité réseau ne sont pas des échecs RdvPermis.

## Règles de sécurité et de non-régression

- Ne pas modifier les redirect URIs production/staging sans approbation explicite.
- Ne pas envoyer de mutations fournisseur live non contrôlées.
- Ne pas ajouter de fallback ou de schéma API "supposé".
- Ne pas faire de `migrate:fresh`, rollback destructif ni test DB non isolé sur la base de développement.
- Ne pas inclure de secrets dans commits, documents, issues ou frontend.
- Le secret OAuth a été collé dans une conversation : le propriétaire doit le faire tourner côté RECETTE, puis le mettre à jour uniquement dans l'environnement serveur approprié.

## Commandes de vérification

Backend, depuis `C:\wamp64\www\Passpermislaravel` :

```powershell
php artisan test tests/Feature/RdvPermisReadinessTest.php tests/Unit/RdvPermisOAuthConfigurationTest.php tests/Feature/RdvPermisOAuthCallbackTest.php --testdox
```

Frontend, depuis `C:\wamp64\www\MaleeqPermiFAst` :

```powershell
npm run test:rdvpermis
npm run build
```

## Définition de terminé

Ne pas déclarer l'intégration 100 % prête pour production avant :

1. confirmation du contrat Swagger/OpenAPI v2 courant;
2. tests automatisés verts;
3. vérification RECETTE réussie pour les flux pertinents;
4. preuve que les routes et données production ne sont pas affectées.

---

# Annexe A — Inventaire complet des routes Laravel exposées

Toutes les routes ci-dessous sont sous `auth:sanctum` et exigent le rôle
`admin`, `super-admin` ou `secretary`, sauf le callback OAuth. Les routes
frontend sont relatives à `/api`.

| Route Laravel | Méthode | Contrôleur / service | Nature |
| --- | --- | --- | --- |
| `/api/rdvpermis/callback` | GET | `RdvPermisController@callback` | Callback OAuth serveur, sans Sanctum |
| `/api/rdvpermis/status` | GET | `RdvPermisController@status` | Lecture locale, aucune requête fournisseur |
| `/api/rdvpermis/connect` | POST | `RdvPermisController@connect` | Démarre OAuth si le callback est compatible |
| `/api/rdvpermis/current-school` | GET | `AutoEcoleService::currentSchool` | Lecture fournisseur |
| `/api/rdvpermis/employees` | GET | `AutoEcoleService::employees` | Lecture fournisseur |
| `/api/rdvpermis/trainer-assignments` | GET | `AutoEcoleService::trainerAssignments` | Lecture fournisseur |
| `/api/rdvpermis/centres/recherche` | POST | `CentreService::search` | Lecture fournisseur |
| `/api/rdvpermis/centres/{centreId}` | GET | `CentreService::find` | Lecture fournisseur |
| `/api/rdvpermis/centres-favoris` | GET | `CentreService::favorites` | Lecture fournisseur |
| `/api/rdvpermis/centres-favoris` | POST | `CentreService::saveFavorites` | Mutation fournisseur |
| `/api/rdvpermis/planning/recherche` | POST | `PlanningService::search` | Lecture fournisseur |
| `/api/rdvpermis/candidats/recherche` | POST | `CandidateEligibilityService::search` | Lecture fournisseur |
| `/api/rdvpermis/candidats-groupe-lourd/recherche` | POST | `CandidateEligibilityService::searchHeavyVehicle` | Lecture fournisseur |
| `/api/rdvpermis/candidats-mandats/recherche` | POST | `CandidateMandateService::search` | Lecture fournisseur |
| `/api/rdvpermis/examens` | GET | `ExamService::all` | Lecture fournisseur |
| `/api/rdvpermis/examens/{examenId}` | GET | `ExamService::find` | Lecture fournisseur |
| `/api/rdvpermis/examens/{examenId}` | DELETE | `ExamService::cancel` | Mutation fournisseur |
| `/api/rdvpermis/examens/permutables` | GET | `ExamService::permutables` | Lecture fournisseur |
| `/api/rdvpermis/examens/nombre-de-remplacement` | GET | `ExamService::replacementAllowance` | Lecture fournisseur |
| `/api/rdvpermis/examens/dates` | GET | `ExamService::dates` | Lecture fournisseur |
| `/api/rdvpermis/examens/pagines` | GET | `ExamService::paginated` | Lecture fournisseur |
| `/api/rdvpermis/examens/remplacement` | POST | `ExamService::replace` | Mutation fournisseur |
| `/api/rdvpermis/examens/permutation` | POST | `ExamService::permute` | Mutation fournisseur |
| `/api/rdvpermis/paniers` | GET | `PanierService::all` | Lecture fournisseur |
| `/api/rdvpermis/paniers` | POST | `PanierService::create` | Mutation fournisseur |
| `/api/rdvpermis/paniers/{panierId}` | GET | `PanierService::find` | Lecture fournisseur |
| `/api/rdvpermis/paniers/{panierId}` | DELETE | `PanierService::delete` | Mutation fournisseur |
| `/api/rdvpermis/paniers/{panierId}/valider` | POST | `PanierService::validate` | Mutation fournisseur / résultat parfois 207 |
| `/api/rdvpermis/paniers/{panierId}/creneaux` | POST | `PanierService::addSlot` | Mutation fournisseur |
| `/api/rdvpermis/paniers/{panierId}/creneaux-multiples` | POST | `PanierService::addSlots` | Mutation fournisseur |
| `/api/rdvpermis/paniers/{panierId}/creneaux/{creneauId}` | DELETE | `PanierService::removeSlot` | Mutation fournisseur |
| `/api/rdvpermis/paniers/{panierId}/creneaux/{creneauId}/candidat` | PUT | `PanierService::assignCandidate` | Mutation fournisseur; `candidatId: null` retire l'affectation |
| `/api/admin/students/{student}/rdvpermis/mandate` | POST | `StudentMandateController::store` | Mutation fournisseur après validation élève local |

## Annexe B — Correspondance Laravel -> fournisseur v2

Les chemins suivants sont effectivement construits dans les services. La
présence d'un chemin dans cette liste ne remplace pas sa confirmation Swagger
pour toute évolution future.

| Service | Chemin fournisseur | Requête construite |
| --- | --- | --- |
| `AutoEcoleService::currentSchool` | `GET /api/v2/auto-ecole/moi` | aucune |
| `AutoEcoleService::employees` | `GET /api/v2/auto-ecole/employes` | aucune |
| `AutoEcoleService::trainerAssignments` | `GET /api/v2/auto-ecole/affectations-formateurs` | aucune; réponse transmise telle quelle |
| `AutoEcoleService::createMandate` | `POST /api/v2/auto-ecole/mandats` | payload candidat/mandat préparé localement |
| `CentreService::search` | `POST /api/v2/auto-ecole/centres/recherche` | `{ filtre: { codeDepartement, groupePermis, estFerme? } }` |
| `CentreService::find` | `GET /api/v2/auto-ecole/centres/{centreId}` | aucune |
| `CentreService::favorites` | `GET /api/v2/auto-ecole/centres-favoris` | aucune |
| `CentreService::saveFavorites` | `POST /api/v2/auto-ecole/centres-favoris` | payload passé sans transformation |
| `PlanningService::search` | `POST /api/v2/auto-ecole/planning/recherche` | `{ filtre: { groupePermis, date, centreId } }` |
| `CandidateEligibilityService::search` | `POST /api/v2/auto-ecole/candidats/recherche` | critères transmis |
| `CandidateEligibilityService::searchHeavyVehicle` | `POST /api/v2/auto-ecole/candidats-groupe-lourd/recherche` | JSON transmis volontairement sans schéma inventé |
| `CandidateMandateService::search` | `POST /api/v2/auto-ecole/candidats-mandats/recherche` | critères transmis |
| `PanierService::*` | `/api/v2/auto-ecole/paniers*` | voir Annexe C |
| `ExamService::*` | `/api/v2/auto-ecole/examens*` | voir Annexe C |

## Annexe C — Contrats actuellement codés : paniers et examens

### Panier

- Création : `POST /api/v2/auto-ecole/paniers`, sans JSON si aucun employé
  n'est choisi; sinon `{ "employeAutoEcoleId": "..." }`.
- Ajout : `POST /api/v2/auto-ecole/paniers/{id}/creneaux` avec
  `{ "creneauId": "..." }`; le query paramètre
  `inclureEstCandidatObligatoire` n'est envoyé que lorsqu'il est défini.
- Ajout multiple : même règle avec `{ "creneauxId": ["..."] }`.
- Affectation : `PUT .../creneaux/{creneauId}/candidat` avec
  `{ "candidatId": "..." }`; `null` désaffecte.
- Validation : `POST .../valider`. Un HTTP 207 est une réponse fonctionnelle
  par créneau, pas un succès global automatique.
- Expiration : elle appartient au fournisseur. Le frontend ne l'invente pas;
  il respecte `dateHeureExpiration`, puis les aliases reconnus.

### Examens

- Liste : `GET /api/v2/auto-ecole/examens` avec `statut=PROGRAMME`,
  `groupe-permis`, `date`.
- Annulation : `DELETE /api/v2/auto-ecole/examens/{id}`.
- Permutables : `GET .../examens/permutables` avec `examen-permute-id`, `date`.
- Nombre de remplacements : `GET .../examens/nombre-de-remplacement` avec
  `groupe-permis`, `date`.
- Remplacement : `POST .../examens/remplacement` avec
  `{ examenId, candidatRemplacantId }`.
- Permutation : `POST .../examens/permutation` avec `{ examenId1, examenId2 }`.

Ne changer aucun de ces payloads sans le Swagger v2 courant et des tests HTTP
fake reproduisant le nouveau contrat.

## Annexe D — OAuth, stockage et token management

### Données persistées

- `rdvpermis_tokens` : une ligne par utilisateur Laravel. `access_token`,
  `refresh_token` et `scopes` sont chiffrés via les casts Eloquent et cachés
  lors de la sérialisation.
- `rdvpermis_oauth_states` : `state_hash` seulement, jamais le state clair;
  `expires_at`, `consumed_at`, utilisateur associé.

### Cycle OAuth

1. `POST /api/rdvpermis/connect` valide la configuration.
2. `OAuthService::authorizationUrl()` crée un state aléatoire 64 hex, ne
   stocke que SHA-256, avec TTL par défaut de 10 minutes.
3. Le navigateur est redirigé vers l'autorisation RECETTE.
4. Le callback Laravel vérifie/consomme le state une seule fois grâce à un
   update conditionnel DB.
5. Le code est échangé côté serveur en `application/x-www-form-urlencoded`.
6. Le token est enregistré avec une marge de 30 secondes avant expiration.
7. Le navigateur est redirigé vers le frontend avec seulement
   `?rdvpermis=connected` ou `?rdvpermis=error`; ni code ni token n'est inclus.

### Refresh automatique

- `ApiClient` demande le token via `TokenService::accessToken()`.
- Si nécessaire, le refresh est protégé par un lock cache par utilisateur.
- Les tokens rotatifs sont relus après lock afin d'éviter l'usage d'une copie
  stale.
- `invalid_grant`, `invalid_token`, refresh absent ou refresh expiré mènent à
  `reconnect_required`.
- HTTP 401 fournisseur marque aussi la connexion à renouveler.
- HTTP 429 met en place un cooldown en respectant `Retry-After` quand présent.

### Statut local

`GET /api/rdvpermis/status` ne transmet jamais de credential. Il expose un
statut de disponibilité (`connected`, `not_connected`,
`connection_unverified`, `reconnect_required`) et les métadonnées de sécurité
nécessaires à l'UI.

Sur localhost avec callback non autorisé, un token irrévocablement inutilisable
est supprimé seulement de la DB locale. Les déploiements non locaux ne passent
pas dans ce nettoyage.

## Annexe E — Gestion des erreurs

`ApiClient` transforme les erreurs fournisseur en `RdvPermisApiException` et
les contrôleurs les retournent sans fuite de secrets :

| Fournisseur | Réponse Laravel / UX |
| --- | --- |
| 400 / 422 | Données métier refusées; code fournisseur conservé si non sensible |
| 401 | Connexion à renouveler; token marqué pour réauthentification |
| 403 | Compte non autorisé sur la ressource |
| 404 | Ressource fournisseur introuvable |
| 409 | Conflit fournisseur |
| 429 | Cooldown + `Retry-After` exposé |
| 5xx / réseau | 502 ou 503 générique, journal technique redigé |

Les diagnostics de logs masquent tokens, secrets, email, NEPH, candidat et
champs PII. Ne pas réduire cette redaction.

## Annexe F — Parcours frontend RdvPermis

`RdvPermisPage.jsx` contient :

1. statut OAuth et bouton de connexion;
2. auto-école courante et compteurs `formateursParDepartement`;
3. affectations de formateurs et employés;
4. centre -> planning -> créneau;
5. vérification d'éligibilité candidat;
6. élèves locaux et mandat avec confirmation;
7. panier -> ajout créneau -> affectation -> validation;
8. examens -> détail -> annulation/permutation/remplacement.

Les mutations ont une boîte de confirmation. Les actions fournisseur sont
bloquées quand aucune connexion RdvPermis utilisable n'est présente. Les élèves
locaux peuvent rester accessibles même si RdvPermis est hors connexion.

Le module `src/services/rdvPermisApi.js` est la frontière React : il ne contient
que des appels first-party `/api/...` et ne doit jamais recevoir de token
fournisseur.

## Annexe G — Tests existants et lacunes

### Tests backend présents

- `tests/Unit/RdvPermisOAuthConfigurationTest.php`
- `tests/Feature/RdvPermisOAuthCallbackTest.php`
- `tests/Feature/RdvPermisReadinessTest.php`
- tests de services et contrôleurs RdvPermis voisins dans `tests/`

Ils couvrent notamment : state inconnu/expiré/rejoué, échange sans fuite de
tokens, absence de client secret, callback, refresh/readiness, nettoyage local
expiré, 401/429 selon les services et absence d'appel HTTP imprévu.

### Tests frontend présents

- `tests/rdvpermis-workflows.test.js` : parcours Playwright mocké;
- `tests/rdvpermis-oauthPopup.test.js`;
- `tests/rdvpermis-slotSelection.test.js`;
- `tests/rdvpermis-validation.test.js`;
- `tests/rdvpermis-localCalendarDate.test.js`.

Ils couvrent le flux centre/créneau, panier, validation 200/204/207, expiration,
mutation avec confirmation, mandat, erreurs, permutation, remplacement, et le
statut OAuth local indisponible.

### Lacunes à traiter après Swagger

1. HTTP fakes spécifiques à la déclaration du nombre de formateurs.
2. HTTP fakes spécifiques au seuil départemental.
3. Contrats précis de réponse d'affectations formateurs.
4. Tests RECETTE avec données jetables approuvées.

## Annexe H — Checklist de reprise Claude

Avant toute modification :

- [ ] Lire ce document et inspecter le diff existant; les deux worktrees sont
      déjà sales avec des changements hors RdvPermis qui ne doivent pas être
      écrasés.
- [ ] Ne pas utiliser les anciennes routes v1 ou la documentation PDF comme
      preuve du contrat v2 courant.
- [ ] Obtenir Swagger UI/OpenAPI RECETTE authentifié et capturer les opérations
      exactes visées.
- [ ] Comparer chemin, verbe HTTP, query params, body, champs requis, réponses
      et erreurs avant de coder.

Pour chaque nouvelle opération :

- [ ] ajouter une méthode service très ciblée;
- [ ] ajouter contrôleur et route Laravel protégés;
- [ ] ajouter méthode dans `rdvPermisApi.js`;
- [ ] ajouter UI minimale sans modifier les flux existants;
- [ ] ajouter test HTTP fake Laravel;
- [ ] ajouter test React si l'UI ou l'état change;
- [ ] exécuter les commandes de vérification ci-dessus.

Avant test live mutation :

- [ ] avoir un candidat/panier/créneau RECETTE jetable approuvé;
- [ ] logguer uniquement statut, chemin et diagnostics redigés;
- [ ] ne pas réutiliser une ressource réelle;
- [ ] documenter précisément succès, rejet métier ou blocage externe.

## Annexe I — Résultat attendu à la prochaine pass

Le prochain agent doit pouvoir fournir, avec preuves :

```text
Authentication: XX%
Candidate / Mandates: XX%
Planning / Available Slots: XX%
Panier: XX%
Candidate Assignment: XX%
Booking Validation: XX%
Exam Management: XX%
Trainer Thresholds: XX%
Frontend UI: XX%
Error Handling: XX%
Token Management: XX%
Automated Tests: XX%
RECETTE Live Verification: XX%

SOURCE IMPLEMENTATION: XX%
PRODUCTION READINESS: XX%
```

Ne jamais indiquer 100 % de production readiness tant que le Swagger v2 courant
et les parcours RECETTE pertinents n'ont pas été validés.
