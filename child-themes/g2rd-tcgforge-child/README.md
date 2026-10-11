# G2RD — TCGForge

Thème enfant du thème G2RD pour TCGForge, boutique française de cartes à collectionner, d'accessoires TCG, de figurines et de produits dérivés.

Le thème gère l'affichage. Il ne contient aucune intégration fournisseur : ni API, ni cron, ni mapping, ni import. Flux produits, stocks, prix, commandes fournisseurs et suivi restent hors du thème.

- Parent requis : `g2rd-theme` 1.39.0 ou plus récent
- WordPress 6.6 minimum, testé en local sur WordPress 6.8 et 7.0
- WooCommerce avec modèles en blocs. Les filtres du catalogue utilisent le bloc « Filtres de produits »
- PHP 8.0 minimum

## Installation

1. Copier le dossier `g2rd-tcgforge-child` dans `wp-content/themes/`.
2. Copier `tcgForge.json` à la racine du thème parent `g2rd-theme/`. C'est le seul fichier ajouté au parent. Aucun fichier existant du parent n'est modifié.
3. Activer « G2RD — TCGForge » dans Apparence › Thèmes. La charte sombre s'applique seule, sans passer par le sélecteur de styles.
4. Installer et activer WooCommerce. Laisser WooCommerce créer ses pages (Panier, Commande, Mon compte).
5. Créer les contenus : Apparence › Installation TCGForge › « Créer les contenus manquants ». L'outil crée les pages et les guides depuis `gutenberg-pages/`, avec le bon slug et le bon modèle. Il ne remplace jamais un contenu existant. Tu peux aussi coller chaque fichier à la main (voir « Pages »).
6. Faire les réglages ci-dessous.

`tcgForge.json` et les mises à jour du parent : le GitHub Updater remplace tout le dossier `g2rd-theme` à chaque mise à jour, donc `tcgForge.json` disparaît. Le thème enfant retombe alors sur des valeurs identiques (theme.json et `inc/tokens.php`). Le rendu ne change pas. Recopie le fichier seulement si tu as personnalisé des tokens dedans.

## Réglages WordPress

| Réglage | Valeur |
| --- | --- |
| Réglages › Lecture › Page d'accueil | Page statique « Accueil » |
| Réglages › Permaliens › Structure personnalisée | `/conseils/%postname%/` (les guides sont des articles, leurs liens internes utilisent ce préfixe) |
| Réglages › Général › TCGForge › Formulaire de contact | Identifiant du formulaire Fluent Forms. Il alimente le shortcode `[tcgforge_contact]` de la page Contact |
| Réglages › Général › TCGForge › Formulaire de newsletter | Shortcode du formulaire d'inscription, par exemple `[fluentform id="2"]`. Vide : la section newsletter du pied de page reste masquée |
| Réglages › Identité du site | Logo facultatif. Sans logo, le thème affiche `assets/img/logo-tcgforge.svg` |
| Pages de licence et de rayon | Remplir le champ « Extrait » : il sert de chapeau. Les extraits sont préremplis par l'outil d'installation |
| Apparence › Éditeur › Navigation | Le rail des univers est un bloc Navigation. Ajoute, retire ou réordonne les liens ici, sans toucher au code |
| G2RD › Options | Le mode sombre du parent est coupé par le thème enfant (site sombre par nature). Désactive les animations GSAP si tu ne les utilises pas : le parent charge GSAP depuis un CDN |

## Réglages WooCommerce

