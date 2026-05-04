# Blocs Gutenberg G2RD

Le thème inclut **34 blocs custom** regroupés sous la catégorie `g2rd-blocks` dans l'éditeur Gutenberg.

---

## Blocs de mise en page

### `g2rd/container`
Bloc conteneur responsive avec 4 modes de layout : **Flex**, **Grille**, **Contraint** et **Flux**. Gère espacement, fond, bordure et animation par appareil. Remplace avantageusement le bloc core/group pour les mises en page avancées.

**Attributs clés** : `layout` (flex/grid/constrained/flow), `gap`, `padding`, `backgroundColor`, `backgroundImage`

---

### `g2rd/slider`
Slider multi-contenu : ajoutez des groupes de blocs (images, textes, CTA) comme slides. Configure transitions, pagination et flèches de navigation.

**Attributs clés** : `autoplay`, `speed`, `pagination`, `navigation`, `effect`

---

### `g2rd/sliding-panel`
Panneau coulissant ou popup déclenché par un clic sur bouton, texte ou image. Idéal pour menus mobiles, méga-menus, infobulles, popovers. Position configurable : gauche, droite, haut, bas.

**Attributs clés** : `position`, `overlay`, `triggerType`, `width`

---

### `g2rd/toggle-content`
Affiche alternativement l'un de deux groupes de contenu via un interrupteur. Utile pour afficher des variantes (ex: tarifs mensuel/annuel).

---

## Blocs de contenu

### `g2rd/hero`
Section d'en-tête principale avec accroche, titre, sous-titre et CTA. Supporte les images de fond, la hauteur minimale configurable et la preuve sociale.

**Attributs clés** : `kicker`, `heading`, `subheading`, `ctaText`, `ctaUrl`, `minHeight`, `backgroundImage`, `textAlign`

---

### `g2rd/advanced-heading`
Titre enrichi avec accroche pré-titre, sous-titre, dégradé de couleur sur le texte et animation d'entrée. Remplace core/heading pour les titres de sections importantes.

---

### `g2rd/card`
Carte de contenu polyvalente avec image, titre, texte et CTA. Supporte plusieurs dispositions et styles visuels.

---

### `g2rd/icon-box`
Affiche une icône (bibliothèque intégrée, image ou SVG custom) avec titre et texte. Deux styles : standard et enveloppé. Conteneur entier cliquable possible.

**Attributs clés** : `iconType`, `iconSrc`, `title`, `text`, `linkUrl`, `iconColor`, `backgroundColor`

---

### `g2rd/info`
Bloc d'information contextuelle avec icône, titre et texte. Utile pour les notices, tips et encadrés explicatifs.

---

### `g2rd/toolbars`
Bloc d'alerte avec plusieurs styles visuels prédéfinis (info, succès, attention, erreur).

---

### `g2rd/advanced-list`
Liste avec icônes personnalisables par ligne (bibliothèque, image ou SVG). Options de couleur et d'espacement par item et pour l'ensemble. Idéal pour listes de services, avantages, menus.

---

### `g2rd/code`
Bloc de code avec coloration syntaxique côté serveur via `highlight.php`. Supporte de nombreux langages de programmation.

---

## Blocs interactifs

### `g2rd/carousel`
Carrousel d'images propulsé par Swiper.js. Modes de défilement multiples (coverflow, fade, cube), autoplay, pagination, navigation.

**Attributs clés** : `images[]`, `autoplayDelay`, `pagination`, `navigation`, `effect`, `coverflowSettings`

---

### `g2rd/countdown`
Compte à rebours jusqu'à une date cible avec affichage jours/heures/minutes/secondes. Typographie, couleurs et espacements entièrement configurables.

**Attributs clés** : `targetDate`, `labelDays`, `labelHours`, `labelMinutes`, `labelSeconds`

---

### `g2rd/counter`
Compteur animé qui s'incrémente jusqu'à une valeur cible au scroll. Idéal pour les sections "chiffres clés".

