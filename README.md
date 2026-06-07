# G2RD Theme (FSE)

[![Smart CI](https://github.com/SebG2RD/g2rd-theme/actions/workflows/smart-ci.yml/badge.svg)](https://github.com/SebG2RD/g2rd-theme/actions/workflows/smart-ci.yml)
[![Release](https://github.com/SebG2RD/g2rd-theme/actions/workflows/release.yml/badge.svg)](https://github.com/SebG2RD/g2rd-theme/actions/workflows/release.yml)
[![PHPCS & sécurité](https://github.com/SebG2RD/g2rd-theme/actions/workflows/phpcs-security.yml/badge.svg)](https://github.com/SebG2RD/g2rd-theme/actions/workflows/phpcs-security.yml)
[![Auto-tag](https://github.com/SebG2RD/g2rd-theme/actions/workflows/auto-tag.yml/badge.svg)](https://github.com/SebG2RD/g2rd-theme/actions/workflows/auto-tag.yml)

| | |
| --- | --- |
| **Version actuelle** | **1.21.3** (voir aussi `style.css` et `package.json`) |
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

### **1.21.3**

- **Fix mises à jour du thème** : la dernière release GitHub est désormais mise en cache (6 h, transient partagé par la vérification de MAJ et la fiche thème). Fini l'épuisement du quota API GitHub lors des snapshots REST du connector, qui faisait disparaître la mise à jour du thème custom dans le manager.
- **Robustesse** : un `403 rate limit` (qui n'est pas une erreur WP) est désormais détecté via le code HTTP, avec backoff de 15 min ; une mise à jour déjà connue n'est plus jamais effacée par un appel raté.

### **1.21.2**

- **MCP — `g2rd_create-full-post`** : crée un article complet (contenu, image à la une importée depuis une URL, catégories, tags, métas SEO) en un seul appel et une seule confirmation e-mail, là où il fallait 4 actions auparavant.
- **MCP — `g2rd_batch`** : regroupe jusqu'à 20 actions d'écriture en une seule confirmation (exécution séquentielle, statut par opération).
- **Sécurité** : vérification du type MIME réel des médias importés (sideload), bénéficiant aussi à `g2rd_upload-media` ; rollback automatique du média si la création de l'article échoue.
- **Style** : clarification du libellé de licence dans l'onglet Licence (FluentCart).

### **1.21.1**

- **Fix IA** : fiabilisation de la persistance du « Profil du site » (sauvegarde indépendante de la méthode HTTP, en-tête anti-cache, et garde-fou de diagnostic si une écriture d'option est bloquée).

### **1.21.0**

- **Profil du site IA** : le contexte (activité, ville, cible, ton) se saisit une seule fois dans Options G2RD → IA, puis est réinjecté automatiquement dans toutes les générations — fini la re-saisie dans chaque bloc.
- **Connecteur WordPress AI** : l'inférence passe par le WordPress AI Client (providers Anthropic/OpenAI) avec repli automatique sur l'appel direct Anthropic.
- **Panneau IA simplifié** dans les blocs (Hero, FAQ, CTA, Tarifs, Témoignage, Card) : plus de champs de contexte répétés.
- **Fix FluentCart** : reconnaissance du post type produit `fluent-products` (mise à jour de la version produit à chaque release).
- **Grille filtrable** : nouveaux contrôles du CTA (padding, bordure complète, rayon) — valeurs CSS libres (dont `clamp()` pour un rendu fluide), visibles dans l'éditeur et sur le front.

### **1.20.0**

- **Téléchargement FluentCart (g2rd.fr)** : nouvel endpoint sécurisé `/download/g2rd-theme.zip?license=…` qui valide la licence et streame le ZIP depuis un cache local hors webroot, mis à jour automatiquement à chaque release.
- **Livraison automatisée** : lien de téléchargement de la dernière version ajouté dans l'email d'achat et dans l'espace client (onglet Licences).
- **Admin licences** : alignement de l'onglet de gestion des licences (TabLicenceAdmin).

### **1.19.0**

- **theme.json enfant prioritaire** : le `theme.json` d'un thème enfant pilote désormais entièrement les réglages qu'il définit — palette, polices, espacements remplacent ceux du parent (deep-merge « enfant prioritaire ») ; les réglages absents héritent du parent.
- **Point d'extension `externalTabs`** : onglet externalisable sur la page d'options (filtre PHP + hook JS) pour brancher des modules tiers.
- **Bridge MCP — debug optionnel** : les traces du bridge stdio sont conditionnées à la variable `G2RD_MCP_DEBUG` (silencieuses par défaut).
- **Maintenance** : montée de `@wordpress/scripts` (Dependabot).

### **1.18.2**

- **Fix** : support thème enfant dans `composeThemeJson()` — les couleurs définies dans le `theme.json` du thème enfant sont désormais fusionnées avec celles du parent (ajout + surcharge par slug). La clé de cache intègre le `mtime` du `theme.json` enfant pour une invalidation automatique à chaque modification.

### **1.18.1**

- **Fix** : renommage des 37 outils MCP de `g2rd/xxx` vers `g2rd_xxx` pour respecter le pattern imposé par l'API Anthropic (`^[a-zA-Z0-9_-]{1,64}$`) — corrige le rejet de `tools/list` par Claude Desktop.

### **1.18.0**

- **Feature** : Nouvel outil MCP `upload-media` — télécharge une image depuis une URL et l'importe dans la médiathèque WordPress (types autorisés : jpg, png, gif, webp, svg, pdf ; limite 10 Mo).
- **Feature** : Nouvel outil MCP `upload-media-base64` — importe un fichier encodé en base64 dans la médiathèque (utile pour les images générées par Claude).
- **Feature** : Nouvel outil MCP `delete-media` — déplace un attachment en corbeille (suppression permanente impossible).
- **Refactor** : MCP passe de 34 à 37 outils.

### **1.17.0**

- **Feature** : MCP WordPress étendu à 34 outils — `get-post` avec HTML, taxonomies, SEO et image mise en avant ; `list-posts` avec filtres statut/recherche/catégorie/tag ; nouveaux outils lecture (get-post-meta, list-categories, list-tags, list-media, get-seo-data, get-seo-overview, get-redirections, list-plugins, get-theme-info, list-themes, get-options, get-users, get-site-health, get-cron-jobs, list-menus).
- **Feature** : 15 outils d'écriture MCP avec confirmation admin (delete-post, update-post étendu, update-post-meta, update-seo-data, create-redirection, create-category, create-tag, update-media, activate/deactivate/update-plugin, update-option whitelist, flush-cache, update-menu-item).
- **Refactor** : déplacement `export-theme.ps1` dans `tools/`.

### 1.16.3.1

- **Fix** : bridge MCP — ne répond plus aux notifications MCP (corrige l'erreur de validation `invalid_union` dans Claude Desktop).

### 1.16.3

- **Fix** : endpoint MCP déplacé de `g2rd/mcp/v1/` vers `g2rd/v1/mcp` — corrige la 404 sur Hostinger (conflit avec le plugin AI Assistant natif).

### 1.16.2

- **Fix** : double préfixe URL dans `apiFetch` — `restUrl` (URL absolue) remplacé par `restPath` (chemin relatif `/g2rd/v1/ai/`), corrige le 404 sur la génération IA en production.

### 1.16.1

- **Fix** : Message "Connecteur IA non configuré" remplacé par un lien cliquable vers Apparence › Options G2RD › IA.
- **Fix** : Thème sombre (dark chrome) retiré de la modal et du launcher IA — rendu neutre WordPress.

### 1.16.0

- **Feature** : Module IA G2RD — appel direct Anthropic (claude-sonnet-4-6), sans dépendance à `wp_ai_client()` ni plugin tiers. Clé API saisie dans les réglages, masquée en base.
- **Feature** : Refonte UI assistant IA — sidebar remplacée par une modal plein-écran (contexte, onglets, résultat inline avec Insérer / Copier / Régénérer).
- **Fix** : Validation IP stricte (`filter_var FILTER_VALIDATE_IP`) aux points d'entrée MCP et REST IA.
- **Fix** : Suppression optimiste des tokens MCP + config Claude Desktop via commande `g2rd-mcp-bridge` npm-link.
- **Fix** : Suppression ignore PHPStan `wp_ai_client` devenu obsolète.

### 1.15.3

- **Fix** : révocation d'un token MCP supprime immédiatement l'enregistrement en base de données.

### 1.15.2

- **Feat** : tokens MCP inactifs (révoqués/expirés) visibles avec badge statut + bouton « Supprimer » (purge BD).
- **Fix** : bridge MCP enveloppe les erreurs WordPress en JSON-RPC valide — élimine l'erreur de validation Zod côté Claude.

### 1.15.1

- **Feat** : g2rd-mcp-bridge déclaré comme binaire npm global (`npm link`) — configuration Claude.ai MCP sans chemin absolu.

### 1.15.0

- **Feat** : champ « Consignes personnalisées » dans les réglages Module IA (contexte site injecté dans le prompt).
- **Feat** : G2RDAiInspectorPanel intégré dans les blocs Pricing Table et Testimonial.
- **Feat** : compilation fr_FR.mo automatisée en CI lors des releases.
- **Fix** : source maps désactivées en production + .map existants supprimés du dépôt.
- **Fix** : wp_kses_post appliqué sur echo $markdown dans AgentDiscovery (PHPCS).
- **Fix** : webpack-dev-server mis à jour vers ^5.2.4 (vulnérabilité Dependabot #59).
- **Test** : couverture PHPUnit LicenseManager + GithubUpdater (29 tests).
- **Docs** : stratégie vendor/ documentée dans docs/internal/vendor-strategy.md.

### 1.14.0

- **Module IA G2RD** : nouveau module complet (AiModule, AiClient, AiRest, AiPrompts) — génération de contenu via WordPress AI Connectors, désactivable depuis la page d'options.
- **Panneau IA dans les blocs** : G2RDAiInspectorPanel intégré dans Hero, FAQ, CTA Band, Pricing Table, Testimonial et Card.
- **Tab "Module IA"** : nouvelle section dans la page d'options pour configurer les paramètres IA (activation, quotas, ton/longueur par défaut).
- **Bridge MCP stdio** : `tools/g2rd-mcp-bridge.js` — connecteur Claude Desktop via stdio pour le serveur MCP WordPress.
- **Fix** : endpoint MCP corrigé dans TabMcpTokens (`g2rd/v1` → `g2rd/mcp/v1/`), config Claude Desktop générée au bon format stdio.
- **Fix** : exclusion CSS LiteSpeed pour `accessibility.css` — le panneau d'accessibilité s'ouvre correctement après optimisation LiteSpeed.

### 1.13.7

- **Feat (filterable-grid)** : couleurs CTA personnalisables — fond, texte, survol fond, survol texte — via PanelColorSettings dans l'inspecteur Gutenberg.
- **Fix (filterable-grid)** : caractères spéciaux (apostrophes typographiques `&#8217;`, entités HTML nommées) s'affichaient littéralement dans les titres, extraits et noms de termes.

### 1.13.6

- **Fix** : page d'options admin inaccessible via `admin.php?page=g2rd-options` — le slug enregistré était `g2rd-theme-settings` (incohérence avec tous les liens du thème).
- **Fix** : navigation par hash dans la page d'options — `#mcp-queue` (et tout onglet) dans l'URL ouvre directement le bon onglet.
- **MCP** : code d'intégration complet (config Claude Desktop + Claude Code) affiché à la création d'un token API, et bouton "Voir config" sur chaque token existant.
- **Fix critique** : CSS des blocs `g2rd-card`, `g2rd-carousel`, `g2rd-countdown`, `g2rd-dynamic-content`, `g2rd-geo-faq`, `g2rd-geo-summary`, `g2rd-info`, `g2rd-typed` jamais livré en production — `block.json` pointait vers `src/*.css` exclu du ZIP de production. Migré vers `build/*.css` généré par webpack.

### 1.13.5

- **Fix (éditeur)** : supprime les warnings de dépréciation `SelectControl` et `RangeControl` WP 6.8+ — `__next40pxDefaultSize` et `__nextHasNoMarginBottom` ajoutés sur 65+74 occurrences dans 24 fichiers, compatibilité assurée jusqu'à WP 7.1.
- **Feat (blocs)** : dépréciations automatiques `g2rd-card` (4 versions), `g2rd-advanced-heading` (`fontSizeValue→fontSize`) et `g2rd-countdown` (`valueSize/labelSize→valueFontSize/labelFontSize`) — migration silencieuse des blocs existants lors d'une mise à jour thème sur un site client.
- **Chore** : bump `@playwright/test` 1.59.1 → 1.60.0.

### 1.13.4

- **Fix (Card)** : contrôle "Alignement des éléments" fonctionnel — passage à la CSS custom property `--g2rd-card-align` pour éliminer tout conflit de cascade entre inline style et sélecteurs CSS.
- **Fix (Card)** : overlay lien carte entière — ajout `cursor:pointer`, effet hover (fond semi-transparent) et `focus-visible` outline pour l'accessibilité clavier.

### 1.13.3

- **Fix (Card)** : `align-items: center` sur les positions gauche/droite — icône et contenu correctement alignés sur la même ligne.
- **Fix (Card)** : `display: block` sur l'overlay link (correction rendu lien carte entière).
- **Fix (MCP)** : migrations tables déclenchées sur `after_setup_theme` — tables créées lors des mises à jour GitHub (plus seulement à l'activation).
- **Fix (MCP)** : affichage du nom du token dans le tableau de la page d'options.
- **Fix (Filterable Grid)** : titres et extraits avec apostrophes ou accents affichés correctement — `wptexturize()` convertissait les apostrophes en entités HTML (`&rsquo;`) double-échappées par le JS.
- **Feat (Card)** : nouveaux contrôles dans l'onglet Styles — alignement global des éléments (`align-items`), écart média/contenu, écart entre éléments du contenu.

### 1.13.2

- **Fix** : Migrations MCP déclenchées sur `after_setup_theme` — tables `wp_g2rd_mcp_tokens` et `wp_g2rd_mcp_audit_log` créées lors des mises à jour GitHub (plus seulement à l'activation initiale).
- **Fix** : Page d'options — onglet Tokens MCP affiche correctement le nom du token (`token_name`).

### 1.13.1

- **Fix** : Bloc Card — `URLInput` importé depuis `@wordpress/block-editor` (était `@wordpress/components` → erreur à l'ouverture du panneau CTA).
- **Feature** : Bloc Card — position icône/image en haut alignée sur l'alignement du texte (gauche/centre/droite).
- **Feature** : Bloc Card — toggles pour masquer/afficher le sous-titre et la description (éditeur + front).
- **Feature** : Bloc Card — mode de lien exclusif : Désactivé / Bouton CTA / Carte entière cliquable (overlay `<a>` absolu).

### 1.13.0

- **Feature** : Contrôle de taille typographique par élément sur 14 blocs — panneau "Typographie" dans l'onglet Styles (presets S/M/L/XL/XXL du thème).
- **Feature** : Composant partagé `TypographySizePanel` — architecture unifiée, `FontSizePicker` presets, aucune duplication entre blocs.
- **Feature** : Page d'options admin — refonte visuelle complète (sidebar nav groupée par section, thème clair, polices Outfit + JetBrains Mono).
- **Feature** : SBOM CycloneDX JSON — inventaire automatique des dépendances PHP + npm sur chaque release GitHub.
- **Fix** : Audit MCP — câblage `X-G2RD-Screen`, `User-Agent` et `execution_ms` dans l'audit log (SP-1).
- **Fix** : `McpTokenManager` — passage du paramètre `$audit` manquant, correctif PHPStan.

### 1.12.1

- **Fix MCP audit** : câblage de `X-G2RD-Screen`, `User-Agent` et `execution_ms` dans chaque entrée d'audit (allowed et denied) — complète l'intent du JS bridge (SP-5).
- **CI SBOM** : nouveau workflow `sbom.yml` — génère un inventaire des dépendances CycloneDX JSON (Composer PHP + npm) signé via attestation sigstore et attaché à chaque GitHub Release.
- **Thème enfant** : dossier `g2rd-child/` ajouté localement (non versionné) avec `style.css`, `functions.php`, `theme.json` et palette de design prête à l'emploi.
- **Chore** : autoload Composer mis à jour pour `McpAbilities`, `McpConfirmationQueue` et `McpServer`.

### 1.11.2

- **Option BO** : bouton pause désactivé nativement sur tous les blocs animés (g2rd-carousel, g2rd-typed, g2rd-marquee, g2rd-testimonial) — activable via un toggle dans le panneau d'inspection Gutenberg.

### 1.11.1

- **Accessibilité RGAA 10.1** : `:focus-visible` sur boutons carousel, counter, login, options-page, code éditeur.
- **Accessibilité RGAA 13.1** : `prefers-reduced-motion` sur carousel, marquee, testimonial, typed.
- **Accessibilité RGAA 13.2** : bouton pause ⏸ sur marquee (g2rd-marquee + g2rd-testimonial), carousel autoplay, typed animation en boucle.
- **Accessibilité RGAA 2.4** : `aria-label="En-tête coloré"` sur header-color.html.
- **Accessibilité RGAA 9.1** : `<blockquote>/<cite>` sémantique sur le bloc testimonial (mode manuel + Google Reviews).
- **Accessibilité RGAA 12.8** : `aria-current="page"` dynamique sur les liens de navigation actifs.
- **Accessibilité RGAA 4.1/12.8** : focus trap + retour de focus sur g2rd-modal et g2rd-sliding-panel.
- **Accessibilité RGAA 10.2** : `aria-hidden` dynamique sur le contenu masqué de g2rd-toggle-content.
- **Accessibilité RGAA 6.1** : alternative textuelle accessible sur g2rd-map (screen-reader-text).
- **Accessibilité RGAA 12.1** : `aria-label` sur les liens icône-seule de g2rd-icon-box.
- **Accessibilité RGAA 4.1** : `aria-controls`, `aria-live`, `aria-current` sur g2rd-slider.

### 1.11.0

- **Bloc** : nouveau bloc G2RD Pin Scroll — séquence d'images synchronisée au défilement (style Apple), verrouillé derrière licence + feature toggle.
- **Accessibilité** : refonte complète du panneau d'accessibilité flottant — localStorage, aria-live, icônes distinctes, profils rapides (Dyslexie, Basse vision, Anti-mouvement), reset, mobile-responsive.
- **RGAA** : intégration de `inc/rgaa-accessibility.php` directement dans le thème (8 correctifs : aria-label réseaux sociaux, lire la suite, privacy, formulaires Fluent Forms, skip-link, images décoratives).
- **Dark mode** : isolation CSS Fluent Community — les blocs code de Fluent Community ne sont plus impactés par le dark mode du thème.
- **Fix** : `overflow-x: clip` sur `.g2rd-magic-page` (RGAA 10.4 — zoom 200% sans troncature horizontale).

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
