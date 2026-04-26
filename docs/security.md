# Sécurité du dépôt et du thème G2RD

Ce document complète [SECURITY.md](../SECURITY.md) à l’usage des contributeurs et des sites en production.

## Dépôt public : interdits

Ne jamais committer :

- clés API (Google Maps, services tiers, etc.) ;
- tokens d’authentification, secrets HMAC, mots de passe ;
- fichiers `.env`, dumps de base, certificats privés ;
- URL de webhooks contenant des secrets en paramètre.

Les **clés sensibles** se configurent dans l’administration WordPress (options du thème) ou via des mécanismes serveur (variables d’environnement, fichiers hors dépôt), pas dans le code versionné.

## Messages d’erreur et journaux

- En production, les messages affichés aux administrateurs ou dans l’API REST doivent rester **génériques** (ex. « impossible de contacter le serveur ») lorsque `WP_DEBUG` est désactivé.
- Les journaux PHP (`error_log`) ne doivent pas inclure des **chemins absolus** complets ni du **SQL brut** : cela facilite les attaques ciblées et la fuite d’informations sur l’hébergement.

## API REST et administration

- Les routes réservées aux administrateurs doivent vérifier les **capacités** WordPress et utiliser des **nonces** ou l’authentification appropriée.
- Éviter de renvoyer des structures trop détaillées sur l’infrastructure (chemins internes, versions de tous les plugins, etc.) sans nécessité fonctionnelle.

## Blocs et éditeur

- Les blocs « API », « options », avis Google, etc. ne doivent pas embarquer de secrets dans le HTML ou les attributs du bloc sauvegardés en base : préférer des options côté serveur ou des placeholders explicites dans l’UI.

## Signalement

Voir [SECURITY.md](../SECURITY.md) pour la procédure de signalement de vulnérabilité.
