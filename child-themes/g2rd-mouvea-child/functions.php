<?php
/**
 * Thème enfant G2RD — Moovéa
 *
 * @package G2RD_Moovea
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const G2RD_MOOVEA_VERSION = '1.10.4';

/**
 * Polices Google Fonts du projet moovéa.
 *
 * Playfair Display (titres) · DM Sans (corps) · Cormorant (voix).
 * Les familles sont déclarées dans styles/moovea.json, dans ce thème enfant ;
 * ici on ne fait que charger les fichiers.
 *
 * Pour passer en auto-hébergé (RGPD) : déposer les .woff2 dans
 * assets/fonts/, remplacer cette fonction par un @font-face local
 * et ajouter les "fontFace" correspondants dans moovea.json.
 */
function g2rd_moovea_fonts_url(): string {
	return 'https://fonts.googleapis.com/css2'
		. '?family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,500'
		. '&family=DM+Sans:wght@300;400;500'
		. '&family=Cormorant:ital,wght@1,400;1,500;1,600'
		. '&display=swap';
}

/**
 * Feuilles de style front.
 */
function g2rd_moovea_enqueue_assets(): void {
	// Preconnect pour couper la latence sur le premier rendu.
	wp_enqueue_style(
		'g2rd-mouvea-child-fonts',
		g2rd_moovea_fonts_url(),
		array(),
		null
	);

	wp_enqueue_style(
		'g2rd-mouvea-child',
		get_stylesheet_directory_uri() . '/style.css',
		array( 'g2rd-mouvea-child-fonts' ),
		G2RD_MOOVEA_VERSION
	);

	wp_enqueue_script(
		'g2rd-mouvea-child',
		get_stylesheet_directory_uri() . '/assets/js/moovea.js',
		array(),
		G2RD_MOOVEA_VERSION,
		array( 'strategy' => 'defer', 'in_footer' => true )
	);
}
add_action( 'wp_enqueue_scripts', 'g2rd_moovea_enqueue_assets', 20 );

/**
 * Preconnect vers les domaines Google Fonts.
 *
 * @param string[] $urls          URLs déjà planifiées.
 * @param string   $relation_type Type de relation.
 * @return string[]
 */
function g2rd_moovea_resource_hints( array $urls, string $relation_type ): array {
	if ( 'preconnect' === $relation_type ) {
		$urls[] = array( 'href' => 'https://fonts.googleapis.com' );
		$urls[] = array(
			'href'        => 'https://fonts.gstatic.com',
			'crossorigin' => 'anonymous',
		);
	}
	return $urls;
}
add_filter( 'wp_resource_hints', 'g2rd_moovea_resource_hints', 10, 2 );

/**
 * Mêmes polices et mêmes styles dans l'éditeur de blocs.
 */
function g2rd_moovea_editor_assets(): void {
	add_editor_style(
		array(
			g2rd_moovea_fonts_url(),
			'style.css',
		)
	);
}
add_action( 'after_setup_theme', 'g2rd_moovea_editor_assets' );

/**
 * Année courante dans les blocs de texte.
 *
 * Écrire {{annee}} dans un paragraphe le remplace par l'année en cours au rendu.
 * Sert au copyright du pied de page, qui se met ainsi à jour tout seul —
 * un bloc de paragraphe ne sait pas exécuter de PHP, et un texte figé finit
 * toujours par dater.
 *
 * @param string $content Rendu du bloc.
 * @return string
 */
function g2rd_moovea_annee_courante( string $content ): string {
	if ( false === strpos( $content, '{{annee}}' ) ) {
		return $content;
	}
	return str_replace( '{{annee}}', esc_html( wp_date( 'Y' ) ), $content );
}
add_filter( 'render_block_core_paragraph', 'g2rd_moovea_annee_courante' );
add_filter( 'render_block_core_heading', 'g2rd_moovea_annee_courante' );

