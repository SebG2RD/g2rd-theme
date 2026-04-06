<?php
// Auto-généré par sync_g2rd_build_to_g2rd_blocks.php
return array (
  'AdvancedList' => 
  array (
    '$schema' => 'https://schemas.wp.org/trunk/block.json',
    'apiVersion' => 3,
    'name' => 'g2rd/advanced-list',
    'version' => '1.0.0',
    'title' => 'G2RD Liste avancée',
    'category' => 'g2rd-blocks',
    'description' => 'Liste avec icônes personnalisables (bibliothèque ou image/SVG). Options de couleur et d\'espacement par ligne et pour l\'ensemble du bloc. Idéal pour listes, menus, boutons.',
    'keywords' => 
    array (
      0 => 'list',
      1 => 'liste',
      2 => 'icône',
      3 => 'menu',
      4 => 'boutons',
    ),
    'attributes' => 
    array (
      'items' => 
      array (
        'type' => 'array',
        'default' => 
        array (
        ),
      ),
      'listTag' => 
      array (
        'type' => 'string',
        'default' => 'ul',
      ),
      'iconPosition' => 
      array (
        'type' => 'string',
        'default' => 'left',
      ),
      'iconSize' => 
      array (
        'type' => 'number',
        'default' => 24,
      ),
      'gapBetweenItems' => 
      array (
        'type' => 'number',
        'default' => 12,
      ),
      'styleVariant' => 
      array (
        'type' => 'string',
        'default' => 'list',
      ),
    ),
    'example' => 
    array (
      'attributes' => 
      array (
        'items' => 
        array (
          0 => 
          array (
            'id' => '1',
            'content' => 'Premier élément',
            'iconType' => 'dashicon',
            'iconValue' => 'yes',
          ),
          1 => 
          array (
            'id' => '2',
            'content' => 'Deuxième élément',
            'iconType' => 'dashicon',
            'iconValue' => 'star-filled',
          ),
        ),
      ),
    ),
    'supports' => 
    array (
      'html' => false,
      'align' => true,
      'color' => 
      array (
        'background' => true,
        'text' => true,
      ),
      'spacing' => 
      array (
        'margin' => true,
        'padding' => true,
      ),
      'typography' => 
      array (
        'fontSize' => true,
      ),
    ),
    'textdomain' => 'g2rd',
    'editorScript' => 'file:./index.js',
    'editorStyle' => 'file:./index.css',
    'style' => 'file:./style-index.css',
  ),
  'Breadcrumb' => 
  array (
    '$schema' => 'https://schemas.wp.org/trunk/block.json',
    'apiVersion' => 3,
    'name' => 'g2rd/breadcrumb',
    'version' => '1.0.0',
    'title' => 'G2RD Fil d\'Ariane',
    'category' => 'g2rd-blocks',
    'description' => 'Fil d\'Ariane intelligent avec microdonnées Schema.org. Améliore le SEO et l\'expérience utilisateur.',
    'keywords' => 
    array (
      0 => 'breadcrumb',
      1 => 'fil d\'ariane',
      2 => 'navigation',
      3 => 'seo',
      4 => 'schema.org',
    ),
    'attributes' => 
    array (
      'showHome' => 
      array (
        'type' => 'boolean',
        'default' => true,
      ),
      'separator' => 
      array (
        'type' => 'string',
        'default' => 'chevron',
      ),
      'homeLabel' => 
      array (
        'type' => 'string',
        'default' => '',
      ),
      'textColor' => 
      array (
        'type' => 'string',
      ),
      'linkColor' => 
      array (
        'type' => 'string',
      ),
    ),
    'example' => 
    array (
      'attributes' => 
      array (
        'showHome' => true,
        'separator' => 'chevron',
      ),
    ),
    'supports' => 
    array (
      'html' => false,
      'align' => true,
      'typography' => 
      array (
        'fontSize' => true,
      ),
      'spacing' => 
      array (
        'margin' => true,
        'padding' => true,
      ),
    ),
    'textdomain' => 'g2rd',
    'render' => 'file:./render.php',
    'editorScript' => 'file:./index.js',
    'editorStyle' => 'file:./index.css',
    'style' => 'file:./style-index.css',
  ),
  'CardG2rd' => 
  array (
    '$schema' => 'https://schemas.wp.org/trunk/block.json',
    'apiVersion' => 3,
    'name' => 'g2rd/card',
    'version' => '1.0.0',
    'title' => 'Carte G2RD',
    'category' => 'g2rd-blocks',
    'description' => 'Bloc de carte pour G2RD',
    'attributes' => 
    array (
      'emoji' => 
      array (
        'type' => 'string',
        'default' => '😃',
      ),
      'title' => 
      array (
        'type' => 'string',
        'source' => 'html',
        'selector' => '.wp-block-capitainewp-inspector__title',
        'role' => 'content',
      ),
      'description' => 
      array (
        'type' => 'string',
        'source' => 'html',
        'selector' => '.wp-block-capitainewp-inspector__description',
        'role' => 'content',
      ),
      'hasTag' => 
      array (
        'type' => 'boolean',
        'default' => true,
      ),
      'tag' => 
      array (
        'type' => 'string',
        'source' => 'html',
        'selector' => '.wp-block-capitainewp-inspector__tag',
        'default' => 'New',
        'role' => 'content',
      ),
      'tagColor' => 
      array (
        'type' => 'string',
        'default' => 'var(--wp--preset--color--accent-2)',
      ),
      'tagTextColor' => 
      array (
        'type' => 'string',
        'default' => 'var(--wp--preset--color--accent-3)',
      ),
      'tagRadius' => 
      array (
        'type' => 'number',
        'default' => 5,
      ),
    ),
    'example' => 
    array (
      'attributes' => 
      array (
        'emoji' => '😃',
        'title' => 'Carte G2RD',
        'description' => 'Exemple de carte G2RD',
        'hasTag' => true,
        'tag' => 'New',
        'tagColor' => 'var(--wp--preset--color--accent-2)',
        'tagTextColor' => 'var(--wp--preset--color--accent-3)',
        'tagRadius' => 5,
      ),
    ),
    'supports' => 
    array (
      'color' => 
      array (
        'text' => true,
        'background' => true,
      ),
    ),
    'textdomain' => 'g2rd',
    'editorScript' => 'file:./index.js',
    'editorStyle' => 'file:./index.css',
    'style' => 'file:./style-index.css',
  ),
  'CodeG2rd' => 
  array (
    '$schema' => 'https://schemas.wp.org/trunk/block.json',
    'apiVersion' => 3,
    'name' => 'g2rd/code',
    'version' => '1.0.0',
    'title' => 'G2RD Code',
    'category' => 'g2rd-blocks',
    'icon' => 'media-code',
    'description' => 'Bloc de coloration syntaxique avec highlight.js. Éditez votre code en direct avec 0 JS en frontend.',
    'keywords' => 
    array (
      0 => 'code',
      1 => 'syntax',
      2 => 'highlight',
      3 => 'coloration',
    ),
    'attributes' => 
    array (
      'source' => 
      array (
        'type' => 'string',
        'default' => '',
      ),
      'file' => 
      array (
        'type' => 'string',
        'default' => '',
      ),
      'language' => 
      array (
        'type' => 'string',
        'default' => 'html',
      ),
      'theme' => 
      array (
        'type' => 'string',
        'default' => 'monokai',
      ),
      'fontSize' => 
      array (
        'type' => 'number',
        'default' => 14,
      ),
      'startLine' => 
      array (
        'type' => 'number',
        'default' => 1,
      ),
      'showLines' => 
      array (
        'type' => 'boolean',
        'default' => true,
      ),
      'wrapLines' => 
      array (
        'type' => 'boolean',
        'default' => true,
      ),
      'border' => 
      array (
        'type' => 'object',
        'default' => 
        array (
        ),
      ),
      'borderRadius' => 
      array (
        'type' => 'number',
        'default' => 0,
      ),
      'shadow' => 
      array (
        'type' => 'string',
        'default' => '',
      ),
    ),
    'example' => 
    array (
      'attributes' => 
      array (
        'source' => '<div class="example">Hello World</div>',
        'language' => 'html',
        'theme' => 'monokai',
      ),
    ),
    'supports' => 
    array (
      'html' => false,
      'align' => 
      array (
        0 => 'left',
        1 => 'right',
        2 => 'wide',
        3 => 'full',
      ),
      'spacing' => 
      array (
        'margin' => true,
        'padding' => false,
      ),
      'shadow' => false,
    ),
    'textdomain' => 'g2rd',
    'render' => 'file:./render.php',
    'editorScript' => 'file:./build/index.js',
    'editorStyle' => 'file:./index.css',
    'style' => 'file:./style-index.css',
  ),
  'DeviceMockup' => 
  array (
    '$schema' => 'https://schemas.wp.org/trunk/block.json',
    'apiVersion' => 3,
    'name' => 'g2rd/device-mockup',
    'version' => '1.0.0',
    'title' => 'G2RD Device Mockup',
    'category' => 'g2rd-blocks',
    'description' => 'Enveloppe vos images ou vidéos dans des cadres réalistes (smartphone, tablette, ordinateur) générés en CSS.',
    'keywords' => 
    array (
      0 => 'mockup',
      1 => 'device',
      2 => 'smartphone',
      3 => 'laptop',
      4 => 'présentation',
      5 => 'écran',
    ),
    'attributes' => 
    array (
      'deviceType' => 
      array (
        'type' => 'string',
        'default' => 'smartphone',
      ),
      'mediaId' => 
      array (
        'type' => 'number',
      ),
      'mediaUrl' => 
      array (
        'type' => 'string',
        'default' => '',
      ),
      'mediaType' => 
      array (
        'type' => 'string',
        'default' => 'image',
      ),
      'mediaAlt' => 
      array (
        'type' => 'string',
        'default' => '',
      ),
    ),
    'example' => 
    array (
      'attributes' => 
      array (
        'deviceType' => 'smartphone',
        'mediaUrl' => '',
      ),
    ),
    'supports' => 
    array (
      'html' => false,
      'align' => true,
      'spacing' => 
      array (
        'margin' => true,
        'padding' => true,
      ),
    ),
    'textdomain' => 'g2rd',
    'editorScript' => 'file:./index.js',
    'editorStyle' => 'file:./index.css',
    'style' => 'file:./style-index.css',
  ),
  'FilterableGrid' => 
  array (
    '$schema' => 'https://schemas.wp.org/trunk/block.json',
    'apiVersion' => 3,
    'name' => 'g2rd/filterable-grid',
    'version' => '1.0.0',
    'title' => 'G2RD Grille filtrable de contenus',
    'category' => 'g2rd-blocks',
    'description' => 'Affiche une grille dynamique de contenus avec onglets de filtrage par type, recherche instantanée et pagination.',
    'keywords' => 
    array (
      0 => 'grille',
      1 => 'filtre',
      2 => 'post type',
      3 => 'pagination',
      4 => 'search',
    ),
    'attributes' => 
    array (
      'selectedPostTypes' => 
      array (
        'type' => 'array',
        'items' => 
        array (
          'type' => 'string',
        ),
        'default' => 
        array (
          0 => 'post',
        ),
      ),
      'postsPerPage' => 
      array (
        'type' => 'number',
        'default' => 6,
      ),
      'showSearch' => 
      array (
        'type' => 'boolean',
        'default' => true,
      ),
      'showTaxonomyFilter' => 
      array (
        'type' => 'boolean',
        'default' => false,
      ),
      'taxonomy' => 
      array (
        'type' => 'string',
        'default' => 'category',
      ),
      'layoutColumns' => 
      array (
        'type' => 'number',
        'default' => 3,
      ),
      'cardDisplay' => 
      array (
        'type' => 'string',
        'default' => 'summary',
      ),
      'linkType' => 
      array (
        'type' => 'string',
        'default' => 'title',
      ),
      'readMoreText' => 
      array (
        'type' => 'string',
        'default' => 'Lire la suite',
      ),
      'excerptLength' => 
      array (
        'type' => 'number',
        'default' => 150,
      ),
      'blockId' => 
      array (
        'type' => 'string',
      ),
    ),
    'example' => 
    array (
      'attributes' => 
      array (
        'selectedPostTypes' => 
        array (
          0 => 'post',
          1 => 'page',
        ),
        'showSearch' => true,
        'layoutColumns' => 3,
      ),
    ),
    'supports' => 
    array (
      'html' => false,
      'align' => 
      array (
        0 => 'wide',
        1 => 'full',
      ),
      'color' => 
      array (
        'text' => true,
        'background' => true,
      ),
      'typography' => 
      array (
        'fontSize' => true,
      ),
      'spacing' => 
      array (
        'margin' => true,
        'padding' => true,
      ),
    ),
    'textdomain' => 'g2rd',
    'editorScript' => 'file:./index.js',
    'editorStyle' => 'file:./index.css',
    'style' => 'file:./style-index.css',
    'viewScript' => 'file:./view.js',
  ),
  'IconBox' => 
  array (
    '$schema' => 'https://schemas.wp.org/trunk/block.json',
    'apiVersion' => 3,
    'name' => 'g2rd/icon-box',
    'version' => '1.0.0',
    'title' => 'G2RD Icône',
    'category' => 'g2rd-blocks',
    'description' => 'Affiche une icône prédéfinie, une image ou du SVG personnalisé. Deux styles (standard et emballé). Conteneur cliquable possible. Options de conception complètes (couleurs, espacement, effets).',
    'keywords' => 
    array (
      0 => 'icon',
      1 => 'icône',
      2 => 'svg',
      3 => 'image',
      4 => 'bouton',
    ),
    'attributes' => 
    array (
      'iconType' => 
      array (
        'type' => 'string',
        'default' => 'dashicon',
      ),
      'iconValue' => 
      array (
        'type' => 'string',
        'default' => 'star-filled',
      ),
      'iconId' => 
      array (
        'type' => 'number',
      ),
      'iconUrl' => 
      array (
        'type' => 'string',
        'default' => '',
      ),
      'iconAlt' => 
      array (
        'type' => 'string',
        'default' => '',
      ),
      'svgCode' => 
      array (
        'type' => 'string',
        'default' => '',
      ),
      'styleVariant' => 
      array (
        'type' => 'string',
        'default' => 'standard',
      ),
      'iconSize' => 
      array (
        'type' => 'number',
        'default' => 48,
      ),
      'iconColor' => 
      array (
        'type' => 'string',
        'default' => '',
      ),
      'linkUrl' => 
      array (
        'type' => 'string',
        'default' => '',
      ),
      'linkOpenInNewTab' => 
      array (
        'type' => 'boolean',
        'default' => false,
      ),
    ),
    'example' => 
    array (
      'attributes' => 
      array (
        'iconType' => 'dashicon',
        'iconValue' => 'star-filled',
        'styleVariant' => 'wrapped',
      ),
    ),
    'supports' => 
    array (
      'html' => false,
      'align' => true,
      'color' => 
      array (
        'background' => true,
        'text' => true,
      ),
      'spacing' => 
      array (
        'margin' => true,
        'padding' => true,
      ),
      'typography' => false,
    ),
    'textdomain' => 'g2rd',
    'editorScript' => 'file:./index.js',
    'editorStyle' => 'file:./index.css',
    'style' => 'file:./style-index.css',
  ),
  'Map' => 
  array (
    '$schema' => 'https://schemas.wp.org/trunk/block.json',
    'apiVersion' => 3,
    'name' => 'g2rd/map',
    'version' => '1.0.0',
    'title' => 'G2RD Carte',
    'category' => 'g2rd-blocks',
    'description' => 'Carte Google Maps (clé API) ou OpenStreetMap (sans clé). Taille, zoom, style. Marqueurs illimités avec titre, description et icône personnalisée (SVG, image ou bibliothèque).',
    'keywords' => 
    array (
      0 => 'map',
      1 => 'carte',
      2 => 'google',
      3 => 'openstreetmap',
      4 => 'marker',
      5 => 'marqueur',
    ),
    'attributes' => 
    array (
      'mapProvider' => 
      array (
        'type' => 'string',
        'default' => 'osm',
      ),
      'apiKey' => 
      array (
        'type' => 'string',
        'default' => '',
      ),
      'centerLat' => 
      array (
        'type' => 'number',
        'default' => 48.8566,
      ),
      'centerLng' => 
      array (
        'type' => 'number',
        'default' => 2.3522,
      ),
      'zoom' => 
      array (
        'type' => 'number',
        'default' => 12,
      ),
      'height' => 
      array (
        'type' => 'number',
        'default' => 400,
      ),
      'mapStyle' => 
      array (
        'type' => 'string',
        'default' => 'roadmap',
      ),
      'markers' => 
      array (
        'type' => 'array',
        'default' => 
        array (
        ),
      ),
    ),
    'example' => 
    array (
      'attributes' => 
      array (
        'mapProvider' => 'osm',
        'zoom' => 14,
        'height' => 300,
      ),
    ),
    'supports' => 
    array (
      'html' => false,
      'align' => 
      array (
        0 => 'wide',
        1 => 'full',
      ),
      'spacing' => 
      array (
        'margin' => true,
        'padding' => false,
      ),
    ),
    'textdomain' => 'g2rd',
    'editorScript' => 'file:./index.js',
    'editorStyle' => 'file:./index.css',
    'style' => 'file:./style-index.css',
    'viewScript' => 'file:./view.js',
  ),
  'Marquee' => 
  array (
    '$schema' => 'https://schemas.wp.org/trunk/block.json',
    'apiVersion' => 3,
    'name' => 'g2rd/marquee',
    'version' => '1.0.0',
    'title' => 'G2RD Marquee',
    'category' => 'g2rd-blocks',
    'description' => 'Bloc de texte défilant en boucle infinie.',
    'attributes' => 
    array (
      'direction' => 
      array (
        'type' => 'string',
        'default' => 'left',
      ),
      'speed' => 
      array (
        'type' => 'number',
        'default' => 30,
      ),
      'gap' => 
      array (
        'type' => 'string',
        'default' => '40px',
      ),
      'pauseOnHover' => 
      array (
        'type' => 'boolean',
        'default' => true,
      ),
    ),
    'example' => 
    array (
      'attributes' => 
      array (
        'direction' => 'left',
        'speed' => 30,
        'gap' => '40px',
        'pauseOnHover' => true,
      ),
    ),
    'supports' => 
    array (
      'align' => 
      array (
        0 => 'wide',
        1 => 'full',
      ),
      'spacing' => 
      array (
        'margin' => true,
        'padding' => true,
      ),
      'color' => 
      array (
        'text' => true,
        'background' => true,
      ),
    ),
    'textdomain' => 'g2rd',
    'editorScript' => 'file:./index.js',
    'editorStyle' => 'file:./index.css',
    'style' => 'file:./style-index.css',
    'viewScript' => 'file:./view.js',
  ),
  'Modal' => 
  array (
    '$schema' => 'https://schemas.wp.org/trunk/block.json',
    'apiVersion' => 3,
    'name' => 'g2rd/modal',
    'version' => '1.0.0',
    'title' => 'G2RD Modal',
    'category' => 'g2rd-blocks',
    'description' => 'Ouvre une fenêtre modale au clic sur un bouton, un texte ou une image.',
    'keywords' => 
    array (
      0 => 'modal',
      1 => 'popup',
      2 => 'fenêtre',
      3 => 'bouton',
    ),
    'attributes' => 
    array (
      'triggerType' => 
      array (
        'type' => 'string',
        'default' => 'button',
      ),
      'triggerButtonText' => 
      array (
        'type' => 'string',
        'default' => 'Ouvrir',
      ),
      'triggerText' => 
      array (
        'type' => 'string',
        'default' => '',
      ),
      'triggerImageId' => 
      array (
        'type' => 'number',
      ),
      'triggerImageUrl' => 
      array (
        'type' => 'string',
        'default' => '',
      ),
      'triggerImageAlt' => 
      array (
        'type' => 'string',
        'default' => '',
      ),
      'triggerButtonBackground' => 
      array (
        'type' => 'string',
      ),
      'triggerButtonTextColor' => 
      array (
        'type' => 'string',
      ),
      'modalWidth' => 
      array (
        'type' => 'string',
        'default' => 'medium',
      ),
      'blockId' => 
      array (
        'type' => 'string',
      ),
    ),
    'example' => 
    array (
      'attributes' => 
      array (
        'triggerType' => 'button',
        'triggerButtonText' => 'Ouvrir',
      ),
    ),
    'supports' => 
    array (
      'html' => false,
      'align' => true,
      'color' => 
      array (
        'background' => true,
        'text' => true,
      ),
      'spacing' => 
      array (
        'margin' => true,
        'padding' => true,
      ),
    ),
    'textdomain' => 'g2rd',
    'editorScript' => 'file:./index.js',
    'editorStyle' => 'file:./index.css',
    'style' => 'file:./style-index.css',
    'viewScript' => 'file:./view.js',
  ),
  'ProgressBar' => 
  array (
    '$schema' => 'https://schemas.wp.org/trunk/block.json',
    'apiVersion' => 3,
    'name' => 'g2rd/progress-bar',
    'version' => '1.0.0',
    'title' => 'G2RD Barre de progression',
    'category' => 'g2rd-blocks',
    'description' => 'Affiche une barre de progression (linéaire ou circulaire). Utile pour notes d’avis, pourcentages. Valeur fixe ou dynamique depuis un champ de métadonnées (contexte Requête). Design modifiable.',
    'keywords' => 
    array (
      0 => 'progress',
      1 => 'bar',
      2 => 'progression',
      3 => 'note',
      4 => 'rating',
      5 => 'pourcentage',
    ),
    'attributes' => 
    array (
      'value' => 
      array (
        'type' => 'number',
        'default' => 75,
      ),
      'max' => 
      array (
        'type' => 'number',
        'default' => 100,
      ),
      'label' => 
      array (
        'type' => 'string',
        'default' => '',
      ),
      'styleVariant' => 
      array (
        'type' => 'string',
        'default' => 'bar',
      ),
      'showPercentage' => 
      array (
        'type' => 'boolean',
        'default' => true,
      ),
      'valueSource' => 
      array (
        'type' => 'string',
        'default' => 'static',
      ),
      'valueMetaKey' => 
      array (
        'type' => 'string',
        'default' => '',
      ),
      'barColor' => 
      array (
        'type' => 'string',
        'default' => '',
      ),
      'trackColor' => 
      array (
        'type' => 'string',
        'default' => '',
      ),
    ),
    'example' => 
    array (
      'attributes' => 
      array (
        'value' => 85,
        'styleVariant' => 'circle',
        'label' => 'Satisfaction',
      ),
    ),
    'supports' => 
    array (
      'html' => false,
      'align' => true,
      'color' => 
      array (
        'background' => true,
        'text' => true,
      ),
      'spacing' => 
      array (
        'margin' => true,
        'padding' => true,
      ),
    ),
    'textdomain' => 'g2rd',
    'render' => 'file:./render.php',
    'editorScript' => 'file:./index.js',
    'editorStyle' => 'file:./index.css',
    'style' => 'file:./style-index.css',
  ),
  'ShareButtons' => 
  array (
    '$schema' => 'https://schemas.wp.org/trunk/block.json',
    'apiVersion' => 3,
    'name' => 'g2rd/share-buttons',
    'version' => '1.0.0',
    'title' => 'G2RD Boutons de partage',
    'category' => 'g2rd-blocks',
    'description' => 'Boutons de partage social (Facebook, Twitter, LinkedIn, etc.). Utilisable dans les articles et les blocs de requête. Styles : arrondi, icônes simples, boutons avec étiquettes. Affichage horizontal ou vertical.',
    'keywords' => 
    array (
      0 => 'share',
      1 => 'partage',
      2 => 'social',
      3 => 'facebook',
      4 => 'twitter',
      5 => 'linkedin',
    ),
    'attributes' => 
    array (
      'shares' => 
      array (
        'type' => 'array',
        'default' => 
        array (
          0 => 
          array (
            'network' => 'facebook',
            'enabled' => true,
            'label' => 'Facebook',
          ),
          1 => 
          array (
            'network' => 'twitter',
            'enabled' => true,
            'label' => 'X (Twitter)',
          ),
          2 => 
          array (
            'network' => 'linkedin',
            'enabled' => true,
            'label' => 'LinkedIn',
          ),
          3 => 
          array (
            'network' => 'pinterest',
            'enabled' => false,
            'label' => 'Pinterest',
          ),
          4 => 
          array (
            'network' => 'whatsapp',
            'enabled' => false,
            'label' => 'WhatsApp',
          ),
          5 => 
          array (
            'network' => 'email',
            'enabled' => true,
            'label' => 'Email',
          ),
        ),
      ),
      'styleVariant' => 
      array (
        'type' => 'string',
        'default' => 'rounded',
      ),
      'layout' => 
      array (
        'type' => 'string',
        'default' => 'horizontal',
      ),
      'iconSize' => 
      array (
        'type' => 'number',
        'default' => 24,
      ),
    ),
    'example' => 
    array (
      'attributes' => 
      array (
        'styleVariant' => 'rounded',
        'layout' => 'horizontal',
      ),
    ),
    'supports' => 
    array (
      'html' => false,
      'align' => true,
      'color' => 
      array (
        'background' => true,
        'text' => true,
      ),
      'spacing' => 
      array (
        'margin' => true,
        'padding' => true,
      ),
    ),
    'textdomain' => 'g2rd',
    'render' => 'file:./render.php',
    'editorScript' => 'file:./index.js',
    'editorStyle' => 'file:./index.css',
    'style' => 'file:./style-index.css',
  ),
  'Slider' => 
  array (
    '$schema' => 'https://schemas.wp.org/trunk/block.json',
    'apiVersion' => 3,
    'name' => 'g2rd/slider',
    'version' => '1.0.0',
    'title' => 'G2RD Slider',
    'category' => 'g2rd-blocks',
    'description' => 'Slider avec images et texte. Ajoutez des groupes de blocs (images, paragraphes, etc.) et configurez transitions, pagination et flèches.',
    'keywords' => 
    array (
      0 => 'slider',
      1 => 'carousel',
      2 => 'diaporama',
      3 => 'images',
      4 => 'slides',
    ),
    'attributes' => 
    array (
      'transitionEffect' => 
      array (
        'type' => 'string',
        'default' => 'slide',
      ),
      'transitionDuration' => 
      array (
        'type' => 'number',
        'default' => 500,
      ),
      'showPagination' => 
      array (
        'type' => 'boolean',
        'default' => true,
      ),
      'paginationStyle' => 
      array (
        'type' => 'string',
        'default' => 'dots',
      ),
      'paginationPosition' => 
      array (
        'type' => 'string',
        'default' => 'bottom',
      ),
      'showArrows' => 
      array (
        'type' => 'boolean',
        'default' => true,
      ),
      'arrowType' => 
      array (
        'type' => 'string',
        'default' => 'chevron',
      ),
      'arrowPosition' => 
      array (
        'type' => 'string',
        'default' => 'sides',
      ),
      'autoplay' => 
      array (
        'type' => 'boolean',
        'default' => false,
      ),
      'autoplayInterval' => 
      array (
        'type' => 'number',
        'default' => 5000,
      ),
      'loop' => 
      array (
        'type' => 'boolean',
        'default' => true,
      ),
      'pauseOnHover' => 
      array (
        'type' => 'boolean',
        'default' => true,
      ),
      'blockId' => 
      array (
        'type' => 'string',
      ),
    ),
    'example' => 
    array (
      'attributes' => 
      array (
        'transitionEffect' => 'slide',
        'showPagination' => true,
        'showArrows' => true,
      ),
    ),
    'supports' => 
    array (
      'html' => false,
      'align' => 
      array (
        0 => 'wide',
        1 => 'full',
      ),
      'color' => 
      array (
        'background' => true,
        'text' => true,
      ),
      'spacing' => 
      array (
        'margin' => true,
        'padding' => true,
      ),
    ),
    'textdomain' => 'g2rd',
    'editorScript' => 'file:./index.js',
    'editorStyle' => 'file:./index.css',
    'style' => 'file:./style-index.css',
    'viewScript' => 'file:./view.js',
  ),
  'SlidingPanel' => 
  array (
    '$schema' => 'https://schemas.wp.org/trunk/block.json',
    'apiVersion' => 3,
    'name' => 'g2rd/sliding-panel',
    'version' => '1.0.0',
    'title' => 'G2RD Panneau coulissant',
    'category' => 'g2rd-blocks',
    'description' => 'Panneau coulissant ou fenêtre contextuelle : déclencheur (bouton, texte, image) et contenu en InnerBlocks. Idéal pour menus mobiles, méga menus, infobulles, popovers. Position : gauche, droite, haut, bas. Avec ou sans fond (overlay).',
    'keywords' => 
    array (
      0 => 'panel',
      1 => 'panneau',
      2 => 'coulissant',
      3 => 'slide',
      4 => 'popup',
      5 => 'drawer',
      6 => 'menu',
      7 => 'offcanvas',
    ),
    'attributes' => 
    array (
      'triggerType' => 
      array (
        'type' => 'string',
        'default' => 'button',
      ),
      'triggerButtonText' => 
      array (
        'type' => 'string',
        'default' => 'Ouvrir le panneau',
      ),
      'triggerText' => 
      array (
        'type' => 'string',
        'default' => '',
      ),
      'triggerImageId' => 
      array (
        'type' => 'number',
      ),
      'triggerImageUrl' => 
      array (
        'type' => 'string',
        'default' => '',
      ),
      'triggerImageAlt' => 
      array (
        'type' => 'string',
        'default' => '',
      ),
      'triggerButtonBackground' => 
      array (
        'type' => 'string',
      ),
      'triggerButtonTextColor' => 
      array (
        'type' => 'string',
      ),
      'panelPosition' => 
      array (
        'type' => 'string',
        'default' => 'right',
      ),
      'overlay' => 
      array (
        'type' => 'boolean',
        'default' => true,
      ),
      'showCloseButton' => 
      array (
        'type' => 'boolean',
        'default' => true,
      ),
      'closeOnOverlayClick' => 
      array (
        'type' => 'boolean',
        'default' => true,
      ),
      'panelSize' => 
      array (
        'type' => 'string',
        'default' => 'medium',
      ),
      'animationDuration' => 
      array (
        'type' => 'number',
        'default' => 300,
      ),
      'blockId' => 
      array (
        'type' => 'string',
      ),
    ),
    'example' => 
    array (
      'attributes' => 
      array (
        'triggerButtonText' => 'Menu',
        'panelPosition' => 'right',
        'overlay' => true,
      ),
    ),
    'supports' => 
    array (
      'html' => false,
      'align' => true,
      'color' => 
      array (
        'background' => true,
        'text' => true,
      ),
      'spacing' => 
      array (
        'margin' => true,
        'padding' => true,
      ),
    ),
    'textdomain' => 'g2rd',
    'editorScript' => 'file:./index.js',
    'editorStyle' => 'file:./index.css',
    'style' => 'file:./style-index.css',
    'viewScript' => 'file:./view.js',
  ),
  'TableOfContents' => 
  array (
    '$schema' => 'https://schemas.wp.org/trunk/block.json',
    'apiVersion' => 3,
    'name' => 'g2rd/table-of-contents',
    'version' => '1.0.0',
    'title' => 'G2RD Table des matières',
    'category' => 'g2rd-blocks',
    'description' => 'Sommaire interactif généré automatiquement à partir des titres du contenu pour faciliter la lecture et la navigation.',
    'keywords' => 
    array (
      0 => 'sommaire',
      1 => 'table des matières',
      2 => 'navigation',
      3 => 'titres',
      4 => 'toc',
    ),
    'attributes' => 
    array (
      'title' => 
      array (
        'type' => 'string',
        'default' => 'Sommaire',
      ),
      'headingLevels' => 
      array (
        'type' => 'string',
        'default' => '2,3,4',
      ),
      'listStyle' => 
      array (
        'type' => 'string',
        'default' => 'bullet',
      ),
      'blockId' => 
      array (
        'type' => 'string',
      ),
    ),
    'example' => 
    array (
      'attributes' => 
      array (
        'title' => 'Sommaire',
      ),
    ),
    'supports' => 
    array (
      'html' => false,
      'align' => true,
      'color' => 
      array (
        'text' => true,
        'background' => true,
      ),
      'typography' => 
      array (
        'fontSize' => true,
      ),
      'spacing' => 
      array (
        'margin' => true,
        'padding' => true,
      ),
    ),
    'textdomain' => 'g2rd',
    'editorScript' => 'file:./index.js',
    'editorStyle' => 'file:./index.css',
    'style' => 'file:./style-index.css',
    'viewScript' => 'file:./view.js',
  ),
  'ToggleContent' => 
  array (
    '$schema' => 'https://schemas.wp.org/trunk/block.json',
    'apiVersion' => 3,
    'name' => 'g2rd/toggle-content',
    'version' => '1.0.0',
    'title' => 'G2RD Toggle Content',
    'category' => 'g2rd-blocks',
    'description' => 'Un bloc avec un interrupteur pour afficher l\'un ou l\'autre de deux groupes de contenu.',
    'keywords' => 
    array (
      0 => 'toggle',
      1 => 'switch',
      2 => 'content',
      3 => 'interrupteur',
    ),
    'attributes' => 
    array (
      'showLeft' => 
      array (
        'type' => 'boolean',
        'default' => true,
      ),
      'toggleAlign' => 
      array (
        'type' => 'string',
        'default' => 'center',
      ),
      'toggleStyle' => 
      array (
        'type' => 'string',
        'default' => 'default',
      ),
      'toggleColorActive' => 
      array (
        'type' => 'string',
      ),
      'toggleColorInactive' => 
      array (
        'type' => 'string',
      ),
      'blockId' => 
      array (
        'type' => 'string',
      ),
    ),
    'example' => 
    array (
      'attributes' => 
      array (
        'showLeft' => true,
        'toggleAlign' => 'center',
        'toggleStyle' => 'rounded',
      ),
    ),
    'supports' => 
    array (
      'html' => false,
      'align' => true,
      'color' => 
      array (
        'background' => true,
        'text' => true,
      ),
      'spacing' => 
      array (
        'margin' => true,
        'padding' => true,
      ),
    ),
    'textdomain' => 'g2rd',
    'editorScript' => 'file:./index.js',
    'editorStyle' => 'file:./index.css',
    'style' => 'file:./style-index.css',
  ),
  'Toolbars' => 
  array (
    '$schema' => 'https://schemas.wp.org/trunk/block.json',
    'apiVersion' => 3,
    'name' => 'g2rd/toolbars',
    'version' => '1.0.0',
    'title' => 'G2RD Toolbars',
    'category' => 'g2rd-blocks',
    'description' => 'Un bloc d\'alerte avec plusieurs styles.',
    'keywords' => 
    array (
      0 => 'attention',
      1 => 'alerte',
      2 => 'conseil',
      3 => 'éviter',
      4 => 'succès',
    ),
    'attributes' => 
    array (
      'type' => 
      array (
        'type' => 'string',
        'default' => 'advice',
      ),
    ),
    'example' => 
    array (
      'attributes' => 
      array (
        'type' => 'warning',
      ),
    ),
    'supports' => 
    array (
      'html' => false,
    ),
    'textdomain' => 'g2rd',
    'editorScript' => 'file:./index.js',
    'editorStyle' => 'file:./index.css',
    'style' => 'file:./style-index.css',
  ),
  'g2rd' => 
  array (
    '$schema' => 'https://schemas.wp.org/trunk/block.json',
    'apiVersion' => 3,
    'name' => 'g2rd/bases',
    'version' => '1.0.0',
    'title' => 'Blocs de base G2RD',
    'category' => 'g2rd-blocks',
    'description' => 'Bloc de base pour G2RD',
    'example' => 
    array (
    ),
    'supports' => 
    array (
      'align' => 
      array (
        0 => 'wide',
        1 => 'full',
      ),
      'color' => 
      array (
        'background' => true,
        'text' => true,
        'link' => true,
        'button' => true,
        'heading' => true,
        'gradients' => true,
      ),
      'spacing' => 
      array (
        'padding' => true,
        'margin' => true,
        'blockGap' => true,
      ),
      'typography' => 
      array (
        'fontSize' => true,
        'lineHeight' => true,
        'textAlign' => true,
      ),
      'background' => 
      array (
        'backgroundImage' => true,
        'backgroundSize' => true,
      ),
      'dimensions' => 
      array (
        'minHeight' => true,
      ),
      'position' => 
      array (
        'sticky' => true,
      ),
      'shadow' => true,
      'multiple' => true,
      'anchor' => true,
      'customClassName' => false,
      'className' => true,
      'html' => true,
      'reusable' => true,
      'renaming' => true,
    ),
    'attributes' => 
    array (
      'content' => 
      array (
        'type' => 'string',
        'source' => 'html',
        'selector' => 'p',
        'default' => 'Hello World',
        'role' => 'content',
      ),
    ),
    'textdomain' => 'g2rd',
    'editorScript' => 'file:./index.js',
    'editorStyle' => 'file:./index.css',
    'style' => 'file:./style-index.css',
  ),
);
