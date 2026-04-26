# Bloc G2RD FAQ

## Description

Bloc FAQ orienté SEO/GEO avec rendu serveur (`render.php`) et génération JSON-LD (`FAQPage`).

## Utilisation

1. Ajouter le bloc **G2RD FAQ** dans Gutenberg.
2. Renseigner la question et la réponse.
3. Vérifier le rendu frontend (balises `details/summary` + données structurées).

## Qualité technique

- `block.json` avec `apiVersion: 3`
- Rendu PHP sécurisé (sanitization + escape)
- Accessibilité (`aria-*`)
- Compatibilité FSE
