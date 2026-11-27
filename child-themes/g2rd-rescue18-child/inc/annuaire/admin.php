<?php
/**
 * Annuaire et recrutement : back-office.
 *
 * Menu « Annuaire et recrutement » : tableau de bord, réglages, fiches SDIS,
 * offres, actualités, calendrier des concours. Chaque fiche se modifie dans
 * un panneau clair ; une valeur changée à la main est verrouillée contre la
 * synchronisation (cadenas décochable).
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18\Annuaire;

use function G2RD_Rescue18\Veille\bouton;
use function G2RD_Rescue18\Veille\etat;

\defined( 'ABSPATH' ) || exit;

const PAGE_ADMIN = 'rescue18-annuaire';

\add_action(
	'admin_menu',
	static function (): void {
		$a_valider = (int) ( \wp_count_posts( 'r18_concours' )->draft ?? 0 );
		$bulle     = $a_valider > 0 ? ' <span class="awaiting-mod">' . $a_valider . '</span>' : '';
		\add_menu_page( 'Annuaire et recrutement', 'Annuaire SDIS' . $bulle, 'edit_posts', PAGE_ADMIN, __NAMESPACE__ . '\ecran', 'dashicons-location-alt', 28 );
		\add_submenu_page( PAGE_ADMIN, 'Tableau de bord', 'Tableau de bord', 'edit_posts', PAGE_ADMIN, __NAMESPACE__ . '\ecran' );
	}
);

\add_action(
	'admin_enqueue_scripts',
	static function ( string $hook ): void {
		$ecran = \function_exists( 'get_current_screen' ) ? \get_current_screen() : null;
		$types = array( 'r18_service', 'r18_offre', 'r18_actu_sdis', 'r18_concours' );
		if ( 'toplevel_page_' . PAGE_ADMIN !== $hook && ! ( $ecran && \in_array( $ecran->post_type, $types, true ) ) ) {
			return;
		}
		foreach ( array( 'r18-veille' => 'assets/css/veille-admin.css', 'r18-annuaire-admin' => 'assets/css/annuaire-admin.css' ) as $h => $f ) {
			$a = \G2RD_Rescue18\asset( $f );
			if ( $a ) {
				\wp_enqueue_style( $h, $a[0], array(), $a[1] );
			}
		}
		if ( 'toplevel_page_' . PAGE_ADMIN === $hook ) {
			\G2RD_Rescue18\Veille\charger_js();
		}
	}
);

/**
 * Tuile chiffrée.
 *
 * @param string $icone Dashicon.
 * @param string $val   Valeur.
 * @param string $lib   Libellé.
 * @param string $lien  Lien.
 * @return string
 */
function tuile( string $icone, string $val, string $lib, string $lien ): string {
	return '<a class="r18a-tile" href="' . \esc_url( $lien ) . '"><span class="dashicons dashicons-' . \esc_attr( $icone ) . '" aria-hidden="true"></span><strong>' . \esc_html( $val ) . '</strong><span>' . \esc_html( $lib ) . '</span></a>';
}

/**
 * Ligne d'état d'une source automatique.
 *
 * @param string $id  Connecteur.
 * @param string $nom Nom lisible.
 * @param string $rythme Rythme.
 * @return string
 */
