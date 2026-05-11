# Corrections RGAA — Thème FSE g2rd-theme

**Date :** 2026-05-11  
**Critères :** RGAA 9.1 (citations sémantiques), RGAA 12.8 (aria-current navigation)  
**Périmètre :** Thème uniquement — les corrections plugins (Fluent Forms, FluentCart) restent dans `rgaa-fixes-v2.php` (sandbox Novamira)

---

## Contexte

Un audit RGAA a relevé des non-conformités. Le fichier `inc/rgaa-accessibility.php` contient déjà 8 sections de corrections. Cette session finalise les corrections relevant du thème FSE et nettoie le fichier des corrections plugins qui y avaient été placées temporairement.

---

## Tâche 1 — aria-current="page" sur la navigation (RGAA 12.8)

**Fichier :** `inc/rgaa-accessibility.php` — section 7

Ajouter dans le hook `wp_footer` existant le script qui compare `window.location.href` avec chaque `href` des liens `nav a[href]` et pose `aria-current="page"` sur la correspondance.

La normalisation supprime les slashs finaux et les fragments hash pour éviter les faux négatifs.

```js
var cur = window.location.href.replace(/\/$/, "").replace(/#.*$/, "");
document.querySelectorAll("nav a[href]").forEach(function(a){
    var h = a.href.replace(/\/$/, "").replace(/#.*$/, "");
    if (h === cur) { a.setAttribute("aria-current", "page"); }
});
```

---

## Tâche 2 — Sémantique blockquote/cite dans le bloc témoignages (RGAA 9.1)

**Fichiers :** `blocks/g2rd-testimonial/src/save.js`, `view.js`, `index.js`, `style.css`

### save.js — mode manuel

- Wrapper racine `<div {...blockProps}>` → `<blockquote {...blockProps}>`
- Nom d'auteur : envelopper `RichText.Content tagName="strong"` dans `<cite>` :
  ```jsx
  <cite className="g2rd-testimonial__name" style={{ color: authorColor, display: "block", fontWeight: 700 }}>
    <RichText.Content tagName="strong" value={authorName} />
  </cite>
  ```
- Retrait du style inline `display:block; fontWeight:700` du `RichText.Content` (déplacé sur `cite`)

### index.js — entrée deprecated

Ajouter une entrée `deprecated` pour valider les blocs déjà en base (ancien markup `<div>`).  
Le code de l'ancien `save()` est dupliqué inline dans `deprecated[0].save`.

### view.js — mode Google Reviews

- `renderCard` : `<div class="g2rd-testimonial__card...">` → `<blockquote class="g2rd-testimonial__card...">`
- Nom sans lien : `<strong class="g2rd-testimonial__name">` → `<cite class="g2rd-testimonial__name"><strong>...</strong></cite>`
- Nom avec lien : `<a class="g2rd-testimonial__name--link">` → `<cite class="..."><a>...</a></cite>`

### style.css — reset navigateur

```css
blockquote.g2rd-testimonial,
blockquote.g2rd-testimonial__card {
    margin: 0;
}
```

### Build

```bash
cd blocks/g2rd-testimonial && npm run build
```
Ou depuis la racine : `npm run build:testimonial`

---

## Tâche 3 — Zoom 200% overflow-x (RGAA 10.4)

**Statut : déjà réalisée.** `overflow-x: clip` est présent aux lignes 9 et 27 de `assets/css/magic-page.css`. Aucune modification requise.

---

## Tâche 4 — Nettoyage de inc/rgaa-accessibility.php

**Fichier :** `inc/rgaa-accessibility.php`

### Retirer entièrement

- **Section 5** (lignes ~116-152) : Fluent Forms champs — tabindex, autocomplete, label sr-only
- **Section 6** (lignes ~154-173) : Fluent Forms bouton — tabindex, aria-label redondant
- **Dans section 7** : le bloc honeypot `.ff-hpsf-container` aria-hidden

Ces corrections sont déjà couvertes par `rgaa-fixes-v2.php` dans la sandbox Novamira.

### Sections conservées après nettoyage

| Section | Description | Critère RGAA |
|---------|-------------|--------------|
| 1 | Liens sociaux aria-label (`core/social-link`) | 6.1 |
| 2 | Lien "Lire la suite" aria-label (`core/read-more`) | 6.1 |
| 3 | Lien politique de confidentialité (WP core) | 6.1 |
| 4 | Formulaire de commentaires (WP core) | 6.1 |
| 7 | Skip-link tabindex + aria-current navigation | 12.8 |
| 8 | Images décoratives `wp_get_attachment_image` | 1.2, 1.6 |

---

## Stratégie de déploiement

- **Approche A** : Commit unique groupé — PHP et JS indépendants, pas de risque de conflit
- Message de commit : `fix: corrections RGAA 9.1, 12.8 — blockquote/cite testimonial + aria-current nav + nettoyage FF`

## Vérification post-déploiement

1. `npm run build:testimonial` — zéro erreur
2. Vérifier zéro erreur PHP dans les logs
3. Purger le cache LiteSpeed
4. Tester navigation : attribut `aria-current="page"` présent sur le lien actif
5. Tester bloc témoignage : HTML source contient `<blockquote>` et `<cite>`
6. Audit axe DevTools / WAVE

## Ce qui reste dans la sandbox (ne pas déplacer dans le thème)

- FluentCart iframe : frameborder, title (RGAA 2.1, 2.2)
- Fluent Forms : honeypot, champs requis, erreurs role=alert, labels, autocomplete, bouton
- Images décoratives plugin-level
