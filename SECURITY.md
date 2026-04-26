# Politique de sécurité

Ce dépôt est **public**. Aucune clé API, token, secret HMAC ou mot de passe ne doit y être commité.

## Signaler une vulnérabilité

- Utilisez de préférence **[GitHub Security Advisories](https://github.com/SebG2RD/g2rd-theme/security/advisories/new)** (rapport privé) si la fonctionnalité est activée sur le dépôt.
- Sinon, ouvrez un ticket en **marquant les détails sensibles** (ne collez pas de preuves d’exploitation complètes en public sans accord) ou contactez les mainteneurs par un canal privé convenu.

Nous traiterons les signalements de bonne foi et pourrons coordonner une correction et une divulgation responsable.

## Ce que nous attendons des contributeurs

- Ne pas pousser de secrets (clés Google, FluentCart, webhooks, `.env`, certificats, etc.).
- Ne pas activer de logs verbeux en production qui exposent des chemins serveur, du SQL ou des corps de réponse d’API.
- Éviter les messages d’erreur utilisateur trop détaillés hors mode débogage WordPress (`WP_DEBUG`).

Pour le détail côté code et bonnes pratiques du thème, voir [docs/security.md](docs/security.md).
