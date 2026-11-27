<?php
/**
 * Veille documentaire : normalisation commune aux connecteurs.
 *
 * Une « référence » collectée est un tableau :
 *   source      dgscgc|pnrs|ensosp
 *   ref         identifiant stable chez la source (n° SYRACUSE, chemin…)
 *   titre       titre exact publié
 *   type        gdo|gto|pio|memento|vademecum|referentiel|retex|pex|etude|memoire|actualite|autre
 *   editeur     organisme éditeur (dgscgc|ensosp-pnrs|…)
 *   url         URL de la fiche officielle
 *   pdf         URL du PDF (lien seulement, jamais recopié)
 *   date        date officielle AAAA-MM-JJ si connue
 *   date_maj    date de mise à jour AAAA-MM-JJ si connue
 *   version     version explicite (V2, 1.1, édition 2025…)
 *   resume      résumé public court
 *   image       image de présentation (non téléchargée par défaut)
 *   categorie   rubrique de la source
 *   signal      valeur dont le changement signifie une nouvelle version
 *
 * Fonctions pures : aucune dépendance à WordPress, testées dans tests/.
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18\Veille;

/**
 * Types documentaires suivis : slug => libellé.
 *
 * @return array<string,string>
 */
function types(): array {
	return array(
		'gdo'         => 'GDO',
		'gto'         => 'GTO',
		'gnr'         => 'GNR',
		'pio'         => 'PIO',
		'memento'     => 'Mémentos',
		'vademecum'   => 'Vadémécums',
		'referentiel' => 'Référentiels',
		'retex'       => 'RETEX / PEX',
		'pex'         => 'PEX',
		'etude'       => 'Études et rapports',
		'memoire'     => 'Mémoires',
	);
}

/**
 * Termes r18_type_doc à poser pour un type (les PEX apparaissent aussi
 * dans la rubrique RETEX / PEX historique, les vadémécums dans GNR).
 *
 * @param string $type Type normalisé.
 * @return string[]
 */
function termes_type( string $type ): array {
	$map = array(
		'pex'       => array( 'retex', 'pex' ),
		'vademecum' => array( 'gnr', 'vademecum' ),
	);
	return $map[ $type ] ?? ( isset( types()[ $type ] ) ? array( $type ) : array() );
}

/**
 * Minuscules sans accents.
 *
 * @param string $s Texte.
 * @return string
 */
