# Design Tokens

Tous les tokens sont définis dans `theme-settings.json` et exposés automatiquement en variables CSS par WordPress.

---

## Couleurs

Variables CSS : `var(--wp--preset--color--{slug})`

| Nom | Slug | Hex | Usage recommandé |
|-----|------|-----|-----------------|
| Bleu profond | `primary` | `#2F425D` | Couleur principale — CTA, titres, éléments forts |
| Beige doré | `secondary` | `#D4A373` | Couleur d'accent — hover, séparateurs, highlights |
| Blanc éclatant | `white` | `#FAFAFA` | Fonds clairs, texte sur fond sombre |
| Bleu sombre | `blue-dark` | `#172233` | Fonds sections sombres, dark mode |
| Bleu doux | `blue-soft` | `#3C5473` | Variante intermédiaire du primary |
| Crème | `cream` | `#F7F2EA` | Fonds doux, sections alternées |
| Texte atténué | `muted` | `#6B7280` | Textes secondaires, labels, métadonnées |

### Couleurs de survol (variables custom)

Variables CSS : `var(--wp--custom--color--{slug})`

| Nom | Variable | Hex |
|-----|----------|-----|
| Primary hover | `--wp--custom--color--primaryhover` | `#4B6A95` |
| Secondary hover | `--wp--custom--color--secondaryhover` | `#8C5C2B` |
| White hover | `--wp--custom--color--whitehover` | `#CCCCCC` |

### Neutres (variables custom)

| Token | Variable | Valeur |
|-------|----------|--------|
| Neutral 50 | `--wp--custom--color--neutral-50` | `#F8FAFC` |
| Neutral 100 | `--wp--custom--color--neutral-100` | `#F1F5F9` |
| Neutral 200 | `--wp--custom--color--neutral-200` | `#E2E8F0` |
| Neutral 500 | `--wp--custom--color--neutral-500` | `#64748B` |
| Neutral 700 | `--wp--custom--color--neutral-700` | `#334155` |
| Neutral 900 | `--wp--custom--color--neutral-900` | `#0F172A` |

---

## Dégradés

Variables CSS : `var(--wp--preset--gradient--{slug})`

| Nom | Slug | Définition | Usage |
|-----|------|-----------|-------|
| Bleu dégradé | `blue` | `linear-gradient(90deg, #2f425d → #fafafa)` | Bannières, hero sections |
| Beige dégradé | `beige` | `linear-gradient(90deg, #d4a373 → #fafafa)` | Sections chaudes |
| Bleu beige | `bleubeige` | `linear-gradient(135deg, #2f425d → #d4a373)` | CTA, badges premium |
| Section sombre | `magic-dark` | `linear-gradient(135deg, #172233 → #2f435e → #111827)` | Sections Magic Dark |
| Section claire | `magic-light` | `linear-gradient(180deg, #ffffff → #f7f2ea)` | Sections Magic Light |

---

## Typographie

### Familles de polices

Variables CSS : `var(--wp--preset--font-family--{slug})`

| Nom | Slug | Classification | Usage recommandé |
|-----|------|---------------|-----------------|
| Rubik | `rubik` | Sans-serif géométrique | Corps de texte principal |
| Quantum | `Quantum` | Display futuriste | Titres impact, hero |
| DM Serif Display | `dm-serif-display` | Serif élégant | Titres éditoriaux |
| Audiowide | `audiowide` | Display technologique | Labels, badges, UI |
| Inter | `inter` | Sans-serif neutre | Interface, formulaires |
| Plus Jakarta Sans | `plus-jakarta-sans` | Sans-serif moderne | Titres et corps alternatif |
| Acme | `acme` | Display condensé | Titres accrocheurs |
| Borel | `borel` | Script cursive | Accents décoratifs |

### Tailles de police

Variables CSS : `var(--wp--preset--font-size--{slug})`

