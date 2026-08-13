<?php
/**
 * Support du bloc « G2RD Carte de parcours GPX ».
 *
 * ── Impact sur les sites existants ───────────────────────────────────────────
 * Le seul effet de bord observable — l'autorisation du dépôt de fichiers GPX et
 * KML dans la médiathèque — est conditionné à une déclaration explicite du thème
 * actif :
 *
 *     add_theme_support( 'g2rd-route-map' );
 *
 * ou au filtre `g2rd_route_map_enabled` pour un site qui n'a pas de thème
 * enfant. Un site qui ne fait ni l'un ni l'autre ne voit aucun type MIME
 * supplémentaire accepté.
 *
 * L'enregistrement de Leaflet, lui, n'est pas conditionné : `wp_register_script`
 * n'émet rien, ne charge rien et n'ajoute aucune requête. Le script n'est mis en
 * file que lorsqu'un bloc `g2rd/route-map` est réellement présent dans la page,
 * via la dépendance déclarée dans `view.asset.php`. C'est ce qui permet au bloc
 * de fonctionner tel quel, sans câblage supplémentaire côté thème enfant.
 *
 * L'enregistrement est en outre conditionné à la présence effective du fichier :
 * sans `assets/vendor/leaflet/leaflet.js`, rien n'est déclaré et aucune 404
 * n'est émise.
 *
 * @package G2RD
 * @since   1.36.0
 */

namespace G2RD;

defined( 'ABSPATH' ) || exit;

/**
 * Câblage du bloc de carte de parcours.
 */
class RouteMapSupport {

	/**
	 * Version de Leaflet embarquée.
	 */
	private const LEAFLET_VERSION = '1.9.4';

	/**
	 * Taille maximale d'un GPX analysé, en octets.
	 *
	 * Au-delà, le fichier n'est pas un tracé mais un journal d'activité complet :
	 * l'analyser bloquerait la génération de la page pour un résultat inutile.
	 */
	private const TAILLE_MAX = 20971520; // 20 Mo.

	/**
	 * Nombre de points conservés après simplification du tracé.
	 */
	private const POINTS_MAX = 400;

	/**
	 * Accroche les hooks.
	 */
	public function register_hooks(): void {
		\add_action( 'init', [ $this, 'register_leaflet' ], 5 );
		\add_filter( 'upload_mimes', [ $this, 'allow_gpx' ] );
		// 3 arguments seulement : le quatrième ($mimes) ne sert pas ici.
		\add_filter( 'wp_check_filetype_and_ext', [ $this, 'check_gpx' ], 10, 3 );
	}

	/**
	 * Le site autorise-t-il le dépôt de traces GPX ?
	 *
	 * @return bool
	 */
	private function is_enabled(): bool {
		/**
		 * Filtre l'activation du support des fichiers de trace.
		 *
		 * @since 1.36.0
		 * @param bool $actif Vrai si le thème actif déclare `g2rd-route-map`.
		 */
		return (bool) \apply_filters( 'g2rd_route_map_enabled', \current_theme_supports( 'g2rd-route-map' ) );
	}

	/**
	 * Enregistre Leaflet en version auto-hébergée.
	 *
	 * Servi depuis le thème et non depuis un CDN : une carte n'a aucune raison
	 * d'attendre un consentement cookies, et un script tiers de plus pèse sur le
	 * budget de performance.
	 */
	public function register_leaflet(): void {
		$dir  = \get_template_directory() . '/assets/vendor/leaflet/';
		$base = \get_template_directory_uri() . '/assets/vendor/leaflet/';

		if ( ! file_exists( $dir . 'leaflet.js' ) ) {
			return;
		}

		\wp_register_style( 'leaflet', $base . 'leaflet.css', [], self::LEAFLET_VERSION );
		\wp_register_script( 'leaflet', $base . 'leaflet.js', [], self::LEAFLET_VERSION, true );
		\wp_script_add_data( 'leaflet', 'strategy', 'defer' );
	}

