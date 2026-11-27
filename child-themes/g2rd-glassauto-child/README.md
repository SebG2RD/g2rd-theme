# G2RD — GlassAuto Vernon

Thème enfant du thème G2RD pour le centre GlassAuto de Vernon (Eure) : réparation et remplacement de vitrage automobile. Il reproduit les maquettes validées le 23 octobre 2026 (`design-preview/`) avec des blocs natifs stylés par les jetons de `theme.json`, et des blocs G2RD à rendu PHP là où ils apportent une fonction.

- Parent requis : `g2rd-theme` 1.39.0 ou plus récent (testé avec 1.41.1)
- WordPress 6.6 minimum, testé sur 6.9 et 7.0
- PHP 8.0 minimum

Le thème gère l'affichage du site public. L'espace partenaires, la réservation directe et les commissions sont dans l'extension **G2RD Business Connect**, jamais dans le thème.

## Installation

1. Copier le dossier `g2rd-glassauto-child` dans `wp-content/themes/` à côté de `g2rd-theme`. Aucun fichier du parent n'est ajouté ni modifié.
2. Activer « G2RD GlassAuto Vernon » dans Apparence › Thèmes. La charte s'applique seule, sans passer par le sélecteur de styles.
3. Installer et activer les extensions prévues : FluentSMTP, Fluent Forms, Ultimate Member, Loco Translate, SEOPress et SEOPress PRO, G2RD Connector, Imagify, G2RD Business Connect.
4. Créer les contenus : Apparence › Installation GlassAuto › « Créer les contenus manquants », ou `wp glassauto install`. L'outil crée les 23 pages depuis `gutenberg-pages/` et `patterns/`, les 6 articles Conseils depuis `gutenberg-posts/` (avec leur image à la une et la catégorie « Conseils »), la navigation principale et les réglages de lecture (permaliens `/actualites/%postname%/` pour les articles). Il ne remplace jamais un contenu existant ; seules exceptions : le brouillon « Politique de confidentialité » créé par WordPress est complété et publié, et l'article d'exemple « Bonjour tout le monde ! », s'il n'a pas été modifié, est mis à la corbeille.
5. Préremplir SEOPress : Apparence › Installation GlassAuto › « Préremplir SEOPress », ou `wp glassauto seo` (voir la section SEOPress).
6. Faire les réglages ci-dessous.

## Réglages WordPress

| Réglage | Valeur |
| --- | --- |
| Réglages › Général › **GlassAuto Vernon** | Adresse, téléphone, e-mail, coordonnées GPS, horaires jour par jour, lien de réservation du réseau, lien « Laisser un avis », Google Place ID, identifiants des formulaires Fluent Forms, communes retirées de la zone. Vide = fiche officielle glassauto.fr. |
| Réglages › Lecture | Page d'accueil « Accueil », page des articles « Actualités » (posés par l'installateur) |
| Réglages › Permaliens | `/%postname%/` (posé par l'installateur) |
| Réglages › Identité du site | Logo officiel GlassAuto une fois fourni par le réseau. Sans logo, le thème affiche le logotype typographique provisoire |
| G2RD › Thème | Clé API Google Maps (avis Google). Le mode sombre, GSAP, particules et glassmorphism sont coupés par le thème enfant |
| Apparence › Éditeur › Navigation | « Navigation principale » : ajouter, retirer, réordonner les liens sans toucher au code |

### Fluent Forms

Deux formulaires à créer, puis saisir leur identifiant dans Réglages › Général › GlassAuto Vernon :

| Formulaire | Shortcode dans la page | Champs attendus |
| --- | --- | --- |
| Demande de rendez-vous | `[glassauto_formulaire type="rdv"]` (page Prendre rendez-vous) | Vitrage concerné, type de dommage, immatriculation, type de véhicule, marque, modèle, assurance bris de glace (oui/non/ne sais pas), assureur, lieu d'intervention (au centre / à domicile, avec l'adresse), jours et moments disponibles, prénom, nom, téléphone, e-mail, commune, précisions |
| Contact / rappel | `[glassauto_formulaire type="contact"]` (page Contact) | Objet (question, assurance, être rappelé, devenir apporteur, autre), prénom, nom, téléphone, e-mail, moment du rappel, message, consentement |

