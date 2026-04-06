# G2RD Carousel - Bloc WordPress

Un bloc de carousel moderne et responsive pour WordPress, utilisant Swiper.js pour des animations fluides et une expérience utilisateur optimale.

## 🚀 Fonctionnalités

### Responsive Design
- **Mobile (320px+)**: 1 slide visible, effet slide pour les performances
- **Tablette (768px+)**: 2 slides visibles, navigation optimisée
- **Desktop (1024px+)**: 3 slides visibles, effets avancés
- **Grand écran (1200px+)**: Configuration complète avec effet coverflow

### Effets Visuels
- **Slide**: Transition simple et fluide
- **Coverflow**: Effet 3D avec perspective
- **Fade**: Transition en fondu
- **Cube**: Rotation 3D (si supporté)

### Navigation
- Boutons de navigation personnalisables
- Pagination avec bullets
- Navigation tactile optimisée
- Autoplay avec pause au survol

### Contenu
- Support des images avec légendes
- Intégration des posts WordPress
- Lazy loading pour les performances
- Accessibilité améliorée (ARIA labels)

## 📱 Responsive Breakpoints

Le carousel s'adapte automatiquement à la taille d'écran :

```javascript
// Configuration responsive automatique
320: { slidesPerView: 1, spaceBetween: 15, effect: 'slide' }
768: { slidesPerView: 2, spaceBetween: 25, effect: 'slide' }
1024: { slidesPerView: 3, spaceBetween: 30, effect: 'slide' }
1200: { slidesPerView: 3, spaceBetween: 50, effect: 'coverflow' }
```

## 🎨 Personnalisation

### Couleurs du thème
Le carousel utilise automatiquement les couleurs de votre thème WordPress :
- Couleurs primaires et secondaires
- Support des gradients
- Adaptation aux modes sombre/clair

### Styles CSS
```css
/* Personnalisation des boutons de navigation */
.swiper-button-prev,
.swiper-button-next {
  background: var(--wp--preset--color--primary);
  border-radius: 50%;
  transition: all 0.3s ease;
}

/* Adaptation mobile */
@media (max-width: 768px) {
  .swiper-slide {
    width: 280px !important;
    height: 200px !important;
  }
}
```

## 🔧 Installation

1. Copiez le dossier `g2rd-carousel` dans votre thème
2. Assurez-vous que Swiper.js est chargé
3. Le bloc sera automatiquement disponible dans l'éditeur

## 📋 Utilisation

### Dans l'éditeur WordPress
1. Ajoutez le bloc "G2RD Carousel"
2. Configurez les images ou sélectionnez des posts
3. Ajustez les paramètres d'affichage
4. Le responsive est automatique !

### Programmatiquement
```php
// Afficher un carousel dans votre thème
echo do_blocks('<!-- wp:g2rd/carousel {"images":[...]} /-->');
```

## ⚡ Performance

### Optimisations incluses
- **Lazy loading** des images
- **Détection tactile** pour désactiver les effets lourds
- **Debouncing** des événements de redimensionnement
- **Mise à jour intelligente** de Swiper

### Mobile First
- Effets simplifiés sur mobile
- Navigation tactile optimisée
- Chargement progressif
- Gestion de l'orientation

## 🎯 Accessibilité

- Labels ARIA pour la navigation
- Support du clavier
- Contraste des couleurs respecté
- Structure sémantique

## 🔄 Mise à jour

Le carousel se met à jour automatiquement lors des changements de taille d'écran :
- Redimensionnement de fenêtre
- Changement d'orientation mobile
- Rotation d'écran

## 🐛 Dépannage

### Problèmes courants
1. **Images qui ne s'affichent pas** : Vérifiez les URLs et les permissions
2. **Navigation qui ne fonctionne pas** : Assurez-vous que Swiper.js est chargé
3. **Problèmes sur mobile** : Vérifiez la console pour les erreurs JavaScript

### Debug
```javascript
// Accéder aux instances Swiper
window.G2RDCarousel.getAllInstances();

// Mettre à jour manuellement
window.G2RDCarousel.updateAllResponsive();
```

## 📄 Licence

Ce bloc fait partie du thème G2RD et suit les mêmes conditions de licence.

---

**Version**: 1.0.0  
**Dernière mise à jour**: Responsive complet  
**Compatibilité**: WordPress 5.0+, Swiper.js 8+
