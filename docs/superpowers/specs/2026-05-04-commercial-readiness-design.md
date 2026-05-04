# Spec — Préparation distribution commerciale G2RD Theme
**Date** : 2026-05-04  
**Version cible** : post-1.8.6  
**Statut** : Approuvé

---

## Contexte

Le thème G2RD FSE (v1.8.6) est un thème WordPress Full Site Editing professionnel avec 38 classes PHP, 28+ blocs Gutenberg custom, CI/CD complet et système de licence. L'objectif est de le rendre distribuable publiquement en modèle SaaS (licence par site) sans supprimer de fonctionnalités, en réduisant la dette technique et en ajoutant tests + documentation.

## Hors scope

- Migration des CPTs vers un plugin compagnon (choix délibéré : solution tout-en-un conservée)
- Normalisation des namespaces de blocs (chantier A, traité après)
- Ajout de nouvelles fonctionnalités

---

## Chantier B — Fallback `require_once` dynamique

### Problème actuel

`functions.php` contient 38 lignes `require_once` statiques comme fallback pour les distributions ZIP (sans `vendor/`). Ce bloc grossit à chaque nouvelle classe et peut désynchroniser si une classe est renommée ou supprimée.

```php
// Situation actuelle — 38 lignes à maintenir manuellement
require_once get_template_directory() . '/classes/class-theme-setup.php';
require_once get_template_directory() . '/classes/class-theme-admin.php';
// ... 36 autres lignes
```

### Solution retenue : glob dynamique avec tableau de priorité

Remplacer les 38 lignes par un chargeur en deux passes :

**Passe 1 — Classes prioritaires** (chargées en premier, ordre garanti) :
```php
$priority_classes = [
    'class-json-config.php',          // config JSON — dépendance de theme-options
    'class-theme-options.php',        // options — dépendance de presque tout
    'class-block-categories.php',     // doit être avant autoload des blocs
    'class-block-editor-autoload.php',// charge les blocs — dépend de theme-options
    'class-scripts-manager.php',      // gestion scripts — dépend de theme-options
    'class-license-manager.php',      // licence — dépend de theme-options
    'class-github-updater.php',       // updater — dépend de license-manager
];
```

**Passe 2 — Toutes les autres** via `glob()` (exclut les prioritaires déjà chargées) :
```php
foreach ( glob( $classes_dir . '/class-*.php' ) as $file ) {
    $basename = basename( $file );
    if ( ! in_array( $basename, $loaded, true ) ) {
        require_once $file;
    }
}
```

### Ce qu'on ne touche pas

- La logique `bootstrap_theme()` — aucun changement
- L'autoloader Composer — reste prioritaire, le glob ne s'exécute que si `vendor/autoload.php` est absent
- Les instances de classes dans `bootstrap_theme()`

### Risques

- **Ordre de chargement** : mitigé par le tableau de priorité explicite
- **Glob retourne null** : protégé par `glob(...) ?: []`
- **Régression** : à valider avec `composer run phpcs` + smoke tests PHPUnit

---

## Chantier C — Tests Playwright (frontend)

### Situation existante

- `tests/js/blocks-editor-safety.test.js` — tests Jest unitaires existants
- `tests/phpunit/` — smoke tests PHP existants
- Aucun test Playwright, aucun `playwright.config.js`
- `@wordpress/scripts` inclut Playwright mais non configuré
- Environnement : Local by Flywheel (URL fixe locale)

### Architecture retenue

```
tests/
├── js/                              # existant — ne pas toucher
│   └── blocks-editor-safety.test.js
├── phpunit/                         # existant — ne pas toucher
│   ├── bootstrap.php
│   └── CoreClassesSmokeTest.php
└── playwright/                      # NOUVEAU
    ├── hero.spec.js
    ├── carousel.spec.js
    └── faq.spec.js
```

