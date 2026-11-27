<?php
/**
 * Mesure d'audience et des conversions (GA4, Google Ads), avec consentement.
 *
 * Rien n'est chargé tant qu'aucun identifiant n'est saisi dans
 * Réglages › Général › « GlassAuto Vernon — mesure des conversions ».
 *
 * - Mode consentement Google v2 : tout est refusé par défaut, le bandeau
 *   accorde ou refuse, le choix est gardé 6 mois (cookie ga_consent).
 * - Conversions suivies : clic sur un lien tel:, demande de rendez-vous en
 *   ligne enregistrée (événement g2rd-bc:booked de Business Connect), envoi
 *   réussi d'un formulaire Fluent Forms.
 * - Un lien vers #cookies (pied de page, politique de cookies) rouvre le bandeau.
 *
 * Désactivable : add_filter( 'glassauto_tracking_enabled', '__return_false' ).
 *
 * @package G2RD_GlassAuto
 */

declare( strict_types = 1 );

namespace G2RD_GlassAuto;

\defined( 'ABSPATH' ) || exit;

const OPTION_TRACKING = 'g2rd_glassauto_tracking';

/**
 * Réglages de suivi, assainis.
 *
 * @return array<string,string>
 */
function tracking(): array {
	$defaults = array(
		'ga4_id'        => '',
		'ads_id'        => '',
		'label_tel'     => '',
		'label_rdv'     => '',
		'label_contact' => '',
	);
	$saved    = \get_option( OPTION_TRACKING, array() );
	return \array_merge( $defaults, \is_array( $saved ) ? \array_map( 'strval', $saved ) : array() );
}

/**
 * Le suivi est-il actif sur cette page ?
 */
function tracking_active(): bool {
	$t = tracking();
	if ( '' === $t['ga4_id'] && '' === $t['ads_id'] ) {
		return false;
	}
	if ( \is_admin() || \is_customize_preview() || \wp_is_json_request() ) {
		return false;
	}
	// Pas de mesure pour l'équipe connectée (admin, responsable, apporteurs).
	if ( \is_user_logged_in() ) {
		return false;
	}
	return (bool) \apply_filters( 'glassauto_tracking_enabled', true );
}

/**
 * Configuration injectée dans la page.
 *
 * @return array<string,mixed>
 */
function tracking_config(): array {
	$t    = tracking();
	$send = static fn( string $label ): string => ( '' !== $t['ads_id'] && '' !== $label ) ? $t['ads_id'] . '/' . $label : '';
	return array(
		'ga4'         => $t['ga4_id'],
		'ads'         => $t['ads_id'],
		'conversions' => array(
			'tel'     => $send( $t['label_tel'] ),
			'rdv'     => $send( $t['label_rdv'] ),
			'contact' => $send( $t['label_contact'] ),
		),
		'policy'      => \home_url( '/politique-de-cookies/' ),
	);
}

\add_action(
	'wp_head',
	static function (): void {
		if ( ! tracking_active() ) {
			return;
		}
		$cfg = tracking_config();
		$id  = '' !== $cfg['ga4'] ? $cfg['ga4'] : $cfg['ads'];
		// Consentement refusé par défaut, avant toute balise Google.
		echo "<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}"
			. "gtag('consent','default',{ad_storage:'denied',ad_user_data:'denied',ad_personalization:'denied',analytics_storage:'denied',wait_for_update:500});"
			. "(function(){var m=document.cookie.match(/(?:^|; )ga_consent=(granted|denied)/);if(m&&m[1]==='granted'){gtag('consent','update',{ad_storage:'granted',ad_user_data:'granted',ad_personalization:'granted',analytics_storage:'granted'});}})();"
			. "gtag('js',new Date());"
			. ( '' !== $cfg['ga4'] ? "gtag('config'," . \wp_json_encode( $cfg['ga4'] ) . ');' : '' )
			. ( '' !== $cfg['ads'] ? "gtag('config'," . \wp_json_encode( $cfg['ads'] ) . ');' : '' )
			. "</script>\n";
		\printf( '<script async src="%s"></script>' . "\n", \esc_url( 'https://www.googletagmanager.com/gtag/js?id=' . \rawurlencode( $id ) ) );
	},
	1
);

