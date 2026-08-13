<?php
/**
 * Rendu du bloc g2rd/route-map.
 *
 * Le GPX est analysé **côté serveur** et mis en cache : le navigateur ne reçoit
 * qu'un tracé simplifié et un profil altimétrique déjà dessiné en SVG. Parser
 * un GPX de plusieurs milliers de points dans le navigateur coûterait plusieurs
 * centaines de millisecondes sur un mobile d'entrée de gamme.
 *
 * Cartographie : Leaflet + tuiles OpenStreetMap, auto-hébergé, sans clé d'API
 * ni cookie tiers. L'analyse du fichier vit dans `G2RD\RouteMapSupport` — un
 * render.php ne déclare pas de fonction globale.
 *
 * @package G2RD
 * @since   1.36.0
 *
 * @var array    $attributes Attributs du bloc.
 * @var string   $content    Contenu interne (inutilisé).
 * @var WP_Block $block      Instance du bloc.
 */

defined( 'ABSPATH' ) || exit;

$etat = $attributes['etatValidation'] ?? 'publie';

/* ── État « en cours de validation » ─────────────────────────────────────
   Tant qu'un tracé n'est pas validé, le bloc affiche un message d'attente
   plutôt qu'une carte fausse. */
if ( 'attente' === $etat ) {
	$wrapper = get_block_wrapper_attributes( [ 'class' => 'g2rd-route-map g2rd-route-map--attente' ] );
	?>
	<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
		<p class="g2rd-route-map__attente">
			<?php echo esc_html( (string) ( $attributes['messageAttente'] ?? '' ) ); ?>
		</p>
	</div>
	<?php
	return;
}

$gpx_id = (int) ( $attributes['gpxId'] ?? 0 );

if ( ! $gpx_id ) {
	return;
}

$trace = \G2RD\RouteMapSupport::parse_gpx( $gpx_id );

if ( null === $trace ) {
	if ( current_user_can( 'edit_pages' ) ) {
		echo '<p class="g2rd-route-map__erreur">'
			. esc_html__( 'Le fichier GPX est introuvable ou illisible. Vérifiez le média sélectionné.', 'g2rd' )
			. '</p>';
	}
	return;
}

$hauteur    = max( 240, (int) ( $attributes['hauteur'] ?? 460 ) );
$zoom       = (int) ( $attributes['zoom'] ?? 15 );
$points_int = isset( $attributes['points'] ) && is_array( $attributes['points'] ) ? $attributes['points'] : [];
$titre_a11y = trim( (string) ( $attributes['titreAccessible'] ?? '' ) );
$gpx_url    = wp_get_attachment_url( $gpx_id );
$id_unique  = 'g2rd-route-map-' . wp_unique_id();

/* Les libellés partent d'ici : view.js n'embarque aucune chaîne de langue, et
   tout ce qui s'affiche sur la carte reste traduisible et paramétrable. */
$donnees = [
	'trace'  => $trace['points'],
	'zoom'   => $zoom,
	'depart' => [
		'titre' => wp_strip_all_tags( (string) ( $attributes['titreDepart'] ?? __( 'Départ et arrivée', 'g2rd' ) ) ),
		'texte' => wp_strip_all_tags( (string) ( $attributes['texteDepart'] ?? '' ) ),
	],
	'i18n'   => [
		'point'       => __( 'Point remarquable', 'g2rd' ),
		'attribution' => sprintf(
			/* translators: %s : lien vers la page de licence d'OpenStreetMap. */
			__( '© contributeurs %s', 'g2rd' ),
			'<a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
		),
	],
	'points' => array_values(
		array_filter(
			array_map(
				static function ( $p ) {
					if ( ! is_array( $p ) || ! isset( $p['lat'], $p['lng'] ) ) {
						return null;
					}
					return [
						'lat'   => (float) $p['lat'],
						'lng'   => (float) $p['lng'],
						'titre' => wp_strip_all_tags( (string) ( $p['titre'] ?? '' ) ),
						'texte' => wp_strip_all_tags( (string) ( $p['texte'] ?? '' ) ),
						'type'  => sanitize_key( (string) ( $p['type'] ?? 'repere' ) ),
					];
				},
				$points_int
			)
		)
	),
];