function plat( string $s ): string {
	$s = \html_entity_decode( $s, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$s = \mb_strtolower( $s, 'UTF-8' );
	$s = \strtr(
		$s,
		array(
			'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ç' => 'c', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
			'î' => 'i', 'ï' => 'i', 'í' => 'i', 'ô' => 'o', 'ö' => 'o', 'ó' => 'o', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u',
			'ÿ' => 'y', 'œ' => 'oe', 'æ' => 'ae', '’' => "'", '‘' => "'", '–' => '-', '—' => '-', "\u{FEFF}" => '', "\u{00A0}" => ' ',
		)
	);
	return \trim( (string) \preg_replace( '/\s+/u', ' ', $s ) );
}

/**
 * Type documentaire déduit d'un titre.
 *
 * @param string $titre  Titre.
 * @param string $defaut Type de repli (rubrique de la page source).
 * @return string
 */
function type_depuis_titre( string $titre, string $defaut = 'autre' ): string {
	$t = plat( $titre );
	$regles = array(
		'gdo'         => '/\(gdo\)|^gdo\b|guide de doctrine/',
		'gto'         => '/\(gto\)|^gto\b|guide de te\w*niques? op/',
		'gnr'         => '/\bgnr\b|guide national de reference/',
		'pio'         => '/\bpio\b|partage d.information operationnelle/',
		'memento'     => '/\bmemento\b/',
		'vademecum'   => '/\bvademecum\b|vade-mecum/',
		'pex'         => '/\bpias\b|partage d.experience|\bpex\b/',
		'retex'       => '/\bretex\b|retour d.experience|retours d.experience/',
		'referentiel' => '/referentiel|\breac\b|\bremc\b/',
		'memoire'     => '/\bmemoire\b/',
		'etude'       => '/\betude\b|\brapport\b|these/',
	);
	foreach ( $regles as $type => $re ) {
		if ( \preg_match( $re, $t ) ) {
			return $type;
		}
	}
	return $defaut;
}

/**
 * Clé de rapprochement entre sources : type + intitulé réduit.
 * « GDO - Interventions en présence de gaz » (DGSCGC) et
 * « Guide de doctrine opérationnelle (GDO) : interventions en présence de gaz »
 * (PNRS) donnent la même clé.
 *
 * @param string $titre Titre.
 * @param string $type  Type normalisé.
 * @return string
 */
function cle( string $titre, string $type ): string {
	$t = plat( $titre );
	$t = (string) \preg_replace( '/^guide (de|national de) [^:(]*?(\((gdo|gto|gnr)\))?\s*[:,\-]\s*/', '', $t );
	$t = (string) \preg_replace( '/^(guide (de doctrine operationnelle|de te\w*niques? operationnelles?))\s*(\((gdo|gto)\))?\s*/', '', $t );
	$t = (string) \preg_replace( '/^(gdo|gto|gnr|pio|memento|vademecum)\s*[:\-–]?\s*/', '', $t );
	$t = (string) \preg_replace( '/\b(v|version)\s*\d+(\.\d+)?\b|\b(19|20)\d{2}\b|\bsigne\b/', ' ', $t );
	$t = (string) \preg_replace( "/[^a-z0-9]+/", ' ', $t );
	$stop = array( 'de', 'des', 'du', 'la', 'le', 'les', 'l', 'd', 'en', 'a', 'au', 'aux', 'et', 'sur', 'dans', 'pour', 'par', 'type', 'un', 'une', 'ou' );
	$mots = \array_values( \array_filter( \explode( ' ', $t ), static fn( string $m ): bool => '' !== $m && ! \in_array( $m, $stop, true ) ) );
	return $type . '|' . \implode( ' ', $mots );
}

/**
 * Ressemblance entre deux clés de même type, de 0 à 100.
 *
 * @param string $a Clé.
 * @param string $b Clé.
 * @return float
 */
function ressemblance( string $a, string $b ): float {
	[ $ta, $ma ] = \array_pad( \explode( '|', $a, 2 ), 2, '' );
	[ $tb, $mb ] = \array_pad( \explode( '|', $b, 2 ), 2, '' );
	if ( $ta !== $tb || '' === $ma || '' === $mb ) {
		return 0.0;
	}
	$wa    = \array_unique( \explode( ' ', $ma ) );
	$wb    = \array_unique( \explode( ' ', $mb ) );
	$inter = \count( \array_intersect( $wa, $wb ) );
	$union = \count( \array_unique( \array_merge( $wa, $wb ) ) );
	$jacc  = $union > 0 ? 100 * $inter / $union : 0;
	\similar_text( $ma, $mb, $pct );
	return \round( \max( $jacc, (float) $pct ), 1 );
}

/**
 * Organisme éditeur d'après un libellé libre.
 *
 * @param string $texte Libellé (ligne d'information du PNRS, auteur…).
 * @return string Slug r18_organisme ou vide.
 */
function editeur_depuis( string $texte ): string {
	$t = plat( $texte );
	if ( \preg_match( '/securite civile et de la gestion des crises|\bdgscgc\b|direction generale de la securite civile/', $t ) ) {
		return 'dgscgc';
	}
	if ( \preg_match( '/\bensosp\b|ecole nationale superieure des officiers/', $t ) ) {
		return 'ensosp-pnrs';
	}
	return '';
}

/**
 * Version explicite dans un titre ou un nom de fichier.
 *
 * @param string $texte Titre ou URL.
 * @return string
 */
function version_depuis( string $texte ): string {
	$t = plat( \rawurldecode( $texte ) );
	if ( \preg_match( '/(?:^|[^a-z])v(?:ersion)?\s*(\d+(?:[.,]\d+)?)(?![a-z0-9])/', $t, $m ) ) {
		return 'V' . \str_replace( ',', '.', $m[1] );
	}
	if ( \preg_match( '/(?:edition|ed\.)\s*((?:19|20)\d{2})/', $t, $m ) ) {
		return 'Édition ' . $m[1];
	}
	return '';
}

/**
 * Date française « 27 octobre 2026 » ou « 30/07/2026 » vers AAAA-MM-JJ.
 *
 * @param string $texte Texte.
 * @return string
 */
function date_fr( string $texte ): string {
	$t = plat( $texte );
	if ( \preg_match( '/\b(\d{1,2})\/(\d{1,2})\/((?:19|20)\d{2})\b/', $t, $m ) ) {
		return \sprintf( '%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1] );
	}
	$mois = array( 'janvier' => 1, 'fevrier' => 2, 'mars' => 3, 'avril' => 4, 'mai' => 5, 'juin' => 6, 'juillet' => 7, 'aout' => 8, 'septembre' => 9, 'octobre' => 10, 'novembre' => 11, 'decembre' => 12 );
	if ( \preg_match( '/\b(\d{1,2})(?:er)?\s+(' . \implode( '|', \array_keys( $mois ) ) . ')\s+((?:19|20)\d{2})\b/', $t, $m ) ) {
		return \sprintf( '%04d-%02d-%02d', (int) $m[3], $mois[ $m[2] ], (int) $m[1] );
	}
	if ( \preg_match( '/^((?:19|20)\d{2})-(\d{2})-(\d{2})/', $t, $m ) ) {
		return $m[1] . '-' . $m[2] . '-' . $m[3];
	}
	return '';
}

/**
 * Empreinte des données qui définissent une version : titre réduit, fichier,
 * version explicite, signal propre à la source. La date de la page de liste
 * n'y entre jamais : la republier ne crée pas de version.
 *
 * @param array<string,mixed> $r Référence normalisée.
 * @return string
 */
function empreinte( array $r ): string {
	return \hash(
		'sha256',
		\implode(
			"\n",
			array(
				cle( (string) ( $r['titre'] ?? '' ), (string) ( $r['type'] ?? '' ) ),
				(string) ( $r['pdf'] ?? '' ),
				(string) ( $r['version'] ?? '' ),
				(string) ( $r['signal'] ?? '' ),
			)
		)
	);
}

/**
 * Charge du HTML dans un DOMXPath (UTF-8, erreurs silencieuses).
 *
 * @param string $html HTML.
 * @return \DOMXPath
 */
function dom( string $html ): \DOMXPath {
	$doc = new \DOMDocument();
	\libxml_use_internal_errors( true );
	$doc->loadHTML( '<?xml encoding="utf-8"?>' . $html, LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR );
	\libxml_clear_errors();
	return new \DOMXPath( $doc );
}

/**
 * Texte d'un nœud, espaces réduits.
 *
 * @param \DOMNode|null $n Nœud.
 * @return string
 */
function texte( ?\DOMNode $n ): string {
	return null === $n ? '' : \trim( (string) \preg_replace( '/[\s\x{FEFF}\x{00A0}]+/u', ' ', $n->textContent ) );
}

/**
 * URL absolue à partir d'une base.
 *
 * @param string $href Lien.
 * @param string $base URL de la page.
 * @return string
 */
function absolue( string $href, string $base ): string {
	$href = \trim( \html_entity_decode( $href, ENT_QUOTES, 'UTF-8' ) );
	if ( '' === $href || \preg_match( '#^(mailto|javascript|tel):#i', $href ) ) {
		return '';
	}
	if ( \preg_match( '#^https?://#i', $href ) ) {
		return $href;
	}
	$p = \parse_url( $base );
	if ( ! \is_array( $p ) || empty( $p['host'] ) ) {
		return '';
	}
	$root = ( $p['scheme'] ?? 'https' ) . '://' . $p['host'];
	if ( \str_starts_with( $href, '//' ) ) {
		return ( $p['scheme'] ?? 'https' ) . ':' . $href;
	}
	if ( \str_starts_with( $href, '/' ) ) {
		return $root . $href;
	}
	$dir = \preg_replace( '#/[^/]*$#', '/', (string) ( $p['path'] ?? '/' ) );
	return $root . $dir . $href;
}
