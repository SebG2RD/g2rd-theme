# Veille documentaire Rescue 18

Connecteurs officiels DGSCGC, PNRS et ENSOSP. Administration : menu **Automatisations**.

## 1. Inventaire des sources et accès réellement constatés (30 octobre 2026)

Tests faits depuis un poste client, requêtes identifiées « WordPress », 2 à 4 s entre deux appels.

| Source | Pages suivies | Réponse à un serveur | robots.txt | Données exploitables | Verdict |
| --- | --- | --- | --- | --- | --- |
| DGSCGC | GDO, GTO, doctrines et techniques, PIO, mémentos, vadémécums, formation aux emplois | **403, protection Cloudflare (`cf-mitigated: challenge`) sur toutes les pages** | Interdit `/*.pdf$` et `/content/download/` | Liste DSFR : titre, URL du PDF, mois de mise en ligne dans le chemin du fichier, version dans le nom (V2, V4…). Pas de taille sans lire le PDF. | **Bloqué côté serveur** |
| PNRS | Page GDO/GTO, 5 pages thématiques RETEX | 200 ; **429** au-delà d'un débit soutenu | Absent (404) | **Flux RSS 2.0** (`application/xml`) liés sur les pages : n° SYRACUSE stable, titre, date, type, auteur, **éditeur**, résumé | **Fonctionnel** |
| ENSOSP | Liste des actualités | 200 | Sans restriction sur les actualités | Pas de RSS (`/rss.xml`, `/feed` : 404). Liste HTML : titre, date, lien. Article : `og:description`, `og:image`, `og:updated_time` | **Fonctionnel** |
| Journal officiel (DILA) | Module juridique | 200, archives quotidiennes `echanges.dila.gouv.fr/OPENDATA/JORF/` (licence ouverte, aucun compte) | — | XML de chaque texte : nature, numéro, NOR, dates, titre complet, ministère | **Fonctionnel** |

Aucun jeu de données documentaire DGSCGC sur data.gouv.fr.

### Blocage DGSCGC et alternatives autorisées

Le connecteur DGSCGC est codé (analyse DSFR) et tente une requête par cycle. Il affiche **« Bloqué par la source »** tant que Cloudflare refuse l'accès. Il n'y a ni faux navigateur, ni proxy, ni contournement, et aucun PDF n'est téléchargé (robots.txt).

