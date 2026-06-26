# DESIGN_AUDIT.md — Alignement du thème G2RD FSE sur wp-manager.g2rd.fr

> **Statut : à valider.** Aucune modification du thème n'a été faite. Ce document
> extrait les tokens de la **référence** (wp-manager), les mappe vers des slugs FSE
> stables, et liste les écarts avec le thème actuel (1.22.0).

## 0. Périmètre (clarifié avec toi)

- **But** : que `g2rd.fr` se *lise comme la même société* que `wp-manager.g2rd.fr` —
  même **couleurs**, **forme des composants** et **style général**.
- **PAS** de création de pages / clonage d'écrans SaaS. On reprend la **forme** (boutons,
  cartes, inputs, badges, sections sombres, sidebar-look) et on l'applique **globalement**
  via `theme.json` + styles de blocs + variations de styles.
- **FSE-first** : tout en `theme.json` / variables `--wp--…`. Zéro CSS par page, zéro hex/px
  en dur. Une exception CSS n'est tolérée que si impossible en theme.json, et alors uniquement
  en `var(--wp--…)`, justifiée et globale.
- **RGAA / accessibilité = critère bloquant**, avec vigilance sur le **texte sur dégradé**.

## 1. Sources analysées

| Source | Nature | Apport |
| --- | --- | --- |
| `docs/figma-header.png` | Header design-system | logo lime, nav, bouton sombre, recherche |
| `docs/figma-login.png` | Écran d'auth | fond navy + halos radiaux, carte blanche, bouton lime |
| `docs/figma-dashboard.png` | Dashboard app | sidebar navy, stat-cards (sombre / dégradé / claire), table, badges |
| `docs/figma-overview.png` | « System Identity & Visual Standards » | swatches (Primary/Accent/Gradient), échelle typo, variantes de boutons |
| `docs/figma-General-Settings.png` | Réglages | cartes blanches, inputs/selects, labels uppercase, toggle, save/discard |
| (`figma-site.png`, `figma-users.png`) | Vues app | corroborent le même système (tables, cartes) |

> **Note sur l'exactitude** : les valeurs hex ci-dessous sont **déduites** des captures et
> de la convention **Tailwind CSS** que la maquette suit clairement (échelles `slate`,
> `lime`, `pink`, `purple`). À **confirmer au pixel près** depuis les variables Figma si tu
> les as ; je les ai calées sur les pas Tailwind standards les plus proches.

## 2. Tokens de référence extraits

### 2.1 Couleurs

| Rôle | Valeur (déduite) | Où, dans la réf. |
| --- | --- | --- |
| **Navy / sombre signature** | `#0f172a` (slate-900) | sidebar, cartes sombres, bouton « Export Assets » |
| **Navy très profond** | `#020617` (slate-950) | fond de l'écran login |
| **Navy doux (surfaces sombres 2)** | `#1e293b` (slate-800) | hover sidebar, bordures internes sombres |
| **Lime / accent de marque** | `#a3e635` (lime-400) — variante claire `#bef264` | logo, item actif, boutons primaires, badges ONLINE/ACTIVE, shield 100% |
| **Dégradé « action »** | `linear-gradient(135deg, #ec4899 0%, #a855f7 100%)` (pink-500 → purple-500) | carte « UPDATES READY », boutons « UPDATE NOW » |
| **Blanc / surface carte** | `#ffffff` | cartes, header, carte login |
| **Fond de page clair** | `#f8fafc` (slate-50) | zone de contenu de l'app |
| **Gris champ / input** | `#f1f5f9` (slate-100) | inputs, selects, search |
| **Texte titre** | `#0f172a` (slate-900) | titres, valeurs |
| **Texte courant** | `#334155` (slate-700) | paragraphes |
| **Texte atténué / labels** | `#64748b` (slate-500) | labels uppercase, sous-titres |
| **Bordure** | `#e2e8f0` (slate-200) | cartes, tables, séparateurs |
| **Succès** | lime (`#a3e635`) ou `#22c55e` | badges ONLINE / ACTIVE / score |
| **Danger / « NEW »** | `#ef4444` / le pink `#ec4899` | indicateurs de mise à jour |
| **Avertissement** | `#f59e0b` (amber-500) | icône Maintenance Mode |
| **Texte sur sombre** | `#ffffff` / `#cbd5e1` (slate-300) | sidebar, sections sombres |