function ligne_source( string $id, string $nom, string $rythme ): string {
	$e   = etat( $id );
	$res = (string) ( $e['resultat'] ?? '' );
	$lib = array(
		'ok'      => array( 'ok', 'À jour' ),
		'partiel' => array( 'warn', 'Partiel' ),
		'erreur'  => array( 'err', 'Erreur' ),
		'bloque'  => array( 'err', 'Bloqué par la source' ),
	)[ $res ] ?? array( 'idle', 'Jamais lancé' );
	$quand = ! empty( $e['verif'] ) ? 'il y a ' . \human_time_diff( (int) $e['verif'] ) : '—';
	$h     = '<tr><th scope="row">' . \esc_html( $nom ) . '<small>' . \esc_html( $rythme ) . '</small></th>';
	$h    .= '<td><span class="r18a-dot is-' . \esc_attr( $lib[0] ) . '"></span>' . \esc_html( $lib[1] ) . '</td><td>' . \esc_html( $quand ) . '</td>';
	$h    .= '<td>' . (int) ( $e['nouveaux'] ?? 0 ) . ' nouveau(x), ' . (int) ( $e['actualises'] ?? 0 ) . ' actualisé(s)</td><td>';
	$h    .= bouton( 'r18v_sync', '<span class="dashicons dashicons-update" aria-hidden="true"></span>Lancer', array( 'cible' => $id ), 'r18v-btn' );
	return $h . '</td></tr>';
}

/**
 * Écran : tableau de bord et réglages.
 */
