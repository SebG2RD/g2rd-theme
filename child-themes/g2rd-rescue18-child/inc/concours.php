<?php
/**
 * Concours de sapeurs-pompiers : collecte quotidienne depuis Concours-Territorial.
 *
 * Source officielle : concours-territorial.fr, portail national des centres de
 * gestion de la fonction publique territoriale (GIP Informatique des CDG).
 * Filière « Sapeurs pompiers » : caporal, sergent, lieutenant, capitaine,
 * commandant, colonel, SSSM. Pour chaque session :
 * - dates d'inscription, de dépôt du dossier et des premières épreuves ;
 * - centres organisateurs, voies d'accès et lien vers le centre ;
 * - épreuves, conditions d'accès et missions (textes officiels).
 *
 * Chaque session devient une fiche « r18_concours » publiée (/concours/…).
 * Une fiche n'est jamais supprimée. Le texte rédigé au back-office (contenu
 * de la fiche) n'est jamais écrasé : seules les données officielles le sont.
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18\Concours;

use G2RD_Rescue18\Veille\Erreur_Acces;
use G2RD_Rescue18\Veille\Http;
use G2RD_Rescue18\Veille\Transport_WP;
use function G2RD_Rescue18\Veille\dom;
use function G2RD_Rescue18\Veille\plat;
use function G2RD_Rescue18\Veille\texte;

const SITE     = 'https://www.concours-territorial.fr/';
const HOTE     = 'www.concours-territorial.fr';
const FILIERE  = '8';
const HOOK     = 'r18_concours_sync';
const HOOK_LOT = 'r18_concours_lot';
const OPT_RUN  = 'r18_concours_run';
const OPT_BIL  = 'r18_concours_bilan';

/* -------------------------------------------------------------------------
 * Lecture (sans WordPress)
 * ---------------------------------------------------------------------- */

/**
 * « 05/01/2027 » vers « 2027-01-05 ».
 *
 * @param string $d Date française.
 * @return string
 */
function iso( string $d ): string {
	return \preg_match( '#(\d{2})/(\d{2})/(\d{4})#', $d, $m ) ? $m[3] . '-' . $m[2] . '-' . $m[1] : '';
}

/**
 * Dates d'une ligne de session.
 *
 * @param string $t Texte.
 * @return array{debut:string,fin:string,dossier:string,epreuves:string}
 */
function dates( string $t ): array {
	$d = array(
		'debut'    => '',
		'fin'      => '',
		'dossier'  => '',
		'epreuves' => '',
	);
	if ( \preg_match( '#du (\d{2}/\d{2}/\d{4}) au (\d{2}/\d{2}/\d{4})#u', $t, $m ) ) {
		$d['debut'] = iso( $m[1] );
		$d['fin']   = iso( $m[2] );
	} elseif ( \preg_match( '#jusqu.au (\d{2}/\d{2}/\d{4})#u', $t, $m ) ) {
		$d['fin'] = iso( $m[1] );
	}
	if ( \preg_match( '#(?:dossier|renvoyer|d[ée]p[ôo]t)[^0-9]{0,40}(\d{2}/\d{2}/\d{4})#iu', $t, $m ) ) {
		$d['dossier'] = iso( $m[1] );
	}
	if ( \preg_match( '#premi[èe]res? [ée]preuves?\s*:\s*le (\d{2}/\d{2}/\d{4})#iu', $t, $m ) ) {
		$d['epreuves'] = iso( $m[1] );
	}
	return $d;
}

/**
 * Catégorie du calendrier Rescue 18.
 *
 * @param string $titre Intitulé.
 * @param string $type  Concours|Examen professionnel.
 * @return string
 */
function categorie( string $titre, string $type ): string {
	if ( \preg_match( '/medecin|pharmacien|infirmier|sante|veterinaire/', plat( $titre ) ) ) {
		return 'sante';
	}
	return \str_contains( plat( $type ), 'examen' ) ? 'examen' : 'spp';
}

/**
 * Résultats de recherche (réponse JSON de AjaxCall.asmx/ResultatsSuivants).
 *
 * @param string $json Réponse.
 * @return array<int,array<string,mixed>>
 */
function resultats( string $json ): array {
	$d    = \json_decode( $json, true );
	$html = (string) ( $d['d'][0]['Libelle'] ?? '' );
	if ( '' === \trim( $html ) ) {
		return array();
	}
	$x   = dom( '<div id="r">' . $html . '</div>' );
	$out = array();
	foreach ( $x->query( "//div[contains(concat(' ',normalize-space(@class),' '),' gip-search-result ')]" ) as $c ) {
		$q   = static fn( string $cls ): string => texte( $x->query( ".//*[contains(concat(' ',normalize-space(@class),' '),' " . $cls . " ')]", $c )->item( 0 ) );
		$on  = '';
		$lien = $x->query( ".//a[contains(@onclick,'ouvre_specialites')]", $c )->item( 0 );
		if ( $lien instanceof \DOMElement && \preg_match( "/ouvre_specialites\('(\d+)'/", $lien->getAttribute( 'onclick' ), $m ) ) {
			$on = $m[1];
		}
		$titre = texte( $x->query( './/h3', $c )->item( 0 ) );
		if ( '' === $on || '' === $titre ) {
			continue;
		}
		$lignes = $q( 'gip-dates-inscription' ) . ' ' . $q( 'gip-dates-epreuves' );
		$type   = $q( 'gip-badge-type-concours' );
		$out[]  = array(
			'id'          => $on,
			'titre'       => $titre,
			'statut'      => $q( 'gip-badge-inscriptions' ),
			'type'        => $type,
			'categorie'   => \trim( (string) \preg_replace( '/^Cat[ée]gorie\s*/u', '', $q( 'gip-badge-categorie' ) ) ),
			'session'     => \preg_replace( '/\D/', '', $q( 'gip-badge-session' ) ),
			'dates'       => dates( $lignes ),
			'ligne'       => \trim( $lignes ),
			'centres'     => \preg_match( '/(\d+)\s+centres?/u', $q( 'gip-organisateurs' ), $m ) ? (int) $m[1] : 0,
			'specialites' => \trim( (string) \preg_replace( '/^Sp[ée]cialit[ée]s organis[ée]es\s*:\s*/u', '', $q( 'gip-specialites' ) ) ),
			'cat'         => categorie( $titre, $type ),
		);
	}
	return $out;
}

