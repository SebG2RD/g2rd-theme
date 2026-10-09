# Refonte g2rd.fr — Phase 3 (Gutenberg) — Guide d'intégration

## Installation
1. Déposer `g2rd.json` à la **racine du thème parent** `g2rd-theme/` (seul ajout au parent, aucun fichier modifié).
2. Installer le dossier `g2rd-child/` dans `wp-content/themes/` et **activer « G2RD Child »**.
3. Réglages > Lecture : vérifier que la page d'accueil statique est assignée (template `front-page.html` pris en charge automatiquement).
4. Éditeur de site : assigner le menu existant au bloc Navigation du header, vérifier le logo (bloc Logo du site).

## Architecture
- `g2rd.json` (parent) → lu par `functions.php` (cache transient + filemtime, repli complet si absent) → custom properties `--g2rd-*`.
- CSS : `global` (base/header/footer), `pages` (pont Gutenberg + pages internes : hero, services, projets, équipe, valeurs, contact, pagination, 404), `home` (accueil + composants partagés : dossiers, FAQ, CTA, stats, process), `magic-bento`, `animations`.
- JS : `decor.js` (chargé en premier — injecte TOUT le décor aria-hidden : scène et panneau du hero, visuels bento, piste du processus, bouton « Désactiver les animations » ; règle du projet : patterns et parts ne contiennent QUE des blocs natifs ou g2rd, aucun bloc HTML), `global.js` (header, lettres des boutons, marquee, pilotage du bouton animations, gel du bloc advanced-heading), `animations.js` (GSAP/ScrollTrigger, dépendances sur les handles `gsap`/`scrolltrigger` du parent — jamais rechargés), `magic-bento.js` (halo/liseré/tilt/magnétisme/stagger, injection du halo et des flèches).
- 13 patterns (`g2rd-child/*`, catégorie « G2RD — Refonte », dont `contact-infos`), 15 templates, parts `header`/`footer`.
- Accueil : le portfolio est une pile de « dossiers » (`.g2rd-case`, pattern `portfolio`) — au scroll desktop chaque dossier se fige sous le header, le suivant glisse par-dessus et le précédent disparaît complètement (opacity 0, validé maquette v3.0.2) ; numéros PRJ.NN et numéros fantômes générés par compteur CSS sur la Query Loop. Mobile/reduced-motion/bouton animations : flux vertical natif.
- Bindings du parent réutilisés : `g2rd/portfolio-link` (bouton « Visiter le site », single + archive portfolio, contexte postId en Query Loop) et `g2rd/current-year` (copyright footer).
- H1 du hero : bloc parent `g2rd/advanced-heading` (statique + rotation du dernier mot). Si l'éditeur affiche « Tenter la récupération » à la première ouverture du pattern, un clic régénère le markup depuis les attributs.

## Actions manuelles restantes
- Remplacer le bloc HTML d'attente de la section WP Manager (pattern `wp-manager`) par un bloc Image avec une **capture réelle**.
- Renseigner de **vrais témoignages** dans le pattern `testimonials` (placeholders explicites, aucun faux avis) ou brancher la source Google Reviews du parent.
- SEOPress : vérifier titles/metas conservés, activer les schémas `LocalBusiness`/`FAQPage` sans doublon avec le contenu.
- Optionnel : passer GSAP en chargement local plutôt que CDN (décision côté parent).

