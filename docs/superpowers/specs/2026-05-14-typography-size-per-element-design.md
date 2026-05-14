# Contrôle de taille typographique par élément — G2RD Blocks

**Date :** 2026-05-14
**Statut :** Approuvé
**Scope :** 14 blocs custom (`g2rd/*`)

---

## Contexte

Aucun bloc G2RD ne propose aujourd'hui de contrôle unifié de la taille des éléments textuels (titres, paragraphes, chiffres, citations…). Deux blocs ont un support partiel via des mécanismes hétérogènes (`supports.typography.fontSize` global sur filterable-grid et carousel, `RangeControl` en em sur countdown, `TextControl` libre sur advanced-heading). Les autres blocs ont des tailles codées en dur (inline `clamp`, `rem` fixes).

L'objectif est de permettre à l'éditeur de choisir la taille de chaque élément textuel significatif, depuis l'onglet **Styles** de la sidebar Gutenberg, en utilisant exclusivement les 5 presets typographiques du thème.

---

## Presets typographiques du thème

Définis dans `theme-settings.json`, tous fluides (responsive) :

| Slug | Nom | Taille | Fluide min → max |
|------|-----|--------|-----------------|
| `s`  | Petit | 0.88rem | 0.55rem → 0.88rem |
| `m`  | Moyen | 1.125rem | 1rem → 1.125rem |
| `l`  | Grand | 1.4rem | 1.125rem → 1.4rem |
| `xl` | Très Grand | 3rem | 1.5rem → 3rem |
| `xxl` | Énorme | 4rem | 2rem → 4rem |

Aucune valeur libre n'est autorisée (`disableCustomFontSizes={true}`).

---

## Architecture

### Composant partagé `TypographySizePanel`

**Fichier :** `blocks/shared/TypographySizePanel.js`

Composant unique réutilisable dans tous les `edit.js`. Il :

- S'intègre dans `<InspectorControls group="styles">` → onglet **Styles** de la sidebar
- Récupère les presets via `useSettings('typography.fontSizes')` de `@wordpress/block-editor`
- Génère un `<FontSizePicker>` par élément déclaré, avec label, `disableCustomFontSizes={true}`, et `withSlider={false}`
- Rend le titre de panneau configurable (défaut : "Typographie")

**Interface :**

```jsx
<TypographySizePanel
    title="Typographie"   // optionnel
    elements={[
        {
            label: 'Titre',
            value: attributes.titleFontSize,
            onChange: (v) => setAttributes({ titleFontSize: v }),
        },
        {
            label: 'Texte',
            value: attributes.textFontSize,
            onChange: (v) => setAttributes({ textFontSize: v }),
        },
    ]}
/>
```

### Application des tailles

**Blocs statiques (save.js)** :
```jsx
<h2 style={{ fontSize: titleFontSize || undefined }}>…</h2>
```
Le `|| undefined` évite un attribut `style=""` vide dans le HTML sauvegardé.

**Blocs dynamiques (render.php)** :
```php
$title_font_size = ! empty( $attributes['titleFontSize'] )
    ? esc_attr( $attributes['titleFontSize'] )
    : '';
$title_style = $title_font_size ? 'font-size:' . $title_font_size . ';' : '';
// <h2 style="<?php echo $title_style; ?>">
```

---

## Cartographie des éléments par bloc

### Blocs statiques (save.js)

| Bloc | Éléments | Attributs |
|------|----------|-----------|
| **g2rd-counter** | Chiffre, Libellé | `numberFontSize`, `titleFontSize` |
| **g2rd-hero** | Titre principal, Texte, Accroche | `headingFontSize`, `textFontSize`, `kickerFontSize` |
| **g2rd-card** | Titre, Sous-titre, Description | `headingFontSize`, `subheadingFontSize`, `descriptionFontSize` |
| **g2rd-filterable-grid** | Titre carte, Extrait | `cardTitleFontSize`, `excerptFontSize` |
| **g2rd-testimonial** | Citation, Auteur, Rôle | `quoteFontSize`, `authorFontSize`, `roleFontSize` |
| **g2rd-cta-band** | Titre, Texte | `titleFontSize`, `textFontSize` |
| **g2rd-info** | Titre, Description | `titleFontSize`, `descriptionFontSize` |
| **g2rd-countdown** | Valeur, Libellé | `valueFontSize`, `labelFontSize` |
| **g2rd-carousel** | Titre bloc, Description | `titleFontSize`, `descriptionFontSize` |
| **g2rd-charts** | Libellés axes | `labelFontSize` |
| **g2rd-advanced-heading** | Titre | `fontSize` *(renommé depuis `fontSizeValue`)* |
| **g2rd-geo-summary** | Résumé, Points clés | `summaryFontSize`, `keyPointFontSize` |

### Blocs dynamiques (render.php)

| Bloc | Éléments | Attributs | Fichier render |
|------|----------|-----------|----------------|
| **g2rd-faq** | Question, Réponse | `questionFontSize`, `answerFontSize` | `render.php` |
| **g2rd-geo-faq** | Question, Réponse | `questionFontSize`, `answerFontSize` | `render.php` |

---

## Migrations et breaking changes

### g2rd-countdown

Les attributs `valueSize` (string em) et `labelSize` (string em) sont **supprimés** et remplacés par `valueFontSize` et `labelFontSize` (presets thème). Les blocs sauvegardés sans valeur tomberont sur les tailles CSS par défaut du bloc — comportement acceptable, les tailles hardcodées en CSS restent inchangées.

Le panneau "Apparence" perd les deux `RangeControl` correspondants. Les autres contrôles (layout, timerStyle, animation…) sont conservés.

### g2rd-filterable-grid & g2rd-carousel

`supports.typography.fontSize: true` (et `lineHeight: true` pour carousel) sont **retirés** des `block.json` — le contrôle global WordPress disparaît au profit des `FontSizePicker` par élément. Même conséquence sur les blocs sauvegardés : fallback CSS.

### g2rd-advanced-heading

L'attribut `fontSizeValue` (TextControl valeur libre) est renommé `fontSize` et son contrôle est migré vers `FontSizePicker` presets dans l'onglet Styles. Le panneau "Typographie" existant dans `InspectorControls` (onglet Paramètres) perd ce contrôle — les autres (`fontWeight`, `lineHeight`, `letterSpacing`) restent dans le panneau Paramètres.

---

## Ce qui ne change pas

- Les tailles CSS hardcodées en `clamp()` ou `rem` dans les fichiers `style.css` de chaque bloc restent comme valeurs par défaut (quand `FontSizePicker` est vide / non défini).
- Les couleurs de texte (PanelColorSettings) ne sont pas touchées.
- Les attributs de layout, spacing, animation, etc. ne sont pas modifiés.
- Les blocs pré-compilés sans `src/` (g2rd-breadcrumb, g2rd-modal, etc.) sont hors scope.

---

## Critères de succès

1. L'onglet **Styles** de chaque bloc affiche un panneau "Typographie" avec un `FontSizePicker` par élément textuel.
2. Choisir une taille met à jour l'aperçu dans l'éditeur immédiatement.
3. Le HTML sauvegardé contient l'attribut `style="font-size: …"` uniquement quand une taille est sélectionnée (pas de `style=""` vide).
4. Aucun attribut `style` n'est ajouté aux éléments non configurés.
5. Les blocs dynamiques (faq, geo-faq) appliquent les tailles côté PHP dans `render.php`.
6. PHPCS passe sans erreur sur les fichiers PHP modifiés.
7. Build webpack sans erreur sur les 14 blocs.
