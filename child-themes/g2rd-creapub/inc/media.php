<?php
/**
 * Visuels du site actuel crea-pub-communication.fr.
 *
 * L'installateur les importe une seule fois dans la médiathèque (repérés par
 * la méta `_creapub_source`). Un fichier présent dans assets/img/ du thème est
 * utilisé en priorité ; sinon WordPress le télécharge depuis le site actuel.
 * Les compositions appellent media_url( 'clé' ) : URL de la médiathèque si le
 * fichier est importé, URL du site actuel sinon (aperçu dans l'inserteur).
 *
 * @package G2RD_CreaPub
 */

declare( strict_types = 1 );

namespace G2RD_CreaPub;

\defined( 'ABSPATH' ) || exit;

const SOURCE_SITE = 'https://www.crea-pub-communication.fr/';

/**
 * Plan des visuels : clé → [chemin sur le site actuel, texte alternatif].
 *
 * @return array<string,array{0:string,1:string}>
 */
function media_map(): array {
	$map = array(
		'logo'          => array( 'images/creapub2-01.png', 'Créa Pub Communication' ),
		'ico-print'     => array( 'images/creapubnn-28.png', '' ),
		'ico-signa'     => array( 'images/creapubnn-25.png', '' ),
		'ico-vehicule'  => array( 'images/creapubnn-24.png', '' ),
		'ico-textile'   => array( 'images/creapubnn-27.png', '' ),
		'ico-goodies'   => array( 'images/creapubnn-23.png', '' ),
		'video-logo'    => array( 'videos/site-logo-animer.mp4', 'Logo Créa Pub animé' ),
		'brochure'      => array( 'images/brochure.pdf', 'Brochure Créa Pub Communication' ),
		'p-hexis'       => array( 'images/partenaire5.png', 'Logo Hexis, partenaire de Créa Pub' ),
		'p-orafol'      => array( 'images/partenaire2.svg.png', 'Logo Orafol, partenaire de Créa Pub' ),
		'p-avery'       => array( 'images/partenaire_avery.png', 'Logo Avery Dennison, partenaire de Créa Pub' ),
		'p-orajet'      => array( 'images/partenaire_orajet.png', 'Logo Orajet, partenaire de Créa Pub' ),
		'p-clique'      => array( 'images/partenaire6.png', 'Logo Clique, partenaire de Créa Pub' ),
		'p-sols'        => array( 'images/partenaire3.png', "Logo Sol's, partenaire de Créa Pub" ),
		'p-projob'      => array( 'images/partenaire22.jpg', 'Logo Projob, partenaire de Créa Pub' ),
		'p-craft'       => array( 'images/partenaire9.png', 'Logo Craft, partenaire de Créa Pub' ),
		'p-cutter'      => array( 'images/partenaire8.png.webp', 'Logo Cutter & Buck, partenaire de Créa Pub' ),
	);
	for ( $i = 1; $i <= 16; $i++ ) {
		$map[ 'client-' . $i ] = array( 'images/logo' . $i . ( 4 === $i ? '.jpg' : '.png' ), 'Logo client de Créa Pub Communication' );
	}
	return $map + photos_map();
}

/**
 * Photos du site actuel (redimensionnées à l'import, 1920 px maximum).
 *
 * @return array<string,array{0:string,1:string}>
 */
function photos_map(): array {
	$map = array(
		'zebre'          => array( 'images/zebre.png', '' ),
		'agence'         => array( 'images/agence.jpg', "Façade de l'agence Créa Pub Communication, 56 avenue de Rouen à Vernon" ),
		'team-banner'    => array( 'images/team.jpg', "L'équipe Créa Pub Communication" ),
		'team-cathie'    => array( 'images/cathie.PNG', 'Cathie, dirigeante de Créa Pub Communication' ),
		'team-baptiste'  => array( 'images/Baptiste.png', 'Baptiste, commercial chez Créa Pub Communication' ),
		'team-leslie'    => array( 'images/leslie.jpg', 'Leslie, graphiste chez Créa Pub Communication' ),
		'team-mathieu'   => array( 'images/mathieu.jpg', 'Mathieu, graphiste et poseur chez Créa Pub Communication' ),
		'team-adrien'    => array( 'images/adrien.png', 'Adrien, graphiste chez Créa Pub Communication' ),
		'atelier-1'      => array( 'images/img1.png', "Façade de l'atelier Créa Pub côté Seine" ),
		'atelier-2'      => array( 'images/img3.png', "L'atelier Créa Pub vu depuis les quais de Seine" ),
		'atelier-3'      => array( 'images/img4.png', "Entrée de l'atelier Créa Pub, rue Pierre Maubert" ),
		'video-textile'  => array( 'videos/Textile.mp4', 'Flocage textile : broderie et DTF à l’atelier' ),
		'video-vehicule' => array( 'videos/vehicule.mp4', 'Technique de pose d’un marquage véhicule' ),
	);
	foreach ( gallery_files() as $prefix => $files ) {
		foreach ( $files as $i => $file ) {
			$map[ $prefix . '-' . ( $i + 1 ) ] = array( 'images/' . $file, gallery_alt( $prefix, $i + 1 ) );
		}
	}
	return $map;
}

