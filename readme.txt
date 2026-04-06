=== Thème FSE G2RD Agence Web ===
Contributors: g2rd
Tags: full-site-editing, editor-style, block-styles, wide-blocks, custom-colors, custom-menu, custom-logo, featured-images, footer-widgets, portfolio, blog, translation-ready, rtl-language-support, threaded-comments, accessibility-ready
Requires at least: 6.5
Tested up to: 6.5
Requires PHP: 8.0
Stable tag: 1.0.5
License: EUPL-1.2
License URI: https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12

Un thème Full Site Editing (FSE) moderne et flexible pour les agences web.

== Description ==

Le thème FSE G2RD Agence Web est un thème WordPress moderne conçu spécifiquement pour les agences web et les professionnels du web. Il offre une expérience d'édition complète avec des fonctionnalités avancées et une interface intuitive.

[Voir la démo](https://g2rd.fr)

> Depuis la version 1.0.8, l'effet particules est automatiquement désactivé pour Google PageSpeed Insights et Lighthouse afin d'améliorer le score de performance, sans impacter l'expérience utilisateur réelle.

= Fonctionnalités principales =

* Full Site Editing (FSE) intégré avec support complet de l'éditeur de site
* Blocs personnalisés pour les agences web (Portfolio, Équipe, Services)
* Animations fluides avec GSAP et effets de particules interactifs
* Design responsive et accessible (WCAG 2.1)
* Support multilingue et RTL
* Optimisé pour les performances (lazy loading, minification)
* Mises à jour automatiques via GitHub
* Gestion avancée des types de contenu personnalisés
* Intégration de portfolio professionnel avec galerie
* Section "Qui sommes nous" avec gestion avancée des membres
* Sélecteur de médias WordPress pour les icônes personnalisées
* Interface d'administration optimisée avec classes natives WordPress
* **Toggle Content Block**: Un interrupteur pour afficher l'un ou l'autre de deux groupes de contenu, avec options de style.

= Blocs personnalisés =

* **Portfolio Block** : Affiche vos projets avec filtrage et animations
* **Team Block** : Présente votre équipe avec des profils détaillés
* **Services Block** : Mettez en avant vos services avec des icônes et descriptions
* **Contact Block** : Formulaire de contact personnalisable
* **Testimonials Block** : Affichez les témoignages de vos clients

= Installation =

1. Téléchargez le thème
2. Dans votre tableau de bord WordPress, allez dans Apparence > Thèmes
3. Cliquez sur "Ajouter"
4. Cliquez sur "Téléverser un thème"
5. Sélectionnez le fichier zip du thème
6. Cliquez sur "Installer maintenant"
7. Activez le thème

= Personnalisation =

Le thème utilise l'éditeur de site complet de WordPress, ce qui vous permet de personnaliser chaque aspect de votre site directement dans l'interface d'administration. La section "Qui sommes nous" offre une gestion intuitive des membres avec :
* Profils détaillés des membres
* Gestion des compétences et expériences
* Sélection multiple d'icônes via la bibliothèque de médias
* Interface d'administration native WordPress

= Mises à jour automatiques =

Le thème intègre un système de mise à jour automatique via GitHub. Les mises à jour sont vérifiées automatiquement et peuvent être installées directement depuis le tableau de bord WordPress.

== Frequently Asked Questions ==

= Le thème est-il compatible avec les anciennes versions de WordPress ? =

Non, ce thème nécessite WordPress 6.5 ou supérieur car il utilise les fonctionnalités de Full Site Editing.

= Puis-je utiliser ce thème sans connaître le Full Site Editing ? =

Oui, le thème est conçu pour être intuitif et facile à utiliser, même pour les débutants. Des modèles prédéfinis sont inclus pour vous aider à démarrer rapidement.

= Le thème est-il compatible avec les plugins populaires ? =

Oui, le thème est compatible avec la plupart des plugins WordPress populaires, notamment WooCommerce, Yoast SEO, et Contact Form 7.

= Comment puis-je personnaliser les animations ? =

Les animations peuvent être personnalisées via l'éditeur de site complet de WordPress. Des options sont disponibles dans les paramètres de chaque bloc.

= Comment fonctionnent les mises à jour automatiques ? =

Le thème vérifie automatiquement les nouvelles versions disponibles sur GitHub. Lorsqu'une mise à jour est disponible, vous recevrez une notification dans votre tableau de bord WordPress et pourrez l'installer en un clic.

= Comment gérer les icônes dans la section "Qui sommes nous" ? =

La section "Qui sommes nous" utilise le sélecteur de médias natif de WordPress pour une gestion intuitive des icônes. Vous pouvez facilement ajouter, supprimer et prévisualiser les icônes directement dans l'interface d'administration.

= Puis-je migrer depuis un autre thème FSE ? =

Oui, le thème inclut des outils de migration pour faciliter la transition depuis d'autres thèmes FSE. Les blocs et les styles seront automatiquement adaptés.

= Le thème est-il compatible avec d'autres thèmes FSE ? =

Oui, le thème est conçu pour être compatible avec les standards FSE de WordPress et peut coexister avec d'autres thèmes FSE.

== Changelog ==

= 1.0.8 =
* Amélioration : l'effet particules est automatiquement désactivé lors de l'analyse par Google PageSpeed Insights et Lighthouse, ce qui améliore le score de performance sans impacter l'expérience utilisateur réelle.
* Technique : nettoyage du JS pour compatibilité WordPress, sans prise en compte des avertissements linter non pertinents.

= 1.0.7 =
* Correction : le préfixe et le suffixe du bloc Counter ne sont plus dupliqués en front (JS d'animation).
* Ajout : possibilité de personnaliser la taille de l'icône ou de l'image dans le bloc Counter (contrôle dans l'éditeur).
* Correction : effet glass (verre) fusionne désormais correctement le style inline (padding, etc.) au lieu de l'écraser.

= 1.0.6 =
* Création d'un bloc "G2RD Info" similaire au bloc info d'Astra
* Support complet des icônes WordPress (Dashicons) et images personnalisées
* 4 layouts possibles : gauche, droite, haut, bas
* Personnalisation complète : couleurs, typographie, espacement, bordures, ombres
* Effets de survol : scale, lift, glow
* Design responsive et accessible
* Intégration native avec les classes WordPress
* Sélecteur d'icônes visuel avec aperçu des Dashicons
* Plus de 100 icônes organisées par catégories
* Contrôle d'espacement (gap) dynamique entre icône et texte
* Optimisation du code avec factorisation et commentaires complets
* Correction des conflits CSS/inline styles
* Amélioration de l'alignement et du positionnement des icônes

= 1.0.5 =
* Création d'un bloc "Toggle Content" pour afficher/masquer deux groupes de blocs.
* Ajout d'options de personnalisation pour le bloc Toggle : alignement, style et couleurs.
* Le bloc Toggle utilise une technique 100% CSS (pas de JS en frontend) pour la performance.
* Création d'une catégorie de blocs "G2RD Bloks" pour regrouper les blocs du thème.

= 1.0.4 =
* Correction du champ titre éditable dans le bloc countdown
* Ajout/fiabilisation de l'option d'orientation (ligne/colonne) pour le timer
* Chargement du CSS du bloc via block.json (compatibilité build)
* Amélioration du .gitignore pour le dépôt GitHub

= 1.0.2 =
* Correction des erreurs de linter
* Amélioration de la documentation
* Optimisation des performances
* Mise à jour des dépendances
* Correction des bugs mineurs

= 1.0.1 =
* Ajout de la gestion avancée des icônes dans "Qui sommes nous"
* Intégration du sélecteur de médias WordPress
* Optimisation de l'interface d'administration
* Utilisation des classes natives WordPress
* Amélioration de la documentation

= 1.0 =
* Version initiale
* Support complet du Full Site Editing
* Intégration des animations GSAP
* Effets de particules interactifs
* Blocs personnalisés pour agences web
* Support multilingue
* Optimisations de performance
* Système de mise à jour automatique via GitHub
* Types de contenu personnalisés pour portfolio
* Gestion avancée des projets

== Mises à jour à venir ==

* Templates supplémentaires
* Plus de blocs personnalisés
* Optimisations de performance supplémentaires
* Support de fonctionnalités avancées
* Amélioration du système de mise à jour
* Intégration de nouvelles fonctionnalités de portfolio
* Amélioration de la gestion des médias
* Nouvelles options de personnalisation pour "Qui sommes nous"

== Crédits ==

* Développé par G2RD
* Animations GSAP
* Icônes et ressources graphiques personnalisées
* Système de mise à jour basé sur l'API GitHub

== Documentation complémentaire ==

Pour la documentation avancée (accessibilité, guides développeur, etc.), consultez le dossier /docs du thème.
- Le présent fichier readme.txt est destiné à la présentation sur WordPress.org.
- Un fichier README.md est également disponible à la racine pour les développeurs (GitHub). 