| Réglage | Valeur |
| --- | --- |
| Produits › Page boutique | « Boutique » (slug `boutique`) |
| Produits › Inventaire | Gestion de stock activée. Le « seuil de stock faible » déclenche le badge Stock faible |
| Produits › Attributs | Créer `jeu`, `extension`, `langue`, `type-de-produit` (slugs exacts). Cartes, fiches et filtres les lisent dès qu'ils existent |
| Produits › Catégories | Slugs attendus : `pokemon`, `yu-gi-oh`, `one-piece`, `magic`, `lorcana`, `autres-tcg`, `accessoires`, `figurines`, `produits-derives`. Une page de licence affiche la catégorie qui porte le même slug qu'elle. Les sous-catégories (boosters, displays, coffrets…) deviennent des rayons de la page |
| Avancé › Pages | Conditions générales : « Conditions générales de vente ». Politique de confidentialité : dans Réglages › Confidentialité |
| Paiements | Le pied de page liste seulement les moyens activés, en texte. Aucun logo n'est affiché |
| Réglages › Général › Devise | Euro, séparateur décimal virgule |
| Site « bientôt disponible » | À désactiver au lancement |

### Données produit lues par le thème

Tout badge vient d'une donnée réelle. Sans donnée, rien ne s'affiche.

| Affichage | Source |
| --- | --- |
| Nouveau | Date de création du produit, 30 jours (filtre `tcgforge_new_badge_days`) |
| Précommande | Métadonnée `_tcgf_preorder` = `yes`, ou extension reconnue : WooCommerce Pre-Orders, YITH Pre-Order, Pre-Orders for WooCommerce. Filtre `tcgforge_is_preorder` |
| Promotion | Prix promo WooCommerce |
| Stock faible | Stock géré et quantité inférieure ou égale au seuil |
| Rupture | Statut de stock WooCommerce |
| Sur commande | Statut « en réapprovisionnement » |
| Date de sortie | `_tcgf_release_date` (AAAA-MM-JJ), ou la date de l'extension de précommande. Filtre `tcgforge_release_timestamp` |
| Expédition estimée | `_tcgf_shipping_estimate` (texte libre) |
| Jeu, extension, langue, type | Attributs `jeu` (ou `licence`), `extension`, `langue`, `type-de-produit`. Correspondance modifiable : filtre `tcgforge_fact_map` |
| Accessoires complémentaires | Ventes croisées du produit, sinon catégorie `accessoires` |
| Rayon « Dernières sorties » de l'accueil | Produits « Mis en avant » (étoile de la liste des produits) |

Les trois métadonnées `_tcgf_*` se saisissent aussi à la main : fiche produit, onglet Général. Ton import écrit dans ces clés. Une précommande dont la date de sortie est passée perd son badge toute seule.

### Compte à rebours

Le bloc `g2rd/countdown` du parent sert sur une landing de sortie (modèle « Page libre »). Le thème enfant le retire du rendu si la date est vide, invalide ou passée. Aucun compte à rebours n'est placé sur l'accueil.

## Blocs G2RD du parent retenus

Vérifiés dans le dépôt, puis en rendu réel.

| Bloc | Usage | Motif |
| --- | --- | --- |
| `g2rd/breadcrumb` | Pages et guides | Rendu PHP, JSON-LD, compatible extensions SEO |
| `g2rd/table-of-contents` | Guides et pages longues | Sommaire généré depuis les titres |
| `g2rd/countdown` | Landing de sortie, à la main | Autorisé seulement avec une vraie date |

Blocs écartés, avec la raison :

- `g2rd/filterable-grid` : la grille est chargée en JavaScript après l'affichage et ne filtre ni par attribut, ni par prix, ni par disponibilité. Le catalogue utilise les blocs WooCommerce officiels (collection de produits, filtres, tri, pagination), rendus côté serveur.
- `g2rd/hero`, `g2rd/cta-band`, `g2rd/card` : couleurs écrites en dur dans leurs attributs (charte G2RD). Le hero, les tuiles et le bandeau final sont faits en blocs natifs stylés par tokens.
- `g2rd/faq` : remplacé par des blocs Détails natifs, présents dans le DOM et valides au copier-coller.
- `g2rd/carousel`, `g2rd/marquee`, `g2rd/modal` : aucun besoin dans cette boutique. Ils restent disponibles dans l'éditeur.

## Blocs du thème enfant

