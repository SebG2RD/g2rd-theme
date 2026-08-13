<?php
/**
 * Rendu du bloc g2rd/timeline.
 *
 * Sémantique : une timeline est une suite ordonnée, elle sort donc en `<ol>`.
 * Le repère (heure, kilomètre, numéro d'étape) est porté par un `<time>` quand
 * c'est une heure, sinon par un `<span>` — un lecteur d'écran doit pouvoir
 * distinguer « 9h00 » d'un simple libellé.
 *
 * @package G2RD
 * @since   1.0.0
 *
 * @var array    $attributes Attributs du bloc.
 * @var string   $content    Contenu interne (inutilisé).
 * @var WP_Block $block      Instance du bloc.
 */

defined( 'ABSPATH' ) || exit;

$items = isset( $attributes['items'] ) && is_array( $attributes['items'] ) ? $attributes['items'] : [];

if ( empty( $items ) ) {
	return;
}

$orientation = ( 'horizontale' === ( $attributes['orientation'] ?? 'verticale' ) ) ? 'horizontale' : 'verticale';
$type_repere = sanitize_key( $attributes['repere'] ?? 'heure' );
$avec_axe    = ! empty( $attributes['afficherAxe'] );
$numeros     = ! empty( $attributes['afficherNumeros'] );
$titre_a11y  = trim( (string) ( $attributes['titreAccessible'] ?? '' ) );

$niveau = $attributes['niveauTitre'] ?? 'h3';
$niveau = in_array( $niveau, [ 'h2', 'h3', 'h4', 'p' ], true ) ? $niveau : 'h3';

$classes = [
	'g2rd-timeline',
	'g2rd-timeline--' . $orientation,
];

if ( $avec_axe ) {
	$classes[] = 'g2rd-timeline--axe';
}
if ( $numeros ) {
	$classes[] = 'g2rd-timeline--numerotee';
}

$wrapper = get_block_wrapper_attributes( [ 'class' => implode( ' ', $classes ) ] );

/**
 * Détermine si un repère est une heure exploitable en `datetime`.
 *
 * Accepte « 9h00 », « 9 h 00 », « 09:00 ». Renvoie la valeur ISO 8601 partielle
 * (« 09:00 ») ou une chaîne vide si le repère n'est pas une heure.
 *
 * @param string $repere Repère saisi.
 * @return string
 */
$to_datetime = static function ( string $repere ): string {
	if ( preg_match( '/^\s*(\d{1,2})\s*[h:]\s*(\d{2})?\s*$/i', $repere, $m ) ) {
		return sprintf( '%02d:%02d', (int) $m[1], isset( $m[2] ) ? (int) $m[2] : 0 );
	}
	return '';
};
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<?php if ( '' !== $titre_a11y ) : ?>
		<h2 class="screen-reader-text"><?php echo esc_html( $titre_a11y ); ?></h2>
	<?php endif; ?>

	<ol class="g2rd-timeline__liste" role="list">
		<?php
		foreach ( $items as $index => $item ) :
			$repere = trim( (string) ( $item['repere'] ?? '' ) );
			$titre  = trim( (string) ( $item['titre'] ?? '' ) );
			$texte  = trim( (string) ( $item['texte'] ?? '' ) );
			$lien   = trim( (string) ( $item['lien'] ?? '' ) );
			$fort   = ! empty( $item['fort'] );

			if ( '' === $titre && '' === $texte && '' === $repere ) {
				continue;
			}

			$datetime = ( 'heure' === $type_repere ) ? $to_datetime( $repere ) : '';
			?>
			<li class="g2rd-timeline__etape<?php echo $fort ? ' est-fort' : ''; ?>">

				<?php if ( $numeros ) : ?>
					<span class="g2rd-timeline__numero" aria-hidden="true"><?php echo esc_html( str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
				<?php endif; ?>

				<?php if ( '' !== $repere ) : ?>
					<?php if ( '' !== $datetime ) : ?>
						<time class="g2rd-timeline__repere" datetime="<?php echo esc_attr( $datetime ); ?>"><?php echo esc_html( $repere ); ?></time>
					<?php else : ?>
						<span class="g2rd-timeline__repere"><?php echo esc_html( $repere ); ?></span>
					<?php endif; ?>
				<?php endif; ?>

				<div class="g2rd-timeline__corps">
					<?php if ( '' !== $titre ) : ?>
						<<?php echo esc_html( $niveau ); ?> class="g2rd-timeline__titre">
							<?php if ( '' !== $lien ) : ?>
								<a href="<?php echo esc_url( $lien ); ?>"><?php echo esc_html( $titre ); ?></a>
							<?php else : ?>
								<?php echo esc_html( $titre ); ?>
							<?php endif; ?>
						</<?php echo esc_html( $niveau ); ?>>
					<?php endif; ?>

					<?php if ( '' !== $texte ) : ?>
						<p class="g2rd-timeline__texte"><?php echo wp_kses_post( $texte ); ?></p>
					<?php endif; ?>
				</div>
			</li>
		<?php endforeach; ?>
	</ol>
</div>