/**
 * Nombre de résultats renvoyés par un appel (champ Id).
 *
 * @param string $json Réponse.
 * @return int
 */
function compte( string $json ): int {
	$d = \json_decode( $json, true );
	return (int) ( $d['d'][0]['Id'] ?? 0 );
}

/**
 * Page d'une session : statut, dates, message et centres organisateurs.
 *
 * @param string $html Page session.aspx.
 * @return array<string,mixed>
 */
function session( string $html ): array {
	$x     = dom( $html );
	$tete  = $x->query( "//*[@id='Contenu_Entete']" )->item( 0 );
	$q     = static fn( string $cls, ?\DOMNode $ctx = null ): string => texte( $x->query( ".//*[contains(concat(' ',normalize-space(@class),' '),' " . $cls . " ')]", $ctx ?? $tete )->item( 0 ) );
	$lignes = $tete ? $q( 'gip-dates-inscription' ) . ' ' . $q( 'gip-dates-epreuves' ) : '';
	$info  = texte( $x->query( "//*[@id='Contenu_Contenu_InfoSessionListe']//*[contains(@class,'alert-info')]" )->item( 0 ) );
	if ( '' === $info ) {
		$info = texte( $x->query( "//*[@id='Contenu_Contenu_InfoSessionListe']/p" )->item( 0 ) );
	}
	$orgs = array();
	foreach ( $x->query( "//*[@id='Contenu_Contenu_ListeCentreOrganisateurs']/li" ) as $li ) {
		/** @var \DOMElement $li */
		$nom   = $q( 'gip-cdg-name', $li );
		$bloc  = $x->query( ".//*[contains(@class,'gip-cdg-name')]/following-sibling::div[1]", $li )->item( 0 );
		$tout  = texte( $bloc );
		$voies = \preg_match( '/Voies\s*:\s*(.+?)(?:Plus d.informations|$)/u', $tout, $m ) ? \trim( $m[1] ) : '';
		$note  = \trim( (string) \preg_replace( '/Voies\s*:.*$/su', '', $tout ) );
		$url   = '';
		foreach ( $x->query( './/a[@href]', $li ) as $a ) {
			/** @var \DOMElement $a */
			$h = $a->getAttribute( 'href' );
			if ( \preg_match( '#^https://#', $h ) ) {
				$url = $h;
				break;
			}
		}
		$orgs[] = array(
			'cdg'         => $li->getAttribute( 'cdg_id' ),
			'nom'         => $nom,
			'note'        => $note,
			'voies'       => $voies,
			'url'         => $url,
			'inscription' => $q( 'gip-cdg-inscription', $li ),
		);
	}
	return array(
		'statut' => $tete ? $q( 'gip-badge-inscriptions' ) : '',
		'dates'  => dates( $lignes ),
		'ligne'  => \trim( $lignes ),
		'info'   => $info,
		'orgs'   => $orgs,
	);
}

/**
 * Onglet officiel nettoyé (épreuves, conditions, missions) : HTML simple.
 *
 * @param string $html Page.
 * @param string $id   Identifiant du bloc (DetailEpreuves, DetailConditions, DetailMissions).
 * @return string
 */
function onglet( string $html, string $id ): string {
	$x    = dom( $html );
	$bloc = $x->query( "//*[@id='Contenu_Contenu_" . $id . "']" )->item( 0 );
	if ( ! $bloc instanceof \DOMElement ) {
		return '';
	}
	// Sommaire interne et icônes : retirés.
	foreach ( \iterator_to_array( $x->query( ".//p[a[starts-with(@href,'#Ep')]] | .//span[contains(@class,'icon')]", $bloc ) ) as $n ) {
		$n->parentNode?->removeChild( $n );
	}
	foreach ( \iterator_to_array( $x->query( './/a', $bloc ) ) as $a ) {
		/** @var \DOMElement $a */
		$h = $a->getAttribute( 'href' );
		if ( \preg_match( '#^https?://#', $h ) ) {
			continue;
		}
		if ( \str_contains( $a->textContent, 'Note de cadrage' ) ) {
			$a->parentNode?->removeChild( $a );
		} else {
			$a->parentNode?->replaceChild( $x->document->createTextNode( $a->textContent ), $a );
		}
	}
	foreach ( \iterator_to_array( $x->query( './/*', $bloc ) ) as $e ) {
		/** @var \DOMElement $e */
		foreach ( \iterator_to_array( $e->attributes ) as $at ) {
			if ( 'href' !== $at->nodeName ) {
				$e->removeAttribute( $at->nodeName );
			}
		}
	}
	$h = '';
	foreach ( $bloc->childNodes as $c ) {
		$h .= $x->document->saveHTML( $c );
	}
	$h = (string) \preg_replace( '/\s*-\s*(<\/div>)/u', '$1', $h );
	$h = (string) \preg_replace( '/<div>\s*<\/div>|<br\s*\/?>\s*(?=<(?:ul|h3|h4))/u', '', $h );
	$h = (string) \preg_replace( '/\s+/u', ' ', $h );
	return \trim( $h );
}