### 2.2 Dégradés signature

- **`action` (pink→purple)** : `linear-gradient(135deg, #ec4899 0%, #a855f7 100%)` — emphase
  « mise à jour / action ». ⚠️ voir RGAA §5.
- **`dark-glow` (fond login)** : navy `#020617` + halos radiaux subtils
  (violet en haut-droite, lime en bas-gauche) — équivalent de l'actuel `magic-dark`.

### 2.3 Typographie

- **Famille** : sans-serif géométrique type **Inter** (corps + titres). Le thème a déjà
  `Inter` et `Plus Jakarta Sans` → réutilisables tels quels.
- **Titres** : gras (600–800), interlettrage serré (≈ -0.02em sur les grands titres).
- **Labels** : UPPERCASE, ~11–12px, interlettrage large (≈ +0.08em), gris `#64748b`.
- **Échelle (déduite)** : H1 ≈ 32–40px / H2 ≈ 22–24px / H3 ≈ 18px / corps ≈ 14–16px /
  label ≈ 11–12px. Typo fluide (clamp) pertinente sur H1/H2.

### 2.4 Espacements & layout

- Sidebar ≈ **240–260px**, contenu max ≈ **1126px** (cohérent avec le `#root: 1126px` du repo).
- Padding cartes généreux ≈ **24–32px** ; gaps grille ≈ **16–24px**.
- → cible `layout.contentSize ≈ 1126px`, `wideSize ≈ 1440px`.

### 2.5 Rayons

| Élément | Rayon |
| --- | --- |
| Cartes / sections | 12–16px |
| Boutons | 8–10px |
| Inputs / selects | 8px |
| Pills / badges | 999px (full) |
| Boîtes d'icône / avatars | 10–12px |

### 2.6 Ombres (élévation)

- Très douces, type Tailwind `shadow-sm` / `shadow`/`shadow-md` :
  `0 1px 2px rgba(15,23,42,.06)`, `0 1px 3px rgba(15,23,42,.10)`,
  `0 4px 6px -1px rgba(15,23,42,.10)`. Carte « dégradé » un cran au-dessus.

### 2.7 Transitions

- `~0.2s ease` (boutons/nav), `~0.3s` sur hovers d'ombre. Le thème a déjà
  `--transition-standard: 0.2s` / `--transition-slow: 0.5s`.

## 3. Mapping token de référence → slug FSE

> **Décision recommandée : garder les slugs ACTUELS** (`primary`, `secondary`, `accent`,
> `white`, `cream`, `muted`, `blue-dark`, `blue-soft`, `accent-2`) et **remapper leurs
> valeurs** + **ajouter** les slugs sémantiques manquants. Raison : tout le markup, le CSS
> et les blocs de la 1.22.0 référencent déjà ces slugs — les renommer re-casserait tout.
> J'ajoute des slugs sémantiques (`surface`, `border`, `success`, `warning`, `danger`) sans
> retirer l'existant.

