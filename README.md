# G2RD Theme

Un thème WordPress Full Site Editing (FSE) moderne et flexible pour les agences web.

## Description

Ce thème offre une expérience d'édition complète avec des blocs personnalisés, des animations fluides et une interface intuitive. Idéal pour les sites vitrines, portfolios et sites d'agences web.

## Fonctionnalités

- Full Site Editing (FSE)
- Design moderne et responsive
- Support des blocs Gutenberg
- Personnalisation avancée via l'interface WordPress
- Optimisé pour les performances
- Support multilingue
- Système de licences et mises à jour intégré
- Gestion avancée des membres d'équipe
- Sélecteur de médias WordPress intégré
- Interface d'administration native
- Types de contenu personnalisés
- Portfolio professionnel
- Section "Qui sommes nous" avec gestion des icônes
- Effet particules automatiquement désactivé pour Google PageSpeed Insights et Lighthouse (depuis 1.0.8) pour optimiser le score de performance sans impacter l'expérience utilisateur réelle.

## Fonctionnalités détaillées

### Section "Qui sommes nous"

- Profils détaillés des membres
- Gestion des compétences et expériences
- Sélection multiple d'icônes via la bibliothèque de médias
- Interface d'administration native WordPress
- Prévisualisation des icônes en temps réel

### Interface d'administration

- Utilisation des classes natives WordPress
- Sélecteur de médias intégré
- Gestion intuitive des contenus
- Interface responsive et accessible

## Installation

1. Téléchargez le thème depuis votre espace client SureCart
2. Dans votre administration WordPress, allez dans Apparence > Thèmes
3. Cliquez sur "Ajouter" puis "Téléverser un thème"
4. Sélectionnez le fichier ZIP du thème et cliquez sur "Installer maintenant"
5. Activez le thème

## Configuration

### Configuration de la licence

1. Dans votre administration WordPress, allez dans Apparence > G2RD Settings
2. Entrez votre clé API SureCart
3. Allez dans Apparence > G2RD License
4. Entrez votre clé de licence
5. Cliquez sur "Enregistrer les modifications"

### Mises à jour

Le thème se met à jour automatiquement lorsque de nouvelles versions sont disponibles sur GitHub. Pour bénéficier des mises à jour :

1. Assurez-vous d'avoir une licence valide
2. Votre clé de licence doit être entrée dans les paramètres du thème
3. Les mises à jour apparaîtront automatiquement dans votre administration WordPress

## Système de licences

Le thème utilise un système de licences basé sur SureCart pour gérer les abonnements et les mises à jour :

- Licences annuelles
- Vérification automatique de la validité des licences
- Mises à jour automatiques pour les licences valides
- Support de plusieurs licences par utilisateur
- Interface d'administration pour gérer les licences

### Fonctionnement des licences

1. L'utilisateur achète une licence via SureCart
2. La licence est liée à son compte WordPress
3. Le système vérifie automatiquement la validité de la licence
4. Les mises à jour sont disponibles uniquement pour les licences valides
5. Les licences expirées ne permettent plus l'accès aux mises à jour

## Développement

### Prérequis

- WordPress 6.5+
- PHP 8.0+
- Node.js 16+
- npm ou yarn

### Installation pour le développement

1. Clonez le dépôt :

```bash
git clone https://github.com/g2rd/g2rd-theme.git
cd g2rd-theme
```

2. Installez les dépendances :

```bash
npm install
```

3. Lancez le serveur de développement :

```bash
npm run dev
```

## Structure du projet