/* -------------------------------------------------------------------------
 * Collecte (WordPress)
 * ---------------------------------------------------------------------- */

/**
 * Appel POST de la recherche publique (adresse fixe, aucune donnée transmise).
 *
 * @param string $position Position.
 * @return string
 */
function rechercher( string $position ): string {
	$r = \wp_safe_remote_post(
		SITE . 'AjaxCall.asmx/ResultatsSuivants',
		array(
			'timeout'             => 20,
			'redirection'         => 0,
			'limit_response_size' => 1048576,
			'user-agent'          => \G2RD_Rescue18\Veille\agent(),
			'headers'             => array(
				'Content-Type' => 'application/json; charset=utf-8',
				'Accept'       => 'application/json',
			),
			'body'                => (string) \wp_json_encode(
				array(
					'examen'    => '',
					'diplome'   => '',
					'categorie' => '',
					'filiere'   => FILIERE,
					'grade'     => '',
					'position'  => $position,
				)
			),
		)
	);
	if ( \is_wp_error( $r ) ) {
		throw new Erreur_Acces( 'reseau', 'Concours-Territorial injoignable : ' . $r->get_error_message() );
	}
	$code = (int) \wp_remote_retrieve_response_code( $r );
	if ( 200 !== $code ) {
		throw new Erreur_Acces( 'http', 'Recherche Concours-Territorial : HTTP ' . $code . '.' );
	}
	return (string) \wp_remote_retrieve_body( $r );
}

/**
 * Toutes les sessions de la filière, sans doublon.
 *
 * @return array<string,array<string,mixed>>
 */
function sessions(): array {
	$out = array();
	$pos = '-1';
	for ( $i = 0; $i < 12; $i++ ) {
		$j = rechercher( $pos );
		$n = compte( $j );
		foreach ( resultats( $j ) as $s ) {
			$out[ $s['id'] ] = $s;
		}
		if ( $n < 1 ) {
			break;
		}
		$pos = '-1' === $pos ? '0' : (string) ( (int) $pos + $n );
		\usleep( 800000 );
	}
	return $out;
}

/**
 * Fiche existante d'une session.
 *
 * @param string $id Identifiant Concours-Territorial.
 * @return int
 */
