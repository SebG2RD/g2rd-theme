# G2RD Theme (FSE)

[![Smart CI](https://github.com/SebG2RD/g2rd-theme/actions/workflows/smart-ci.yml/badge.svg)](https://github.com/SebG2RD/g2rd-theme/actions/workflows/smart-ci.yml)
[![Release](https://github.com/SebG2RD/g2rd-theme/actions/workflows/release.yml/badge.svg)](https://github.com/SebG2RD/g2rd-theme/actions/workflows/release.yml)
[![PHPCS & sécurité](https://github.com/SebG2RD/g2rd-theme/actions/workflows/phpcs-security.yml/badge.svg)](https://github.com/SebG2RD/g2rd-theme/actions/workflows/phpcs-security.yml)
[![Auto-tag](https://github.com/SebG2RD/g2rd-theme/actions/workflows/auto-tag.yml/badge.svg)](https://github.com/SebG2RD/g2rd-theme/actions/workflows/auto-tag.yml)

| | |
| --- | --- |
| **Version actuelle** | **1.7.0** (voir aussi `style.css` et `package.json`) |
| **Licence** | [EUPL-1.2](LICENSE) |
| **WordPress minimum** | **6.6** |
| **PHP minimum** | **8.0** |
| **Node.js minimum** | **18** (npm **8+**) |

Thème WordPress **Full Site Editing** pour sites vitrines, portfolios et agences : templates FSE, blocs G2RD, options en React et qualité de code suivie en CI.

---

## Intro

G2RD Theme fournit une base **FSE** (templates, parties, variations) et une collection de **blocs Gutenberg** orientés métier (SEO, GEO, mise en page, contenus dynamiques). Le dépôt inclut scripts de build, tests ciblés et workflow de **release** (ZIP + GitHub Releases).

---

## Pour qui

- **Agences / freelances** qui livrent des sites WordPress FSE avec des blocs sur mesure.
- **Clients finaux** qui veulent éditer le site dans l’éditeur sans toucher au code (après formation légère).
- **Développeurs** qui clonent le repo pour compiler les blocs, lancer les audits et contribuer.

---

## Ce qui est inclus

- **FSE** : `templates/`, `parts/`, `patterns/`, `styles/`, `theme.json` + composition via `theme-settings.json` / `theme-styles.json`.
- **Blocs** : dossier `blocks/` (workspaces npm), manifest généré, build via `@wordpress/scripts`.
- **PHP** : classes sous `classes/` (autoload Composer), `functions.php`, CPT optionnels, intégrations documentées dans le code.
- **Qualité** : PHPCS (WordPress + sécurité + compat PHP), tests JS blocs, Smart CI sur `main` / `develop`.
- **Documentation** : `docs/` — **[guides client (installation, licence, blocs, SEO, GEO…)](docs/client/README.md)** ; côté technique : [Licences](docs/licensing.md), [Standard blocs](docs/blocks/README.md), [CLAUDE.md](CLAUDE.md) pour les conventions du repo.

---

## Ce qui est premium

- **Blocs G2RD** (`g2rd/*`) : fonctionnalités avancées ; sans licence active, le thème reste utilisable mais l’insertion de nouveaux blocs premium peut être restreinte (voir comportement dans `class-block-editor-autoload.php` et [docs/licensing.md](docs/licensing.md)).
- **Mises à jour automatiques** depuis GitHub : liées à une **licence valide** (FluentCart côté boutique G2RD).
- **SureCart** : conservé uniquement pour **compatibilité** de certains contenus (ex. produits), pas pour les nouvelles licences — détail dans [docs/licensing.md](docs/licensing.md).

---

## Installation développeur

```bash
git clone https://github.com/SebG2RD/g2rd-theme.git
cd g2rd-theme
npm install
composer install
```

- PHP **8.0+**, WordPress **6.6+**, Node **18+**.
- Pour un site local : activer le thème comme tout thème WordPress (dossier dans `wp-content/themes/` ou ZIP fourni par une release).

---

## Build et release

| Action | Commande / déclencheur |
| --- | --- |
| Compiler tous les blocs | `npm run build` |
| Watch développement | `npm run start` |
| Un bloc (exemple) | `npm run build:faq` |
| Audits npm / blocs | `npm run audit:blocks`, `npm run test:blocks` |
| Qualité PHP | `composer run phpcs`, `composer run phpstan`, `composer run phpunit` |

**Release** : push d’un tag `v*` (ex. `v1.6.9.5`) déclenche [`.github/workflows/release.yml`](.github/workflows/release.yml) — build, `npm audit`, `composer audit`, vérification des versions, ZIP propre, test d’activation WordPress, GitHub Release + notification g2rd.fr.

Alignement des versions : `style.css`, `package.json`, `readme.txt` (`Stable tag`), `composer.json` (`extra.g2rd_theme_version`) — script [`tools/verify-release-version.sh`](tools/verify-release-version.sh).

**Historique des versions** : [changelog.txt](changelog.txt) et [Releases GitHub](https://github.com/SebG2RD/g2rd-theme/releases) (notes détaillées par version).

---

## Architecture (vue d’ensemble)

```text
g2rd-theme/
├── assets/           # CSS/JS compilés, images, polices
├── blocks/           # Blocs Gutenberg (un dossier par bloc + workspaces)
├── classes/          # Logique PHP (FSE, options, licence, CPT, etc.)
├── includes/         # Fichiers inclus (ex. licence)
├── parts/ templates/ patterns/ styles/   # FSE
├── theme.json        # Base FSE (complétée à l’exécution)
├── theme-settings.json / theme-styles.json
├── functions.php
└── tools/            # Scripts de vérification (release, versions, ZIP)
```

- Point d’entrée : [`functions.php`](functions.php) + autoload Composer.
- Détails techniques et conventions : [**CLAUDE.md**](CLAUDE.md).

---

## Roadmap

- Poursuivre la **documentation par bloc** et l’audit [`docs/blocks/README.md`](docs/blocks/README.md).
- **Modularisation** progressive (thème “design” vs extensions “suite / blocs / licence”) pour isoler les risques.
- Renforcer les **tests** (PHP + éditeur) sur les blocs les plus utilisés.
- Suivre les évolutions **Gutenberg** / **WordPress** et ajuster dépréciations + `theme.json`.

---

## Support

- **Documentation** : dossier [`docs/`](docs/) et ce README.
- **Bugs / évolutions** : [Issues GitHub](https://github.com/SebG2RD/g2rd-theme/issues).
- **Clients** : espace **G2RD (FluentCart)** pour licence et téléchargements.

---

## Crédits

- Développement : **Sebastien GERARD** — [g2rd.fr](https://g2rd.fr)
- Basé sur **WordPress** FSE et l’écosystème **@wordpress/scripts**.

---

## Changelog

### 1.10.7

- **Fix (RGAA 10.4)** : remplacement de `overflow: hidden` par `overflow-x: clip` sur `.g2rd-magic-page` et `.g2rd-magic-dark` — le contenu textuel n'est plus tronqué au zoom navigateur 200%.

### 1.10.6

- **Accessibilité (RGAA 6.1)** : champs `aria-label` éditables ajoutés sur les blocs g2rd-card, g2rd-hero, g2rd-cta-band, g2rd-pricing-table et g2rd-filterable-grid pour rendre explicites les liens au texte ambigu.
- **Accessibilité (RGAA 3.2)** : contraste des couleurs `muted` et `neutral-500` corrigé — ratio ≥ 4.59:1 sur fond crème (WCAG AA).
- **Accessibilité (RGAA 1.1)** : attributs `alt` de substitution ajoutés aux patterns `section-temoignages` (3 avatars) et `card-info-box` (icône).
- **Updater** : lien "Afficher les détails de la version" ajouté sur la page Mises à jour WordPress pour le thème G2RD.
- **Fix** : résolution vulnérabilité `basic-ftp` (npm audit fix).

### 1.10.5

- **Feature** : édition inline RichText sur 7 blocs (g2rd-cta-band, g2rd-hero, g2rd-card, g2rd-advanced-heading, g2rd-geo-summary, g2rd-pricing-table, g2rd-geo-faq) — le contenu textuel s'édite directement sur le canvas sans passer par la sidebar.
- **Feature** : nouveau bloc `g2rd/effect-kits` enregistré — support dans `BlockEditorAutoload` (localisation licence éditeur) et dans la page d'options.
- **Fix** : `aria-hidden="true"` ajouté sur les icônes Dashicons décoratives dans g2rd-card, g2rd-info et g2rd-counter.
- **Fix** : g2rd-carousel — fallback `alt` sur les images de carrousel (`caption` si `alt` vide).
- **Fix** : g2rd-pricing-table `render.php` — `wp_kses_post` remplace `esc_html` pour `ctaText` et `badge` (support HTML RichText).

### 1.10.4

- **Feature** : lien « Voir les détails de la version X.Y.Z » dans la notification de MAJ WordPress — ouvre un modal Thickbox avec le changelog formaté depuis la release GitHub.
- **Fix** : `getThemeInfo` retourne désormais un objet stdClass (attendu par `theme-install.php`).

### 1.10.3

- **Fix** : groupe cliquable — `next_tag()` sans filtre de classe (robuste en WP 6.6+) ; `cursor:pointer` injecté via `wp_head` indépendamment du JS ; navigation via `window.location.href` au lieu de `link.click()`.

### 1.10.2

- **Fix** : groupe cliquable — regex remplacé par `WP_HTML_Tag_Processor` (WP 6.2+) pour compatibilité fiable avec les structures de blocs WordPress 6.6+ (classes layout multiples, inner container).
- **Fix** : CSS manquant en navigation privée — suppression du `defer_non_critical_css` dans `PerformanceCSS` (conflit avec `css_async.min.js` de LiteSpeed Cache en cache froid).

### 1.10.1

- **Fix** : groupe cliquable — `readyState` check remplace `DOMContentLoaded` pour compatibilité LiteSpeed Cache (callback ne se déclenchait jamais si LiteSpeed avait déjà exécuté le DOM) ; `cursor: pointer` ajouté sur le groupe activé ; `clickable-articles.js` exclu de l'optimisation LiteSpeed.

### 1.10.0

- **Performance** : CSS critique inliné dans `<head>` via `PerformanceCSS` — footer et micro-interactions chargés en différé (amélioration FCP/LCP).
- **Performance** : `font-display: swap` sur les 8 polices du thème — texte visible pendant le chargement des polices (amélioration FCP).
- **Performance** : lazy loading intelligent via `PerformanceImages` — première image en `eager/fetchpriority=high`, reste en `lazy/decoding=async` avec dimensions explicites (amélioration CLS/LCP).
- **Performance** : preload de l'image hero dans `<head>` via `parse_blocks()` (amélioration LCP).
- **Performance** : détection WebP automatique via `wp_content_img_tag` — swap vers `.webp` si disponible sur le serveur.
- **Performance** : suppression `feed_links_extra` et déqueue conditionnel de `comment-reply` (réduction TBT).
- **Performance** : cache transients centralisé via `PerformanceCache` — CSS critique 24h, JSON-LD 12h, invalidation automatique sur `switch_theme`/`save_post`.
- **Performance** : `PerformanceAudit` — log WP_DEBUG : SQL, styles, scripts et taille HTML par page.
- **Fix** : suppression des preload GSAP CDN qui causaient des warnings console « preloaded but not used within a few seconds ».
- **Fix** : bloc Google Reviews — cache stale permanent (fallback sur dernières données connues si API en erreur ou clé manquante).
- **Style** : composants page prestation intégrés dans `magic-page.css` — supprime le CSS inline Gutenberg des `<!-- wp:html -->`.

### 1.9.3

- **Fix FAQ GEO** : mode `<details>/<summary>` — `allowMultiple` fonctionnel sur le frontend via événement `toggle` natif ; masquage du marqueur `<summary>` dans Firefox, Chrome et Safari (`list-style: none` + `::marker { content: none }`).
- **Perf** : suppression de `wp-embed.min.js` sur toutes les pages frontend — script oEmbed inutile sur un site vitrine (~3 KB JS évités).
- **Perf** : déqueue automatique des assets frontend des plugins admin-only (Hostinger AI, ManageWP Worker, Fluent Boards, Fluent Security, Fluent Messaging, Loco Translate) via `ScriptsManager::dequeuePluginAssets()` ; filtre `g2rd_dequeue_plugin_handles` pour personnalisation.
- **Perf** : blocs Counter, Typed, FAQ migrés de `"script"` vers `"viewScript"` — leurs scripts ne chargent plus dans le canvas Gutenberg (éditeur plus léger), le frontend est inchangé.

### 1.9.1

- **Sécurité** : chargement dynamique des classes renforcé — `realpath()`, `basename()`, `str_starts_with()` + confinement au répertoire `classes/`.
- **Fix FAQ** : overflow corrigé éditeur/frontend + option `allowMultiple` accordéon fonctionnelle dans le canvas Gutenberg.
- **CI** : correction des faux positifs PHPCS Security sur les `require_once` dynamiques.
- **Dépendances** : `npm audit fix` axios high severity.

### 1.9.0

- **Sécurité** : validation `accentColor` avant injection dans `dangerouslySetInnerHTML` du bloc Hero — protection XSS sur l'attribut style.
- **Sécurité** : strip `<script>` sur `price_html` dans filterable-grid/view.js — défense en profondeur sur l'HTML WooCommerce/FluentCart.
- **Fix** : memory leak `setTimeout` dans `TabLicence.js` — `clearTimeout` ajouté à l'unmount via `useRef` + `useEffect`.
- **Perf** : `cardOpts` et `cardColors` mémorisés avec `useMemo` dans filterable-grid/edit.js — re-renders inutiles des cartes éliminés.
- **Fix** : `openIndex` de l'accordéon FAQ resynchronisé avec l'attribut `openFirst` via `useEffect`.
- **Refactor** : `TabLicence.js` migré de 6 `useState` vers `useReducer` — transitions d'état déterministes.
- **UX** : `window.confirm()` remplacé par `<ConfirmDialog>` WordPress dans l'onglet Licence.
- **Refactor** : 40 `require_once` statiques remplacés par un glob dynamique avec ordre de priorité dans `functions.php`.
- **Feat** : setup Playwright E2E — config, `.env.example`, specs hero/carousel/faq, CI optionnel.
- **Docs** : design system complet — tokens, blocs, patterns, features, page Gutenberg.
- **Chore** : normalisation des noms de dossiers de blocs en kebab-case (`g2rd-*`).

### 1.8.6

- **Fix dark mode** : couverture complète de tous les blocs (Testimonial, FilterableGrid, Modal, Counter, Carousel, Countdown, GEO Summary, FAQ, Toggle Content, Slider, Header).
- **Fix** : synchronisation cookie/localStorage corrigée — le dark mode PHP (body class) restait actif après désactivation JS.
- **Fix a11y** : contrastes WCAG 2.1 corrigés en dark mode — `contrast-3` #9e9e9e (6.99:1 AA), liens #7cb4f5 (5.5:1 AA), FAQ accent secondaire gold (7.37:1 AAA), GEO Summary tagline/bullet (7.37:1 AAA).

### 1.8.5

- **Fix** : Table des matières — cache-buster `view.asset.php` mis à jour pour forcer le rechargement du JS corrigé (hash identique = ancien fichier servi par LiteSpeed/navigateur).

### 1.8.4

- **Fix** : Table des matières — priorité du sélecteur corrigée, seuls les titres du contenu de l'article (`.wp-block-post-content`) sont indexés, plus les titres de la page entière.

### 1.8.3

- **Perf** : Plus Jakarta Sans — `fontDisplay: swap` + preload déclaré en `<head>` (police découverte à 334ms dans la chaîne critique).
- **Perf** : `fetchpriority="high"` automatique sur la première image LCP des templates FSE (suppression du délai d'affichage élément).
- **Perf** : stratégie `defer` appliquée automatiquement à tous les `viewScript` des blocs g2rd (suppression du render-blocking sur LCP/FCP).

### 1.8.2

- **Feat** : Agent Discovery — WebMCP (`navigator.modelContext.provideContext()`), Agent Skills index (`/.well-known/agent-skills/index.json`), MCP Server Card (`/.well-known/mcp/server-card.json`), Link headers RFC 8288 pour la compatibilité agents IA.
- **Fix** : page de connexion WordPress — responsive mobile/tablette corrigé (media queries injectées dans le CSS inline après les règles dynamiques).

### 1.8.1

- **Testimonial** : nouveau toggle "Lien vers Google Business" — la barre de note globale (logo Google + étoiles + nombre d'avis) devient un lien cliquable vers la fiche Google Maps, activable dans le panneau du bloc, sans saisie manuelle d'URL.
- **GoogleReviews API** : champ `url` ajouté aux fields Places API → exposé en `place_url` dans la réponse REST et inclus dans le cache transient 12h.

### 1.8.0

- **Templates** : redesign complet des 21 templates FSE — heroes bleubeige gradient, barres de stats navy, sections cream, CTA bands cohérents sur toutes les pages (page-accueil, page-agence, page-artisan, page-vtc, page-ecommerce, page-contact, page-landing, page-services, page.html, single.html, archive.html, search.html, 404.html, single/archive CPT portfolio/prestations/qui-sommes-nous, taxonomy-site_web).
- **CSS** : `assets/css/header.css` + `assets/css/footer.css` — styles du header dark/light et du footer glassmorphism extraits dans des fichiers CSS dédiés (separation of concerns, maintenance facilitée).
- **ScriptsManager** : enqueue global de `g2rd-header` et `g2rd-footer` sur toutes les pages frontend.

### 1.7.3.2

- **fix** : FAQPage JSON-LD en double — les items de tous les blocs FAQ (`g2rd/faq` mode GEO et `g2rd-geo-faq`) sont fusionnés dans un seul `<script type="application/ld+json">` émis en `wp_footer`, éliminant l'alerte Google Search Console "Champ FAQPage en double".
- **fix(CI)** : `package.json` verrouillé à 3 parties (`1.7.3`) pour compatibilité `verify-release-version.sh`.

### 1.7.3.1

- **fix** : `composer.lock` resynchronisé — hash de contenu désaligné après la mise à jour de `extra.g2rd_theme_version` en 1.7.3, corrigeant l'erreur CI `composer validate`.
- **docs** : CLAUDE.md mis à jour — table des skills disponibles, Magic Page design system, features admin, LiteSpeed compatibility.

### 1.7.3

- **feat** : Anti-FOUC dark mode — script inline dans `<head>` (priority 1) élimine le flash lumière→sombre avant le rendu.
- **feat** : FSE templates persistants — `wp_option` résistant aux flush de transients et aux mises à jour ; suppression conservatrice (trash/auto-draft uniquement).
- **feat** : Panneau d'accessibilité contrôlable depuis la page d'options admin (toggle + icône dashicons).
- **feat** : Magic Page design system — `magic-page.css` conditionnel via `style_handle`, 4 styles de blocs (magic-dark, magic-light, neomorphic, soft-pressed), 2 patterns.
- **fix** : LiteSpeed Cache — filtre `litespeed_optm_js_exc` exclut `dark-mode.js`, `accessibility.js` et `fluent-cart` du defer LiteSpeed.
- **fix** : GSAP preload 404 — URLs CDN Cloudflare corrigées avec `crossorigin="anonymous"`.
- **fix** : `clickable-articles.js` — preload warning supprimé (conflit avec LiteSpeed defer).
- **perf** : `will-change: transform` déplacé sur `:hover` uniquement — réduit la saturation GPU sur pages denses.
- **perf** : Scroll listener passif (`{ passive: true }`) + throttle `requestAnimationFrame` dans `accessibility.js`.

### 1.7.2.3

- **Fix** : styles du bloc FAQ absents en production — `src/style.css` exclu du ZIP ; compilation webpack vers `build/style-style.css` + `block.json` mis à jour.

### 1.7.2.2

- **Style** : redesign frontend bloc FAQ — accordéon "Editorial Lines" avec numéros CSS counter (`01`, `02`…), séparateurs horizontaux, icône cercle animé (fill au survol/ouverture), barre accent gauche `scaleY`, fade-in + slide des réponses alignées sous le texte question.

### 1.7.2.1

- **Fix** : classmap Composer régénérée — classe `AgentDiscovery` absente au démarrage du thème.
- **Fix CI** : permissions exécutables (`+x`) restaurées sur `vendor/bin/` (perte depuis Windows).
- **Chore** : `vendor/` dev-dependencies ajoutées (phpunit, phpstan, WPCS, PHPCompatibility) + `composer.lock` synchronisé.

### 1.7.2

- **Agent Discovery** : nouvelle classe `AgentDiscovery` — RFC 8288 Link headers (`service-desc`, `api-catalog`), endpoint `/.well-known/api-catalog` (RFC 9727, `application/linkset+json`), négociation Markdown (`Accept: text/markdown`), Content Signals dans `robots.txt`.
- **Marquee** : support layout (`inherit: false`), CSS `alignfull`/`alignwide`, `box-sizing: border-box`.
- **GEO Summary** : déprécié v1 enregistré (migration badge GEO → v2 sans badge), null-safety sur `keyPoints`.

### 1.7.1.4

- **Fix CI** : release.yml — hostname MySQL `127.0.0.1` au lieu de `mysql` (jobs sans container GitHub Actions).

### 1.7.1.3

- **Fix CI** : verify-theme-zip.sh — herestring `<<<` au lieu de `echo | grep` pour éviter EPIPE avec `pipefail` (fausse erreur "index.php absent du ZIP").

### 1.7.1.2

- **Fix** : semver package.json — versions 4-parties tronquées à 3 pour compatibilité wp-scripts.
- **Fix** : script verify-release-version.sh — comparaison package.json tolère les versions WordPress 4-parties.

### 1.7.1.1

- **Fix** : alignement des versions (package.json, readme.txt, composer.json) après release 1.7.1.
- **Fix** : synchronisation composer.lock suite au bump de version.

### 1.7.1

- **Fix CI** : PHPStan — config neon, mémoire illimitée, excludePaths optionnels.
- **Fix CI** : PHPCS — fallback statique sans glob, sync composer.lock, scope security audit.
- **Sécurité** : override postcss ^8.5.10 (GHSA-qx2v-qp2m-jg93 XSS).

### 1.7.0

- **Autoloader** : chargement PSR-4 via Composer avec fallback require_once pour les ZIPs de production sans vendor/.
- **Licence** : mode dégradé gracieux — blocs existants préservés (inserter désactivé) si licence inactive, au lieu de désinscrire les blocs.
- **Éditeur** : notice WordPress affichée dans l’éditeur quand la licence est inactive.
- **Sécurité** : messages d’erreur du gestionnaire de licences masqués en production (détails visibles uniquement avec WP_DEBUG).
- **Tests** : infrastructure PHPUnit + smoke tests des classes critiques + tests JS blocs Gutenberg.
- **CI** : release.yml — vérification d’alignement des versions, audit Composer/npm, test d’installation WordPress via wp-cli.
- **CI** : scripts de vérification ZIP et d’alignement de version (tools/).
- **Fix** : enqueueLicenseEditorNotice attaché au bon handle wp-dom-ready.
- **Fix** : python3 remplacé par node dans verify-release-version.sh.
- **PHPCS** : règles durcies — suppressions globales remplacées par exceptions ciblées par fichier.
