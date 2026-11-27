<?php
/**
 * Veille juridique automatique : Journal officiel (JORF), données ouvertes DILA.
 *
 * Source vérifiée le 31 octobre 2026 : https://echanges.dila.gouv.fr/OPENDATA/JORF/
 * (licence ouverte, aucun compte). Deux archives tar.gz par jour de parution,
 * environ 15 mois d'historique en ligne. Chaque texte publié y figure en XML
 * (texte/version/…/JORFTEXT….xml) : nature, numéro, NOR, dates, titre complet.
 *
 * Le connecteur lit les archives les plus récentes d'abord, garde les textes
 * dont l'intitulé touche les sapeurs-pompiers et la sécurité civile, et crée
 * une fiche « Juridique » par texte (lien Légifrance). Une archive traitée
 * n'est jamais relue. L'archive est lue en flux : seuls les fichiers
 * texte/version sont décompressés.
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18\Juridique;

use G2RD_Rescue18\Veille\Connecteur;
use G2RD_Rescue18\Veille\Contexte;
use G2RD_Rescue18\Veille\Erreur_Acces;
use G2RD_Rescue18\Veille\Http;
use function G2RD_Rescue18\Annuaire\etat_get;
use function G2RD_Rescue18\Annuaire\etat_set;
use function G2RD_Rescue18\Veille\plat;

const INDEX_JORF = 'https://echanges.dila.gouv.fr/OPENDATA/JORF/';

/**
 * Mots qui suffisent : le texte concerne directement les pompiers ou les
 * services d'incendie et de secours.
 *
 * @return string[]
 */
function mots_forts(): array {
	return array(
		'sapeurs-pompiers', 'sapeur-pompier', 'marins-pompiers', 'marin-pompier', 'incendie et de secours',
		'jeunes sapeurs', 'brigade de sapeurs', 'bataillon de marins', 'secours a personne', 'feux de foret', 'incendies de foret',
	);
}

/**
 * Mots retenus par défaut en plus des mots forts (secours, sauvetage,
 * prévention incendie), sauf si une exclusion s'applique.
 *
 * @return string[]
 */
function mots_defaut(): array {
	return array(
		'securite civile', 'premiers secours', 'secourisme', 'sauvetage', 'secours en montagne', 'secours en mer',
		'risques d\'incendie', 'risque d\'incendie', 'securite contre l\'incendie', 'securite incendie', 'immeubles de grande hauteur',
		'detecteurs de fumee', 'defibrillateur', 'defense exterieure contre l\'incendie', 'traitement de l\'alerte', 'appel d\'urgence',
		'aide medicale urgente', 'ensosp',
	);
}

/**
 * Exclusions par défaut : actes individuels et administratifs sans intérêt
 * pour le terrain (toujours écartés, même avec un mot fort).
 *
 * @return string[]
 */
function exclus_defaut(): array {
	return array(
		'portant nomination', 'nomination', 'tableau d\'avancement', 'liste d\'aptitude', 'medaille', 'citation a l\'ordre', 'promotion',
		'portant attribution', 'admission a la retraite', 'cessation de fonctions', 'delegation de signature', 'portant designation',
		'portant habilitation', 'bureau de verification', 'agrement national', 'agrement interdepartemental', 'agrement departemental',
		'portant derogation', 'portant derogations', 'derogation au profit', 'accessibilite', 'personnels enseignants', 'education nationale',
		'temps de conduite', 'interdiction de circulation', 'redevance', 'tenues d\'uniformes', 'genie parasismique',
	);
}

/**
 * Exclusions levées par un mot fort (primes et indemnités des pompiers : gardées ;
 * celles d'autres personnels : écartées).
 *
 * @return string[]
 */
function exclus_faibles(): array {
	return array( 'prime', 'indemnite', 'demineur', 'deminage', 'reconstruction', 'responsabilite militaire', 'organisation interne' );
}

/**
 * Natures retenues : NATURE XML => slug du site.
 *
 * @return array<string,string>
 */
function natures(): array {
	return array(
		'LOI'         => 'loi',
		'ORDONNANCE'  => 'ordonnance',
		'DECRET'      => 'decret',
		'ARRETE'      => 'arrete',
		'CIRCULAIRE'  => 'circulaire',
		'INSTRUCTION' => 'instruction',
	);
}

/**
 * Archives de l'index, de la plus récente à la plus ancienne.
 *
 * @param string $html Page d'index Apache.
 * @return array<int,array{nom:string,date:string}>
 */
