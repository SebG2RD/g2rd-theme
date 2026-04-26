# Mise à jour du thème

G2RD peut proposer des **mises à jour** comme les autres thèmes WordPress, en s’appuyant sur les **versions publiées** du dépôt officiel (GitHub Releases).

## Condition importante : licence active

Le thème ne propose les **téléchargements de mise à jour** automatiques que si une **licence valide** est enregistrée sur le site. Sans licence active, WordPress ne recevra pas la nouvelle version via ce mécanisme (vous pourriez toutefois mettre à jour manuellement par ZIP si votre prestataire vous fournit un fichier).

Vérifiez le statut dans **Apparence → Options G2RD → Licence**.

## Comment mettre à jour depuis l’administration

1. Connectez-vous en **Administrateur**.
2. Allez dans **Apparence → Thèmes** (ou **Tableau de bord → Mises à jour** si WordPress affiche une notification globale).
3. Si une mise à jour pour **G2RD** est listée, suivez l’assistant WordPress (téléchargement puis remplacement des fichiers du thème).

**Recommandation** : faites une **sauvegarde** du site (fichiers + base de données) avant toute mise à jour majeure, ou testez d’abord sur une copie de site (environnement de préproduction).

## Après la mise à jour

- Rechargez une page du site et l’**éditeur de site** pour vérifier que tout s’affiche correctement.
- Si vous utilisez un **plugin de cache**, videz le cache.
- Consultez les notes de version sur les [Releases GitHub](https://github.com/SebG2RD/g2rd-theme/releases) ou le fichier `changelog.txt` fourni avec le thème pour les changements importants.

## Liens utiles

- [Migration depuis une ancienne version](migration-ancienne-version.md) pour les grosses montées de version.
- [Résolution des problèmes](depannage.md) si la mise à jour échoue ou casse l’affichage.
