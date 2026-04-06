# G2RD Theme FSE

Thème WordPress Full Site Editing développé par G2RD Agence Web.

## Stack technique

- WordPress 6.4+ avec Full Site Editing
- PHP 8.0+ (WordPress Coding Standards)
- JavaScript ES modules (ESNext) + React/JSX pour les blocs Gutenberg
- `@wordpress/scripts` pour le build (webpack)
- npm workspaces (monorepo) : un package par bloc compilé
- Git + GitHub (mises à jour via GitHub Updater)

## Structure du projet

```
g2rd-theme/
├── assets/
│   ├── css/          # Styles compilés du thème
│   ├── js/           # Scripts compilés du thème
│   ├── images/       # Images du thème
│   └── fonts/        # Polices custom
├── blocks/           # Blocs Gutenberg custom (un dossier par bloc)
│   ├── blocks-manifest.php          # Auto-généré par build-blocks-manifest
│   ├── AdvancedList/                # Blocs "compilés" : pas de src/, assets directs
│   ├── Breadcrumb/
│   ├── CardG2rd/
│   ├── DeviceMockup/
│   ├── FilterableGrid/
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
│   ├── g2rd/
│   ├── CodeG2rd/                    # Blocs avec npm workspace (src/ + build/)
│   │   ├── src/
│   │   │   ├── edit.js
│   │   │   ├── save.js
│   │   │   ├── index.js
│   │   │   └── languages.json
│   │   ├── block.json
│   │   ├── package.json
│   │   └── webpack.config.js
│   ├── g2rd-carousel/
│   │   ├── src/
│   │   │   ├── edit.js
│   │   │   ├── save.js
│   │   │   ├── index.js
│   │   │   └── carousel-frontend.js
│   │   ├── block.json
│   │   ├── package.json
│   │   └── webpack.config.js
│   ├── g2rd-countdown/
│   │   └── src/
│   │       ├── edit.js
│   │       ├── save.js
│   │       ├── index.js
│   │       └── countdown-frontend.js
│   ├── g2rd-counter/
│   │   └── src/
│   │       ├── edit.js
│   │       ├── save.js
│   │       ├── index.js
│   │       └── view.js
│   ├── g2rd-info/
│   │   └── src/
│   │       ├── edit.js
│   │       ├── save.js
│   │       ├── index.js
│   │       └── info-frontend.js
│   ├── g2rd-typed/
│   │   └── src/
│   │       ├── edit.js
│   │       ├── save.js
│   │       ├── index.js
│   │       └── view.js
│   └── shared/
│       └── PostSelector.js          # Composant partagé entre blocs
├── classes/
│   ├── class-block-editor-autoload.php  # Enregistrement blocs + theme.json dynamique
│   ├── class-carousel-assets.php        # Chargement conditionnel Swiper
│   ├── class-custom-post-types-portfolio.php
│   ├── class-custom-post-types-prestations.php
│   └── class-custom-post-types-qui-sommes-nous.php
├── includes/         # Autres fichiers PHP inclus
├── parts/            # Template parts FSE
│   ├── header.html
│   └── footer.html
├── patterns/         # Block patterns PHP
├── styles/           # Variations de styles JSON
├── templates/        # Templates FSE
│   ├── index.html
│   ├── single.html
│   ├── page.html
│   ├── archive.html
│   ├── 404.html
│   └── search.html
├── categories/       # Catégories de blocs
├── configuration.json
├── theme-settings.json
├── theme-styles.json
├── functions.php
├── style.css         # Métadonnées du thème uniquement (Text Domain: g2rd)
└── theme.json        # Configuration globale FSE (base)
```

## Commandes

```bash
# Depuis la racine du projet (npm workspaces)
npm install                  # installer toutes les dépendances
npm run build                # compiler tous les blocs
npm run start                # mode watch tous les blocs
npm run build:carousel       # compiler uniquement g2rd-carousel
npm run build:countdown      # compiler uniquement g2rd-countdown
npm run build:counter        # compiler uniquement g2rd-counter
npm run build:info           # compiler uniquement g2rd-info
npm run build:typed          # compiler uniquement g2rd-typed
npm run build:code           # compiler uniquement CodeG2rd

# Qualité PHP
composer run phpcs           # vérifier les standards PHP WordPress
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
- Utiliser les custom properties CSS définies dans `theme.json`
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

## Performance

- Scripts non critiques : stratégie `defer` native WP 6.4+ (`wp_script_add_data($handle, 'strategy', 'defer')`) — pas de `str_replace` sur les balises script
- Assets conditionnels : charger avec `has_block()` — ne jamais enqueuer inconditionnellement un CSS/JS lié à un bloc
- Images : `loading="lazy"` systématique, `fetchpriority="high"` sur les images LCP
- Polices : `<link rel="preload" … fetchpriority="high">` pour la police principale
- Preconnect : `<link rel="preconnect">` pour les CDN tiers (cdn.jsdelivr.net, etc.)
- `wp_head` : supprimer les balises inutiles (wp_generator, wlwmanifest, rsd_link, liens REST)
- Fusionner les callbacks `render_block` quand plusieurs filtres s'appliquent au même hook

## Workflow

- Créer une branche par fonctionnalité ou bloc
- Tester chaque bloc dans l'éditeur Gutenberg ET sur le front avant commit
- Vérifier le rendu responsive sur mobile, tablette et desktop
- Messages de commit en français, format : `type: description courte`
  - `feat:` nouvelle fonctionnalité
  - `fix:` correction de bug
  - `style:` modification visuelle
  - `refactor:` restructuration du code
  - `docs:` documentation
- Ne jamais modifier les fichiers dans `/node_modules` ou `/vendor`

## Compatibilité

- WordPress 6.4+ minimum
- PHP 8.0+
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
| `/senior-architect` | Décisions d'architecture, patterns système, analyse de dépendances, trade-offs techniques |
| `/senior-backend` | Conception d'API REST, optimisation de requêtes, authentification, logique métier complexe |
| `/senior-frontend` | Composants React/JSX avancés, optimisation de bundle, gestion d'état, performance frontend |
| `/react-best-practices` | Optimisation spécifique des blocs Gutenberg React : éviter les waterfalls, bundle, rendering |
| `/frontend-design` | Création d'interfaces visuellement distinctives, direction artistique, composants UI |
| `/ui-design-system` | Tokens de design, documentation composants, cohérence visuelle, handoff design-dev |
| `/code-reviewer` | Revue de code : qualité, sécurité, bonnes pratiques, checklist PR |
| `/webapp-testing` | Tests frontend avec Playwright, debug UI, vérification comportement visuel |
| `/skill-creator` | Créer ou améliorer un skill `.claude/skills/` |
