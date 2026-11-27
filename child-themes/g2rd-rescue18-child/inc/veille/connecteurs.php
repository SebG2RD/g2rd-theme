<?php
/**
 * Veille documentaire : connecteurs DGSCGC, PNRS et ENSOSP.
 *
 * Chaque connecteur découpe son travail en unités (une page source = une
 * unité). Le moteur traite les unités par lots et garde les résultats
 * acquis si une unité échoue.
 *
 * Constats des tests d'accès (30 octobre 2026), depuis un serveur :
 * - DGSCGC : protection Cloudflare (HTTP 403, « challenge ») sur toutes les
 *   pages. robots.txt interdit /*.pdf$ et /content/download/. Le connecteur
 *   tente une requête par cycle, constate le blocage et s'arrête. L'import
 *   assisté (HTML collé depuis le navigateur) passe par le même analyseur.
 * - PNRS : pages et flux RSS 2.0 accessibles. Les clés RSS sont relues sur
 *   la page à chaque cycle, jamais écrites en dur. Limitation 429 constatée :
 *   4 s entre deux requêtes.
 * - ENSOSP : pas de flux RSS. Liste HTML des actualités, balises og:
 *   sur chaque article.
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18\Veille;

/**
 * Contrat d'un connecteur.
 */
interface Connecteur {

	/** @return string Identifiant. */
	public function id(): string;

	/** @return string Nom affiché. */
	public function nom(): string;

	/** @return string Slug r18_organisme de la source. */
	public function organisme(): string;

	/** @return string URL d'entrée. */
	public function url(): string;

	/** @return string Méthode utilisée. */
	public function methode(): string;

	/** @return string[] Hôtes autorisés. */
	public function domaines(): array;

	/** @return float Secondes entre deux requêtes. */
	public function pause(): float;

	/** @return int Requêtes maximales par cycle. */
	public function plafond(): int;

	/**
	 * Unités de travail du cycle.
	 *
	 * @param array<string,mixed> $reglages Réglages du connecteur.
	 * @return array<int,array<string,string>>
	 */
	public function unites( array $reglages ): array;

	/**
	 * Traite une unité.
	 *
	 * @param array<string,string> $unite    Unité.
	 * @param Http                 $http     Client.
	 * @param array<string,mixed>  $reglages Réglages.
	 * @param callable             $connu    fn(string $ref): bool, référence déjà suivie.
	 * @return array{items:array<int,array<string,string>>,notes:string[],methode:string}
	 */
	public function traiter( array $unite, Http $http, array $reglages, callable $connu ): array;
}

/* -------------------------------------------------------------------------
 * DGSCGC
 * ---------------------------------------------------------------------- */

/**
 * Pages documentaires DGSCGC suivies : URL => type par défaut.
 *
 * @return array<string,string>
 */
function pages_dgscgc_defaut(): array {
	$b = 'https://www.securite-civile.interieur.gouv.fr/documentation/';
	return array(
		$b . 'doctrines-et-techniques-professionnelles/sapeurs-pompiers-guide-de-doctrine-operationnelle.html' => 'gdo',
		$b . 'doctrines-et-techniques-professionnelles/sapeurs-pompiers-guides-de-techniques-operationnelles.html' => 'gto',
		$b . 'doctrines-et-techniques-professionnelles/sapeurs-pompiers-doctrines-et-techniques-professionnelles.html' => 'gnr',
		$b . 'doctrines-et-techniques-professionnelles/sapeurs-pompiers-doctrines-et-techniques-professionnelles-partage-dinformation-operationnelle-pio.html' => 'pio',
		$b . 'doctrines-et-techniques-professionnelles/sapeurs-pompiers-doctrines-et-techniques-professionnelles-memento.html' => 'memento',
		$b . 'doctrines-et-techniques-professionnelles/sapeurs-pompiers-doctrines-et-techniques-professionnelles-vademecum.html' => 'vademecum',
		$b . 'formation-des-sapeurs-pompiers/sapeurs-pompiers-formation-aux-emplois-operationnels-et-dencadrement.html' => 'referentiel',
	);
}