	/**
	 * Autorise le dépôt de fichiers GPX et KML dans la médiathèque.
	 *
	 * WordPress les refuse par défaut. Sans cela, mettre un tracé en ligne
	 * suppose un accès FTP — inacceptable pour une équipe qui doit pouvoir
	 * republier un parcours modifié en autonomie.
	 *
	 * @param array<string,string> $mimes Types autorisés.
	 * @return array<string,string>
	 */
	public function allow_gpx( array $mimes ): array {
		if ( ! $this->is_enabled() ) {
			return $mimes;
		}

		$mimes['gpx'] = 'application/gpx+xml';
		$mimes['kml'] = 'application/vnd.google-earth.kml+xml';

		return $mimes;
	}

	/**
	 * Corrige la détection de type pour les GPX et KML.
	 *
	 * `finfo` renvoie `text/xml` pour un GPX, ce qui fait échouer le contrôle de
	 * cohérence entre l'extension et le type réel. On rétablit explicitement le
	 * couple attendu, et uniquement pour ces deux extensions.
	 *
	 * @param array<string,mixed> $donnees Résultat du contrôle.
	 * @param string              $fichier Chemin du fichier.
	 * @param string              $nom     Nom du fichier.
	 * @return array<string,mixed>
	 */
	public function check_gpx( array $donnees, string $fichier, string $nom ): array {
		if ( ! $this->is_enabled() ) {
			return $donnees;
		}

		if ( ! empty( $donnees['ext'] ) && ! empty( $donnees['type'] ) ) {
			return $donnees;
		}

		$extension = strtolower( (string) pathinfo( $nom, PATHINFO_EXTENSION ) );

		if ( 'gpx' === $extension ) {
			$donnees['ext']  = 'gpx';
			$donnees['type'] = 'application/gpx+xml';
		} elseif ( 'kml' === $extension ) {
			$donnees['ext']  = 'kml';
			$donnees['type'] = 'application/vnd.google-earth.kml+xml';
		}

		return $donnees;
	}

