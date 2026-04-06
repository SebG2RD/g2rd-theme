# Traductions du Block G2RD Carousel

Ce dossier contient les fichiers de traduction pour le block G2RD Carousel.

## Fichiers disponibles

- `g2rd-carousel.pot` - Template de traduction (fichier source)
- `g2rd-carousel-fr_FR.po` - Traduction française
- `g2rd-carousel-fr_FR.mo` - Fichier binaire de traduction française (généré)

## Comment ajouter une nouvelle traduction

### 1. Créer un fichier .po

1. Copiez le fichier `g2rd-carousel.pot`
2. Renommez-le avec le code de langue approprié (ex: `g2rd-carousel-es_ES.po`)
3. Modifiez l'en-tête du fichier :
   ```
   "Language: es_ES\n"
   "Language-Team: Spanish\n"
   ```

### 2. Traduire les textes

Pour chaque entrée dans le fichier .po :

```
msgid "Content Selection"
msgstr "Selección de contenido"
```

### 3. Générer le fichier .mo

Utilisez un outil comme Poedit ou la commande :

```bash
msgfmt g2rd-carousel-es_ES.po -o g2rd-carousel-es_ES.mo
```

## Installation des traductions

### Méthode 1 : Dossier languages du thème

Placez les fichiers .mo dans le dossier `languages` de votre thème :

```
wp-content/themes/your-theme/languages/
├── g2rd-carousel-fr_FR.mo
├── g2rd-carousel-es_ES.mo
└── ...
```

### Méthode 2 : Plugin de traduction

Utilisez un plugin comme Loco Translate ou WPML pour gérer les traductions.

## Textes traduisibles

Le block carrousel inclut les traductions pour :

### Interface d'administration

- Titres des panneaux de configuration
- Labels des champs
- Messages d'aide
- Options de sélection

### Contenu utilisateur

- Textes par défaut
- Messages d'erreur
- Textes d'interface

## Support des langues

Actuellement supporté :

- 🇫🇷 Français (fr_FR)

## Contribution

Pour contribuer à une traduction :

1. Créez un fichier .po basé sur le template
2. Traduisez tous les textes
3. Testez la traduction
4. Soumettez votre contribution

## Support

Pour toute question concernant les traductions, contactez l'équipe G2RD.