/**
 * Analyse une page documentaire DGSCGC (thème DSFR de l'État).
 * Lit les liens de téléchargement, sans dépendre d'une classe CSS : la
 * classe « fr-link--downloads » sert en priorité, les liens PDF du contenu
 * principal en repli.
 *
 * @param string $html  HTML de la page.
 * @param string $url   URL de la page.
 * @param string $type  Type par défaut.
 * @return array<int,array<string,string>>
 */
function analyser_dgscgc( string $html, string $url, string $type ): array {
	$x     = dom( $html );
	$liens = $x->query( "//a[contains(concat(' ', normalize-space(@class), ' '), ' fr-link--downloads ')]" );
	if ( ! $liens || 0 === $liens->length ) {
		$liens = $x->query( "//main//a[contains(translate(@href, 'PDF', 'pdf'), '.pdf')]" );
	}
	$categorie = texte( $x->query( '//main//h1' )->item( 0 ) );
	$out       = array();
	$vus       = array();
	foreach ( $liens ?: array() as $a ) {
		/** @var \DOMElement $a */
		$cx    = new \DOMXPath( $a->ownerDocument );
		foreach ( $cx->query( ".//*[contains(@class,'fr-sr-only') or contains(@class,'fr-link__detail') or contains(@class,'fr-link__icon')]", $a ) as $n ) {
			$n->parentNode && $n->parentNode->removeChild( $n );
		}
		$titre = \trim( (string) \preg_replace( '/^t[ée]l[ée]charger\s*/iu', '', texte( $a ) ) );
		$pdf   = absolue( $a->getAttribute( 'href' ), $url );
		if ( '' === $titre || '' === $pdf || isset( $vus[ $pdf ] ) ) {
			continue;
		}
		$vus[ $pdf ] = true;
		$t           = type_depuis_titre( $titre, 'auto' === $type ? 'autre' : $type );
		$fichier     = \rawurldecode( (string) \basename( (string) \parse_url( $pdf, PHP_URL_PATH ) ) );
		$mise_ligne  = \preg_match( '#/(20\d{2})-(\d{2})/#', $pdf, $m ) ? $m[1] . '-' . $m[2] . '-01' : '';
		$version     = version_depuis( $titre );
		$version     = '' !== $version ? $version : version_depuis( $fichier );
		$out[]       = array(
			'source'    => 'dgscgc',
			'ref'       => cle( $titre, $t ),
			'titre'     => $titre,
			'type'      => $t,
			'editeur'   => 'dgscgc',
			'url'       => $url,
			'pdf'       => $pdf,
			'date'      => '',
			'date_maj'  => $mise_ligne,
			'version'   => $version,
			'resume'    => '',
			'image'     => '',
			'categorie' => $categorie,
			'signal'    => $fichier,
		);
	}
	return $out;
}

/**
 * Connecteur DGSCGC.
 */
class Connecteur_DGSCGC implements Connecteur {

	public function id(): string {
		return 'dgscgc';
	}

	public function nom(): string {
		return 'DGSCGC · Sécurité civile';
	}

	public function organisme(): string {
		return 'dgscgc';
	}

	public function url(): string {
		return 'https://www.securite-civile.interieur.gouv.fr/documentation';
	}

	public function methode(): string {
		return 'Lecture HTML contrôlée des pages publiques (aucune API ni flux disponible)';
	}

	public function domaines(): array {
		return array( 'www.securite-civile.interieur.gouv.fr' );
	}

	public function pause(): float {
		return 3.0;
	}

	public function plafond(): int {
		return 12;
	}

	public function unites( array $reglages ): array {
		$out = array();
		// Source bloquée récemment : une seule nouvelle tentative par semaine, sauf demande manuelle.
		$etat = \function_exists( 'get_option' ) ? (array) ( ( (array) \get_option( 'r18_veille_etat', array() ) )['dgscgc'] ?? array() ) : array();
		if ( empty( $reglages['force'] ) && 'bloque' === ( $etat['resultat'] ?? '' ) && \time() - (int) ( $etat['verif'] ?? 0 ) < 7 * 86400 ) {
			return array();
		}
		foreach ( pages_reglees( $reglages, pages_dgscgc_defaut() ) as $url => $type ) {
			$out[] = array(
				'url'  => $url,
				'type' => $type,
			);
		}
		return $out;
	}