/**
 * Galeries « Nos réalisations » du site actuel, dans l'ordre d'affichage.
 *
 * @return array<string,string[]>
 */
function gallery_files(): array {
	return array(
		'print'    => array( 'print1.JPG', 'print2.jpg', 'print3.jpg', 'print4.jpg', 'print5.jpg', 'print6.jpg', 'print9.JPG', 'print7.jpg', 'print8.jpg', 'print10.JPG', 'print11.JPG', 'print12.JPG' ),
		'signa'    => array( 'signaletique1.jpg', 'signaletique2.jpg', 'signaletique3.jpg', 'signaletique4.jpg', 'signaletique5.jpg', 'signaletique6.jpg', 'signaletique7.jpg', 'signaletique8.jpg', 'signaletique9.JPG', 'signaletique10.jpg', 'signaletique11.jpg', 'signaletique12.jpg' ),
		'vehicule' => array( 'vehicule1.jpg', 'vehicule2.jpg', 'vehicule3.jpg', 'vehicule4.jpg', 'vehicule5.jpg', 'vehicule6.jpg', 'vehicule7.jpg', 'vehicule8.jpg', 'vehicule9.jpg' ),
		'textile'  => array( 'textile1.jpg', 'textile2.jpg', 'textile3.jpg', 'textile4.jpg', 'textile5.jpg', 'textile6.jpg', 'textile7.jpg', 'textile8.jpg', 'textile9.jpg' ),
		'goodies'  => array( 'goodies1.jpg', 'goodies2.jpg', 'goodies3.jpg', 'goodies4.jpg', 'goodies5.jpg', 'goodies6.jpg', 'goodies7.jpg', 'goodies8.jpg', 'goodies9.jpg', 'goodies10.JPG', 'goodies11.JPG', 'goodies12.png' ),
	);
}

/**
 * Texte alternatif d'une photo de réalisation.
 *
 * @param string $prefix Galerie.
 * @param int    $n      Numéro.
 * @return string
 */
function gallery_alt( string $prefix, int $n ): string {
	$labels = array(
		'print'    => 'Réalisation print Créa Pub à Vernon',
		'signa'    => 'Réalisation signalétique et enseigne Créa Pub',
		'vehicule' => 'Marquage véhicule réalisé par Créa Pub',
		'textile'  => 'Textile personnalisé par Créa Pub',
		'goodies'  => 'Objets publicitaires personnalisés par Créa Pub',
	);
	return ( $labels[ $prefix ] ?? 'Réalisation Créa Pub' ) . ' (' . $n . ')';
}

/**
 * Bloc image Gutenberg d'un visuel : identifiant de médiathèque, taille,
 * agrandissement au clic natif (lightbox) si demandé.
 *
 * @param string $key      Clé du plan.
 * @param string $class    Classe CSS du bloc.
 * @param string $size     Taille (large, full…).
 * @param bool   $lightbox Agrandissement au clic.
 * @param string $alt      Texte alternatif (par défaut celui du plan).
 * @return string
 */
function image_block( string $key, string $class = '', string $size = 'large', bool $lightbox = false, ?string $alt = null ): string {
	$id    = media_id( $key );
	$src   = media_url( $key, $size );
	$alt   = null === $alt ? media_alt( $key ) : $alt;
	$attrs = array();
	if ( $id ) {
		$attrs['id'] = $id;
	}
	$attrs['sizeSlug']        = $size;
	$attrs['linkDestination'] = 'none';
	if ( $lightbox ) {
		$attrs['lightbox'] = array( 'enabled' => true );
	}
	if ( '' !== $class ) {
		$attrs['className'] = $class;
	}
	$classes = \trim( 'wp-block-image size-' . $size . ' ' . $class );
	return \sprintf(
		'<!-- wp:image %1$s -->' . "\n" . '<figure class="%2$s"><img src="%3$s" alt="%4$s"%5$s/></figure>' . "\n" . '<!-- /wp:image -->',
		\wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
		\esc_attr( $classes ),
		\esc_url( $src ),
		\esc_attr( $alt ),
		$id ? ' class="wp-image-' . $id . '"' : ''
	);
}

/**
 * Identifiant de pièce jointe d'un visuel importé, ou 0.
 *
 * @param string $key Clé du plan.
 * @return int
 */
