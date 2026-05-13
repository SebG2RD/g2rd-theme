# RGAA Phase 2 — Accessibilité blocs interactifs

**Date :** 2026-05-11
**Critères RGAA :** 4.1, 6.1, 10.2, 12.1
**Approche retenue :** Patch DOM via `view.js` (Option A) — aucune modification des `index.js` compilés

## Contexte

Suite à l'audit RGAA global du thème G2RD (Phase 1 complétée : RGAA 9.1, 10.1, 12.8, 13.1, 13.2, 2.4), cette phase cible 6 blocs interactifs pré-compilés (pas de dossier `src/`) qui présentent des non-conformités ARIA.

Les blocs pré-compilés ne peuvent pas être recompilés. Les corrections s'appliquent uniquement via :
- `view.js` existant (modifié directement)
- `view.js` créé de zéro + déclaration `"viewScript"` dans `block.json` (pour les blocs sans view.js)

Les `index.js` compilés ne sont **jamais modifiés** — risque de casser la validation Gutenberg des blocs existants en base de données.

## Blocs et corrections

---

### 1. g2rd-modal — `blocks/g2rd-modal/view.js`

**Manques :** focus trap, retour de focus, focus à l'ouverture, aria-labelledby
**Fichier modifié :** `view.js` (existant, minifié)

**Corrections :**

1. **Mémoriser le déclencheur** — avant ouverture, stocker `document.activeElement` dans une variable `lastFocus`
2. **Focus vers dialog à l'ouverture** — après `classList.add("is-open")`, appeler `focus()` sur le premier élément focusable du overlay : `overlay.querySelector('button, a[href], input, select, textarea, [tabindex]:not([tabindex="-1"])')`
3. **Focus trap (Tab/Shift+Tab)** — listener `keydown` sur le overlay à l'ouverture :
   - Calculer les éléments focusables (`querySelectorAll` sur sélecteurs standards)
   - Si `Tab` depuis le dernier → focus sur le premier (preventDefault)
   - Si `Shift+Tab` depuis le premier → focus sur le dernier (preventDefault)
   - Listener retiré à la fermeture
4. **Retour de focus** — à la fermeture, appeler `lastFocus?.focus()`
5. **aria-labelledby dynamique** — à l'initialisation, chercher le premier `h1,h2,h3,h4,h5,h6` dans le overlay ; lui attribuer `id="g2rd-modal-title-<blockId>"` s'il n'en a pas ; poser `aria-labelledby` sur le overlay

---

### 2. g2rd-sliding-panel — `blocks/g2rd-sliding-panel/view.js`

**Manques :** identiques à g2rd-modal (focus trap, retour focus, focus ouverture, aria-labelledby)
**Fichier modifié :** `view.js` (existant, minifié)

**Corrections :** pattern identique à g2rd-modal, adapté aux sélecteurs du bloc :
- Déclencheur : `[data-sliding-panel-trigger]`
- Overlay : `[data-sliding-panel-overlay]`
- Fermeture : `[data-sliding-panel-close]`
- ID titre généré : `g2rd-panel-title-<blockId>`

---

### 3. g2rd-toggle-content — nouveau `view.js`

**Manques :** aria-expanded absent, aria-hidden absent sur contenu masqué, aria-controls absent
**Fichiers modifiés :**
- `block.json` — ajouter `"viewScript": "file:./view.js"`
- `view.js` — créer

**Structure HTML générée par save.js :**
```html
<div class="g2rd-toggle-content">
  <input type="checkbox" id="g2rd-toggle-xxx" class="g2rd-toggle-content__checkbox" aria-label="...">
  <div class="g2rd-toggle-content__container">
    <label for="g2rd-toggle-xxx" class="g2rd-toggle-content__switch">
      <span class="g2rd-toggle-content__slider"></span>
    </label>
  </div>
  <div class="g2rd-toggle-content__content">
    <div><!-- contenu gauche --></div>
    <div><!-- contenu droite --></div>
  </div>
</div>
```

**Logique view.js :**
1. Trouver les deux sections : 1er et 2ème enfant de `.g2rd-toggle-content__content`
2. Attribuer des `id` : `g2rd-tc-left-<blockId>`, `g2rd-tc-right-<blockId>`
3. Poser `aria-controls="g2rd-tc-left-<id> g2rd-tc-right-<id>"` sur le label
4. Fonction `updateAria(checked)` :
   - Section gauche : `aria-hidden = checked ? "false" : "true"`
   - Section droite : `aria-hidden = checked ? "true" : "false"`
5. Appeler `updateAria` au chargement (état initial) + à chaque `change` du checkbox
6. Pattern `data-g2rd-init` pour éviter la double initialisation (compatibilité éditeur)

---

### 4. g2rd-map — `blocks/g2rd-map/view.js`

**Manques :** alternative textuelle absente, pas de landmark sur le wrapper
**Fichier modifié :** `view.js` (existant, minifié)

