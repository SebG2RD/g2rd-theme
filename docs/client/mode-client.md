# Mode client

Le **mode client** simplifie l’**administration WordPress** pour les personnes qui ne sont **pas administrateurs** (par exemple auteurs ou éditeurs) : moins de menus, barre d’outils allégée, message d’accueil personnalisé.

## Qui est concerné

- Les comptes avec le rôle **Administrateur** continuent de voir l’interface **complète** : le mode client ne les limite pas.
- Les **autres rôles** (éditeur, auteur, etc.) reçoivent l’interface simplifiée lorsque le mode est activé.

## Activer le mode client

1. Connectez-vous en **Administrateur**.
2. **Apparence → Options G2RD → Clients**
3. Activez **Activer le mode client**.
4. Optionnel : remplissez le **Message d’accueil** (texte affiché sur le tableau de bord ; un peu de HTML simple peut être autorisé).
5. **Enregistrez** les modifications.

## Ce qui change pour les non-administrateurs (résumé)

Exemples de comportements prévus par le thème :

- certains menus sensibles sont **masqués** (extensions, réglages généraux, personnalisation avancée du thème, etc.) ;
- la **barre d’administration** en haut du site est nettoyée de certains raccourcis ;
- un **message d’accueil** peut rappeler les consignes de votre agence.

L’objectif est de réduire les risques d’erreurs pour un client qui ne doit **que publier du contenu**.

## Désactiver

Décochez **Activer le mode client** dans les mêmes réglages, puis enregistrez. Les menus réapparaissent pour les rôles concernés après rechargement de la page.

## Liens utiles

- [README de la doc client](README.md) — autres guides.
- [Résolution des problèmes](depannage.md) — si un menu indispensable a disparu, un administrateur peut ajuster les rôles ou désactiver le mode.
