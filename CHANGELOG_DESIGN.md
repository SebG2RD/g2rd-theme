# CHANGELOG_DESIGN.md — Alignement du thème G2RD sur wp-manager.g2rd.fr

Branche : `feat/design-wp-manager` · Référence : `DESIGN_AUDIT.md` · FSE-first, RGAA.

## Principe

`theme.json` (composé depuis `theme-settings.json` + `theme-styles.json`) est la **source
unique** des couleurs/typo/espacements/dégradés/ombres/rayons. Les couleurs étant
**dynamiques** (tout le markup et le CSS référencent `var(--wp--preset--color--…)` depuis la
1.22.0), le recalage des tokens fait adopter la palette wp-manager **partout
automatiquement** : templates, parts (`is-style-primary` = navy), patterns, blocs.

## Tokens ajoutés / modifiés (`theme-settings.json`)

### Palette (recalée sur l'échelle Tailwind de la référence)

| slug | avant (1.22.0) | après (wp-manager) |
| --- | --- | --- |
| `primary` | `#0a0e27` | `#0f172a` (slate-900) |
| `secondary` (lime) | `#b9ff56` | `#a3e635` (lime-400) |
| `accent` (magenta) | `#f43f5e` | `#ec4899` (pink-500) |
| `accent-2` (violet) | `#8b5cf6` | `#a855f7` (purple-500) |
| `cream` | `#f5f6fa` | `#f8fafc` (slate-50) |
| `muted` | `#5b6472` | `#64748b` (slate-500) |
| `blue-dark` | `#060a1a` | `#020617` (slate-950) |
| `blue-soft` | `#283047` | `#1e293b` (slate-800) |

### Nouveaux slugs sémantiques

`border` `#e2e8f0` · `surface` `#f1f5f9` · `success` `#22c55e` · `warning` `#f59e0b` ·
`danger` `#ef4444`.

### Dégradés / ombres / customs

- Dégradé `secondary` (« action ») : `#db2777 → #9333ea` (assombri pour contraste **AA** du
  texte blanc — voir RGAA). `magic-dark`/`magic-light` recalés navy/slate.
- Ombres recalées **slate** + lueurs sur nouvelles couleurs (`secondary-glow`, `accent-glow`).
- `custom.color` : hovers/neutrals recalés sur slate (`neutral-200 #e2e8f0`, `neutral-500
  #64748b`, `neutral-900 #0f172a`, `secondaryhover #84cc16`, `accenthover #db2777`…).
- Duotones recalés navy/lime/magenta-violet.

## Styles globaux (`theme-styles.json`)

- **Blocs natifs** stylés via `styles.blocks` (FSE, tokens) : bouton **outline**, séparateur
  (slate), citation (barre lime), pullquote, table (bordure slate), code (fond `surface` +
  mono).
- **RGAA** : lien `accent` → `accenthover #db2777` (AA sur blanc) ; bouton radius 12→8 px.

## Variations de styles (Étape 3 — `class-block-styles.php` + `theme.json`)

Enregistrées via `register_block_style` (méthode `registerSectionStyles`), **stylées
exclusivement en theme.json** (zéro CSS) :

- `core/group` : **Section sombre**, **Carte**, **Carte sombre**, **Carte action (dégradé)**.
- `core/button` : **Action (dégradé)**, **Ghost** (+ **Outline**).

Ces presets apparaissent dans le sélecteur de styles de l'éditeur Gutenberg.

## Animations (`style.css` — exception CSS justifiée, voir plus bas)

- Hover lift + transition sur cartes (`is-style-card` / `-dark` / `-action`) et boutons.
- Halo radial signature sur les sections sombres (rappel du fond login wp-manager), via
  `color-mix()` sur les tokens `accent-2` / `secondary`.
- Respecte `prefers-reduced-motion` (RGAA).

## Accessibilité / RGAA

- **Lime = aplat uniquement** (fond de bouton/badge avec texte navy) — jamais en texte sur
  fond clair (échec ~1.4:1).
- **Dégradé action** assombri (`#db2777→#9333ea`) → texte blanc **AA**.
- **Liens** en `#db2777` (AA sur blanc) ; en lime sur fonds sombres (sections/cartes sombres).
- **Sections/cartes sombres** : texte blanc (AAA sur navy).
- **Motion** : transitions/transform désactivés sous `prefers-reduced-motion: reduce`.

## Exceptions CSS (objectif : minimal, global, var-only)