Rendu PHP, sans build. Catégorie « WooCommerce » de l'éditeur.

| Bloc | Rôle |
| --- | --- |
| `tcgforge/products` | Rayon de produits : nouveautés, mis en avant, précommandes, promotions, meilleures ventes, catégorie, attribut. Masqué tant qu'il est vide si aucun message n'est saisi |
| `tcgforge/licence-products` | Catalogue d'une page de licence ou de rayon |
| `tcgforge/product-badges` | Badges de statut |
| `tcgforge/product-facts` | Jeu, extension, langue, type (étiquettes ou liste) |
| `tcgforge/product-release` | Précommande, date de sortie, expédition |
| `tcgforge/product-related` | Même licence, ou accessoires complémentaires |
| `tcgforge/payment-methods` | Moyens de paiement activés |
| `tcgforge/wishlist-link` | Lien liste de souhaits, affiché seulement si YITH Wishlist ou TI Wishlist est actif |
| `tcgforge/newsletter` | Section newsletter, affichée seulement si un formulaire est configuré |

Shortcodes : `[tcgforge_produits]` (mêmes options que le bloc, pour le contenu des pages) et `[tcgforge_contact]`.

## Pages

Chaque fichier de `gutenberg-pages/` contient du markup Gutenberg complet. Pour le coller à la main : nouvelle page, menu ⋮ › Éditeur de code, coller, enregistrer. Le titre de la page sert de H1 : il n'est pas répété dans le contenu.

| Fichier | Titre | Adresse | Type | Modèle |
| --- | --- | --- | --- | --- |
| `accueil.html` | Accueil | `/accueil/` | Page | Page libre |
| `boutique.html` | Boutique | `/boutique/` | Page | Par défaut |
| `nouveautes.html` | Nouveautés | `/nouveautes/` | Page | Par défaut |
| `precommandes-rayon.html` | Précommandes | `/precommandes/` | Page | Par défaut |
| `promotions.html` | Promotions | `/promotions/` | Page | Par défaut |
| `pokemon.html` | Pokémon TCG : boosters, displays et coffrets | `/cartes-a-collectionner/pokemon/` | Page | Univers ou rayon |
| `yu-gi-oh.html` | Yu-Gi-Oh! : boosters, decks de structure et tins | `/cartes-a-collectionner/yu-gi-oh/` | Page | Univers ou rayon |
| `one-piece.html` | One Piece Card Game : boosters, displays et decks | `/cartes-a-collectionner/one-piece/` | Page | Univers ou rayon |
| `magic.html` | Magic: The Gathering : boosters, decks Commander et bundles | `/cartes-a-collectionner/magic/` | Page | Univers ou rayon |
| `lorcana.html` | Disney Lorcana : boosters, decks et coffrets | `/cartes-a-collectionner/lorcana/` | Page | Univers ou rayon |
| `autres-tcg.html` | Autres jeux de cartes à collectionner | `/cartes-a-collectionner/autres-tcg/` | Page | Univers ou rayon |
| `accessoires.html` | Accessoires TCG : sleeves, deck box et classeurs | `/accessoires/` | Page | Univers ou rayon |
| `figurines.html` | Figurines et collectibles | `/figurines/` | Page | Univers ou rayon |
| `produits-derives.html` | Produits dérivés manga et gaming | `/produits-derives/` | Page | Univers ou rayon |
| `a-propos.html` | À propos de TCGForge | `/a-propos/` | Page | Par défaut |
| `contact.html` | Contact | `/contact/` | Page | Par défaut |
| `faq.html` | Questions fréquentes | `/aide/faq/` | Page | Par défaut |
| `livraison.html` | Livraison | `/aide/livraison/` | Page | Page avec sommaire |
| `retours-remboursements.html` | Retours et remboursements | `/aide/retours-remboursements/` | Page | Page avec sommaire |
| `authenticite.html` | Authenticité des produits | `/aide/authenticite/` | Page | Par défaut |
| `etat-emballage.html` | État et emballage des produits | `/aide/etat-emballage/` | Page | Par défaut |
| `precommandes.html` | Fonctionnement des précommandes | `/aide/precommandes-fonctionnement/` | Page | Page avec sommaire |
| `suivi-de-commande.html` | Suivi de commande | `/aide/suivi-de-commande/` | Page | Par défaut |
| `conseils.html` | Conseils et guides | `/conseils/` | Page | Par défaut |
| `guide-debuter-tcg.html` | Bien débuter dans les TCG | `/conseils/bien-debuter-tcg/` | Article | Par défaut |
| `guide-proteger-cartes.html` | Comment protéger ses cartes | `/conseils/proteger-ses-cartes/` | Article | Par défaut |
| `guide-ranger-collection.html` | Comment ranger une collection | `/conseils/ranger-collection-cartes/` | Article | Par défaut |
| `guide-protections.html` | Sleeves, top loaders et protections | `/conseils/sleeves-top-loaders-protections/` | Article | Par défaut |
| `guide-produits-tcg.html` | Comprendre un display, un booster et un coffret | `/conseils/comprendre-display-booster-coffret/` | Article | Par défaut |
| `guide-conserver-cartes.html` | Comment conserver des cartes de collection | `/conseils/conserver-cartes-collection/` | Article | Par défaut |
| `mentions-legales.html` | Mentions légales | `/informations-legales/mentions-legales/` | Page | Par défaut |
| `cgv.html` | Conditions générales de vente | `/informations-legales/cgv/` | Page | Page avec sommaire |
| `confidentialite.html` | Politique de confidentialité | `/informations-legales/politique-de-confidentialite/` | Page | Page avec sommaire |
| `cookies.html` | Politique de cookies | `/informations-legales/politique-de-cookies/` | Page | Par défaut |
| `retractation.html` | Droit de rétractation | `/informations-legales/droit-de-retractation/` | Page | Par défaut |
| `formulaire-retractation.html` | Formulaire de rétractation | `/informations-legales/formulaire-de-retractation/` | Page | Par défaut |
| `conditions-precommandes.html` | Conditions de précommande | `/informations-legales/conditions-de-precommande/` | Page | Page avec sommaire |
| `accessibilite.html` | Accessibilité | `/informations-legales/accessibilite/` | Page | Par défaut |