function fiche( string $id ): int {
	$p = \get_posts(
		array(
			'post_type'      => 'r18_concours',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => 'r18_ct_id', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => $id, // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	return (int) ( $p[0] ?? 0 );
}

/**
 * Lance la collecte : liste des sessions, puis lecture par lots.
 *
 * @param string $origine cron|manuel.
 */
function demarrer( string $origine = 'cron' ): void {
	$run = (array) \get_option( OPT_RUN, array() );
	if ( ! empty( $run['file'] ) && \time() - (int) ( $run['maj'] ?? 0 ) < 15 * MINUTE_IN_SECONDS ) {
		return;
	}
	try {
		$liste = sessions();
	} catch ( Erreur_Acces $e ) {
		\update_option(
			OPT_BIL,
			array(
				'fin'    => \time(),
				'erreur' => $e->getMessage(),
			),
			false
		);
		return;
	}
	\update_option(
		OPT_RUN,
		array(
			'file'    => \array_values( $liste ),
			'pos'     => 0,
			'debut'   => \time(),
			'maj'     => \time(),
			'origine' => $origine,
			'stats'   => array(
				'nouvelles' => 0,
				'maj'       => 0,
				'erreurs'   => array(),
			),
		),
		false
	);
	lot();
}

/**
 * Lit les pages des sessions pendant 30 secondes, puis replanifie la suite.
 */
function lot(): void {
	$run = (array) \get_option( OPT_RUN, array() );
	if ( empty( $run['file'] ) || \get_transient( 'r18_concours_verrou' ) ) {
		return;
	}
	\set_transient( 'r18_concours_verrou', 1, 5 * MINUTE_IN_SECONDS );
	$http        = new Http( new Transport_WP(), array( HOTE ), 1.0, 200 );
	$http->delai = 20;
	$t0          = \microtime( true );
	while ( $run['pos'] < \count( $run['file'] ) && \microtime( true ) - $t0 < 30 ) {
		$s = $run['file'][ $run['pos'] ];
		++$run['pos'];
		try {
			$id  = fiche( (string) $s['id'] );
			$ses = session( $http->get( SITE . 'session.aspx?id=' . \rawurlencode( (string) $s['id'] ) )->corps );
			$d   = $s['dates'];
			foreach ( (array) $ses['dates'] as $k => $v ) {
				if ( '' !== $v ) {
					$d[ $k ] = $v;
				}
			}
			$meta = array(
				'r18_ct_id'           => (string) $s['id'],
				'r18_cat'             => (string) $s['cat'],
				'r18_type'            => (string) $s['type'],
				'r18_categorie_fp'    => (string) $s['categorie'],
				'r18_session'         => (string) $s['session'],
				'r18_statut_ct'       => '' !== $ses['statut'] ? (string) $ses['statut'] : (string) $s['statut'],
				'r18_info_ct'         => (string) $ses['info'],
				'r18_insc_debut'      => $d['debut'],
				'r18_insc_fin'        => $d['fin'],
				'r18_dossier'         => $d['dossier'],
				'r18_epreuves'        => $d['epreuves'],
				'r18_specialites'     => (string) $s['specialites'],
				'r18_organisateur'    => \count( $ses['orgs'] ) ? \count( $ses['orgs'] ) . ' centre' . ( \count( $ses['orgs'] ) > 1 ? 's' : '' ) . ' organisateur' . ( \count( $ses['orgs'] ) > 1 ? 's' : '' ) : 'Centres organisateurs à paraître',
				'r18_organisateurs'   => (string) \wp_json_encode( $ses['orgs'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
				'r18_url'             => SITE . 'session.aspx?id=' . (string) $s['id'],
				'r18_origine'         => 'officiel',
				'r18_ct_maj'          => (string) \time(),
			);
			// Textes officiels : à la création, puis une fois par semaine.
			if ( 0 === $id || \time() - (int) \get_post_meta( $id, 'r18_ct_textes', true ) > 7 * DAY_IN_SECONDS ) {
				foreach ( array( 'epreuves' => 'DetailEpreuves', 'conditions' => 'DetailConditions', 'missions' => 'DetailMissions' ) as $page => $bloc ) {
					$h = onglet( $http->get( SITE . $page . '.aspx?id=' . \rawurlencode( (string) $s['id'] ) )->corps, $bloc );
					if ( '' !== $h ) {
						$meta[ 'r18_' . $page . '_html' ] = \wp_kses_post( $h );
					}
				}
				$meta['r18_ct_textes'] = (string) \time();
			}
			if ( 0 === $id ) {
				$id = (int) \wp_insert_post(
					\wp_slash(
						array(
							'post_type'   => 'r18_concours',
							'post_status' => 'publish',
							'post_title'  => (string) $s['titre'],
							'post_name'   => \sanitize_title( ( 'examen' === $s['cat'] ? 'examen-' : 'concours-' ) . $s['titre'] . '-' . $s['session'] ),
						)
					)
				);
				++$run['stats']['nouvelles'];
			} else {
				++$run['stats']['maj'];
			}
			if ( $id > 0 ) {
				foreach ( $meta as $k => $v ) {
					\update_post_meta( $id, $k, \wp_slash( $v ) );
				}
			}
		} catch ( Erreur_Acces $e ) {
			$run['stats']['erreurs'][] = $s['titre'] . ' ' . $s['session'] . ' : ' . $e->getMessage();
		}
	}
	$run['maj'] = \time();
	if ( $run['pos'] < \count( $run['file'] ) ) {
		\update_option( OPT_RUN, $run, false );
		\wp_schedule_single_event( \time() + 20, HOOK_LOT );
	} else {
		\delete_option( OPT_RUN );
		\update_option(
			OPT_BIL,
			array(
				'fin'       => \time(),
				'sessions'  => \count( $run['file'] ),
				'nouvelles' => $run['stats']['nouvelles'],
				'maj'       => $run['stats']['maj'],
				'erreurs'   => \array_slice( $run['stats']['erreurs'], 0, 20 ),
			),
			false
		);
		\wp_cache_delete( 'r18_concours_liste', 'r18' );
	}
	\delete_transient( 'r18_concours_verrou' );
}

\add_action(
	HOOK,
	static function (): void {
		if ( ! \G2RD_Rescue18\reporter_si_occupe( HOOK ) ) {
			demarrer( 'cron' );
		}
	}
);
\add_action( HOOK_LOT, __NAMESPACE__ . '\lot' );
\add_action(
	'switch_theme',
	static function (): void {
		\wp_clear_scheduled_hook( HOOK );
		\wp_clear_scheduled_hook( HOOK_LOT );
	}
);

/* Bouton « Mettre à jour » et bilan sur la liste des concours. */
\add_action(
	'admin_notices',
	static function (): void {
		$ecran = \get_current_screen();
		if ( ! $ecran || 'edit-r18_concours' !== $ecran->id || ! \current_user_can( 'edit_pages' ) ) {
			return;
		}
		$b   = (array) \get_option( OPT_BIL, array() );
		$run = (array) \get_option( OPT_RUN, array() );
		echo '<div class="notice notice-info"><p><strong>Source officielle : Concours-Territorial</strong> (portail des centres de gestion). Mise à jour automatique chaque matin. ';
		if ( ! empty( $run['file'] ) ) {
			echo 'Mise à jour en cours : ' . (int) $run['pos'] . ' sur ' . \count( $run['file'] ) . '. ';
		} elseif ( ! empty( $b['erreur'] ) ) {
			echo 'Dernier essai le ' . \esc_html( \wp_date( 'j/m à G \h i', (int) $b['fin'] ) ) . ' : ' . \esc_html( (string) $b['erreur'] ) . ' ';
		} elseif ( ! empty( $b['fin'] ) ) {
			echo 'Dernière mise à jour le ' . \esc_html( \wp_date( 'j/m à G \h i', (int) $b['fin'] ) ) . ' : ' . (int) $b['sessions'] . ' sessions, ' . (int) $b['nouvelles'] . ' nouvelle(s). ';
		}
		echo '</p><form method="post" action="' . \esc_url( \admin_url( 'admin-post.php' ) ) . '" style="margin:0 0 .6em">';
		\wp_nonce_field( 'r18_concours_sync' );
		echo '<input type="hidden" name="action" value="r18_concours_sync"><button class="button">Mettre à jour maintenant</button></form>';
		if ( ! empty( $b['erreurs'] ) ) {
			echo '<details><summary>' . \count( (array) $b['erreurs'] ) . ' page(s) illisible(s)</summary><ul>';
			foreach ( (array) $b['erreurs'] as $e ) {
				echo '<li>' . \esc_html( (string) $e ) . '</li>';
			}
			echo '</ul></details>';
		}
		echo '</div>';
	}
);
\add_action(
	'admin_post_r18_concours_sync',
	static function (): void {
		if ( ! \current_user_can( 'edit_pages' ) ) {
			\wp_die( 'Accès refusé.', 403 );
		}
		\check_admin_referer( 'r18_concours_sync' );
		\wp_schedule_single_event( \time(), HOOK );
		\spawn_cron();
		\wp_safe_redirect( \admin_url( 'edit.php?post_type=r18_concours' ) );
		exit;
	}
);

/* -------------------------------------------------------------------------
 * Affichage
 * ---------------------------------------------------------------------- */

/**
 * Date longue : « 5 janvier 2027 ».
 *
 * @param string $d Date ISO.
 * @return string
 */
function longue( string $d ): string {
	return '' !== $d ? (string) \wp_date( 'j F Y', (int) \strtotime( $d . ' 12:00' ) ) : '';
}

/**
 * Jours restants avant une date (négatif si passée).
 *
 * @param string $d Date ISO.
 * @return int
 */
function jours( string $d ): int {
	return (int) \floor( ( \strtotime( $d . ' 00:00' ) - \strtotime( \wp_date( 'Y-m-d' ) . ' 00:00' ) ) / DAY_IN_SECONDS );
}

/**
 * Étape en cours d'une session.
 *
 * @param array<string,string> $m Métas.
 * @return array{0:string,1:string} clé, libellé.
 */
function etape( array $m ): array {
	$t = \wp_date( 'Y-m-d' );
	if ( '' !== $m['r18_insc_debut'] && $m['r18_insc_debut'] > $t ) {
		return array( 'bientot', 'Inscriptions à venir' );
	}
	if ( '' !== $m['r18_insc_fin'] && $m['r18_insc_fin'] >= $t ) {
		return array( 'ouvert', 'Inscriptions ouvertes' );
	}
	if ( '' !== $m['r18_dossier'] && $m['r18_dossier'] >= $t ) {
		return array( 'dossier', 'Dépôt des dossiers' );
	}
	if ( '' !== $m['r18_epreuves'] && $m['r18_epreuves'] >= $t ) {
		return array( 'epreuves', 'Épreuves à venir' );
	}
	return array( 'passe', 'Session en cours ou terminée' );
}

/**
 * Métas d'une fiche.
 *
 * @param int $id Fiche.
 * @return array<string,string>
 */
function metas( int $id ): array {
	$out = array();
	foreach ( array( 'r18_ct_id', 'r18_cat', 'r18_type', 'r18_categorie_fp', 'r18_session', 'r18_statut_ct', 'r18_info_ct', 'r18_insc_debut', 'r18_insc_fin', 'r18_dossier', 'r18_epreuves', 'r18_specialites', 'r18_organisateur', 'r18_organisateurs', 'r18_url', 'r18_epreuves_html', 'r18_conditions_html', 'r18_missions_html', 'r18_ct_maj', 'r18_origine' ) as $k ) {
		$out[ $k ] = (string) \get_post_meta( $id, $k, true );
	}
	return $out;
}

/**
 * Bloc rescue18/concours-fiche : partie « tete » (en-tête, frise des dates)
 * ou « corps » (organisateurs, épreuves, conditions, missions, sources).
 *
 * @param array<string,mixed> $attrs Attributs.
 * @return string
 */
function render_fiche( array $attrs ): string {
	\wp_enqueue_style( 'rescue18-annuaire' );
	$id = (int) \get_the_ID();
	if ( 'r18_concours' !== \get_post_type( $id ) ) {
		return '';
	}
	$m = metas( $id );
	return 'corps' === ( $attrs['partie'] ?? 'tete' ) ? corps( $id, $m ) : tete( $id, $m );
}

/**
 * En-tête de fiche.
 *
 * @param int                  $id Fiche.
 * @param array<string,string> $m  Métas.
 * @return string
 */
function tete( int $id, array $m ): string {
	[ $cle, $lib ] = etape( $m );
	$type  = '' !== $m['r18_type'] ? $m['r18_type'] : 'Concours';
	$pills = '<span class="r18-pill r18-ccf__st is-' . \esc_attr( $cle ) . '">' . \esc_html( $lib ) . '</span>';
	foreach ( array( $type, '' !== $m['r18_categorie_fp'] ? 'Catégorie ' . $m['r18_categorie_fp'] : '', '' !== $m['r18_session'] ? 'Session ' . $m['r18_session'] : '' ) as $p ) {
		$pills .= '' !== $p ? '<span class="r18-pill r18-pill--grey">' . \esc_html( $p ) . '</span>' : '';
	}
	$etapes = array(
		array( 'Ouverture des inscriptions', $m['r18_insc_debut'] ),
		array( 'Clôture des inscriptions', $m['r18_insc_fin'] ),
		array( 'Dépôt du dossier', $m['r18_dossier'] ),
		array( 'Premières épreuves', $m['r18_epreuves'] ),
	);
	$frise = '';
	$t     = \wp_date( 'Y-m-d' );
	foreach ( $etapes as [ $l, $d ] ) {
		if ( '' === $d ) {
			continue;
		}
		$j      = jours( $d );
		$quand  = $j > 1 ? 'dans ' . $j . ' jours' : ( 1 === $j ? 'demain' : ( 0 === $j ? 'aujourd’hui' : 'passé' ) );
		$frise .= '<li class="r18-ccf__step' . ( $d < $t ? ' is-done' : '' ) . '"><span class="r18-ccf__dot" aria-hidden="true"></span><strong>' . \esc_html( longue( $d ) ) . '</strong><span>' . \esc_html( $l ) . '</span><em>' . \esc_html( $quand ) . '</em></li>';
	}
	$prochaine = '';
	foreach ( $etapes as [ $l, $d ] ) {
		if ( '' !== $d && $d >= $t ) {
			$prochaine = '<p class="r18-ccf__next"><span class="r18-ccf__count">' . ( 0 === jours( $d ) ? 'J' : 'J-' . jours( $d ) ) . '</span> ' . \esc_html( $l ) . ' le ' . \esc_html( longue( $d ) ) . '</p>';
			break;
		}
	}
	$h  = '<div class="r18-ccf__tags">' . $pills . '</div>';
	$h .= $prochaine;
	$h .= $frise ? '<ol class="r18-ccf__frise" aria-label="Calendrier de la session">' . $frise . '</ol>' : '<p class="r18-ccf__vide">Les dates de cette session ne sont pas encore publiées.</p>';
	$h .= '' !== $m['r18_info_ct'] ? '<p class="r18-ccf__info">' . \esc_html( $m['r18_info_ct'] ) . '</p>' : '';
	if ( '' !== $m['r18_url'] ) {
		$h .= '<p class="r18-ccf__cta"><a class="wp-block-button__link wp-element-button" href="' . \esc_url( $m['r18_url'] ) . '" rel="noopener">S’inscrire sur Concours-Territorial</a></p>';
	}
	return '<div class="r18-ccf r18-ccf--tete">' . $h . '</div>';
}

/**
 * Corps de fiche : organisateurs et textes officiels.
 *
 * @param int                  $id Fiche.
 * @param array<string,string> $m  Métas.
 * @return string
 */
function corps( int $id, array $m ): string {
	$secs = array();
	$orgs = \json_decode( $m['r18_organisateurs'], true );
	$orgs = \is_array( $orgs ) ? $orgs : array();
	$oh   = '';
	foreach ( $orgs as $o ) {
		$oh .= '<li class="r18-ccf__org"><strong>' . \esc_html( \mb_convert_case( \mb_strtolower( (string) $o['nom'] ), MB_CASE_TITLE ) ) . '</strong>'
			. ( '' !== $o['note'] ? '<span>' . \esc_html( (string) $o['note'] ) . '</span>' : '' )
			. ( '' !== $o['voies'] ? '<span class="r18-ccf__voies">Voies : ' . \esc_html( \mb_strtolower( (string) $o['voies'] ) ) . '</span>' : '' )
			. ( '' !== $o['url'] ? '<a href="' . \esc_url( (string) $o['url'] ) . '" rel="noopener">Site du centre</a>' : '' ) . '</li>';
	}
	$secs['organisateurs'] = array(
		'Centres organisateurs',
		$oh ? '<ul class="r18-ccf__orgs" role="list">' . $oh . '</ul>' : '<p>Les centres de gestion qui organisent cette session ne sont pas encore publiés. La liste apparaît ici dès sa parution.</p>',
	);
	if ( '' !== $m['r18_epreuves_html'] ) {
		$secs['epreuves'] = array( 'Les épreuves', $m['r18_epreuves_html'] );
	}
	if ( '' !== $m['r18_conditions_html'] ) {
		$secs['conditions'] = array( 'Conditions d’accès', $m['r18_conditions_html'] );
	}
	if ( '' !== $m['r18_missions_html'] ) {
		$secs['missions'] = array( 'Missions du grade', $m['r18_missions_html'] );
	}
	$nav = '';
	$h   = '';
	foreach ( $secs as $k => [ $titre, $html ] ) {
		$nav .= '<li><a href="#cc-' . \esc_attr( $k ) . '">' . \esc_html( $titre ) . '</a></li>';
		$h   .= '<section class="r18-ccf__sec r18-ccf__sec--' . \esc_attr( $k ) . '" id="cc-' . \esc_attr( $k ) . '" aria-labelledby="cc-' . \esc_attr( $k ) . '-t"><h2 class="r18-h2" id="cc-' . \esc_attr( $k ) . '-t">' . \esc_html( $titre ) . '</h2><div class="r18-ccf__txt">' . \wp_kses_post( $html ) . '</div></section>';
	}
	$src = '' !== $m['r18_url'] ? '<p class="r18-src-note"><span>Source officielle : <a href="' . \esc_url( $m['r18_url'] ) . '" rel="noopener">Concours-Territorial</a>, portail des centres de gestion de la fonction publique territoriale' . ( '' !== $m['r18_ct_maj'] ? ', relevé le ' . \esc_html( \wp_date( 'j F Y', (int) $m['r18_ct_maj'] ) ) : '' ) . '. Seul l’arrêté d’ouverture du centre organisateur fait foi.</span></p>' : '';
	return '<div class="r18-ccf r18-ccf--corps"><nav class="r18-ccf__toc" aria-label="Sur cette page"><ul>' . $nav . '</ul></nav><div class="r18-ccf__main">' . $h . $src . '</div></div>';
}

/**
 * Bloc rescue18/concours-chiffres : sessions suivies, inscriptions ouvertes, prochaine ouverture.
 *
 * @return string
 */
function render_chiffres(): string {
	\wp_enqueue_style( 'rescue18-annuaire' );
	$t     = \wp_date( 'Y-m-d' );
	$suivi = 0;
	$ouv   = 0;
	$proch = '';
	$nom   = '';
	foreach ( \get_posts(
		array(
			'post_type'      => 'r18_concours',
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'fields'         => 'ids',
			'meta_key'       => 'r18_origine', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => 'officiel', // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	) as $id ) {
		$m = metas( (int) $id );
		if ( 'passe' === etape( $m )[0] ) {
			continue;
		}
		++$suivi;
		if ( '' !== $m['r18_insc_fin'] && $m['r18_insc_fin'] >= $t && ( '' === $m['r18_insc_debut'] || $m['r18_insc_debut'] <= $t ) ) {
			++$ouv;
		}
		if ( '' !== $m['r18_insc_debut'] && $m['r18_insc_debut'] > $t && ( '' === $proch || $m['r18_insc_debut'] < $proch ) ) {
			$proch = $m['r18_insc_debut'];
			$nom   = \get_the_title( (int) $id );
		}
	}
	$c = static fn( string $n, string $l ): string => '<li><strong>' . $n . '</strong><span>' . \esc_html( $l ) . '</span></li>';
	return '<ul class="r18-ccs-chiffres" role="list">'
		. $c( (string) $suivi, 'sessions à venir suivies' )
		. $c( (string) $ouv, $ouv > 1 ? 'inscriptions ouvertes' : 'inscription ouverte' )
		. $c( '' !== $proch ? \esc_html( \wp_date( 'j M', (int) \strtotime( $proch . ' 12:00' ) ) ) : '–', '' !== $nom ? 'prochaine ouverture : ' . \mb_strtolower( $nom ) : 'prochaine ouverture' )
		. $c( '1', 'mise à jour chaque matin' )
		. '</ul>';
}

/**
 * Bloc rescue18/concours-grades : un repère par grade, avec sa dernière session.
 *
 * @return string
 */
function render_grades(): string {
	\wp_enqueue_style( 'rescue18-annuaire' );
	$g = array();
	foreach ( \get_posts(
		array(
			'post_type'      => 'r18_concours',
			'post_status'    => 'publish',
			'posts_per_page' => 300,
			'fields'         => 'ids',
			'meta_key'       => 'r18_origine', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => 'officiel', // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	) as $id ) {
		$m   = metas( (int) $id );
		$cle = plat( \get_the_title( (int) $id ) ) . '|' . $m['r18_type'];
		$a   = 'passe' === etape( $m )[0] ? 0 : 1;
		$rang = $a . $m['r18_session'];
		if ( ! isset( $g[ $cle ] ) || $rang > $g[ $cle ][0] ) {
			$g[ $cle ] = array( $rang, (int) $id, $m );
		}
	}
	if ( ! $g ) {
		return '<p class="r18-empty">Les fiches des concours arrivent avec la prochaine mise à jour.</p>';
	}
	$ordre = array( 'C' => 1, 'B' => 2, 'A' => 3, 'A+' => 4 );
	\uasort( $g, static fn( array $x, array $y ): int => ( $ordre[ $x[2]['r18_categorie_fp'] ] ?? 9 ) <=> ( $ordre[ $y[2]['r18_categorie_fp'] ] ?? 9 ) ?: \strcmp( (string) \get_the_title( $x[1] ), (string) \get_the_title( $y[1] ) ) );
	$h = '';
	foreach ( $g as [ , $id, $m ] ) {
		[ $cle, $lib ] = etape( $m );
		$quand = '';
		foreach ( array( 'r18_insc_debut' => 'Ouverture le ', 'r18_insc_fin' => 'Clôture le ', 'r18_dossier' => 'Dossier avant le ', 'r18_epreuves' => 'Épreuves le ' ) as $k => $l ) {
			if ( '' !== $m[ $k ] && $m[ $k ] >= \wp_date( 'Y-m-d' ) ) {
				$quand = $l . longue( $m[ $k ] );
				break;
			}
		}
		$h .= '<li class="r18-ccg__card is-' . \esc_attr( $cle ) . '"><p class="r18-ccg__cat">' . \esc_html( \trim( ( '' !== $m['r18_categorie_fp'] ? 'Catégorie ' . $m['r18_categorie_fp'] : '' ) . ' · ' . ( '' !== $m['r18_type'] ? $m['r18_type'] : 'Concours' ), ' ·' ) ) . '</p>'
			. '<h3><a href="' . \esc_url( (string) \get_permalink( $id ) ) . '">' . \esc_html( \get_the_title( $id ) ) . '</a></h3>'
			. '<p class="r18-ccg__st"><span class="r18-pill r18-ccf__st is-' . \esc_attr( $cle ) . '">' . \esc_html( $lib ) . '</span> <span>Session ' . \esc_html( $m['r18_session'] ) . '</span></p>'
			. ( '' !== $quand ? '<p class="r18-ccg__q">' . \esc_html( $quand ) . '</p>' : '' ) . '</li>';
	}
	return '<ul class="r18-ccg" role="list">' . $h . '</ul>';
}

/**
 * Définitions des blocs.
 *
 * @return array<string,array<string,mixed>>
 */
function block_definitions(): array {
	return array(
		'concours-fiche'    => array(
			'title'  => 'Fiche concours (données officielles)',
			'icon'   => 'awards',
			'attrs'  => array(
				'partie' => array(
					'string',
					'tete',
					'Partie',
					array(
						'tete'  => 'En-tête et calendrier',
						'corps' => 'Organisateurs et textes officiels',
					),
				),
			),
			'render' => 'Concours\render_fiche',
		),
		'concours-grades'   => array(
			'title'  => 'Concours par grade',
			'icon'   => 'groups',
			'attrs'  => array(),
			'render' => 'Concours\\render_grades',
		),
		'concours-chiffres' => array(
			'title'  => 'Concours en chiffres',
			'icon'   => 'chart-bar',
			'attrs'  => array(),
			'render' => 'Concours\render_chiffres',
		),
	);
}

/**
 * Contenu par défaut d'une fiche sans texte rédigé : préparation et suite du concours.
 *
 * @param int $id Fiche.
 * @return string
 */
function contenu_defaut( int $id ): string {
	$m      = metas( $id );
	$sport  = \str_contains( plat( $m['r18_epreuves_html'] ), 'natation' ) || \str_contains( plat( $m['r18_epreuves_html'] ), 'sportive' );
	$etapes = array(
		array( 'Vérifie ta voie d’accès', 'Diplôme, années de volontariat, ancienneté : les conditions changent selon la voie. Relis la rubrique <a href="#cc-conditions">Conditions d’accès</a> avant de t’inscrire.' ),
		array( 'Inscris-toi dans les délais', 'La préinscription se fait en ligne sur Concours-Territorial, auprès d’un seul centre organisateur. Le dossier complet part avant la date limite de dépôt : un dossier en retard est refusé.' ),
		array( 'Révise les écrits', 'Entraîne-toi avec les <a href="/formation/quiz/">quiz Rescue 18</a> et la <a href="/documentations-referentiels/">bibliothèque documentaire</a> : GDO, GTO et référentiels.' ),
	);
	if ( $sport ) {
		$etapes[] = array( 'Prépare les épreuves physiques', 'Natation, parcours professionnel adapté, endurance : planifie ta préparation plusieurs mois à l’avance.' );
	}
	$etapes[] = array( 'Prépare l’entretien', 'Connais ton futur métier et l’organisation des secours. La fiche de <a href="/sdis/">chaque SDIS</a> et les <a href="/actualites-des-sdis/">actualités des SDIS</a> t’aident à parler du terrain.' );
	$apres = array(
		array( 'Liste d’aptitude', 'Le lauréat est inscrit sur une liste d’aptitude valable deux ans, puis prolongée d’un an à deux reprises sur sa demande écrite : quatre ans au plus.' ),
		array( 'Candidature auprès des SDIS', 'Réussir le concours ne donne pas de poste. C’est au lauréat de candidater : suis les <a href="/recrutement/offres-emploi/">offres d’emploi des SDIS</a>.' ),
		array( 'Nomination stagiaire', 'Le SDIS qui recrute nomme le lauréat stagiaire. Il quitte alors la liste d’aptitude.' ),
		array( 'Formation et titularisation', 'Formation d’intégration, puis titularisation à la fin du stage si la manière de servir donne satisfaction.' ),
	);
	$li = static function ( array $l ): string {
		$h = '';
		foreach ( $l as $i => [ $t, $d ] ) {
			$h .= '<li><span class="r18-ccf-step__n" aria-hidden="true">' . ( $i + 1 ) . '</span><div><h3>' . \esc_html( $t ) . '</h3><p>' . \wp_kses( $d, array( 'a' => array( 'href' => true ) ) ) . '</p></div></li>';
		}
		return $h;
	};
	return '<div class="entry-content wp-block-post-content r18-ccf-edito">'
		. '<p class="r18-eyebrow">Se préparer</p><h2 class="r18-h2">' . ( 5 === \count( $etapes ) ? 'Cinq' : 'Quatre' ) . ' étapes pour réussir</h2><ol class="r18-ccf-steps" role="list">' . $li( $etapes ) . '</ol>'
		. '<p class="r18-eyebrow">Après la réussite</p><h2 class="r18-h2">Du concours au premier poste</h2><ol class="r18-ccf-steps r18-ccf-steps--apres" role="list">' . $li( $apres ) . '</ol>'
		. '<p class="r18-src-note"><span>Liste d’aptitude : règles de la fonction publique territoriale, rappelées par le <a href="https://www.maisondescommunes85.fr/actualites/quelle-est-duree-validite-concours-d-acces-a-fpt" rel="noopener">centre de gestion de la Vendée</a>.</span></p></div>';
}

\add_filter(
	'render_block_core/post-content',
	static function ( string $html, array $bloc, \WP_Block $instance ): string {
		$id = (int) ( $instance->context['postId'] ?? 0 );
		if ( 'r18_concours' !== \get_post_type( $id ) || '' !== \trim( (string) \get_post_field( 'post_content', $id ) ) ) {
			return $html;
		}
		return contenu_defaut( $id );
	},
	10,
	3
);
