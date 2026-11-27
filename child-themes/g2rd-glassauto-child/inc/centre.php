<?php
/**
 * Informations du centre : une seule source de vérité.
 *
 * Adresse, téléphone, horaires, coordonnées GPS, liens de réservation et
 * identifiants des formulaires sont saisis dans Réglages › Général, section
 * « GlassAuto Vernon ». Les blocs dynamiques (horaires, coordonnées, carte) les
 * lisent, et les jetons {{tel}}, {{adresse}}, {{annee}}… les injectent dans les
 * paragraphes, titres, boutons et listes au rendu : aucun numéro de téléphone
 * n'est écrit en dur dans les pages.
 *
 * Valeurs par défaut : fiche officielle glassauto.fr/centre/glassauto-vernon
 * (relevée le 23/10/2026). Le samedi y est indiqué « Sur RDV / Fermé » : à
 * confirmer par le centre avant publication.
 *
 * @package G2RD_GlassAuto
 */

declare( strict_types = 1 );

namespace G2RD_GlassAuto;

\defined( 'ABSPATH' ) || exit;

const OPTION_CENTRE = 'g2rd_glassauto_centre';

/**
 * Jours de la semaine, dans l'ordre d'affichage.
 *
 * @return array<string,string> clé → libellé.
 */
function jours(): array {
	return array(
		'lundi'    => \__( 'Lundi', 'g2rd-glassauto-child' ),
		'mardi'    => \__( 'Mardi', 'g2rd-glassauto-child' ),
		'mercredi' => \__( 'Mercredi', 'g2rd-glassauto-child' ),
		'jeudi'    => \__( 'Jeudi', 'g2rd-glassauto-child' ),
		'vendredi' => \__( 'Vendredi', 'g2rd-glassauto-child' ),
		'samedi'   => \__( 'Samedi', 'g2rd-glassauto-child' ),
		'dimanche' => \__( 'Dimanche', 'g2rd-glassauto-child' ),
	);
}

/**
 * Valeurs par défaut du centre.
 *
 * @return array<string,mixed>
 */
function centre_defaults(): array {
	$semaine = '8 h 30 – 12 h · 13 h 30 – 18 h';
	return array(
		'nom'              => 'GlassAuto Vernon',
		'rue'              => '1 chemin du Virolet',
		'cp'               => '27200',
		'ville'            => 'Vernon',
		'departement'      => 'Eure (27)',
		'region'           => 'Normandie',
		'tel'              => '02 32 21 06 09',
		'email'            => '',
		'lat'              => '49.09661',
		'lon'              => '1.46908',
		'horaires'         => array(
			'lundi'    => $semaine,
			'mardi'    => $semaine,
			'mercredi' => $semaine,
			'jeudi'    => $semaine,
			'vendredi' => $semaine,
			'samedi'   => \__( 'Sur rendez-vous', 'g2rd-glassauto-child' ),
			'dimanche' => \__( 'Fermé', 'g2rd-glassauto-child' ),
		),
		'horaires_court'   => \__( 'Lun–ven · 8 h 30 – 12 h / 13 h 30 – 18 h', 'g2rd-glassauto-child' ),
		'reservation_url'  => '',
		'avis_url'         => '',
		'place_id'         => '',
		'partenaires_url'  => '/espace-partenaires/',
		'form_rdv'         => '',
		'form_contact'     => '',
		'communes_exclues' => "Évreux\nLouviers\nMantes-la-Ville\nMantes-la-Jolie\nLimay\nBuchelay\nMagnanville",
	);
}

/**
 * Informations du centre, option fusionnée avec les valeurs par défaut.
 *
 * @return array<string,mixed>
 */
function centre(): array {
	static $centre = null;
	if ( null !== $centre ) {
		return $centre;
	}
	$defaults = centre_defaults();
	$saved    = \get_option( OPTION_CENTRE, array() );
	$saved    = \is_array( $saved ) ? $saved : array();

	$centre             = \array_merge( $defaults, \array_filter( $saved, static fn( $v ) => '' !== $v && null !== $v ) );
	$centre['horaires'] = \array_merge( $defaults['horaires'], \is_array( $saved['horaires'] ?? null ) ? \array_filter( $saved['horaires'] ) : array() );

	return (array) \apply_filters( 'glassauto_centre', $centre );
}

