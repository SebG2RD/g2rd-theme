# Thèmes enfants de référence

Ce dossier rassemble les thèmes enfants réalisés pour des projets clients. Ils servent de
**base de départ** pour les prochains projets : on copie le plus proche, on renomme, on adapte.

**Rien de ce dossier ne part en production.** Il est exclu du ZIP du thème par :

- `tools/export-theme.ps1` (export local) — dossier `child-themes` dans `$excludedFolders` ;
- `.github/workflows/release.yml` (release GitHub) — `--exclude="child-themes"` ;
- `tools/verify-theme-zip.sh` — la validation échoue si `child-themes/` apparaît dans le ZIP.

Le GitHub Updater installe l'asset ZIP de la release, jamais le zipball brut du dépôt : les sites
clients ne reçoivent donc jamais ce dossier.

## Convention

Un sous-dossier par thème enfant, nommé comme le dossier déployé dans `wp-content/themes/` :

```text
child-themes/
├── README.md
├── g2rd-child-moovea/
│   ├── style.css          # En-tête WordPress avec « Template: g2rd-theme »
│   ├── theme.json         # settings uniquement (voir CLAUDE.md › Thème enfant)
│   ├── functions.php
│   └── styles/
│       └── moovea.json    # Variation de styles propre au client (v1.39.0+)
└── g2rd-child-<client>/
```

Rappels tirés de `CLAUDE.md` :

- Le `theme.json` d'un enfant n'apporte de façon fiable que des **`settings`** ; pour surcharger
  des styles, utiliser une **variation** dans `styles/<projet>.json` ou le CSS de l'enfant.
- Une variation placée dans l'enfant remplace celle du parent portant le même nom de fichier.

## Démarrer un nouveau projet

1. Copier le thème enfant le plus proche vers `wp-content/themes/g2rd-child-<client>/`
   (hors de ce dépôt).
2. Renommer : en-tête `style.css` (Theme Name, Text Domain), slug de la variation, préfixes.
3. Une fois le projet livré, rapatrier une copie ici pour enrichir la base d'exemples.

## À ne jamais mettre ici

- Secrets, clés API, identifiants, exports de base de données.
- Médias clients (`uploads/`) ou données personnelles.
- `node_modules/` ou tout artefact de build.

WordPress n'enregistre un thème que s'il est un sous-dossier direct de `wp-content/themes/` :
un thème enfant rangé ici n'est jamais détecté comme thème, même sur une installation qui
pointe directement sur le dépôt.
