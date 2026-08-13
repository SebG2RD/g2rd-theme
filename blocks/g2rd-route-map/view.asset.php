<?php
/**
 * Dépendances du script public du bloc g2rd/route-map.
 *
 * `leaflet` est enregistré par G2RD\RouteMapSupport et pointe sur la copie
 * auto-hébergée de assets/vendor/leaflet/ : aucun script tiers. La dépendance
 * déclarée ici est ce qui met Leaflet en file, et uniquement sur les pages où
 * le bloc est réellement présent.
 *
 * @package G2RD
 */

return [
	'dependencies' => [ 'leaflet' ],
	'version'      => '1.0.0',
];