function ecran(): void {
	if ( ! \current_user_can( 'edit_posts' ) ) {
		return;
	}
	$reg  = reglages();
	$svc  = services();
	$flux = \count( \array_filter( $svc, static fn( array $s ): bool => '' !== $s['flux'] ) );
	$offr = (int) ( \wp_count_posts( 'r18_offre' )->publish ?? 0 );
	$actu = (int) ( \wp_count_posts( 'r18_actu_sdis' )->publish ?? 0 );
	$conc = \wp_count_posts( 'r18_concours' );
	$cas  = (array) \get_option( 'r18_casernes', array() );
	$msg  = \sanitize_key( \wp_unslash( $_GET['r18msg'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- affichage.
	$admin_edit = static fn( string $t, array $q = array() ): string => \add_query_arg( \array_merge( array( 'post_type' => $t ), $q ), \admin_url( 'edit.php' ) );

	echo '<div class="wrap r18v r18a"><h1 class="screen-reader-text">Annuaire et recrutement</h1>';
	echo '<header class="r18v-hero"><div><p class="r18v-kicker">Rescue 18 · Pompiers de France</p><p class="r18a-title">Annuaire et recrutement</p>';
	echo '<p class="r18v-sub">Fiches des SDIS, de la BSPP et du BMPM, actualités des services, offres d\'emploi et calendrier des concours. Tout se met à jour seul chaque nuit. Tu gardes la main sur chaque fiche.</p></div>';
	echo '<div class="r18v-hero__act"><a class="r18v-btn is-ghost" href="' . \esc_url( (string) \get_post_type_archive_link( 'r18_service' ) ) . '" target="_blank" rel="noopener">Voir l\'annuaire</a>';
	echo '<a class="r18v-btn is-ghost" href="' . \esc_url( \admin_url( 'admin.php?page=rescue18-automatisations' ) ) . '">Journal des automatisations</a></div></header>';
	if ( 'enregistre' === $msg ) {
		echo '<p class="r18v-toast" role="status"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>Réglages enregistrés.</p>';
	}
	if ( empty( $svc ) ) {
		echo '<section class="r18a-start"><h2>Première étape : importer l\'annuaire</h2><p>Un clic récupère les 98 services officiels (Annuaire de l\'administration), puis le BMPM. Compte une minute.</p>';
		echo bouton( 'r18v_sync', '<span class="dashicons dashicons-download" aria-hidden="true"></span>Importer les SDIS maintenant', array( 'cible' => 'annuaire' ), 'r18v-btn is-primary' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</section>';
	}

	echo '<div class="r18a-tiles">';
	echo tuile( 'location-alt', (string) \count( $svc ), 'services dans l\'annuaire', $admin_edit( 'r18_service' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo tuile( 'rss', $flux . ' / ' . \count( $svc ), 'flux d\'actualités trouvés', $admin_edit( 'r18_service' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo tuile( 'megaphone', (string) $actu, 'actualités en ligne', $admin_edit( 'r18_actu_sdis' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo tuile( 'businessperson', (string) $offr, 'offres d\'emploi en ligne', $admin_edit( 'r18_offre' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo tuile( 'calendar-alt', (string) (int) ( $conc->publish ?? 0 ), 'dates au calendrier', $admin_edit( 'r18_concours' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo tuile( 'admin-home', isset( $cas['n'] ) ? \number_format_i18n( (int) $cas['n'] ) : '—', 'casernes sur la carte', \admin_url( 'admin.php?page=rescue18-automatisations' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</div>';

	if ( (int) ( $conc->draft ?? 0 ) > 0 ) {
		echo '<p class="r18a-todo"><span class="dashicons dashicons-flag" aria-hidden="true"></span><strong>' . (int) $conc->draft . ' date(s) de concours ou de recrutement repérée(s) dans les actualités.</strong> Ajoute les dates d\'inscription puis publie. <a href="' . \esc_url( $admin_edit( 'r18_concours', array( 'post_status' => 'draft' ) ) ) . '">Les vérifier</a></p>';
	}

	echo '<h2 class="r18v-h2">Sources automatiques</h2><table class="r18a-src"><thead><tr><th scope="col">Source</th><th scope="col">État</th><th scope="col">Dernier passage</th><th scope="col">Dernier résultat</th><th scope="col"><span class="screen-reader-text">Action</span></th></tr></thead><tbody>';
	echo ligne_source( 'annuaire', 'Annuaire officiel des SDIS', 'chaque semaine · DILA' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo ligne_source( 'sdis', 'Actualités des services', 'chaque nuit · sites officiels' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo ligne_source( 'offres', 'Offres d\'emploi', 'chaque semaine · Choisir le Service Public' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo ligne_source( 'casernes', 'Casernes sur la carte', 'chaque mois · OpenStreetMap' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</tbody></table><p class="r18v-hint">Activer, mettre en pause ou simuler une source : menu Automatisations. Les lancements manuels avancent tant que la page reste ouverte.</p>';

	$champ = static function ( string $k, string $lib, string $type = 'text', string $aide = '', array $opts = array() ) use ( $reg ): string {
		$id = 'r18a-' . $k;
		$v  = $reg[ $k ] ?? '';
		if ( 'checkbox' === $type ) {
			return '<label class="r18v-switch"><input type="checkbox" name="' . \esc_attr( $k ) . '" value="1"' . \checked( ! empty( $v ), true, false ) . '><span aria-hidden="true"></span>' . \esc_html( $lib ) . '</label>' . ( '' !== $aide ? '<p class="r18v-hint">' . \esc_html( $aide ) . '</p>' : '' );
		}
		if ( 'radio' === $type ) {
			$h = '<fieldset class="r18v-seg"><legend>' . \esc_html( $lib ) . '</legend>';
			foreach ( $opts as $ov => $ol ) {
				$h .= '<label><input type="radio" name="' . \esc_attr( $k ) . '" value="' . \esc_attr( $ov ) . '"' . \checked( (string) $v, (string) $ov, false ) . '><span>' . \esc_html( $ol ) . '</span></label>';
			}
			return $h . '</fieldset>' . ( '' !== $aide ? '<p class="r18v-hint">' . \esc_html( $aide ) . '</p>' : '' );
		}
		return '<label for="' . \esc_attr( $id ) . '">' . \esc_html( $lib ) . '</label><input id="' . \esc_attr( $id ) . '" type="' . \esc_attr( $type ) . '" name="' . \esc_attr( $k ) . '" value="' . \esc_attr( (string) $v ) . '"' . ( 'number' === $type ? ' min="1" max="365"' : '' ) . ' class="regular-text">' . ( '' !== $aide ? '<p class="r18v-hint">' . \esc_html( $aide ) . '</p>' : '' );
	};

	echo '<h2 class="r18v-h2">Réglages</h2><form method="post" action="' . \esc_url( \admin_url( 'admin-post.php' ) ) . '" class="r18a-settings">';
	\wp_nonce_field( 'r18a_reglages' );
	echo '<input type="hidden" name="action" value="r18a_reglages">';
	echo '<section class="r18a-box"><h3><span class="dashicons dashicons-megaphone" aria-hidden="true"></span>Actualités des SDIS</h3>';
	echo $champ( 'actus_publication', 'Publication des articles', 'radio', 'Avec validation, chaque article attend ton accord dans Actualités des SDIS.', array( 'auto' => 'Automatique', 'validation' => 'Avec validation' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $champ( 'actus_jours', 'Ne récupérer que les articles de moins de (jours)', 'number' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $champ( 'actus_par_service', 'Articles maximum par service et par jour', 'number' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $champ( 'actus_conserve', 'Supprimer les articles après (jours)', 'number', 'Ce sont des résumés avec lien vers le site du SDIS, pas des copies.' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $champ( 'actus_exclus', 'Écarter les titres contenant (séparés par des virgules)', 'text', 'Exemple : marché public, appel d\'offres.' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</section><section class="r18a-box"><h3><span class="dashicons dashicons-businessperson" aria-hidden="true"></span>Offres d\'emploi</h3>';
	echo $champ( 'offres_publication', 'Publication des offres', 'radio', '', array( 'auto' => 'Automatique', 'validation' => 'Avec validation' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $champ( 'offres_exclus', 'Écarter les offres contenant (séparés par des virgules)', 'text', 'Exemple : stage, apprentissage.' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<p class="r18v-hint">Une offre disparue de Choisir le Service Public ou dont la date limite est passée passe en brouillon. Rien n\'est supprimé.</p>';
	echo '</section><section class="r18a-box"><h3><span class="dashicons dashicons-format-image" aria-hidden="true"></span>Carte et images</h3>';
	echo $champ( 'carte_fond', 'Fond de carte', 'radio', 'Cartes de l\'IGN (Géoplateforme, gratuites) ou OpenStreetMap.', array( 'plan' => 'Plan IGN', 'ortho' => 'Photos aériennes IGN', 'osm' => 'OpenStreetMap' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $champ( 'casernes', 'Afficher les casernes (bouton sur la carte)', 'checkbox' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $champ( 'images', 'Afficher les images et logos des sites officiels', 'checkbox', 'Les images restent hébergées par les SDIS : rien n\'est copié sur ton serveur. Le navigateur du visiteur les charge depuis le site d\'origine.' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</section><section class="r18a-box"><h3><span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>Concours et parcours</h3>';
	echo $champ( 'detect_concours', 'Repérer les concours et campagnes de recrutement dans les actualités', 'checkbox', 'Chaque annonce crée un brouillon dans Concours et recrutements. Tu complètes les dates et tu publies.' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $champ( 'lien_spv', 'Lien « Devenir sapeur-pompier volontaire »', 'url' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $champ( 'lien_bspp', 'Lien recrutement BSPP', 'url' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $champ( 'lien_bmpm', 'Lien recrutement BMPM (Marine nationale)', 'url' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $champ( 'lien_csp', 'Lien Choisir le Service Public', 'url' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</section><p class="r18a-save"><button type="submit" class="r18v-btn is-primary"><span class="dashicons dashicons-saved" aria-hidden="true"></span>Enregistrer les réglages</button></p></form>';

	echo '<h2 class="r18v-h2">Où ça s\'affiche</h2><ul class="r18a-where">';
	foreach ( array(
		array( (string) \get_post_type_archive_link( 'r18_service' ), 'Annuaire et carte', 'bloc « Annuaire des SDIS »' ),
		array( \home_url( '/actualites-des-sdis/' ), 'Actualités des SDIS', 'bloc « Fil des SDIS »' ),
		array( \home_url( '/recrutement/' ), 'Recrutement', 'parcours, calendrier, dernières offres' ),
		array( \home_url( '/recrutement/offres-emploi/' ), 'Offres d\'emploi', 'bloc « Offres d\'emploi »' ),
		array( \home_url( '/recrutement/concours/' ), 'Calendrier des concours', 'bloc « Calendrier des concours »' ),
	) as [ $u, $t, $d ] ) {
		echo '<li><a href="' . \esc_url( $u ) . '" target="_blank" rel="noopener">' . \esc_html( $t ) . '</a><span>' . \esc_html( $d ) . '</span></li>';
	}
	echo '</ul><p class="r18v-hint">Les blocs se placent aussi dans n\'importe quelle page depuis l\'éditeur (catégorie Rescue 18).</p></div>';
}

\add_action(
	'admin_post_r18a_reglages',
	static function (): void {
		if ( ! \current_user_can( 'manage_options' ) || ! \check_admin_referer( 'r18a_reglages' ) ) {
			\wp_die( 'Action non autorisée.' );
		}
		$in  = \wp_unslash( $_POST );
		$num = static fn( string $k, int $min, int $max, int $d ): int => \max( $min, \min( $max, (int) ( $in[ $k ] ?? $d ) ) );
		\update_option(
			OPT_REG,
			array(
				'actus_publication'  => 'validation' === ( $in['actus_publication'] ?? '' ) ? 'validation' : 'auto',
				'actus_jours'        => $num( 'actus_jours', 1, 365, 45 ),
				'actus_par_service'  => $num( 'actus_par_service', 1, 20, 6 ),
				'actus_conserve'     => $num( 'actus_conserve', 7, 730, 120 ),
				'actus_exclus'       => \sanitize_text_field( (string) ( $in['actus_exclus'] ?? '' ) ),
				'offres_publication' => 'validation' === ( $in['offres_publication'] ?? '' ) ? 'validation' : 'auto',
				'offres_exclus'      => \sanitize_text_field( (string) ( $in['offres_exclus'] ?? '' ) ),
				'carte_fond'         => \in_array( $in['carte_fond'] ?? '', array( 'plan', 'ortho', 'osm' ), true ) ? $in['carte_fond'] : 'plan',
				'casernes'           => ! empty( $in['casernes'] ),
				'images'             => ! empty( $in['images'] ),
				'detect_concours'    => ! empty( $in['detect_concours'] ),
				'lien_spv'           => \esc_url_raw( (string) ( $in['lien_spv'] ?? '' ) ),
				'lien_bspp'          => \esc_url_raw( (string) ( $in['lien_bspp'] ?? '' ) ),
				'lien_bmpm'          => \esc_url_raw( (string) ( $in['lien_bmpm'] ?? '' ) ),
				'lien_csp'           => \esc_url_raw( (string) ( $in['lien_csp'] ?? '' ) ),
			),
			false
		);
		\wp_safe_redirect( \admin_url( 'admin.php?page=' . PAGE_ADMIN . '&r18msg=enregistre' ) );
		exit;
	}
);

/* -------------------------------------------------------------------------
 * Panneau de modification des fiches
 * ---------------------------------------------------------------------- */

\add_action(
	'add_meta_boxes',
	static function (): void {
		$titres = array(
			'r18_service'   => 'Informations du service',
			'r18_offre'     => 'Détails de l\'offre',
			'r18_actu_sdis' => 'Article d\'origine',
			'r18_concours'  => 'Dates et lien officiel',
		);
		foreach ( $titres as $t => $titre ) {
			\add_meta_box( 'r18a-fiche', $titre, __NAMESPACE__ . '\panneau', $t, 'normal', 'high' );
		}
	}
);

/**
 * Panneau de champs.
 *
 * @param \WP_Post $p Fiche.
 */
function panneau( \WP_Post $p ): void {
	\wp_nonce_field( 'r18a_fiche', 'r18a_nonce' );
	$sync = \in_array( $p->post_type, array( 'r18_service', 'r18_offre', 'r18_actu_sdis' ), true );
	$v    = verrous( $p->ID );
	if ( $sync ) {
		echo '<p class="r18a-intro"><span class="dashicons dashicons-lock" aria-hidden="true"></span>Cette fiche se met à jour toute seule. Une valeur que tu modifies est verrouillée : la synchronisation ne la touche plus. Décoche le cadenas pour la rendre de nouveau automatique.</p>';
	}
	if ( 'r18_service' === $p->post_type ) {
		$img  = (string) \get_post_meta( $p->ID, 'r18_image', true );
		$logo = (string) \get_post_meta( $p->ID, 'r18_logo', true );
		if ( '' !== $img || '' !== $logo ) {
			echo '<div class="r18a-visuels">';
			if ( '' !== $img ) {
				echo '<figure><img src="' . \esc_url( $img ) . '" alt="" loading="lazy" referrerpolicy="no-referrer"><figcaption>Visuel</figcaption></figure>';
			}
			if ( '' !== $logo ) {
				echo '<figure class="is-logo"><img src="' . \esc_url( $logo ) . '" alt="" loading="lazy" referrerpolicy="no-referrer"><figcaption>Logo</figcaption></figure>';
			}
			echo '</div>';
		}
	}
	echo '<div class="r18a-grid">';
	foreach ( champs( $p->post_type ) as $k => $def ) {
		$val  = (string) \get_post_meta( $p->ID, $k, true );
		$id   = 'r18a-f-' . $k;
		$type = $def[1];
		$full = \in_array( $type, array( 'textarea', 'reseaux' ), true ) || 'r18_nom' === $k;
		echo '<div class="r18a-field' . ( $full ? ' is-full' : '' ) . ( 'checkbox' === $type ? ' is-check' : '' ) . '">';
		if ( 'checkbox' === $type ) {
			echo '<label><input type="checkbox" name="r18a[' . \esc_attr( $k ) . ']" value="1"' . \checked( '' !== $val, true, false ) . '> ' . \esc_html( $def[0] ) . '</label>';
		} else {
			echo '<label for="' . \esc_attr( $id ) . '">' . \esc_html( $def[0] ) . '</label>';
			if ( \str_starts_with( $type, 'select:' ) ) {
				echo '<select id="' . \esc_attr( $id ) . '" name="r18a[' . \esc_attr( $k ) . ']">';
				foreach ( \explode( '|', \substr( $type, 7 ) ) as $o ) {
					[ $ov, $ol ] = \array_pad( \explode( '=', $o, 2 ), 2, '' );
					echo '<option value="' . \esc_attr( $ov ) . '"' . \selected( $val, $ov, false ) . '>' . \esc_html( $ol ) . '</option>';
				}
				echo '</select>';
			} elseif ( 'reseaux' === $type ) {
				$lignes = array();
				foreach ( (array) \json_decode( $val, true ) as $r ) {
					$lignes[] = ( $r['reseau'] ?? '' ) . ' | ' . ( $r['url'] ?? '' );
				}
				echo '<textarea id="' . \esc_attr( $id ) . '" name="r18a[' . \esc_attr( $k ) . ']" rows="4">' . \esc_textarea( \implode( "\n", $lignes ) ) . '</textarea>';
			} elseif ( 'textarea' === $type ) {
				echo '<textarea id="' . \esc_attr( $id ) . '" name="r18a[' . \esc_attr( $k ) . ']" rows="5">' . \esc_textarea( $val ) . '</textarea>';
			} else {
				echo '<input id="' . \esc_attr( $id ) . '" type="' . \esc_attr( 'number' === $type ? 'number' : $type ) . '" name="r18a[' . \esc_attr( $k ) . ']" value="' . \esc_attr( $val ) . '">';
				if ( 'url' === $type && '' !== $val ) {
					echo '<a class="r18a-open" href="' . \esc_url( $val ) . '" target="_blank" rel="noopener noreferrer">Ouvrir<span class="screen-reader-text"> (nouvel onglet)</span></a>';
				}
			}
		}
		if ( $sync && 'checkbox' !== $type ) {
			echo '<label class="r18a-lock" title="Ne plus actualiser automatiquement"><input type="checkbox" name="r18a_lock[]" value="' . \esc_attr( $k ) . '"' . \checked( \in_array( $k, $v, true ), true, false ) . '><span class="dashicons dashicons-lock" aria-hidden="true"></span><span class="screen-reader-text">Ne plus actualiser « ' . \esc_html( $def[0] ) . ' » automatiquement</span></label>';
		}
		if ( ! empty( $def[2] ) ) {
			echo '<p class="r18a-help">' . \esc_html( $def[2] ) . '</p>';
		}
		echo '</div>';
	}
	echo '</div>';
}

/**
 * Nettoie une valeur selon son type de champ.
 *
 * @param string $type Type.
 * @param string $v    Valeur.
 * @return string
 */
function nettoyer( string $type, string $v ): string {
	switch ( true ) {
		case 'url' === $type:
			return \esc_url_raw( \trim( $v ) );
		case 'email' === $type:
			return \sanitize_email( $v );
		case 'number' === $type:
			return '' === \trim( $v ) ? '' : (string) \absint( $v );
		case 'date' === $type:
			return \preg_match( '/^\d{4}-\d{2}-\d{2}$/', $v ) ? $v : '';
		case 'checkbox' === $type:
			return '' !== $v ? '1' : '';
		case 'textarea' === $type:
			return \sanitize_textarea_field( $v );
		case 'reseaux' === $type:
			$out = array();
			foreach ( \preg_split( '/\R/u', $v ) as $l ) {
				$p = \array_map( 'trim', \explode( '|', $l, 2 ) );
				$u = \esc_url_raw( $p[1] ?? '' );
				if ( '' !== $u ) {
					$out[] = array( 'reseau' => \sanitize_text_field( $p[0] ), 'url' => $u );
				}
			}
			return (string) \wp_json_encode( $out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		case \str_starts_with( $type, 'select:' ):
			$ok = \array_map( static fn( string $o ): string => \explode( '=', $o, 2 )[0], \explode( '|', \substr( $type, 7 ) ) );
			return \in_array( $v, $ok, true ) ? $v : ( $ok[0] ?? '' );
		default:
			return \sanitize_text_field( $v );
	}
}

\add_action(
	'save_post',
	static function ( int $id, \WP_Post $p ): void {
		if ( ! isset( $_POST['r18a_nonce'] ) || ! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['r18a_nonce'] ) ), 'r18a_fiche' ) || ! \current_user_can( 'edit_post', $id ) || \wp_is_post_revision( $id ) ) {
			return;
		}
		$champs = champs( $p->post_type );
		if ( empty( $champs ) ) {
			return;
		}
		$in     = (array) \wp_unslash( $_POST['r18a'] ?? array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nettoyé champ par champ.
		$locks  = \array_map( 'sanitize_key', (array) \wp_unslash( $_POST['r18a_lock'] ?? array() ) );
		$avant  = verrous( $id );
		$apres  = array();
		foreach ( $champs as $k => $def ) {
			$nouv = nettoyer( $def[1], (string) ( $in[ $k ] ?? '' ) );
			$anc  = (string) \get_post_meta( $id, $k, true );
			if ( 'reseaux' === $def[1] && \json_decode( $anc, true ) == \json_decode( $nouv, true ) ) { // phpcs:ignore Universal.Operators.StrictComparisonOperators -- comparaison de structures.
				$nouv = $anc;
			}
			$change = $nouv !== $anc;
			if ( $change ) {
				\update_post_meta( $id, $k, $nouv );
			}
			$coche = \in_array( $k, $locks, true );
			// Modifié à la main : verrouillé, sauf si l'utilisateur vient de décocher un cadenas déjà posé.
			if ( $coche || ( $change && ! \in_array( $k, $avant, true ) ) ) {
				$apres[] = $k;
			}
		}
		\update_post_meta( $id, 'r18_verrous', \implode( ',', \array_unique( $apres ) ) );
		\wp_cache_delete( 'services', 'r18_annuaire' );
	},
	20,
	2
);

/* -------------------------------------------------------------------------
 * Colonnes des listes
 * ---------------------------------------------------------------------- */

/**
 * Colonnes par type.
 *
 * @return array<string,array<string,string>>
 */
function colonnes(): array {
	return array(
		'r18_service'   => array( 'r18_logo' => 'Logo', 'r18_deps' => 'Départements', 'r18_region' => 'Région', 'r18_flux' => 'Actualités' ),
		'r18_offre'     => array( 'r18_employeur' => 'Employeur', 'r18_categorie' => 'Catégorie', 'r18_fin' => 'Date limite', 'r18_etat' => 'État' ),
		'r18_actu_sdis' => array( 'r18_service_id' => 'Service', 'r18_recrutement' => 'Recrutement', 'r18_url' => 'Source' ),
		'r18_concours'  => array( 'r18_cat' => 'Catégorie', 'r18_insc_fin' => 'Clôture', 'r18_epreuves' => 'Épreuves', 'r18_origine' => 'Origine' ),
	);
}

foreach ( \array_keys( colonnes() ) as $type ) {
	\add_filter(
		'manage_' . $type . '_posts_columns',
		static function ( array $c ) use ( $type ): array {
			$date = $c['date'] ?? null;
			unset( $c['date'] );
			$c = \array_merge( $c, colonnes()[ $type ] );
			if ( null !== $date ) {
				$c['date'] = $date;
			}
			return $c;
		}
	);
	\add_action(
		'manage_' . $type . '_posts_custom_column',
		static function ( string $col, int $id ): void {
			$v = (string) \get_post_meta( $id, $col, true );
			switch ( $col ) {
				case 'r18_logo':
					echo '' !== $v ? '<img class="r18a-thumb" src="' . \esc_url( $v ) . '" alt="" loading="lazy" referrerpolicy="no-referrer">' : '—';
					break;
				case 'r18_flux':
					echo '' !== $v ? '<span class="r18a-dot is-ok"></span>Flux trouvé' : ( (int) \get_post_meta( $id, 'r18_flux_verif', true ) > 0 ? '<span class="r18a-dot is-warn"></span>Aucun flux' : '<span class="r18a-dot is-idle"></span>Pas encore vérifié' );
					break;
				case 'r18_service_id':
					echo $v ? '<a href="' . \esc_url( (string) \get_edit_post_link( (int) $v ) ) . '">' . \esc_html( \get_the_title( (int) $v ) ) . '</a>' : '—';
					break;
				case 'r18_recrutement':
					echo '' !== $v ? '<span class="r18a-badge">Recrutement</span>' : '';
					break;
				case 'r18_url':
					echo '' !== $v ? '<a href="' . \esc_url( $v ) . '" target="_blank" rel="noopener noreferrer">' . \esc_html( (string) \wp_parse_url( $v, PHP_URL_HOST ) ) . '</a>' : '—';
					break;
				case 'r18_etat':
					echo \esc_html( array( 'retiree' => 'Retirée (absente de la source)', 'expiree' => 'Date limite passée' )[ $v ] ?? ( 'publish' === \get_post_status( $id ) ? 'En ligne' : '' ) );
					break;
				case 'r18_fin':
				case 'r18_insc_fin':
				case 'r18_epreuves':
					echo '' !== $v ? \esc_html( \wp_date( 'j M Y', (int) \strtotime( $v ) ) ) : '—';
					break;
				case 'r18_origine':
					echo 'auto' === $v ? '<span class="r18a-badge is-auto">Repéré automatiquement</span>' : 'Saisie';
					break;
				default:
					echo \esc_html( $v );
			}
		},
		10,
		2
	);
}
