# Design System G2RD

Le design system G2RD est l'ensemble des tokens, composants et règles visuelles qui garantissent la cohérence du thème à travers tous les sites créés avec lui.

## Comment utiliser cette documentation

| Fichier | Contenu |
|---------|---------|
| [tokens.md](tokens.md) | Couleurs, typographie, espacements, ombres, rayons |
| [blocs.md](blocs.md) | Les 34 blocs Gutenberg — namespace, description, attributs clés |
| [patterns.md](patterns.md) | Les 28 patterns — description et cas d'usage |
| [features.md](features.md) | Les 8 fonctionnalités activables depuis la page d'options |

## Principe fondamental

**Toutes les valeurs visuelles passent par des variables CSS.** Ne jamais coder de couleurs, tailles ou espacements en dur dans les styles personnalisés.

```css
/* ✅ Correct */
color: var(--wp--preset--color--primary);
padding: var(--wp--preset--spacing--m);

/* ❌ Incorrect */
color: #2F425D;
padding: 3.2rem;
```

Les variables sont résolues depuis `theme-settings.json` et s'adaptent automatiquement quand l'utilisateur change de variation de style.

## Source de vérité

- **Couleurs / typo / espacements** : `theme-settings.json`
- **Styles appliqués** : `theme-styles.json`
- **Configuration blocs** : `configuration.json`
- **Styles conditionnels** : `assets/css/magic-page.css`