function media_id( string $key, ?int $set = null ): int {
	static $cache = array();
	if ( null !== $set ) {
		$cache[ $key ] = $set;
		return $set;
	}
	if ( ! empty( $cache[ $key ] ) ) {
		return $cache[ $key ];
	}
	$ids           = \get_posts(
		array(
			'post_type'   => 'attachment',
			'post_status' => 'inherit',
			'numberposts' => 1,
			'fields'      => 'ids',
			'meta_key'    => '_creapub_source', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'  => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
	$cache[ $key ] = (int) ( $ids[0] ?? 0 );
	return $cache[ $key ];
}

/**
 * Mémorise l'identifiant d'un visuel qui vient d'être importé.
 *
 * @param string $key Clé du plan.
 * @param int    $id  Identifiant de la pièce jointe.
 */
function media_id_reset( string $key, int $id ): void {
	media_id( $key, $id );
}

/**
 * URL d'un visuel : médiathèque si importé, site actuel sinon.
 *
 * @param string $key Clé du plan.
 * @return string
 */
function media_url( string $key, string $size = 'full' ): string {
	$id = media_id( $key );
	if ( $id ) {
		$url = 'full' === $size ? \wp_get_attachment_url( $id ) : \wp_get_attachment_image_url( $id, $size );
		if ( ! $url ) {
			$url = \wp_get_attachment_url( $id );
		}
		if ( $url ) {
			return (string) $url;
		}
	}
	$map = media_map();
	return isset( $map[ $key ] ) ? SOURCE_SITE . $map[ $key ][0] : '';
}

/**
 * Texte alternatif d'un visuel.
 *
 * @param string $key Clé du plan.
 * @return string
 */
function media_alt( string $key ): string {
	$map = media_map();
	return $map[ $key ][1] ?? '';
}

/**
 * Importe les visuels manquants dans la médiathèque, par lots.
 *
 * Aucune miniature n'est générée pendant l'import (logos et icônes n'en ont
 * pas besoin) : chaque fichier est une simple copie. L'import s'arrête après
 * $budget secondes ; la page d'installation relance alors la suite toute seule.
 *
 * @param int $budget Durée maximale en secondes.
 * @return array<string,string> clé → état. Clé « _restant » si l'import n'est pas fini.
 */
function install_media( int $budget = 20 ): array {
	$start = \microtime( true );
	// Le site actuel (o2switch) limite les requêtes : identification navigateur + pause entre deux fichiers.
	$ua = static function ( array $args ): array {
		$args['user-agent'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36';
		$args['headers']    = \array_merge( (array) ( $args['headers'] ?? array() ), array( 'Accept' => 'image/avif,image/webp,image/*,video/*,application/pdf,*/*;q=0.8' ) );
		return $args;
	};
	\add_filter( 'http_request_args', $ua, 99 );
	\add_filter( 'intermediate_image_sizes_advanced', '__return_empty_array', 99 );
	\add_filter( 'big_image_size_threshold', '__return_false', 99 );

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	if ( \function_exists( 'set_time_limit' ) ) {
		@\set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}

	$report    = array();
	$pending   = 0;
	$throttled = false;
	$tries     = (int) \get_option( 'g2rd_creapub_media_tries', 0 );
	$photos    = photos_map();
	foreach ( media_map() as $key => $entry ) {
		if ( media_id( $key ) ) {
			$report[ $key ] = 'existant';
			continue;
		}
		if ( \microtime( true ) - $start > $budget ) {
			break;
		}

		$is_photo = isset( $photos[ $key ] );
		$sizes    = $is_photo
			? static function () { return \array_intersect_key( \wp_get_registered_image_subsizes(), array( 'large' => 1, 'medium' => 1 ) ); }
			: '__return_empty_array';
		$limit    = $is_photo ? static function () { return 1920; } : '__return_false';
		\add_filter( 'intermediate_image_sizes_advanced', $sizes, 100 );
		\add_filter( 'big_image_size_threshold', $limit, 100 );

		$name  = \basename( $entry[0] );
		$local = \get_stylesheet_directory() . '/assets/img/' . $name;
		if ( \is_readable( $local ) ) {
			$tmp = \wp_tempnam( $name );
			\copy( $local, $tmp );
		} else {
			\usleep( 1200000 );
			$tmp = \download_url( SOURCE_SITE . $entry[0], 0 === \strpos( $entry[0], 'videos/' ) ? 240 : 90 );
		}
		if ( \is_wp_error( $tmp ) ) {
			\remove_filter( 'intermediate_image_sizes_advanced', $sizes, 100 );
			\remove_filter( 'big_image_size_threshold', $limit, 100 );
			// Blocage ou erreur réseau : retenté au lot suivant (6 lots au maximum).
			$report[ $key ] = 'en attente : ' . $tmp->get_error_message();
			if ( $tries < 6 ) {
				$throttled = true;
				if ( false !== \stripos( $tmp->get_error_message(), 'Too Many' ) ) {
					break;
				}
			}
			continue;
		}

		// « partenaire8.png.webp » → extension réelle, sinon WordPress refuse le type.
		$clean = \preg_replace( '/\.(png|svg)\.(webp|png)$/i', '.$2', $name );
		$id    = \media_handle_sideload(
			array(
				'name'     => 'creapub-' . $clean,
				'tmp_name' => $tmp,
			),
			0,
			$entry[1]
		);
		if ( \is_wp_error( $id ) ) {
			\remove_filter( 'intermediate_image_sizes_advanced', $sizes, 100 );
			\remove_filter( 'big_image_size_threshold', $limit, 100 );
			@\unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.unlink_unlink
			$report[ $key ] = 'erreur : ' . $id->get_error_message();
			continue;
		}
		\update_post_meta( (int) $id, '_creapub_source', $key );
		media_id_reset( $key, (int) $id );
		if ( '' !== $entry[1] && \wp_attachment_is_image( (int) $id ) ) {
			\update_post_meta( (int) $id, '_wp_attachment_image_alt', $entry[1] );
		}
		$report[ $key ] = 'importé';
		\remove_filter( 'intermediate_image_sizes_advanced', $sizes, 100 );
		\remove_filter( 'big_image_size_threshold', $limit, 100 );

		// Logo du site (header, footer) : posé dès son import.
		if ( 'logo' === $key && ! (int) \get_option( 'site_logo' ) ) {
			\update_option( 'site_logo', (int) $id );
			\set_theme_mod( 'custom_logo', (int) $id );
			$report['logo-du-site'] = 'défini';
		}
	}

	\remove_filter( 'intermediate_image_sizes_advanced', '__return_empty_array', 99 );
	\remove_filter( 'big_image_size_threshold', '__return_false', 99 );
	\remove_filter( 'http_request_args', $ua, 99 );

	// Restants : non tentés (lot interrompu) ou en échec avec encore des essais.
	foreach ( media_map() as $key => $entry ) {
		if ( media_id( $key ) ) {
			continue;
		}
		if ( ! isset( $report[ $key ] ) || ( $tries < 6 && 0 === \strpos( $report[ $key ], 'en attente' ) ) ) {
			++$pending;
		}
	}
	\update_option( 'g2rd_creapub_media_tries', $throttled ? $tries + 1 : 0, false );
	if ( $pending ) {
		$report['_restant'] = (string) $pending;
		if ( $throttled ) {
			$report['_pause'] = '1';
		}
	}
	return $report;
}

/**
 * Icônes du méga-menu et de la bande des prestations : variables CSS posées
 * en ligne, le contenu ne contient aucune URL d'image.
 */
\add_action(
	'wp_enqueue_scripts',
	function (): void {
		$icons = array(
			'print'             => 'ico-print',
			'signaletique'      => 'ico-signa',
			'marquage-vehicule' => 'ico-vehicule',
			'textile'           => 'ico-textile',
			'goodies'           => 'ico-goodies',
		);
		$css   = \sprintf(
			':root{--cp-zebre:url("%1$s");--cp-agence:url("%2$s")}',
			\esc_url_raw( media_url( 'zebre', 'large' ) ),
			\esc_url_raw( media_url( 'agence', 'large' ) )
		);
		foreach ( $icons as $slug => $key ) {
			$css .= \sprintf( '.creapub-menu-%1$s{--cp-menu-ico:url("%2$s")}', $slug, \esc_url_raw( media_url( $key ) ) );
		}
		\wp_add_inline_style( 'g2rd-creapub', $css );
	},
	30
);

/**
 * Logo : tant qu'aucun logo n'est défini dans Réglages › Identité du site,
 * le bloc core/site-logo ne rend rien et le header perd son logo à gauche.
 * On affiche alors le logo du site actuel ; dès qu'un logo est défini,
 * WordPress reprend la main.
 *
 * @param string $html Rendu du bloc.
 * @return string
 */
\add_filter(
	'render_block_core/site-logo',
	static function ( $html ) {
		$html  = (string) $html;
		$label = \__( 'Créa Pub Communication, retour à l’accueil', 'g2rd-creapub' );
		if ( '' === \trim( $html ) ) {
			return \sprintf(
				'<div class="wp-block-site-logo"><a href="%1$s" class="custom-logo-link" rel="home" aria-label="%2$s"><img class="custom-logo" src="%3$s" alt="Créa Pub Communication" width="160" height="160"></a></div>',
				\esc_url( \home_url( '/' ) ),
				\esc_attr( $label ),
				\esc_url( media_url( 'logo' ) )
			);
		}
		return \str_replace( 'alt=""', 'alt="Créa Pub Communication"', $html );
	}
);