Fichiers à créer à la racine :
- `playwright.config.js`
- `.env.example` (contient `PLAYWRIGHT_BASE_URL`)
- `.env` (non versionné, URL locale de l'utilisateur)

### Configuration `playwright.config.js`

```js
import { defineConfig, devices } from '@playwright/test';
import dotenv from 'dotenv';
dotenv.config();

export default defineConfig({
    testDir: './tests/playwright',
    timeout: 30_000,
    use: {
        baseURL: process.env.PLAYWRIGHT_BASE_URL || 'http://localhost',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
    },
    projects: [
        { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
    ],
    reporter: [['html', { outputFolder: 'tests/playwright/report' }]],
});
```

### Stratégie de test — Frontend uniquement

Chaque spec teste le rendu frontend sur une page préexistante du site local (pas d'insertion via l'éditeur). Les vérifications couvrent :
1. Rendu visible du bloc (pas de div vide, pas d'erreur console JS)
2. Interactions clés (carousel : slide suivant/précédent ; FAQ : ouverture/fermeture accordéon)
3. Dark mode : le bloc reste lisible après activation

### Détail des 3 specs

**`hero.spec.js`**
- Naviguer vers une page contenant le bloc `g2rd/hero`
- Vérifier : `.wp-block-g2rd-hero` visible, heading présent, CTA cliquable
- Vérifier : aucune erreur console de type `Error` ou `Uncaught`

**`carousel.spec.js`**
- Naviguer vers une page contenant `g2rd/carousel`
- Vérifier : `.wp-block-g2rd-carousel` visible, au moins 1 slide visible
- Interaction : cliquer bouton "suivant" → vérifier slide suivant actif
- Vérifier : pas d'erreur Swiper dans la console

**`faq.spec.js`**
- Naviguer vers une page contenant `g2rd/faq`
- Vérifier : `.wp-block-g2rd-faq` visible, au moins 1 item présent
- Interaction : cliquer premier item → vérifier contenu visible (accordéon ouvert)
- Cliquer à nouveau → vérifier repli
- Vérifier : JSON-LD FAQPage présent dans `<head>` (si `optimizeForGEO: true`)

### Intégration CI

Ajouter dans `smart-ci.yml` un job optionnel `playwright-tests` :
- Déclenché uniquement si `PLAYWRIGHT_BASE_URL` secret est défini
- Installe Playwright browsers (`npx playwright install --with-deps chromium`)
- Exécute `npx playwright test`
- Upload artifact `playwright-report` en cas d'échec

### Packages à ajouter

```bash
npm install --save-dev @playwright/test dotenv
```

---

## Chantier D — Documentation design system

### Situation existante

`docs/` contient déjà 16 fichiers Markdown (développeur-orientés) : installation, sécurité, accessibilité, blocs, changelog, etc. Ces fichiers sont bons et ne sont pas remplacés.

### Ce qui manque

1. **Documentation design system** (tokens, blocs, patterns, features) — aucun fichier dédié aux utilisateurs finaux non-techniques
2. **Page Gutenberg pour g2rd.fr** — contenu prêt à coller dans l'éditeur WordPress

### Structure à créer

```
docs/
├── [16 fichiers existants — intacts]
└── design-system/                   # NOUVEAU
    ├── index.md                     # Vue d'ensemble + comment utiliser la doc
    ├── tokens.md                    # Couleurs, typo, espacements, ombres
    ├── blocs.md                     # Les 28 blocs — usage, attributs, captures
    ├── patterns.md                  # Les 28 patterns — description, cas d'usage
    └── features.md                  # Features admin (dark mode, GSAP, GEO, etc.)

docs/gutenberg-page/                 # NOUVEAU
└── design-system.html               # HTML + WP block comments — copier-coller Gutenberg
```

### Contenu `docs/design-system/tokens.md`

Généré depuis `theme-settings.json` (source de vérité). Couvre :
- **Couleurs** (7 presets) : slug CSS, valeur hex, usage recommandé
- **Dégradés** (5) : nom, composition, cas d'usage
- **Typographie** (8 familles) : nom, fallback, cas d'usage
- **Tailles de police** (5) : S→XXL avec valeurs rem et fluid
- **Espacement** (5) : XS→XL avec valeurs
- **Ombres** (10) : nom, intensité, cas d'usage

### Contenu `docs/design-system/blocs.md`

Pour chaque bloc :
- Nom d'affichage + namespace (`g2rd/hero`)
- Description en une phrase
- Attributs principaux (pas exhaustif — les plus utiles)
- Compatibilité (dynamic/static, FSE, innerBlocks)

### Contenu `docs/gutenberg-page/design-system.html`

Page complète prête à coller dans Gutenberg. Structure :
- Header hero avec titre "Design System G2RD"
- Section "Palette de couleurs" — swatches visuels
- Section "Typographie" — familles + tailles
- Section "Blocs disponibles" — tableau ou cards
- Section "Patterns" — liste catégorisée
- Section "Features" — toggle features avec explications
- Footer CTA vers la documentation complète

Le HTML utilise les variables CSS du thème (`var(--wp--preset--color--primary)`) et les classes WordPress standard (`wp-block-group`, `has-primary-color`, etc.) pour s'adapter automatiquement au thème G2RD.

---

## Ordre d'implémentation

| Ordre | Chantier | Durée estimée | Risque |
|-------|----------|---------------|--------|
| 1 | **B** — Glob dynamique `functions.php` | ~1h | Faible |
| 2 | **D1** — `docs/design-system/` (5 fichiers MD) | ~2h | Nul |
| 3 | **D2** — `docs/gutenberg-page/design-system.html` | ~3h | Nul |
| 4 | **C** — Setup Playwright + 3 specs | ~3h | Moyen |

---

## Règle transversale

> Avant toute suppression de code, de fichier ou de fonctionnalité : demander confirmation à l'utilisateur avec justification.

---

## Critères de succès

- [ ] `functions.php` : plus de require_once statiques, glob avec priorité
- [ ] `composer run phpcs` : 0 erreur après modification B
- [ ] `npx playwright test` : 3 specs passent sur l'environnement local
- [ ] `docs/design-system/` : 5 fichiers complets, générés depuis `theme-settings.json`
- [ ] `docs/gutenberg-page/design-system.html` : collable dans Gutenberg sans modification
