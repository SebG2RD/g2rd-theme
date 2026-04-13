# Changelog du thème G2RD

## 1.3.0

- **Licence** : système complet — `class-license-manager.php` refonte totale : activation, désactivation, validation périodique (cron 24h), cache transient, détection de changement de domain.
- **Licence** : `class-license-server.php` (nouveau) — endpoints REST sur g2rd.fr : `activate`, `deactivate`, `check`, `release-webhook`. Rate limiting 10 req/5min par IP. Liaison domain pour bloquer le partage.
- **Blocs** : `class-block-editor-autoload.php` — les blocs Gutenberg G2RD custom nécessitent une licence active (`LicenseManager::is_active()`).
- **Mises à jour** : `class-github-updater.php` — check licence réactivé. Sans licence : pas de notification de mise à jour.
- **Portail client** : `class-fluent-cart-support.php` — onglet « Licences » dans le portail FluentCart : liste des domaines activés, bouton « Libérer ce domaine » (changement de nom de domaine sans perdre l'activation).
- **CI/CD** : `.github/workflows/release.yml` (nouveau) — build blocs + ZIP production + release GitHub + webhook g2rd.fr sur tag `v*`.
- **Security** : `phpcs-security.xml` — noms de sniffs corrigés (`Security.BadFunctions.FilesystemFunctions`, `PHPCS_SecurityAudit.BadFunctions.PregReplace.PregReplaceWeird/PregReplaceDyn`). Security Audit passe à 0 erreur.
- **Nettoyage** : suppression des artefacts build orphelins à la racine de `blocks/` (`index.js`, `index.asset.php`, `style-index*.css`).

## 1.2.13

- **PHPCS** : migration `phpcs.xml.dist` vers WPCS 3.x — exclusions ajoutées pour les sniffs renommés (`PEAR.Functions.FunctionCallSignature.*`, `Universal.Arrays.DisallowShortArraySyntax`, `NormalizedArrays.Arrays.*`, `Universal.WhiteSpace.CommaSpacing`, `Universal.Operators.DisallowShortTernary`) et nouveaux sniffs PHPCSExtra introduits par WPCS 3.x.
- **PHPCS** : 8 règles globales `severity 0` documentées dans `phpcs.xml.dist` pour les comportements intentionnels (`DevelopmentFunctions`, `file_get_contents`, `NoSilencedErrors`, `UnusedFunctionParameter`, `DirectDatabaseQuery`, `SlowDBQuery`, `rename`, `NoReservedKeywordParameterNames`).
- **Composer** : scripts `phpcs`, `phpcs:security`, `phpcs:compat` ajoutés à `composer.json` — `composer run phpcs` opérationnel localement.
- **Sécurité** : `wp_unslash()` ajouté sur toutes les lectures `$_POST` dans `class-custom-post-types-portfolio.php`, `class-custom-post-types-prestations.php`, `class-custom-post-types-qui-sommes-nous.php` et `class-theme-options.php`.
- **Sécurité** : `esc_url()` appliqué sur `get_template_directory_uri()` dans `class-clickable-articles.php` et `class-gsap-animations.php`.
- **i18n** : commentaires `// translators:` ajoutés sur les 15 appels `sprintf(__())` dans les 3 classes CPT.
- **Qualité** : `$configurationData` → `$configuration_data` dans `class-json-config.php` (convention snake_case WordPress).
- **Qualité** : `json_encode()` → `wp_json_encode()` dans `class-json-config.php`.
- **Qualité** : `in_array()` strict (`true` en 3e paramètre) dans `class-glass-effect.php`.

## 1.2.12

- **Sécurité** : suppression de `class-surecart-license-manager.php` — système de licence SureCart retiré (remplacement par FluentCart prévu ultérieurement).
- **CI** : `FORCE_JAVASCRIPT_ACTIONS_TO_NODE24=true` — élimination des warnings de dépréciation Node.js 20 dans les GitHub Actions.
- **FilterableGrid** : support SureCart (`sc-product`) conservé pour les utilisateurs ayant SureCart installé.

## 1.2.11

- **CI** : Security Audit — remplacement de l'option `--exclude` CLI (inefficace) par un fichier de configuration dédié `phpcs-security.xml` référençant `<rule ref="Security">` avec exclusions `FileSystems` et `CallbackFunctions`.
- **PHPCS** : `phpcs-security.xml` ajouté à la racine du projet — centralise les exclusions des faux positifs de sécurité (fonctions filesystem WordPress légitimes et callbacks PHP natifs).

## 1.2.10

- **PHPCS** : `phpcs.xml.dist` — exclusion de `./blocks/*/*.php` (fichiers PHP des blocs Gutenberg : conventions de build différentes des classes PHP du thème).
- **PHPCS** : `class-block-patterns.php` — `phpcs:ignore` sur l'`include $file` dynamique (chemin garanti par `glob(patterns/*.php)`).
- **CI** : Security Audit — exclusion de `Security.BadFunctions.FileSystems` et `Security.BadFunctions.CallbackFunctions` (faux positifs pour `file_exists`, `filemtime`, `glob`, `is_dir`, `array_map('trim', ...)` légitimes dans un thème WordPress).

## 1.2.9

- **PHPCS** : `phpcs.xml.dist` — règles `Generic.Arrays.DisallowShortArraySyntax` et `WordPress.WhiteSpace.FunctionCallSignature` aussi désactivées au niveau global (severity 0), ceinture et bretelles pour garantir la suppression quelle que soit la version WPCS installée par CI.
- **PHPCS** : `class-custom-post-types-portfolio.php` — alignement des `=` dans `renderMetaBox` corrigé (1 espace en trop sur chaque variable), `phpcs:ignore` sur `enqueueAdminAssets` pour le paramètre `$_hook` requis par l'interface du hook WordPress.

## 1.2.8

- **PHPCS** : correction globale des accolades ouvrantes — toutes les méthodes et classes de `classes/` passent en style K&R (brace sur la même ligne), 24 fichiers corrigés.
- **phpcs.xml.dist** : exclusion de `Generic.Arrays.DisallowShortArraySyntax` (syntaxe `[]` autorisée, PHP 8.0+), `WordPress.WhiteSpace.FunctionCallSignature.SpaceAfterOpenBracket`, `SpaceBeforeCloseBracket`, `NoSpaceBeforeOpenBracket` (style PSR-2 pour les appels de fonctions).
- **PHPCS** : `class-custom-post-types-portfolio.php` — réécriture complète : braces K&R, `$hook` → `$_hook`, alignement de 11 variables dans `renderMetaBox`, alignement `$args`/`$labels`.

## 1.2.7

- **PHPCS** : `class-block-patterns.php` — `@var string` ajouté sur `$theme_version`, accolades ouvrantes sur la même ligne (`__construct`, `register_hooks`), espaces dans les appels `get()`, conversion `[]` → `array()` et espaces dans `add_action`.
- **PHPCS** : `class-shortcode.php` — alignement des `=` dans quatre méthodes (`softSkillsShortcode`, `methodologieShortcode`, `objectifShortcode`, `iconesImagesShortcode`).
- **PHPCS** : `class-filterable-grid.php` — alignement des `=` (WooCommerce orderby/meta_key, img_data/thumbnail, SureCart price/currency), commentaire inline `// 'instock' | ...` réécrit pour ne plus être détecté comme code.
- **PHPCS** : `class-abilities.php` — suppression `phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found` sur `executeThemeSettings` (paramètre requis par l'interface de callback).

## 1.2.6

- **PHPCS** : `class-block-patterns.php` — trailing whitespace, accolade ouvrante sur la même ligne, conversion complète espaces → tabs.
- **PHPCS** : `class-shortcode.php` — alignement des signes `=` dans les blocs d'assignations consécutives.
- **PHPCS** : `class-particules-effect.php` — alignement des `=` pour `$color` et `$speed`.
- **PHPCS** : `class-custom-post-types-prestations.php` — alignement de `$args`.
- **PHPCS** : `class-abilities.php` — paramètre `$input` inutilisé renommé `$_input`.

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
