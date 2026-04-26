# Contribuer au thème G2RD

Merci de votre intérêt. Ce document résume comment proposer des changements de façon sûre et lisible.

## Avant de coder

1. **Sécurité** : lisez [SECURITY.md](SECURITY.md) et [docs/security.md](docs/security.md). Aucun secret dans le dépôt public.
2. **Cohérence** : suivez le style PHP existant (PHPCS / WordPress Coding Standards) et les conventions des blocs (React / `@wordpress/scripts`).

## Environnement local

- **PHP** : version indiquée dans `composer.json` / README.
- **Node** : version compatible avec `package.json` (voir champ `engines` si présent).

Commandes courantes :

```bash
composer install
npm install
```

Qualité et tests (selon ce qui est disponible sur votre machine) :

```bash
composer run phpcs
composer run phpstan
composer run phpunit
npm run test:blocks
```

## Pull requests

- Une PR = un sujet clair (correctif, fonctionnalité, doc).
- Décrivez le **pourquoi** et le **comportement attendu** en phrases complètes.
- Joignez des captures ou notes de test si l’UI ou l’éditeur de blocs est impacté.

## Licence et droits

En contribuant, vous acceptez que vos contributions soient sous la même licence que le projet (voir les fichiers de licence du dépôt).

## Conduite

Le projet applique un [code de conduite](CODE_OF_CONDUCT.md). Merci de le respecter dans les issues et les revues.
