# Fonctionnalités

Les fonctionnalités sont activées ou désactivées depuis la **Page d'options G2RD** dans l'administration WordPress (`Apparence > Options G2RD`).

---

## Fonctionnalités actives par défaut

### Mode sombre (Dark Mode)
**Clé** : `dark_mode`

Ajoute un bouton de bascule jour/nuit accessible à l'utilisateur. Le choix est mémorisé en `localStorage` et synchronisé en cookie pour le serveur.

Caractéristiques :
- Anti-FOUC : script inline dans `<head>` pour appliquer le thème avant le rendu — aucun clignotement
- Contraste WCAG AA sur toutes les couleurs en mode sombre
- Compatible LiteSpeed Cache (exclu de l'optimisation JS)
- Fonctionne avec toutes les variations de style du thème

### Accessibilité
**Clé** : `accessibility`

Améliorations d'accessibilité WCAG 2.1 niveau AA :
- Labels ARIA sur les éléments interactifs
- Gestion du focus clavier
- Attributs `role` corrects sur les composants custom
- Textes alternatifs automatiques sur les images décoratives

---

## Fonctionnalités optionnelles

### Animations GSAP
**Clé** : `gsap_animations`  
**Par défaut** : désactivé

Ajoute des animations d'entrée fluides sur les éléments au scroll (fade, slide, scale). Utilise GSAP + ScrollTrigger chargés depuis le CDN jsDelivr.

À activer pour : les sites d'agence, portfolios, landing pages haut de gamme.  
À éviter pour : les sites e-commerce et blogs avec beaucoup de contenu (impact PageSpeed).

### Effet Particules
**Clé** : `particles_effect`  
**Par défaut** : désactivé

Fond animé avec particules flottantes sur les sections hero. Désactivé automatiquement pour Google PageSpeed Insights et Lighthouse.

### Effet Verre (Glass Effect)
**Clé** : `glass_effect`  
**Par défaut** : désactivé

Applique un effet glassmorphism (flou de fond + transparence) aux cartes et panneaux. Nécessite un fond avec image ou dégradé visible derrière les composants.

### Articles cliquables
**Clé** : `clickable_articles`  
**Par défaut** : désactivé

Rend l'ensemble d'une carte d'article cliquable (pas seulement le titre ou le lien). Améliore l'UX sur les grilles d'articles.

---

## Fonctionnalités IA / GEO

### Analyseur GEO
**Clé** : `enable_ai`  
**Par défaut** : désactivé

Ajoute un panneau latéral dans l'éditeur Gutenberg avec un score GEO /100 et des recommandations pour optimiser le contenu pour les moteurs génératifs (ChatGPT, Perplexity, Gemini, Google AI Overviews).

Nécessite une clé API configurée dans les options.

---

## Gestion des patterns

### Patterns sous licence
**Clé** : `patterns_require_license`  
**Par défaut** : désactivé

Quand activé, restreint l'accès aux patterns premium aux sites avec une licence G2RD valide.

---

## Ajouter une nouvelle fonctionnalité

1. Ajouter la clé dans le tableau `FEATURES` de `class-theme-options.php`
2. Utiliser `ThemeOptions::isFeatureEnabled('ma_feature')` dans la classe concernée
3. Ajouter l'icône dashicon dans `FeatureCard.js` (page d'options React)
4. Compiler avec `npm run build:options`

---

## Récapitulatif

| Fonctionnalité | Clé | Défaut | Impact performance |
|----------------|-----|--------|-------------------|
| Mode sombre | `dark_mode` | **Activé** | Nul |
| Accessibilité | `accessibility` | **Activé** | Nul |
| Animations GSAP | `gsap_animations` | Désactivé | Modéré |
| Effet Particules | `particles_effect` | Désactivé | Élevé |
| Effet Verre | `glass_effect` | Désactivé | Faible |
| Articles cliquables | `clickable_articles` | Désactivé | Nul |
| Analyseur GEO | `enable_ai` | Désactivé | Nul (éditeur uniquement) |
| Patterns sous licence | `patterns_require_license` | Désactivé | Nul |
