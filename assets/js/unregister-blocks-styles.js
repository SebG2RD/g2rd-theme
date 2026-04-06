/**
 * Désactivation des styles de blocs
 *
 * Ce script gère la désactivation des styles par défaut
 * pour certains blocs WordPress.
 *
 * @package G2RD
 * @since 1.0.0
 * @license EUPL-1.2
 * @copyright (c) 2024 Sebastien GERARD
 * @link https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 */

wp.domReady(function () {
  wp.blocks.unregisterBlockStyle("core/button", "outline, fill");
  wp.blocks.unregisterBlockStyle("core/image", "rounded");
  wp.blocks.unregisterBlockStyle("core/core", "plain");
  wp.blocks.unregisterBlockStyle("core/site-logo", "rounded");
  wp.blocks.unregisterBlockStyle("core/separator", "dots");
  wp.blocks.unregisterBlockStyle("core/separator", "wide");
  wp.blocks.unregisterBlockStyle("core/social-link", "logos-only, pill-shape");
  wp.blocks.unregisterBlockStyle("core/tag-cloud", "outline");
});
