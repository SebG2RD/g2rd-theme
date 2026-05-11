# Audit RGAA — Améliorations accessibilité thème g2rd-theme

**Date :** 2026-05-11  
**Critères couverts :** RGAA 10.1, 13.1, 13.2, 2.4  
**Périmètre :** Thème uniquement — plugins exclus  
**Source :** Audit complet automatisé — 27 non-conformités identifiées

---

## Résumé des non-conformités

| Critère | Description | Sévérité | Fichiers |
|---------|-------------|----------|---------|
| **RGAA 10.1** | Focus visible supprimé (`outline:none`) | MAJEUR/BLOQUANT | 5 CSS |
| **RGAA 13.1** | Animations sans `prefers-reduced-motion` | MAJEUR | carousel.css, marquee CSS, 2× view.js |
| **RGAA 13.2** | Animations longues sans mécanisme de pause | BLOQUANT | g2rd-marquee, g2rd-testimonial |
| **RGAA 2.4** | `<header>` décoratif sans `aria-label` | MINEUR | header-color.html |

---

## Stratégie d'implémentation

Approche A : un commit par critère RGAA pour la traçabilité.

---

## Commit 1 — RGAA 10.1 : Focus visible

### Problème
5 fichiers CSS suppriment le focus visible (`outline: none`) sans fournir de style `:focus-visible` alternatif. Les utilisateurs clavier ne voient plus l'indicateur de focus.

### Pattern de correction
```css
/* Remplacer */
element:focus { outline: none; }

/* Par */
element:focus { outline: none; }
element:focus-visible {
  outline: 2px solid var(--wp--preset--color--primary);
  outline-offset: 2px;
}
```

La couleur du ring s'adapte au contexte (front, admin, éditeur).

### Fichiers et corrections

#### `assets/css/admin-options.css` — ligne 141
Sélecteur `.g2rd-help-btn:focus` → ajouter `:focus-visible` avec ring `var(--g2rd-blue)` (couleur déjà utilisée dans le fichier aux lignes 277-278).

#### `blocks/g2rd-carousel/src/carousel.css`
Sélecteurs `.swiper-button-prev:focus, .swiper-button-next:focus` → ajouter `:focus-visible` avec `outline: 2px solid rgba(255,255,255,0.8)` (fond sombre du carousel).

