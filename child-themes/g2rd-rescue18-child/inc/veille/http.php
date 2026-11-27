<?php
/**
 * Veille documentaire : accès HTTP encadré.
 *
 * - API HTTP de WordPress (wp_safe_remote_get : refuse les adresses privées,
 *   protection SSRF), HTTPS seulement, domaines autorisés par connecteur.
 * - Redirections suivies à la main (3 au plus), domaine revérifié à chaque saut.
 * - Délai maximal, taille maximale, type MIME contrôlé.
 * - Temporisation entre deux requêtes vers un même hôte, plafond par cycle.
 * - Requêtes conditionnelles (ETag, Last-Modified) quand la source les fournit.
 * - Empreinte du contenu utile de chaque adresse lue : une page ou un flux
 *   inchangé depuis le cycle précédent n'est pas retraité.
 * - Une protection anti-robots (Cloudflare « challenge ») ou une limitation
 *   (429) arrête la source pour le cycle : aucun contournement, aucun
 *   faux navigateur, aucun proxy.
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18\Veille;

/**
 * Page de référence inchangée depuis le cycle précédent : l'unité est ignorée.
 */
class Inchange extends \RuntimeException {}

/**
 * Empreinte du contenu utile d'une réponse : scripts, styles, commentaires,
 * champs cachés, méta et dates de génération des flux sont ignorés, pour
 * qu'une page régénérée sans nouveauté garde la même empreinte.
 *
 * @param string $corps Corps.
 * @return string
 */
function empreinte_corps( string $corps ): string {
	$t = (string) \preg_replace(
		array( '#<script\b.*?</script>#is', '#<style\b.*?</style>#is', '#<!--.*?-->#s', '#<(input|meta|link)\b[^>]*>#i', '#<(lastBuildDate|updated|generator)>.*?</\1>#is', '#\s+#u' ),
		array( '', '', '', '', '', ' ' ),
		$corps
	);
	return \sha1( \trim( $t ) );
}

/**
 * Erreur d'accès classée : bloque|limite|http|reseau|type|taille|domaine.
 */
class Erreur_Acces extends \RuntimeException {

	/**
	 * Catégorie.
	 *
	 * @var string
	 */
	public string $categorie;

	/**
	 * Constructeur.
	 *
	 * @param string $categorie Catégorie.
	 * @param string $message   Message lisible.
	 */
	public function __construct( string $categorie, string $message ) {
		parent::__construct( $message );
		$this->categorie = $categorie;
	}
}

/**
 * Réponse simplifiée.
 */
class Reponse {

	/**
	 * Constructeur.
	 *
	 * @param int                  $statut  Code HTTP.
	 * @param string               $corps   Corps.
	 * @param array<string,string> $entetes En-têtes (clés en minuscules).
	 * @param string               $url     URL finale.
	 */
	public function __construct( public int $statut, public string $corps, public array $entetes, public string $url ) {}

	/**
	 * Type MIME sans paramètres.
	 *
	 * @return string
	 */
	public function mime(): string {
		return \strtolower( \trim( \explode( ';', $this->entetes['content-type'] ?? '' )[0] ) );
	}
}

/**
 * Transport : appelle réellement le réseau. Remplacé par des fixtures en test.
 */
interface Transport {

	/**
	 * GET sans suivre les redirections.
	 *
	 * @param string               $url     URL.
	 * @param array<string,string> $entetes En-têtes.
	 * @param int                  $delai   Délai maximal (s).
	 * @param int                  $taille  Taille maximale (octets).
	 * @return Reponse
	 */
	public function get( string $url, array $entetes, int $delai, int $taille ): Reponse;

	/**
	 * Pause entre deux requêtes.
	 *
	 * @param float $secondes Durée.
	 */
	public function pause( float $secondes ): void;
}

/**
 * Transport WordPress.
 */
class Transport_WP implements Transport {

	/**
	 * {@inheritDoc}
	 */
	public function get( string $url, array $entetes, int $delai, int $taille ): Reponse {
		$r = \wp_safe_remote_get(
			$url,
			array(
				'timeout'             => $delai,
				'redirection'         => 0,
				'limit_response_size' => $taille,
				'headers'             => $entetes,
				'user-agent'          => agent(),
				'sslverify'           => true,
			)
		);
		if ( \is_wp_error( $r ) ) {
			throw new Erreur_Acces( 'reseau', 'Source injoignable : ' . $r->get_error_message() );
		}
		$h = array();
		$brut = \wp_remote_retrieve_headers( $r );
		// Objet CaseInsensitiveDictionary (Requests) : getAll(), pas de conversion (array).
		$brut = \is_object( $brut ) && \method_exists( $brut, 'getAll' ) ? $brut->getAll() : (array) $brut;
		foreach ( $brut as $k => $v ) {
			$h[ \strtolower( (string) $k ) ] = \is_array( $v ) ? \implode( ', ', \array_map( 'strval', $v ) ) : (string) $v;
		}
		return new Reponse( (int) \wp_remote_retrieve_response_code( $r ), (string) \wp_remote_retrieve_body( $r ), $h, $url );
	}