La typographie est **fluide** : les valeurs s'adaptent entre un minimum (mobile) et un maximum (desktop).

| Nom | Slug | Valeur | Min (mobile) | Max (desktop) |
|-----|------|--------|-------------|--------------|
| Petit | `s` | 0.88rem | 0.55rem | 0.88rem |
| Moyen | `m` | 1.125rem | 1rem | 1.125rem |
| Grand | `l` | 1.4rem | 1.125rem | 1.4rem |
| Très Grand | `xl` | 3rem | 1.5rem | 3rem |
| Énorme | `xxl` | 4rem | 2rem | 4rem |

### Graisses et interlignes (variables custom)

| Token | Variable | Valeur |
|-------|----------|--------|
| Regular | `--wp--custom--font-weight--regular` | 400 |
| Semi-bold | `--wp--custom--font-weight--semibold` | 600 |
| Bold | `--wp--custom--font-weight--bold` | 700 |
| Interligne serré | `--wp--custom--line-height--narrow` | 1.2 |
| Interligne normal | `--wp--custom--line-height--regular` | 1.5 |
| Interligne large | `--wp--custom--line-height--large` | 1.8 |

---

## Espacements

Variables CSS : `var(--wp--preset--spacing--{slug})`

| Nom | Slug | Valeur |
|-----|------|--------|
| Très petit | `xs` | 1rem (16px) |
| Petit | `s` | 1.6rem (25.6px) |
| Moyen | `m` | 3.2rem (51.2px) |
| Grand | `l` | 4.8rem (76.8px) |
| Très grand | `xl` | 6.4rem (102.4px) |

**Règle** : utiliser exclusivement ces espacements pour les paddings et margins des sections. Ne jamais utiliser de valeurs `rem` brutes.

---

## Ombres

Variables CSS : `var(--wp--preset--shadow--{slug})`

| Nom | Slug | Intensité | Usage |
|-----|------|-----------|-------|
| Très légère | `xs` | Minimale | Cartes en fond clair, séparation subtile |
| Douce | `light` | Légère | Cartes standards, éléments flottants |
| Moyenne | `md` | Modérée | Modales, dropdowns |
| Fine | `neat` | Décalée | Boutons, badges avec relief |
| Imposante | `huge` | Forte | Composants mis en avant |
| Élevée | `xl` | Maximale | Hero, sections principales |
| Lueur principale | `primary-glow` | Glow bleu | Boutons CTA, éléments primary |
| Lueur secondaire | `secondary-glow` | Glow beige | Éléments accent, hover states |
| Magic — Carte | `magic` | Dramatique | Cartes Magic Page |
| Magic — Carte XL | `magic-xl` | Très dramatique | Hero Magic Page |

---

## Rayons de bordure

Variables CSS : `var(--wp--custom--radius--{slug})`

| Slug | Valeur | Usage |
|------|--------|-------|
| `xs` | 2px | Badges, tags |
| `s` | 4px | Inputs, boutons carrés |
| `m` | 10px | Cartes, panneaux |
| `l` | 20px | Sections arrondies |
| `xl` | 32px | Grands composants |
| `full` | 999px | Boutons pilule, avatars |

---

## Boutons (variables custom)

| Token | Variable | Valeur |
|-------|----------|--------|
| Rayon standard | `--wp--custom--button--radius` | 10px |
| Rayon pilule | `--wp--custom--button--radius-pill` | 999px |
| Rayon carré | `--wp--custom--button--radius-square` | 4px |
| Padding horizontal | `--wp--custom--button--padding-h` | 1.5rem |
| Padding vertical | `--wp--custom--button--padding-v` | 0.75rem |
| Graisse | `--wp--custom--button--font-weight` | 600 |
| Transition | `--wp--custom--button--transition` | 0.2s ease |

---

## Layout

| Paramètre | Valeur |
|-----------|--------|
| Largeur contenu | 960px |
| Largeur large (wide) | 1440px |