$wrapper = get_block_wrapper_attributes( [ 'class' => 'g2rd-route-map' ] );
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>

	<?php if ( '' !== $titre_a11y ) : ?>
		<h2 class="screen-reader-text"><?php echo esc_html( $titre_a11y ); ?></h2>
	<?php endif; ?>

	<div
		id="<?php echo esc_attr( $id_unique ); ?>"
		class="g2rd-route-map__carte"
		style="--g2rd-route-map-hauteur:<?php echo esc_attr( (string) $hauteur ); ?>px"
		role="region"
		aria-label="<?php esc_attr_e( 'Carte interactive du parcours. Les caractéristiques du tracé sont également décrites en texte ci-dessous.', 'g2rd' ); ?>"
		data-g2rd-route-map="<?php echo esc_attr( wp_json_encode( $donnees ) ); ?>">
		<noscript>
			<p class="g2rd-route-map__noscript">
				<?php esc_html_e( 'La carte interactive nécessite JavaScript. Le parcours est décrit ci-dessous, et la trace GPX reste téléchargeable.', 'g2rd' ); ?>
			</p>
		</noscript>
	</div>

	<?php if ( ! empty( $attributes['afficherStats'] ) ) : ?>
		<ul class="g2rd-route-map__stats" role="list">
			<li><span><?php esc_html_e( 'Distance', 'g2rd' ); ?></span><strong><?php echo esc_html( number_format_i18n( $trace['stats']['distance'], 2 ) ); ?> km</strong></li>
			<li><span><?php esc_html_e( 'Dénivelé positif', 'g2rd' ); ?></span><strong><?php echo esc_html( (string) $trace['stats']['denivele'] ); ?> m</strong></li>
			<li><span><?php esc_html_e( 'Altitude min.', 'g2rd' ); ?></span><strong><?php echo esc_html( (string) $trace['stats']['alt_min'] ); ?> m</strong></li>
			<li><span><?php esc_html_e( 'Altitude max.', 'g2rd' ); ?></span><strong><?php echo esc_html( (string) $trace['stats']['alt_max'] ); ?> m</strong></li>
		</ul>
	<?php endif; ?>

	<?php
	if ( ! empty( $attributes['afficherProfil'] ) && count( $trace['profil'] ) > 1 ) :
		$profil    = $trace['profil'];
		$d_max     = max( 1.0, (float) end( $profil )[0] );
		$e_min     = min( array_column( $profil, 1 ) );
		$e_max     = max( array_column( $profil, 1 ) );
		$amplitude = max( 1.0, $e_max - $e_min );

		$l = 1000;
		$h = 160;

		$coords = [];
		foreach ( $profil as [ $d, $e ] ) {
			$x        = round( $d / $d_max * $l, 1 );
			$y        = round( $h - ( ( $e - $e_min ) / $amplitude ) * ( $h - 12 ) - 6, 1 );
			$coords[] = $x . ',' . $y;
		}
		$ligne = implode( ' ', $coords );
		$aire  = '0,' . $h . ' ' . $ligne . ' ' . $l . ',' . $h;
		?>
		<figure class="g2rd-route-map__profil">
			<svg viewBox="0 0 <?php echo esc_attr( (string) $l ); ?> <?php echo esc_attr( (string) $h ); ?>"
				preserveAspectRatio="none" role="img"
				aria-label="<?php
				printf(
					/* translators: 1: distance, 2: dénivelé, 3: altitude min, 4: altitude max. */
					esc_attr__( 'Profil altimétrique du parcours : %1$s kilomètres, %2$s mètres de dénivelé positif, entre %3$s et %4$s mètres d’altitude.', 'g2rd' ),
					esc_attr( number_format_i18n( $trace['stats']['distance'], 2 ) ),
					esc_attr( (string) $trace['stats']['denivele'] ),
					esc_attr( (string) $trace['stats']['alt_min'] ),
					esc_attr( (string) $trace['stats']['alt_max'] )
				);
				?>">
				<polygon class="g2rd-route-map__aire" points="<?php echo esc_attr( $aire ); ?>" />
				<polyline class="g2rd-route-map__ligne" points="<?php echo esc_attr( $ligne ); ?>" />
			</svg>
			<figcaption>
				<?php
				printf(
					/* translators: 1: altitude min, 2: altitude max, 3: dénivelé positif. */
					esc_html__( 'Profil altimétrique — de %1$s à %2$s m, %3$s m de dénivelé positif.', 'g2rd' ),
					esc_html( (string) $trace['stats']['alt_min'] ),
					esc_html( (string) $trace['stats']['alt_max'] ),
					esc_html( (string) $trace['stats']['denivele'] )
				);
				?>
			</figcaption>
		</figure>
	<?php endif; ?>

	<?php if ( ! empty( $attributes['afficherTelechargements'] ) ) : ?>
		<p class="g2rd-route-map__telechargements">
			<?php if ( $gpx_url ) : ?>
				<a class="wp-element-button" href="<?php echo esc_url( $gpx_url ); ?>" download>
					<?php esc_html_e( 'Télécharger la trace GPX', 'g2rd' ); ?>
				</a>
			<?php endif; ?>
			<?php if ( ! empty( $attributes['urlKml'] ) ) : ?>
				<a class="wp-element-button is-style-outline" href="<?php echo esc_url( (string) $attributes['urlKml'] ); ?>" download>
					<?php esc_html_e( 'KML', 'g2rd' ); ?>
				</a>
			<?php endif; ?>
			<?php if ( ! empty( $attributes['urlPlanPdf'] ) ) : ?>
				<a class="wp-element-button is-style-outline" href="<?php echo esc_url( (string) $attributes['urlPlanPdf'] ); ?>" download>
					<?php esc_html_e( 'Plan imprimable (PDF)', 'g2rd' ); ?>
				</a>
			<?php endif; ?>
		</p>
		<p class="g2rd-route-map__compat">
			<?php esc_html_e( 'Le format GPX est lisible par la plupart des montres et applications de sport.', 'g2rd' ); ?>
		</p>
	<?php endif; ?>
</div>
