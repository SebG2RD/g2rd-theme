# Utilisation des blocs G2RD

Les **blocs** sont les briques que vous assemblez dans **Gutenberg** (l’éditeur de blocs de WordPress) pour construire pages et articles.

## Où trouver les blocs G2RD

1. Ouvrez une **page** ou un **article** (ou un modèle du site selon votre configuration).
2. Cliquez sur **+** pour ajouter un bloc.
3. Dans la liste ou la recherche, cherchez le préfixe **G2RD** ou le nom du bloc (Portfolio, Services, Témoignages, FAQ, etc.).

Les blocs du thème sont enregistrés sous l’espace de noms `g2rd/…` : ils apparaissent avec les autres blocs WordPress et extensions.

## Licence et insertion des blocs

Le thème est conçu pour rester **utilisable** même sans licence. En revanche, **sans licence active**, l’ajout de **nouveaux** blocs premium G2RD depuis l’inserter peut être **restreint** : les contenus déjà créés sur le site restent en général affichés, mais votre agence pourra vous demander d’activer la licence pour débloquer pleinement l’édition ou l’ajout de blocs G2RD.

Détail technique pour les développeurs : voir [../licensing.md](../licensing.md).

## Bonnes pratiques pour le contenu

- Utilisez les **titres** (blocs « Titre ») dans un ordre logique (un grand titre principal, puis des sous-titres) pour l’accessibilité et le SEO.
- Prévisualisez en **mobile** : beaucoup de blocs G2RD sont responsives, mais le texte trop long ou les images lourdes peuvent nuire à l’expérience.
- Pour des aides intégrées à l’éditeur, activez [SEO Helper](seo-helper.md) et [GEO Analyzer](geo-analyzer.md) dans **Apparence → Options G2RD → Clients**.

## Documentation plus poussée

Une documentation orientée **développeurs / structure des blocs** existe dans le dossier [../blocks/](../blocks/README.md) et [../blocks.md](../blocks.md). Les clients finaux n’en ont en principe pas besoin pour rédiger du contenu au quotidien.
