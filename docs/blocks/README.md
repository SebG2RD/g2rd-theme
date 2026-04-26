# Standard qualité des blocs G2RD

Chaque bloc doit fournir les éléments suivants pour être prêt à la distribution.

## 1) Métadonnées

- `block.json` complet (minimum: `apiVersion`, `name`, `title`, `category`, `attributes`, `supports`).
- Déclaration propre des scripts/styles via métadonnées.

## 2) Documentation locale du bloc

Dans le dossier du bloc (`blocks/<slug>/`) :

- `README.md` (description, usage, limites, compatibilité).
- `screenshot.png` (ou jpg/jpeg/webp) pour aperçu rapide.

## 3) Documentation centralisée

Dans `docs/blocks/<slug>/` :

- `attributes.md`
- `frontend-demo.md`
- `editor-demo.md`
- `deprecations.md`
- `variations.md`

## 4) Compatibilité éditeur

- Stratégie de dépréciation présente (`deprecated`).
- Variations documentées (dans `block.json` et/ou `src/index.js`).
- Tests unitaires JS activés dans la CI.

## 5) Contrôle automatique

Commande locale :

```bash
npm run audit:blocks
```

La CI exécute aussi cet audit pour éviter qu’un bloc incomplet passe en release.