	public function traiter( array $unite, Http $http, array $reglages, callable $connu ): array {
		try {
			$r = $http->get( $unite['url'], array( 'text/html' ), true );
		} catch ( Erreur_Acces $e ) {
			// 403 : le site refuse les robots. Aucun contournement : arrêt du connecteur pour le cycle.
			if ( 'http' === $e->categorie && \str_contains( $e->getMessage(), 'HTTP 403' ) ) {
				throw new Erreur_Acces( 'bloque', 'Le site de la DGSCGC refuse les lectures automatiques (HTTP 403). Rescue 18 ne contourne pas ce blocage. Utilise l\'import en un clic ci-dessous.' );
			}
			throw $e;
		}
		if ( 304 === $r->statut ) {
			return array(
				'items'   => array(),
				'notes'   => array( 'Page inchangée depuis la dernière lecture (304).' ),
				'methode' => 'html',
			);
		}
		$items = analyser_dgscgc( $r->corps, $unite['url'], $unite['type'] );
		if ( empty( $items ) ) {
			throw new Erreur_Acces( 'structure', 'Aucun document trouvé sur ' . $unite['url'] . '. La structure de la page a peut-être changé : vérifier avant toute action.' );
		}
		return array(
			'items'   => $items,
			'notes'   => array( \count( $items ) . ' document(s) lus.' ),
			'methode' => 'html',
		);
	}
}

/* -------------------------------------------------------------------------
 * PNRS
 * ---------------------------------------------------------------------- */

/**
 * Pages PNRS suivies : URL => type par défaut.
 *
 * @return array<string,string>
 */
function pages_pnrs_defaut(): array {
	$b = 'https://pnrs.ensosp.fr/';
	return array(
		$b . 'default/gdo-gto.aspx?_lg=fr-FR'                         => 'auto',
		$b . 'default/biblio-retex-ri.aspx'                            => 'retex',
		$b . 'default/biblio-retex-secours-durgence-a-personne.aspx'   => 'retex',
		$b . 'default/biblio-retex-risques-naturels.aspx'              => 'retex',
		$b . 'default/biblio-retex-risques-technologiques.aspx'        => 'retex',
		$b . 'default/biblio-retex-operations-particulieres.aspx'      => 'retex',
	);
}

/**
 * Liens RSS présents sur une page PNRS (lus dans le HTML, jamais devinés).
 *
 * @param string $html HTML.
 * @param string $url  URL de la page.
 * @return string[]
 */
function flux_pnrs( string $html, string $url ): array {
	$x   = dom( $html );
	$out = array();
	foreach ( $x->query( "//a[contains(@href, 'Search.svc/SearchRss')] | //link[contains(@href, 'Search.svc/SearchRss')]" ) as $a ) {
		/** @var \DOMElement $a */
		$u = absolue( $a->getAttribute( 'href' ), $url );
		if ( '' !== $u && \preg_match( '/[?&]key=[0-9a-f]{16,64}(&|$)/i', $u ) ) {
			$out[ $u ] = true;
		}
	}
	return \array_keys( $out );
}

/**
 * Analyse un flux RSS 2.0 du PNRS. Refuse tout XML qui n'est pas un flux
 * RSS lisible avec des éléments complets.
 *
 * @param string $xml  Corps du flux.
 * @param string $type Type par défaut.
 * @return array<int,array<string,string>>
 */
