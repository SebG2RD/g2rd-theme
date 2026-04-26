# Migration depuis une ancienne version

« Migrer » signifie ici : vous aviez déjà **G2RD** (ou un site à préparer pour G2RD) et vous montez de **version**, ou vous changez d’environnement. L’objectif est d’**éviter la casse** et la perte de contenu.

## Avant toute chose

1. **Sauvegarde complète** : fichiers (`wp-content` au minimum) + **base de données**.
2. Idéalement, reproduisez la mise à jour sur une **copie de site** (staging) avant la production.

## Mise à jour du thème G2RD

1. Vérifiez la **licence** active si vous utilisez les mises à jour intégrées ([Mise à jour](mise-a-jour.md)).
2. Appliquez la mise à jour depuis **Apparence → Thèmes** ou **Mises à jour**.
3. Videz les **caches** (plugin, serveur, navigateur).

## Après la mise à jour

- Ouvrez l’**éditeur de site** (**Apparence → Éditeur**) et contrôlez les **templates** (en-tête, pied de page, pages modèles).
- Ouvrez quelques **pages clés** en édition pour vérifier que les blocs G2RD s’affichent et se sauvegardent.
- Retestez les intégrations : **Google / avis**, **SEO Helper**, **GEO** selon votre usage.

## Changement de domaine ou de serveur

- Les **contenus** (pages, médias) suivent la base de données migrée.
- La **licence** est souvent liée au **domaine** : vous devrez peut-être **désactiver** sur l’ancien site puis **réactiver** sur le nouveau ([FAQ licence](faq-licence.md)).
- Mettez à jour l’URL du site dans **Réglages → Général** si WordPress ne l’a pas fait automatiquement après une migration d’URL.

## Migration depuis un autre thème

Si vous **remplacez** un thème classique ou un autre FSE par G2RD :

- le **contenu** des pages reste, mais la **mise en page** peut changer fortement (blocs différents, templates FSE) ;
- prévoyez une **recomposition** des pages importantes dans l’éditeur ;
- listez les **fonctionnalités** de l’ancien thème (portfolio, shortcodes) pour les reproduire avec les blocs G2RD ou des extensions.

Pour une vue technique complémentaire (développeurs), voir [../migration.md](../migration.md).