/* ==========================================================================
   Places restantes sur l'offre Founders
   ==========================================================================

   Le compte était écrit en dur à six endroits. Sur une page dont la rareté est
   l'argument principal, un chiffre figé se remarque : il ne bouge jamais, ou
   il remonte après une correction. Désormais un seul endroit fait foi, et
   trois jetons l'affichent partout :

       {{places}}         le nombre de places restantes
       {{places-total}}   le total de l'offre
       {{places-prises}}  le nombre déjà pris

   Réglage : Réglages › Général, section « Moovéa — offre Founders ».
   Même mécanisme que {{annee}} : un paragraphe ou un titre ne sait pas
   exécuter de PHP, on remplace au rendu du bloc.
   ========================================================================== */

const G2RD_MOOVEA_OPT_TOTAL  = 'g2rd_moovea_places_total';
const G2RD_MOOVEA_OPT_PRISES = 'g2rd_moovea_places_prises';
const G2RD_MOOVEA_OPT_AUTO   = 'g2rd_moovea_places_auto';
const G2RD_MOOVEA_OPT_FORM   = 'g2rd_moovea_places_form';
const G2RD_MOOVEA_OPT_TARIF  = 'g2rd_moovea_tarif_founders';

/**
 * Compte les inscriptions enregistrées par un formulaire Fluent Forms.
 *
 * Mis en cache cinq minutes : le jeton peut apparaître plusieurs fois sur une
 * même page, et une requête par occurrence serait du gâchis.
 *
 * @param int $form_id Identifiant du formulaire.
 * @return int
 */
