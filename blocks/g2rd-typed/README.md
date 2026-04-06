# G2RD Typed Block

Un bloc WordPress personnalisé qui crée un effet de texte animé avec frappe automatique utilisant la bibliothèque Typed.js.

## Fonctionnalités

- **Textes animés personnalisables** : Ajoutez, modifiez ou supprimez facilement les textes à animer
- **Texte avant/après** : Possibilité d'ajouter du texte statique avant et après l'animation
- **Paramètres d'animation avancés** : Contrôle de la vitesse de frappe, d'effacement, délais, etc.
- **Curseur personnalisable** : Afficher/masquer le curseur et personnaliser son caractère
- **Options avancées** : Boucle, mélange, fondu, effacement intelligent
- **Interface intuitive** : Contrôles dans la sidebar de l'éditeur Gutenberg
- **Responsive** : S'adapte automatiquement aux différentes tailles d'écran

## Installation

1. Assurez-vous que le bloc est inclus dans votre thème G2RD
2. Le bloc sera automatiquement disponible dans l'éditeur Gutenberg
3. Cherchez "G2RD Typed" dans la liste des blocs

## Utilisation

### Interface d'édition

1. **Ajouter le bloc** : Recherchez "G2RD Typed" dans l'éditeur Gutenberg
2. **Configurer les textes** : Dans la sidebar, ajoutez vos textes à animer
3. **Personnaliser l'apparence** : Utilisez les contrôles d'alignement et de style
4. **Ajuster l'animation** : Modifiez les paramètres de vitesse et de comportement

### Paramètres disponibles

#### Textes animés

- Ajouter/supprimer des textes à animer
- Modifier le contenu de chaque texte

#### Texte avant/après

- Texte qui apparaît avant l'animation
- Texte qui apparaît après l'animation

#### Paramètres d'animation

- **Vitesse de frappe** : 10-200 (défaut: 70)
- **Vitesse d'effacement** : 10-200 (défaut: 35)
- **Délai de départ** : 0-5000ms (défaut: 0)
- **Délai avant effacement** : 0-5000ms (défaut: 500)
- **Boucle infinie** : Activer/désactiver
- **Mélanger les textes** : Ordre aléatoire

#### Curseur

- **Afficher le curseur** : Activer/désactiver
- **Caractère du curseur** : Personnaliser (défaut: "|")

#### Options avancées

- **Effacement intelligent** : Optimise l'effacement
- **Fondu de sortie** : Effet de transition
- **CSS automatique** : Injection automatique des styles
- **Type de contenu** : HTML ou texte brut

## Structure HTML générée

```html
<div class="g2rd-typed" data-typed-config="...">
  <span class="typed-text-before">Texte avant</span>
  <div id="typed-strings" style="display: none;">
    <p><strong>Texte 1</strong></p>
    <p><strong>Texte 2</strong></p>
  </div>
  <span id="typed" class="typed-text-animated"></span>
  <span class="typed-text-after">Texte après</span>
</div>
```

## Personnalisation CSS

Le bloc utilise les classes CSS suivantes pour la personnalisation :

```css
.g2rd-typed {
  /* Conteneur principal */
}

.typed-text-before {
  /* Texte avant l'animation */
}

.typed-text-animated {
  /* Zone d'animation */
}

.typed-text-after {
  /* Texte après l'animation */
}

.typed-cursor {
  /* Curseur animé */
}
```

## Dépendances

- **Typed.js** : Chargé automatiquement depuis CDN (https://cdn.jsdelivr.net/npm/typed.js@2.0.12)
- **WordPress 6.5+** : Compatible avec les fonctionnalités FSE

## Support

Pour toute question ou problème, consultez la documentation du thème G2RD ou contactez le support.

## Licence

Ce bloc fait partie du thème G2RD et est distribué sous la même licence.
