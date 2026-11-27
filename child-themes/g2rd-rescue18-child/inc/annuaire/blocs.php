<?php
/**
 * Blocs de l'annuaire, des actualités des SDIS, des offres et des concours.
 *
 * Rendu PHP serveur : la liste complète s'affiche sans JavaScript ; la carte
 * Leaflet (fond IGN) et les filtres l'enrichissent. Les images restent
 * hébergées par les sites officiels (chargement différé, sans référent,
 * visuel de repli si l'image ne répond pas).
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18;

use function G2RD_Rescue18\Annuaire\reglages as reglages_annuaire;
use function G2RD_Rescue18\Annuaire\service as service_par_id;
use function G2RD_Rescue18\Annuaire\services as tous_services;
use function G2RD_Rescue18\Annuaire\departements;
use function G2RD_Rescue18\Annuaire\nom_dep;
use function G2RD_Rescue18\Annuaire\region_dep;
use function G2RD_Rescue18\Annuaire\regions;

\defined( 'ABSPATH' ) || exit;

/**
 * Définitions des blocs (fusionnées dans block_definitions()).
 *
 * @return array<string,array<string,mixed>>
 */
function annuaire_block_definitions(): array {
	$var = static fn( string $d ): array => array(
		'string',
		$d,
		\__( 'Variante', 'g2rd-rescue18-child' ),
		array(
			'accueil' => \__( 'Aperçu (accueil, rubrique)', 'g2rd-rescue18-child' ),
			'page'    => \__( 'Page complète avec filtres', 'g2rd-rescue18-child' ),
		),
	);
	$ctx = array(
		'string',
		'tous',
		\__( 'Contenu', 'g2rd-rescue18-child' ),
		array(
			'tous'    => \__( 'Tous les services', 'g2rd-rescue18-child' ),
			'service' => \__( 'Service de la fiche affichée', 'g2rd-rescue18-child' ),
		),
	);
	return array(
		'carte-casernes' => array(
			'title'  => \__( 'Carte des casernes', 'g2rd-rescue18-child' ),
			'icon'   => 'location',
			'attrs'  => array(
				'portee' => array(
					'string',
					'france',
					\__( 'Carte', 'g2rd-rescue18-child' ),
					array(
						'france' => \__( 'France (SDIS, BSPP, BMPM)', 'g2rd-rescue18-child' ),
						'monde'  => \__( 'Monde entier', 'g2rd-rescue18-child' ),
					),
				),
			),
			'render' => 'render_carte_casernes',
		),
		'annuaire'       => array(
			'title'  => \__( 'Annuaire des SDIS (carte et liste)', 'g2rd-rescue18-child' ),
			'icon'   => 'location-alt',
			'attrs'  => array(
				'variante' => array(
					'string',
					'page',
					\__( 'Variante', 'g2rd-rescue18-child' ),
					array(
						'page'    => \__( 'Carte, recherche et liste', 'g2rd-rescue18-child' ),
						'accueil' => \__( 'Carte et recherche seules', 'g2rd-rescue18-child' ),
					),
				),
			),
			'render' => 'render_annuaire',
		),
		'service-entete' => array(
			'title'   => \__( 'Fiche SDIS · logo, visuel et boutons', 'g2rd-rescue18-child' ),
			'icon'    => 'id',
			'attrs'   => array(),
			'context' => array( 'postId', 'postType' ),
			'render'  => 'render_service_entete',
		),
		'service-infos'  => array(
			'title'   => \__( 'Fiche SDIS · coordonnées et carte', 'g2rd-rescue18-child' ),
			'icon'    => 'phone',
			'attrs'   => array(),
			'context' => array( 'postId', 'postType' ),
			'render'  => 'render_service_infos',
		),
		'fil-sdis'       => array(
			'title'   => \__( 'Fil des actualités des SDIS', 'g2rd-rescue18-child' ),
			'icon'    => 'megaphone',
			'attrs'   => array(
				'variante' => $var( 'accueil' ),
				'contexte' => $ctx,
				'filtre'   => array(
					'string',
					'tous',
					\__( 'Articles', 'g2rd-rescue18-child' ),
					array(
						'tous'        => \__( 'Toutes les actualités', 'g2rd-rescue18-child' ),
						'recrutement' => \__( 'Recrutements seulement', 'g2rd-rescue18-child' ),
					),
				),
				'nombre'   => array( 'number', 6, \__( 'Nombre d\'articles (aperçu)', 'g2rd-rescue18-child' ) ),
			),
			'context' => array( 'postId', 'postType' ),
			'render'  => 'render_fil_sdis',
		),
		'offres'         => array(
			'title'   => \__( 'Offres d\'emploi des SDIS', 'g2rd-rescue18-child' ),
			'icon'    => 'businessperson',
			'attrs'   => array(
				'variante' => $var( 'accueil' ),
				'contexte' => $ctx,
				'nombre'   => array( 'number', 4, \__( 'Nombre d\'offres (aperçu)', 'g2rd-rescue18-child' ) ),
			),
			'context' => array( 'postId', 'postType' ),
			'render'  => 'render_offres',
		),
		'offre-details'  => array(
			'title'   => \__( 'Offre · détails et candidature', 'g2rd-rescue18-child' ),
			'icon'    => 'clipboard',
			'attrs'   => array(),
			'context' => array( 'postId', 'postType' ),
			'render'  => 'render_offre_details',
		),
		'concours'       => array(
			'title'  => \__( 'Calendrier des concours et recrutements', 'g2rd-rescue18-child' ),
			'icon'   => 'calendar-alt',
			'attrs'  => array(
				'variante' => $var( 'accueil' ),
				'nombre'   => array( 'number', 4, \__( 'Nombre de dates (aperçu)', 'g2rd-rescue18-child' ) ),
			),
			'render' => 'render_concours',
		),
	);
}

/* -------------------------------------------------------------------------
 * Ressources
 * ---------------------------------------------------------------------- */

\add_action(
	'init',
	static function (): void {
		$l = asset( 'assets/vendor/leaflet/leaflet.js' );
		if ( $l ) {
			\wp_register_script( 'leaflet', $l[0], array(), '1.9.4', array( 'strategy' => 'defer', 'in_footer' => true ) );
		}
		$lc = asset( 'assets/vendor/leaflet/leaflet.css' );
		if ( $lc ) {
			\wp_register_style( 'leaflet', $lc[0], array(), '1.9.4' );
		}
		$a = asset( 'assets/js/annuaire.js' );
		if ( $a ) {
			\wp_register_script( 'rescue18-annuaire', $a[0], array(), $a[1], array( 'strategy' => 'defer', 'in_footer' => true ) );
		}
		$c = asset( 'assets/css/annuaire.css' );
		if ( $c ) {
			\wp_register_style( 'rescue18-annuaire', $c[0], array(), $c[1] );
		}
	}
);

/**
 * Styles et script du module (images externes, repli, cartes) : légers,
 * chargés sur tout le site car les fiches documentaires les utilisent aussi.
 */
\add_action(
	'wp_enqueue_scripts',
	static function (): void {
		\wp_enqueue_style( 'rescue18-annuaire' );
		\wp_enqueue_script( 'rescue18-annuaire' );
	},
	30
);

/**
 * Bloc rescue18/carte-casernes : toutes les casernes de France ou du monde.
 *
 * @param array<string,mixed> $a Attributs.
 * @return string
 */