Pages créées par WooCommerce, à laisser telles quelles : Panier, Commande, Mon compte. Le thème fournit leurs modèles et leur habillage.

L'accueil fonctionne sans contenu : `templates/front-page.html` assemble les compositions. `accueil.html` en est la copie éditable. Pour piloter l'accueil depuis la page, ouvre le modèle « Page d'accueil » dans l'éditeur de site et remplace son contenu par un bloc Contenu.

## Compositions (patterns)

| Slug | Titre |
| --- | --- |
| `tcgforge/accueil-hero` | Accueil — Hero |
| `tcgforge/catalogue-recherche` | Catalogue — Résultats de recherche |
| `tcgforge/catalogue` | Catalogue — Liste de produits filtrable |
| `tcgforge/confiance` | Bloc confiance |
| `tcgforge/cta` | Appel à l'action |
| `tcgforge/editorial` | Bloc éditorial |
| `tcgforge/faq-accueil` | Questions fréquentes (accueil) |
| `tcgforge/guides-recents` | Guides récents |
| `tcgforge/rassurance-produit` | Fiche produit — Livraison et retours |
| `tcgforge/selections-produits` | Accueil — Sélections de produits |
| `tcgforge/univers` | Univers et licences |

## Modèles

| Fichier | Rôle |
| --- | --- |
| `templates/404.html` | Page introuvable |
| `templates/archive-product.html` | Boutique |
| `templates/archive.html` | Archives d'articles |
| `templates/front-page.html` | Accueil (compositions dynamiques) |
| `templates/home.html` | Liste des articles |
| `templates/index.html` | Repli |
| `templates/order-confirmation.html` | Commande validée |
| `templates/page-accueil.html` | Page Accueil éditable (neutralise le modèle page-accueil du parent) |
| `templates/page-cart.html` | Panier |
| `templates/page-checkout.html` | Commande (en-tête allégé) |
| `templates/page-contact.html` | Page Contact (neutralise le modèle page-contact du parent) |
| `templates/page-guide.html` | Page avec sommaire collé |
| `templates/page-libre.html` | Page libre pleine largeur (landing de sortie) |
| `templates/page-licence.html` | Univers ou rayon : chapeau, catalogue de la catégorie, texte |
| `templates/page.html` | Page par défaut |
| `templates/product-search-results.html` | Résultats de recherche produit |
| `templates/search.html` | Recherche dans les guides et les pages |
| `templates/single-product.html` | Fiche produit |
| `templates/single.html` | Guide (article) avec sommaire |
| `templates/taxonomy-product_attribute.html` | Attribut produit (jeu, extension, langue…) |
| `templates/taxonomy-product_cat.html` | Catégorie produit |
| `templates/taxonomy-product_tag.html` | Étiquette produit |