function archives( string $html ): array {
	if ( ! \preg_match_all( '/href="(JORF_(\d{8})-\d{6}\.tar\.gz)"/', $html, $m, PREG_SET_ORDER ) ) {
		throw new Erreur_Acces( 'structure', 'Index du Journal officiel illisible : aucune archive JORF_AAAAMMJJ trouvée.' );
	}
	$out = array();
	foreach ( $m as $x ) {
		$out[ $x[1] ] = array(
			'nom'  => $x[1],
			'date' => \substr( $x[2], 0, 4 ) . '-' . \substr( $x[2], 4, 2 ) . '-' . \substr( $x[2], 6, 2 ),
		);
	}
	\krsort( $out );
	return \array_values( $out );
}

/**
 * Un texte XML (TEXTE_VERSION) en tableau.
 *
 * @param string $xml XML.
 * @return array<string,string>
 */
function lire_texte( string $xml ): array {
	$v = static function ( string $balise ) use ( $xml ): string {
		return \preg_match( '#<' . $balise . '>(.*?)</' . $balise . '>#s', $xml, $m ) ? \trim( \html_entity_decode( \strip_tags( $m[1] ), ENT_QUOTES | ENT_XML1, 'UTF-8' ) ) : '';
	};
	return array(
		'id'        => $v( 'ID' ),
		'nature'    => \strtoupper( $v( 'NATURE' ) ),
		'num'       => $v( 'NUM' ),
		'nor'       => $v( 'NOR' ),
		'date_jo'   => $v( 'DATE_PUBLI' ),
		'date'      => $v( 'DATE_TEXTE' ),
		'jo'        => $v( 'ORIGINE_PUBLI' ),
		'titre'     => (string) \preg_replace( '/\s*\(\d+\)\s*$/', '', $v( 'TITREFULL' ) ),
		'ministere' => $v( 'MINISTERE' ),
	);
}

/**
 * Le texte intéresse-t-il Rescue 18 ?
 *
 * @param array<string,string> $t      Texte.
 * @param string[]             $mots   Mots-clés.
 * @param string[]             $exclus Exclusions.
 * @return bool
 */
function pertinent( array $t, array $mots, array $exclus ): bool {
	if ( ! isset( natures()[ $t['nature'] ] ) || '' === $t['titre'] ) {
		return false;
	}
	$p   = plat( $t['titre'] );
	$has = static function ( array $liste ) use ( $p ): bool {
		foreach ( $liste as $x ) {
			if ( '' !== $x && \str_contains( $p, plat( $x ) ) ) {
				return true;
			}
		}
		return false;
	};
	if ( $has( $exclus ) ) {
		return false;
	}
	if ( $has( mots_forts() ) ) {
		return true;
	}
	return $has( $mots ) && ! $has( exclus_faibles() );
}

/**
 * Lit une archive tar.gz en flux et renvoie les textes retenus.
 *
 * @param string   $chemin Fichier local.
 * @param callable $garder fn( array $texte ): bool.
 * @return array{textes:array<int,array<string,string>>,lus:int}
 */
function lire_archive( string $chemin, callable $garder ): array {
	$gz = \gzopen( $chemin, 'rb' );
	if ( false === $gz ) {
		throw new Erreur_Acces( 'structure', 'Archive du Journal officiel illisible.' );
	}
	$textes = array();
	$lus    = 0;
	$long   = '';
	while ( ! \gzeof( $gz ) ) {
		$h = \gzread( $gz, 512 );
		if ( false === $h || \strlen( $h ) < 512 || '' === \trim( $h, "\0" ) ) {
			break;
		}
		$nom    = \rtrim( \substr( $h, 0, 100 ), "\0" );
		$taille = (int) \octdec( \trim( \substr( $h, 124, 12 ), "\0 " ) );
		$type   = $h[156];
		$pref   = \rtrim( \substr( $h, 345, 155 ), "\0" );
		$bloc   = (int) \ceil( $taille / 512 ) * 512;
		if ( 'L' === $type ) {
			// Nom long GNU : porté par le bloc suivant.
			$long = \rtrim( (string) \gzread( $gz, $bloc ), "\0" );
			continue;
		}
		$plein = '' !== $long ? $long : ( '' !== $pref ? $pref . '/' . $nom : $nom );
		$long  = '';
		if ( ( '0' === $type || "\0" === $type ) && \str_contains( $plein, '/texte/version/' ) && \preg_match( '/JORFTEXT\d+\.xml$/', $plein ) && $taille > 0 && $taille < 4194304 ) {
			$xml = (string) \gzread( $gz, $taille );
			if ( $bloc > $taille ) {
				\gzread( $gz, $bloc - $taille );
			}
			++$lus;
			$t = lire_texte( $xml );
			if ( \call_user_func( $garder, $t ) ) {
				$textes[] = $t;
			}
			continue;
		}
		if ( $bloc > 0 ) {
			\gzseek( $gz, $bloc, SEEK_CUR );
		}
	}
	\gzclose( $gz );
	return array( 'textes' => $textes, 'lus' => $lus );
}