**Hiérarchie de description (injectée avant l'init de la carte) :**
1. `markers[0].title` — titre du premier marqueur (le plus informatif)
2. `el.getAttribute("data-title")` — titre configuré dans le BO
3. Fallback : `"Carte interactive — naviguez avec les contrôles de la carte"`

**Corrections dans view.js (avant l'init Google Maps / Leaflet) :**
1. Générer un `id` unique : `g2rd-map-desc-<index>`
2. Créer `<div class="screen-reader-text" id="g2rd-map-desc-<index>">` avec le texte décrit ci-dessus
3. Insérer ce div comme premier enfant de l'élément `.g2rd-map`
4. Poser `role="region"` sur l'élément `.g2rd-map`
5. Poser `aria-describedby="g2rd-map-desc-<index>"` sur l'élément `.g2rd-map`
6. Le `aria-hidden="true"` reste sur `.g2rd-map__canvas` (correct)

---

### 5. g2rd-icon-box — nouveau `view.js`

**Manques :** liens sans label accessible quand seule l'icône est contenu du lien
**Fichiers modifiés :**
- `block.json` — ajouter `"viewScript": "file:./view.js"`
- `view.js` — créer

**Logique view.js :**
1. Sélectionner tous les `a[href]` dans `.g2rd-iconbox`
2. Pour chaque lien, calculer le texte accessible :
   - Cloner le lien, retirer les enfants `[aria-hidden="true"]`, lire `textContent.trim()`
3. Si texte vide (lien icône-seule) :
   - Chercher `.g2rd-iconbox__title, h2, h3, h4` dans le bloc parent `.g2rd-iconbox`
   - Poser `aria-label` sur le lien avec ce texte
   - Si aucun texte trouvé : `aria-label="Lien"` + `console.warn` en développement

---

### 6. g2rd-slider — `blocks/g2rd-slider/view.js`

**Manques :** aria-controls absent sur prev/next, pas d'annonce aria-live, aria-current absent sur slide actif
**Fichier modifié :** `view.js` (existant, **non minifié** — modifications lisibles)

**Corrections dans `initSlider()` :**

1. **ID sur le track** — après la sélection du track : `track.id = track.id || "g2rd-slider-track-" + Date.now()`
2. **aria-controls sur prev/next** — après création des boutons :
   ```js
   prevBtn?.setAttribute("aria-controls", track.id);
   nextBtn?.setAttribute("aria-controls", track.id);
   ```
3. **aria-live region** — créer un `<div>` visually-hidden avec `aria-live="polite"` et `aria-atomic="true"`, l'insérer dans `el`
4. **Dans `goTo(index)` :**
   - Retirer `aria-current` de tous les slides
   - Poser `aria-current="true"` sur `slides[index]`
   - Mettre à jour le texte de la live region : `"Slide ${index + 1} sur ${total}"`

## Fichiers modifiés — récapitulatif

| Fichier | Action |
|---|---|
| `blocks/g2rd-modal/view.js` | Modifier — focus trap + aria-labelledby |
| `blocks/g2rd-sliding-panel/view.js` | Modifier — focus trap + aria-labelledby |
| `blocks/g2rd-toggle-content/block.json` | Modifier — ajouter viewScript |
| `blocks/g2rd-toggle-content/view.js` | Créer — aria-controls + aria-hidden dynamiques |
| `blocks/g2rd-map/view.js` | Modifier — screen-reader-text + role region |
| `blocks/g2rd-icon-box/block.json` | Modifier — ajouter viewScript |
| `blocks/g2rd-icon-box/view.js` | Créer — aria-label sur liens icône-seule |
| `blocks/g2rd-slider/view.js` | Modifier — aria-controls + aria-live + aria-current |

## Critères RGAA couverts

| Critère | Bloc(s) | Correction |
|---|---|---|
| RGAA 12.1 — Liens et boutons sans intitulé | icon-box | aria-label sur liens icône-seule |
| RGAA 10.2 — Contenu masqué non signalé | toggle-content | aria-hidden sur sections cachées |
| RGAA 4.1 — Contrôle clavier | modal, sliding-panel, slider | focus trap, aria-controls, aria-live |
| RGAA 6.1 — Alternative textuelle | map | screen-reader-text avec adresse |
| RGAA 12.8 — Focus management | modal, sliding-panel | retour focus déclencheur, focus ouverture |

## Contraintes

- Aucun `index.js` compilé n'est modifié
- Les `view.js` créés sont autonomes (pas d'imports ES modules)
- Le pattern `data-g2rd-init` est utilisé sur tous les nouveaux view.js pour éviter la double init dans l'éditeur
- `prefers-reduced-motion` n'est pas impacté (corrections ARIA uniquement, pas d'animations)
- Compatibilité : les corrections sont additive-only, aucun attribut existant n'est supprimé