Éléments de modèle : `parts/header.html`, `parts/header-commande.html`, `parts/footer.html`.

## Champs à compléter

Aucune information juridique n'est inventée. Remplace chaque champ entre crochets avant de publier.

| Champ | Fichiers |
| --- | --- |
| `[RAISON_SOCIALE]` | a-propos, cgv, confidentialite, contact, formulaire-retractation, mentions-legales, retractation |
| `[FORME_JURIDIQUE]` | a-propos, cgv, mentions-legales |
| `[CAPITAL_SOCIAL]` | cgv, mentions-legales |
| `[SIREN]` | cgv, mentions-legales |
| `[RCS]` | cgv, mentions-legales |
| `[TVA_INTRACOMMUNAUTAIRE]` | cgv, mentions-legales |
| `[ADRESSE_SIEGE]` | cgv, confidentialite, contact, formulaire-retractation, mentions-legales, retractation |
| `[EMAIL]` | accessibilite, cgv, conditions-precommandes, confidentialite, contact, formulaire-retractation, mentions-legales, retours-remboursements, retractation |
| `[TELEPHONE]` | cgv, contact, mentions-legales |
| `[DIRECTEUR_PUBLICATION]` | mentions-legales |
| `[HEBERGEUR]` | mentions-legales |
| `[MEDIATEUR_CONSOMMATION]` | cgv, mentions-legales |
| `[DATE_MISE_A_JOUR]` | accessibilite, cgv, conditions-precommandes, confidentialite, cookies, mentions-legales, retractation |
| `[ADRESSE_RETOUR]` | formulaire-retractation, retours-remboursements, retractation |
| `[CONTENUS_NON_ACCESSIBLES]` | accessibilite |
| `[COOKIES_PUBLICITAIRES]` | cookies |
| `[DATE_AUDIT_ACCESSIBILITE]` | accessibilite |
| `[DELAI_1]` | livraison |
| `[DELAI_2]` | livraison |
| `[DELAI_3]` | livraison |
| `[DELAI_PREPARATION]` | livraison |
| `[HORAIRES_SERVICE_CLIENT]` | contact |
| `[LIEN_GESTION_COOKIES]` | cookies |
| `[MODALITE_PAIEMENT_PRECOMMANDE]` | conditions-precommandes, precommandes |
| `[OUTIL_MESURE_AUDIENCE]` | cookies |
| `[SEUIL_LIVRAISON_OFFERTE]` | livraison |
| `[TARIF_1]` | livraison |
| `[TARIF_2]` | livraison |
| `[TARIF_3]` | livraison |
| `[TRANSFERTS_HORS_UE]` | confidentialite |
| `[TRANSPORTEUR_1]` | livraison |
| `[TRANSPORTEUR_2]` | livraison |
| `[TRANSPORTEUR_3]` | livraison |
| `[ZONES_LIVRAISON]` | livraison |