/**
 * Titre et objet d'un texte : « Décret n° 2026-12 du 5 janvier 2026 » / « Relatif à… ».
 *
 * @param string $full Titre complet.
 * @return array{0:string,1:string}
 */
function couper_titre( string $full ): array {
	$full = (string) \preg_replace_callback( '/^(LOI|ORDONNANCE|DECRET|DÉCRET|ARRETE|ARRÊTÉ|CIRCULAIRE|INSTRUCTION)\b/u', static fn( array $m ): string => \mb_convert_case( \mb_strtolower( $m[1] ), MB_CASE_TITLE ), $full );
	if ( \preg_match( '/^(.*?\bdu\s+\d{1,2}(?:er)?\s+\p{L}+\s+\d{4})[\s,]*(.*)$/u', $full, $m ) ) {
		return array( \trim( $m[1] ), \trim( \mb_strtoupper( \mb_substr( $m[2], 0, 1 ) ) . \mb_substr( $m[2], 1 ) ) );
	}
	return array( $full, '' );
}

/**
 * Connecteur Journal officiel.
 */
class Connecteur_JORF implements Connecteur {

	public function id(): string {
		return 'jorf';
	}

	public function nom(): string {
		return 'Journal officiel · textes sapeurs-pompiers';
	}

	public function organisme(): string {
		return 'legifrance';
	}

	public function url(): string {
		return INDEX_JORF;
	}

	public function methode(): string {
		return 'Archives quotidiennes du JORF (DILA, licence ouverte), filtrées par mots-clés';
	}

	public function domaines(): array {
		return array( 'echanges.dila.gouv.fr' );
	}

	public function pause(): float {
		return 1.0;
	}

	public function plafond(): int {
		return 70;
	}

	/**
	 * Une unité « index », puis jusqu'à 60 archives par cycle (rattrapage de
	 * l'historique en quelques nuits, puis 2 archives par jour).
	 */
	public function unites( array $reglages ): array {
		$n   = 60;
		$out = array(
			array(
				'url'  => INDEX_JORF,
				'type' => 'index',
			),
		);
		for ( $i = 0; $i < $n; $i++ ) {
			$out[] = array(
				'url'  => 'archive ' . ( $i + 1 ),
				'type' => 'archive',
				'rang' => (string) $i,
			);
		}
		return $out;
	}

	public function traiter( array $unite, Http $http, array $reglages, callable $connu ): array {
		$st = etat_get( 'r18_jorf' );
		if ( 'index' === $unite['type'] ) {
			$depuis = \preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) ( $reglages['depuis'] ?? '' ) ) ? (string) $reglages['depuis'] : '2025-06-01';
			$faites = \array_flip( (array) ( $st['faites'] ?? array() ) );
			$todo   = array();
			foreach ( archives( $http->get( INDEX_JORF, array( 'text/html' ), false, 1048576 )->corps ) as $a ) {
				if ( $a['date'] >= $depuis && ! isset( $faites[ $a['nom'] ] ) ) {
					$todo[] = $a['nom'];
				}
			}
			$st['todo'] = $todo;
			etat_set( 'r18_jorf', $st );
			return array(
				'items'   => array(),
				'notes'   => array( \count( $todo ) . ' archive(s) du Journal officiel à lire depuis le ' . $depuis . '.' ),
				'methode' => 'opendata',
			);
		}
		$nom = (string) ( ( (array) ( $st['todo'] ?? array() ) )[ (int) $unite['rang'] ] ?? '' );
		if ( '' === $nom ) {
			return array( 'items' => array(), 'notes' => array(), 'methode' => 'opendata' );
		}
		$http->delai = 90;
		$r           = $http->get( INDEX_JORF . $nom, array( 'application/x-gzip', 'application/gzip', 'application/x-tar', 'application/octet-stream' ), false, 31457280 );
		$tmp         = \tempnam( \function_exists( 'get_temp_dir' ) ? \get_temp_dir() : \sys_get_temp_dir(), 'jorf' );
		\file_put_contents( $tmp, $r->corps ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- fichier temporaire.
		$mots   = ! empty( $reglages['mots'] ) ? (array) $reglages['mots'] : mots_defaut();
		$exclus = ! empty( $reglages['exclus'] ) ? (array) $reglages['exclus'] : exclus_defaut();
		// Les archives contiennent aussi des mises à jour de textes anciens : on ne garde
		// que les textes publiés au JO dans le mois qui précède l'archive.
		$jour  = \preg_match( '/JORF_(\d{4})(\d{2})(\d{2})/', $nom, $dm ) ? $dm[1] . '-' . $dm[2] . '-' . $dm[3] : '';
		$plancher = '' !== $jour ? \gmdate( 'Y-m-d', (int) \strtotime( $jour . ' -31 days' ) ) : '';
		try {
			$a = lire_archive( $tmp, static fn( array $t ): bool => ( '' === $plancher || $t['date_jo'] >= $plancher ) && pertinent( $t, $mots, $exclus ) );
		} finally {
			@\unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.unlink_unlink -- nettoyage.
		}
		$st             = etat_get( 'r18_jorf' );
		$st['faites']   = \array_slice( \array_values( \array_unique( \array_merge( (array) ( $st['faites'] ?? array() ), array( $nom ) ) ) ), -2000 );
		etat_set( 'r18_jorf', $st );
		$items = array();
		foreach ( $a['textes'] as $t ) {
			[ $titre, $objet ] = couper_titre( $t['titre'] );
			$items[]           = array(
				'source'    => 'jorf',
				'ref'       => $t['id'],
				'type'      => 'texte_jorf',
				'titre'     => $titre,
				'objet'     => $objet,
				'complet'   => $t['titre'],
				'nature'    => natures()[ $t['nature'] ],
				'num'       => $t['num'],
				'nor'       => $t['nor'],
				'date'      => $t['date'],
				'date_jo'   => $t['date_jo'],
				'jo'        => $t['jo'],
				'ministere' => $t['ministere'],
				'url'       => 'https://www.legifrance.gouv.fr/jorf/id/' . $t['id'],
			);
		}
		return array(
			'items'   => $items,
			'notes'   => array( $nom . ' : ' . $a['lus'] . ' texte(s) lus, ' . \count( $items ) . ' retenu(s).' ),
			'methode' => 'opendata',
		);
	}
}