| Réf. (rôle) | Valeur cible | Slug FSE | Variable générée |
| --- | --- | --- | --- |
| Navy signature | `#0f172a` | `primary` | `--wp--preset--color--primary` |
| Navy profond (login) | `#020617` | `blue-dark` | `--wp--preset--color--blue-dark` |
| Navy doux (surf. sombre 2) | `#1e293b` | `blue-soft` | `--wp--preset--color--blue-soft` |
| Lime accent | `#a3e635` | `secondary` | `--wp--preset--color--secondary` |
| Pink (début dégradé) | `#ec4899` | `accent` | `--wp--preset--color--accent` |
| Purple (fin dégradé) | `#a855f7` | `accent-2` | `--wp--preset--color--accent-2` |
| Blanc | `#ffffff` | `white` | `--wp--preset--color--white` |
| Fond clair de page | `#f8fafc` | `cream` | `--wp--preset--color--cream` |
| Texte atténué | `#64748b` | `muted` | `--wp--preset--color--muted` |
| **Bordure** (nouveau) | `#e2e8f0` | `border` | `--wp--preset--color--border` |
| **Surface input** (nouveau) | `#f1f5f9` | `surface` | `--wp--preset--color--surface` |
| **Succès** (nouveau) | `#22c55e` | `success` | `--wp--preset--color--success` |
| **Avertissement** (nouveau) | `#f59e0b` | `warning` | `--wp--preset--color--warning` |
| **Danger** (nouveau) | `#ef4444` | `danger` | `--wp--preset--color--danger` |

**Dégradés** (`settings.color.gradients`) :

| Slug | Valeur cible |
| --- | --- |
| `secondary` (déjà présent, à recaler) | `linear-gradient(135deg, #ec4899 0%, #a855f7 100%)` |
| `magic-dark` (déjà présent) | navy `#020617`/`#0f172a` (fond sombre signature) |

**Custom** (`settings.custom`) — rayons / transitions déjà présents et **déjà bien calés**
(`radius.s=8 / m=12 / l=16 / full=999`, `transition 0.2s`). Ajouts éventuels : `radius.card=14px`.

## 4. Écarts thème actuel (1.22.0) → cible wp-manager

| Token | Actuel (1.22.0) | Cible | Action |
| --- | --- | --- | --- |
| `primary` | `#0a0e27` | `#0f172a` | recaler (navy un peu plus bleu, = signature dashboard) |
| `secondary` (lime) | `#b9ff56` | `#a3e635` | recaler sur le lime exact de la réf. |
| `accent` | `#f43f5e` (rose) | `#ec4899` (pink) | recaler (début du dégradé action) |
| `accent-2` | `#8b5cf6` | `#a855f7` | recaler (fin du dégradé action) |
| `cream` | `#f5f6fa` | `#f8fafc` | recaler (slate-50) |
| `muted` | `#5b6472` | `#64748b` | recaler (slate-500) |
| `blue-dark` | `#060a1a` | `#020617` | recaler (slate-950, fond login) |
| `blue-soft` | `#283047` | `#1e293b` | recaler (slate-800) |
| gradient `secondary` | rose→violet | `#ec4899→#a855f7` | recaler |
| `border` / `surface` / `success` / `warning` / `danger` | **absents** | voir §3 | **ajouter** |
| Rayons / transitions / ombres | présents, proches | quasi identiques | ajustements mineurs |

**Écarts de COMPOSANTS** (le gros du travail, en `styles.blocks` + variations) :