Points à valider avant mise en ligne, car ils engagent la boutique :

- Les engagements des pages Authenticité, État et emballage, À propos (produits officiels, neufs, circuits professionnels, emballage). Ils décrivent une boutique type. Aligne-les sur tes fournisseurs réels.
- Le moment du débit d'une précommande : `[MODALITE_PAIEMENT_PRECOMMANDE]`.
- La mention « prix le plus bas des trente derniers jours » des promotions (page Promotions et CGV, article 6). C'est la règle légale d'annonce de réduction de prix. WooCommerce ne la calcule pas seul.
- Les CGV, les conditions de précommande et la politique de confidentialité sont des bases rédigées avec soin. Fais-les relire par un juriste avant ouverture.
- Les liens des réseaux sociaux du pied de page pointent vers `#`.

## Charte

| Token | Valeur | Usage |
| --- | --- | --- |
| Noir | `#09090B` | Fond |
| Surface sombre | `#18181B` | Cartes, panneaux |
| Surface relevée | `#27272A` | Survols, en-têtes de tableau |
| Blanc cassé | `#FAFAFA` | Texte |
| Texte atténué | `#A1A1AA` | Texte secondaire |
| Violet | `#7C3AED` | Marque, boutons |
| Violet clair | `#A855F7` | Soulignés, repères, décor |
| Lien | `#C084FC` | Liens et nom de licence sur fond sombre |
| Or | `#F59E0B` | Précommandes, sorties, éléments premium. Toujours en aplat avec texte noir, ou en texte sur fond sombre |

Les slugs de couleur du parent (`primary`, `secondary`, `white`…) sont conservés et inversés pour un site sombre. Les blocs G2RD restent lisibles sans réglage.

Typographie : Plus Jakarta Sans pour les titres, Inter pour le texte, toutes deux auto-hébergées dans `assets/fonts/`. Aucune requête vers Google Fonts.

CSS : préfixe `tcgf-`, couleurs par tokens seulement, aucune valeur hexadécimale dans les feuilles de style. `!important` n'apparaît que dans le bloc `prefers-reduced-motion` de `global.css`, exception documentée sur place.

Animations : survol des cartes (tranche holo), éventail du hero au chargement, micro-interactions des boutons et du menu. Pas de particules, pas de curseur, pas d'animation au défilement. `prefers-reduced-motion` coupe tout.

## Contrôles effectués

Environnement de test local : WordPress 7.0, thème parent 1.39.0, WooCommerce (code PHP de la branche principale, 11 produits de démonstration couvrant tous les statuts).

- Syntaxe PHP de tous les fichiers, JSON, JavaScript
- Markup Gutenberg : 74 fichiers passés au parseur et au validateur de blocs de WordPress, zéro bloc invalide
- Chemins des assets, slugs des compositions, liens internes vers des pages existantes
- 21 pages rendues à 360, 480, 768, 1024, 1280 et 1440 px : aucun débordement horizontal, un seul H1 par page
- Contrastes des couples texte/fond de la charte : tous au niveau AA, la plupart AAA
- Navigation clavier, focus visible, réduction des animations
- Boutique, catégorie, filtres par attribut, fiche produit simple et variable, précommande, rupture, promotion, recherche avec et sans résultat, 404, compte, ajout au panier, page de commande, commande validée : rendus sans erreur ni avertissement PHP
- Thème parent : aucun fichier modifié, `tcgForge.json` seul ajouté

Limite du test : les blocs Panier, Commande et mini-panier de WooCommerce s'affichent en JavaScript à partir de fichiers compilés, absents de l'environnement de test. Leur habillage (`assets/css/woocommerce.css`) est écrit d'après le code source de WooCommerce mais n'a pas été vu à l'écran. Vérifie ces trois écrans sur ta préproduction, ainsi que le tiroir de filtres sur mobile.

## Arborescence