function g2rd_moovea_compte_inscriptions( int $form_id ): int {
	$cle = 'g2rd_moovea_inscriptions_' . $form_id;
	$cache = get_transient( $cle );
	if ( false !== $cache ) {
		return (int) $cache;
	}

	global $wpdb;
	$table = $wpdb->prefix . 'fluentform_submissions';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Fluent Forms n'expose pas d'API de comptage.
	$existe = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
	if ( $existe !== $table ) {
		return 0;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$nombre = (int) $wpdb->get_var(
		$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE form_id = %d AND status != 'trashed'", $form_id )
	);

	set_transient( $cle, $nombre, 5 * MINUTE_IN_SECONDS );
	return $nombre;
}

/**
 * État de l'offre Founders.
 *
 * @return array{total:int,prises:int,restantes:int}
 */
function g2rd_moovea_places(): array {
	$total = max( 1, (int) get_option( G2RD_MOOVEA_OPT_TOTAL, 30 ) );

	if ( (bool) get_option( G2RD_MOOVEA_OPT_AUTO, false ) ) {
		$prises = g2rd_moovea_compte_inscriptions( (int) get_option( G2RD_MOOVEA_OPT_FORM, 4 ) );
	} else {
		$prises = (int) get_option( G2RD_MOOVEA_OPT_PRISES, 12 );
	}

	$prises = max( 0, min( $prises, $total ) );

	return array(
		'total'     => $total,
		'prises'    => $prises,
		'restantes' => $total - $prises,
	);
}

/**
 * Remplace les jetons de places dans un bloc rendu.
 *
 * @param string $content Rendu du bloc.
 * @return string
 */
function g2rd_moovea_jetons_places( string $content ): string {
	if ( false === strpos( $content, '{{places' ) ) {
		return $content;
	}

	$p = g2rd_moovea_places();

	return str_replace(
		array( '{{places-total}}', '{{places-prises}}', '{{places}}' ),
		array( (string) $p['total'], (string) $p['prises'], (string) $p['restantes'] ),
		$content
	);
}

/**
 * Remplace le jeton du tarif Founders.
 *
 * Séparé des places : le tarif n'a pas la même durée de vie et, surtout, il ne
 * doit PAS être posé dans les CGV. Un document contractuel dont le prix change
 * tout seul réécrirait les conditions de celles qui ont déjà signé. Là-bas, le
 * chiffre reste écrit à la main, et on l'aligne consciemment.
 *
 * @param string $content Rendu du bloc.
 * @return string
 */
function g2rd_moovea_jeton_tarif( string $content ): string {
	if ( false === strpos( $content, '{{tarif-founders}}' ) ) {
		return $content;
	}
	return str_replace( '{{tarif-founders}}', esc_html( (string) get_option( G2RD_MOOVEA_OPT_TARIF, '65' ) ), $content );
}
/**
 * Applique les jetons à tout bloc rendu.
 *
 * ATTENTION au nom du crochet. WordPress declenche `render_block_{$nom}` ou
 * `$nom` est le nom COMPLET du bloc, barre oblique comprise :
 * « render_block_core/paragraph ». La forme a tirets bas
 * « render_block_core_paragraph » n'existe pas et ne se declenche jamais —
 * c'est le nom de la fonction de rendu de core, pas celui d'un filtre.
 *
 * On passe donc par le filtre general `render_block`, qui a deux avantages :
 * il ne depend d'aucun nom de bloc, et il couvre aussi les parties de modele
 * comme la barre Founders, que `the_content` ne traverse pas.
 *
 * Le test `strpos` sort immediatement pour l'ecrasante majorite des blocs, le
 * cout sur une page est negligeable.
 *
 * @param string $content Rendu du bloc.
 * @return string
 */
function g2rd_moovea_jetons( string $content ): string {
	if ( false === strpos( $content, '{{' ) ) {
		return $content;
	}

	return g2rd_moovea_jeton_tarif( g2rd_moovea_jetons_places( $content ) );
}
add_filter( 'render_block', 'g2rd_moovea_jetons' );

/**
 * Réglages › Général : la section « Moovéa — offre Founders ».
 *
 * Volontairement dans un écran que la cliente connaît déjà, plutôt qu'une page
 * d'administration de plus à trouver.
 */
function g2rd_moovea_reglages_places(): void {
	register_setting( 'general', G2RD_MOOVEA_OPT_TOTAL, array( 'type' => 'integer', 'sanitize_callback' => 'absint', 'default' => 30 ) );
	register_setting( 'general', G2RD_MOOVEA_OPT_PRISES, array( 'type' => 'integer', 'sanitize_callback' => 'absint', 'default' => 12 ) );
	register_setting( 'general', G2RD_MOOVEA_OPT_AUTO, array( 'type' => 'boolean', 'sanitize_callback' => 'rest_sanitize_boolean', 'default' => false ) );
	register_setting( 'general', G2RD_MOOVEA_OPT_FORM, array( 'type' => 'integer', 'sanitize_callback' => 'absint', 'default' => 4 ) );
	register_setting( 'general', G2RD_MOOVEA_OPT_TARIF, array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => '65' ) );

	add_settings_section(
		'g2rd_moovea_founders',
		__( 'Moovéa — offre Founders', 'g2rd-mouvea-child' ),
		'g2rd_moovea_reglages_intro',
		'general'
	);

	add_settings_field( G2RD_MOOVEA_OPT_TOTAL, __( 'Places au total', 'g2rd-mouvea-child' ), 'g2rd_moovea_champ_total', 'general', 'g2rd_moovea_founders' );
	add_settings_field( G2RD_MOOVEA_OPT_PRISES, __( 'Places déjà prises', 'g2rd-mouvea-child' ), 'g2rd_moovea_champ_prises', 'general', 'g2rd_moovea_founders' );
	add_settings_field( G2RD_MOOVEA_OPT_AUTO, __( 'Compter automatiquement', 'g2rd-mouvea-child' ), 'g2rd_moovea_champ_auto', 'general', 'g2rd_moovea_founders' );
	add_settings_field( G2RD_MOOVEA_OPT_TARIF, __( 'Tarif mensuel', 'g2rd-mouvea-child' ), 'g2rd_moovea_champ_tarif', 'general', 'g2rd_moovea_founders' );
}
add_action( 'admin_init', 'g2rd_moovea_reglages_places' );