	/**
	 * Analyse un fichier GPX et renvoie le tracé simplifié, le profil et les
	 * statistiques.
	 *
	 * Le résultat est mis en cache dans un transient dont la clé intègre la date
	 * de modification du fichier : remplacer le GPX invalide le cache
	 * automatiquement, sans purge manuelle.
	 *
	 * @since 1.36.0
	 * @param int $attachment_id Identifiant du média GPX.
	 * @return array{points:array<int,array<int,float>>,profil:array<int,array<int,float>>,stats:array<string,float|int>}|null
	 */
	public static function parse_gpx( int $attachment_id ): ?array {
		$chemin = \get_attached_file( $attachment_id );

		if ( ! $chemin || ! is_readable( $chemin ) ) {
			return null;
		}

		// Le bloc n'analyse que des traces : tout autre média est refusé avant
		// même d'être ouvert.
		$extension = strtolower( (string) pathinfo( $chemin, PATHINFO_EXTENSION ) );
		if ( 'gpx' !== $extension ) {
			return null;
		}

		$taille = (int) @filesize( $chemin ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		if ( $taille <= 0 || $taille > self::TAILLE_MAX ) {
			return null;
		}

		$cle   = 'g2rd_gpx_' . $attachment_id . '_' . (string) @filemtime( $chemin ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		$cache = \get_transient( $cle );

		if ( false !== $cache ) {
			return $cache;
		}

		// LIBXML_NONET coupe l'accès réseau ; LIBXML_NOENT est volontairement
		// omis, sans quoi les entités externes seraient substituées (XXE).
		$xml = @simplexml_load_file( $chemin, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA ); // phpcs:ignore WordPress.PHP.NoSilencedErrors

		if ( false === $xml ) {
			return null;
		}

		$points = [];

		foreach ( $xml->xpath( '//*[local-name()="trkpt"] | //*[local-name()="rtept"]' ) ?: [] as $pt ) {
			$lat = isset( $pt['lat'] ) ? (float) $pt['lat'] : null;
			$lon = isset( $pt['lon'] ) ? (float) $pt['lon'] : null;

			if ( null === $lat || null === $lon ) {
				continue;
			}

			$ele = 0.0;
			$e   = $pt->xpath( './*[local-name()="ele"]' );
			if ( ! empty( $e ) ) {
				$ele = (float) $e[0];
			}

			$points[] = [ $lat, $lon, $ele ];
		}

		if ( count( $points ) < 2 ) {
			return null;
		}

		// Distance cumulée (haversine) et altitudes extrêmes.
		$distance = 0.0;
		$alt_min  = PHP_FLOAT_MAX;
		$alt_max  = -PHP_FLOAT_MAX;
		$cumul    = [ 0.0 ];

		for ( $i = 1, $n = count( $points ); $i < $n; $i++ ) {
			[ $lat1, $lon1, $ele1 ] = $points[ $i - 1 ];
			[ $lat2, $lon2, $ele2 ] = $points[ $i ];

			$r         = 6371000.0;
			$dla       = deg2rad( $lat2 - $lat1 );
			$dlo       = deg2rad( $lon2 - $lon1 );
			$a         = sin( $dla / 2 ) ** 2 + cos( deg2rad( $lat1 ) ) * cos( deg2rad( $lat2 ) ) * sin( $dlo / 2 ) ** 2;
			$distance += $r * 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) );

			$cumul[] = $distance;

			$alt_min = min( $alt_min, $ele1, $ele2 );
			$alt_max = max( $alt_max, $ele1, $ele2 );
		}

		// Dénivelé positif, par hystérésis.
		//
		// Un seuil appliqué entre deux points consécutifs ne fonctionne pas : sur
		// un GPX enregistré à la seconde, l'écart d'altitude d'un point au suivant
		// est toujours inférieur au seuil, et le dénivelé ressort à zéro.
		//
		// On garde donc une altitude de référence et on ne comptabilise une montée
		// que lorsqu'elle dépasse le seuil depuis cette référence ; toute descente
		// abaisse la référence. C'est la méthode utilisée par les plateformes de
		// suivi d'activité, et elle absorbe le bruit GPS sans effacer le relief.
		$seuil     = 3.0; // Mètres — bruit vertical typique d'un GPS grand public.
		$denivele  = 0.0;
		$reference = $points[0][2];

		foreach ( $points as [ , , $ele ] ) {
			if ( $ele > $reference + $seuil ) {
				$denivele += $ele - $reference;
				$reference = $ele;
			} elseif ( $ele < $reference ) {
				$reference = $ele;
			}
		}

		// Simplification : au-delà de quelques centaines de points, le tracé
		// n'apporte plus rien à l'œil mais alourdit le JSON.
		$total     = count( $points );
		$pas       = max( 1, (int) ceil( $total / self::POINTS_MAX ) );
		$simplifie = [];
		$profil    = [];

		for ( $i = 0; $i < $total; $i += $pas ) {
			$simplifie[] = [ round( $points[ $i ][0], 6 ), round( $points[ $i ][1], 6 ) ];
			$profil[]    = [ round( $cumul[ $i ], 1 ), round( $points[ $i ][2], 1 ) ];
		}

		// Le dernier point ferme toujours la boucle.
		$dernier     = $points[ $total - 1 ];
		$simplifie[] = [ round( $dernier[0], 6 ), round( $dernier[1], 6 ) ];
		$profil[]    = [ round( $cumul[ $total - 1 ], 1 ), round( $dernier[2], 1 ) ];

		$resultat = [
			'points' => $simplifie,
			'profil' => $profil,
			'stats'  => [
				'distance' => round( $distance / 1000, 2 ),
				'denivele' => (int) round( $denivele ),
				'alt_min'  => (int) round( $alt_min ),
				'alt_max'  => (int) round( $alt_max ),
			],
		];

		\set_transient( $cle, $resultat, WEEK_IN_SECONDS );

		return $resultat;
	}
}
