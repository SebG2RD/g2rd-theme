<?php
/**
 * G2RD — Rescue 18 : thème enfant de la refonte de rescue18.com.
 *
 * Rôles de ce fichier : déclarer les constantes et charger les modules (inc/).
 * Tout le spécifique au site vit ici ; le thème parent g2rd-theme reste intact.
 *
 *   inc/icons.php     Icônes SVG (générées par tools/icons.py)
 *   inc/setup.php     Charte, parent forcé, assets, logo, styles de blocs
 *   inc/content.php   Types de contenu et taxonomies métier (documents, textes, épisodes, quiz)
 *   inc/blocks.php    Blocs dynamiques rendus en PHP (bibliothèque, veille, quiz…)
 *   inc/sync.php      Cycle documentaire quotidien (WP-Cron, 1 passage par 24 h)
 *   inc/cli.php       Installateur WP-CLI : pages aux URL historiques, termes, menus
 *
 * @package G2RD_Rescue18
 * @license EUPL-1.2
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18;

\defined( 'ABSPATH' ) || exit;

const VERSION    = '0.6.38';
const TEXTDOMAIN = 'g2rd-rescue18-child';

require_once __DIR__ . '/inc/icons.php';
require_once __DIR__ . '/inc/setup.php';
require_once __DIR__ . '/inc/content.php';
require_once __DIR__ . '/inc/blocks.php';
require_once __DIR__ . '/inc/sync.php';
require_once __DIR__ . '/inc/install.php';
require_once __DIR__ . '/inc/compat.php';
require_once __DIR__ . '/inc/seo.php';

if ( \is_admin() || \wp_doing_ajax() ) {
	require_once __DIR__ . '/inc/import.php';
	require_once __DIR__ . '/inc/outils-admin.php';
}

if ( \defined( 'WP_CLI' ) && \WP_CLI ) {
	require_once __DIR__ . '/inc/cli.php';
}