	/**
	 * {@inheritDoc}
	 */
	public function pause( float $secondes ): void {
		if ( $secondes > 0 ) {
			\usleep( (int) ( $secondes * 1000000 ) );
		}
	}
}

/**
 * Identité honnête du collecteur.
 *
 * @return string
 */
function agent(): string {
	$site = \function_exists( 'home_url' ) ? \home_url( '/' ) : 'https://rescue18.com/';
	return 'Rescue18-Veille/1.0 (+' . $site . '; WordPress; veille documentaire quotidienne)';
}

/**
 * Client HTTP d'un connecteur.
 */
class Http {

	/**
	 * Requêtes émises pendant le cycle.
	 *
	 * @var int
	 */
	public int $requetes = 0;

	/**
	 * Délai maximal d'une requête (secondes).
	 *
	 * @var int
	 */
	public int $delai = 20;

	/**
	 * Dernier appel par hôte (microtime).
	 *
	 * @var array<string,float>
	 */
	private array $dernier = array();

	/**
	 * Validateurs conditionnels mémorisés : url => [etag, last-modified].
	 *
	 * @var array<string,array{0:string,1:string}>
	 */
	public array $validateurs = array();

	/**
	 * Empreintes connues au cycle précédent (URL => sha1).
	 *
	 * @var array<string,string>
	 */
	public array $connues = array();

	/**
	 * Empreintes lues pendant ce passage (URL => sha1).
	 *
	 * @var array<string,string>
	 */
	public array $vues = array();

	/**
	 * Page de référence de l'unité : inchangée, elle arrête l'unité (Inchange).
	 *
	 * @var string
	 */
	public string $garde = '';

	/**
	 * Contenu d'une adresse nouveau ou modifié depuis le cycle précédent.
	 *
	 * @param string $url URL lue dans ce passage.
	 * @return bool
	 */
	public function nouveau( string $url ): bool {
		return ! isset( $this->vues[ $url ], $this->connues[ $url ] ) || $this->vues[ $url ] !== $this->connues[ $url ];
	}

	/**
	 * Constructeur.
	 *
	 * @param Transport $transport Transport.
	 * @param string[]  $domaines  Hôtes autorisés.
	 * @param float     $pause     Secondes entre deux requêtes vers un hôte.
	 * @param int       $plafond   Requêtes maximales pour le cycle.
	 */
	public function __construct( private Transport $transport, private array $domaines, private float $pause = 3.0, private int $plafond = 40 ) {}

	/**
	 * Contrôle d'URL : HTTPS et domaine autorisé.
	 *
	 * @param string $url URL.
	 */
	public function verifier_url( string $url ): void {
		$p = \parse_url( $url );
		if ( ! \is_array( $p ) || 'https' !== \strtolower( (string) ( $p['scheme'] ?? '' ) ) ) {
			throw new Erreur_Acces( 'domaine', 'URL refusée (HTTPS obligatoire) : ' . $url );
		}
		$host = \strtolower( (string) ( $p['host'] ?? '' ) );
		$tous = \in_array( '*', $this->domaines, true ) && \str_contains( $host, '.' ) && ! \preg_match( '/^[\d.]+$|^\[|localhost|\.local$|\.internal$/', $host );
		if ( ( ! $tous && ! \in_array( $host, $this->domaines, true ) ) || isset( $p['user'] ) || isset( $p['port'] ) ) {
			throw new Erreur_Acces( 'domaine', 'Domaine non autorisé : ' . $host );
		}
	}

