# Résolution des problèmes (dépannage)

Liste de **vérifications simples** avant d’ouvrir un ticket support. Procédez dans l’ordre si possible.

## 1. Versions WordPress et PHP

- WordPress doit être en **6.6 minimum** (idéalement à jour dans la branche supportée).
- PHP doit être en **8.0 minimum** sur l’hébergement.

Tableau de bord → **Mises à jour** ou page « Santé du site » (si disponible) pour un aperçu.

## 2. Licence

Si les **mises à jour automatiques** du thème n’apparaissent pas, ou si l’**insertion de blocs G2RD** semble bloquée, vérifiez **Apparence → Options G2RD → Licence** : la licence doit être **active** sur ce domaine.

Voir [Activation de la licence](activation-licence.md) et [FAQ licence](faq-licence.md).

## 3. Cache

Un **plugin de cache** ou le cache du **CDN / hébergeur** peut afficher une ancienne version du site.

- Videz le cache du plugin (bouton souvent dans la barre admin).
- Videz le cache côté hébergeur si vous l’utilisez.

## 4. Avis Google qui ne se mettent pas à jour

Vérifiez la **clé API** et le **Place ID** ([Avis Google](avis-google.md)), puis utilisez **Vider le cache des avis** dans **Options G2RD → Configuration** si besoin.

## 5. Panneaux SEO / GEO invisibles

Ils s’activent dans **Apparence → Options G2RD → Clients** :

- **Aide SEO** — surtout pour les **articles** et **pages**.
- **Aide GEO** — panneau GEO Analyzer dans la barre latérale de l’éditeur.

Rechargez l’éditeur (F5) après avoir enregistré les options.

## 6. Erreur critique / écran blanc

- Si WordPress affiche un message d’erreur, notez l’heure et contactez votre **hébergeur** ou votre **agence** (souvent lié à PHP, mémoire, ou conflit d’extension).
- Si vous avez accès aux sauvegardes, une restauration peut être nécessaire après une mise à jour hasardeuse.

## 7. Conflit avec une autre extension

Désactivez **temporairement** les autres plugins (sur une copie de site de préférence), puis réactivez-les un par un pour identifier un conflit. Ne laissez pas le site en production sans les extensions de sécurité habituelles.

## Encore bloqué ?

Préparez pour le support : URL du site, rôle du compte utilisé, capture d’écran, étapes pour reproduire le problème, et confirmation que la **licence** est active si le sujet concerne les mises à jour ou les blocs premium.
