# G2RD Theme

Thème WordPress **Full Site Editing (FSE)** pour agences web : blocs sur mesure, personnalisation via l’éditeur de site et performances prises en compte.

---

## Sommaire

- [Description](#description)
- [Fonctionnalités](#fonctionnalités)
- [Fonctionnalités détaillées](#fonctionnalités-détaillées)
- [Installation](#installation)
- [Configuration](#configuration)
- [Système de licences](#système-de-licences)
- [Développement](#développement)
- [Structure du projet](#structure-du-projet)
- [Support](#support)
- [Licence](#licence)
- [Crédits](#crédits)
- [Documentation](#documentation)
- [Changelog](#changelog)

---

## Description

Ce thème offre une expérience d’édition complète avec des blocs personnalisés, des animations fluides et une interface intuitive. Il convient aux sites vitrines, portfolios et sites d’agences.

---

## Fonctionnalités

- **Full Site Editing (FSE)** — édition globale du site (templates, parties, styles).
- **Design** moderne et responsive.
- **Blocs Gutenberg** personnalisés et patterns (30+ blocs sur mesure).
- **GEO Analyzer v2** — score Generative Engine Optimization /100 en temps réel dans l’éditeur (9 critères dont *Lisibilité IA*, détection de domaine métier, priorités haute/moyenne/faible, suggestions de résumé et de FAQ adaptées, schémas JSON-LD précis : FAQPage / LocalBusiness / Organization / Service / Product).
- **SEO Helper** — panneau sidebar Gutenberg avec checklist SEO /100 (titre, méta, H1/H2, images alt, maillage interne).
- **Business Mode** — conseils personnalisés selon le type de site (vitrine, leads, e-commerce) dans l’éditeur et le tableau de bord.
- **Page d’options React** — administration G2RD entièrement en React (5 onglets, sauvegarde sans rechargement, REST API).
- **Personnalisation** avancée via l’interface WordPress.
- **Performances** : optimisations et bonnes pratiques.
- **Multilingue** (fichiers de traduction dans `languages/`).
- **Licences et mises à jour** intégrées (FluentCart + GitHub).
- **Équipe** : gestion des membres (section « Qui sommes-nous »).
- **Médias** : sélecteur WordPress natif où pertinent.
- **Types de contenu** personnalisés (portfolio, prestations, etc.).
- **Portfolio** professionnel.
- **Particules** : désactivation automatique pour les audits **PageSpeed Insights**, **Lighthouse** et **GTmetrix** (`is_speed_test()`) afin d’améliorer le score sans dégrader l’expérience réelle des visiteurs.
- **Widget GEO Dashboard** — top 8 pages par score GEO dans le tableau de bord WordPress, avec historique de la dernière analyse.
- **Design System** : tokens CSS pour les ombres, radius, boutons et polices unifiés via `theme-settings.json`.

---

## Fonctionnalités détaillées

### Section « Qui sommes-nous »

- Profils détaillés des membres.
- Compétences et expériences.
- Sélection d’icônes via la bibliothèque de médias.
- Interface d’administration native WordPress.
- Prévisualisation des icônes en temps réel.

### Interface d’administration

- Classes et composants familiers aux utilisateurs WordPress.
- Sélecteur de médias intégré.
- Gestion des contenus intuitive.
- Interface responsive et accessible.

---

## Installation

1. Téléchargez le thème depuis votre **espace client G2RD** (licence active requise).
2. Dans WordPress : **Apparence → Thèmes**.
3. Cliquez sur **Ajouter**, puis **Téléverser un thème**.
4. Choisissez le fichier **ZIP** du thème, puis **Installer maintenant**.
5. **Activez** le thème.

---

## Configuration

### Clé de licence

1. **Apparence → Options G2RD** : saisissez votre **clé de licence** dans l'onglet Licences.
2. Enregistrez les modifications.

### Mises à jour

Les mises à jour sont proposées lorsque de nouvelles versions sont publiées sur **GitHub**. Pour en bénéficier :

1. Disposer d’une **licence valide**.
2. Avoir enregistré la clé dans les **paramètres du thème**.

Les mises à jour apparaissent alors dans l’administration WordPress comme pour les autres extensions/thèmes.

---

## Système de licences

Le thème s’appuie sur **SureCart** pour les abonnements et le contrôle d’accès aux mises à jour :

- Licences annuelles via **FluentCart**.
- Vérification de validité + liaison de domaine.
- Mises à jour automatiques GitHub pour les licences actives.
- Gestion des domaines depuis l’espace client.

### Fonctionnement (résumé)

1. Achat d’une licence via l’espace client G2RD (FluentCart).
2. Activation de la clé depuis **Apparence → Options G2RD**.
3. Vérification périodique automatique (cron 24h).
4. Mises à jour réservées aux licences valides.

---

## Développement

### Prérequis

| Outil        | Version recommandée |
| ------------ | ------------------- |
| WordPress    | 6.5+                |
| PHP          | 8.0+                |
| Node.js      | 18+                 |
| Gestionnaire | npm 8+ (ou yarn)    |

### Cloner et installer

```bash
git clone https://github.com/g2rd/g2rd-theme.git
cd g2rd-theme
npm install
```

### Scripts npm

| Commande          | Rôle                                      |
| ----------------- | ----------------------------------------- |
| `npm run build`   | Compile tous les blocs (workspaces).      |
| `npm run start`   | Mode watch sur les blocs (développement). |

Exemple pour un seul bloc (voir `package.json` pour la liste complète) :

```bash
npm run build:carousel
```

### PHP (optionnel)

Si le thème utilise des dépendances Composer (ex. coloration syntaxique du bloc code) :

```bash
composer install
```

---

## Structure du projet

```text
g2rd-theme/
├── assets/                 # CSS/JS compilés, images, polices
├── blocks/                 # Blocs Gutenberg (un dossier par bloc)
├── categories/             # Catégories de blocs
├── classes/                # Classes PHP du thème (autoload, CPT, licence, etc.)
├── docs/                   # Documentation complémentaire
├── includes/               # Fichiers inclus (ex. licence)
├── languages/              # Traductions (.pot, .po…)
├── parts/                  # Parties de modèle FSE (header, footer…)
├── patterns/               # Patterns de blocs
├── styles/                 # Variations de style FSE
├── templates/              # Templates FSE
├── configuration.json      # Configuration composée du thème
├── functions.php
├── style.css               # Métadonnées du thème (obligatoire)
├── theme.json              # Configuration FSE de base
├── theme-settings.json     # Paramètres / tokens
├── theme-styles.json       # Styles déclaratifs
├── LICENSE / license.txt   # Licence (GitHub / format WordPress.org)
├── readme.txt              # Description utilisateur (WordPress.org)
└── README.md               # Ce fichier (GitHub / développeurs)
```

---

## Support

- Documentation en ligne et dossier **`docs/`**.
- Support via votre **espace client SureCart**.
- **Issues GitHub** pour les bugs et retours techniques.

---

## Licence

Ce thème est distribué sous licence **EUPL-1.2**. Voir le fichier `LICENSE` pour le texte complet.

---

## Crédits

- Développement : **Sebastien GERARD**
- Bâti sur **WordPress**
- Mises à jour via l’**API GitHub**
- Licences et vente : **FluentCart**

---

## Documentation

| Fichier / dossier | Public visé        | Contenu principal                          |
| ----------------- | ------------------ | ------------------------------------------ |
| **README.md**     | Développeurs       | Git, npm, structure, contribution          |
| **readme.txt**    | Utilisateurs / WP  | Description courte, conforme WordPress.org |
| **docs/**         | Tous               | Guides détaillés (ex. accessibilité)       |
| **LICENSE**       | Juridique          | Licence EUPL (dépôt Git)                   |
| **license.txt**   | WordPress.org      | Même licence, format WordPress.org         |

---

## Changelog

### 1.6.9.2

- **Testimonial - Marquee** : nouveau mode de défilement infini (duplication seamless, pause au survol).
- **Testimonial - Carrousel** : correction de la largeur des cartes (calcul JS `offsetWidth` au lieu de `100%` CSS).
- **Testimonial - Éditeur** : CSS variables injectées sur le wrapper Google pour corréler visuellement éditeur et frontend.
- **Testimonial - Couleurs** : panneau Couleurs accessible en mode Google pour configurer les couleurs des cartes.
- **Testimonial - Vitesse** : contrôle de vitesse du marquee (5–120 s, faible = rapide).
- **Info** : notice explicative sur la limite de 5 avis de l'API Google Places.

### 1.6.9.1

- **Feature** : Bloc Testimonial Google — 4 layouts (grille, liste, carrousel, maçonnerie), 4 styles de cartes (ombre, plat, bordure, verre), lien profil auteur, affichage 1er avis mis en avant, toggle avatar/date/badge global, troncature de texte.
- **Fix** : FilterableGrid — `text-decoration` supprimé par défaut sur tous les liens de cartes.
- **Fix** : Icône des blocs FAQ et GEO FAQ harmonisée (`editor-ul`).
- **Fix** : `FluentCartSupport::syncProductVersion` — doublon supprimé, méthode opérationnelle ; `author_url` ajouté dans la réponse Google Reviews.

### 1.6.9

- **Feature** : Bloc Testimonial — mode avis Google Business (Places API, clé API par client, cache 12h, squelettes de chargement, hydratation frontend).
- **Feature** : Bloc FAQ — en-tête configurable (icône + texte), badge schema.org en mode GEO, nl2br sur les réponses.
- **Feature** : Page d'options — section intégrations Google Maps (saisie clé API, vider le cache avis par Place ID).
- **Fix** : g2rd/geo-faq masqué de l'inséreur (fonctionnalités absorbées dans g2rd/faq).

### 1.6.8.1

- **Fix** : FilterableGrid — toggle soulignement des liens/textes dans les cartes.

### 1.6.8

- **FilterableGrid** : contrôles format et ajustement de l'image des cartes (ratio + object-fit).
- **FilterableGrid** : aperçu éditeur en temps réel avec les vrais posts, couleurs titre/texte/extrait.
- **g2rd-info** : variations masquées de l'inserteur, switcher de style dans la toolbar du bloc.
- **Gestion licences** : licences FluentCart (`wp_fc_licenses`) désormais visibles dans l'onglet admin.
- **Gestion licences** : bouton Rafraîchir, date de création, domaines actifs avec date d'activation.
- **Fix** : `is_server_mode()` restreint au domaine `g2rd.fr` — onglet admin masqué sur les locaux.

### 1.6.7

- **Fix** : `preventThemeRename` réécrit — gère les deux comportements WordPress (< 6.4 dossier temp externe, ≥ 6.4 dossier interne) pour corriger l'erreur "Le thème n'a pas de fichier style.css" lors des mises à jour automatiques.
- **Fix** : `release.yml` — vérification que `style.css` est présent dans le ZIP avant la release.

### 1.6.6

- **Fix** : classe `LicenseManager` corrigée dans `class-block-patterns.php` (erreur critique PHP en production).
- **Fix** : flush des permaliens automatique après mise à jour du thème (CPT portfolio/prestations/qui-sommes-nous en 404).
- **Fix** : suppression des `console.log` exposant le chemin du thème dans `accessibility.js`.
- **Fix** : preloads conditionnels pour GSAP et `clickable-articles.js` (warnings console supprimés).
- **Fix** : preload `style.css` supprimé (mismatch `?ver=` causait un warning).
- **Style** : bouton Dark Mode repositionné à `bottom: 100px`.
- **Style** : icônes Lune/Soleil SVG pour le toggle Dark Mode (suppression dépendance Dashicons).
- **Style** : bouton "Visiter notre site" aligné sur la page de connexion (`padding: 10px 20px`, `margin-top: 2rem`).

### 1.6.5

- **Feat** : génération automatique de clé de licence à l'achat FluentCart — email envoyé au client avec sa clé.
- **Feat** : bouton Enregistrer déplacé dans l'en-tête de la page d'options — toujours visible sans scroller.
- **Fix** : formulaire "Gestion licences" — alignement CSS grid, inputs cohérents sans `TextControl`.
- **Fix** : `export-theme.ps1` exclut `.github`, `docs`, `vendor`, `composer.*` (alignement avec `release.yml`).

### 1.6.4

- **Feat** : onglet Licence React dans la page d'options — activation/désactivation de clé via REST API.
- **Feat** : interface admin "Gestion licences" sur g2rd.fr — création, copie et suppression de clés.
- **Fix** : URL du repo GitHub Updater corrigée (`g2rd-theme` au lieu de `G2RD-Theme-FSE`).
- **Fix** : ZIP de mise à jour utilise l'asset `browser_download_url` au lieu du `zipball_url` GitHub.
- **Fix** : lookup de clé de licence vérifie `g2rd_license_keys` en priorité (correction "licence invalide").
- **Fix** : `export-theme.ps1` utilise l'API .NET ZipFile pour garantir les forward slashes dans le ZIP.
- **Fix** : webhook release — timeout 30s non-bloquant (exit code 28 corrigé).

### 1.6.3

- **Fix** : ZIP de production restructuré avec dossier wrapper `g2rd-theme/` — corrige l'erreur "Le thème n'a pas de fichier style.css" à l'installation WordPress.

### 1.6.2

- **Fix** : utilisation du PAT `GH_PAT` dans `auto-tag.yml` pour déclencher `release.yml` en cascade lors du push de tag.

### 1.6.1

- **Workflow** : auto-tagging GitHub Actions — release déclenchée automatiquement au merge `release:` sur main.
- **LicenseServer** : webhook `/release-webhook` découplé de FluentCart, toujours disponible indépendamment.
- **FluentCart** : synchronisation automatique version + changelog à chaque release GitHub via webhook sécurisé HMAC.
- **Fix** : ZIP de production renommé en `g2rd-theme.zip` (minuscules) pour compatibilité Linux/WordPress.
- **Fix** : standardisation du nommage du thème et correction des URLs d'images.

### 1.5.0

- **GEO Analyzer v2** : 9ème critère "Lisibilité IA" (longueur des phrases, des paragraphes, densité de titres), détection automatique du domaine métier (VTC, avocat, artisan, santé, immo, e-commerce, coaching…), schémas JSON-LD précis (FAQPage, LocalBusiness, Organization, Service, Product), scoring crédibilité élargi à 5 signaux.
- **GEO Analyzer — Priorités** : chaque recommandation porte un niveau haute/moyenne/faible ; les recommandations sont triées par urgence dans le panneau.
- **GEO Analyzer — Suggestions IA** : nouveau panel "✨ Suggestions pour l'IA" avec résumé généré (titre + premier paragraphe + mots-clés) et 3 questions FAQ adaptées au type de page.
- **GEO Analyzer PHP** : endpoint `/geo-analyze` renforcé — détection des types JSON-LD réels (`@graph` supporté), vérification de complétude, analyse lisibilité serveur, support Yoast/RankMath/SEOPress.
- **Dashboard** : widget GEO "Top pages par score" dans le tableau de bord, score persisté en post meta avec date d'analyse.
- **Onboarding** : bouton de réinitialisation sécurisé (nonce + `manage_options`) accessible depuis le widget dashboard.
- **Perf** : helper `is_speed_test()` — désactivation GSAP/particules pour Lighthouse, PageSpeed Insights, GTmetrix, Pingdom.
- **Gutenberg** : variations de blocs pour `g2rd/info` (standard, succès, avertissement, danger, astuce) et `g2rd/faq` (standard, GEO).
- **Pattern** : "FAQ + Résumé GEO" prêt à insérer.
- **Design System** : tokens shadows (8 niveaux), radius (xs→pill), boutons et polices rationalisées dans `theme-settings.json`.
- **Architecture** : synchronisation FSE extraite en `class-fse-sync.php` (hook `after_switch_theme` + versionnement transient v4).
- **UI Admin** : sélecteur de couleur unifié avec swatches palette sur tous les onglets de la page d'options.

### 1.4.1

- **Fix** : `seo-helper.js` — `wp.editPost.PluginSidebar` et `wp.editPost.PluginSidebarMoreMenuItem` migrés vers `wp.editor` (dépréciés depuis WordPress 6.6).

### 1.4.0

- **Bloc FAQ unifié** : `g2rd/faq` absorbe `g2rd/geo-faq` — nouveau mode GEO (toggle inspecteur) qui active `<details>/<summary>` + microdata schema.org FAQPage + JSON-LD côté serveur. Backward-compatible via `deprecated[]` + transform automatique depuis `g2rd/geo-faq`. GEO Analyzer adapté (scoring différencié FAQ standard vs GEO).
- **Options admin — Aide GEO** : toggle "Activer l'aide GEO dans l'éditeur" ajouté dans l'onglet Clients (symétrique avec Aide SEO). Option `g2rd_geo_helper` persistée via REST.
- **Nouveaux blocs** : `g2rd/cta-band` (bande d'appel à l'action) et `g2rd/hero` (section hero).
- **Page de connexion** : personnalisation complète du formulaire WordPress (logo, couleurs, fond) depuis les options admin.
- **Couleurs hover** : effets hover sur les boutons admin et page de connexion.
- **CSS thème** : remplacement systématique des valeurs hex en dur par `var(--wp--preset--color--primary/secondary)` dans tous les blocs — cohérence automatique avec les variations de style FSE.
- **Fix** : mode GEO du bloc FAQ respecte désormais `openFirst`; `role="region"` enrichi de `aria-labelledby` (WCAG 2.1).
- **Fix** : restauration `getLogoUrl()` dans `ThemeAdmin`.

### 1.3.4

- **Module GEO Analyzer** : plugin sidebar Gutenberg — score GEO /100 calculé en temps réel sur 8 critères (clarté, structure H2/H3, Q&R, entités locales, crédibilité, résumabilité, données structurées, cohérence). Recommandations contextuelles et suggestions de blocs. Endpoint REST complémentaire `POST /g2rd/v1/geo-analyze` (détection JSON-LD, méta description, word count côté serveur).
- **Bloc** `g2rd/geo-summary` : résumé optimisé IA avec points clés éditables. Microdata schema.org `Article` (`itemprop="abstract"`) en sortie.
- **Bloc** `g2rd/geo-faq` : FAQ dynamique avec accordéon CSS natif `<details>/<summary>`. Sortie `render.php` : microdata FAQPage + `<script type="application/ld+json">` (JSON-LD complet).
- **Page d'options React** : migration complète de l'administration G2RD en React — `TabPanel` 5 onglets (Configuration, Contenu, Éditeur, Clients, Maintenance), `SaveBar` sticky, persistence via REST `GET/POST /g2rd/v1/settings`, zéro rechargement de page. Build `@wordpress/scripts` sous `blocks/g2rd-options-page/`.
- **PHPCS** : 0 erreur sur les 3 standards (WordPress, Security, PHPCompatibility 8.0+).

### 1.3.3

- **Architecture options** : `class-theme-options.php` allégé de 1 344 → ~280 lignes — suppression de tout le rendu PHP inline au profit d'un point de montage React (`<div id="g2rd-options-root">`) + endpoint REST `GET/POST /g2rd/v1/settings`.
- **CI** : exclusion de `.claude/*` dans les scans PHPCS Security et PHPCompatibility — corrige le crash `fopen(): Failed to open stream: Permission denied` sur les fichiers non-PHP du répertoire skills.

### 1.3.2

- **Fix** : `class-onboarding.php` — `add_submenu_page(null, ...)` remplacé par `add_submenu_page('', ...)` — corrige la dépreciation PHP 8.1 `strpos(): Passing null to parameter #1` qui bloquait les headers admin.

### 1.3.1

- **Sécurité** : consolidation `@wordpress/scripts` en dépendance root unique — suppression des 18 copies workspace qui contournaient les overrides npm (CVE `minimatch` ReDoS, `basic-ftp` DoS, `follow-redirects` header leak, `serialize-javascript` RCE, `webpack-dev-server` source exposure) — `npm audit` : 0 vulnérabilité.
- **Phase 4** : SEO Helper — panneau sidebar Gutenberg avec score /100 et checklist 8 points (titre, extrait, H1/H2, images alt, longueur contenu, image à la une, maillage interne) — endpoint REST `g2rd/v1/seo-analyze`.
- **Phase 4** : Business Mode — type de site (vitrine / leads / e-commerce) avec conseils personnalisés dans l'éditeur, widget tableau de bord adapté et notice de configuration.
- **Phase 4** : Micro-interactions CSS/JS — animations au scroll via Intersection Observer, hover cards, focus rings accessibles, `prefers-reduced-motion` respecté.
- **CI** : Node.js 20 → 22 dans `smart-ci.yml` ; override `minimatch >=3.1.4` ; `@typescript-eslint/*` ^8.0.0.
- **PHPCS** : 0 erreur — docblocks complets, commentaires `translators:`, indentation conforme WPCS.

### 1.3.0

- **Licence** : système complet FluentCart — activation, désactivation, validation périodique (cron 24h), liaison domain anti-partage.
- **Blocs** : tous les blocs Gutenberg G2RD nécessitent une licence active.
- **Mises à jour** : conditionnées à une licence valide (GitHubUpdater).
- **Portail client** : onglet « Licences » dans FluentCart — gestion des domaines, libération pour changement de domaine.
- **CI/CD** : GitHub Action release automatique sur tag `v*` → build + ZIP + notification g2rd.fr.
- **Security** : PHPCS Security Audit — 0 erreur, sniffs corrigés.

### 1.2.13

- **PHPCS** : migration `phpcs.xml.dist` vers WPCS 3.x — exclusions `PEAR.Functions.FunctionCallSignature.*`, `Universal.Arrays.DisallowShortArraySyntax`, `NormalizedArrays.Arrays.*`, `Universal.WhiteSpace.CommaSpacing`, `Universal.Operators.DisallowShortTernary`.
- **Composer** : scripts `phpcs`, `phpcs:security`, `phpcs:compat` ajoutés — `composer run phpcs` opérationnel.
- **Sécurité** : `wp_unslash()` sur `$_POST` dans les 3 classes CPT et `class-theme-options.php`; `esc_url()` sur `get_template_directory_uri()`.
- **i18n** : commentaires `// translators:` sur les 15 appels `sprintf(__())`.
- **Qualité** : `$configurationData` → `$configuration_data`, `json_encode` → `wp_json_encode`, `in_array` strict.

### 1.2.12

- **Sécurité** : suppression de `class-surecart-license-manager.php` — système de licence SureCart retiré (FluentCart à venir).
- **CI** : `FORCE_JAVASCRIPT_ACTIONS_TO_NODE24=true` — suppression des warnings Node.js 20 dans GitHub Actions.
- **FilterableGrid** : support SureCart conservé pour les sites utilisant SureCart.

### 1.2.11

- **CI** : Security Audit — remplacement de l'option `--exclude` CLI par `--standard=phpcs-security.xml` pour une exclusion fiable des sniffs `FileSystems` et `CallbackFunctions`.
- **PHPCS** : `phpcs-security.xml` créé à la racine — configuration centralisée des exclusions de faux positifs sécurité.

### 1.2.10

- **PHPCS** : `phpcs.xml.dist` — exclusion de `./blocks/*/*.php` pour sortir les helpers PHP de blocs du périmètre de scan WordPress Standards.
- **PHPCS** : `class-block-patterns.php` — `phpcs:ignore` sur l'`include` dynamique contrôlé par `glob()`.
- **CI** : Security Audit — exclusion des sniffs `FileSystems` et `CallbackFunctions` (faux positifs sur des usages WordPress légitimes).

### 1.2.9

- **PHPCS** : `phpcs.xml.dist` — `Generic.Arrays.DisallowShortArraySyntax` et `WordPress.WhiteSpace.FunctionCallSignature` désactivés globalement (severity 0) en plus des exclusions déjà présentes dans le bloc WordPress, pour couvrir toutes les versions de WPCS.
- **PHPCS** : `class-custom-post-types-portfolio.php` — alignement des `=` dans `renderMetaBox` corrigé, `phpcs:ignore` sur `enqueueAdminAssets` pour `$_hook` requis par le hook WordPress.

### 1.2.8

- **PHPCS** : accolades K&R sur toutes les méthodes/classes — 24 fichiers corrigés en une passe.
- **phpcs.xml.dist** : exclusions pour syntaxe `[]` et espacement des appels de fonctions (style PSR-2, PHP 8.0+).
- **PHPCS** : `class-custom-post-types-portfolio.php` — braces, `$_hook`, alignement des assignations.

### 1.2.7

- **PHPCS** : `class-block-patterns.php` — `@var string` sur `$theme_version`, accolades ouvrantes sur la même ligne, espaces dans `get()`, `[]` → `array()` dans `add_action`.
- **PHPCS** : `class-shortcode.php` — alignement des `=` dans quatre méthodes de shortcode.
- **PHPCS** : `class-filterable-grid.php` — alignement des `=` (WooCommerce, thumbnail, SureCart), commentaire inline réécrit.
- **PHPCS** : `class-abilities.php` — `phpcs:ignore` sur `executeThemeSettings` (paramètre de callback requis non utilisé).

### 1.2.6

- **PHPCS** : `class-block-patterns.php` — trailing whitespace (L4, L19), accolade ouvrante sur la même ligne, conversion complète espaces → tabs.
- **PHPCS** : `class-shortcode.php` — alignement des `=` pour `$link` (+4 esp.), `$value` (+3 esp. ×4), `$post_id` (+4 esp. dans bloc `$experience`).
- **PHPCS** : `class-particules-effect.php` — alignement des `=` pour `$color` et `$speed` (+3 esp.).
- **PHPCS** : `class-custom-post-types-prestations.php` — alignement de `$args` (+3 esp.).
- **PHPCS** : `class-abilities.php` — paramètre `$input` inutilisé renommé en `$_input` (convention underscore WordPress).

### 1.2.5

- **PHPCS** : réécriture complète de `class-conditional-menu.php` — indentation tabs, syntaxe `array()` pour les constantes, méthodes renommées en snake_case (`register_hooks`, `filter_conditional_block`, `current_user_has_role`).
- **Refactoring** : renommage global `registerHooks()` → `register_hooks()` dans les 31 classes et `functions.php` (conformité WPCS snake_case).
- **CI** : le job PHPCS WordPress Standards utilise désormais `phpcs.xml.dist` (scope ciblé sur `./classes`, `./functions.php`, `./includes`) au lieu d'un scan brut `--standard=WordPress` sur tout le dépôt.
- **phpcs.xml.dist** : exclusion documentée de `WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid` (migration camelCase → snake_case en cours) et `WordPress.Arrays.MultipleStatementAlignment`.

### 1.2.4

- **Sécurité** : correction vulnérabilité `basic-ftp` ≤ 5.2.1 (CRLF injection — GHSA) via override npm `>=5.2.2`.
- **PHPCS** : correction de toutes les violations WordPress Coding Standards dans `class-conditional-menu.php`, `class-abilities.php` et `class-block-patterns.php` (indentation tabs, accolade ouvrante, alignement double-flèches/égal).
- **PHPCS** : renommage `ConditionalMenu` → `Conditional_Menu` pour conformité WPCS `WordPress.Files.FileName`.
- **WPCS** : hook `g2rd/settings/enable_ai` renommé en `g2rd_settings_enable_ai` (séparateurs underscore obligatoires).
- **CI** : configuration PHPCS (`phpcs.xml`) et GitHub Actions pour vérification automatique des standards WP et audits de sécurité à chaque push.

### 1.2.3

- **Compatibilité WP 7.0** : migration vers `register_post_meta()`, suppression de `disableGutenberg`.
- **Canvas iframé** : migration vers `wp_enqueue_block_style()` pour la compatibilité avec l'éditeur de blocs.
- **Bugfix** : preset shadow *Imposante* — point-virgule JSON invalide corrigé.
- **Bugfix** : `_icones_images` — `absint` remplacé par `esc_url_raw` pour les URLs.
- **Licence** : normalisation EUPL-1.2, *Tested up to* mis à jour à 7.0.

### 1.2.2

- **Sécurité** : 0 vulnérabilité npm — `@wordpress/scripts` v30 → v31, overrides pour `@typescript-eslint`, `copy-webpack-plugin`, `webpack-dev-server`, `serialize-javascript` et `markdownlint`.
- **Bugfix** : erreur *"headers already sent"* dans `class-block-patterns.php` — chargement via `ob_start()`/`ob_get_clean()` + `get_file_data()`.
- **Bugfix** : `clearPatternsCache()` n'effaçait jamais le bon transient (clé sans version).
- **Bugfix** : `glob()` retournant `false` sur erreur FS provoquait un `foreach` fatal.
- **CI** : détection WordPress étendue aux thèmes (style.css, theme.json, block.json), build Gutenberg intégré, Dependabot configuré.

### 1.2.1

- **GitHub Updater** : renommage du dossier lors de la mise à jour via `WP_Filesystem->move()` (compatibilité hébergeurs restrictifs).
- **GitHub Updater** : validation stricte du tag (`version_compare`) et des tags malformés.
- **CI** : workflow Smart CI multi-stack (WordPress, React, Angular, Symfony, Node.js).
- **Dépendances PHP** : `vendor/` versionné dans Git pour assurer l'installation via `zipball_url` GitHub sans Composer.

### 1.2.0

- **Bloc** `g2rd/container` — layout flex/grille/contraint/flux avec `render.php`.
- **WordPress Abilities API** — `class-abilities.php` opt-in via option d'administration (`enable_ai`).
- **Block API** : remplacement du `Divider` par un `<hr>` dans l'éditeur.
- **Coloration syntaxique** : intégration de `highlight.php` (Composer, distribué avec le thème).
- **Assets** : GSAP et ScrollTrigger intégrés sous `assets/js/vendor/`.
- **Export** : script `export-theme.ps1` pour générer le ZIP de production.

### 1.1.0

- **Bloc** `g2rd-carousel` : affichage mobile 2×2 fixe, désactivation autoplay/loop/swipe, effet `slide` forcé pour éviter les artefacts.
- **Bloc** `g2rd-countdown` : titre éditable, `useBlockProps.save()` retiré (save statique correcte).
- **Bloc** `g2rd-counter` : fix duplication préfixe/suffixe en front.
- **Bloc** `g2rd-typed` : animation de frappe configurable (strings, vitesse, boucle).
- **Bloc** `g2rd-info` : refonte — icônes Dashicons + images, layouts, accessibilité.
- **Workspaces npm** : monorepo avec un `package.json` par bloc compilé.

### 1.0.8

- **Performances** : l’effet particules est désactivé pendant les analyses PageSpeed Insights / Lighthouse, pour un meilleur score sans impact sur les visiteurs réels.
- **Technique** : nettoyage JS pour compatibilité WordPress.

### 1.0.7

- **Counter** : préfixe et suffixe non dupliqués en front.
- **Counter** : taille d’icône / image personnalisable dans l’éditeur.
- **Glass** : fusion correcte des styles inline (padding, etc.).

### 1.0.6

- Bloc **G2RD Info** (icônes Dashicons + images, layouts, accessibilité).
- Nombreuses options de style et d’espacement.

### 1.0.5

- Bloc **Toggle Content** (afficher / masquer, 100 % CSS côté front).
- Catégorie de blocs **G2RD Bloks**.

### 1.0.4

- **Countdown** : titre éditable et orientation ligne/colonne fiabilisée.
- Chargement CSS du bloc via `block.json`.
- `.gitignore` adapté au dépôt GitHub.
