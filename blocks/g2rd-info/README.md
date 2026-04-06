# G2RD Info Block

Un bloc d'information personnalisable similaire à celui du thème Astra, permettant d'afficher des informations avec une icône ou une image, un titre et une description.

## Fonctionnalités

### Media

- **Choix entre icône et image** : Possibilité d'utiliser soit une icône WordPress (Dashicons) soit une image personnalisée
- **Icônes disponibles** : Info, Warning, Success, Error, Question, Star, Heart, Lightbulb, Book, Camera
- **Gestion des images** : Upload via la bibliothèque WordPress, tailles prédéfinies ou personnalisées
- **Alt text** : Support complet de l'accessibilité

### Layout

- **4 positions possibles** : Gauche, Droite, Haut, Bas
- **Alignement du contenu** : Gauche, Centre, Droite
- **Responsive** : Adaptation automatique sur mobile

### Typographie

- **Titre** : Taille de police, poids, couleur, couleur de fond configurables
- **Description** : Taille de police, poids, couleur, couleur de fond configurables
- **Unités supportées** : px, em, rem

### Couleurs

- **Couleur de fond** du bloc
- **Couleur du texte** principal
- **Couleur du titre** et **couleur de fond du titre**
- **Couleur de la description** et **couleur de fond de la description**

### Espacement

- **Padding** : Contrôle complet des marges intérieures
- **Margin** : Contrôle complet des marges extérieures
- **Gap** : Espacement entre l'icône/image et le texte

### Bordures et Ombres

- **Largeur de bordure** : 0 à 20px
- **Couleur de bordure** : Personnalisable
- **Rayon de bordure** : Coins arrondis configurables
- **Ombres** : Aucune, Petite, Moyenne, Grande
- **Effets de survol** : Aucun, Scale, Lift, Glow

## Utilisation

1. Ajoutez le bloc "G2RD Info" depuis l'éditeur Gutenberg
2. Configurez le type de média (icône ou image)
3. Saisissez le titre et la description
4. Ajustez la mise en page et les couleurs selon vos besoins
5. Personnalisez l'espacement et les effets visuels

## Classes CSS

Le bloc génère automatiquement les classes CSS suivantes :

- `.g2rd-info-block` : Conteneur principal
- `.g2rd-info-content` : Conteneur du contenu
- `.g2rd-info-text` : Zone de texte
- `.g2rd-info-icon` : Conteneur de l'icône
- `.g2rd-info-image` : Conteneur de l'image
- `.g2rd-info-layout-{position}` : Classes de mise en page (left, right, top, bottom)

## Accessibilité

- Support complet des attributs alt pour les images
- Gestion du focus pour la navigation au clavier
- Structure sémantique appropriée (h3 pour le titre, p pour la description)
- Contraste des couleurs respecté

## Compatibilité

- WordPress 6.0+
- Éditeur Gutenberg
- Thème G2RD
- Responsive design
- Impression

## Développement

Pour modifier le bloc :

1. Modifiez les fichiers dans `/src/`
2. Exécutez `npm run build` pour compiler
3. Le bloc sera automatiquement rechargé dans WordPress

### Structure des fichiers

```
g2rd-info/
├── block.json          # Configuration du bloc
├── package.json        # Dépendances npm
├── webpack.config.js   # Configuration webpack
├── src/
│   ├── index.js        # Point d'entrée
│   ├── edit.js         # Composant d'édition
│   ├── save.js         # Composant de sauvegarde
│   ├── info.css        # Styles CSS
│   └── info-frontend.js # Script frontend
└── build/              # Fichiers compilés
```