| Fichier | Règle | Justification |
| --- | --- | --- |
| `style.css` (section « WP MANAGER — Animations ») | hover lift, transition, halo radial des sections sombres | `:hover` + `transform` + `radial-gradient` **non exprimables en theme.json**. Global (sélecteurs de variations), **variables `--wp--…` uniquement**, aucune valeur en dur, **aucune règle par page**. |

Aucune autre exception. `style.css` n'a pas grossi ailleurs.

## Fichiers touchés

- `theme-settings.json`, `theme-styles.json` (tokens + styles + variations).
- `classes/class-block-styles.php` (enregistrement des variations).
- `style.css` (animations globales, exception ci-dessus).
- `styles/wp-manager.json` **supprimé** (variation obsolète).
- `DESIGN_AUDIT.md`, `CHANGELOG_DESIGN.md` (docs).

## Étape 5 — Blocs custom : état & réserve

- **Déjà fait (1.22.0)** : tout le **CSS** des 41 blocs custom est en `var(--wp--…)` → ils
  **suivent déjà** la palette wp-manager.
- **Non fait — volontairement** : les **valeurs par défaut en dur** dans le **JS source**
  (`#2F425D`/`#D4A373`/`#FAFAFA`, ancienne charte pré-1.22.0) et l'activation de supports
  manquants. Raison : modifier `save.js`/`deprecated.js` d'un bloc **statique** change sa
  sortie sauvegardée → **invalide les instances existantes** (« ce bloc contient du contenu
  inattendu »). Cela viole la contrainte « aucune fonctionnalité cassée » si fait en aveugle.
  → À traiter **bloc par bloc avec test éditeur** (et rebuild) : recaler les defaults, activer
  les supports `color/spacing/border/shadow`, vérifier la parité éditeur/front. Non inclus
  dans ce lot autonome.

## Reste possible (sûr, sur demande)

- Appliquer les variations (`is-style-card`, `section-dark`, bouton **Action**) à des patterns
  existants ou créer des patterns « façon dashboard » (sections cartes, CTA action).
- Étape 5 bloc par bloc (ci-dessus), avec validation éditeur.

## Backoffice — console d'options, chrome admin, onboarding

Même charte que le site (navy `#0f172a`, lime `#a3e635`, pink `#ec4899`→purple `#a855f7`,
neutres slate), appliquée au back-office à la place de l'ancienne charte « logo »
(navy `#2F425D` / doré `#D4A373` / or `#b8924a`).

- **Page d'options React** (`blocks/g2rd-options-page/src/style.css`, `App.js`,
  `FeatureCard.js`) : tokens fixes de la charte (jamais les couleurs configurables de la
  barre d'admin) ; sidebar **navy** avec item actif **lime / texte navy** (façon sidebar
  wp-manager) ; en-tête collant translucide ; en-tête de page (groupe, titre, description) ;
  sections en cartes ; boutons primaires **lime / navy** via les variables
  `--wp-components-color-accent*` (zéro `!important`) ; bouton Enregistrer en dégradé
  « action » assombri (texte blanc AA) ; liens en pink AA `#db2777` ; toast de notification ;
  lien d'évitement ; hiérarchie h1 → h2 (page) → h3 (sections) ; Inter **variable**
  (`inter-var.woff2`, graisses réelles).
- **Chrome admin** (`assets/css/admin.css`) : habillage complet de l'administration hors
  éditeur de blocs — Inter variable sur tout le back-office (`@font-face` injecté par
  `ThemeAdmin::registerAdminAssets()`), champs 36 px arrondis avec anneau de focus navy,
  boutons secondaires blancs/slate et primaires (couleurs configurables, forme ici), onglets
  `nav-tab` à surlignage lime, filtres de statut en pastilles, tableaux de listes arrondis
  avec en-tête slate et survol, boîtes/cartes/plugins/thèmes à rayon 12 px, notices et
  panneau « Options de l'écran » harmonisés. Barre/menu toujours configurables (slugs de
  palette, défauts = charte, texte sur lime = **navy**), entrées de menu arrondies.
  Bannières G2RD et widgets tableau de bord sans styles inline ni emoji (dashicons).
- **Onboarding** (`assets/css/onboarding.css`, `class-onboarding.php`) : mêmes tokens,
  étapes/cartes secteur avec dashicons, bouton héro lime / navy.
- **Supprimé** : `assets/css/admin-options.css` et `assets/js/admin-options.js` (ancienne
  page d'options PHP, plus référencés).
