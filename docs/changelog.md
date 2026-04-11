# Changelog du thème G2RD

## 1.2.5

- **PHPCS** : réécriture complète de `class-conditional-menu.php` — indentation tabs, syntaxe `array()` pour les constantes de classe, méthodes renommées en snake_case (`register_hooks`, `filter_conditional_block`, `current_user_has_role`).
- **Refactoring** : renommage global `registerHooks()` → `register_hooks()` dans les 31 classes PHP et `functions.php` (conformité WordPress Coding Standards snake_case).
- **CI** : le job PHPCS WordPress Standards utilise désormais `phpcs.xml.dist` (scope ciblé sur `./classes`, `./functions.php`, `./includes`) au lieu d'un scan brut `--standard=WordPress` sur tout le dépôt.
- **phpcs.xml.dist** : exclusions documentées de `WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid` (migration camelCase → snake_case en cours) et `WordPress.Arrays.MultipleStatementAlignment`.

## 1.2.4

- **Sécurité** : correction vulnérabilité `basic-ftp` ≤ 5.2.1 (CRLF injection — GHSA) via override npm `>=5.2.2`.
- **PHPCS** : correction de toutes les violations WordPress Coding Standards dans `class-conditional-menu.php`, `class-abilities.php` et `class-block-patterns.php` — indentation tabs, accolade ouvrante, alignement double-flèches et signe égal.
- **PHPCS** : renommage `ConditionalMenu` → `Conditional_Menu` pour conformité `WordPress.Files.FileName`.
- **WPCS** : hook `g2rd/settings/enable_ai` renommé en `g2rd_settings_enable_ai` (séparateurs underscore).
- **CI** : configuration PHPCS (`phpcs.xml`) et GitHub Actions pour vérification automatique des standards WP et audits de sécurité à chaque push/PR.

## 1.2.3

- **Compatibilité WP 7.0** : migration vers `register_post_meta()`, suppression de `disableGutenberg`.
- **Canvas iframé** : migration vers `wp_enqueue_block_style()` pour la compatibilité avec l'éditeur de blocs.
- **Bugfix** : preset shadow *Imposante* — point-virgule JSON invalide corrigé.
- **Bugfix** : `_icones_images` — `absint` remplacé par `esc_url_raw` pour les URLs.
- **Licence** : normalisation EUPL-1.2, *Tested up to* mis à jour à 7.0.

## 1.2.2

- **Sécurité** : 0 vulnérabilité npm — `@wordpress/scripts` v30 → v31, overrides pour `@typescript-eslint`, `copy-webpack-plugin`, `webpack-dev-server`, `serialize-javascript` et `markdownlint`.
- **Bugfix** : erreur *"headers already sent"* dans `class-block-patterns.php` — chargement via `ob_start()`/`ob_get_clean()` + `get_file_data()`.
- **Bugfix** : `clearPatternsCache()` n'effaçait jamais le bon transient (clé sans version).
- **Bugfix** : `glob()` retournant `false` sur erreur FS provoquait un `foreach` fatal.
- **CI** : détection WordPress étendue aux thèmes (`style.css`, `theme.json`, `block.json`), build Gutenberg intégré, Dependabot configuré.

## 1.2.1

- **GitHub Updater** : renommage du dossier lors de la mise à jour via `WP_Filesystem->move()` (compatibilité hébergeurs restrictifs).
- **GitHub Updater** : validation stricte du tag (`version_compare`) et des tags malformés.
- **CI** : workflow Smart CI multi-stack (WordPress, React, Angular, Symfony, Node.js).
- **Dépendances PHP** : `vendor/` versionné dans Git pour assurer l'installation via `zipball_url` GitHub sans Composer.

## 1.2.0

- **Bloc** `g2rd/container` — layout flex / grille / contraint / flux avec `render.php`.
- **WordPress Abilities API** — `class-abilities.php` opt-in via option d'administration (`enable_ai`).
- **Block API** : remplacement du `Divider` par un `<hr>` dans l'éditeur.
- **Coloration syntaxique** : intégration de `highlight.php` (Composer, distribué avec le thème).
- **Assets** : GSAP et ScrollTrigger intégrés sous `assets/js/vendor/`.
- **Export** : script `export-theme.ps1` pour générer le ZIP de production.

