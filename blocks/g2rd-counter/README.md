# G2RD Counter Block

Bloc compteur animé pour WordPress avec différents styles et options de personnalisation.

## Fonctionnalités

### Layouts

- **Number** - Affichage simple du nombre
- **Circle** - Affichage avec barre circulaire
- **Bar** - Affichage avec barre horizontale

### Configuration des nombres

- Nombre de départ et d'arrivée
- Places décimales (0-5)
- Préfixe et suffixe personnalisables
- Séparateur de milliers (virgule, espace, aucun)
- **Animation fluide** du nombre de départ vers le nombre d'arrivée
- Durée d'animation personnalisable (500ms à 10s)

### Icônes et images

- Plus de 50 icônes Dashicons organisées par catégories
- Support des images personnalisées
- Positionnement : haut, bas, gauche, droite

### Personnalisation

- Couleurs personnalisables (nombre, icône, titre, arrière-plan)
- Alignement (gauche, centre, droite)
- Marges personnalisables pour préfixe/suffixe

## Installation

Le bloc est automatiquement enregistré via le système d'auto-chargement du thème G2RD.

## Test

Pour vérifier que le bloc est correctement enregistré :

1. Ouvrez l'éditeur WordPress
2. Ouvrez la console développeur (F12)
3. Copiez et exécutez le contenu de `test-counter-in-editor.js`

## Build

```bash
cd blocks/g2rd-counter
npm install
npm run build
```

## Structure

```
g2rd-counter/
├── src/
│   ├── index.js      # Point d'entrée
│   ├── edit.js       # Interface d'édition
│   ├── save.js       # Sortie frontend
│   ├── style.css     # Styles frontend
│   └── editor.css    # Styles éditeur
├── build/            # Fichiers compilés
├── block.json        # Configuration du bloc
└── package.json      # Dépendances
```

## Utilisation

1. Dans l'éditeur WordPress, cherchez "G2RD Counter" dans la catégorie "G2RD Bloks"
2. Ajoutez le bloc à votre page/article
3. Configurez les options dans la barre latérale droite
4. Prévisualisez et publiez

## Animation

Le bloc comprend un système d'animation complet :

- **Déclenchement au scroll** : L'animation démarre quand le bloc entre dans la zone visible
- **Intersection Observer** : Performance optimisée
- **Respect de `prefers-reduced-motion`** : Affichage statique si l'utilisateur préfère moins d'animations
- **Easing** : Animation fluide avec fonction d'easing `easeOutQuart`
- **RequestAnimationFrame** : Animation 60fps fluide

### Animations par layout :

- **Number** : Animation du nombre avec formatage en temps réel
- **Circle** : Animation de la barre circulaire SVG + nombre
- **Bar** : Animation de la barre horizontale + nombre

## Version

Version 1.0.0 - Bloc avec animations complètes
