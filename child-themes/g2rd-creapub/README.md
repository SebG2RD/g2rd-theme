# G2RD — Créa Pub Communication

Thème enfant du thème G2RD pour Créa Pub Communication, agence de communication visuelle à Vernon (27) : print, signalétique et enseignes, marquage véhicule, textile, objets publicitaires. Charte noir `#050505` / rose magenta `#EC008C` / blanc, Poppins, relevée sur le site actuel (rose à confirmer au pixel sur le logo vectoriel).

- Parent requis : `g2rd-theme` 1.39.0 ou plus récent
- WordPress 6.6 minimum
- PHP 8.1 minimum

## Répartition parent / enfant

Le thème parent reçoit UN SEUL fichier : la variation `styles/creapub.json` (copiée dans `g2rd-theme/styles/`). Tout le reste vit ici. Le thème enfant applique la charte par défaut dès l'activation (`functions.php`, priorité 20) ; la variation reste disponible dans le sélecteur de styles.

## Installation

1. Copier `creapub.json` dans `g2rd-theme/styles/` (seule intervention sur le parent).
2. Copier le dossier `g2rd-creapub` dans `wp-content/themes/` à côté de `g2rd-theme`.
3. Activer « G2RD Créa Pub » dans Apparence › Thèmes. La charte s'applique seule.
4. Déployer le contenu en 1 clic : **Apparence › Installation Créa Pub › « Créer les contenus manquants »**, ou `wp creapub install`. L'outil crée :
   - les 7 pages (Accueil assemblée depuis 11 sections, L'agence, La Team, Actualités, Contact, Mentions légales, Politique de confidentialité), références de compositions développées en vrais blocs éditables ;
   - les 5 prestations rédigées (CPT `prestations`, structure du gabarit verrouillé) ;
   - les 6 catégories du blog (5 prestations + Vie de l'atelier) ;
   - la navigation principale (sous-menu Prestations pointant sur les CPT) ;
   - les réglages de lecture (page d'accueil, page des articles, permaliens `/actualites/%postname%/`, fuseau, slogan).
   Rien d'existant n'est écrasé ; la case « Forcer » réécrit les contenus déjà en place (titres et statuts conservés). L'outil retire aussi les copies en base des modèles du parent restées identiques à leurs fichiers, pour que les gabarits de l'enfant prennent la main.
5. Téléverser le logo officiel (Réglages › Identité du site) : header, footer et hero l'utilisent.
6. En production : `.htaccess` selon `docs/redirections-301.md` du projet (301 + 5 en-têtes de sécurité, CSP en Report-Only d'abord).

## Ce que force le thème enfant (sans toucher au parent)

- CPT du parent actifs : `portfolio` (URL `/realisations/`), `prestations` (URL `/prestations/`, sans archive), `qui-sommes-nous` (sans archive, affiché par la page La Team).
- CPT verrouillés en « champs à remplir » (`template_lock contentOnly`) : le client remplit, ne casse pas la structure. Les pages restent en édition libre.
- Mode sombre, GSAP, particules et glassmorphism coupés. L'animation du hero (logo → machine à écrire → pinceau) est `assets/js/creapub-hero.js`, H1 complet dans le DOM, `prefers-reduced-motion` respecté.

## Contenu du thème

```text
g2rd-creapub/
├── style.css                 En-tête (Template: g2rd-theme) + CSS charte (pinceau, tuiles, animation hero)
├── theme.json                Settings : palette 12 tokens, dégradés, Poppins (fontFace gstatic)
├── functions.php             Variation par défaut, CPT forcés + verrouillés, enqueue, catégories de compositions
├── inc/setup.php             Installateur 1 clic + page Apparence › Installation Créa Pub
├── inc/class-cli-command.php wp creapub install [--force]
├── assets/js/creapub-hero.js Séquence d'apparition du hero (SEO-safe)
├── parts/                    header.html (sticky, liseré rose, CTA devis), footer.html (NAP réel)
├── templates/                single-prestations, single-post, archive-portfolio, home (blog)
└── patterns/                 23 compositions : 12 sections (catégorie « Créa Pub — Sections »)
                              + 11 pages et prestations (catégorie « Créa Pub — Pages »)
```

## Reste à faire après installation

- Insérer le bloc `g2rd/breadcrumb` dans `templates/single-prestations.html` à la place du paragraphe repère.
- Remplacer la grille de `templates/archive-portfolio.html` par le bloc `g2rd/filterable-grid` (filtre par prestation).
- Photos réelles : 4 tuiles du hero, 6 dernières créations, logos clients et partenaires en WebP avec alt.
- Pipetter le rose exact sur le logo vectoriel → ajuster `rose` / `rose-vif` dans `creapub.json` et `theme.json` si différent de `#EC008C`.
- Compléter les champs `[À COMPLÉTER]` des pages légales (SIRET, RCS, durées RGPD) et faire relire les textes des prestations (délais annoncés) par Cathy.
- Formulaire de contact (nom, contact, message + honeypot) à insérer dans la zone repérée de la page Contact.

— G2RD Agence Web · g2rd.fr