```text
g2rd-theme/
    tcgForge.json                      (seul ajout au parent)
    child-themes/
        g2rd-tcgforge-child/
            functions.php
            screenshot.png
            style.css
            theme.json
            assets/
                css/
                    catalog.css
                    global.css
                    header-footer.css
                    home.css
                    pages.css
                    product.css
                    tcg.css
                    woocommerce.css
                fonts/
                    inter-var.woff2
                    plus-jakarta-sans-var.woff2
                img/
                    etincelle-or.svg
                    etincelle.svg
                    icone-tcgforge.svg
                    logo-tcgforge-clair.svg
                    logo-tcgforge.svg
                js/
                    editor-blocks.js
                    tcgforge.js
            gutenberg-pages/
                (38 fichiers .html, voir « Pages »)
            inc/
                blocks.php
                product-data.php
                setup.php
                tokens.php
                woocommerce.php
            parts/
                footer.html
                header-commande.html
                header.html
            patterns/
                accueil-hero.php
                catalogue-recherche.php
                catalogue.php
                confiance.php
                cta.php
                editorial.php
                faq-accueil.php
                guides-recents.php
                rassurance-produit.php
                selections-produits.php
                univers.php
            styles/
                tcgforge.json
            templates/
                404.html
                archive-product.html
                archive.html
                front-page.html
                home.html
                index.html
                order-confirmation.html
                page-accueil.html
                page-cart.html
                page-checkout.html
                page-contact.html
                page-guide.html
                page-libre.html
                page-licence.html
                page.html
                product-search-results.html
                search.html
                single-product.html
                single.html
                taxonomy-product_attribute.html
                taxonomy-product_cat.html
                taxonomy-product_tag.html
```

## Arborescence des pages (1.1.0)

Trois pages parentes regroupent les contenus :

- `/cartes-a-collectionner/` : pokemon, yu-gi-oh, one-piece, magic, lorcana, autres-tcg
- `/aide/` : faq, livraison, retours-remboursements, suivi-de-commande, authenticite, etat-emballage, precommandes-fonctionnement
- `/informations-legales/` : mentions-legales, cgv, politique-de-confidentialite, politique-de-cookies, droit-de-retractation, formulaire-de-retractation, conditions-de-precommande, accessibilite

Les guides sont des articles sous `/conseils/` (Réglages › Permaliens : `/conseils/%postname%/`).

À la mise à jour du thème, la première visite de l'administration ajoute les pages parentes, rattache les pages existantes, corrige les liens internes des contenus et remplit les titres et descriptions SEOPress restés vides. Le bouton « Harmoniser les pages » (Apparence › Installation TCGForge) relance l'opération. Le plan est défini dans `inc/setup.php` (`structure_map()`, `seo_map()`).

## Pages légales et rétractation en ligne (1.2.0)

- Les pages légales nomment le vendeur : G2RD Brands, SASU, capital 100 €, 8 rue Maurice Maire, 27600 Gaillon. Les numéros restent en champs `[… À COMPLÉTER]`.
- `inc/withdrawal.php` : bloc `tcgforge/withdrawal`, placé sur la page `/informations-legales/formulaire-de-retractation/`. Deux écrans, sans compte ni motif. Chaque notification est enregistrée (WooCommerce › Rétractations), notée sur la commande, envoyée à l'e-mail d'administration et confirmée au client. Aucun remboursement automatique.
- Filtres : `tcgforge_withdrawal_recipient` (destinataire interne), `tcgforge_withdrawal_url` (adresse de la page).
- Tunnel de commande : bouton « Commande avec obligation de paiement » et case d'acceptation des CGV.
- Apparence › Installation TCGForge › « Mettre à jour les pages légales » remplace le contenu des pages cochées par les fichiers de `gutenberg-pages/`. L'ancien texte reste dans les révisions.
- Aucun gestionnaire de cookies : le site ne dépose aujourd'hui aucun traceur soumis au consentement. En ajouter un avant toute mesure d'audience ou publicité.
