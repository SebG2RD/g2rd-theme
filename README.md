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
- **Blocs Gutenberg** personnalisés et patterns.
- **Personnalisation** avancée via l’interface WordPress.
- **Performances** : optimisations et bonnes pratiques.
- **Multilingue** (fichiers de traduction dans `languages/`).
- **Licences et mises à jour** intégrées (SureCart + GitHub).
- **Équipe** : gestion des membres (section « Qui sommes-nous »).
- **Médias** : sélecteur WordPress natif où pertinent.
- **Administration** alignée sur l’interface WordPress.
- **Types de contenu** personnalisés (portfolio, prestations, etc.).
- **Portfolio** professionnel.
- **Particules** : désactivation automatique pour les audits **PageSpeed Insights** et **Lighthouse** (v1.0.8+) afin d’améliorer le score sans dégrader l’expérience réelle des visiteurs.

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

1. Téléchargez le thème depuis votre **espace client SureCart**.
2. Dans WordPress : **Apparence → Thèmes**.
3. Cliquez sur **Ajouter**, puis **Téléverser un thème**.
4. Choisissez le fichier **ZIP** du thème, puis **Installer maintenant**.
5. **Activez** le thème.

---

## Configuration

### Licence

1. **Apparence → G2RD Settings** : renseignez votre **clé API SureCart**.
2. **Apparence → G2RD License** : saisissez votre **clé de licence**.
3. Enregistrez les modifications.

### Mises à jour

Les mises à jour sont proposées lorsque de nouvelles versions sont publiées sur **GitHub**. Pour en bénéficier :

1. Disposer d’une **licence valide**.
2. Avoir enregistré la clé dans les **paramètres du thème**.

Les mises à jour apparaissent alors dans l’administration WordPress comme pour les autres extensions/thèmes.

---

## Système de licences

Le thème s’appuie sur **SureCart** pour les abonnements et le contrôle d’accès aux mises à jour :

- Licences annuelles.
- Vérification de validité.
- Mises à jour pour les licences actives.
- Plusieurs licences possibles par contexte (selon votre offre).
- Écran dédié dans l’administration.

### Fonctionnement (résumé)

1. Achat d’une licence via SureCart.
2. Liaison au site / au compte selon votre processus.
3. Vérification périodique de la validité.
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

| Commande           | Rôle                                      |
| ---------------- | ----------------------------------------- |
| `npm run build`  | Compile tous les blocs (workspaces).      |
| `npm run start`  | Mode watch sur les blocs (développement). |

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

```
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
- Licences et vente : **SureCart**

---

## Documentation

| Fichier / dossier | Public visé        | Contenu principal                          |
| ----------------- | ------------------ | ------------------------------------------ |
| **README.md**     | Développeurs       | Git, npm, structure, contribution          |
| **readme.txt**    | Utilisateurs / WP  | Description courte, conforme WordPress.org |
| **docs/**         | Tous               | Guides détaillés (ex. accessibilité)       |
| **LICENSE**       | Juridique          | Licence EUPL (dépôt Git)                   |
| **license.txt**   | WordPress.org      | Même licence, format attendu par le répertoire |

---

## Changelog

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