#### `blocks/g2rd-code/src/*.css` (ou index.css si pas de src)
Sélecteur `.g2rd-code-editor__editor textarea` — `outline: none !important` → ajouter `:focus-visible { outline: 2px solid rgba(255,255,255,0.4); outline-offset: -2px; }` (contexte fond sombre de l'éditeur de code).

#### `blocks/g2rd-counter/src/editor.css` — ligne 15
Contexte éditeur admin → ring `var(--wp-admin-theme-color, #3858e9)`.

#### `assets/css/login.css` — ligne 181
Formulaire login → ring `var(--wp--preset--color--primary)` ou couleur de marque.

### Commit message
```
fix: RGAA 10.1 — focus visible sur boutons et champs (outline:none → :focus-visible)
```

---

## Commit 2 — RGAA 13.1 : prefers-reduced-motion

### Problème
Les animations CSS du carrousel et du marquee s'exécutent sans respecter la préférence système `prefers-reduced-motion: reduce`. Les utilisateurs photosensibles ne peuvent pas désactiver les mouvements depuis leur OS.

### Fichiers et corrections

#### `blocks/g2rd-carousel/src/carousel.css`
Ajouter en fin de fichier un bloc `@media (prefers-reduced-motion: reduce)` qui :
- Met toutes les `transition` à `none` sur `.swiper-slide`, `.carousel-slide`, `.carousel-image`, `.carousel-caption`, `.carousel-post-info`, `.swiper-button-prev`, `.swiper-button-next` et leurs pseudo-éléments
- Supprime `@keyframes slideInButtons` (en ciblant les éléments qui l'utilisent)

```css
@media (prefers-reduced-motion: reduce) {
  .swiper-slide,
  .carousel-slide,
  .carousel-image,
  .carousel-caption,
  .carousel-post-info,
  .swiper-button-prev,
  .swiper-button-next,
  .swiper-button-prev::before,
  .swiper-button-prev::after,
  .swiper-button-next::before,
  .swiper-button-next::after {
    transition: none !important;
    animation: none !important;
  }
}
```

#### `blocks/g2rd-marquee/style-index.css` (pré-compilé)
Ajouter en fin de fichier :
```css
@media (prefers-reduced-motion: reduce) {
  .g2rd-marquee__track {
    animation: none !important;
    display: flex;
    flex-wrap: wrap;
    overflow: visible;
  }
}
```

#### `blocks/g2rd-testimonial/src/view.js` — fonction `buildMarquee()`
Avant d'appliquer `animationDuration`, vérifier la préférence :
```js
function buildMarquee(cardsHTML, speed) {
  const wrapper = document.createElement('div');
  wrapper.className = 'g2rd-testimonial__marquee';
  const track = document.createElement('div');
  track.className = 'g2rd-testimonial__marquee-track';
  track.innerHTML = cardsHTML + cardsHTML;

  if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    track.style.animationDuration = (speed || 40) + 's';
  } else {
    track.innerHTML = cardsHTML; // pas de duplication si statique
    track.style.display = 'flex';
    track.style.flexWrap = 'wrap';
    track.style.animationName = 'none';
  }
  wrapper.appendChild(track);
  return wrapper;
}
```

#### `blocks/g2rd-marquee/view.js` (pré-compilé)
Même vérification `window.matchMedia('(prefers-reduced-motion: reduce)')` si l'animation est pilotée via JS dans ce fichier (à vérifier à l'implémentation).

### Commit message
```
fix: RGAA 13.1 — prefers-reduced-motion carousel, marquee, testimonial marquee
```

---

## Commit 3 — RGAA 13.2 : Bouton pause marquee BO-configurable

### Problème
Le bloc `g2rd-marquee` et le mode marquee de `g2rd-testimonial` produisent une animation continue (≥ 40 s) sans mécanisme visible permettant à un utilisateur clavier de la mettre en pause.

### Décision d'architecture
- **Carrousel** : exempt — la pause au focus clavier (`focusin` → `autoplay.stop()`) couvre déjà RGAA 13.2.
- **Marquee** : bouton pause flottant en overlay, coin supérieur droit, **activable/désactivable depuis le BO** via un attribut dédié.

### Bloc `g2rd-marquee` (pré-compilé — pas de src/)

**`block.json`** — Nouvel attribut :
```json
"showPauseButton": {
  "type": "boolean",
  "default": true
}
```

**`index.js`** — InspectorControls, panneau "Options" existant :
```js
<ToggleControl
  label={__('Afficher le bouton pause', 'g2rd')}
  help={__('Requis RGAA 13.2 si animation > 5 secondes', 'g2rd')}
  checked={showPauseButton}
  onChange={(val) => setAttributes({ showPauseButton: val })}
/>
```

**`index.js`** — Save, dans le wrapper `.g2rd-marquee` :
```html
{showPauseButton && (
  <button
    class="g2rd-marquee__pause"
    aria-label="Mettre le défilement en pause"
    aria-pressed="false"
    type="button"
  >⏸</button>
)}
```

**`view.js`** — Logique de bascule :
```js
const btn = el.querySelector('.g2rd-marquee__pause');
if (btn) {
  btn.addEventListener('click', function () {
    const paused = this.getAttribute('aria-pressed') === 'true';
    track.style.animationPlayState = paused ? 'running' : 'paused';
    this.setAttribute('aria-pressed', String(!paused));
    this.setAttribute('aria-label', paused
      ? 'Mettre le défilement en pause'
      : 'Reprendre le défilement'
    );
  });
}
```

**`style-index.css`** — Styles du bouton :
```css
.g2rd-marquee {
  position: relative;
}
.g2rd-marquee__pause {
  position: absolute;
  top: 0.5rem;
  right: 0.5rem;
  z-index: 10;
  background: rgba(0, 0, 0, 0.55);
  color: #fff;
  border: none;
  border-radius: 50%;
  width: 2rem;
  height: 2rem;
  cursor: pointer;
  font-size: 1rem;
  line-height: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}
.g2rd-marquee__pause:focus-visible {
  outline: 2px solid #fff;
  outline-offset: 2px;
}
```

### Bloc `g2rd-testimonial` — mode marquee (workspace src/)

**`block.json`** — Nouvel attribut :
```json
"marqueePauseButton": {
  "type": "boolean",
  "default": true
}
```

**`edit.js`** — Dans le panneau "Options Marquee" (section mode Google marquee) :
```jsx
<ToggleControl
  label={__('Afficher le bouton pause', 'g2rd')}
  checked={marqueePauseButton}
  onChange={(val) => setAttributes({ marqueePauseButton: val })}
/>
```

**`save.js`** — Ajouter dans les blockProps Google :
```js
"data-marquee-pause-button": String(!!marqueePauseButton),
```

**`view.js`** — Dans `buildMarquee()`, lire `el.dataset.marqueePauseButton` et créer le bouton si `"true"`, puis même logique toggle que pour g2rd-marquee.

### Markup HTML rendu (les deux blocs)
```html
<div class="g2rd-marquee" role="region" aria-label="Contenu défilant">
  <button class="g2rd-marquee__pause" type="button"
          aria-label="Mettre le défilement en pause"
          aria-pressed="false">⏸</button>
  <div class="g2rd-marquee__track">…</div>
</div>
```

### Builds nécessaires
```bash
npm run build:testimonial   # g2rd-testimonial (workspace)
# g2rd-marquee : pas de build (pré-compilé, édition directe)
```

### Commit message
```
feat: RGAA 13.2 — bouton pause marquee BO-configurable (g2rd-marquee + g2rd-testimonial)
```

---

## Commit 4 — RGAA 2.4 : aria-label header-color

### Problème
`parts/header-color.html` contient un `<header>` landmark sans `aria-label` distinctif. Quand une page utilise les deux headers (principal + color), un lecteur d'écran ne peut pas les distinguer.

### Correction
```html
<!-- avant -->
<header class="…">

<!-- après -->
<header class="…" aria-label="Décoration en-tête">
```

### Commit message
```
fix: RGAA 2.4 — aria-label distinctif sur header-color.html
```

---

## Récapitulatif des fichiers modifiés

| Fichier | Commit | Action |
|---------|--------|--------|
| `assets/css/admin-options.css` | 1 | Ajouter `:focus-visible` ligne 141 |
| `blocks/g2rd-carousel/src/carousel.css` | 1 + 2 | Focus-visible buttons + prefers-reduced-motion |
| `blocks/g2rd-code/src/*.css` | 1 | Focus-visible textarea |
| `blocks/g2rd-counter/src/editor.css` | 1 | Focus-visible RichText éditeur |
| `assets/css/login.css` | 1 | Focus-visible formulaire login |
| `blocks/g2rd-marquee/style-index.css` | 2 + 3 | prefers-reduced-motion + bouton pause styles |
| `blocks/g2rd-testimonial/src/view.js` | 2 + 3 | prefers-reduced-motion + pause button |
| `blocks/g2rd-marquee/block.json` | 3 | Attribut `showPauseButton` |
| `blocks/g2rd-marquee/index.js` | 3 | ToggleControl BO |
| `blocks/g2rd-marquee/view.js` | 3 | Logique pause JS |
| `blocks/g2rd-testimonial/src/save.js` | 3 | data-marquee-pause-button |
| `blocks/g2rd-testimonial/src/edit.js` | 3 | ToggleControl BO |
| `blocks/g2rd-testimonial/block.json` | 3 | Attribut `marqueePauseButton` |
| `parts/header-color.html` | 4 | aria-label |

---

## Hors périmètre (intentionnel)

- **Carrousel RGAA 13.2** : couvert par la pause au focus clavier existante dans `carousel-frontend.js`
- **Fluent Forms / FluentCart** : corrections dans sandbox Novamira `rgaa-fixes-v2.php`
- **Contrastes couleurs RGAA 3.x** : dépend des variables CSS du site client — non modifiable au niveau thème