\add_action(
	'wp_footer',
	static function (): void {
		if ( ! tracking_active() ) {
			return;
		}
		$cfg = tracking_config();
		?>
<div class="ga-consent" id="ga-consent" role="region" aria-label="<?php \esc_attr_e( 'Choix des cookies', 'g2rd-glassauto-child' ); ?>" hidden>
	<p class="ga-consent__text"><?php \esc_html_e( 'Nous mesurons la fréquentation du site et l\'efficacité de nos annonces avec Google, uniquement si vous l\'acceptez.', 'g2rd-glassauto-child' ); ?> <a href="<?php echo \esc_url( $cfg['policy'] ); ?>"><?php \esc_html_e( 'Politique de cookies', 'g2rd-glassauto-child' ); ?></a></p>
	<div class="ga-consent__actions">
		<button type="button" class="ga-consent__btn ga-consent__btn--ghost" data-consent="denied"><?php \esc_html_e( 'Refuser', 'g2rd-glassauto-child' ); ?></button>
		<button type="button" class="ga-consent__btn" data-consent="granted"><?php \esc_html_e( 'Accepter', 'g2rd-glassauto-child' ); ?></button>
	</div>
</div>
<script>
( function () {
	var CFG = <?php echo \wp_json_encode( $cfg ); ?>;
	var box = document.getElementById( 'ga-consent' );
	function choice() { var m = document.cookie.match( /(?:^|; )ga_consent=(granted|denied)/ ); return m ? m[1] : ''; }
	function save( v ) {
		document.cookie = 'ga_consent=' + v + '; max-age=' + ( 182 * 86400 ) + '; path=/; SameSite=Lax' + ( location.protocol === 'https:' ? '; Secure' : '' );
		var g = v === 'granted' ? 'granted' : 'denied';
		gtag( 'consent', 'update', { ad_storage: g, ad_user_data: g, ad_personalization: g, analytics_storage: g } );
		box.hidden = true;
	}
	if ( ! choice() ) { box.hidden = false; }
	box.addEventListener( 'click', function ( e ) { var b = e.target.closest( '[data-consent]' ); if ( b ) { save( b.getAttribute( 'data-consent' ) ); } } );
	document.addEventListener( 'click', function ( e ) {
		var a = e.target.closest( 'a' );
		if ( ! a ) { return; }
		if ( a.getAttribute( 'href' ) === '#cookies' ) { e.preventDefault(); box.hidden = false; var f = box.querySelector( 'button' ); if ( f ) { f.focus(); } return; }
		if ( /^tel:/i.test( a.getAttribute( 'href' ) || '' ) ) { conv( 'tel', 'clic_telephone' ); }
	} );
	function conv( type, name ) {
		var params = { event_category: 'conversion', page_location: location.href };
		if ( CFG.ga4 ) { gtag( 'event', name, params ); }
		if ( CFG.conversions[ type ] ) { gtag( 'event', 'conversion', { send_to: CFG.conversions[ type ] } ); }
	}
	document.addEventListener( 'g2rd-bc:booked', function () { conv( 'rdv', 'demande_rendez_vous' ); } );
	if ( window.jQuery ) {
		window.jQuery( document ).on( 'fluentform_submission_success', function () {
			conv( document.querySelector( '.ga-form--rdv form, form[data-ga-form="rdv"]' ) ? 'rdv' : 'contact', 'envoi_formulaire' );
		} );
	}
} )();
</script>
		<?php
	},
	50
);

// Réglages › Général › mesure des conversions.

\add_action(
	'admin_init',
	static function (): void {
		\register_setting(
			'general',
			OPTION_TRACKING,
			array(
				'type'              => 'array',
				'sanitize_callback' => __NAMESPACE__ . '\sanitize_tracking',
				'default'           => array(),
			)
		);
		\add_settings_section(
			'glassauto_tracking',
			\__( 'GlassAuto Vernon — mesure des conversions', 'g2rd-glassauto-child' ),
			static function (): void {
				echo '<p>' . \esc_html__( 'Laisser vide pour ne rien charger. Le bandeau de consentement s\'affiche dès qu\'un identifiant est saisi. Libellés de conversion : Google Ads › Objectifs › Conversions › Configuration de la balise.', 'g2rd-glassauto-child' ) . '</p>';
			},
			'general'
		);
		$champs = array(
			'ga4_id'        => array( \__( 'Google Analytics 4 (G-XXXXXXX)', 'g2rd-glassauto-child' ), 'G-XXXXXXXXXX' ),
			'ads_id'        => array( \__( 'Google Ads (AW-XXXXXXXXX)', 'g2rd-glassauto-child' ), 'AW-123456789' ),
			'label_tel'     => array( \__( 'Libellé conversion « Appel (clic tel:) »', 'g2rd-glassauto-child' ), 'AbCdEfGhIj' ),
			'label_rdv'     => array( \__( 'Libellé conversion « Demande de rendez-vous »', 'g2rd-glassauto-child' ), 'AbCdEfGhIj' ),
			'label_contact' => array( \__( 'Libellé conversion « Formulaire de contact »', 'g2rd-glassauto-child' ), 'AbCdEfGhIj' ),
		);
		foreach ( $champs as $cle => [ $label, $exemple ] ) {
			\add_settings_field(
				'glassauto_tracking_' . $cle,
				$label,
				static function () use ( $cle, $exemple ): void {
					\printf(
						'<input type="text" id="glassauto_tracking_%1$s" name="%2$s[%1$s]" value="%3$s" class="regular-text" placeholder="%4$s">',
						\esc_attr( $cle ),
						\esc_attr( OPTION_TRACKING ),
						\esc_attr( tracking()[ $cle ] ),
						\esc_attr( $exemple )
					);
				},
				'general',
				'glassauto_tracking',
				array( 'label_for' => 'glassauto_tracking_' . $cle )
			);
		}
	}
);

/**
 * Assainit les réglages de suivi (formats Google stricts).
 *
 * @param mixed $raw Valeur soumise.
 * @return array<string,string>
 */
function sanitize_tracking( $raw ): array {
	$raw   = \is_array( $raw ) ? $raw : array();
	$clean = array();
	$ga4   = \strtoupper( \trim( (string) ( $raw['ga4_id'] ?? '' ) ) );
	$ads   = \strtoupper( \trim( (string) ( $raw['ads_id'] ?? '' ) ) );
	$clean['ga4_id'] = \preg_match( '/^G-[A-Z0-9]{4,20}$/', $ga4 ) ? $ga4 : '';
	$clean['ads_id'] = \preg_match( '/^AW-\d{6,15}$/', $ads ) ? $ads : '';
	foreach ( array( 'label_tel', 'label_rdv', 'label_contact' ) as $cle ) {
		$v             = \trim( (string) ( $raw[ $cle ] ?? '' ) );
		$clean[ $cle ] = \preg_match( '/^[A-Za-z0-9_-]{4,40}$/', $v ) ? $v : '';
	}
	return $clean;
}
