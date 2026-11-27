# G2RD Rescue 18 — thème enfant

Refonte de [rescue18.com](https://rescue18.com), le webmédia des sapeurs-pompiers.
Thème enfant de `g2rd-theme` (FSE). Le parent n'est jamais modifié : tout le spécifique
au site vit ici.

Référence visuelle : maquettes Claude Design « Rescue18 Maquettes » (octobre 2026) et
prototype `rescue18_maquette.html`. Consignes : `consignes_codex_claude.md` (projet Rescue 18).

## Installation

1. Copier `g2rd-rescue18-child/` dans `wp-content/themes/` (le parent `g2rd-theme` 1.39.0+ doit être installé).
2. Activer le thème. La charte (`styles/rescue18.json`) s'applique seule, le cycle de veille quotidien est planifié (3 h 17).
3. Lancer l'installateur :

   ```bash
   wp rescue18 installer          # termes, pages aux URL historiques, accueil et page des articles
   wp rescue18 installer --force  # remplace aussi le contenu des pages existantes par les compositions
   ```

   Ou, sans ligne de commande : *Outils › Préparer Rescue 18*. L'écran reprend le contenu de
   rescue18.com par lots (structure, articles, fiches GDO / GTO / GNR / référentiels / RETEX,
   textes juridiques, épisodes, quiz, images, boutique si WooCommerce est actif). Chaque étape
   est relançable sans doublon (méta `r18_source_id`), slugs et dates d'origine conservés.

4. Déposer le logo officiel dans *Apparence › Éditeur › Identité du site*. Tant qu'il manque,
   le logotype typographique « /// RESCUE 18 » de la maquette s'affiche.
5. Renseigner les réseaux sociaux (bloc Icônes sociales) dans la partie de modèle « Pied de page ».

## Structure

```text
g2rd-rescue18-child/
├── style.css                  En-tête WordPress (Template: g2rd-theme)
├── theme.json                 settings uniquement : palette r18-*, Inter local, tailles, espacements
├── styles/rescue18.json       Variation appliquée par défaut (styles)
├── functions.php              Constantes et chargement des modules
├── inc/
│   ├── icons.php              Icônes SVG (générées par tools/icons.py)
│   ├── setup.php              Parent forcé, charte, assets, logo, styles de blocs
│   ├── content.php            Types de contenu, taxonomies, métadonnées, boîte de saisie
│   ├── blocks.php             Enregistrement des blocs dynamiques + fonctions communes
│   ├── blocks-docs.php        Bibliothèque, nouveautés, veille juridique, fiches
│   ├── blocks-media.php       Spécialités, ticker, podcasts, quiz, produits, article
│   ├── sync.php               Point d'entrée de la veille (charge inc/veille/)
│   ├── veille/                Connecteurs DGSCGC, PNRS, ENSOSP, moteur, écran Automatisations
│   ├── install.php            Termes, pages aux URL historiques, permaliens
│   ├── outils.php             Boîte à outils (registre, blocs)
│   ├── seo.php                Référencement de base sans extension
│   ├── import.php             Outils › Préparer Rescue 18 (import depuis rescue18.com)
│   └── cli.php                wp rescue18 installer
├── parts/                     header, header-commande, footer, newsletter
├── templates/                 accueil, archives, article, fiches, pages, WooCommerce, 404
├── patterns/                  sections d'accueil (10) et pages (bibliothèque, juridique, formation, podcasts)
├── assets/css/                global, icons, header-footer, accueil, pages, boutique (Woo seulement)
├── assets/js/                 rescue18 (commun), scene, bibliotheque, quiz, podcasts, editor-blocks
├── assets/img/casque-rescue18.webp
├── assets/fonts/inter-var.woff2
└── tools/icons.py             Source unique des icônes (PHP + masques CSS)
```

## Contenus métier

| Type | URL | Rôle |
| --- | --- | --- |
| `r18_document` | `/document/<fiche>/` | GDO, GTO, GNR, RETEX / PEX, référentiels |
| `r18_texte` | `/texte-juridique/<texte>/` | Lois, décrets, arrêtés suivis via Légifrance |
| `r18_episode` | `/podcast/<episode>/` | SSUAP Le Podcast, lecteur intégré |
| `r18_quiz` | `/quiz/<quiz>/` | Quiz publics, sans compte ni score enregistré |

Taxonomies : `r18_type_doc` (gdo, gto, gnr, retex, referentiel), `r18_specialite` (partagée avec
les articles, icône par terme), `r18_organisme`, `r18_public` (jsp, spv, spp).

Aucune archive de type de contenu : les rubriques historiques restent des pages, URL conservées.

| Page | Composition | Bloc |
| --- | --- | --- |
| `/documentations-referentiels/` | Page — Bibliothèque | `rescue18/bibliotheque` variante page |
| `/documentations-referentiels/juridique/` | Page — Veille juridique | `rescue18/veille-juridique` variante page |
| `/guides-doctrines-operationnelles-gdo-guides-techniques-operationnelles-gto/` | Page — Bibliothèque | type présélectionné `gdo,gto` |
| `/retex/` | Page — Bibliothèque | type présélectionné `retex` |
| `/formation/` | Page — Formation | quiz, parcours, fiches |
| `/ssuap-le-podcast/` | Page — Podcasts | `rescue18/episodes` variante page |
| `/articles/` | gabarit `home.html` | page des articles |

Format d'un quiz (champ « Questions ») :

```json
[{"q":"Quel sigle désigne un guide de doctrine opérationnelle ?","c":["GTO","GDO","RETEX"],"a":1,"why":"GDO signifie guide de doctrine opérationnelle."}]
```

Historique des versions (champ JSON des fiches) : `[{"date":"2026-07-01","label":"Édition juillet 2026","note":"…"}]`.
La première ligne est la version suivie.

## Blocs dynamiques (catégorie « Rescue 18 »)

Rendu PHP, aperçu serveur dans l'éditeur, aucun build. `bibliotheque`, `nouveautes`,
`veille-juridique`, `veille-statut`, `fiche-etiquettes`, `fiche-actions`, `fiche-references`,
`etat-juridique`, `historique`, `source-officielle`, `lies`, `specialites`, `ticker`, `episodes`,
`quiz`, `produits`, `fil-ariane` (SEOPress s'il est actif), `sommaire`, `outils-article`,
`newsletter` (MailPoet), `panier`.

Les scripts ne se chargent que sur les pages qui en ont besoin (`scene.js` avec le groupe
`r18-stage`, `bibliotheque.js`, `quiz.js`, `podcasts.js` avec leurs blocs).

## Boîte à outils

`inc/outils.php` porte le registre des outils (champs, méthode, sources, FAQ) et les blocs
`rescue18/outils` (catalogue filtrable, `/outils/`) et `rescue18/outil` (une page par outil,
`/outils/<outil>/`). Calcul en direct dans `assets/js/outils.js`, sans envoi de données. Les valeurs
numériques passent dans l'URL : un résultat se partage par lien. `assets/css/outils.css` ne se charge
que sur ces pages.

| Outil | Source |
| --- | --- |
| Calcul du RIS, dimensionnement DPS | Référentiel national DPS, arrêté du 7 novembre 2006 |
| Autonomie ARI | Guide ARI SDIS 01, INC-FTO-07.3 Gestion de l'air |
| Noria | Calcul de rotation (paramètres saisis) |
| Messages opérationnels | GDO Exercice du commandement 2020, mémento ENSOSP chef de groupe |

Réglages sans code : menu *Boîte à outils* du back-office (`inc/outils-admin.php`, option
`rescue18_outils`). Visibilité, ordre, mission, icône, textes, titre et description Google avec aperçu,
libellés, aides, unités, valeurs par défaut et bornes des champs, méthode, sources, FAQ. Un champ vide
reprend la valeur du thème. Les coefficients de la grille DPS et les types de message restent figés.
L'en-tête de chaque page d'outil est un bloc dynamique (`rescue18/outil-entete`) : il suit les réglages.

Ajouter un outil : une entrée dans `outils()`, une fonction de calcul dans `TOOLS` (outils.js),
une ligne dans `managed_pages()`, puis relancer l'étape « Structure ».

## Référencement

`inc/seo.php` : description, Open Graph, données structurées (WebSite, Organization,
BreadcrumbList, Article) tant qu'aucune extension SEO n'est active. WebApplication, FAQPage et
CollectionPage des outils restent actifs dans tous les cas.

## Hero 3D

Le casque est une illustration en relief CSS 3D (image `casque-rescue18.webp` issue du prototype
validé), comme dans la maquette. Anneaux, halo et sol sont des pseudo-éléments ; panneaux flottants
en paragraphes liés éditables (classe `r18-fc--N` pour la position, `r18-i--<icône>`, `r18-fc--dark`).
Perspective au curseur sur pointeur fin, coupée avec `prefers-reduced-motion` et hors écran.

Étape suivante prévue par les consignes : remplacer l'image par un modèle glTF autorisé et optimisé,
avec Three.js chargé à la demande dans `scene.js` et l'image actuelle en repli.

## Veille documentaire (DGSCGC, PNRS, ENSOSP)

Moteur unique dans `inc/veille/` (normalisation, HTTP encadré, connecteurs, décisions, intégration
WordPress, écran **Automatisations**). Un cycle par 24 h (`r18_daily_document_sync`), traitement par lots,
verrou, journal, rapprochement entre sources, versions, simulation. Documentation complète, rapport
d'accès aux sources et blocage DGSCGC : `docs/VEILLE.md`. Tests : `php tests/veille-test.php`.

## Parent forcé depuis l'enfant

`inc/compat.php` neutralise la recopie en base des gabarits du parent (`G2RD\FseSync`) sous le slug de
l'enfant et retire les copies déjà créées et jamais retouchées. Sans cela, l'en-tête, le pied de page et
les gabarits du parent masquent ceux de Rescue 18.

Mode sombre, GSAP, particules et effet verre désactivés (`rescue18_force_parent_features`).
CPT `portfolio`, `prestations`, `qui-sommes-nous` désactivés.

## Contrastes (RGAA 3.2 et 3.3)

| Usage | Couleur | Fond le plus défavorable | Ratio |
| --- | --- | --- | --- |
| Petits textes rouges, liens, icônes | `r18-rouge-texte` #BE0C19 | halo rose du hero #F3D6DA | 4,74:1 |
| Textes secondaires | `r18-gris` #535D6A | halo rose #F3D6DA | 4,92:1 |
| Texte blanc sur bouton rouge | #FFFFFF sur #E21422 | — | 4,82:1 |
| Grands titres rouges (≥ 24 px) | `r18-rouge` #E21422 | #E9EDF1 | 4,10:1 (3:1 requis) |
| Bordures de champs | `r18-bordure` #7E8896 | #F5F6F8 | 3,32:1 |
| Texte sur encadré rouge | #FFFFFF sur #D81523 | — | 5,18:1 |

Le rouge #E21422 reste la couleur de marque pour les aplats, les boutons et les grands titres.
Tout texte de moins de 24 px (ou 18,66 px gras) passe par `r18-rouge-texte`.

## Reste à faire

- Logo officiel, HEX exacts de la charte actuelle (le rouge `#E21422` vient du prototype).
- Logos partenaires autorisés à la place des noms (composition « Accueil — Partenaires »).
- Connecteurs officiels de la veille, migration des contenus (articles, PDF, boutique, SEO).
- Modèle 3D glTF autorisé.
- Les couleurs encore écrites en dur dans le CSS sont les arrêts de dégradés et les textes clairs
  sur fond sombre repris de la maquette ; les couleurs de charte passent toutes par les jetons.
