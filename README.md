# G2RD Theme (FSE)

[![Smart CI](https://github.com/SebG2RD/g2rd-theme/actions/workflows/smart-ci.yml/badge.svg)](https://github.com/SebG2RD/g2rd-theme/actions/workflows/smart-ci.yml)
[![Release](https://github.com/SebG2RD/g2rd-theme/actions/workflows/release.yml/badge.svg)](https://github.com/SebG2RD/g2rd-theme/actions/workflows/release.yml)
[![PHPCS & sécurité](https://github.com/SebG2RD/g2rd-theme/actions/workflows/phpcs-security.yml/badge.svg)](https://github.com/SebG2RD/g2rd-theme/actions/workflows/phpcs-security.yml)
[![Auto-tag](https://github.com/SebG2RD/g2rd-theme/actions/workflows/auto-tag.yml/badge.svg)](https://github.com/SebG2RD/g2rd-theme/actions/workflows/auto-tag.yml)

| | |
| --- | --- |
| **Version actuelle** | **1.26.2** (voir aussi `style.css` et `package.json`) |
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

### **1.26.2**

- **Fix — collision `.has-border-color`** : le slug `border` de la palette générait automatiquement l'utilitaire WordPress `.has-border-color { color: … !important }`, qui **écrasait la couleur du texte** de tout bloc doté d'une couleur de bordure nommée (sur toutes les variations de style, dont *Bela Vista*). `border` est déplacé de la palette vers `settings.custom.color` (socle, hérité par toutes les variations) → WordPress ne génère plus l'utilitaire fautif. Les 20 références migrées de `var(--wp--preset--color--border)` vers `var(--wp--custom--color--border)` (même valeur `#e2e8f0`) : les bordures des cartes (`is-style-card`, etc.) restent identiques.
- **Note** : `surface` reste en palette (aucune collision). Aucun changement visuel des bordures, uniquement suppression de l'écrasement parasite de couleur de texte.

### **1.26.1**

- **Fix — champs membre (Qui sommes-nous)** : la section « Le profil » (Expérience, Soft skills, Méthodologie, Objectif, Stack technique) est désormais rendue en PHP (filtre `the_content`) après le contenu sur les pages membre — approche robuste indépendante du markup du template, qui **survit aux ré-enregistrements dans l'éditeur de site** (l'ancienne section en *block bindings* était perdue à chaque sauvegarde). Couleurs 100 % tokens → suit le style actif.
- **Bloc CTA Band** : couleurs par défaut migrées vers les tokens du thème (`primary`/`white`/`secondary`) avec **déprécation** — les blocs existants restent valides puis migrent en douceur à la ré-édition.
- **Nettoyage** : retrait de ~130 lignes de code mort dans `class-block-styles.php` (chargement de styles jamais exécuté + cache inerte).

### **1.26.0**

- **Accessibilité des blocs (RGAA/WCAG)** : autoplay des carrousels/sliders **pausable au clavier** (WCAG 2.2.2) + module `Swiper.A11y` (aria-roledescription, live region) ; respect de **`prefers-reduced-motion`** sur carrousel, slider, countdown, effect-kits et particules (animations neutralisées, éléments toujours visibles).
- **Couleurs 100 % tokens dans les templates** : conversion de **toutes** les couleurs codées en dur (ancienne palette navy/tan) vers `var(--wp--preset--color--…)` dans l'ensemble des templates — le rendu suit désormais la variation de style active. Contraste `accent` aligné AA (`#e11d48`).
- **Import header/footer fiabilisé** : nouveau filtre `FseSync::normalize_template_part_theme()` qui neutralise l'attribut `theme` des `core/template-part` au rendu → plus d'erreur « template part supprimé ou non disponible », même après une re-sauvegarde dans l'éditeur de site (fichier **et** BDD).
- **Design-system** : tokens `border`/`surface`, gradient `secondary` et alias structurels ajoutés aux variations `g2rd-style`, `apiculteur` et `bela-vista` (cartes, boutons `outline`/`action` et sections sombres corrects sur chaque style).
- **Page d'options (BO)** : accessibilité corrigée (labels sur tous les interrupteurs et champs, `<h1>`, état React au lieu de lecture DOM), polices **Inter self-hostées** (fin de l'`@import` Google Fonts — perf + RGPD), suppression de code mort (`SaveBar.js`).
- **i18n** : correction du textdomain `wrb` → `g2rd` sur 19 blocs (365 chaînes) — libellés d'éditeur enfin traduisibles.
- **Performance** : exclusions LiteSpeed pour `gsap-animation.js` et `carousel-frontend.js` (données localisées non réordonnées).
- **Correctifs** : template `single` (groupe non fermé), header/footer (`is-style-primary` inexistant → attributs FSE réels), `single-prestations` (attribut `theme`), style de bouton « Alternatif » (contraste blanc→navy sur lime), `template-vtc` (clé `style` dupliquée), attributs `alt`/`aria` manquants (sidebar, share-buttons, geo-faq).

### **1.25.1**

- **Nettoyage de `style.css`** : retrait de ~284 lignes de CSS legacy **mort** — TranslatePress (`.trp-*`), cookies SeoPress (`.seopress-*`), template produit FluentCart (`.g2rd-product-*`, orphelin : plus aucun template ne l'émet), `.resize-img`, `.ti-widget`. Fichier allégé de 666 → 397 lignes. `.img-h-100` **conservée** (utilisée par les patterns `grid-portfolio` et `post-grid`). **Aucun impact visuel** : uniquement du code mort — vérifié par diff règle-à-règle, aucune règle partagée modifiée, les variations de style (bela-vista, apiculteur…) sont strictement inchangées. Première étape de l'isolation « chacun son style » : le CSS spécifique à chaque projet migrera ensuite vers sa variation `styles/*.json`.
- **Champs membre FSE (Qui sommes-nous)** : nouvelle source de *block binding* `g2rd/member-meta` — les champs Expérience, Soft skills, Méthodologie et Objectif s'affichent désormais via des paragraphes natifs liés (valeur réelle visible dans l'éditeur de site **et** en front, principe FSE). Section « Le profil » restaurée et harmonisée dans `single-qui-sommes-nous.html` (pill lime en aplat, cartes `is-style-card`) — ces champs avaient disparu du template lors de la refonte 1.8.0. Icônes affichées en rangée via le shortcode `[icones_images]` amélioré.
- **Template produit FluentCart** : nouveau modèle `single-fluent-products.html` (fil d'Ariane, galerie, prix, ajout au panier, description) pour la fiche produit FluentCart.

### **1.25.0**

- **Uniformisation de l'interface des blocs** : les 20 blocs G2RD partagent désormais la même organisation de l'inspecteur — onglets natifs de WordPress (Réglages / Styles / Avancé), vocabulaire de panneaux commun (`Dimensions`, `Arrière-plan`, `Couleur`, `Bordure`, `Animation`…) et contrôles natifs (`ToggleGroupControl` à la place des boutons maison). Chaque réglage se retrouve au même endroit d'un bloc à l'autre. Le `TabPanel` maison du Conteneur est remplacé par les onglets natifs. **Rendu front strictement identique** : seul l'inspecteur change, `save.js` / `render.php` / `block.json` restent inchangés.
- **Sécurité (Conteneur G2RD)** : blocage d'une injection CSS possible via les attributs du bloc. `sanitize_text_field()` laissait passer `{ } ; @`, permettant à un contributeur de fermer la déclaration et d'injecter des règles CSS arbitraires dans le `<style>` du bloc. Les valeurs de style sont désormais filtrées (`g2rd_css_value()`), les énumérations validées par liste blanche.
- **Outillage / CI** : `@wordpress/scripts` monté en `33.0.0` (override `webpack ^5.108.1` pour éviter la duplication d'instance) ; protection de la branche `main` exigeant les status checks avant fusion ; Dependabot bridé sur les montées majeures de la chaîne de build.

### **1.24.16**

- **Agent Discovery — `/auth.md`** : le thème sert désormais `/auth.md` à la racine (spec auth.md), décrivant aux agents IA comment s'authentifier. Document *self-contained* et honnête : endpoints publics sans authentification, et — si `enable_ai` est actif — API MCP sur `POST /wp-json/g2rd/v1/mcp` par jeton **Bearer délivré manuellement par l'admin** (scopes `read_only`/`editor`, écritures en file d'approbation). Aucun bloc `agent_auth`/`register_uri` n'est publié : le site n'opère pas de serveur d'autorisation OAuth 2.0/OIDC et la spec ne l'impose que dans ce cas. Filtre `g2rd_auth_md` pour personnalisation.
- **Correction de documentation** : `CLAUDE.md` indiquait l'endpoint MCP `/wp-json/g2rd/mcp/v1` alors que la route réelle est `/wp-json/g2rd/v1/mcp`.

### **1.24.15**

- **Accessibilité — deux options indépendantes** : le bouton « retour en haut » est désormais une fonctionnalité distincte du panneau d'accessibilité (nouvelle feature `back_to_top`, activée par défaut). On peut activer l'un sans l'autre depuis l'onglet *Éditeur*.
- **Couleur des boutons flottants** : nouveau réglage pour choisir la couleur des boutons « accessibilité » et « retour en haut » dans la palette du thème (stockée en slug → `var(--wp--preset--color--slug)`, suit la variation de style active). Section « Couleur des boutons flottants » dans l'onglet *Éditeur*.
- **Fix panneau d'accessibilité** : le panneau apparaissait en bas du document (au lieu d'être collé au bouton) lorsqu'un ancêtre était transformé (`transform`/`filter`/`will-change`). Bouton et panneau sont désormais solidaires dans un conteneur fixe `.a11y-widget`, le panneau étant ancré au-dessus du bouton. Ajout de fallbacks `--primary`/`--white` pour les variations sans ces slugs.

### **1.24.14**

- **Variation Bela Vista** : header responsive — items de navigation et `.bv-tel` (téléphone) en `white-space:nowrap`, et media query `max-width:1180px` réduisant `font-size`/`gap` de la navigation. Garde le header enrichi (logo + menu + téléphone + bouton) sur une seule ligne, puis le réduit proprement avant le passage en menu mobile. Barre d'annonce et rangée de navigation (`.bv-promo.alignwide` / nouvelle classe `.bv-nav-row.alignwide`) élargies à `max-width:1560px`.

### **1.24.13**

- **Variation Bela Vista** : ajout du token de couleur **`green-soft`** (`#EAF3EE`) à la palette et de nouveaux helpers CSS — `.bv-anchors` (ancres pilule scrollables), `.bv-toc` (sommaire collant), `.bv-doc` (mise en forme document légal : titres, listes, liens), `.bv-step` / `.bv-num`, `.bv-idcard`, `.bv-resbar` et `.g2rd-breadcrumb`. JSON validé, toutes les références `var(--wp--preset--…)` résolvent.

### **1.24.12**

- **Conteneur G2RD — Position collante (sticky)** : nouveau panneau **« Position collante »** (onglet *Mise en page*, sous *Dimensions*) permettant de rendre le bloc collant au défilement — il suit le contenu et reste visible dans sa colonne (ex. sommaire latéral), puis s'arrête à la fin de la colonne. Toggle d'activation + **décalage haut** réglable (px/rem/em). Appliqué en aperçu éditeur et en CSS front (`position:sticky`, `align-self:flex-start`), prioritaire sur le `position:relative` de l'overlay.

### **1.24.11**

- **Variation Bela Vista** : mise en charte du **formulaire FluentForm** (`.bv-form .fluentform` — labels, champs, focus, checkbox, bouton d'envoi pétrole en pilule, messages succès / erreur) et du **bloc carte** (`.bv-acc .g2rd-map`, coins arrondis). Couleurs 100 % issues de la palette de la variation.

### **1.24.10**

- **Fix Conteneur G2RD (animations)** : les conteneurs avec une animation d'entrée (`fadeInUp`…) restaient **invisibles sur le front** — le CSS les masque (`opacity:0`) en attendant la classe `.g2rd-animated`, mais le script chargé de la poser était absent (aucun `viewScript`). Ajout de `view.js` (`IntersectionObserver` : révèle à l'entrée dans le viewport, applique durée/délai/easing ; repli immédiat en `prefers-reduced-motion`) et déclaration du `viewScript` dans `block.json`.

### **1.24.9**

- **Variation Bela Vista** : finalisation du style bouton — plus d'ombre au repos, ombre `bela` uniquement au survol (bouton plein et variation « outline ») ; variation outline complète (fond transparent, bordure pétrole pilule, padding / typo alignés). Nouveaux helpers `styles.css` : formulaire (`.bv-form`, `.bv-field`, `.bv-consent`, bouton pleine largeur), légendes (`.bv-legend`), plan (`.bv-plan`), grille 2 colonnes responsive (`.bv-grid2`), titre à mot manuscrit (`.bv-title-script`).

### **1.24.8**

- **Fix Conteneur G2RD** : correction des layouts **Grille** et **Flex** dont les blocs enfants s'empilaient au lieu de se répartir en colonnes / en ligne. Cause : un double conteneur (wrapper de `save()` + wrapper de `render.php`) et l'imbrication des `InnerBlocks` dans l'éditeur. Le layout s'applique désormais directement aux enfants (`useInnerBlocksProps` + `save()` nu), avec déprécation et déballage compat pour le contenu déjà enregistré.
- **Variation Bela Vista** : enrichie — polices `@font-face` (Poppins, Dancing Script), presets d'ombres, variation de bouton « outline », spacings alignés sur le thème et feuille `styles.css` d'helpers de mise en page.

### **1.24.7**

- **Variation de style « Bela Vista »** : nouvelle variation FSE (`styles/bela-vista.json`) — charte pétrole & ciel, typographies Poppins + Dancing Script, boutons pilule. Sélectionnable dans l'éditeur de site.

### **1.24.6**

- **Template prestations** : refonte « content-first » de `single-prestations.html` — suppression du hero sombre en haut (le contenu démarre directement sous le header, plus d'espace blanc), ajout d'une navigation article précédent/suivant, conservation des sections « D'autres services » et CTA « Prêts à travailler ensemble ».
- **Fix articles cliquables** : la détection du lien dans une carte cliquable prend désormais en compte un lien qui est un **enfant direct** du groupe (bloc `read-more`), et plus seulement un lien imbriqué dans un enfant. Rétablit le clic sur les cartes (prestations, portfolio, articles…) dont le lien provient du bloc « Lire la suite ».

### **1.24.5**

- **Fix portfolio** : le bouton « Visiter le site » de `single-portfolio.html` pointe enfin vers l'URL du projet. Enregistrement de la source de block binding `g2rd/portfolio-link` (lit le champ `_portfolio_link` du CPT portfolio) et câblage du bouton du template sur ce binding — un champ meta protégé ne peut être lu ni par un shortcode ni par `core/post-meta`, d'où la source custom.

### **1.24.4**

- **Fix activation du globe** : l'animation globe est désormais pilotée par la classe `g2rd-globe-bg` (source unique de vérité). Décocher le toggle de la sidebar **retire réellement le globe** — y compris sur les sections dont la classe provient d'un pattern/template (l'ancien attribut ne supprimait pas la classe manuelle).
- **Globe — UX** : le panneau « Animation globe » n'apparaît que sur les sections sombres (`is-style-section-dark`) ; la position devient une classe `is-globe-{position}` ; le réglage fin (décalage/taille) est conservé avec aperçu temps réel.
- **Globe — simplification** : suppression de l'interrupteur global dans la page d'options (activation par section uniquement) et nettoyage de l'ancien attribut `globeEffect`.

### **1.24.3**

- **Globe — positionnement précis** : les positions droite / gauche / haut / bas sont recentrées (le globe reste entièrement visible, plus de découpe), et un réglage fin est ajouté dans la sidebar — décalage horizontal / vertical (px) et taille — avec aperçu en direct dans le BO et sur le front.
- **Grille filtrable — CTA façon natif WordPress** : les réglages du bouton (couleurs, padding, rayon, bordure, alignements titre / extrait / bouton) passent dans l'onglet **Styles** avec des composants natifs (`UnitControl`, `ToggleGroupControl`). Nouvelle **bordure activable** (largeur + couleur + style, `BorderControl`). Aperçu **temps réel** dans l'éditeur (rendu inline).

### **1.24.2**

- **Animation globe** : le globe filaire devient une fonctionnalité activable / désactivable depuis la page d'options (activée par défaut), avec choix de la **position** (centre / droite / gauche / haut / bas) par section depuis la sidebar de l'éditeur, et **aperçu en direct dans l'éditeur WordPress** (CSS chargé aussi dans le canvas). CSS extrait de `style.css` vers `assets/css/globe.css`.
- **Grille filtrable** : nouveaux réglages du **bouton CTA** (couleur fond / texte + survol, padding, rayon, alignement) et **alignement des textes** (titre, description) depuis l'inspecteur — les overrides ne s'activent que s'ils sont réglés (style de bouton natif préservé par défaut).

### **1.24.1**

- **Fix Magic Page (front)** : `magic-page.css` est désormais chargé sur le front pour les sections `is-style-magic-dark` / `is-style-magic-light` (et les boutons `neomorphic` / `soft-pressed`), y compris quand elles vivent dans un **template FSE**. La détection couvre tous les marqueurs du design system (plus seulement la chaîne `g2rd-magic-page`) et résout le template de bloc courant — corrige le **fond blanc** des sections Magic sur le front (le chargement via `style_handle` au rendu n'était pas imprimé dans le `<head>`).

### **1.24.0**

- **WP Manager — généralisation** : les 19 templates FSE (archives, singles, `page-*`, `home`, `search`, `taxonomy`) alignés sur la charte — covers `bleubeige` → sections sombres `is-style-section-dark` + globe, pill eyebrows lime, cartes `is-style-card`, boutons `is-style-action` / `is-style-outline`, liens `accenthover` (AA).
- **Section wp-manager** : nouveau pattern réutilisable `section-wp-manager.php` (mock dashboard 100 % natif + CTA vers `wp-manager.g2rd.fr`), inséré avant la FAQ de l'accueil.
- **Globe géodésique filaire** : SVG icosphère `assets/img/wire-globe.svg` en fond des sections `.is-style-section-dark.g2rd-globe-bg` (couleur pilotée par le token lime, rotation lente, `prefers-reduced-motion`).
- **Patterns** : sections (services, cta, témoignages, faq…) et cartes (box, info-box, contact, details, présentation…) modernisées ; slugs périmés purgés (`tertiary`, `main`, `base`, `main-accent`, `border-dark`, `spacing|large/medium/small`).
- **Contenus Gutenberg** : dossier `Gutenberg/` (15 pages + template single, prêts à coller) — exclu du ZIP de production.
- **Fix** : FAQ + JSON-LD `FAQPage` dupliqués retirés (le bloc `g2rd/faq` génère déjà son schéma), zéro CSS personnalisé dans les pages, zéro couleur en dur.

### **1.23.0**

- **Refonte design — alignement wp-manager** : palette recalée sur le SaaS `wp-manager.g2rd.fr` (navy `#0f172a`, lime `#a3e635`, dégradé magenta-violet) + slugs sémantiques `border` / `surface` / `success` / `warning` / `danger`.
- **Variations de styles** (FSE, tokens uniquement, zéro CSS) : `core/group` → Section sombre, Carte, Carte sombre, Carte action (dégradé) ; `core/button` → Action (dégradé), Ghost, Outline.
- **Composants modernisés** : hero (pill, titre avec mot-clé surligné lime, double CTA, stats, halo radial), **headers (×2)** et **footer** avec boutons « Se connecter » (→ `g2rd.fr/account/`) et « Contact » (→ `g2rd.fr/contact/`), nouveaux patterns « bandeau de chiffres » et « cartes statistiques ».
- **Animations** : hover lift sur cartes/boutons, halo radial des sections sombres (`color-mix` sur tokens), respect de `prefers-reduced-motion`.
- **RGAA** : lime en aplat uniquement, dégradé d'action assombri pour contraste AA du texte blanc, liens en pink-600 (AA), tout en `var(--wp--…)` / `color-mix`.
- **Docs** : `DESIGN_AUDIT.md` (extraction + mapping FSE) et `CHANGELOG_DESIGN.md`.

### **1.22.0**

- **Charte graphique** : nouvelle palette navy/lime/rose en remplacement de bleu/beige.
- **Couleurs dynamiques** : tout le markup (patterns, parts, templates) et le CSS référencent les variables de la palette (`var(--wp--preset--color--*)` + `color-mix()` pour les translucides) — un changement de charte ou de variation de style se propage automatiquement à tous les blocs, sans repasser sur chacun.
- **Fix template parts** : résolution robuste du header/footer (retrait de l'attribut `theme` codé en dur dans les `wp:template-part`) — corrige l'erreur « élément de modèle supprimé ou non disponible » sur une install neuve ; suppression du template part fantôme `newsletter`.
- **Maintenance** : mises à jour de dépendances Dependabot (`@wordpress/scripts` 32.5.0, `@playwright/test` 1.61.1).

### **1.21.5**

- **Accessibilité (RGAA)** : compteurs affichant leur valeur réelle sans JS, liens de navigation `href="#"` redirigés vers une vraie destination, emojis décoratifs masqués aux lecteurs d'écran (`aria-hidden`), `alt=""` de secours sur les images de galerie, `autocomplete` + association label/champ sur les formulaires.
- **Accessibilité (structure)** : repère `<main>` ajouté à 15 templates — lien d'évitement fonctionnel sur toutes les pages.
- **Fix** : réparation de `archive-prestations.html` (tronqué depuis la 1.8.0 — pied de page et structure restaurés).
- **Grille filtrable** : CTA transformé en bouton WordPress natif avec choix du style natif (cohérence graphique) ; tailles de titre et d'extrait désormais appliquées côté front.

### **1.21.4**

- **Style** : refonte et réorganisation de `style.css` en sections structurées (variables `:root`, reset FSE, animations, images, articles cliquables, effet glass, particules, template produit FluentCart, responsive).
- **Fix** : montée de version pour forcer le rafraîchissement du cache navigateur — les modifications CSS de `style.css` n'étaient pas prises en compte tant que la version (cache-buster `?ver=`) restait identique.

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
