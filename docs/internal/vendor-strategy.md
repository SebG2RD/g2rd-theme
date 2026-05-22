# Stratégie vendor/ — Analyse et décision

**Date :** 2026-05-22
**Statut :** Décision confirmée (Option A)
**Contexte :** Thème WordPress distribué via GitHub Updater — le ZIP est téléchargé directement sans étape `composer install` côté client.

---

## Contrainte fondamentale

GitHub Updater télécharge un ZIP de release et l'extrait dans `wp-content/themes/`. Ce ZIP n'exécute pas `composer install`. Si `vendor/` est absent du ZIP, les classes PHP du thème ne se chargent pas et le site plante.

---

## Options comparées

### Option A — Versionner `vendor/` intégralement *(actuel)*

**Principe :** `vendor/` est commité et inclus dans le ZIP de release.

| + Avantages | - Inconvénients |
|---|---|
| ZIP autonome — installation sans prérequis | `vendor/` alourdit le dépôt (~3 Mo) |
| Compatible GitHub Updater sans modification | Diffs pollués par les mises à jour de dépendances |
| Déploiement fiable même sans Composer côté client | `composer.lock` doit rester synchronisé avec les fichiers présents |
| CI valide contre les mêmes fichiers qu'en prod | |

**Adapté quand :** distribution à des clients non-techniques via WordPress, installation one-click.

---

### Option B — Exclure `vendor/`, `composer install` dans le CI

**Principe :** `vendor/` est gitignored. Le CI fait `composer install --no-dev` avant de créer le ZIP.

| + Avantages | - Inconvénients |
|---|---|
| Dépôt plus léger, historique propre | Nécessite une étape CI supplémentaire (déjà présente dans `release.yml`) |
| Versions de dépendances visibles dans `composer.lock` uniquement | Le ZIP doit inclure `vendor/` généré — logique de packaging plus complexe |
| Pratique standard pour les packages Composer | Incompatible avec GitHub Updater sans adapter le workflow de release |

**Adapté quand :** package Composer classique ou thème déployé via pipeline CD avec accès Composer.

---

### Option C — Hybride : versionner avec `.gitattributes` export-ignore

**Principe :** `vendor/` est commité mais marqué `export-ignore` dans `.gitattributes` pour être exclu des archives GitHub automatiques. Le ZIP de release est construit manuellement par CI (qui inclut `vendor/`).

| + Avantages | - Inconvénients |
|---|---|
| Dépôt propre — clone sans `vendor/` possible | Dépend entièrement du ZIP CI ; le zipball GitHub auto serait inutilisable |
| ZIP de release identique à Option A | Complexité accrue du workflow release |
| Diffs moins pollués (selon config IDE/GitHub) | Les contributeurs doivent faire `composer install` après clone |

**Adapté quand :** équipe dev familière avec Composer, distribution uniquement via releases CI.

---

## Décision retenue : Option A

### Justification

1. **Contrainte GitHub Updater** — les clients activent les mises à jour depuis le tableau de bord WordPress. Le ZIP doit être autonome. Modifier ce comportement nécessiterait de remplacer GitHub Updater par un mécanisme personnalisé (LicenseServer déjà en place, évolution possible mais hors scope).

2. **Cohérence CI/prod** — le workflow `release.yml` fait `composer install --no-dev`, ce qui garantit que `vendor/` en prod = `vendor/` en CI. L'Option B n'apporterait aucun avantage de fiabilité supplémentaire dans ce contexte.

3. **Coût faible** — `vendor/` représente ~3 Mo dans un thème dont le ZIP fait ~4–5 Mo. L'impact sur le dépôt est acceptable.

4. **Simplicité** — l'Option C ajoute de la complexité (`.gitattributes`, deux modes d'archivage) sans bénéfice mesurable pour un projet monorepo à faible surface contributeurs.

### Condition de révision

Cette décision doit être réévaluée si :
- Le thème est distribué via un dépôt Packagist ou un registre Composer privé.
- GitHub Updater est remplacé par un mécanisme de mise à jour REST natif (le `LicenseServer` sur g2rd.fr pourrait servir de point de distribution).
- `vendor/` dépasse 10 Mo ou contient des dépendances lourdes (actuellement : 4 packages légers).

---

## État actuel des dépendances Composer

```
composer.json (--no-dev, prod uniquement)
├── phpstan/phpstan            (dev uniquement — non inclus dans release.yml)
└── [dépendances runtime]      voir composer.json
```

`composer audit` est exécuté à chaque release CI — aucune vulnérabilité connue au 2026-05-22.