function render_carte_casernes( array $a ): string {
	$monde = 'monde' === ( $a['portee'] ?? 'france' );
	charger_carte();
	$sc = asset( 'assets/vendor/supercluster/supercluster.min.js' );
	$js = asset( 'assets/js/carte-casernes.js' );
	if ( ! $sc || ! $js ) {
		return '';
	}
	\wp_enqueue_script( 'supercluster', $sc[0], array(), '7.1.5', array( 'strategy' => 'defer', 'in_footer' => true ) );
	\wp_enqueue_script( 'rescue18-carte-casernes', $js[0], array( 'leaflet', 'supercluster' ), $js[1], array( 'strategy' => 'defer', 'in_footer' => true ) );
	$up  = \wp_upload_dir();
	$base = \trailingslashit( $up['baseurl'] ) . 'rescue18/';
	$carte = config_carte();
	$info  = (array) \get_option( $monde ? 'r18_casernes_monde' : 'r18_casernes', array() );
	$n     = (int) ( $info['n'] ?? 0 );
	$cfg   = array(
		'monde'   => $monde,
		'data'    => $monde ? $base . 'monde/' : $base . 'casernes.json',
		'v'       => (int) ( $info['t'] ?? 0 ),
		'n'       => $n,
		'tuiles'  => $monde || '' === $carte['tuiles'] ? 'https://tile.openstreetmap.org/{z}/{x}/{y}.png' : $carte['tuiles'],
		'attrib'  => ( $monde || '' === $carte['tuiles'] ? '&copy; <a href="https://www.openstreetmap.org/copyright">contributeurs OpenStreetMap</a>' : \explode( ' · ', $carte['attrib'] )[0] ) . ' · Casernes : &copy; contributeurs <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> (ODbL), corrections Rescue 18',
		'max'     => $monde ? 19 : (int) $carte['max'],
		'centre'  => $monde ? array( 25, 5, 2 ) : array( 46.6, 2.4, 6 ),
		'corriger' => \current_user_can( 'edit_pages' ) ? \admin_url( 'post-new.php?post_type=r18_caserne&osm=' ) : '',
		'fiche'   => \current_user_can( 'edit_pages' ) ? \admin_url( 'post.php?action=edit&post=' ) : '',
	);
	$sauts = $monde ? '' : '<div class="r18-cc__sauts" role="group" aria-label="' . \esc_attr__( 'Aller à', 'g2rd-rescue18-child' ) . '">'
		. '<button type="button" data-cc-saut="46.6,2.4,6">Métropole</button>'
		. '<button type="button" data-cc-saut="16.2,-61.5,10">Guadeloupe</button>'
		. '<button type="button" data-cc-saut="14.65,-61.02,11">Martinique</button>'
		. '<button type="button" data-cc-saut="4.0,-53.0,7">Guyane</button>'
		. '<button type="button" data-cc-saut="-21.12,55.53,10">La Réunion</button>'
		. '<button type="button" data-cc-saut="-12.82,45.15,11">Mayotte</button></div>';
	$uid = \wp_unique_id( 'r18-cc-' );
	$h   = '<div class="r18-cc" data-r18-cc="' . \esc_attr( (string) \wp_json_encode( $cfg ) ) . '">';
	$h  .= '<form class="r18-cc__barre" role="search" data-cc-form><label for="' . $uid . '-q">' . \esc_html( $monde ? __( 'Rechercher une caserne par son nom', 'g2rd-rescue18-child' ) : __( 'Rechercher une caserne ou une commune', 'g2rd-rescue18-child' ) ) . '</label><div class="r18-cc__champ"><input id="' . $uid . '-q" type="search" data-cc-q autocomplete="off" placeholder="' . \esc_attr( $monde ? 'New York, Tokyo, Berlin…' : 'Gaillon, Sévigné, Lyon…' ) . '"><button type="submit" class="r18-btn r18-btn--dark">' . \esc_html__( 'Chercher', 'g2rd-rescue18-child' ) . '</button><button type="button" class="r18-btn r18-btn--white" data-cc-moi>' . icone( 'locate' ) . \esc_html__( 'Autour de moi', 'g2rd-rescue18-child' ) . '</button></div><p class="r18-cc__aide">' . \esc_html( $monde ? __( 'Nom de caserne ou de ville. La ville est localisée par Photon (OpenStreetMap).', 'g2rd-rescue18-child' ) : __( 'Nom de caserne ou de commune. La commune est localisée par la Base adresse nationale.', 'g2rd-rescue18-child' ) ) . '</p></form>';
	$h  .= $sauts;
	$h  .= '<p class="r18-cc__compte" data-cc-compte aria-live="polite">' . ( $n ? \esc_html( \sprintf( /* translators: %s : nombre. */ __( '%s casernes sur la carte', 'g2rd-rescue18-child' ), \number_format_i18n( $n ) ) ) : \esc_html__( 'Les casernes sont en cours d\'import. La carte se remplit à la prochaine synchronisation.', 'g2rd-rescue18-child' ) ) . '</p>';
	$h  .= '<div class="r18-cc__map" data-cc-map role="region" aria-label="' . \esc_attr( $monde ? __( 'Carte des casernes du monde', 'g2rd-rescue18-child' ) : __( 'Carte des casernes de France', 'g2rd-rescue18-child' ) ) . '"></div>';
	$h  .= '<div class="r18-cc__res" data-cc-res aria-live="polite"></div>';
	$h  .= '</div>';
	return $h;
}

/**
 * Charge la carte.
 */
function charger_carte(): void {
	\wp_enqueue_style( 'leaflet' );
	\wp_enqueue_script( 'leaflet' );
	\wp_enqueue_style( 'rescue18-annuaire' );
	\wp_enqueue_script( 'rescue18-annuaire' );
}

/**
 * Configuration de la carte (fond, attribution, casernes).
 *
 * @return array<string,mixed>
 */
function config_carte(): array {
	$reg  = reglages_annuaire();
	$ign  = 'https://data.geopf.fr/wmts?SERVICE=WMTS&REQUEST=GetTile&VERSION=1.0.0&STYLE=normal&TILEMATRIXSET=PM&TILEMATRIX={z}&TILEROW={y}&TILECOL={x}';
	$fond = array(
		'plan'  => array( $ign . '&LAYER=GEOGRAPHICALGRIDSYSTEMS.PLANIGNV2&FORMAT=image/png', '&copy; <a href="https://www.ign.fr/">IGN</a> Géoplateforme', 19 ),
		'ortho' => array( $ign . '&LAYER=ORTHOIMAGERY.ORTHOPHOTOS&FORMAT=image/jpeg', '&copy; <a href="https://www.ign.fr/">IGN</a> Géoplateforme', 19 ),
		'osm'   => array( 'https://tile.openstreetmap.org/{z}/{x}/{y}.png', '&copy; <a href="https://www.openstreetmap.org/copyright">contributeurs OpenStreetMap</a>', 19 ),
	)[ (string) $reg['carte_fond'] ] ?? null;
	$up   = \wp_upload_dir();
	$cas  = (array) \get_option( 'r18_casernes', array() );
	return array(
		'tuiles'   => $fond[0] ?? '',
		'attrib'   => ( $fond[1] ?? '' ) . ' · Données : Annuaire de l\'administration (DILA)',
		'max'      => $fond[2] ?? 18,
		'casernes' => ! empty( $reg['casernes'] ) && ! empty( $cas['n'] ) ? array(
			'url'    => \trailingslashit( $up['baseurl'] ) . 'rescue18/casernes.json?v=' . (int) ( $cas['t'] ?? 0 ),
			'n'      => (int) $cas['n'],
			'attrib' => 'Casernes : &copy; contributeurs <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> (ODbL)',
		) : null,
	);
}

/**
 * Image externe avec repli. Vide si les images externes sont désactivées.
 *
 * @param string $url   URL.
 * @param string $alt   Texte alternatif (vide = décorative).
 * @param string $class Classe.
 * @return string
 */
function image_externe( string $url, string $alt = '', string $class = '' ): string {
	if ( '' === $url || empty( reglages_annuaire()['images'] ) ) {
		return '';
	}
	return '<img class="' . \esc_attr( $class ) . '" src="' . \esc_url( $url ) . '" alt="' . \esc_attr( $alt ) . '" loading="lazy" decoding="async" referrerpolicy="no-referrer" data-r18-img>';
}

/**
 * Pastille de repli : numéro de département sur dégradé.
 *
 * @param array<string,mixed>|null $s Service.
 * @return string
 */
function repli_service( ?array $s ): string {
	$code = $s ? ( 'bspp' === $s['genre'] ? 'BSPP' : ( 'bmpm' === $s['genre'] ? 'BMPM' : (string) $s['dep'] ) ) : '18';
	return '<span class="r18-repli' . ( \strlen( $code ) > 3 ? ' is-long' : '' ) . '" aria-hidden="true"><span>' . \esc_html( $code ) . '</span></span>';
}

/**
 * Libellé court d'un service (« SDIS 27 »).
 *
 * @param array<string,mixed>|null $s Service.
 * @return string
 */
function court_service( ?array $s ): string {
	if ( ! $s ) {
		return '';
	}
	return \trim( (string) \explode( '·', (string) $s['titre'] )[0] );
}

/**
 * Service de la fiche affichée.
 *
 * @param \WP_Block|null $block Bloc.
 * @return array<string,mixed>|null
 */
function service_courant( ?\WP_Block $block ): ?array {
	$id = current_id( $block );
	return 'r18_service' === \get_post_type( $id ) ? service_par_id( $id ) : null;
}

/**
 * Lien téléphone.
 *
 * @param string $tel Numéro.
 * @return string
 */
function lien_tel( string $tel ): string {
	return 'tel:' . \preg_replace( '/[^\d+]/', '', $tel );
}

/* -------------------------------------------------------------------------
 * Annuaire
 * ---------------------------------------------------------------------- */

/**
 * Bloc rescue18/annuaire.
 *
 * @param array<string,mixed> $attrs Attributs.
 * @return string
 */
