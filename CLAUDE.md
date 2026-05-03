# G2RD Theme FSE

Thème WordPress Full Site Editing développé par G2RD Agence Web.

## Stack technique

- WordPress 6.5+ avec Full Site Editing
- PHP 8.0+ (WordPress Coding Standards)
- JavaScript ES modules (ESNext) + React/JSX pour les blocs Gutenberg
- `@wordpress/scripts` pour le build (webpack)
- npm workspaces (monorepo) : un package par bloc compilé
- Git + GitHub (mises à jour via GitHub Updater)
- Node.js >= 18.0.0, npm >= 8.0.0

## Structure du projet

```text
g2rd-theme/
├── assets/
│   ├── css/          # Styles compilés du thème
│   ├── js/           # Scripts compilés du thème (+ vendor/ pour GSAP, ScrollTrigger)
│   ├── images/       # Images du thème
│   └── fonts/        # Polices custom
├── docs/             # Documentation du thème
├── languages/        # Fichiers de traduction (.po, .pot, .mo)
├── blocks/           # Blocs Gutenberg custom (un dossier par bloc)
│   ├── blocks-manifest.php          # Auto-généré par build-blocks-manifest
│   ├── shared/
│   │   └── PostSelector.js          # Composant partagé entre blocs
│   │
│   ├── — Blocs pré-compilés (pas de src/, assets directs) —
│   ├── AdvancedList/
│   ├── Breadcrumb/
│   ├── DeviceMockup/
│   ├── g2rd/                        # Bloc générique (index.js + block.json)
│   ├── IconBox/
│   ├── Map/
│   ├── Marquee/
│   ├── Modal/
│   ├── ProgressBar/
│   ├── ShareButtons/
│   ├── Slider/
│   ├── SlidingPanel/
│   ├── TableOfContents/
│   ├── ToggleContent/
│   ├── Toolbars/
│   │
│   ├── — Blocs avec npm workspace (src/ + build/) —
│   ├── CardG2rd/
│   ├── CodeG2rd/
│   ├── FilterableGrid/
│   ├── PricingTable/
│   ├── g2rd-advanced-heading/
│   ├── g2rd-block-api/
│   ├── g2rd-carousel/
│   ├── g2rd-charts/
│   ├── g2rd-container/              # Layout flex/grille/contraint/flux — bloc dynamique
│   ├── g2rd-countdown/
│   ├── g2rd-counter/
│   ├── g2rd-dynamic-content/
│   ├── g2rd-faq/
│   ├── g2rd-hero/                     # Bloc hero — section d'en-tête avec image et CTA
│   ├── g2rd-info/
│   ├── g2rd-testimonial/              # Bloc témoignages clients
│   ├── g2rd-cta-band/                 # Bloc bande CTA — bandeau d'appel à l'action
│   ├── g2rd-typed/
│   │
│   ├── — Modules GEO (npm workspace, src/ + build/) —
│   ├── g2rd-geo-analyzer/             # Plugin sidebar GEO score /100 (éditeur uniquement)
│   ├── g2rd-geo-summary/              # Bloc résumé IA — microdata schema.org Article
│   ├── g2rd-geo-faq/                  # Bloc FAQ GEO — accordéon CSS + JSON-LD FAQPage
│   │
│   └── g2rd-options-page/             # App React page d'options admin (src/ + build/)
│
├── classes/
│   ├── class-abilities.php                      # WordPress Abilities API (opt-in admin)
│   ├── class-api-connector.php
│   ├── class-block-categories.php               # Catégorie de blocs g2rd-blocks
│   ├── class-block-editor-autoload.php          # Enregistrement blocs + theme.json dynamique
│   ├── class-block-editor-enhancements.php
│   ├── class-block-patterns.php
│   ├── class-block-styles.php
│   ├── class-block-stylesheets.php
│   ├── class-business-mode.php                  # Mode agence — fonctionnalités spécifiques business
│   ├── class-carousel-assets.php                # Chargement conditionnel Swiper
│   ├── class-clickable-articles.php
│   ├── class-client-mode.php                    # Mode client — interface simplifiée pour les clients
│   ├── class-coming-soon.php
│   ├── class-conditional-menu.php
│   ├── class-custom-post-types-portfolio.php
│   ├── class-custom-post-types-prestations.php
│   ├── class-custom-post-types-qui-sommes-nous.php
│   ├── class-dark-mode.php
│   ├── class-filterable-grid.php                # Grille filtrée (WooCommerce, SureCart, CPT)
│   ├── class-fluent-cart-support.php            # Intégration FluentCart (remplace SureCart licence)
│   ├── class-fse-sync.php                       # Synchronisation FSE (extrait de block-editor-autoload)
│   ├── class-github-updater.php                 # Mise à jour automatique depuis GitHub
│   ├── class-glass-effect.php
│   ├── class-gsap-animations.php
│   ├── class-json-config.php
│   ├── class-license-manager.php                # Gestionnaire de licences (GitHub Updater)
│   ├── class-license-server.php                 # Serveur de licences (validation/distribution)
│   ├── class-login-customizer.php               # Personnalisation de la page de connexion WP
│   ├── class-onboarding.php                     # Onboarding — assistant de configuration initiale
│   ├── class-particules-effect.php
│   ├── class-portfolio-query.php
│   ├── class-scripts-manager.php
│   ├── class-seo-helper.php                     # Helpers SEO — meta, schema.org, balises canoniques
│   ├── class-shortcode.php
│   ├── class-theme-admin.php
│   ├── class-geo-analyzer.php                      # Module GEO Analyzer — enqueue éditeur + REST
│   ├── class-theme-options.php                     # Options React — REST GET/POST /g2rd/v1/settings
│   └── class-theme-setup.php
├── includes/
│   └── license-init.php             # Initialisation du gestionnaire de licences
├── parts/                           # Template parts FSE
│   ├── header.html
│   ├── header-color.html            # Variante header avec fond coloré
│   ├── footer.html
│   └── sidebar.html                 # Sidebar latérale
├── patterns/                        # Block patterns PHP
├── styles/                          # Variations de styles JSON
├── templates/                       # Templates FSE (21 templates)
│   ├── index.html
│   ├── single.html
│   ├── page.html
│   ├── archive.html
│   ├── 404.html
│   ├── home.html
│   ├── search.html
│   ├── archive-portfolio.html       # Archive CPT portfolio
│   ├── archive-prestations.html     # Archive CPT prestations
│   ├── archive-qui-sommes-nous.html # Archive CPT qui-sommes-nous
│   ├── single-portfolio.html        # Single CPT portfolio
│   ├── single-prestations.html      # Single CPT prestations
│   ├── single-qui-sommes-nous.html  # Single CPT qui-sommes-nous
│   └── page-*.html                  # Pages spécialisées (accueil, agence, artisan, contact, ecommerce, landing, etc.)
├── categories/                      # Catégories de blocs
├── .github/workflows/
│   ├── phpcs-security.yml           # CI : PHPCS WordPress + Security + PHPCompatibility
│   ├── release.yml                  # Release automatique sur tag v* — build + ZIP + GitHub Release
│   └── smart-ci.yml                 # CI : multi-stack (React, Gutenberg, Node.js…)
├── phpcs.xml.dist                   # Config PHPCS WordPress Standards (scope ciblé)
├── phpcs-security.xml               # Config PHPCS Security Audit (exclusions faux positifs)
├── configuration.json
├── theme-settings.json              # Tokens de design (couleurs, typo, espacements)
├── theme-styles.json                # Styles déclaratifs FSE
├── export-theme.ps1                 # Script PowerShell export ZIP production
├── functions.php
├── style.css                        # Métadonnées du thème (Text Domain: g2rd)
└── theme.json                       # Configuration FSE de base (composé dynamiquement)
```

