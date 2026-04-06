# Guide de personnalisation avancée

Ce guide explique comment personnaliser le thème G2RD au-delà des options de l'éditeur.

## Surcharger les styles

- Utilisez un thème enfant ou ajoutez vos SCSS/CSS dans le dossier approprié
- Utilisez les variables CSS du thème
- **Exemple** :
  ```css
  :root {
    --wp--preset--color--primary: #1a237e;
  }
  ```

## Modifier les templates

- Les templates FSE sont dans /templates et /parts
- Vous pouvez les dupliquer et les adapter dans un thème enfant
- **Exemple** :
  Copiez `parts/header.html` dans votre thème enfant et modifiez le logo ou la structure.

## Ajouter des hooks ou filtres

- Utilisez les hooks WordPress pour ajouter des fonctionnalités
- **Exemple** :
  ```php
  add_action('wp_footer', function() {
    echo '<!-- Custom footer code -->';
  });
  ```

## Ajouter des blocs personnalisés

- Placez vos blocs dans /src/blocks
- Suivez la structure des blocs existants
- **Exemple** :
  Créez `/src/blocks/mon-bloc/` avec un fichier `block.json` et le JS associé.

## Personnaliser le bloc G2RD Info _(v1.0.6)_

Le bloc G2RD Info peut être personnalisé de plusieurs façons :

### Ajouter des icônes personnalisées

- Ajoutez vos propres icônes dans la liste des Dashicons disponibles
- Modifiez le fichier `blocks/g2rd-info/src/edit.js` pour étendre la liste d'icônes :
  ```javascript
  const customIcons = [
    { name: "mon-icone", label: "Mon Icône Custom", category: "Custom" },
  ];
  ```

### Personnaliser les styles CSS

- Surchargez les styles dans votre fichier CSS :

  ```css
  .wp-block-g2rd-info {
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
  }

  .wp-block-g2rd-info .dashicons {
    transition: transform 0.3s ease;
  }

  .wp-block-g2rd-info:hover .dashicons {
    transform: scale(1.1);
  }
  ```

### Ajouter des layouts personnalisés

- Étendez les options de layout en modifiant le `block.json` et `edit.js`
- Exemple pour ajouter un layout diagonal :
  ```javascript
  { label: 'Diagonal', value: 'icon-diagonal' }
  ```

## Ressources utiles

- [Documentation WordPress FSE](https://developer.wordpress.org/block-editor/full-site-editing/)
- [Documentation des blocs personnalisés](blocks.md)
