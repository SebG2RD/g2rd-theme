# Guide d'accessibilité du thème G2RD

Ce document décrit les mesures d'accessibilité mises en place dans le thème G2RD pour assurer la conformité avec les normes WCAG 2.1.

## Fonctionnalités d'accessibilité

### Navigation

- Structure de navigation claire et cohérente
- Support de la navigation au clavier
- Indicateurs de focus visibles
- Liens d'évitement pour accéder au contenu principal
- Support des raccourcis clavier

### Contenu

- Structure sémantique HTML5
- Hiérarchie des titres appropriée
- Textes alternatifs pour les images
- Contraste des couleurs conforme WCAG 2.1
- Taille de texte ajustable
- Support du mode sombre

### Formulaires

- Labels explicites pour tous les champs
- Messages d'erreur clairs et accessibles
- Validation des formulaires côté client et serveur
- Support des lecteurs d'écran
- Indication des champs obligatoires

### Médias

- Sous-titres pour les vidéos
- Transcripts pour l'audio
- Contrôles de lecture accessibles
- Support des descriptions alternatives

### Composants interactifs

- Boutons avec états visuels clairs
- Menus déroulants accessibles
- Modales et dialogues conformes
- Support des attributs ARIA

## Implémentation

### Classes CSS

```css
/* Focus visible */
:focus {
  outline: 2px solid #0073aa;
  outline-offset: 2px;
}

/* Mode sombre */
@media (prefers-color-scheme: dark) {
  :root {
    --text-color: #ffffff;
    --background-color: #1a1a1a;
  }
}

/* Taille de texte */
.text-large {
  font-size: 1.2em;
}

.text-larger {
  font-size: 1.4em;
}
```

### Attributs ARIA

```html
<!-- Navigation principale -->
<nav aria-label="Navigation principale">
  <!-- ... -->
</nav>

<!-- Contenu principal -->
<main id="main" role="main">
  <!-- ... -->
</main>

<!-- Formulaire -->
<form aria-labelledby="form-title">
  <h2 id="form-title">Titre du formulaire</h2>
  <!-- ... -->
</form>
```

### JavaScript

```javascript
// Gestion du focus
document.addEventListener("keydown", (e) => {
  if (e.key === "Tab") {
    document.body.classList.add("keyboard-navigation");
  }
});

document.addEventListener("mousedown", () => {
  document.body.classList.remove("keyboard-navigation");
});

// Support des raccourcis clavier
document.addEventListener("keydown", (e) => {
  if (e.altKey && e.key === "m") {
    document.getElementById("main").focus();
  }
});
```

## Tests d'accessibilité

### Outils recommandés

- WAVE (Web Accessibility Evaluation Tool)
- axe DevTools
- Lighthouse
- NVDA (lecteur d'écran)
- VoiceOver (macOS)

### Points de contrôle

1. Navigation au clavier
2. Structure sémantique
3. Contraste des couleurs
4. Textes alternatifs
5. Formulaires accessibles
6. Support des lecteurs d'écran
7. Responsive design
8. Performance

## Ressources

- [WCAG 2.1 Guidelines](https://www.w3.org/TR/WCAG21/)
- [ARIA Authoring Practices](https://www.w3.org/WAI/ARIA/apg/)
- [WebAIM](https://webaim.org/)
- [A11Y Project](https://www.a11yproject.com/)