## Commandes

```bash
# Depuis la racine du projet (npm workspaces)
npm install                        # installer toutes les dépendances
npm run build                      # compiler tous les blocs
npm run start                      # mode watch tous les blocs

# Compiler un seul bloc
npm run build:carousel             # g2rd-carousel
npm run build:container            # g2rd-container
npm run build:countdown            # g2rd-countdown
npm run build:counter              # g2rd-counter
npm run build:info                 # g2rd-info
npm run build:typed                # g2rd-typed
npm run build:code                 # CodeG2rd
npm run build:advanced-heading     # g2rd-advanced-heading
npm run build:charts               # g2rd-charts
npm run build:dynamic-content      # g2rd-dynamic-content
npm run build:faq                  # g2rd-faq
npm run build:card                 # CardG2rd
npm run build:filterable-grid      # FilterableGrid
npm run build:pricing-table        # PricingTable
npm run build:block-api            # g2rd-block-api
npm run build:hero                 # g2rd-hero
npm run build:testimonial          # g2rd-testimonial
npm run build:cta-band             # g2rd-cta-band
npm run build:geo-analyzer         # GEO Analyzer (plugin sidebar éditeur)
npm run build:geo-summary          # Bloc GEO Résumé
npm run build:geo-faq              # Bloc FAQ GEO
npm run build:geo                  # Les 3 blocs GEO en une commande
npm run build:options              # Page d'options React (admin)

# Qualité PHP — nécessite PHPCS installé globalement (une seule fois)
# composer global require squizlabs/php_codesniffer wp-coding-standards/wpcs phpcompatibility/phpcompatibility-wp phpcsstandards/phpcsextra pheromone/phpcs-security-audit dealerdirect/phpcodesniffer-composer-installer
# Puis ajouter au PATH : C:\Users\<user>\AppData\Roaming\Composer\vendor\bin
composer run phpcs                 # WordPress Standards (phpcs.xml.dist)
composer run phpcs:security        # Security Audit OWASP (phpcs-security.xml)
composer run phpcs:compat          # PHPCompatibility 8.0+
```