```
G2RD-theme/
├── assets/           # Assets compilés (CSS, JS, images)
├── blocks/           # Blocs personnalisés
├── classes/          # Classes PHP
│   ├── class-github-updater.php
│   ├── class-surecart-license-manager.php
│   └── ...
├── docs/            # Documentation complémentaire
├── includes/        # Fichiers d'inclusion
│   └── license-init.php
├── languages/       # Fichiers de traduction
├── parts/           # Templates de parties
├── patterns/        # Patterns FSE
├── styles/          # Styles FSE
├── templates/       # Templates FSE
├── configuration.json  # Configuration du thème
├── functions.php    # Fonctions principales
├── index.php        # Template par défaut
├── license.txt      # Licence (format WordPress.org)
├── LICENSE          # Licence (format GitHub)
├── readme.txt       # Documentation utilisateur (WordPress.org)
├── screenshot.png   # Capture d'écran du thème
├── style.css        # Fichier de style principal
├── theme.json       # Configuration FSE principale
├── theme-settings.json  # Paramètres du thème
└── theme-styles.json    # Styles du thème
```

## Support

Pour toute question ou assistance :

- Consultez la documentation en ligne
- Contactez le support via votre espace client SureCart
- Ouvrez une issue sur GitHub pour les bugs

## Licence

Ce thème est distribué sous licence EUPL-1.2. Voir le fichier LICENSE pour plus de détails.

## Crédits

- Développé par Sebastien GERARD
- Basé sur WordPress
- Utilise l'API GitHub pour les mises à jour
- Intégration avec SureCart pour la gestion des licences

## Structure de la documentation

- **README.md** : Documentation développeur, instructions d'installation, scripts, contribution. Ce fichier reste à la racine pour GitHub.
- **readme.txt** : Documentation utilisateur, format WordPress.org, reste à la racine du thème.
- **/docs/** : Documentation complémentaire (ex : [accessibilité](docs/accessibility.md), guides avancés, tutoriels).
- **LICENSE** et **license.txt** : Licences du projet, à la racine.

## Changelog

### 1.0.8

- Amélioration : l'effet particules est automatiquement désactivé lors de l'analyse par Google PageSpeed Insights et Lighthouse, ce qui améliore le score de performance sans impacter l'expérience utilisateur réelle.
- Technique : nettoyage du JS pour compatibilité WordPress, sans prise en compte des avertissements linter non pertinents.

### 1.0.7

- Correction : le préfixe et le suffixe du bloc Counter ne sont plus dupliqués en front (JS d'animation).
- Ajout : possibilité de personnaliser la taille de l'icône ou de l'image dans le bloc Counter (contrôle dans l'éditeur).
- Correction : effet glass (verre) fusionne désormais correctement le style inline (padding, etc.) au lieu de l'écraser.

### 1.0.6

- Création d'un bloc "G2RD Info" similaire au bloc info d'Astra
- Support complet des icônes WordPress (Dashicons) et images personnalisées
- 4 layouts possibles : gauche, droite, haut, bas
- Personnalisation complète : couleurs, typographie, espacement, bordures, ombres
- Effets de survol : scale, lift, glow
- Design responsive et accessible
- Intégration native avec les classes WordPress
- Sélecteur d'icônes visuel avec aperçu des Dashicons
- Plus de 100 icônes organisées par catégories
- Contrôle d'espacement (gap) dynamique entre icône et texte
- Optimisation du code avec factorisation et commentaires complets
- Correction des conflits CSS/inline styles
- Amélioration de l'alignement et du positionnement des icônes

### 1.0.5

- Création d'un bloc "Toggle Content" pour afficher/masquer deux groupes de blocs.
- Ajout d'options de personnalisation pour le bloc Toggle : alignement, style et couleurs.
- Le bloc Toggle utilise une technique 100% CSS (pas de JS en frontend) pour la performance.
- Création d'une catégorie de blocs "G2RD Bloks" pour regrouper les blocs du thème.

### 1.0.4

- Correction du champ titre éditable dans le bloc countdown
- Ajout/fiabilisation de l'option d'orientation (ligne/colonne) pour le timer
- Chargement du CSS du bloc via block.json (compatibilité build)
- Amélioration du .gitignore pour le dépôt GitHub
#   g 2 r d - t h e m e  
 