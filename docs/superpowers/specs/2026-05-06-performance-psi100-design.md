# Performance PSI 100 — Design Spec

**Date :** 2026-05-06  
**Objectif :** Atteindre 100/100 sur Google PageSpeed Insights (mobile + desktop) sans casser aucune fonctionnalité existante.

---

## Décisions clés

| Décision | Choix retenu |
|---|---|
| Structure | Hybride : 3 nouvelles classes + compléter les existantes |
| CSS critique | Fichier `assets/css/critical.css` maintenu manuellement, inliné par PHP |
| WebP/AVIF | Hook `wp_content_img_tag` présent, no-op si pas de fichiers `.webp` |
| font-display | Toutes les polices en `swap` (actuellement 7/8 en `block`) |
| Cache headers | LiteSpeed actif → transients uniquement, pas de headers PHP |

---

## Ce qui existe déjà (ne pas dupliquer)

- Scripts `defer` sur 5 handles (class-scripts-manager.php)
- `wp-embed`, emojis, `wp_generator`, `wlwmanifest`, `rsd_link`, shortlink supprimés
- GSAP chargement conditionnel + speed test bypass
- Particules bypass Lighthouse/PageSpeed
- Polices Inter + PlusJakartaSans préchargées
- LCP `fetchpriority="high"` sur 1ère image
- Preconnect CDN Typed.js
- LiteSpeed exclusions (dark-mode.js, accessibility.js, fluent-cart)

---

## Ordre d'implémentation

### 1. `theme-settings.json` — font-display swap
Changer `"fontDisplay": "block"` → `"fontDisplay": "swap"` pour les 7 polices concernées :
Rubik, Quantum, DM Serif Display, Audiowide, Inter, Acme, Borel.  
PlusJakartaSans est déjà en `swap`.

**Impact :** FCP immédiat — Lighthouse pénalise `block` sur toutes les polices.

---

### 2. `class-performance-css.php` (nouvelle) + `assets/css/critical.css` (nouveau)

**`critical.css`** : fichier maintenu manuellement contenant les styles above-the-fold du thème (header, nav, hero, boutons CTA, couleurs CSS custom props). Ne pas y mettre les styles de blocs Gutenberg dynamiques.

**`class-performance-css.php`** :
- Hook `wp_head` priorité 1 : lit `assets/css/critical.css`, le minifie (strip comments + whitespace via regex), l'inline dans un `<style id="g2rd-critical-css">`
- Les handles CSS non critiques du thème sont modifiés : `media="print" onload="this.media='all'"` + fallback `<noscript>`
- Transient `g2rd_critical_css_v{version}` de 24h pour le CSS minifié
- Invalidation du transient sur `switch_theme`
- Ne touche pas aux styles des blocs Gutenberg (gérés par WordPress core)

**Handles CSS à différer :**
- `g2rd-micro-interactions`
- `g2rd-block-responsive`
- `g2rd-magic-page` (déjà conditionnel)

---

### 3. `class-performance-images.php` (nouvelle)

Hook `wp_content_img_tag` (priorité 10) — s'applique uniquement au contenu des posts, pas au header/footer FSE :

1. **Lazy loading intelligent** : compteur statique — première image = `loading="eager" fetchpriority="high"`, reste = `loading="lazy" decoding="async"`
2. **Dimensions explicites** : si `width`/`height` absents, tente `wp_get_attachment_metadata()` via l'ID d'attachement extrait de la classe `wp-image-{ID}`
3. **WebP check** : si fichier `.webp` existe au même chemin, remplace `src` + ajoute `<picture>` avec source WebP + fallback original

Hook `wp_head` priorité 2 : preload de l'image LCP (premier `core/cover` ou `g2rd/hero` du contenu).

---

### 4. Compléter `class-theme-setup.php`

Dans `setupFeatures()`, ajouter :
```php
remove_action('wp_head', 'feed_links_extra', 3);
```

Dans `removeUnnecessaryAssets()`, ajouter :
```php
// comment-reply uniquement si commentaires désactivés sur le post
if (!is_singular() || !comments_open()) {
    wp_dequeue_script('comment-reply');
}
```

---

### 5. `class-performance-cache.php` (nouvelle)

Transients uniquement (pas de headers HTTP — LiteSpeed actif) :

- `g2rd_critical_css_v{theme_version}` — CSS critique minifié, 24h, invalidé sur `switch_theme`
- `g2rd_jsonld_{post_id}` — schema JSON-LD par post, 12h, invalidé sur `save_post_{id}`
- Méthode statique `get_critical_css()` et `get_jsonld(int $post_id)` pour centraliser l'accès
- Les classes `class-performance-css.php` et `class-seo-helper.php` consomment ces méthodes

---

### 6. `class-performance-audit.php` (nouvelle)

Activé uniquement si `WP_DEBUG === true`.

Hook `shutdown` :
```
[G2RD PERF] Page "Titre" : X queries SQL, Y styles, Z scripts, Wko HTML
```

Données loguées :
- `$wpdb->num_queries`
- `count(wp_styles()->queue)`
- `count(wp_scripts()->queue)`
- `ob_get_length()` (si output buffering actif)

---

## Fichiers modifiés / créés

| Fichier | Action |
|---|---|
| `theme-settings.json` | Modifier — font-display swap |
| `assets/css/critical.css` | Créer — styles above-the-fold |
| `classes/class-performance-css.php` | Créer |
| `classes/class-performance-images.php` | Créer |
| `classes/class-performance-cache.php` | Créer |
| `classes/class-performance-audit.php` | Créer |
| `classes/class-theme-setup.php` | Modifier — feed_links_extra + comment-reply |
| `functions.php` | Vérifier — autoload automatique via glob() |

---

## Checklist PageSpeed finale

### LCP (< 2.5s)
- [x] Image LCP preloadée (class-theme-setup.php existant)
- [x] `fetchpriority="high"` sur image LCP (existant)
- [ ] Preload image hero (class-performance-images.php)
- [ ] CSS critique inliné (class-performance-css.php)
- [ ] font-display swap (theme-settings.json)

### INP (< 200ms)
- [x] Scripts non critiques en defer (existant)
- [x] GSAP conditionnel (existant)

### CLS (< 0.1)
- [ ] Dimensions explicites sur images (class-performance-images.php)
- [x] font-display swap + preload (évite FOUT)

### FCP (< 1.8s)
- [ ] CSS critique inline (class-performance-css.php)
- [x] Polices préchargées (existant)

### TBT (< 200ms)
- [x] Scripts defer (existant)
- [ ] comment-reply conditionnel (class-theme-setup.php)