## Conventions de code

### PHP

- Suivre les WordPress Coding Standards strictement (`phpcs --standard=WordPress`)
- Namespace `G2RD` pour toutes les classes
- Classes dans `/classes/` avec préfixe de fichier `class-`
- Utiliser les hooks WordPress (`add_action`, `add_filter`) dans `functions.php`
- Échapper systématiquement les sorties : `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`
- Textdomain **`g2rd`** (unique pour tout le thème) : `__()`, `_e()`, `esc_html__()`

### Sécurité (obligatoire sur chaque feature)

- **Nonces** : vérifier avec `wp_verify_nonce()` sur toute action POST ou AJAX
- **Capabilities** : vérifier `current_user_can()` avant toute écriture en base
- **Sanitisation** : `sanitize_text_field()`, `sanitize_email()`, `absint()`, `wp_kses_post()` à l'entrée
- **Échappement** : toujours échapper à la sortie — jamais de `echo $var` direct
- **REST API** : valider `permission_callback` sur chaque endpoint custom, utiliser `register_rest_route()` avec schéma de paramètres
- Ne jamais exposer de données sensibles dans les réponses REST ou les attributs de blocs

### JavaScript / React

- Syntaxe ESNext avec imports ES modules
- Déstructurer les imports WordPress : `import { useState } from '@wordpress/element'`
- **Ne pas importer React** : `import React from 'react'` inutile avec le JSX transform de @wordpress/scripts
- Utiliser `useBlockProps()` sur chaque bloc
- Nommer les composants en PascalCase, les fichiers en kebab-case
- Couleurs : utiliser `PanelColorSettings` (block-editor) plutôt que `ColorPicker` brut
- **Pas de cascades async** : préférer `Promise.all()` pour les requêtes parallèles
- **Code splitting** : `import()` dynamique pour les dépendances lourdes (bibliothèques tierces)
- **MutationObserver + `data-g2rd-init`** : pattern obligatoire pour les scripts frontend interactifs (compatibilité canvas Gutenberg)

### CSS / SCSS

- Convention BEM : `.block__element--modifier`
- **Toujours utiliser les variables CSS du thème** — jamais de valeurs hex, rgb ou nommées en dur
  - Couleurs front : `var(--wp--preset--color--{slug})` (ex. `var(--wp--preset--color--primary)`)
  - Couleurs admin : `var(--primary-color)`, `var(--secondary-color)` injectées par `ThemeAdmin::outputAdminColorVars()`
  - Espacements : `var(--wp--preset--spacing--{slug})`
  - Typographie : `var(--wp--preset--font-size--{slug})`
  - Ombres : `var(--wp--preset--shadow--{slug})`
- Ces variables sont résolues depuis `theme.json` **et** la variation de style active → le CSS s'adapte automatiquement quand l'utilisateur change de style
- Pas de `!important` sauf cas exceptionnel documenté
- Mobile-first pour le responsive
- Design tokens définis dans `theme-settings.json` — ne jamais coder de valeurs brutes (couleurs, tailles) en dur

### Blocs Gutenberg