/**
 * Une valeur du centre.
 *
 * @param string $key Clé.
 * @return mixed
 */
function centre_get( string $key ) {
	return centre()[ $key ] ?? '';
}

/**
 * Lien téléphone au format international.
 *
 * @return string
 */
function tel_href(): string {
	$digits = \preg_replace( '/\D+/', '', (string) centre_get( 'tel' ) );
	if ( '' === $digits ) {
		return '';
	}
	if ( 0 === \strpos( $digits, '0' ) && 10 === \strlen( $digits ) ) {
		$digits = '33' . \substr( $digits, 1 );
	}
	return 'tel:+' . $digits;
}

/**
 * Adresse sur une ligne.
 *
 * @return string
 */
function adresse_ligne(): string {
	return \trim( centre_get( 'rue' ) . ', ' . centre_get( 'cp' ) . ' ' . centre_get( 'ville' ), ', ' );
}

/**
 * Lien d'itinéraire (OpenStreetMap, sans cookie tiers).
 *
 * @return string
 */
function itineraire_url(): string {
	return 'https://www.openstreetmap.org/directions?to=' . \rawurlencode( centre_get( 'lat' ) . ',' . centre_get( 'lon' ) );
}

/**
 * Adresse de l'espace partenaires, relative ou absolue.
 *
 * @return string
 */
function partenaires_url(): string {
	$url = (string) centre_get( 'partenaires_url' );
	return 0 === \strpos( $url, '/' ) ? \home_url( $url ) : $url;
}

/**
 * Jetons remplacés au rendu des blocs de texte.
 *
 * @return array<string,string>
 */
function jetons(): array {
	static $jetons = null;
	if ( null !== $jetons ) {
		return $jetons;
	}
	$c      = centre();
	$jetons = array(
		'{{annee}}'          => \wp_date( 'Y' ),
		'{{nom}}'            => \esc_html( (string) $c['nom'] ),
		'{{tel}}'            => \esc_html( (string) $c['tel'] ),
		'{{tel-lien}}'       => \esc_url( tel_href() ),
		'{{email}}'          => \esc_html( (string) $c['email'] ),
		'{{email-lien}}'     => '' !== $c['email'] ? 'mailto:' . \esc_attr( (string) $c['email'] ) : '',
		'{{rue}}'            => \esc_html( (string) $c['rue'] ),
		'{{cp}}'             => \esc_html( (string) $c['cp'] ),
		'{{ville}}'          => \esc_html( (string) $c['ville'] ),
		'{{adresse}}'        => \esc_html( adresse_ligne() ),
		'{{itineraire}}'     => \esc_url( itineraire_url() ),
		'{{horaires-court}}' => \esc_html( (string) $c['horaires_court'] ),
		'{{reservation}}'    => \esc_url( '' !== $c['reservation_url'] ? (string) $c['reservation_url'] : \home_url( '/prendre-rendez-vous/' ) ),
		'{{partenaires}}'    => \esc_url( partenaires_url() ),
		'{{avis}}'           => \esc_url( (string) $c['avis_url'] ),
	);
	return $jetons;
}

/**
 * Remplace les jetons dans un bloc rendu.
 *
 * @param string $content Rendu du bloc.
 * @return string
 */
function remplacer_jetons( $content ): string {
	$content = (string) $content;
	if ( false === \strpos( $content, '{{' ) ) {
		return $content;
	}
	return \strtr( $content, jetons() );
}
foreach ( array( 'paragraph', 'heading', 'button', 'list-item', 'navigation-link', 'details', 'quote' ) as $bloc ) {
	\add_filter( 'render_block_core/' . $bloc, __NAMESPACE__ . '\remplacer_jetons' );
}
\add_filter( 'render_block_g2rd/faq', __NAMESPACE__ . '\remplacer_jetons' );
\add_filter( 'render_block_g2rd/timeline', __NAMESPACE__ . '\remplacer_jetons' );