	/**
	 * GET contrôlé.
	 *
	 * @param string   $url          URL.
	 * @param string[] $mimes        Types MIME acceptés.
	 * @param bool     $conditionnel Envoyer ETag / Last-Modified connus.
	 * @param int      $taille       Taille maximale.
	 * @param array<string,string> $extra En-têtes supplémentaires (Range…).
	 * @return Reponse Statut 304 possible si conditionnel.
	 */
	public function get( string $url, array $mimes = array( 'text/html' ), bool $conditionnel = false, int $taille = 3145728, array $extra = array() ): Reponse {
		$sauts = 0;
		while ( true ) {
			$this->verifier_url( $url );
			if ( $this->requetes >= $this->plafond ) {
				throw new Erreur_Acces( 'limite', 'Plafond de ' . $this->plafond . ' requêtes atteint pour ce cycle. La suite reprendra au prochain cycle.' );
			}
			$host = (string) \parse_url( $url, PHP_URL_HOST );
			if ( isset( $this->dernier[ $host ] ) ) {
				$attente = $this->pause - ( \microtime( true ) - $this->dernier[ $host ] );
				$this->transport->pause( \max( 0.0, $attente ) );
			}
			$entetes = \array_merge( array( 'Accept' => \implode( ', ', $mimes ) . ', */*;q=0.1', 'Accept-Language' => 'fr-FR,fr;q=0.9' ), $extra );
			if ( ( $conditionnel || $url === $this->garde ) && isset( $this->validateurs[ $url ] ) ) {
				[ $etag, $lm ] = $this->validateurs[ $url ];
				if ( '' !== $etag ) {
					$entetes['If-None-Match'] = $etag;
				}
				if ( '' !== $lm ) {
					$entetes['If-Modified-Since'] = $lm;
				}
			}
			++$this->requetes;
			$this->dernier[ $host ] = \microtime( true );
			$r = $this->transport->get( $url, $entetes, $this->delai, $taille );

			if ( \in_array( $r->statut, array( 301, 302, 303, 307, 308 ), true ) ) {
				if ( ++$sauts > 3 ) {
					throw new Erreur_Acces( 'http', 'Trop de redirections depuis ' . $url );
				}
				// Redirection vers http:// : on tente la même adresse en HTTPS.
				$url = (string) \preg_replace( '#^http://#i', 'https://', absolue( (string) ( $r->entetes['location'] ?? '' ), $url ) );
				continue;
			}
			break;
		}

		if ( '' !== ( $r->entetes['cf-mitigated'] ?? '' ) || ( 403 === $r->statut && \str_contains( $r->corps, 'challenges.cloudflare.com' ) ) ) {
			throw new Erreur_Acces( 'bloque', 'Accès automatisé refusé par la protection anti-robots du site (Cloudflare, HTTP ' . $r->statut . '). Aucun contournement.' );
		}
		if ( 429 === $r->statut ) {
			$retry = (string) ( $r->entetes['retry-after'] ?? '' );
			throw new Erreur_Acces( 'limite', 'Limitation de requêtes (HTTP 429)' . ( '' !== $retry ? ', réessayer après ' . $retry . ' s' : '' ) . '.' );
		}
		if ( 304 === $r->statut && isset( $this->connues[ $url ] ) ) {
			$this->vues[ $url ] = $this->connues[ $url ];
		}
		if ( 304 === $r->statut && $url === $this->garde ) {
			throw new Inchange( 'Page inchangée (304).' );
		}
		if ( 304 === $r->statut && $conditionnel ) {
			return $r;
		}
		if ( 416 === $r->statut && isset( $extra['Range'] ) ) {
			throw new Erreur_Acces( 'fin', 'Fin du fichier atteinte.' );
		}
		if ( $r->statut < 200 || $r->statut >= 300 ) {
			$lisible = (string) \preg_replace( '/\?.*$/', '', $url );
			$code    = array( 502 => ' (serveur de la source surchargé)', 503 => ' (source indisponible)', 504 => ' (source trop lente)', 404 => ' (page introuvable)', 403 => ' (accès refusé par la source)' )[ $r->statut ] ?? '';
			throw new Erreur_Acces( 'http', 'Erreur HTTP ' . $r->statut . $code . ' sur ' . $lisible );
		}
		$mime = $r->mime();
		if ( '' !== $mime && ! \in_array( $mime, $mimes, true ) ) {
			throw new Erreur_Acces( 'type', 'Type de contenu inattendu (' . $mime . ') sur ' . $url );
		}
		if ( \strlen( $r->corps ) >= $taille && ! isset( $extra['Range'] ) ) {
			throw new Erreur_Acces( 'taille', 'Réponse trop volumineuse sur ' . $url );
		}
		if ( ! isset( $extra['Range'] ) && \strlen( $r->corps ) <= 5242880 ) {
			$this->vues[ $url ] = empreinte_corps( $r->corps );
			if ( $url === $this->garde && ! $this->nouveau( $url ) ) {
				throw new Inchange( 'Contenu identique au cycle précédent.' );
			}
		}
		$etag = (string) ( $r->entetes['etag'] ?? '' );
		$lm   = (string) ( $r->entetes['last-modified'] ?? '' );
		if ( '' !== $etag || '' !== $lm ) {
			$this->validateurs[ $url ] = array( $etag, $lm );
		}
		return $r;
	}
}