/**
 * Gestionnaire : fiche r18_texte (jamais recréée si supprimée ou modifiée).
 *
 * @param array<string,string> $r Élément.
 * @param Contexte             $c Contexte.
 * @return array{action:string,id:int,note:string}
 */
function g_texte( array $r, Contexte $c ): array {
	$ids = \get_posts(
		array(
			'post_type'      => 'r18_texte',
			'post_status'    => array( 'publish', 'pending', 'draft', 'private', 'trash' ),
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_key'       => 'r18_jorf', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => $r['ref'], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
	if ( ! empty( $ids ) ) {
		return array( 'action' => 'identique', 'id' => (int) $ids[0], 'note' => '' );
	}
	if ( $c->simulation ) {
		return array( 'action' => 'nouveau', 'id' => 0, 'note' => 'Serait ajouté.' );
	}
	$jour = '' !== $r['date_jo'] ? $r['date_jo'] : $r['date'];
	$id   = (int) \wp_insert_post(
		\wp_slash(
			array(
				'post_type'     => 'r18_texte',
				'post_status'   => 'validation' === $c->publication ? 'pending' : 'publish',
				'post_title'    => $r['titre'],
				'post_excerpt'  => $r['objet'],
				'post_content'  => '<!-- wp:paragraph --><p>' . \esc_html( $r['complet'] ) . '</p><!-- /wp:paragraph -->' . ( '' !== $r['jo'] ? '<!-- wp:paragraph --><p>' . \esc_html( 'Publication : ' . $r['jo'] . '.' ) . '</p><!-- /wp:paragraph -->' : '' ),
				'post_date'     => $jour . ' 09:00:00',
				'post_date_gmt' => \get_gmt_from_date( $jour . ' 09:00:00' ),
			)
		),
		true
	);
	if ( $id <= 0 ) {
		return array( 'action' => 'erreur', 'id' => 0, 'note' => 'Création impossible.' );
	}
	foreach ( array(
		'r18_source_id'      => 'jur:' . $r['ref'],
		'r18_source_url'     => $r['url'],
		'r18_jorf'           => $r['ref'],
		'r18_nature'         => $r['nature'],
		'r18_etat'           => 'a-verifier',
		'r18_numero'         => $r['num'],
		'r18_nor'            => $r['nor'],
		'r18_signataire'     => $r['ministere'],
		'r18_date_signature' => $r['date'],
		'r18_date_jo'        => $r['date_jo'],
		'r18_derniere_synchro' => \wp_date( 'Y-m-d H:i' ),
	) as $k => $v ) {
		if ( '' !== $v ) {
			\update_post_meta( $id, $k, $v );
		}
	}
	\wp_set_object_terms( $id, 'legifrance', 'r18_organisme' );
	concours_depuis_texte( $r, $id );
	return array( 'action' => 'nouveau', 'id' => $id, 'note' => $r['jo'] );
}

/**
 * Un arrêté qui ouvre un concours ou un examen professionnel alimente le
 * calendrier (brouillon à compléter : dates d'inscription et d'épreuves).
 *
 * @param array<string,string> $r  Texte.
 * @param int                  $id Fiche juridique.
 */
function concours_depuis_texte( array $r, int $id ): void {
	$p = plat( $r['objet'] );
	if ( ! \preg_match( '/^portant ouverture|^fixant le nombre de postes|^ouvrant/', $p ) || ! \preg_match( '/concours|examen professionnel/', $p ) ) {
		return;
	}
	if ( empty( \G2RD_Rescue18\Annuaire\reglages()['detect_concours'] ) || \G2RD_Rescue18\Annuaire\par_meta( 'r18_concours', 'r18_url', $r['url'] ) > 0 ) {
		return;
	}
	$cid = (int) \wp_insert_post(
		array(
			'post_type'    => 'r18_concours',
			'post_status'  => 'draft',
			'post_title'   => \wp_strip_all_tags( $r['objet'] ),
			'post_excerpt' => $r['titre'] . ( '' !== $r['jo'] ? ', ' . $r['jo'] : '' ) . '.',
		)
	);
	if ( $cid <= 0 ) {
		return;
	}
	foreach ( array(
		'r18_cat'          => \str_contains( $p, 'examen professionnel' ) ? 'examen' : 'spp',
		'r18_organisateur' => '' !== $r['ministere'] ? $r['ministere'] : 'Ministère de l\'intérieur',
		'r18_url'          => $r['url'],
		'r18_origine'      => 'auto',
		'r18_texte_id'     => (string) $id,
	) as $k => $v ) {
		\update_post_meta( $cid, $k, $v );
	}
}

/**
 * Applique le tri aux fiches déjà créées par ce connecteur : celles qui ne
 * passent plus le filtre repassent en brouillon (réversible, rien n'est
 * supprimé). Les fiches importées ou saisies à la main ne sont pas touchées.
 *
 * @return int Fiches repassées en brouillon.
 */
function refiltrer(): int {
	$reg    = \G2RD_Rescue18\Veille\reglages( 'jorf' );
	$mots   = ! empty( $reg['mots'] ) ? (array) $reg['mots'] : mots_defaut();
	$exclus = ! empty( $reg['exclus'] ) ? (array) $reg['exclus'] : exclus_defaut();
	$n      = 0;
	foreach ( \get_posts(
		array(
			'post_type'      => 'r18_texte',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_key'       => 'r18_derniere_synchro', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		)
	) as $p ) {
		$objet = (string) $p->post_excerpt;
		$titre = $p->post_title . ' ' . ( '' !== $objet ? \mb_strtolower( \mb_substr( $objet, 0, 1 ) ) . \mb_substr( $objet, 1 ) : '' );
		$t     = array(
			'nature' => \strtoupper( (string) \get_post_meta( $p->ID, 'r18_nature', true ) ),
			'titre'  => $titre,
		);
		if ( ! pertinent( $t, $mots, $exclus ) ) {
			\wp_update_post( array( 'ID' => $p->ID, 'post_status' => 'draft' ) );
			\update_post_meta( $p->ID, 'r18_ecarte_filtre', '1' );
			++$n;
		}
	}
	return $n;
}

if ( \function_exists( 'add_filter' ) ) {
	// Nouveau tri (version 2) appliqué une fois aux fiches existantes.
	\add_action(
		'wp_loaded',
		static function (): void {
			if ( '2' === \get_option( 'r18_jorf_filtre' ) || \wp_installing() ) {
				return;
			}
			\update_option( 'r18_jorf_filtre', '2', false );
			$n = refiltrer();
			if ( $n > 0 && \function_exists( 'G2RD_Rescue18\\Veille\\journal' ) ) {
				\G2RD_Rescue18\Veille\journal( 'jorf', 'info', $n . ' texte(s) hors sujet repassé(s) en brouillon après le resserrement du filtre.' );
			}
		},
		40
	);
	\add_filter(
		'r18_veille_connecteurs',
		static fn( array $c ): array => $c + array( 'jorf' => new Connecteur_JORF() )
	);
	\add_filter(
		'r18_veille_gestionnaires',
		static fn( array $g ): array => $g + array( 'texte_jorf' => __NAMESPACE__ . '\g_texte' )
	);
}