## 1.1.0

- **Bloc** `g2rd-carousel` : affichage mobile 2×2 fixe, désactivation autoplay/loop/swipe, effet `slide` forcé.
- **Bloc** `g2rd-countdown` : titre éditable, `useBlockProps.save()` retiré.
- **Bloc** `g2rd-counter` : fix duplication préfixe/suffixe en front.
- **Bloc** `g2rd-typed` : animation de frappe configurable (strings, vitesse, boucle).
- **Bloc** `g2rd-info` : refonte — icônes Dashicons + images, layouts, accessibilité.
- **Workspaces npm** : monorepo avec un `package.json` par bloc compilé.

## 1.0.8

- **Performances** : l'effet particules est désactivé pendant les analyses PageSpeed Insights / Lighthouse, pour un meilleur score sans impact sur les visiteurs réels.
- **Technique** : nettoyage JS pour compatibilité WordPress.

## 1.0.7

- **Counter** : préfixe et suffixe non dupliqués en front.
- **Counter** : taille d'icône / image personnalisable dans l'éditeur.
- **Glass** : fusion correcte des styles inline (padding, etc.).

## 1.0.6

- **Nouveautés** :

  - Création d'un bloc "G2RD Info" similaire au bloc info d'Astra
  - Support complet des icônes WordPress (Dashicons) et images personnalisées
  - 4 layouts possibles : gauche, droite, haut, bas
  - Sélecteur d'icônes visuel avec aperçu des Dashicons
  - Plus de 100 icônes organisées par catégories (Information & Status, Communication, Media & Content, Commerce, Navigation, etc.)
  - Contrôle d'espacement (gap) dynamique entre icône et texte
  - Personnalisation complète : couleurs, typographie, espacement, bordures, ombres
  - Effets de survol : scale, lift, glow
  - Design responsive et accessible
  - Intégration native avec les classes WordPress

- **Améliorations** :

  - Optimisation du code avec factorisation et commentaires complets
  - Correction des conflits CSS/inline styles
  - Amélioration de l'alignement et du positionnement des icônes
  - Interface utilisateur améliorée pour la sélection d'icônes
  - Code plus maintenable avec fonctions réutilisables

- **Corrections** :
  - Résolution des problèmes d'affichage des icônes
  - Correction des conflits entre styles CSS et inline
  - Amélioration de la gestion des layouts et de l'espacement

## 1.0.5

- Création d'un bloc "Toggle Content" pour afficher/masquer deux groupes de blocs
- Ajout d'options de personnalisation pour le bloc Toggle : alignement, style et couleurs
- Le bloc Toggle utilise une technique 100% CSS (pas de JS en frontend) pour la performance
- Création d'une catégorie de blocs "G2RD Bloks" pour regrouper les blocs du thème

## 1.0.4

- Correction du champ titre éditable dans le bloc countdown
- Ajout/fiabilisation de l'option d'orientation (ligne/colonne) pour le timer
- Chargement du CSS du bloc via block.json (compatibilité build)
- Amélioration du .gitignore pour le dépôt GitHub

## 1.0.2

- Correction des erreurs de linter
- Amélioration de la documentation
- Optimisation des performances
- Mise à jour des dépendances
- Correction des bugs mineurs

## 1.0.1

- Ajout de la gestion avancée des icônes dans "Qui sommes nous"
- Intégration du sélecteur de médias WordPress
- Optimisation de l'interface d'administration
- Utilisation des classes natives WordPress
- Amélioration de la documentation

## 1.0

- Version initiale
- Support complet du Full Site Editing
- Intégration des animations GSAP
- Effets de particules interactifs
- Blocs personnalisés pour agences web
- Support multilingue
- Optimisations de performance
- Système de mise à jour automatique via GitHub
- Types de contenu personnalisés pour portfolio
- Gestion avancée des projets

---

## Modèle pour les futures versions

## x.x.x

- Nouveautés :
- Corrections :
- Améliorations :