## Contrôles avant mise en ligne (brief §49)
- H1 unique par page, maillage des prestations intact (header/footer/éditorial), URLs inchangées.
- Lighthouse ≥ 90/95/95/95, LCP < 2,5 s (précharger l'image LCP si besoin).
- RGAA : navigation clavier, `prefers-reduced-motion`, bouton « Désactiver les animations » (footer), contrastes validés en Phase 2.
- Tester : 375/480/768/1024/1280/1440/1920 px, souris/trackpad/tactile/clavier, éditeur + front.

## Journal v1.1 (conversion des maquettes validées v3.0.2)
- **Règle bloquante appliquée : blocs natifs ou blocs du thème g2rd uniquement.** Les 22 blocs HTML décoratifs (scène/panneau du hero, 6 visuels bento, liseré, piste du processus, pastilles des dossiers, placeholder WP Manager, bouton animations) ont été supprimés des patterns/parts et sont injectés par `decor.js` (ou générés en CSS pour les pastilles). Les groupes `g2rd-bvis--*` restent des blocs Groupe natifs vides remplis côté client.
- Accueil : pattern `portfolio` réécrit en pile de dossiers (Query Loop CPT portfolio, 6 posts) + pin GSAP ajouté à `animations.js` (fondu final à 0). `home.css` remplacé par la version maquette + ponts Gutenberg (compteurs PRJ, tags post-terms, cadre image mise en avant).
- `animations.js` : séquence d'entrée des heros de pages intérieures (`.g2rd-page-hero`), tag `data-g2rd-tilt` posé en JS sur les cadres de dossiers et la capture WP Manager (les blocs natifs ne portent pas de data-attributes).
- Templates réécrits selon maquettes : `archive-prestations` (éditorial + grille de services + « par où commencer »), `archive-portfolio` (grille de projets), `single-prestations` (lead + CTA dans le hero), `archive-qui-sommes-nous` (cartes membres avec rôle `categories-qui-sommes-nous` + valeurs), `page-contact` (grille formulaire/infos + FAQ, formulaire dans le contenu de la page), `404` (grand code en dégradé).
- Nouveau pattern `contact-infos` ; `pages.css` complété (services, checklist, projets, équipe, valeurs, contact, pagination Query Loop, 404, styles de formulaire couvrant CF7/Fluent Forms/WPForms via `.g2rd-contact-form`).
- `functions.php` : enqueue de `decor.js` en tête, déclaré en dépendance de `global.js`, `magic-bento.js` et `animations.js`.

## Journal v1.2 (pages Gutenberg 1:1 avec la maquette)
- Contenus des pages livrés en **sections pleines** (`alignfull` + `g2rd-section g2rd-theme-*`, conteneurs `g2rd-wide`/`g2rd-content`, `section-head`) identiques à la maquette v3.0.2 : voir l'archive g2rd-contenus-pages.
- `single-prestations.html` : wrapper `g2rd-single-sections` (aucun espacement propre, chaque section gère le sien) ; le pattern « process » est appelé DANS le contenu pour respecter l'ordre maquette (inclus > méthode > FAQ) ; CTA final par le template.
- Nouveau template `page-g2rd-wp-manager.html` (assigné par slug) : hero deux colonnes, panneau « état du parc » injecté par decor.js dans `.g2rd-page-hero__grid--wpm` (données maquette : 12 sites, 4 MAJ, 2 CVE), contenu en sections pleines, CTA final.
- `functions.php` : `home.css` chargé aussi sur la page g2rd-wp-manager (panneau + section « plateforme en action »).
- `pages.css` : `.g2rd-single-sections` et `.g2rd-page-hero__grid`.

## Journal v1.4 (corrections SEO accueil)
- Mots du H1 repris dans le corps : premier paragraphe de l'éditorial reformulé (« agence web WordPress sur mesure », « sites performants, rapides, sécurisés et bien référencés »).
- Logos header/footer : filtre `render_block_core/site-logo` — alt forcé et `aria-label` sur le lien si l'alt du média est vide (corrige « 2 images sans alt » et « 2 liens sans ancre »). Renseigner tout de même l'alt du logo en médiathèque.
- Ratio titres/texte : 35 → 26 titres rendus. Intitulés de colonnes du footer et titres d'étapes du processus convertis en paragraphes stylés (`.g2rd-footer__title`, `.g2rd-process__title`, sélecteurs CSS doublés).
- Ancres dédupliquées : CTA « Obtenir mon devis gratuit », WP Manager « Explorer la plateforme WP Manager », footer « Automatisation digitale », éditorial « Design d'interfaces web » / « Se former à WordPress » / « formation à l'utilisation de WordPress » ; liens « more » des extraits retirés sur les Query Loops de l'accueil (portfolio, blog) — le titre reste l'unique lien, à ancre descriptive.

## Journal v1.4.1 (header corrigé — bloc Navigation natif)
- `parts/header.html` remplacé par la version corrigée : bloc Navigation avec le menu du site (`ref:1194480`), couleurs blanches, icône menu. NB : le `ref` pointe l'ID du menu sur l'installation en production ; sur un autre environnement, ré-assigner le menu dans l'éditeur de site.
- `global.css` : pont navigation réécrit pour le bloc natif — bouton d'ouverture/fermeture (44px min, icônes 26px), overlay mobile plein écran encre avec liens centrés 1.4rem, soulignement accent au survol et sur l'élément courant. Ancien pont du menu custom (`.g2rd-burger`, `.g2rd-nav.is-open`) retiré (il masquait le menu mobile du bloc).
- Option : ajouter la classe CSS `g2rd-nav__cta` à l'élément « Contact » dans l'éditeur du menu pour retrouver la pastille accent de la maquette.

## Journal v1.4.2 (correctif burger header)
- Bug : le bouton du menu restait visible en desktop — la règle de pont (`display: inline-flex`) avait la même spécificité que la règle cœur qui le masque ≥ 600px, et le CSS enfant charge après. Correctif : plus de `display` sur la règle de base ; verrou explicite `display: none` ≥ 900px.
- Point de bascule aligné sur la maquette : burger < 900px (le cœur gère < 600px, une media query étend le comportement mobile à la tranche 600-899px), menu en ligne ≥ 900px.
- Bouton de fermeture stylé uniquement dans l'overlay ouvert (`.has-modal-open`) pour éviter toute fuite en desktop.

## Journal v1.4.3 (styles manquants sur les pages intérieures)
- Bug : sur les fiches prestations (et toute page intérieure utilisant les patterns process / cta / éditorial), les sections s'affichaient sans styles au front alors que l'éditeur était correct — `home.css` n'était chargée que sur l'accueil et la page WP Manager, or elle contient ces composants partagés.
- Correctif : `home.css` chargée sur tout le site (functions.php). Aucun autre fichier modifié.
- Fichiers à redéployer : `functions.php`, `INTEGRATION.md` uniquement.

## Journal v1.4.4 (coordonnées téléphone + WhatsApp)
- Footer (bloc marque) : ajout du téléphone `tel:+33662510095` (06 62 51 00 95), du lien WhatsApp `https://wa.me/33662510095` et rappel de l'email — visibles sur toutes les pages.
- Pattern `contact-infos` (carte « G2RD Agence Web » de la page contact) : téléphone cliquable + « Écrire sur WhatsApp » (ancre distincte de celle du footer pour éviter les doublons SEO sur la page contact).
- Fichiers à redéployer : `parts/footer.html`, `patterns/contact-infos.php`, `INTEGRATION.md`.

## Journal v1.4.5 (section « Nous trouver » — page contact)
- Nouvelle section entre la grille formulaire/infos et la FAQ : carte de coordonnées complète (adresse 8 rue Maurice Maire 27600 Gaillon, téléphone, WhatsApp « Discuter sur WhatsApp », email, disponibilités) + emplacement `g2rd-map-slot` pour le bloc carte du thème parent.
- À FAIRE dans l'éditeur : ouvrir le template page-contact (Apparence > Éditeur > Modèles), cliquer l'emplacement, insérer le bloc carte G2RD centré sur Gaillon, supprimer le paragraphe repère. Le CSS dimensionne automatiquement le bloc inséré (hauteur mini 320px, coins arrondis, iframe pleine surface).
- Ancres WhatsApp différenciées sur la page contact (footer « WhatsApp », aside « Écrire sur WhatsApp », carte « Discuter sur WhatsApp ») — aucune ancre dupliquée.
- Fichiers à redéployer : `templates/page-contact.html`, `assets/css/pages.css`, `INTEGRATION.md`.

## Journal v1.4.6 (bouton « Espace client » dans le header)
- Header : bouton natif « Espace client » vers /customer-dashboard/ (FluentCart affiche la connexion si non connecté, le tableau de bord sinon). Groupé avec la navigation dans `.g2rd-header__right` pour préserver l'alignement logo/menu ; visible aussi en mobile à côté du burger (taille compacte < 600px). Style pastille contour accent, remplie au survol.
- Fichiers à redéployer : `parts/header.html`, `assets/css/global.css`, `INTEGRATION.md`.

## Journal v1.5 (avis Google, fiches prestations enrichies, accessibilité)
- **Témoignages (accueil)** : pattern `testimonials` réécrit autour du bloc parent `g2rd/testimonial` en mode Google Reviews (grille 3 colonnes, 6 avis max, note ≥ 4, style `bordered`, lien vers la fiche Google, texte tronqué à 280 caractères). Place ID de la fiche G2RD renseigné dans le bloc (`googlePlaceId` = `ChIJ9eDEcQUX5kcRMyRHf4nmF3A`) et dans le lien « Laissez un avis sur Google » du pattern. La clé API Google Maps est celle des options du thème parent. `home.css` §9 réécrit : en-tête note Google en pastille, cartes dans la charte (guillemet accent, étoiles vert profond, auteur + date en mono), squelettes de chargement, section masquée si le bloc est vide. Les anciennes règles `.g2rd-testimonial` génériques sont scopées hors du bloc.
- **Fiches prestations (contenu uniquement, template inchangé)** : les 9 fichiers `contenus-pages/prestation--*.html` sont régénérés avec 7 sections pleine largeur : Comprendre (éditorial + bloc `g2rd/geo-summary` en carte encre) › Ce que comprend (checklist, fond surface) › Tarifs (`g2rd/pricing-table` alimenté par les produits FluentCart : maintenance 50/100/150 € HT, hébergement 20 €, thème 49 €/an, publication réseaux sociaux 200 €) ou Repères (`.g2rd-facts`) › Méthode (`g2rd/timeline` sur fond encre, livrable par étape) › Pour qui (3 profils) › FAQ (`g2rd/faq` mode GEO, schéma FAQPage généré par le bloc) › Aller plus loin (3 prestations liées). Mots-clés cibles d'après Semrush FR (volumes en tête de chaque fichier), H1 proposés, extraits et métas SEOPress réécrits.
- **SEOPress** : désactiver le schéma FAQ automatique sur les fiches prestations (le bloc FAQ émet déjà le JSON-LD FAQPage) pour éviter le doublon.
- `pages.css` : ponts `.g2rd-svc-intro*`, `.g2rd-svc-pricing__table` (cartes dans la charte, forfait mis en avant sur fond encre), `.g2rd-facts`, `.g2rd-svc-method` / `.g2rd-svc-timeline` (variables du bloc timeline surchargées), `.g2rd-audience*`, `.g2rd-faq-block` (variables du bloc FAQ), `.g2rd-related__list`.
- `home.css` §11 : les règles FAQ de l'accueil sont scopées `.g2rd-faq:not(.wp-block-g2rd-faq)` — le bloc parent porte lui aussi la classe `g2rd-faq`.
- **Accessibilité** : audit axe-core 4.10 du 06/09/2026 sur 5 pages (accueil, prestations, fiche maintenance, contact, réalisations). Correctifs `global.css` : couleur des liens de texte (le style parent applique #e11d48 : 4,29:1 sur encre, 4,48:1 sur clair) → vert profond sur clair / dim sur encre ; bouton d'envoi Fluent Forms (texte blanc sur accent, 1,44:1) → encre sur accent ; bouton « Désactiver les animations » déplacé à droite (il chevauchait la barre d'accessibilité du parent et le bouton SEOPress de consentement) ; repli `.screen-reader-text`. Nouveau contenu `contenus-pages/page--declaration-daccessibilite.html` (statut, résultats, non-conformités corrigées / en cours, dérogations, technologies, contact, recours).
- **Restent côté thème parent / extensions** (listés dans la déclaration) : liens d'image sans intitulé dans les Query Loops (featured image cliquable), repères banner/contentinfo doublés par les wrappers `wp-block-template-part`, boutons « Réduire/Agrandir le texte » de la barre d'accessibilité sans nom accessible, `tabindex` positifs et niveau de titre sur le formulaire Fluent Forms, carte Leaflet focusable sous `aria-hidden`, alt de vignettes portfolio remplis avec des mots-clés.
- Fichiers à redéployer : `patterns/testimonials.php`, `assets/css/global.css`, `assets/css/home.css`, `assets/css/pages.css`, `INTEGRATION.md` + les contenus `contenus-pages/`.

## Journal v1.5.1 (menu mobile blanc sur blanc)
- Bug : sur mobile, l'overlay du bloc Navigation s'affichait en blanc avec les liens blancs. Cause : depuis WP 6.5, le cœur applique `.wp-block-navigation:not(.has-background) .wp-block-navigation__responsive-container.is-menu-open:not(.disable-default-overlay) { background-color: #fff }` (spécificité 0,5,0), plus forte que le pont enfant `.g2rd-nav …has-modal-open` (0,3,0).
- Correctif `global.css` : sélecteur aligné sur celui du cœur (`.wp-block-navigation.g2rd-nav:not(.has-background) …`, 0,6,0) → fond encre, testé sur le site en production à 375 px.
- Overlay mobile remis d'aplomb dans la foulée : liens centrés (le cœur traduisait `items-justified-right` en `align-items: flex-end`), contenu en `place-content: start center` avec un dégagement haut pour la croix (4,75 rem) et bas pour la barre d'accessibilité et le bouton de consentement (6,5 rem). Sous-menu « Prestations » : le cœur l'affiche toujours déplié dans l'overlay, l'intitulé parent devient une étiquette mono accent, le bouton de bascule est masqué, les 9 enfants sont resserrés (1,1 rem) et fermés par un filet. Testé en injection sur le site en production à 375 px.
- Fichiers à redéployer : `assets/css/global.css`, `INTEGRATION.md`.