| Composant réf. | À produire en FSE (global) |
| --- | --- |
| Bouton **primaire lime + texte navy** | `core/button` par défaut (déjà ~ok en 1.22.0, recaler lime) |
| Bouton **dégradé** pink→purple + texte blanc | variation de style `core/button` « Action » |
| Bouton **outline** (blanc + bordure) | variation `core/button` « Outline » |
| Bouton **ghost** | variation `core/button` « Ghost » |
| **Carte** blanche (rayon 12-16, bordure, ombre douce) | variation `core/group` « Carte » |
| **Carte sombre** (navy) | variation `core/group` « Carte sombre » |
| **Carte dégradé** (emphase) | variation `core/group` « Carte action » |
| **Section sombre** (look dashboard) | variation `core/group` « Section sombre » (#0f172a) |
| **Badge / pill** (lime, pink) | styles `core/button`/inline ou pattern badge |
| **Inputs / selects** (gris clair, label uppercase) | `styles.blocks` formulaires (search/post-comment) |
| **Tabs** (souligné lime) | styles de nav / pattern |
| **Sidebar look** (navy + item actif lime pill) | variation header/section + nav |

## 5. Accessibilité / RGAA — points bloquants

| Combo | Contraste estimé | Verdict | Règle |
| --- | --- | --- | --- |
| Texte **blanc** sur **navy `#0f172a`** | ~16:1 | ✅ AAA | sections sombres OK |
| Texte **navy** sur **lime `#a3e635`** | ~9:1 | ✅ AA/AAA | **boutons lime → texte navy** (jamais blanc) |
| **Lime en TEXTE** sur blanc | ~1.4:1 | ❌ échec | le lime ne sert qu'en **fond** (texte sombre dessus) ou petit aplat — **jamais en texte** |
| **Blanc** sur **dégradé pink→purple** | pink ~3.5:1 / purple ~4:1 | ⚠️ limite | OK seulement en **grand/gras** (≥24px ou ≥18.66px bold) ; pour du texte normal → assombrir le dégradé ou texte navy |
| `muted #64748b` sur blanc | ~4.6:1 | ✅ AA (normal) | labels OK |
| Texte sur **halos radiaux** (login) | variable | ⚠️ | garder le texte sur la zone navy unie, pas sur les halos |

→ **Conséquences design** : (1) le lime est une couleur d'**aplat** (fond de bouton/badge avec
texte navy), jamais un texte ; (2) le **dégradé action** ne porte que des titres **grands/gras**
blancs, sinon on prévoit une variante assombrie ; (3) on vérifie chaque variation à la
construction (Étape 3-4).

## 6. Synthèse — niveau d'alignement actuel

La charte **1.22.0 est déjà ~70 % alignée** (navy + lime + accents rose/violet, rayons,
ombres, typo Inter). L'écart à combler :

1. **Recaler** ~9 valeurs de couleurs sur les hex exacts (slate / lime / pink-purple).
2. **Ajouter** les slugs sémantiques `border`, `surface`, `success`, `warning`, `danger`.
3. **Construire les composants** (boutons dégradé/outline/ghost, cartes claire/sombre/action,
   section sombre, badges, inputs) en **variations de styles + `styles.blocks`**, appliqués
   globalement — c'est le cœur de la « ressemblance ».

## 7. Approche recommandée (pour l'Étape 2+, après ta validation)

1. **`theme.json`** = source unique : recaler les couleurs, ajouter slugs sémantiques + le
   dégradé `action`, vérifier rayons/ombres/typo. (Étape 2)
2. **Variations de styles de section** `core/group` : « Section sombre », « Carte »,
   « Carte sombre », « Carte action ». (Étape 3)
3. **Blocs natifs** via `styles.blocks` : boutons (+ variations), titres, inputs, tables,
   séparateurs. (Étape 4)
4. **Blocs custom** : retirer les hex restants → variables, activer les supports. (Étape 5)
5. **Templates / parts** (header/footer/sidebar) : adopter les tokens + variations, sans CSS
   par page. (Étape 6)
6. **Patterns** = compositions réutilisables dans le **style** wp-manager (sections, cartes,
   CTA) — **pas des pages**. (Étape 7)
7. **`CHANGELOG_DESIGN.md`** + liste (idéalement vide) des exceptions CSS justifiées.

---

### ❓ Décisions à valider avant l'Étape 2

1. **Valeurs hex** : je pars sur les pas **Tailwind** déduits (§2) — OK, ou tu me confirmes
   les hex exacts depuis Figma (surtout le **lime** : `#a3e635` vs `#bef264`, et le **navy** :
   `#0f172a`) ?
2. **Slugs** : on **garde les slugs actuels en remappant les valeurs** (recommandé, ne casse
   rien) + ajout des sémantiques — OK ?
3. **Dégradé action** : recaler sur **pink→purple `#ec4899→#a855f7`** (remplace le rose→violet
   actuel) — OK ?
4. **Lime = aplat only** (jamais en texte, RGAA) — OK comme règle de marque ?