---

### `g2rd/typed`
Animation de texte dactylographié en boucle. Affiche plusieurs phrases successives avec effet machine à écrire.

---

### `g2rd/marquee`
Texte défilant en boucle infinie. Configurable en vitesse et direction.

---

### `g2rd/modal`
Fenêtre modale ouverte au clic sur un déclencheur (bouton, texte ou image). Contenu libre en InnerBlocks.

---

### `g2rd/progress-bar`
Barre de progression linéaire ou circulaire. Valeur fixe ou dynamique depuis un champ de métadonnées (contexte Requête). Utile pour notes, pourcentages de compétences, progression.

---

## Blocs commerciaux

### `g2rd/pricing-table`
Tableau de tarification avec colonnes, fonctionnalités, prix et CTA. Mise en avant d'un plan possible.

---

### `g2rd/cta-band`
Bandeau d'appel à l'action pleine largeur avec titre, texte et un ou deux boutons.

---

### `g2rd/testimonial`
Bloc de témoignages clients avec avatar, nom, poste, note et texte. Lien vers Google Business intégré.

---

### `g2rd/filterable-grid`
Grille filtrée dynamiquement par catégorie. Compatible WooCommerce, SureCart et CPTs custom.

---

## Blocs techniques

### `g2rd/map`
Carte Google Maps (avec clé API) ou OpenStreetMap (sans clé). Marqueurs illimités avec titre, description et icône custom.

**Attributs clés** : `provider`, `apiKey`, `zoom`, `markers[]`, `height`

---

### `g2rd/block-api`
Connecte un bloc à une API externe. Supporte les connecteurs côté serveur (proxy WP) et côté client (fetch JS), avec chargement Ajax, mode chat et streaming.

---

### `g2rd/dynamic-content`
Affiche des données dynamiques depuis les métadonnées du post ou d'une API. À utiliser dans un contexte de requête (Query Loop).

---

### `g2rd/charts`
Graphiques interactifs (barres, courbes, secteurs, etc.) avec données configurables dans l'éditeur.

---

### `g2rd/share-buttons`
Boutons de partage social (Facebook, X/Twitter, LinkedIn, etc.). Styles : arrondi, icônes simples, boutons avec étiquettes. Affichage horizontal ou vertical.

---

### `g2rd/breadcrumb`
Fil d'Ariane avec microdonnées Schema.org. Améliore le SEO et la navigation.

---

### `g2rd/device-mockup`
Enveloppe une image ou vidéo dans un cadre réaliste généré en CSS : smartphone, tablette ou ordinateur.

---

### `g2rd/table-of-contents`
Sommaire interactif généré automatiquement depuis les titres du contenu.

---

## Blocs GEO (Generative Engine Optimization)

### `g2rd/geo-summary`
Résumé IA structuré avec microdonnées schema.org Article. Améliore la visibilité dans les moteurs génératifs (ChatGPT, Perplexity, Gemini).

---

### `g2rd/geo-faq`
FAQ accordéon en CSS avec JSON-LD FAQPage. Double bénéfice : expérience utilisateur + référencement IA.

**Attributs clés** : `items[]`, `optimizeForGEO`, `iconType`

---

### `g2rd/bases`
Bloc de base interne au thème. Usage avancé uniquement.

---

## Résumé par catégorie

| Catégorie | Blocs |
|-----------|-------|
| Mise en page | container, slider, sliding-panel, toggle-content |
| Contenu | hero, advanced-heading, card, icon-box, info, toolbars, advanced-list, code |
| Interactif | carousel, countdown, counter, typed, marquee, modal, progress-bar |
| Commercial | pricing-table, cta-band, testimonial, filterable-grid |
| Technique | map, block-api, dynamic-content, charts, share-buttons, breadcrumb, device-mockup, table-of-contents |
| GEO | geo-summary, geo-faq, bases |