/**
 * G2RD Business Connect lit les coordonnées du centre ici : une seule saisie.
 *
 * @param array<string,mixed> $defaults Valeurs par défaut de l'extension.
 * @return array<string,mixed>
 */
function business_connect_defaults( $defaults ): array {
	$defaults                   = \is_array( $defaults ) ? $defaults : array();
	$defaults['centre_name']    = (string) centre_get( 'nom' );
	$defaults['centre_address'] = adresse_ligne();
	$defaults['centre_phone']   = (string) centre_get( 'tel' );
	return $defaults;
}
\add_filter( 'g2rd_bc_option_defaults', __NAMESPACE__ . '\\business_connect_defaults' );

// Réglages › Général › GlassAuto Vernon.

\add_action(
	'admin_init',
	function (): void {
		\register_setting(
			'general',
			OPTION_CENTRE,
			array(
				'type'              => 'array',
				'sanitize_callback' => __NAMESPACE__ . '\sanitize_centre',
				'default'           => array(),
			)
		);

		\add_settings_section(
			'glassauto_centre',
			\__( 'GlassAuto Vernon — informations du centre', 'g2rd-glassauto-child' ),
			static function (): void {
				echo '<p>' . \esc_html__( 'Ces informations alimentent les horaires, les coordonnées, la carte et les jetons {{tel}}, {{adresse}}, {{annee}}… des pages. Vide = valeur par défaut de la fiche officielle GlassAuto.', 'g2rd-glassauto-child' ) . '</p>';
			},
			'general'
		);

		$champs = array(
			'nom'             => array( \__( 'Nom du centre', 'g2rd-glassauto-child' ), 'text' ),
			'rue'             => array( \__( 'Adresse (rue)', 'g2rd-glassauto-child' ), 'text' ),
			'cp'              => array( \__( 'Code postal', 'g2rd-glassauto-child' ), 'text' ),
			'ville'           => array( \__( 'Ville', 'g2rd-glassauto-child' ), 'text' ),
			'tel'             => array( \__( 'Téléphone', 'g2rd-glassauto-child' ), 'text' ),
			'email'           => array( \__( 'E-mail public', 'g2rd-glassauto-child' ), 'email' ),
			'lat'             => array( \__( 'Latitude', 'g2rd-glassauto-child' ), 'text' ),
			'lon'             => array( \__( 'Longitude', 'g2rd-glassauto-child' ), 'text' ),
			'horaires_court'  => array( \__( 'Horaires en une ligne (en-tête)', 'g2rd-glassauto-child' ), 'text' ),
			'reservation_url' => array( \__( 'Lien de réservation du réseau GlassAuto (facultatif)', 'g2rd-glassauto-child' ), 'url' ),
			'avis_url'        => array( \__( 'Lien « Laisser un avis Google »', 'g2rd-glassauto-child' ), 'url' ),
			'place_id'        => array( \__( 'Google Place ID (avis Google)', 'g2rd-glassauto-child' ), 'text' ),
			'partenaires_url' => array( \__( 'Adresse de l\'espace partenaires', 'g2rd-glassauto-child' ), 'text' ),
			'form_rdv'        => array( \__( 'Formulaire Fluent Forms « Demande de rendez-vous » (ID)', 'g2rd-glassauto-child' ), 'number' ),
			'form_contact'    => array( \__( 'Formulaire Fluent Forms « Contact » (ID)', 'g2rd-glassauto-child' ), 'number' ),
		);
		foreach ( $champs as $cle => [ $label, $type ] ) {
			\add_settings_field(
				'glassauto_' . $cle,
				$label,
				static function () use ( $cle, $type ): void {
					$valeur = (string) ( ( \get_option( OPTION_CENTRE, array() )[ $cle ] ?? '' ) );
					\printf(
						'<input type="%1$s" id="glassauto_%2$s" name="%3$s[%2$s]" value="%4$s" class="regular-text" placeholder="%5$s">',
						\esc_attr( $type ),
						\esc_attr( $cle ),
						\esc_attr( OPTION_CENTRE ),
						\esc_attr( $valeur ),
						\esc_attr( (string) ( centre_defaults()[ $cle ] ?? '' ) )
					);
				},
				'general',
				'glassauto_centre',
				array( 'label_for' => 'glassauto_' . $cle )
			);
		}

		foreach ( jours() as $jour => $label ) {
			\add_settings_field(
				'glassauto_horaires_' . $jour,
				/* translators: %s : jour de la semaine. */
				\sprintf( \__( 'Horaires : %s', 'g2rd-glassauto-child' ), $label ),
				static function () use ( $jour ): void {
					$valeur = (string) ( \get_option( OPTION_CENTRE, array() )['horaires'][ $jour ] ?? '' );
					\printf(
						'<input type="text" id="glassauto_horaires_%1$s" name="%2$s[horaires][%1$s]" value="%3$s" class="regular-text" placeholder="%4$s">',
						\esc_attr( $jour ),
						\esc_attr( OPTION_CENTRE ),
						\esc_attr( $valeur ),
						\esc_attr( centre_defaults()['horaires'][ $jour ] )
					);
				},
				'general',
				'glassauto_centre',
				array( 'label_for' => 'glassauto_horaires_' . $jour )
			);
		}

		\add_settings_field(
			'glassauto_communes_exclues',
			\__( 'Communes retirées de la zone d\'intervention (une par ligne)', 'g2rd-glassauto-child' ),
			static function (): void {
				$valeur = (string) ( \get_option( OPTION_CENTRE, array() )['communes_exclues'] ?? centre_defaults()['communes_exclues'] );
				\printf(
					'<textarea id="glassauto_communes_exclues" name="%1$s[communes_exclues]" rows="6" class="regular-text">%2$s</textarea><p class="description">%3$s</p>',
					\esc_attr( OPTION_CENTRE ),
					\esc_textarea( $valeur ),
					\esc_html__( 'Par défaut : les communes déjà couvertes par un autre centre GlassAuto (Évreux, Louviers, Mantes-la-Ville) et l\'agglomération de Mantes, en attente de la décision du réseau.', 'g2rd-glassauto-child' )
				);
			},
			'general',
			'glassauto_centre',
			array( 'label_for' => 'glassauto_communes_exclues' )
		);
	}
);