/**
 * Texte d'introduction de la section de réglages.
 */
function g2rd_moovea_reglages_intro(): void {
	$p = g2rd_moovea_places();
	printf(
		'<p>%s <code>{{places}}</code>, <code>{{places-total}}</code>, <code>{{places-prises}}</code> %s<br><strong>%s</strong></p>',
		esc_html__( 'Écrire', 'g2rd-mouvea-child' ),
		esc_html__( 'dans un titre, un paragraphe ou une liste affiche la valeur du moment.', 'g2rd-mouvea-child' ),
		esc_html( sprintf( 'Actuellement : %d places restantes sur %d.', $p['restantes'], $p['total'] ) )
	);
}

/**
 * Champ : places au total.
 */
function g2rd_moovea_champ_total(): void {
	printf(
		'<input type="number" min="1" id="%1$s" name="%1$s" value="%2$d" class="small-text">',
		esc_attr( G2RD_MOOVEA_OPT_TOTAL ),
		(int) get_option( G2RD_MOOVEA_OPT_TOTAL, 30 )
	);
}

/**
 * Champ : places déjà prises.
 */
function g2rd_moovea_champ_prises(): void {
	printf(
		'<input type="number" min="0" id="%1$s" name="%1$s" value="%2$d" class="small-text"> <span class="description">%3$s</span>',
		esc_attr( G2RD_MOOVEA_OPT_PRISES ),
		(int) get_option( G2RD_MOOVEA_OPT_PRISES, 12 ),
		esc_html__( 'Ignoré si le comptage automatique est actif.', 'g2rd-mouvea-child' )
	);
}

/**
 * Champ : tarif mensuel de l'offre Founders.
 */
function g2rd_moovea_champ_tarif(): void {
	printf(
		'<input type="text" id="%1$s" name="%1$s" value="%2$s" class="small-text"> € <span class="description">%3$s</span>',
		esc_attr( G2RD_MOOVEA_OPT_TARIF ),
		esc_attr( (string) get_option( G2RD_MOOVEA_OPT_TARIF, '65' ) ),
		esc_html__( 'Affiché par {{tarif-founders}}. Ne pas utiliser ce jeton dans les CGV : le prix y reste écrit à la main.', 'g2rd-mouvea-child' )
	);
}

/**
 * Champ : comptage automatique des inscriptions.
 */
function g2rd_moovea_champ_auto(): void {
	$form = (int) get_option( G2RD_MOOVEA_OPT_FORM, 4 );
	printf(
		'<label><input type="checkbox" id="%1$s" name="%1$s" value="1" %2$s> %3$s</label>
		<p class="description">%4$s</p>
		<p><label>%5$s <input type="number" min="1" id="%6$s" name="%6$s" value="%7$d" class="small-text"></label></p>',
		esc_attr( G2RD_MOOVEA_OPT_AUTO ),
		checked( (bool) get_option( G2RD_MOOVEA_OPT_AUTO, false ), true, false ),
		esc_html__( 'Déduire les places prises du nombre d\'inscriptions au formulaire', 'g2rd-mouvea-child' ),
		esc_html__( 'Attention : une inscription au formulaire est une manifestation d\'intérêt, pas une place payée. Laisser décoché tant que les Founders ne sont pas encaissées.', 'g2rd-mouvea-child' ),
		esc_html__( 'Formulaire Fluent Forms n°', 'g2rd-mouvea-child' ),
		esc_attr( G2RD_MOOVEA_OPT_FORM ),
		$form
	);
}

/**
 * Charge la traduction du thème enfant.
 */
function g2rd_moovea_load_textdomain(): void {
	load_child_theme_textdomain( 'g2rd-mouvea-child', get_stylesheet_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'g2rd_moovea_load_textdomain' );