Activer l'antispam (honeypot + Turnstile ou reCAPTCHA), les notifications par FluentSMTP et la mention RGPD. Les deux formulaires tiennent dans la version gratuite de Fluent Forms (pas de champ fichier : la photo du dommage est demandée en réponse à l'e-mail de confirmation ; logique conditionnelle, saut de section, Turnstile et notifications multiples sont inclus). Une candidature d'apporteur ne crée jamais de compte : elle arrive par e-mail au responsable.

### SEOPress

- Les titles et meta descriptions initiaux sont posés par l'installateur sur chaque page et chaque article (`_seopress_titles_title`, `_seopress_titles_desc`), modifiables dans l'éditeur.
- Pages hors index : espace partenaires, mentions légales, confidentialité, cookies, conditions, accessibilité.
- `wp glassauto seo` (ou le bouton « Préremplir SEOPress ») pose, sans rien écraser : les modules à activer (titres, sitemaps, social, robots, fil d'Ariane, Local Business, données structurées), le séparateur et les modèles de titres, les archives auteur/date/recherche/médias hors index, les sitemaps XML (pages, articles, catégories, images), le Knowledge Graph (Organization, nom, téléphone) et, si SEOPress PRO est actif, Local Business (type `AutoRepair`, adresse, GPS, Place ID, téléphone, horaires jour par jour depuis Réglages › Général). Relancer la commande après avoir saisi le Place ID.
- Le thème n'émet aucun JSON-LD LocalBusiness, Organization ou Article : ce sont les schémas de SEOPress / SEOPress PRO. Pour les articles, ajouter dans SEOPress PRO › Données structurées un schéma automatique « Article » limité au type de contenu Article.
- Fil d'Ariane : le bloc `g2rd/breadcrumb` du parent affiche le fil, mais il n'émet pas son JSON-LD BreadcrumbList dès qu'un plugin SEO est actif. Le thème enfant garantit qu'il y en a un, et un seul (`inc/seo.php`) : si SEOPress PRO › Fil d'Ariane › « JSON-LD » est coché (option `seopress_breadcrumbs_json_enable`), c'est SEOPress PRO qui l'émet dans le `<head>` ; sinon le thème le construit depuis le fil rendu. Les deux réglages sont donc corrects ; ne pas ajouter de troisième source.
- FAQ : les blocs `g2rd/faq` en mode GEO émettent le JSON-LD FAQPage. Ne pas activer en plus le schéma FAQ de SEOPress sur ces pages.
- L'étude géographique, le mapping mots-clés/pages, le maillage, le plan Google Business Profile et la liste de contrôle SEOPress sont dans [`../docs/seo-local.md`](../docs/seo-local.md).

### Articles Conseils

Six articles dans `gutenberg-posts/`, catégorie « Conseils », avec image à la une importée depuis `assets/img/photos/` (une seule fois, repérée par la méta `_g2rd_glassauto_source`). Les faits réglementaires cités (instruction technique du contrôle technique IT VL F3 « Visibilité » du 5 novembre 2019, articles L113-2 et L211-5-1 du Code des assurances, clause type bonus-malus) sont sourcés dans `docs/seo-local.md`. Les pages de prestations et les articles affichent la composition « Conseils » (trois derniers articles) avant l'appel à l'action final.

### Avis Google

Le bloc Témoignage G2RD en mode Google (composition « Avis Google ») affiche uniquement des avis authentiques, via l'endpoint `/g2rd/v1/google-reviews` du parent. Il faut une clé API Google Maps (G2RD › Thème) et le Place ID du centre (Réglages › Général). Sans eux, des emplacements vides s'affichent.

## Ce que contient le thème

```text
g2rd-glassauto-child/
├── style.css                 En-tête du thème (Template: g2rd-theme)
├── theme.json                Réglages : palette GlassAuto, polices, tailles, espacements, ombres, jetons custom
├── styles/glassauto.json     Styles de la charte, appliqués par défaut (functions.php, priorité 20)
├── functions.php             Modules, neutralisation du parent (mode sombre, CPT), styles de boutons, assets
├── inc/
│   ├── centre.php            Informations du centre (réglages + jetons {{tel}}, {{adresse}}, {{annee}}…)
│   ├── blocks.php            Blocs dynamiques glassauto/* (rendu PHP)
│   ├── forms.php             Shortcode [glassauto_formulaire] → Fluent Forms
│   ├── seo.php               Garde-fous contre les doublons de données structurées
│   ├── setup.php             Installateur (pages, navigation, réglages) + page Apparence › Installation
│   └── cli.php               wp glassauto install | pages | navigation | centre
├── parts/                    header.html, footer.html
├── templates/                index, home, archive, search, single, page, page-libre, page-prestation, page-contact, 404
├── patterns/                 15 compositions (catégorie « GlassAuto — Sections »), dont « Conseils »
├── gutenberg-pages/          Contenus des 22 pages (markup Gutenberg)
├── gutenberg-posts/          Les 6 articles Conseils (markup Gutenberg, image à la une importée)
└── assets/
    ├── css/                  global, icons (masques SVG), header-footer, sections
    ├── js/                   glassauto.js (front) · editor-blocks.js (éditeur)
    ├── fonts/                Outfit et Roboto Flex (SIL OFL)
    ├── img/photos/           Photos d'illustration Unsplash, à remplacer (voir design-preview/assets/img/CREDITS.md)
    ├── img/maps/             Fonds de carte OpenStreetMap
    └── data/                 communes.json (distances routières), maps.json
```

### Blocs dynamiques (catégorie « GlassAuto Vernon »)

| Bloc | Rôle |
| --- | --- |
| `glassauto/horaires` | Tableau des horaires, complet ou résumé |
| `glassauto/coordonnees` | Adresse, téléphone, e-mail, horaires, itinéraire |
| `glassauto/diagnostic` | Schéma interactif « Réparer ou remplacer ? » |
| `glassauto/communes` | Communes de la zone : bandes de temps, secteurs ou tableau |
| `glassauto/carte-zone` | Carte statique des communes, cercles 10/20/30 km |
| `glassauto/plan-acces` | Plan du quartier ; plan interactif OpenStreetMap chargé après clic |

Tous lisent Réglages › Général › GlassAuto Vernon ou `assets/data/`. Aucune valeur n'est écrite dans les pages.

### Jetons dans les textes

Dans un paragraphe, un titre, un bouton, un élément de liste ou une FAQ : `{{tel}}`, `{{tel-lien}}`, `{{email}}`, `{{adresse}}`, `{{rue}}`, `{{cp}}`, `{{ville}}`, `{{nom}}`, `{{annee}}`, `{{horaires-court}}`, `{{itineraire}}`, `{{reservation}}`, `{{partenaires}}`, `{{avis}}`. Remplacés au rendu, jamais stockés.

### Blocs G2RD du parent retenus

| Bloc | Usage | Motif |
| --- | --- | --- |
| `g2rd/breadcrumb` | Toutes les pages intérieures | Rendu PHP, JSON-LD |
| `g2rd/faq` (mode GEO) | Accueil, FAQ, pages prestation | details/summary natifs + JSON-LD FAQPage |
| `g2rd/testimonial` (mode Google) | Accueil | Avis Google authentiques via le parent |

Blocs écartés : Hero, CTA Band, Card, Icon Box (couleurs figées en hexadécimal dans leurs attributs : rendu moins fidèle aux maquettes). Les sections sont des blocs Groupe natifs portés par des classes `ga-*`.

## Pages

| Fichier | Titre | Adresse | Modèle |
| --- | --- | --- | --- |
| (patterns) | Accueil | `/` | Page libre |
| `prestations.html` | Prestations | `/prestations/` | Page prestation |
| `reparation-impact.html` | Réparation d'impact de pare-brise à Vernon | `/prestations/reparation-impact/` | Page prestation |
| `remplacement-pare-brise.html` | Remplacement de pare-brise à Vernon | `/prestations/remplacement-pare-brise/` | Page prestation |
| `vitres-laterales.html` | Remplacement de vitre latérale à Vernon | `/prestations/vitres-laterales/` | Page prestation |
| `lunette-arriere.html` | Remplacement de lunette arrière à Vernon | `/prestations/lunette-arriere/` | Page prestation |
| `vitrages-specifiques.html` | Vitrages spécifiques | `/prestations/vitrages-specifiques/` | Page prestation |
| `retroviseurs.html` | Remplacement de rétroviseur à Vernon | `/prestations/retroviseurs/` | Page prestation |
| `optiques-de-phares.html` | Optiques de phares | `/prestations/optiques-de-phares/` | Page prestation |
| `calibrage-adas.html` | Calibrage des caméras ADAS | `/prestations/calibrage-adas/` | Page prestation |
| `assurances-prise-en-charge.html` | Assurances et prise en charge | `/assurances-prise-en-charge/` | Par défaut |
| `notre-centre.html` | Notre centre | `/notre-centre/` | Par défaut |
| `zone-intervention.html` | Zone d'intervention | `/zone-intervention/` | Par défaut |
| `prendre-rendez-vous.html` | Prendre rendez-vous | `/prendre-rendez-vous/` | Par défaut · bloc `g2rd-bc/reservation` (réservation en ligne, extension Business Connect) + formulaire Fluent Forms replié pour les demandes à domicile |
| `contact.html` | Contact | `/contact/` | Par défaut (page-contact.html) |
| `faq.html` | Questions fréquentes | `/faq/` | Par défaut |
| — | Actualités (page des articles) | `/actualites/` | home.html |
| `espace-partenaires.html` | Espace partenaires | `/espace-partenaires/` | Texte explicatif + emplacement `ga-login-slot` où l'extension insère le formulaire de connexion |
| `mentions-legales.html`, `politique-de-confidentialite.html`, `politique-de-cookies.html`, `conditions-utilisation.html`, `accessibilite.html` | Pages légales | `/…/` | Par défaut |

Les pages prestation utilisent l'image mise en avant comme visuel du bandeau : la renseigner dans l'éditeur. Les pages légales contiennent des champs `[à compléter]` (raison sociale, hébergeur, durées) à renseigner avant publication.

## Informations à confirmer avant mise en ligne

Reprises de la fiche officielle GlassAuto, à valider par le centre : horaires du samedi (« Sur RDV / Fermé »), services (véhicule de courtoisie, convoyage, nettoyage, station de lavage, garantie nationale), calibrage ADAS à Vernon, conditions de l'intervention à domicile (confirmée par le centre le 24 octobre 2026 : annoncée sur la page Zone d'intervention et dans la FAQ, sans tarif ni condition), e-mail public, critères de réparation d'impact, temps de séchage. Communes retirées par défaut de la zone (autres centres GlassAuto) : Évreux, Louviers, Mantes-la-Ville et l'agglomération de Mantes.

## Développement

- Aucune compilation : CSS et JS sont servis tels quels. `assets/css/icons.css` est généré depuis `design-preview/_src/partials/icons.mjs`.
- Qualité PHP : `phpcs --standard=WordPress .` depuis le dossier du thème (exclure `assets/`).
- Une fois le projet livré, copier le thème dans `child-themes/` du dépôt `g2rd-theme` comme exemple de référence.
