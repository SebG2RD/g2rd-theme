# Installation du thème (client)

Ce guide décrit l’installation **comme un produit livré en fichier ZIP**, sans ligne de commande.

## Avant de commencer

Votre hébergement doit respecter au minimum :

- **WordPress 6.6** ou une version plus récente ;
- **PHP 8.0** ou plus récent.

Si une version plus ancienne est indiquée par votre hébergeur, mettez à jour PHP depuis le panneau d’hébergement ou demandez à votre prestataire.

## Méthode recommandée : téléversement dans WordPress

1. Connectez-vous au **tableau de bord** WordPress (souvent `votre-site.fr/wp-admin`).
2. Allez dans **Apparence → Thèmes**.
3. Cliquez sur **Ajouter** puis sur **Téléverser un thème**.
4. Choisissez le fichier **ZIP** du thème G2RD fourni par votre agence ou votre achat.
5. Cliquez sur **Installer maintenant**, puis sur **Activer** une fois l’installation terminée.

Le site utilise immédiatement le nouveau thème. Le contenu des pages et articles **reste en base** ; en revanche, l’**apparence** (mise en page FSE, menus visuels) dépend des templates du thème : prévoyez une relecture du site après activation.

## Méthode alternative : FTP / gestionnaire de fichiers

Si votre hébergeur ne permet pas le téléversement ZIP :

1. Décompressez le ZIP sur votre ordinateur (vous obtenez un dossier, par exemple `g2rd-theme`).
2. Envoyez ce dossier dans `wp-content/themes/` sur le serveur (FTP ou « Gestionnaire de fichiers » du panneau).
3. Dans WordPress : **Apparence → Thèmes**, repérez **G2RD** et cliquez sur **Activer**.

## Après l’installation

- Ouvrez **Apparence → Options G2RD** pour vérifier les réglages (licence, type de site, intégrations).
- Parcourez le site en navigation privée pour voir ce que les visiteurs voient réellement.

## Liens utiles

- [Activation de la licence](activation-licence.md) si vous utilisez les fonctionnalités premium.
- [Résolution des problèmes](depannage.md) si quelque chose bloque.