1. **Les GDO/GTO de la DGSCGC arrivent par le PNRS.** Le flux indique l'éditeur « Direction Générale de la Sécurité Civile ». La fiche porte la DGSCGC comme éditeur et le PNRS comme source complémentaire.
2. **L'import assisté.** Dans la carte DGSCGC, colle le code source de la page ouverte dans le navigateur. Le même analyseur crée ou actualise les fiches, avec une option de simulation.
3. **La solution durable.** Demander à la DGSCGC (ministère de l'Intérieur) d'autoriser l'adresse du serveur Rescue 18 ou de publier un flux. Le connecteur fonctionnera alors sans modification.

### Clés RSS du PNRS

Les URL `/Portal/Recherche/Search.svc/SearchRss?key=…` sont **relues sur la page à chaque cycle**, jamais écrites en dur. Les clés sont restées identiques sur plusieurs lectures espacées pendant les tests, mais leur durée de vie n'est pas garantie. Si une clé expire ou si un flux renvoie autre chose qu'un RSS valide, le flux est signalé dans le journal et le connecteur se replie sur les notices SYRACUSE liées dans la page HTML.

### Recherche plein texte dans les PDF

Étudiée, non activée. Le robots.txt de la DGSCGC interdit l'exploration des PDF, et le PNRS ne publie pas de lien PDF direct dans ses flux. La recherche porte sur les titres, résumés et métadonnées. L'extraction pourra se brancher en tâche de fond sur les PDF dont la réutilisation est autorisée.

### Journal officiel

Deux archives tar.gz par jour de parution, environ 15 mois en ligne (premières archives : 13 juillet 2025). Le connecteur lit l'index, puis jusqu'à 60 archives par cycle, les plus récentes d'abord. Seuls les fichiers `texte/version` sont décompressés, en flux. Un texte est retenu si sa nature est loi, ordonnance, décret, arrêté, circulaire ou instruction, s'il a été publié au JO dans le mois de l'archive et si son titre contient un mot-clé (sapeurs-pompiers, sécurité civile, premiers secours, ERP, IGH, feux de forêt…). Nominations, médailles et actes individuels sont écartés. Mots-clés, exclusions et date de départ se règlent dans la carte « Journal officiel » d'Automatisations. Un arrêté qui ouvre un concours ou un examen professionnel crée un brouillon dans le calendrier des concours.

## 2. Fonctionnement

- **Un cycle par 24 h** : hook `r18_daily_document_sync`, à 3 h 17. Ordre de priorité : DGSCGC, PNRS, ENSOSP.
- **Traitement par lots** : `r18_document_sync_batch`, 20 s par lot. Une page source correspond à une unité, et l'état est enregistré après chaque unité.
- **Verrou** contre les exécutions concurrentes. Un cycle en cours bloque tout nouveau cycle. S'il n'a pas avancé depuis 20 minutes, il reprend là où il s'était arrêté.
- **Une erreur sur une source** n'arrête pas les autres. Un 429 ou une protection anti-robots arrête la source jusqu'au prochain cycle. **Aucune fiche n'est jamais supprimée.**
- **HTTP** : `wp_safe_remote_get` (protection SSRF), HTTPS uniquement, domaines autorisés par source, redirections revérifiées, délai de 20 s, taille maximale de 3 Mo, type MIME contrôlé, plafond de requêtes par cycle, ETag et Last-Modified quand la source les fournit.

### Rapprochement et doublons

1. Une fiche est d'abord retrouvée par l'identifiant de sa source (`r18_ref_pnrs` = n° SYRACUSE…).
2. Sinon, par une **clé** : type plus intitulé réduit. « GDO - Interventions en présence de gaz » et « Guide de doctrine opérationnelle (GDO) : interventions en présence de gaz » donnent la même clé. Les fiches importées depuis rescue18.com reçoivent aussi leur clé.
3. Une ressemblance forte mais non exacte (72 % ou plus) n'aboutit à aucune création. Elle devient une **proposition à valider** : rattacher, garder distinct ou ignorer. La décision est mémorisée.

### Versions

Le signal de version est le nom du fichier PDF (DGSCGC), la date de notice (PNRS) ou la date de mise à jour (ENSOSP). La date de la page de liste n'en fait pas partie. Une nouvelle version passe la fiche en « Mis à jour » (ou « En validation ») et alimente l'historique `r18_versions` affiché sur la fiche.

### Corrections éditoriales

Un titre ou un extrait modifié dans WordPress n'est plus réécrit. Les termes sont ajoutés, jamais retirés.

### Publication

Le mode se règle par source : automatique ou soumis à validation (statut « En attente de relecture »). Par défaut, la DGSCGC et le PNRS publient en automatique, l'ENSOSP passe par la validation. Les actualités ENSOSP vont dans la catégorie **Actualités des organismes**, avec un résumé public et un lien vers l'article, sans recopie ni image téléchargée.

## 3. Administration

Menu **Automatisations** :
- une carte par source : état, références suivies, nouveaux, actualisés, erreurs, dernière vérification, prochaine synchronisation, requêtes ;
- les boutons **Tester la connexion** (lecture réelle, sans écriture), **Synchroniser** (source ciblée) et **Simuler** ;
- les réglages : activation, mode de publication, pages suivies (`URL | type`), mots-clés ENSOSP ;
- les **rapprochements à valider**, l'**import assisté** DGSCGC et le **journal** filtrable (300 dernières lignes, lien vers chaque fiche).

Le bouton général **Synchroniser maintenant** lance un cycle complet. La barre de progression fait avancer les lots tant que la page reste ouverte.

### Cron de l'hébergeur (facultatif)

```
define( 'DISABLE_WP_CRON', true );        // wp-config.php
*/5 * * * * wget -q -O - https://rescue18.com/wp-cron.php?doing_wp_cron >/dev/null 2>&1
```

WP-CLI : `wp rescue18 veille [--source=pnrs] [--simulation]`.

## 4. Tests automatisés

```
php tests/veille-test.php
```

94 vérifications sur des extraits réels des sources (`tests/fixtures/`). Elles couvrent les analyseurs, la normalisation, la sécurité HTTP et les scénarios du cahier des charges : premier import, absence de nouveauté, nouveau GDO, nouvelle version GTO, nouveau RETEX, nouvel article ENSOSP, doublon DGSCGC/PNRS, flux invalide, clé expirée, page modifiée, source inaccessible, erreur HTTP, limitation, import partiel, interruption, reprise, publication automatique, validation, corrections éditoriales et simulation.

Trois points se vérifient sur le site, faute de WordPress dans le banc de test : le cron quotidien (prochaine collecte affichée dans Automatisations), la conservation des URL historiques et l'intégrité du thème parent (aucun fichier modifié).