- Chaque bloc a son propre dossier dans `/blocks`
- `block.json` obligatoire avec `apiVersion: 3`
- Namespace : `g2rd/nom-du-bloc`
- Catégorie custom : `g2rd-blocks` (constante `G2RD_BLOCK_CATEGORY`)
- Textdomain `"g2rd"` dans tous les `block.json`
- Blocs statiques : `edit.js` + `save.js`
- Blocs dynamiques : `edit.js` + `render.php` (pas de `save.js` ou `save` retourne `null`)
- Scripts interactifs : `"script"` dans `block.json` (frontend + canvas éditeur) — `"viewScript"` uniquement si le bloc n'a pas besoin de WYSIWYG dans l'éditeur
- Parité éditeur/frontend : `blockProps` doit avoir les mêmes classes et styles dans `edit.js` et `save.js`
- Dashicons utilisés en frontend → déclarer dans `"style": ["dashicons", "file:./…"]` dans `block.json` (pas d'enqueue global)
- Icône de bloc : toujours utiliser le format objet `{ "src": "dashicon-slug", "foreground": "#FAFAFA", "background": "#2F425D" }` — jamais une chaîne simple

## Enregistrement des blocs

Les blocs sont enregistrés automatiquement par `class-block-editor-autoload.php` :

- Lit tous les sous-dossiers de `/blocks/`
- Valide la présence et la syntaxe de `block.json`
- Compose `theme.json` dynamiquement depuis `theme-settings.json` + `theme-styles.json` + `configuration.json`
- Cache du `theme.json` composé via transients WordPress (invalidation automatique par mtime des fichiers sources)

## theme.json

- Toutes les couleurs, typographies et espacements passent par `theme.json`
- Le `theme.json` final est **composé dynamiquement** via `wp_theme_json_data_theme` filter
- Ne pas modifier `theme.json` directement pour les couleurs/typos — éditer `theme-settings.json`
- Vérifier la compatibilité du JSON après chaque modification des fichiers sources

## Système de licences

- **`class-surecart-license-manager.php` supprimé** — le système de licence SureCart a été retiré en v1.2.12
- **`class-fluent-cart-support.php`** — intégration FluentCart (remplaçant de SureCart pour les licences) — en cours de développement
- **`class-license-manager.php`** — gestionnaire de licences actuel (GitHub Updater)
- **SureCart produits** : le support des produits SureCart (`sc-product`) est **conservé** dans `class-filterable-grid.php` pour les utilisateurs du thème qui utilisent SureCart — ne pas le retirer

## CI / Qualité PHP

### release.yml

Déclenché sur tag `v*` ou manuellement. Exécute : checkout → Node 20 → `npm ci` → `npm run build` → génère un ZIP de production (exclut `src/`, `node_modules/`, `.git/`, `docs/`, `.claude/`) → crée une GitHub Release → notifie g2rd.fr via webhook HMAC-SHA256.

### phpcs-security.yml

Le workflow `.github/workflows/phpcs-security.yml` exécute 3 jobs à chaque push/PR sur `main` :

1. **PHPCS WordPress Standards** — utilise `phpcs.xml.dist` (scope : `./classes`, `./functions.php`, `./includes`)
2. **PHPCS Security Audit** — utilise `phpcs-security.xml` (standard `Security` avec exclusions faux positifs)
3. **PHPCompatibility** — standard `PHPCompatibilityWP`, testVersion `8.0-`

```yaml
# Suppression des warnings Node.js 20 dans GitHub Actions
env:
  FORCE_JAVASCRIPT_ACTIONS_TO_NODE24: true
```

**`phpcs-security.xml`** — exclusions validées (ne pas supprimer) :

- `Security.BadFunctions.FilesystemFunctions` — faux positif sur `file_exists`, `filemtime`, `glob`, `is_dir` avec chemins WordPress internes contrôlés
- `Security.BadFunctions.CallbackFunctions` — faux positif sur `array_map('trim', ...)` avec fonctions PHP natives
- `PHPCS_SecurityAudit.BadFunctions.PregReplace.PregReplaceWeird` / `PregReplaceDyn` — `phpcs:ignore` ciblés dans 3 fichiers (`class-block-styles.php`, `class-block-stylesheets.php`, `class-clickable-articles.php`) — CSS minification interne et pattern via `preg_quote()`, pas de modificateur `/e`

## Performance

- Scripts non critiques : stratégie `defer` native WP 6.4+ (`wp_script_add_data($handle, 'strategy', 'defer')`) — pas de `str_replace` sur les balises script
- Assets conditionnels : charger avec `has_block()` — ne jamais enqueuer inconditionnellement un CSS/JS lié à un bloc
- Images : `loading="lazy"` systématique, `fetchpriority="high"` sur les images LCP
- Polices : `<link rel="preload" … fetchpriority="high">` pour la police principale
- Preconnect : `<link rel="preconnect">` pour les CDN tiers (cdn.jsdelivr.net, etc.)
- `wp_head` : supprimer les balises inutiles (wp_generator, wlwmanifest, rsd_link, liens REST)
- Fusionner les callbacks `render_block` quand plusieurs filtres s'appliquent au même hook
- Effet particules : désactivé automatiquement pour Google PageSpeed Insights et Lighthouse (v1.0.8+)
- **will-change** : déclarer uniquement sur `:hover` / `:focus`, jamais sur les éléments statiques — évite la saturation GPU sur les pages avec beaucoup de cartes ou boutons
- **Scroll listeners** : toujours utiliser `{ passive: true }` + `requestAnimationFrame` throttle pour les callbacks scroll
- **Preload CDN** : les `<link rel="preload">` doivent pointer vers la même URL que le `<script src>` — si les scripts viennent d'un CDN, le preload doit utiliser l'URL CDN avec `crossorigin="anonymous"`, pas un chemin local

### Compatibilité LiteSpeed Cache

LiteSpeed Cache convertit tous les scripts en `type="litespeed/javascript"` (defer JS), y compris les scripts de données localisées (`-js-extra`). Cela crée des erreurs si un script principal s'exécute avant que ses données soient disponibles.

- **Exclusion via filter** : `ScriptsManager::excludeFromLitespeed()` exclut `dark-mode.js`, `accessibility.js` et `fluent-cart` de l'optimisation LiteSpeed via le filtre `litespeed_optm_js_exc`
- **Anti-FOUC dark mode** : script inline dans `<head>` (priorité 1) via `DarkMode::outputAntiFoucScript()` — lit `localStorage` et applique `data-theme="dark"` avant le rendu, indépendamment de LiteSpeed
- **Preload + LiteSpeed** : ne pas ajouter de `<link rel="preload">` pour des scripts que LiteSpeed peut bundler — ils deviennent des 404 ou des warnings "not used within a few seconds"

## Export production

```powershell
# Depuis la racine du projet (PowerShell)
.\export-theme.ps1
# Génère : C:\Users\gerar\Downloads\Développement Web\G2RD-theme.zip
```

## Workflow

- Créer une branche par fonctionnalité ou bloc
- Tester chaque bloc dans l'éditeur Gutenberg ET sur le front avant commit
- Vérifier le rendu responsive sur mobile, tablette et desktop
- Messages de commit en français, format : `type: description courte`
  - `feat:` nouvelle fonctionnalité
  - `fix:` correction de bug
  - `style:` modification visuelle
  - `refactor:` restructuration du code
  - `release:` montée de version
  - `docs:` documentation
- **Ne jamais monter de version sans confirmation explicite de l'utilisateur**
- Ne jamais modifier les fichiers dans `/node_modules` ou `/vendor`

## Compatibilité

- WordPress 6.5+ minimum (testé jusqu'à 7.0)
- PHP 8.0+
- Node.js >= 18.0.0
- Navigateurs : 2 dernières versions majeures (Chrome, Firefox, Safari, Edge)

## SEO

- Balisage sémantique HTML5 dans tous les templates
- Structure de titres hiérarchique (un seul h1 par page)
- Attributs `alt` obligatoires sur les images
- Performances : `loading="lazy"` sur les images, scripts frontend via `script` (WYSIWYG) ou `viewScript` (frontend uniquement)

## Réponses attendues

- Code prêt à copier-coller, directement exploitable
- Commentaires en français dans le code
- Toujours fournir le chemin du fichier concerné
- Expliquer les choix techniques brièvement

## Skills disponibles

Invoquer avec `/nom-du-skill` pour charger un contexte expert spécialisé.

| Skill | Quand l'utiliser |
| --- | --- |
| `/wordpress-pro` | Tout ce qui touche WordPress : hooks, REST API, sécurité WP, WooCommerce, performance, WPCS |
| `/react-best-practices` | Optimisation spécifique des blocs Gutenberg React : éviter les waterfalls, bundle, rendering |
| `/frontend-design` | Création d'interfaces visuellement distinctives, direction artistique, composants UI |
| `/code-reviewer` | Revue de code : qualité, sécurité, bonnes pratiques, checklist PR |
| `/security-review` | Audit de sécurité complet sur les changements en cours (OWASP, nonces, capabilities) |
| `/web-security-testing` | Tests de sécurité OWASP Top 10 : injection, XSS, authentification, contrôle d'accès |
| `/production` | **Release** : bump version, changelog README, PHPCS, build, ZIP, commit `release:` |
| `/simplify` | Revue du code modifié : qualité, réutilisation, efficacité — puis correction |
| `/review` | Revue d'une Pull Request GitHub |