/**
 * Assainit l'option du centre.
 *
 * @param mixed $raw Valeur soumise.
 * @return array<string,mixed>
 */
function sanitize_centre( $raw ): array {
	$raw   = \is_array( $raw ) ? $raw : array();
	$clean = array();
	$texte = array( 'nom', 'rue', 'cp', 'ville', 'tel', 'horaires_court', 'place_id', 'partenaires_url' );
	foreach ( $texte as $cle ) {
		$clean[ $cle ] = \sanitize_text_field( (string) ( $raw[ $cle ] ?? '' ) );
	}
	$clean['email'] = \sanitize_email( (string) ( $raw['email'] ?? '' ) );
	foreach ( array( 'reservation_url', 'avis_url' ) as $cle ) {
		$clean[ $cle ] = \esc_url_raw( (string) ( $raw[ $cle ] ?? '' ) );
	}
	foreach ( array( 'lat', 'lon' ) as $cle ) {
		$v             = \str_replace( ',', '.', (string) ( $raw[ $cle ] ?? '' ) );
		$clean[ $cle ] = \is_numeric( $v ) ? (string) (float) $v : '';
	}
	foreach ( array( 'form_rdv', 'form_contact' ) as $cle ) {
		$n             = \absint( $raw[ $cle ] ?? 0 );
		$clean[ $cle ] = $n > 0 ? (string) $n : '';
	}
	$clean['horaires'] = array();
	foreach ( \array_keys( jours() ) as $jour ) {
		$clean['horaires'][ $jour ] = \sanitize_text_field( (string) ( $raw['horaires'][ $jour ] ?? '' ) );
	}
	$lignes                    = \preg_split( '/\r\n|\r|\n/', (string) ( $raw['communes_exclues'] ?? '' ) );
	$lignes                    = \is_array( $lignes ) ? $lignes : array();
	$clean['communes_exclues'] = \implode( "\n", \array_filter( \array_map( 'sanitize_text_field', $lignes ) ) );
	return $clean;
}