function analyser_flux_pnrs( string $xml, string $type ): array {
	$doc = new \DOMDocument();
	\libxml_use_internal_errors( true );
	$ok = '' !== \trim( $xml ) && $doc->loadXML( $xml, LIBXML_NONET | LIBXML_NOCDATA );
	\libxml_clear_errors();
	if ( ! $ok || 'rss' !== $doc->documentElement?->nodeName || 0 === $doc->getElementsByTagName( 'channel' )->length ) {
		throw new Erreur_Acces( 'flux', 'Réponse XML illisible ou qui n\'est pas un flux RSS.' );
	}
	$out = array();
	foreach ( $doc->getElementsByTagName( 'item' ) as $item ) {
		/** @var \DOMElement $item */
		$get   = static fn( string $tag ): string => \trim( (string) ( $item->getElementsByTagName( $tag )->item( 0 )?->textContent ?? '' ) );
		$titre = \html_entity_decode( $get( 'title' ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$lien  = $get( 'link' );
		if ( '' === $titre || ! \preg_match( '#^https://pnrs\.ensosp\.fr/.+/SYRACUSE/(\d+)#i', $lien, $m ) ) {
			continue;
		}
		$info   = '';
		$resume = '';
		$desc   = $get( 'description' );
		if ( '' !== $desc ) {
			$dx     = dom( $desc );
			$info   = texte( $dx->query( "//*[contains(@class,'template-info')]" )->item( 0 ) );
			$resume = texte( $dx->query( "//*[contains(@class,'template-resume')]" )->item( 0 ) );
		}
		$parts   = \array_map( 'trim', \explode( '|', $info ) );
		$editeur = editeur_depuis( $parts[2] ?? '' );
		$editeur = '' !== $editeur ? $editeur : editeur_depuis( $info . ' ' . $get( 'name' ) );
		$date    = '';
		try {
			$date = '' !== $get( 'pubDate' ) ? ( new \DateTimeImmutable( $get( 'pubDate' ) ) )->format( 'Y-m-d' ) : '';
		} catch ( \Exception $e ) {
			$date = '';
		}
		$t       = type_depuis_titre( $titre, 'auto' === $type ? 'autre' : $type );
		$out[]   = array(
			'source'    => 'pnrs',
			'ref'       => $m[1],
			'titre'     => $titre,
			'type'      => $t,
			'editeur'   => '' !== $editeur ? $editeur : 'ensosp-pnrs',
			'url'       => $lien,
			'pdf'       => '',
			'date'      => $date,
			'date_maj'  => '',
			'version'   => version_depuis( $titre ),
			'resume'    => resume_court( $resume ),
			'image'     => '',
			'categorie' => $parts[0] ?? '',
			'signal'    => $date,
		);
	}
	return $out;
}

/**
 * Repli HTML : notices SYRACUSE liées depuis une page PNRS.
 *
 * @param string $html HTML.
 * @param string $url  URL de la page.
 * @param string $type Type par défaut.
 * @return array<int,array<string,string>>
 */
function analyser_page_pnrs( string $html, string $url, string $type ): array {
	$x   = dom( $html );
	$out = array();
	foreach ( $x->query( "//a[contains(@href, '/SYRACUSE/')]" ) as $a ) {
		/** @var \DOMElement $a */
		$lien  = absolue( $a->getAttribute( 'href' ), $url );
		$titre = \trim( $a->getAttribute( 'title' ) );
		$titre = '' !== $titre ? $titre : texte( $a );
		if ( ! \preg_match( '#/SYRACUSE/(\d+)#', $lien, $m ) || \mb_strlen( $titre ) < 8 || isset( $out[ $m[1] ] ) ) {
			continue;
		}
		$t          = type_depuis_titre( $titre, 'auto' === $type ? 'autre' : $type );
		$out[ $m[1] ] = array(
			'source'    => 'pnrs',
			'ref'       => $m[1],
			'titre'     => $titre,
			'type'      => $t,
			'editeur'   => editeur_depuis( $titre ) ?: 'ensosp-pnrs',
			'url'       => $lien,
			'pdf'       => '',
			'date'      => '',
			'date_maj'  => '',
			'version'   => version_depuis( $titre ),
			'resume'    => '',
			'image'     => '',
			'categorie' => '',
			'signal'    => '',
		);
	}
	return \array_values( $out );
}

/**
 * Résumé public court (400 caractères au plus, phrase entière si possible).
 *
 * @param string $s Texte.
 * @return string
 */
function resume_court( string $s ): string {
	$s = \trim( (string) \preg_replace( '/\s+/u', ' ', $s ) );
	$s = (string) \preg_replace( '/^(présentation de l\'éditeur|sommaire)\s*:\s*/iu', '', $s );
	$s = \trim( $s, " \"«»" );
	if ( \mb_strlen( $s ) <= 400 ) {
		return $s;
	}
	$c = \mb_substr( $s, 0, 400 );
	$p = \mb_strrpos( $c, '. ' );
	return false !== $p && $p > 200 ? \mb_substr( $c, 0, $p + 1 ) : \rtrim( \mb_substr( $c, 0, (int) \mb_strrpos( $c, ' ' ) ), ' ,;:' ) . '…';
}

/**
 * Connecteur PNRS.
 */
class Connecteur_PNRS implements Connecteur {

	public function id(): string {
		return 'pnrs';
	}

	public function nom(): string {
		return 'PNRS · Portail numérique des ressources';
	}

	public function organisme(): string {
		return 'ensosp-pnrs';
	}

	public function url(): string {
		return 'https://pnrs.ensosp.fr/';
	}

	public function methode(): string {
		return 'Flux RSS lus sur les pages publiques, repli HTML';
	}

	public function domaines(): array {
		return array( 'pnrs.ensosp.fr' );
	}

	public function pause(): float {
		return 4.0;
	}

	public function plafond(): int {
		return 45;
	}

	public function unites( array $reglages ): array {
		$out = array();
		foreach ( pages_reglees( $reglages, pages_pnrs_defaut() ) as $url => $type ) {
			$out[] = array(
				'url'  => $url,
				'type' => $type,
			);
		}
		return $out;
	}

	public function traiter( array $unite, Http $http, array $reglages, callable $connu ): array {
		$page  = $http->get( $unite['url'], array( 'text/html' ) );
		$flux  = flux_pnrs( $page->corps, $unite['url'] );
		$identiques = 0;
		$items = array();
		$notes = array();
		$ok    = 0;
		foreach ( $flux as $f ) {
			try {
				$r = $http->get( $f, array( 'application/xml', 'application/rss+xml', 'text/xml' ), false, 2097152 );
				if ( ! $http->nouveau( $f ) ) {
					++$ok;
					++$identiques;
					continue;
				}
				$lu = analyser_flux_pnrs( $r->corps, $unite['type'] );
				$items = \array_merge( $items, $lu );
				++$ok;
			} catch ( Erreur_Acces $e ) {
				if ( \in_array( $e->categorie, array( 'limite', 'bloque' ), true ) ) {
					throw $e;
				}
				$notes[] = 'Flux ignoré (' . $e->getMessage() . ').';
			}
		}
		$methode = 'rss';
		if ( 0 === $ok ) {
			$items   = analyser_page_pnrs( $page->corps, $unite['url'], $unite['type'] );
			$methode = 'html';
			$notes[] = empty( $flux ) ? 'Aucun flux RSS sur la page : repli sur la lecture HTML.' : 'Flux RSS invalides ou expirés : repli sur la lecture HTML.';
		} else {
			$notes[] = $ok . ' flux RSS valide(s) sur ' . \count( $flux ) . ( $identiques ? ', dont ' . $identiques . ' inchangé(s) depuis le dernier cycle, non retraité(s)' : '' ) . '.';
		}
		$uniq = array();
		foreach ( $items as $it ) {
			$uniq[ $it['ref'] ] = $it;
		}
		return array(
			'items'   => \array_values( $uniq ),
			'notes'   => $notes,
			'methode' => $methode,
		);
	}
}

/* -------------------------------------------------------------------------
 * ENSOSP
 * ---------------------------------------------------------------------- */

/**
 * Mots-clés éditoriaux par défaut (actualités retenues).
 *
 * @return string[]
 */
function mots_ensosp_defaut(): array {
	return array( 'doctrine', 'formation', 'technique', 'operationnel', 'retex', 'retour d\'experience', 'innovation', 'recherche', 'management', 'commandement', 'securite civile', 'pedagog', 'ressource', 'exercice', 'simulation', 'caisson', 'feux de foret', 'secours', 'gdo', 'gto', 'pnrs' );
}

/**
 * Analyse la liste des actualités ENSOSP : titre, lien, date.
 * Repère les titres liés (h2 à h4) puis la date dans le bloc parent,
 * sans dépendre des classes CSS.
 *
 * @param string $html HTML.
 * @param string $url  URL de la liste.
 * @return array<int,array<string,string>>
 */
function analyser_liste_ensosp( string $html, string $url ): array {
	$x   = dom( $html );
	$out = array();
	foreach ( $x->query( '//main//*[self::h2 or self::h3 or self::h4]//a[@href]' ) as $a ) {
		/** @var \DOMElement $a */
		$lien  = absolue( $a->getAttribute( 'href' ), $url );
		$titre = texte( $a );
		$p     = \parse_url( $lien );
		if ( '' === $titre || ! \is_array( $p ) || 'www.ensosp.fr' !== ( $p['host'] ?? '' ) ) {
			continue;
		}
		$path = '/' . \trim( (string) ( $p['path'] ?? '' ), '/' );
		if ( '/' === $path || \substr_count( $path, '/' ) > 1 || isset( $out[ $path ] ) ) {
			continue;
		}
		$date = '';
		$node = $a;
		for ( $i = 0; $i < 6 && $node && '' === $date; $i++ ) {
			$node = $node->parentNode;
			$date = $node ? date_fr( texte( $node ) ) : '';
		}
		$out[ $path ] = array(
			'source'    => 'ensosp',
			'ref'       => $path,
			'titre'     => $titre,
			'type'      => 'actualite',
			'editeur'   => 'ensosp-pnrs',
			'url'       => 'https://www.ensosp.fr' . $path,
			'pdf'       => '',
			'date'      => $date,
			'date_maj'  => '',
			'version'   => '',
			'resume'    => '',
			'image'     => '',
			'categorie' => 'Actualités',
			'signal'    => '',
		);
	}
	return \array_values( $out );
}

/**
 * Complète une actualité avec les balises publiques de l'article.
 *
 * @param array<string,string> $it   Actualité.
 * @param string               $html HTML de l'article.
 * @return array<string,string>
 */
function completer_ensosp( array $it, string $html ): array {
	$x    = dom( $html );
	$meta = static function ( string $prop ) use ( $x ): string {
		$n = $x->query( "//meta[@property='{$prop}' or @name='{$prop}']/@content" )->item( 0 );
		return null === $n ? '' : \trim( \html_entity_decode( (string) $n->nodeValue, ENT_QUOTES, 'UTF-8' ) );
	};
	$desc           = $meta( 'og:description' );
	$it['resume']   = resume_court( '' !== $desc ? $desc : $meta( 'description' ) );
	$it['image']    = $meta( 'og:image' );
	$maj            = $meta( 'og:updated_time' );
	$it['date_maj'] = '' !== $maj ? \substr( $maj, 0, 10 ) : '';
	$it['signal']   = $it['date_maj'];
	if ( '' !== $meta( 'og:title' ) ) {
		$it['titre'] = $meta( 'og:title' );
	}
	return $it;
}

/**
 * Filtre éditorial : l'actualité touche-t-elle la ligne de Rescue 18 ?
 *
 * @param array<string,string> $it   Actualité.
 * @param string[]             $mots Mots-clés.
 * @return bool
 */
function pertinent( array $it, array $mots ): bool {
	$t = plat( $it['titre'] . ' ' . $it['resume'] );
	foreach ( $mots as $m ) {
		$m = plat( $m );
		if ( '' !== $m && \str_contains( $t, $m ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Connecteur ENSOSP.
 */
class Connecteur_ENSOSP implements Connecteur {

	public function id(): string {
		return 'ensosp';
	}

	public function nom(): string {
		return 'ENSOSP · Actualités de l\'école';
	}

	public function organisme(): string {
		return 'ensosp-pnrs';
	}

	public function url(): string {
		return 'https://www.ensosp.fr/actualites';
	}

	public function methode(): string {
		return 'Lecture HTML de la liste, balises og: des articles (aucun flux RSS)';
	}

	public function domaines(): array {
		return array( 'www.ensosp.fr' );
	}

	public function pause(): float {
		return 3.0;
	}

	public function plafond(): int {
		return 12;
	}

	public function unites( array $reglages ): array {
		$pages = \max( 1, \min( 5, (int) ( $reglages['pages'] ?? 1 ) ) );
		$out   = array();
		for ( $i = 0; $i < $pages; $i++ ) {
			$out[] = array(
				'url'  => $this->url() . ( $i > 0 ? '?page=' . $i : '' ),
				'type' => 'actualite',
			);
		}
		return $out;
	}

	public function traiter( array $unite, Http $http, array $reglages, callable $connu ): array {
		$r     = $http->get( $unite['url'], array( 'text/html' ) );
		$liste = analyser_liste_ensosp( $r->corps, $unite['url'] );
		if ( empty( $liste ) ) {
			throw new Erreur_Acces( 'structure', 'Aucune actualité lue sur ' . $unite['url'] . '. La structure de la page a peut-être changé.' );
		}
		$mots   = ! empty( $reglages['mots'] ) ? (array) $reglages['mots'] : mots_ensosp_defaut();
		$tout   = ! empty( $reglages['institutionnel'] );
		$items  = array();
		$notes  = array();
		$lus    = 0;
		$ecarts = 0;
		$reste  = false;
		foreach ( $liste as $it ) {
			if ( $connu( $it['ref'] ) ) {
				$items[] = $it;
				continue;
			}
			if ( $lus >= 6 ) {
				$reste   = true;
				$notes[] = 'Lecture des articles plafonnée à 6 par cycle : la suite au prochain cycle.';
				break;
			}
			try {
				$a  = $http->get( $it['url'], array( 'text/html' ) );
				$it = completer_ensosp( $it, $a->corps );
				++$lus;
			} catch ( Erreur_Acces $e ) {
				if ( \in_array( $e->categorie, array( 'limite', 'bloque' ), true ) ) {
					throw $e;
				}
				$notes[] = 'Article non lu : ' . $e->getMessage();
				continue;
			}
			if ( ! $tout && ! pertinent( $it, $mots ) ) {
				++$ecarts;
				$it['ecarte'] = 'hors ligne éditoriale';
			}
			$items[] = $it;
		}
		$notes[] = \count( $liste ) . ' actualité(s) listée(s), ' . $lus . ' article(s) lus, ' . $ecarts . ' écartée(s) par le filtre éditorial.';
		return array(
			'items'   => $items,
			'notes'   => $notes,
			'methode' => 'html',
			'reste'   => $reste,
		);
	}
}

/**
 * Pages réglées dans le back-office, sinon pages par défaut.
 * Format du réglage : une ligne « URL | type ».
 *
 * @param array<string,mixed>  $reglages Réglages.
 * @param array<string,string> $defaut   Pages par défaut.
 * @return array<string,string>
 */
function pages_reglees( array $reglages, array $defaut ): array {
	$txt = \trim( (string) ( $reglages['pages_txt'] ?? '' ) );
	if ( '' === $txt ) {
		return $defaut;
	}
	$out = array();
	foreach ( \preg_split( '/\R/u', $txt ) as $line ) {
		$p = \array_map( 'trim', \explode( '|', $line, 2 ) );
		if ( '' !== $p[0] && \preg_match( '#^https://#', $p[0] ) ) {
			$out[ $p[0] ] = isset( types()[ $p[1] ?? '' ] ) ? $p[1] : 'auto';
		}
	}
	return $out ?: $defaut;
}

/**
 * Connecteurs disponibles, dans l'ordre de priorité du cahier des charges.
 *
 * @return array<string,Connecteur>
 */
function connecteurs(): array {
	static $c = null;
	if ( null === $c ) {
		$c = array(
			'dgscgc' => new Connecteur_DGSCGC(),
			'pnrs'   => new Connecteur_PNRS(),
			'ensosp' => new Connecteur_ENSOSP(),
		);
		if ( \function_exists( 'apply_filters' ) ) {
			$c = (array) \apply_filters( 'r18_veille_connecteurs', $c );
		}
	}
	return $c;
}
