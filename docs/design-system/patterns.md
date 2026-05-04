# Patterns

Les patterns sont des compositions de blocs prêtes à l'emploi, accessibles via l'onglet **Patterns** de l'éditeur Gutenberg. Ils permettent de construire des pages rapidement avec le design G2RD.

---

## Sections hero

### Section Hero
**Fichier** : `section-hero.php`
Section d'en-tête classique avec accroche, titre principal, sous-titre et bouton CTA. Fond image ou couleur configurable. Idéal pour les pages d'accueil et landing pages.

### Section Hero Animé
**Fichier** : `section-hero-animated.php`
Version animée du hero avec effet de texte dactylographié (bloc `g2rd/typed`) et animations d'entrée GSAP. Pour les sites qui veulent un impact visuel fort.

---

## Sections de contenu

### Section Services
**Fichier** : `section-services.php`
Grille de cartes de services avec icônes, titres et descriptions. Version core WordPress (images et texte).

### Section Services G2RD
**Fichier** : `section-services-g2rd.php`
Version optimisée de la section services utilisant les blocs `g2rd/icon-box`. Icônes de la bibliothèque intégrée, mise en page fluide.

### Section FAQ
**Fichier** : `section-faq.php`
Section de questions fréquentes avec accordéon CSS. Structure sémantique HTML5 propre.

### Section FAQ + Résumé GEO
**Fichier** : `section-faq-geo.php`
Combinaison du bloc `g2rd/geo-faq` (JSON-LD FAQPage) et `g2rd/geo-summary`. Double optimisation pour les moteurs de recherche traditionnels et génératifs.

### Section Témoignages
**Fichier** : `section-temoignages.php`
Grille de témoignages clients avec avatars, noms, postes et citations. Version statique.

### Section Témoignages Carousel
**Fichier** : `section-temoignages-carousel.php`
Version carrousel des témoignages utilisant `g2rd/testimonial` + `g2rd/carousel`. Défilement automatique, lien Google Business.

---

## Sections CTA

### Section CTA
**Fichier** : `section-cta.php`
Bandeau d'appel à l'action pleine largeur avec titre, sous-titre et bouton. Fond coloré avec les couleurs du thème.

### Section CTA Urgence
**Fichier** : `section-cta-countdown.php`
Bandeau CTA avec compte à rebours intégré (`g2rd/countdown`). Pour les offres limitées dans le temps, lancements, promotions.

---

## Sections Magic Page

Les sections Magic utilisent des styles visuels distinctifs avec effets de profondeur et ombres dramatiques.

### Section Magic — Sombre
**Fichier** : `section-magic-dark.php`
Section sur fond sombre dégradé (`magic-dark`) avec cartes en verre. Chargement conditionnel de `assets/css/magic-page.css` via le style de bloc `magic-dark`.

### Section Magic — Claire
**Fichier** : `section-magic-light.php`
Version claire de la Magic Page sur fond crème dégradé (`magic-light`). Cartes avec ombres `magic` et `magic-xl`.

---

## Cartes

### Card G2RD
**Fichier** : `card-G2RD.php`
Carte standard G2RD avec image, titre, texte et bouton CTA. Bloc de base pour présenter un service, produit ou article.

### Card Box
**Fichier** : `card-box.php`
Carte encadrée avec fond coloré et bordure. Pour les informations mises en valeur.

### Card Call to Action G2RD
**Fichier** : `card-call-to-action-G2RD.php`
Carte dédiée à la conversion : grande icône, titre accrocheur, texte court et bouton proéminent.

### Card Contact
**Fichier** : `card-contact.php`
Carte d'information de contact avec icônes pour téléphone, email et adresse.

### Card Details
**Fichier** : `card-details.php`
Carte détaillée avec liste de caractéristiques. Idéale pour présenter des offres ou des spécifications.

### Card Info Box
**Fichier** : `card-info-box.php`
Encadré informatif avec icône et texte court. Pour les notices, conseils ou points clés.

### Card Presentation
**Fichier** : `card-presentation.php`
Carte de présentation d'un membre d'équipe ou d'un intervenant avec photo, nom, poste et bio courte.

---

## Grilles et listes

### Post Grid
**Fichier** : `post-grid.php`
Grille d'articles de blog avec image, titre, date et extrait. Utilise le bloc core/query pour les données dynamiques.

### Articles Blog
**Fichier** : `articles-blog.php`
Mise en page alternative pour la liste d'articles, avec accent visuel plus fort sur les images.

### Grid Portfolio
**Fichier** : `grid-portfolio.php`
Grille du CPT Portfolio avec filtrage par catégorie. Affiche les projets avec image et titre.

### Query Portfolio
**Fichier** : `query-portfolio.php`
Variante de la grille portfolio avec une mise en page masonry.

### Banner Sociaux
**Fichier** : `banner-sociaux.php`
Bandeau de liens vers les réseaux sociaux avec icônes. À placer en footer ou en section dédiée.

---

## Templates de pages complètes

Ces patterns remplissent une page entière. À utiliser comme point de départ pour un site.

### Template Agence / Studio
**Fichier** : `template-agence.php`
Page d'accueil complète pour une agence web ou un studio créatif. Inclut : hero animé, services, portfolio, témoignages, CTA.

### Template Artisan / Prestataire local
**Fichier** : `template-artisan.php`
Page d'accueil pour un artisan ou prestataire local. Inclut : hero avec zone géographique, services, galerie, avis, contact.

### Template E-commerce
**Fichier** : `template-ecommerce.php`
Page d'accueil pour une boutique en ligne. Inclut : hero produit, grille de produits filtrée, témoignages, CTA newsletter.

### Template VTC / Taxi
**Fichier** : `template-vtc.php`
Page d'accueil spécialisée pour les chauffeurs VTC et taxis. Inclut : hero avec formulaire de réservation, zones desservies, tarifs, avis.

---

## Récapitulatif par catégorie

| Catégorie | Patterns |
|-----------|---------|
| Hero | Section Hero, Section Hero Animé |
| Contenu | Services, Services G2RD, FAQ, FAQ GEO, Témoignages, Témoignages Carousel |
| CTA | Section CTA, Section CTA Urgence |
| Magic Page | Magic Sombre, Magic Claire |
| Cartes | Card G2RD, Card Box, Card CTA, Card Contact, Card Details, Card Info Box, Card Presentation |
| Grilles | Post Grid, Articles Blog, Grid Portfolio, Query Portfolio, Banner Sociaux |
| Templates | Agence, Artisan, E-commerce, VTC |