function render_annuaire( array $attrs ): string {
	$svc = tous_services();
	if ( empty( $svc ) ) {
		return wrap( 'r18-empty', '<p>' . \esc_html__( 'L\'annuaire se remplit au premier passage de la synchronisation (menu Annuaire SDIS).', 'g2rd-rescue18-child' ) . '</p>' );
	}
	charger_carte();
	$liste  = 'accueil' !== ( $attrs['variante'] ?? 'page' );
	$data   = array();
	$items  = '';
	foreach ( $svc as $s ) {
		$deps   = \array_map( static fn( string $d ): string => $d . ' ' . nom_dep( $d ), $s['deps'] );
		$data[] = array(
			'id'  => $s['id'],
			't'   => $s['titre'],
			'c'   => court_service( $s ),
			'g'   => $s['genre'],
			'd'   => $s['deps'],
			'r'   => $s['region'],
			'v'   => $s['ville'],
			'la'  => $s['lat'],
			'ln'  => $s['lng'],
			'u'   => $s['url'],
			'tel' => $s['tel'],
			'k'   => \G2RD_Rescue18\Veille\plat( $s['titre'] . ' ' . $s['nom'] . ' ' . $s['ville'] . ' ' . \implode( ' ', $deps ) . ' ' . $s['region'] ),
		);
		if ( $liste ) {
			$vis    = image_externe( (string) $s['logo'], '', 'r18-ann__logo' );
			$items .= '<li class="r18-ann__item" data-id="' . (int) $s['id'] . '"><div class="r18-ann__media">' . $vis . repli_service( $s ) . '</div><div class="r18-ann__body">';
			$items .= '<h3 class="r18-ann__name"><a href="' . \esc_url( $s['url'] ) . '">' . \esc_html( $s['titre'] ) . '</a></h3>';
			$items .= '<p class="r18-ann__meta">' . \esc_html( \trim( $s['ville'] . ( '' !== $s['region'] ? ' · ' . $s['region'] : '' ), ' ·' ) ) . '</p>';
			$items .= '<p class="r18-ann__acts">';
			if ( '' !== $s['tel'] ) {
				$items .= '<a href="' . \esc_attr( lien_tel( $s['tel'] ) ) . '">' . icone( 'phone' ) . \esc_html( $s['tel'] ) . '</a>';
			}
			if ( '' !== $s['site'] ) {
				$items .= '<a href="' . \esc_url( $s['site'] ) . '" target="_blank" rel="noopener">' . icone( 'globe' ) . \esc_html__( 'Site officiel', 'g2rd-rescue18-child' ) . '<span class="screen-reader-text"> ' . \esc_html__( '(nouvel onglet)', 'g2rd-rescue18-child' ) . '</span></a>';
			}
			$items .= '<button type="button" class="r18-ann__locate" data-r18-voir="' . (int) $s['id'] . '" hidden>' . icone( 'pin' ) . \esc_html__( 'Voir sur la carte', 'g2rd-rescue18-child' ) . '</button></p></div></li>';
		}
	}
	$uid  = \wp_unique_id( 'r18-ann-' );
	$h    = '<div class="r18-ann__bar" role="search"><div class="r18-ann__q">' . icone( 'search' ) . '<label for="' . \esc_attr( $uid ) . '-q">' . \esc_html__( 'Trouver un service', 'g2rd-rescue18-child' ) . '</label>';
	$h   .= '<input id="' . \esc_attr( $uid ) . '-q" type="search" data-r18-q placeholder="' . \esc_attr__( 'Numéro ou nom du département, ville, région…', 'g2rd-rescue18-child' ) . '" autocomplete="off"></div>';
	$h   .= '<div class="r18-ann__sel"><label for="' . \esc_attr( $uid ) . '-r">' . \esc_html__( 'Région', 'g2rd-rescue18-child' ) . '</label><select id="' . \esc_attr( $uid ) . '-r" data-r18-region><option value="">' . \esc_html__( 'Toutes les régions', 'g2rd-rescue18-child' ) . '</option>';
	foreach ( regions() as $r ) {
		$h .= '<option>' . \esc_html( $r ) . '</option>';
	}
	$h .= '</select></div><button type="button" class="r18-btn r18-btn--dark r18-ann__geo" data-r18-geo hidden>' . icone( 'locate' ) . \esc_html__( 'Autour de moi', 'g2rd-rescue18-child' ) . '</button></div>';
	$h .= '<div class="r18-ann__zones" data-r18-zones hidden><span>' . \esc_html__( 'Aller à :', 'g2rd-rescue18-child' ) . '</span>';
	foreach ( array( 'metro' => 'Métropole', '971' => 'Guadeloupe', '972' => 'Martinique', '973' => 'Guyane', '974' => 'La Réunion', '976' => 'Mayotte' ) as $z => $zl ) {
		$h .= '<button type="button" data-r18-zone="' . \esc_attr( $z ) . '">' . \esc_html( $zl ) . '</button>';
	}
	$h .= '</div><div class="r18-ann__layout' . ( $liste ? '' : ' is-map-only' ) . '">';
	$h .= '<div class="r18-ann__mapwrap"><div class="r18-ann__map" data-r18-map role="region" aria-label="' . \esc_attr__( 'Carte des services d\'incendie et de secours. La liste à côté donne les mêmes informations.', 'g2rd-rescue18-child' ) . '"></div>';
	$h .= '<noscript><p class="r18-ann__nojs">' . \esc_html__( 'La carte demande JavaScript. La liste reste complète.', 'g2rd-rescue18-child' ) . '</p></noscript></div>';
	if ( $liste ) {
		$h .= '<div class="r18-ann__side"><p class="r18-ann__count" data-r18-count aria-live="polite">' . \esc_html( \sprintf( /* translators: %d : nombre. */ \_n( '%d service', '%d services', \count( $svc ), 'g2rd-rescue18-child' ), \count( $svc ) ) ) . '</p>';
		$h .= '<ul class="r18-ann__list" data-r18-list>' . $items . '</ul><p class="r18-ann__none" data-r18-none hidden>' . \esc_html__( 'Aucun service ne correspond. Essaie le numéro du département, par exemple 27 ou 2A.', 'g2rd-rescue18-child' ) . '</p></div>';
	} else {
		$h .= '<p class="r18-ann__more"><a class="r18-btn r18-btn--red" href="' . \esc_url( (string) \get_post_type_archive_link( 'r18_service' ) ) . '">' . \esc_html__( 'Ouvrir l\'annuaire complet', 'g2rd-rescue18-child' ) . icone( 'arrow' ) . '</a></p>';
	}
	$h .= '</div>';
	$h .= '<script type="application/json" data-r18-data>' . \wp_json_encode( array( 'services' => $data, 'carte' => config_carte() ), JSON_HEX_TAG | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>';
	return wrap( 'r18-ann', $h, 'div', array( 'data-r18-ann' => '' ) );
}

/* -------------------------------------------------------------------------
 * Fiche SDIS
 * ---------------------------------------------------------------------- */

/**
 * Bloc rescue18/service-entete.
 *
 * @param array<string,mixed> $attrs Attributs.
 * @param \WP_Block|null      $block Bloc.
 * @return string
 */
function render_service_entete( array $attrs, ?\WP_Block $block = null ): string {
	$s = service_courant( $block );
	if ( ! $s ) {
		return '';
	}
	$genre = array( 'sdis' => 'Service départemental d\'incendie et de secours', 'sis' => 'Service d\'incendie et de secours', 'sdmis' => 'Service départemental-métropolitain d\'incendie et de secours', 'bspp' => 'Brigade de sapeurs-pompiers de Paris · Armée de Terre', 'bmpm' => 'Bataillon de marins-pompiers de Marseille · Marine nationale' )[ $s['genre'] ] ?? '';
	$h     = '';
	$img   = image_externe( (string) $s['image'], '', 'r18-svc__visuel' );
	if ( '' !== $img ) {
		$h .= '<div class="r18-svc__bg" aria-hidden="true">' . $img . '</div>';
	}
	$h .= '<div class="r18-svc__id"><div class="r18-svc__logo">' . image_externe( (string) $s['logo'], 'Logo ' . court_service( $s ), 'r18-svc__logoimg' ) . repli_service( $s ) . '</div>';
	$h .= '<div><p class="r18-eyebrow">' . \esc_html( $genre ) . '</p><p class="r18-svc__deps">';
	foreach ( $s['deps'] as $d ) {
		$h .= '<span class="r18-pill r18-pill--grey">' . \esc_html( $d . ' · ' . nom_dep( $d ) ) . '</span>';
	}
	$h .= '</p></div></div><div class="r18-actions">';
	if ( '' !== $s['recrutement'] ) {
		$h .= '<a class="r18-btn r18-btn--red" href="' . \esc_url( $s['recrutement'] ) . '" target="_blank" rel="noopener">' . icone( 'users' ) . \esc_html__( 'Devenir sapeur-pompier ici', 'g2rd-rescue18-child' ) . '<span class="screen-reader-text"> ' . \esc_html__( '(nouvel onglet)', 'g2rd-rescue18-child' ) . '</span></a>';
	}
	if ( '' !== $s['site'] ) {
		$h .= '<a class="r18-btn r18-btn--white" href="' . \esc_url( $s['site'] ) . '" target="_blank" rel="noopener">' . icone( 'globe' ) . \esc_html__( 'Site officiel', 'g2rd-rescue18-child' ) . '<span class="screen-reader-text"> ' . \esc_html__( '(nouvel onglet)', 'g2rd-rescue18-child' ) . '</span></a>';
	}
	if ( '' !== $s['tel'] ) {
		$h .= '<a class="r18-btn r18-btn--ghost-white" href="' . \esc_attr( lien_tel( $s['tel'] ) ) . '">' . icone( 'phone' ) . \esc_html( $s['tel'] ) . '</a>';
	}
	$h .= '<a class="r18-btn r18-btn--ghost-white" href="#offres-du-service">' . icone( 'briefcase' ) . \esc_html__( 'Offres d\'emploi', 'g2rd-rescue18-child' ) . '</a></div>';
	if ( '' !== (string) \get_post_meta( $s['id'], 'r18_a_verifier', true ) ) {
		$h .= '<p class="r18-svc__warn">' . icone( 'info' ) . \esc_html__( 'Fiche saisie par Rescue 18, hors annuaire officiel. Vérifie les coordonnées sur le site du service.', 'g2rd-rescue18-child' ) . '</p>';
	}
	$h .= '<script type="application/ld+json">' . \wp_json_encode(
		\array_filter(
			array(
				'@context'  => 'https://schema.org',
				'@type'     => 'FireStation',
				'name'      => $s['nom'] ?: $s['titre'],
				'url'       => $s['site'] ?: $s['url'],
				'telephone' => $s['tel'],
				'logo'      => $s['logo'],
				'address'   => array(
					'@type'           => 'PostalAddress',
					'streetAddress'   => (string) \get_post_meta( $s['id'], 'r18_adresse', true ),
					'postalCode'      => (string) \get_post_meta( $s['id'], 'r18_cp', true ),
					'addressLocality' => $s['ville'],
					'addressCountry'  => 'FR',
				),
				'geo'       => $s['lat'] ? array( '@type' => 'GeoCoordinates', 'latitude' => $s['lat'], 'longitude' => $s['lng'] ) : null,
			)
		),
		JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG
	) . '</script>';
	return wrap( 'r18-svc', $h );
}

/**
 * Bloc rescue18/service-infos.
 *
 * @param array<string,mixed> $attrs Attributs.
 * @param \WP_Block|null      $block Bloc.
 * @return string
 */
function render_service_infos( array $attrs, ?\WP_Block $block = null ): string {
	$s = service_courant( $block );
	if ( ! $s ) {
		return '';
	}
	$m   = static fn( string $k ): string => (string) \get_post_meta( $s['id'], $k, true );
	$adr = \trim( $m( 'r18_adresse' ) . ', ' . $m( 'r18_cp' ) . ' ' . $m( 'r18_ville' ), ', ' );
	$h   = '<p class="r18-urgence">' . icone( 'alert' ) . '<span><strong>' . \esc_html__( 'Urgence : compose le 18 ou le 112.', 'g2rd-rescue18-child' ) . '</strong> ' . \esc_html__( 'Les numéros ci-dessous servent aux démarches, jamais aux secours.', 'g2rd-rescue18-child' ) . '</span></p><dl class="r18-coord">';
	$row = static function ( string $ico, string $lib, string $val ) use ( &$h ): void {
		if ( '' !== $val ) {
			$h .= '<div>' . icone( $ico ) . '<dt>' . \esc_html( $lib ) . '</dt><dd>' . $val . '</dd></div>';
		}
	};
	$row( 'pin', 'Adresse', '' !== $adr ? \esc_html( $adr ) : '' );
	$row( 'phone', 'Standard', '' !== $s['tel'] ? '<a href="' . \esc_attr( lien_tel( $s['tel'] ) ) . '">' . \esc_html( $s['tel'] ) . '</a>' : '' );
	$row( 'mail', 'Courriel', '' !== $m( 'r18_email' ) ? '<a href="mailto:' . \esc_attr( \antispambot( $m( 'r18_email' ) ) ) . '">' . \esc_html( \antispambot( $m( 'r18_email' ) ) ) . '</a>' : '' );
	$row( 'clip', 'Formulaire', '' !== $m( 'r18_contact' ) ? '<a href="' . \esc_url( $m( 'r18_contact' ) ) . '" target="_blank" rel="noopener">' . \esc_html__( 'Écrire au service', 'g2rd-rescue18-child' ) . '</a>' : '' );
	$row( 'globe', 'Site', '' !== $s['site'] ? '<a href="' . \esc_url( $s['site'] ) . '" target="_blank" rel="noopener">' . \esc_html( (string) \preg_replace( '#^https?://(www\.)?|/$#', '', $s['site'] ) ) . '</a>' : '' );
	$res = '';
	foreach ( (array) \json_decode( $m( 'r18_reseaux' ), true ) as $r ) {
		$res .= '<a href="' . \esc_url( (string) ( $r['url'] ?? '' ) ) . '" target="_blank" rel="noopener">' . \esc_html( (string) \preg_replace( '/^X ex Twitter$/', 'X', (string) ( $r['reseau'] ?? '' ) ) ) . '</a> ';
	}
	$row( 'users', 'Réseaux', \trim( $res ) );
	$row( 'shield', 'Annuaire', '' !== $m( 'r18_fiche' ) ? '<a href="' . \esc_url( $m( 'r18_fiche' ) ) . '" target="_blank" rel="noopener">Service-Public.fr</a>' : '' );
	$h .= '</dl>';
	if ( $s['lat'] && $s['lng'] ) {
		charger_carte();
		$h .= '<div class="r18-svc__map" data-r18-mini="' . \esc_attr( (string) \wp_json_encode( array( 'la' => $s['lat'], 'ln' => $s['lng'], 't' => $s['titre'], 'carte' => config_carte() ) ) ) . '" role="region" aria-label="' . \esc_attr( \sprintf( /* translators: %s : adresse. */ \__( 'Carte : %s', 'g2rd-rescue18-child' ), $adr ) ) . '"></div>';
		$h .= '<p class="r18-svc__route"><a href="' . \esc_url( 'https://www.openstreetmap.org/directions?to=' . $s['lat'] . '%2C' . $s['lng'] ) . '" target="_blank" rel="noopener">' . icone( 'compass' ) . \esc_html__( 'Itinéraire', 'g2rd-rescue18-child' ) . '</a></p>';
	}
	return wrap( 'r18-box r18-svc-infos', $h, 'section', array( 'aria-label' => \__( 'Coordonnées', 'g2rd-rescue18-child' ) ) );
}

/* -------------------------------------------------------------------------
 * Actualités des SDIS
 * ---------------------------------------------------------------------- */

/**
 * Bloc rescue18/fil-sdis.
 *
 * @param array<string,mixed> $attrs Attributs.
 * @param \WP_Block|null      $block Bloc.
 * @return string
 */
function render_fil_sdis( array $attrs, ?\WP_Block $block = null ): string {
	$page = 'page' === ( $attrs['variante'] ?? 'accueil' );
	$rec  = 'recrutement' === ( $attrs['filtre'] ?? 'tous' );
	$q    = array(
		'post_type'      => 'r18_actu_sdis',
		'post_status'    => 'publish',
		'posts_per_page' => $page ? 120 : \max( 1, (int) ( $attrs['nombre'] ?? 6 ) ),
		'no_found_rows'  => true,
		'meta_query'     => array(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	);
	$svc  = null;
	if ( 'service' === ( $attrs['contexte'] ?? 'tous' ) ) {
		$svc = service_courant( $block );
		if ( ! $svc ) {
			return '';
		}
		$q['meta_query'][] = array( 'key' => 'r18_service_id', 'value' => (string) $svc['id'] );
	}
	if ( $rec ) {
		$q['meta_query'][] = array( 'key' => 'r18_recrutement', 'value' => '1' );
	}
	$posts = \get_posts( $q );
	if ( empty( $posts ) ) {
		$msg = $svc ? \__( 'Aucune actualité récente récupérée pour ce service. Son site officiel reste la référence.', 'g2rd-rescue18-child' ) : ( $rec ? \__( 'Aucune annonce de recrutement repérée ces dernières semaines.', 'g2rd-rescue18-child' ) : \__( 'Les actualités arrivent après la première synchronisation.', 'g2rd-rescue18-child' ) );
		return wrap( 'r18-empty', '<p>' . \esc_html( $msg ) . '</p>' );
	}
	$cards = '';
	$deps  = array();
	foreach ( $posts as $i => $p ) {
		$s    = service_par_id( (int) \get_post_meta( $p->ID, 'r18_service_id', true ) );
		$dep  = (string) \get_post_meta( $p->ID, 'r18_dep', true );
		$url  = (string) \get_post_meta( $p->ID, 'r18_url', true );
		$img  = (string) \get_post_meta( $p->ID, 'r18_image', true );
		$isr  = '' !== (string) \get_post_meta( $p->ID, 'r18_recrutement', true );
		$deps[ $dep ] = ( $deps[ $dep ] ?? 0 ) + 1;
		$vis  = image_externe( $img, '', 'r18-sa__img' );
		if ( '' === $vis && $s ) {
			$vis = image_externe( (string) $s['image'], '', 'r18-sa__img is-service' );
		}
		$cle  = \G2RD_Rescue18\Veille\plat( \get_the_title( $p ) . ' ' . court_service( $s ) . ' ' . ( $s['titre'] ?? '' ) . ' ' . $dep . ' ' . ( departements()[ $dep ][0] ?? '' ) . ' ' . region_dep( $dep ) );
		$cards .= '<li class="r18-sa' . ( $page && $i >= 24 ? ' is-more' : '' ) . '" data-dep="' . \esc_attr( $dep ) . '" data-region="' . \esc_attr( region_dep( $dep ) ) . '" data-rec="' . ( $isr ? '1' : '' ) . '"' . ( $page ? ' data-k="' . \esc_attr( $cle ) . '"' : '' ) . ( $page && $i >= 24 ? ' hidden' : '' ) . '>';
		$cards .= '<div class="r18-sa__media">' . $vis . repli_service( $s ) . ( $s && '' !== $s['logo'] ? '<span class="r18-sa__logo">' . image_externe( (string) $s['logo'], '' ) . '</span>' : '' ) . '</div>';
		$cards .= '<div class="r18-sa__body"><p class="r18-sa__src"><span>' . \esc_html( court_service( $s ) ?: $dep ) . '</span><time datetime="' . \esc_attr( (string) \get_post_time( 'c', true, $p ) ) . '">' . \esc_html( \sprintf( /* translators: %s : durée. */ \__( 'il y a %s', 'g2rd-rescue18-child' ), \human_time_diff( (int) \get_post_time( 'U', true, $p ) ) ) ) . '</time></p>';
		$cards .= ( $isr ? '<span class="r18-pill">' . icone( 'users' ) . \esc_html__( 'Recrutement', 'g2rd-rescue18-child' ) . '</span>' : '' );
		$cards .= '<h3 class="r18-sa__title"><a href="' . \esc_url( $url ) . '" target="_blank" rel="noopener">' . \esc_html( \G2RD_Rescue18\Annuaire\casse_titre( \get_the_title( $p ) ) ) . '<span class="screen-reader-text"> ' . \esc_html__( '(site du service, nouvel onglet)', 'g2rd-rescue18-child' ) . '</span></a></h3>';
		$ex     = \G2RD_Rescue18\Annuaire\nettoyer_resume( (string) $p->post_excerpt );
		$cards .= '' !== $ex ? '<p class="r18-sa__ex">' . \esc_html( \wp_trim_words( $ex, 26 ) ) . '</p>' : '';
		$cards .= '<p class="r18-sa__go">' . \esc_html__( 'Lire sur le site du service', 'g2rd-rescue18-child' ) . icone( 'ext' ) . '</p></div></li>';
	}
	$h = '';
	if ( $page && ! $svc ) {
		$uid  = \wp_unique_id( 'r18-fs-' );
		$nreg = array();
		foreach ( $deps as $code => $n ) {
			$r          = region_dep( (string) $code );
			$nreg[ $r ] = ( $nreg[ $r ] ?? 0 ) + $n;
		}
		$h .= '<div class="r18-fs__bar" role="search" aria-label="' . \esc_attr__( 'Filtrer les actualités des SDIS', 'g2rd-rescue18-child' ) . '">';
		$h .= '<div class="r18-ann__q r18-fs__q"><label for="' . \esc_attr( $uid ) . '-q">' . \esc_html__( 'Rechercher', 'g2rd-rescue18-child' ) . '</label>' . icone( 'search' ) . '<input type="search" id="' . \esc_attr( $uid ) . '-q" data-r18-fs-q placeholder="' . \esc_attr__( 'Feu de forêt, SDIS 27, recrutement…', 'g2rd-rescue18-child' ) . '" autocomplete="off" enterkeyhint="search"></div>';
		$h .= '<div class="r18-ann__sel"><label for="' . \esc_attr( $uid ) . '-r">' . \esc_html__( 'Région', 'g2rd-rescue18-child' ) . '</label><select id="' . \esc_attr( $uid ) . '-r" data-r18-fs-region><option value="">' . \esc_html__( 'Toute la France', 'g2rd-rescue18-child' ) . '</option>';
		foreach ( regions() as $r ) {
			if ( isset( $nreg[ $r ] ) ) {
				$h .= '<option value="' . \esc_attr( $r ) . '">' . \esc_html( $r . ' (' . $nreg[ $r ] . ')' ) . '</option>';
			}
		}
		$h .= '</select></div>';
		if ( ! $rec ) {
			$h .= '<fieldset class="r18-seg r18-fs__type"><legend>' . \esc_html__( 'Type', 'g2rd-rescue18-child' ) . '</legend>';
			foreach ( array( '' => \__( 'Tout', 'g2rd-rescue18-child' ), '1' => \__( 'Recrutements', 'g2rd-rescue18-child' ) ) as $v => $l ) {
				$h .= '<label><input type="radio" name="' . \esc_attr( $uid ) . '-t" value="' . \esc_attr( (string) $v ) . '" data-r18-fs-type' . ( '' === $v ? ' checked' : '' ) . '><span>' . \esc_html( $l ) . ' <b class="r18-fs__n" data-r18-fs-n="' . \esc_attr( (string) $v ) . '"></b></span></label>';
			}
			$h .= '</fieldset>';
		}
		// Départements : cases à cocher, affichées pour la région choisie.
		$h .= '<fieldset class="r18-fs__deps" data-r18-fs-deps><legend>' . \esc_html__( 'Départements', 'g2rd-rescue18-child' ) . '</legend><div class="r18-fs__depl">';
		foreach ( departements() as $code => [ $nom ] ) {
			if ( isset( $deps[ $code ] ) ) {
				$h .= '<label data-region="' . \esc_attr( region_dep( (string) $code ) ) . '"><input type="checkbox" value="' . \esc_attr( (string) $code ) . '" data-r18-fs-dep><span>' . \esc_html( $code . ' · ' . $nom ) . ' <b class="r18-fs__n">' . (int) $deps[ $code ] . '</b></span></label>';
			}
		}
		$h .= '</div></fieldset>';
		$h .= '</div>';
		$h .= '<div class="r18-fs__state"><p class="r18-ann__count" data-r18-fs-count aria-live="polite"></p><ul class="r18-fs__chips" data-r18-fs-chips aria-label="' . \esc_attr__( 'Filtres actifs', 'g2rd-rescue18-child' ) . '"></ul><button type="button" class="r18-fs__reset" data-r18-fs-reset hidden>' . \esc_html__( 'Tout effacer', 'g2rd-rescue18-child' ) . '</button></div>';
	}
	$h .= '<ul class="r18-sa-grid" data-r18-fs-list>' . $cards . '</ul>';
	if ( $page ) {
		$h .= '<div class="r18-fs__none" data-r18-fs-none hidden><p>' . \esc_html__( 'Aucun article pour ce choix.', 'g2rd-rescue18-child' ) . '</p><button type="button" class="r18-btn r18-btn--dark" data-r18-fs-reset>' . \esc_html__( 'Effacer les filtres', 'g2rd-rescue18-child' ) . '</button></div>';
		$h .= \count( $posts ) > 24 ? '<p class="r18-fs__more"><button type="button" class="r18-btn r18-btn--dark" data-r18-fs-more>' . \esc_html__( 'Afficher plus d\'articles', 'g2rd-rescue18-child' ) . '</button></p>' : '';
	}
	$h .= '<p class="r18-src-note">' . \esc_html__( 'Titres et résumés publiés par les services eux-mêmes, avec lien vers l\'article d\'origine. Images affichées depuis leurs sites.', 'g2rd-rescue18-child' ) . '</p>';
	return wrap( 'r18-fs' . ( $page ? ' is-page' : '' ), $h, 'div', array( 'data-r18-fs' => '' ) );
}

/* -------------------------------------------------------------------------
 * Offres d'emploi
 * ---------------------------------------------------------------------- */

/**
 * Date lisible.
 *
 * @param string $ymd Date AAAA-MM-JJ.
 * @param string $fmt Format.
 * @return string
 */
function date_lisible( string $ymd, string $fmt = 'j F Y' ): string {
	$t = '' !== $ymd ? \strtotime( $ymd . ' 12:00:00' ) : false;
	return false !== $t ? \wp_date( $fmt, $t ) : '';
}

/**
 * Jours restants avant une date.
 *
 * @param string $ymd Date.
 * @return int|null
 */
function jours_restants( string $ymd ): ?int {
	if ( '' === $ymd ) {
		return null;
	}
	$d = \date_create( $ymd );
	$n = \date_create( \wp_date( 'Y-m-d' ) );
	return $d && $n ? (int) $n->diff( $d )->format( '%r%a' ) : null;
}

/**
 * Nature de l'emploi en mots simples.
 *
 * @param string $n Libellé officiel.
 * @return string
 */
function nature_courte( string $n ): string {
	$p = \G2RD_Rescue18\Veille\plat( $n );
	if ( \str_contains( $p, 'uniquement aux contractuels' ) ) {
		return \__( 'Contractuels', 'g2rd-rescue18-child' );
	}
	if ( \str_contains( $p, 'titulaires et aux contractuels' ) ) {
		return \__( 'Titulaires et contractuels', 'g2rd-rescue18-child' );
	}
	if ( \str_contains( $p, 'fonctionnaires' ) ) {
		return \__( 'Fonctionnaires ou lauréats de concours', 'g2rd-rescue18-child' );
	}
	return $n;
}

/**
 * Bloc rescue18/offres.
 *
 * @param array<string,mixed> $attrs Attributs.
 * @param \WP_Block|null      $block Bloc.
 * @return string
 */
function render_offres( array $attrs, ?\WP_Block $block = null ): string {
	$page = 'page' === ( $attrs['variante'] ?? 'accueil' );
	$q    = array(
		'post_type'      => 'r18_offre',
		'post_status'    => 'publish',
		'posts_per_page' => $page ? 300 : \max( 1, (int) ( $attrs['nombre'] ?? 4 ) ),
		'no_found_rows'  => true,
	);
	$svc  = null;
	if ( 'service' === ( $attrs['contexte'] ?? 'tous' ) ) {
		$id = current_id( $block );
		if ( 'r18_offre' === \get_post_type( $id ) ) {
			$svc = service_par_id( (int) \get_post_meta( $id, 'r18_service_id', true ) );
			$q['post__not_in'] = array( $id );
		} else {
			$svc = service_courant( $block );
		}
		if ( ! $svc ) {
			return '';
		}
		$q['meta_query'] = array( array( 'key' => 'r18_service_id', 'value' => (string) $svc['id'] ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	}
	$posts = \get_posts( $q );
	$csp   = (string) reglages_annuaire()['lien_csp'];
	if ( empty( $posts ) ) {
		$msg = $svc ? \__( 'Aucune offre publiée en ce moment pour ce service sur Choisir le Service Public.', 'g2rd-rescue18-child' ) : \__( 'Les offres arrivent après la première synchronisation.', 'g2rd-rescue18-child' );
		$h   = '<p>' . \esc_html( $msg ) . '</p>';
		$h  .= $svc && '' !== $svc['recrutement'] ? '<p><a class="r18-btn r18-btn--red" href="' . \esc_url( $svc['recrutement'] ) . '" target="_blank" rel="noopener">' . \esc_html__( 'Page recrutement du service', 'g2rd-rescue18-child' ) . '</a></p>' : '';
		return wrap( 'r18-empty', $h );
	}
	$cards = '';
	$deps  = array();
	$cats  = array();
	foreach ( $posts as $p ) {
		$m    = static fn( string $k ): string => (string) \get_post_meta( $p->ID, $k, true );
		$s    = service_par_id( (int) $m( 'r18_service_id' ) );
		$dep  = $m( 'r18_dep' );
		$cat  = \strtoupper( \substr( \trim( (string) \preg_replace( '/^cat[ée]gorie\s*/iu', '', $m( 'r18_categorie' ) ) ), 0, 1 ) );
		$cat  = \in_array( $cat, array( 'A', 'B', 'C' ), true ) ? $cat : '';
		$rest = jours_restants( $m( 'r18_fin' ) );
		$deps[ $dep ] = true;
		if ( '' !== $cat ) {
			$cats[ $cat ] = true;
		}
		$cards .= '<li class="r18-of" data-dep="' . \esc_attr( $dep ) . '" data-region="' . \esc_attr( region_dep( $dep ) ) . '" data-cat="' . \esc_attr( $cat ) . '" data-k="' . \esc_attr( \G2RD_Rescue18\Veille\plat( \get_the_title( $p ) . ' ' . $m( 'r18_metier' ) . ' ' . $m( 'r18_lieu' ) . ' ' . $m( 'r18_employeur' ) ) ) . '">';
		$cards .= '<div class="r18-of__logo">' . ( $s ? image_externe( (string) $s['logo'], '' ) : '' ) . repli_service( $s ) . '</div><div class="r18-of__body">';
		$cards .= '<p class="r18-of__emp">' . \esc_html( $m( 'r18_employeur' ) ) . '</p><h3 class="r18-of__title"><a href="' . \esc_url( (string) \get_permalink( $p ) ) . '">' . \esc_html( \get_the_title( $p ) ) . '</a></h3>';
		$cards .= '<p class="r18-of__tags">' . ( '' !== $m( 'r18_lieu' ) ? '<span>' . icone( 'pin' ) . \esc_html( $m( 'r18_lieu' ) . ( '' !== $dep ? ' (' . $dep . ')' : '' ) ) . '</span>' : '' );
		$cards .= '' !== $cat ? '<span class="r18-pill r18-pill--grey">' . \esc_html( \sprintf( /* translators: %s : A, B ou C. */ \__( 'Catégorie %s', 'g2rd-rescue18-child' ), $cat ) ) . '</span>' : '';
		$nat    = nature_courte( $m( 'r18_nature' ) );
		$cards .= '' !== $nat ? '<span class="r18-pill r18-pill--grey">' . \esc_html( $nat ) . '</span>' : '';
		$cards .= '</p></div><div class="r18-of__end">';
		if ( null !== $rest ) {
			$cards .= '<p class="r18-of__date' . ( $rest <= 7 ? ' is-soon' : '' ) . '">' . \esc_html( $rest <= 0 ? \__( 'Dernier jour', 'g2rd-rescue18-child' ) : ( 1 === $rest ? \__( 'Clôture demain', 'g2rd-rescue18-child' ) : \sprintf( /* translators: %d : jours. */ \__( 'Encore %d jours', 'g2rd-rescue18-child' ), $rest ) ) ) . '<small>' . \esc_html( \sprintf( /* translators: %s : date. */ \__( 'jusqu\'au %s', 'g2rd-rescue18-child' ), date_lisible( $m( 'r18_fin' ), 'j M' ) ) ) . '</small></p>';
		}
		$cards .= '<span class="r18-of__go" aria-hidden="true">' . icone( 'arrow' ) . '</span></div></li>';
	}
	$h = '';
	if ( $page && ! $svc ) {
		$uid = \wp_unique_id( 'r18-of-' );
		$h  .= '<div class="r18-fs__bar" role="search"><div class="r18-ann__q">' . icone( 'search' ) . '<label for="' . \esc_attr( $uid ) . '-q">' . \esc_html__( 'Métier, ville, mot-clé', 'g2rd-rescue18-child' ) . '</label><input id="' . \esc_attr( $uid ) . '-q" type="search" data-r18-of-q placeholder="' . \esc_attr__( 'Ex. : infirmier, mécanicien, Rouen…', 'g2rd-rescue18-child' ) . '" autocomplete="off"></div>';
		$h  .= '<div class="r18-ann__sel"><label for="' . \esc_attr( $uid ) . '-r">' . \esc_html__( 'Région', 'g2rd-rescue18-child' ) . '</label><select id="' . \esc_attr( $uid ) . '-r" data-r18-of-region><option value="">' . \esc_html__( 'Toute la France', 'g2rd-rescue18-child' ) . '</option>';
		foreach ( regions() as $r ) {
			$h .= '<option>' . \esc_html( $r ) . '</option>';
		}
		$h .= '</select></div><div class="r18-ann__sel"><label for="' . \esc_attr( $uid ) . '-d">' . \esc_html__( 'Département', 'g2rd-rescue18-child' ) . '</label><select id="' . \esc_attr( $uid ) . '-d" data-r18-of-dep><option value="">' . \esc_html__( 'Tous', 'g2rd-rescue18-child' ) . '</option>';
		foreach ( departements() as $code => [ $nom ] ) {
			if ( isset( $deps[ $code ] ) ) {
				$h .= '<option value="' . \esc_attr( $code ) . '">' . \esc_html( $code . ' · ' . $nom ) . '</option>';
			}
		}
		$h .= '</select></div>';
		if ( $cats ) {
			\ksort( $cats );
			$h .= '<fieldset class="r18-seg"><legend>' . \esc_html__( 'Catégorie', 'g2rd-rescue18-child' ) . '</legend><label><input type="radio" name="' . \esc_attr( $uid ) . '-c" value="" checked data-r18-of-cat><span>' . \esc_html__( 'Toutes', 'g2rd-rescue18-child' ) . '</span></label>';
			foreach ( \array_keys( $cats ) as $c ) {
				$h .= '<label><input type="radio" name="' . \esc_attr( $uid ) . '-c" value="' . \esc_attr( (string) $c ) . '" data-r18-of-cat><span>' . \esc_html( (string) $c ) . '</span></label>';
			}
			$h .= '</fieldset>';
		}
		$h .= '</div><details class="r18-help"><summary>' . icone( 'help' ) . \esc_html__( 'Catégories A, B, C : ça veut dire quoi ?', 'g2rd-rescue18-child' ) . '</summary><p>' . \esc_html__( 'Dans la fonction publique, la catégorie indique le niveau du poste. C : exécution (sapeur, caporal, agent technique). B : encadrement intermédiaire (sergent, adjudant, technicien). A : conception et direction (lieutenant, capitaine, ingénieur, médecin).', 'g2rd-rescue18-child' ) . '</p></details>';
		$h .= '<p class="r18-ann__count" data-r18-of-count aria-live="polite"></p>';
	}
	$h .= '<ul class="r18-of-list" data-r18-of-list>' . $cards . '</ul>';
	if ( $page ) {
		$h .= '<p class="r18-fs__none" data-r18-of-none hidden>' . \esc_html__( 'Aucune offre pour ce choix. Élargis la zone ou retire un filtre.', 'g2rd-rescue18-child' ) . '</p>';
	}
	$h .= '<p class="r18-src-note">' . \sprintf(
		/* translators: %s : lien. */
		\esc_html__( 'Offres publiées par les services sur %s, données ouvertes actualisées chaque semaine. La candidature se fait sur la plateforme officielle.', 'g2rd-rescue18-child' ),
		'<a href="' . \esc_url( $csp ) . '" target="_blank" rel="noopener">Choisir le Service Public</a>'
	) . '</p>';
	return wrap( 'r18-ofs' . ( $page ? ' is-page' : '' ), $h, 'div', array( 'data-r18-of' => '' ) );
}

/**
 * Bloc rescue18/offre-details (fiche /offre/…).
 *
 * @param array<string,mixed> $attrs Attributs.
 * @param \WP_Block|null      $block Bloc.
 * @return string
 */
function render_offre_details( array $attrs, ?\WP_Block $block = null ): string {
	$id = current_id( $block );
	if ( 'r18_offre' !== \get_post_type( $id ) ) {
		return '';
	}
	$m    = static fn( string $k ): string => (string) \get_post_meta( $id, $k, true );
	$s    = service_par_id( (int) $m( 'r18_service_id' ) );
	$rest = jours_restants( $m( 'r18_fin' ) );
	$h    = '<div class="r18-od__cta">';
	if ( null !== $rest ) {
		$h .= '<p class="r18-of__date' . ( $rest <= 7 ? ' is-soon' : '' ) . '">' . \esc_html( \sprintf( /* translators: %s : date. */ \__( 'Candidature jusqu\'au %s', 'g2rd-rescue18-child' ), date_lisible( $m( 'r18_fin' ) ) ) ) . '</p>';
	}
	$h .= '<a class="r18-btn r18-btn--red" href="' . \esc_url( $m( 'r18_url' ) ) . '" target="_blank" rel="noopener">' . \esc_html__( 'Postuler sur Choisir le Service Public', 'g2rd-rescue18-child' ) . icone( 'ext' ) . '<span class="screen-reader-text"> ' . \esc_html__( '(nouvel onglet)', 'g2rd-rescue18-child' ) . '</span></a>';
	if ( $s ) {
		$h .= '<a class="r18-btn r18-btn--white" href="' . \esc_url( $s['url'] ) . '">' . \esc_html( \sprintf( /* translators: %s : service. */ \__( 'Fiche du %s', 'g2rd-rescue18-child' ), court_service( $s ) ) ) . '</a>';
	}
	$h .= '</div><dl class="r18-od">';
	foreach ( array(
		'r18_employeur'   => 'Employeur',
		'r18_lieu'        => 'Lieu',
		'r18_metier'      => 'Métier',
		'r18_categorie'   => 'Catégorie',
		'r18_statut'      => 'Statut',
		'r18_nature'      => 'Qui peut postuler',
		'r18_contrat'     => 'Durée du contrat',
		'r18_temps'       => 'Temps plein',
		'r18_teletravail' => 'Télétravail',
		'r18_etudes'      => 'Niveau d\'études',
		'r18_vacance'     => 'Poste à pourvoir le',
		'r18_offre_ref'   => 'Référence',
	) as $k => $lib ) {
		$v = 'r18_vacance' === $k ? date_lisible( $m( $k ) ) : ( 'r18_nature' === $k ? nature_courte( $m( $k ) ) : $m( $k ) );
		if ( 'r18_lieu' === $k && '' !== $m( 'r18_dep' ) ) {
			$v .= \str_contains( \G2RD_Rescue18\Veille\plat( $v ), \G2RD_Rescue18\Veille\plat( nom_dep( $m( 'r18_dep' ) ) ) ) ? ' (' . $m( 'r18_dep' ) . ')' : ' (' . $m( 'r18_dep' ) . ' · ' . nom_dep( $m( 'r18_dep' ) ) . ')';
		}
		if ( '' !== \trim( $v ) ) {
			$h .= '<div><dt>' . \esc_html( $lib ) . '</dt><dd>' . \esc_html( $v ) . '</dd></div>';
		}
	}
	$h .= '</dl>';
	if ( '' !== $m( 'r18_competences' ) ) {
		$h .= '<h2 class="r18-od__h">' . \esc_html__( 'Compétences attendues', 'g2rd-rescue18-child' ) . '</h2><div class="r18-od__txt">' . \wpautop( \esc_html( $m( 'r18_competences' ) ) ) . '</div>';
	}
	$h .= '<p class="r18-src-note">' . \esc_html__( 'Fiche résumée depuis les données ouvertes de Choisir le Service Public. L\'annonce complète et le dépôt de candidature sont sur la plateforme officielle.', 'g2rd-rescue18-child' ) . '</p>';
	$post = \get_post( $id );
	$ld   = array(
		'@context'           => 'https://schema.org',
		'@type'              => 'JobPosting',
		'title'              => \get_the_title( $id ),
		'description'        => '' !== $m( 'r18_competences' ) ? $m( 'r18_competences' ) : \get_the_title( $id ) . ' · ' . $m( 'r18_employeur' ),
		'identifier'         => array( '@type' => 'PropertyValue', 'name' => 'Choisir le Service Public', 'value' => $m( 'r18_offre_ref' ) ),
		'datePosted'         => '' !== $m( 'r18_debut' ) ? $m( 'r18_debut' ) : ( $post ? \get_the_date( 'Y-m-d', $post ) : '' ),
		'validThrough'       => '' !== $m( 'r18_fin' ) ? $m( 'r18_fin' ) . 'T23:59:00+01:00' : null,
		'employmentType'     => \str_contains( \strtolower( $m( 'r18_temps' ) ), 'non' ) ? 'PART_TIME' : 'FULL_TIME',
		'hiringOrganization' => array( '@type' => 'GovernmentOrganization', 'name' => $m( 'r18_employeur' ), 'sameAs' => $s ? $s['site'] : null, 'logo' => $s ? $s['logo'] : null ),
		'jobLocation'        => array( '@type' => 'Place', 'address' => array( '@type' => 'PostalAddress', 'addressLocality' => $m( 'r18_lieu' ), 'addressRegion' => region_dep( $m( 'r18_dep' ) ), 'addressCountry' => 'FR' ) ),
		'directApply'        => false,
		'url'                => (string) \get_permalink( $id ),
	);
	$h .= '<script type="application/ld+json">' . \wp_json_encode( \array_filter( $ld ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ) . '</script>';
	return wrap( 'r18-box r18-offre', $h, 'section' );
}

/* -------------------------------------------------------------------------
 * Calendrier des concours
 * ---------------------------------------------------------------------- */

/**
 * État d'une date de concours.
 *
 * @param string $deb Ouverture.
 * @param string $fin Clôture.
 * @param string $epr Épreuves.
 * @return array{0:string,1:string,2:string} clé, libellé, date de tri.
 */
function etat_concours( string $deb, string $fin, string $epr, string $dos = '' ): array {
	$t = \wp_date( 'Y-m-d' );
	if ( '' !== $deb && $deb > $t ) {
		return array( 'bientot', \__( 'Inscriptions bientôt', 'g2rd-rescue18-child' ), $deb );
	}
	if ( '' !== $fin && $fin >= $t ) {
		return array( 'ouvert', \__( 'Inscriptions ouvertes', 'g2rd-rescue18-child' ), $fin );
	}
	if ( '' !== $dos && $dos >= $t ) {
		return array( 'ouvert', \__( 'Dépôt des dossiers', 'g2rd-rescue18-child' ), $dos );
	}
	if ( '' !== $epr && $epr >= $t ) {
		return array( 'epreuves', \__( 'Épreuves à venir', 'g2rd-rescue18-child' ), $epr );
	}
	if ( '' === $deb && '' === $fin && '' === $epr ) {
		return array( 'annonce', \__( 'Annonce', 'g2rd-rescue18-child' ), '9999' );
	}
	return array( 'passe', \__( 'Terminé', 'g2rd-rescue18-child' ), '' );
}

/**
 * Bloc rescue18/concours.
 *
 * @param array<string,mixed> $attrs Attributs.
 * @return string
 */
function render_concours( array $attrs ): string {
	$page  = 'page' === ( $attrs['variante'] ?? 'accueil' );
	$lst   = array();
	$cats  = array( 'spp' => 'Concours SPP', 'sante' => 'Santé (SSSM)', 'spv' => 'Volontaires', 'jsp' => 'JSP', 'bspp' => 'BSPP', 'bmpm' => 'BMPM', 'examen' => 'Examens', 'autre' => 'Autres' );
	$vus   = array();
	foreach ( \get_posts(
		array(
			'post_type'      => 'r18_concours',
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'no_found_rows'  => true,
			'post__not_in'   => \is_singular( 'r18_concours' ) ? array( (int) \get_queried_object_id() ) : array(),
		)
	) as $p ) {
		$m = static fn( string $k ): string => (string) \get_post_meta( $p->ID, $k, true );
		[ $cle, $lib, $tri ] = etat_concours( $m( 'r18_insc_debut' ), $m( 'r18_insc_fin' ), $m( 'r18_epreuves' ), $m( 'r18_dossier' ) );
		if ( 'passe' === $cle || ( 'annonce' === $cle && \get_post_time( 'U', true, $p ) < \time() - 90 * DAY_IN_SECONDS ) ) {
			continue;
		}
		$lst[] = array( $p, $cle, $lib, $tri, $m );
		$vus[ $m( 'r18_cat' ) ] = true;
	}
	\usort( $lst, static fn( array $a, array $b ): int => \strcmp( $a[3], $b[3] ) );
	if ( ! $page ) {
		$lst = \array_slice( $lst, 0, \max( 1, (int) ( $attrs['nombre'] ?? 4 ) ) );
	}
	if ( empty( $lst ) ) {
		return wrap( 'r18-empty', '<p>' . \esc_html__( 'Aucune date annoncée pour l\'instant. Les campagnes repérées dans les actualités des SDIS apparaissent ici dès leur validation.', 'g2rd-rescue18-child' ) . '</p>' );
	}
	$h = '';
	if ( $page && \count( $vus ) > 1 ) {
		$uid = \wp_unique_id( 'r18-cc-' );
		$h  .= '<fieldset class="r18-seg"><legend>' . \esc_html__( 'Type', 'g2rd-rescue18-child' ) . '</legend><label><input type="radio" name="' . \esc_attr( $uid ) . '" value="" checked data-r18-cc-cat><span>' . \esc_html__( 'Tout', 'g2rd-rescue18-child' ) . '</span></label>';
		foreach ( $cats as $k => $l ) {
			if ( isset( $vus[ $k ] ) ) {
				$h .= '<label><input type="radio" name="' . \esc_attr( $uid ) . '" value="' . \esc_attr( $k ) . '" data-r18-cc-cat><span>' . \esc_html( $l ) . '</span></label>';
			}
		}
		$h .= '</fieldset>';
	}
	$h .= '<ol class="r18-cc">';
	foreach ( $lst as [ $p, $cle, $lib, $tri, $m ] ) {
		$jour = '9999' !== $tri ? \wp_date( 'j', (int) \strtotime( $tri . ' 12:00' ) ) : '';
		$mois = '9999' !== $tri ? \wp_date( 'M', (int) \strtotime( $tri . ' 12:00' ) ) : '';
		$h   .= '<li class="r18-cc__item is-' . \esc_attr( $cle ) . '" data-cat="' . \esc_attr( $m( 'r18_cat' ) ) . '"><div class="r18-cc__date" aria-hidden="true">' . ( '' !== $jour ? '<strong>' . \esc_html( $jour ) . '</strong><span>' . \esc_html( $mois ) . '</span>' : icone( 'bell' ) ) . '</div>';
		$off  = 'officiel' === $m( 'r18_origine' );
		$jr   = '9999' !== $tri && '' !== $tri ? (int) \floor( ( \strtotime( $tri . ' 00:00' ) - \strtotime( \wp_date( 'Y-m-d' ) . ' 00:00' ) ) / DAY_IN_SECONDS ) : -1;
		$h   .= '<div class="r18-cc__body"><p class="r18-cc__tags"><span class="r18-pill r18-cc__st">' . \esc_html( $lib ) . '</span>' . ( $jr >= 0 && $jr <= 60 ? '<span class="r18-pill r18-cc__j">' . ( 0 === $jr ? 'Aujourd’hui' : 'J-' . $jr ) . '</span>' : '' ) . '<span class="r18-pill r18-pill--grey">' . \esc_html( $cats[ $m( 'r18_cat' ) ] ?? '' ) . '</span>' . ( '' !== $m( 'r18_session' ) ? '<span class="r18-pill r18-pill--grey">' . \esc_html( 'Session ' . $m( 'r18_session' ) ) . '</span>' : '' ) . '</p>';
		if ( $off ) {
			$h .= '<h3 class="r18-cc__title"><a href="' . \esc_url( (string) \get_permalink( $p ) ) . '">' . \esc_html( ( 'Examen professionnel' === $m( 'r18_type' ) ? 'Examen professionnel : ' : 'Concours : ' ) . \get_the_title( $p ) ) . '</a></h3>';
		} else {
			$h .= '<h3 class="r18-cc__title">' . ( '' !== $m( 'r18_url' ) ? '<a href="' . \esc_url( $m( 'r18_url' ) ) . '" target="_blank" rel="noopener">' . \esc_html( \get_the_title( $p ) ) . '<span class="screen-reader-text"> ' . \esc_html__( '(nouvel onglet)', 'g2rd-rescue18-child' ) . '</span></a>' : \esc_html( \get_the_title( $p ) ) ) . '</h3>';
		}
		$h   .= '' !== $m( 'r18_organisateur' ) ? '<p class="r18-cc__org">' . \esc_html( $m( 'r18_organisateur' ) ) . '</p>' : '';
		$d    = array();
		if ( '' !== $m( 'r18_insc_debut' ) || '' !== $m( 'r18_insc_fin' ) ) {
			$d[] = \sprintf( /* translators: 1 : début, 2 : fin. */ \__( 'Inscriptions : %1$s → %2$s', 'g2rd-rescue18-child' ), date_lisible( $m( 'r18_insc_debut' ), 'j M' ) ?: '…', date_lisible( $m( 'r18_insc_fin' ), 'j M Y' ) ?: '…' );
		}
		if ( '' !== $m( 'r18_dossier' ) ) {
			$d[] = \sprintf( /* translators: %s : date. */ \__( 'Dossier avant le %s', 'g2rd-rescue18-child' ), date_lisible( $m( 'r18_dossier' ), 'j M Y' ) );
		}
		if ( '' !== $m( 'r18_epreuves' ) ) {
			$d[] = \sprintf( /* translators: %s : date. */ \__( 'Épreuves : %s', 'g2rd-rescue18-child' ), date_lisible( $m( 'r18_epreuves' ) ) );
		}
		$h .= $d ? '<p class="r18-cc__dates">' . \esc_html( \implode( ' · ', $d ) ) . '</p>' : '';
		$h .= '' !== $p->post_excerpt ? '<p class="r18-cc__ex">' . \esc_html( \wp_trim_words( $p->post_excerpt, 30 ) ) . '</p>' : ( $off ? '<p class="r18-cc__ex">' . \esc_html( \implode( ' · ', \array_filter( array( '' !== $m( 'r18_categorie_fp' ) ? 'Catégorie ' . $m( 'r18_categorie_fp' ) : '', '' !== $m( 'r18_specialites' ) ? 'Spécialités : ' . $m( 'r18_specialites' ) : '' ) ) ) ) . '</p>' : '' );
		$h .= '</div></li>';
	}
	$h .= '</ol><p class="r18-src-note">' . \esc_html__( 'Dates relevées chaque matin sur Concours-Territorial, le portail des centres de gestion, et dans les annonces des SDIS. Seul l\'arrêté d\'ouverture du centre organisateur fait foi.', 'g2rd-rescue18-child' ) . '</p>';
	return wrap( 'r18-ccs' . ( $page ? ' is-page' : '' ), $h, 'div', array( 'data-r18-cc' => '' ) );
}
