# RdvPermis — checklist de mise en service

Cette checklist ne contient aucune valeur secrète. Les opérations d'écriture
RdvPermis doivent être faites par un utilisateur habilité et confirmées dans
l'interface.

## Configuration et OAuth

- [ ] Vérifier que les variables `RDVPERMIS_*` requises sont présentes dans
  l'environnement ciblé, sans les afficher.
- [ ] Confirmer que l'URL de callback déclarée est l'URL HTTPS du même
  environnement Laravel.
- [ ] Vider puis reconstruire les caches Laravel après tout changement de
  configuration.
- [ ] Ouvrir la connexion OAuth depuis le tableau de bord et vérifier le statut
  `connected` après le retour du fournisseur.

## Base de données et jobs

- [ ] Appliquer les migrations RdvPermis avant d'activer le tableau de bord.
- [ ] Vérifier que `rdvpermis_tokens`, `rdvpermis_oauth_states` et les tables
  de synchronisation sont disponibles.
- [ ] Vérifier que le chiffrement Laravel fonctionne avec la clé de
  l'environnement ; ne jamais recréer ou tourner cette clé pour RdvPermis.
- [ ] Vérifier la configuration de cache et de sessions partagée si plusieurs
  workers servent l'application.

## Réseau et TLS

- [ ] Confirmer depuis le serveur applicatif que le certificat TLS du fournisseur
  est validé avec le bundle CA actif.
- [ ] Ne pas activer `verify=false` ni désactiver la vérification de certificat.
- [ ] Confirmer les règles de sortie réseau et les éventuelles exigences IP du
  fournisseur depuis le serveur qui effectue réellement les appels.

## Vérification RECETTE sans écriture

- [ ] Vérifier le statut et l'auto-école courante.
- [ ] Rechercher un centre puis afficher son détail.
- [ ] Rechercher le planning et un créneau.
- [ ] Vérifier l'éligibilité du candidat pour ce créneau.
- [ ] Lister les examens, afficher un détail et les options de remplacement ou
  permutation si elles sont disponibles.
- [ ] Lister les paniers et contrôler leur expiration.

## Avant la première écriture

- [ ] Vérifier les données locales du candidat : nom, NEPH texte, e-mail et
  groupe B.
- [ ] Rechercher les mandats existants afin d'éviter un doublon.
- [ ] Vérifier à nouveau l'éligibilité juste avant l'affectation.
- [ ] Lire le résultat de validation par créneau : une réponse HTTP 207 est un
  résultat métier partiel, pas une réussite complète.
- [ ] Actualiser le panier et la liste des examens après une validation.

## Exploitation

- [ ] Surveiller les réponses 401, 403, 409, 422, 429, 502 et 503 sans journaliser
  de jeton, secret client ou corps fournisseur sensible.
- [ ] Mettre en place un délai avant réessai sur 429 selon `Retry-After`.
- [ ] Archiver les résultats métier utiles sans enregistrer de données OAuth.
