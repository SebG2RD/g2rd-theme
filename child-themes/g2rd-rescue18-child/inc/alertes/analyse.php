<?php
/**
 * Alertes d'interventions : lecture des articles et regroupement (sans WordPress).
 *
 * Un article de SDIS ou de presse devient un « signal » s'il décrit une grosse
 * intervention : moyens importants, feu d'espace naturel étendu, plan rouge,
 * colonnes de renfort… Les signaux qui parlent du même événement (même commune
 * ou moins de 10 km, même famille, même période) forment une seule alerte.
 * Une alerte vit le temps de l'intervention : elle disparaît quand une source
 * annonce le feu fixé, éteint ou maîtrisé, ou faute de nouvelle publication.
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18\Alertes;

use function G2RD_Rescue18\Veille\plat;

const UN_JOUR = 86400;

/**
 * Natures d'intervention : libellé, durée de vie en heures sans nouvelle publication.
 *
 * @return array<string,array{0:string,1:int}>
 */
function natures(): array {
	return array(
		'feu_foret'       => array( 'Feu d’espace naturel', 24 ),
		'incendie_indus'  => array( 'Incendie industriel', 6 ),
		'incendie'        => array( 'Incendie', 6 ),
		'explosion'       => array( 'Explosion', 6 ),
		'risque_chimique' => array( 'Risque chimique', 6 ),
		'effondrement'    => array( 'Effondrement', 6 ),
		'intemperies'     => array( 'Intempéries', 6 ),
		'accident'        => array( 'Accident', 6 ),
		'sauvetage'       => array( 'Sauvetage', 6 ),
		'satellite'       => array( 'Feu repéré par satellite', 12 ),
		'autre'           => array( 'Intervention', 6 ),
	);
}

/**
 * Durée de vie d'une alerte (secondes) sans nouvelle publication.
 *
 * @param string $nature Nature.
 * @return int
 */
function ttl( string $nature ): int {
	return ( natures()[ $nature ][1] ?? 6 ) * 3600;
}

/**
 * Famille de rapprochement : les feux se rejoignent entre eux.
 *
 * @param string $nature Nature.
 * @return string
 */
function famille( string $nature ): string {
	return \in_array( $nature, array( 'feu_foret', 'incendie', 'incendie_indus', 'satellite', 'explosion' ), true ) ? 'feu' : $nature;
}

/**
 * Nature déduite d'un texte aplati.
 *
 * @param string $p Texte passé par plat().
 * @return string
 */
function nature( string $p ): string {
	$motifs = array(
		'feu_foret'       => '/\bfeux? (de |d\'|d\'espaces? )?(foret|vegetation|broussailles?|garrigue|maquis|espaces? naturels?|pinede|landes?|chaumes?|recoltes?|champs?|sous-bois)|\bincendies? (de |d\')?(foret|vegetation|espaces? naturels?|garrigue|broussailles?)|\bhectares? (de vegetation |de foret |de garrigue )?(ont )?(brule|parcouru|detruit)|\bcanadairs?\b|\bbombardiers? d\'eau/',
		'explosion'       => '/\bexplosions?\b|\bexplose\b|\bdeflagration/',
		'risque_chimique' => '/fuite de (gaz|produit|ammoniac|chlore|matiere)|nuage toxique|produits? (chimiques?|dangereux)|\bnrbc\b|pollution|hydrocarbures?|matieres? dangereuses|\bseveso\b|risque chimique/',
		'incendie_indus'  => '/(incendie|feu)s? (d\'un |dans un |d\'une |dans une |sur un |sur une |sur le site d\'une? |a l\')?(usine|entrepot|site industriel|zone industrielle|batiment industriel|hangar|exploitation agricole|entreprise|centre de tri|dechetterie|silo|parc de stockage|raffinerie)/',
		'effondrement'    => '/\beffondrement|\bs\'est effondre|\beboulement|glissement de terrain/',
		'intemperies'     => '/\binondations?\b|\bcrues?\b|\btempete\b|\bintemperies\b|orages? violents?|\bsubmersion|coulees? de boue/',
		'accident'        => '/\baccident|\bcollision|\bcarambolage|\bderaillement|\bpercute|sortie de route|\bcrash\b/',
		'incendie'        => '/\bincendies?\b|\bfeux?\b|\bflammes\b|\bembrase/',
		'sauvetage'       => '/\bsauvetage|\bnoyade|secours en montagne|\bavalanche|\bdisparus?\b/',
	);
	foreach ( $motifs as $k => $re ) {
		if ( \preg_match( $re, $p ) ) {
			return $k;
		}
	}
	return 'autre';
}

/**
 * Retire les noms d'organismes qui contiennent « incendie » ou « secours ».
 *
 * @param string $p Texte aplati.
 * @return string
 */
function nettoyer( string $p ): string {
	return \trim( (string) \preg_replace( '/\b(?:services?|centres?|groupements?|sdis|sdmis)?\s*(?:departementa(?:l|ux)|metropolitain|territorial)?\s*d\'incendie et de secours\b|\bcentres? d\'incendie et de secours\b|\bsapeurs-pompiers volontaires\b/', ' ', $p ) );
}

/**
 * Article sans rapport avec une intervention en cours (cérémonie, bilan, procès…).
 *
 * @param string $p Texte aplati.
 * @return bool
 */
function hors_sujet( string $p ): bool {
	return (bool) \preg_match( '/\bexercices?\b|\bmanoeuvres?\b|\bceremonie|sainte[- ]barbe|\brecrut|portes? ouvertes|\bbilan (annuel|de l\'annee|de la saison|de l\'ete|des feux|\d{4})|il y a \d+ ans|\banniversaire|\bhommage|\bcommemor|\bproces\b|\btribunal|\bcondamne|journee nationale|\bprevention\b|\bconseils?\b|\bsensibilisation|\bformation\b|\binauguration|nouvelle caserne|\bmedailles?\b|\bcalendriers?\b|bal des pompiers|\btelethon|\bstage\b|\bconcours\b|\(e\)|\bh\/f\b|offres? d\'emploi|\bposte\b|\bcandidat|\bchampion|\btrail\b|\bcourse\b|\bsportif|\bcompetition|\bchallenge\b|\bcross\b|\bpartenariat|\bconvention\b|\bvisite\b/', $p );
}

/**
 * Nombres écrits en lettres ou approximatifs.
 *
 * @return array<string,int>
 */
function approx(): array {
	return array(
		'plusieurs centaines' => 200,
		'plusieurs dizaines'  => 30,
		'un millier'          => 1000,
		'une centaine'        => 100,
		'une soixantaine'     => 60,
		'une cinquantaine'    => 50,
		'une quarantaine'     => 40,
		'une trentaine'       => 30,
		'une vingtaine'       => 20,
		'une quinzaine'       => 15,
		'une dizaine'         => 10,
		'vingt'               => 20,
		'quinze'              => 15,
		'douze'               => 12,
		'dix'                 => 10,
		'neuf'                => 9,
		'huit'                => 8,
		'sept'                => 7,
		'six'                 => 6,
		'cinq'                => 5,
		'quatre'              => 4,
		'trois'               => 3,
		'deux'                => 2,
	);
}

/**
 * Plus grand nombre placé devant l'un des noms donnés.
 *
 * @param string $p     Texte aplati.
 * @param string $noms  Alternative regex des noms comptés.
 * @return int
 */
function compter( string $p, string $noms ): int {
	$mots = \implode( '|', \array_map( static fn( string $m ): string => \preg_quote( $m, '/' ), \array_keys( approx() ) ) );
	$re   = '/(?<![\d,.])(\d{1,3}(?: \d{3})+|\d+|\b(?:' . $mots . '))\s+(?:de |d\')?(?:' . $noms . ')\b/';
	if ( ! \preg_match_all( $re, $p, $m ) ) {
		return 0;
	}
	$max = 0;
	foreach ( $m[1] as $n ) {
		$v   = approx()[ $n ] ?? (int) \str_replace( ' ', '', $n );
		$max = \max( $max, $v );
	}
	return $max;
}

/**
 * Moyens et ampleur cités dans un texte aplati.
 *
 * @param string $p Texte aplati.
 * @return array{pompiers:int,engins:int,aeriens:int,hectares:float,morts:int,blesses:int,evacues:int}
 */
function moyens( string $p ): array {
	$aeriens = compter( $p, 'canadairs?|bombardiers? d\'eau|dash|avions? bombardiers?|helicopteres? bombardiers?|moyens aeriens|aeronefs?|air tractors?' );
	if ( 0 === $aeriens && \preg_match( '/\bcanadairs?\b|bombardiers? d\'eau|\bdash\b|moyens aeriens|helicopteres? bombardiers?|\blargages?\b|air tractors?/', $p ) ) {
		$aeriens = 1;
	}
	$ha = 0.0;
	if ( \preg_match_all( '/(\d{1,3}(?: \d{3})+|\d+(?:[,.]\d+)?)\s*(?:hectares?|ha)\b/', $p, $m ) ) {
		foreach ( $m[1] as $v ) {
			$ha = \max( $ha, (float) \str_replace( array( ' ', ',' ), array( '', '.' ), $v ) );
		}
	}
	return array(
		'pompiers' => compter( $p, 'sapeurs?[- ]pompiers|pompiers|marins[- ]pompiers|soldats du feu|hommes du feu|militaires de la securite civile|sauveteurs|secouristes' ),
		'engins'   => compter( $p, 'engins(?: de secours| de lutte| d\'incendie)?|camions(?: de pompiers| citernes?)?|vehicules (?:de secours|d\'intervention|de lutte|incendie)|fourgons|ccf' ),
		'aeriens'  => $aeriens,
		'hectares' => $ha,
		'morts'    => compter( $p, 'morts|personnes decedees|deces|victimes decedees|tues' ),
		'blesses'  => compter( $p, 'blesses|personnes blessees|victimes' ),
		'evacues'  => \max( compter( $p, '(?:personnes|habitants|residents|riverains|eleves|salaries) (?:ont ete |sont |ont du etre )?evacue(?:es|s|e)?' ), compter( $p, 'personnes evacuees|habitants evacues' ) ),
	);
}

/**
 * Grosse intervention ?
 *
 * @param string                $p      Texte aplati.
 * @param string                $nature Nature.
 * @param array<string,int|float> $m    Moyens.
 * @return bool
 */
function grosse( string $p, string $nature, array $m ): bool {
	if ( $m['pompiers'] >= 40 || $m['engins'] >= 15 || $m['hectares'] >= 10 || $m['morts'] >= 2 || $m['blesses'] >= 8 || $m['evacues'] >= 50 ) {
		return true;
	}
	if ( $m['aeriens'] > 0 && ( 'feu_foret' === $nature || $m['aeriens'] >= 2 ) ) {
		return true;
	}
	if ( \preg_match( '/plan rouge|nombreuses victimes|\bnovi\b|colonnes? de renforts?|renforts? (?:de|des|venus de) (?:plusieurs|departements|sdis voisins|toute la region)|plan particulier d\'intervention|\bppi\b|cellule de crise|confinement de la population|\bseveso\b|plan orsec/', $p ) ) {
		return true;
	}
	$ampleur = (bool) \preg_match( '/violent incendie|important incendie|gigantesque|spectaculaire|grande ampleur|vaste incendie|incendie (?:geant|majeur|hors norme)|panache de fumee/', $p );
	return $ampleur && ( $m['pompiers'] >= 25 || $m['engins'] >= 8 || \in_array( $nature, array( 'feu_foret', 'incendie_indus', 'explosion', 'risque_chimique' ), true ) );
}

/**
 * Le texte annonce-t-il la fin de l'intervention ?
 *
 * @param string $p Texte aplati.
 * @return bool
 */
function termine( string $p ): bool {
	if ( ! \preg_match_all( '/\b(?:fixes?|fixees?|eteints?|eteintes?|maitrises?|maitrisees?|circonscrits?|circonscrites?|sous controle|fin de l\'intervention|fin d\'intervention|levee? (?:du|de la) (?:confinement|alerte))\b/', $p, $m, PREG_OFFSET_CAPTURE ) ) {
		return false;
	}
	foreach ( $m[0] as [ , $pos ] ) {
		$avant = \substr( $p, \max( 0, $pos - 40 ), \min( 40, $pos ) );
		if ( ! \preg_match( '/\b(?:pas|non|toujours pas|pas encore|n\'est|ne sont|n\'a|en passe|en voie|sur le point|avant d\'etre|loin d\'etre)\b[^.]*$/', $avant ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Communes candidates citées dans un titre.
 *
 * @param string $t Titre (casse d'origine).
 * @return string[]
 */
function lieux( string $t ): array {
	$t   = \str_replace( array( '’', "\u{00A0}" ), array( "'", ' ' ), \html_entity_decode( $t, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	$mot = "\p{Lu}[\p{L}']*";
	$lie = "(?:sur|sous|en|de|du|des|la|le|les|lès|lez|aux|au|et|d'|l')";
	$nom = "((?:(?:Le|La|Les) |L')?" . $mot . "(?:(?:-| )(?:" . $lie . "(?:-| )?)*" . $mot . ')*)';
	$out = array();
	if ( \preg_match( '/^\s*' . $nom . '\s*(?:\((?:\d{2,3}|2A|2B)\))?\s*(?::|,| -)\s/u', $t, $m ) ) {
		$out[] = $m[1];
	}
	if ( \preg_match_all( "/(?:\bà|\bau|\baux|\bprès d'|\bprès de|\bcommune d'|\bcommune de|\bentre|\bdepuis)\s*" . $nom . '/u', $t, $mm, PREG_SET_ORDER ) ) {
		foreach ( $mm as $x ) {
			$prep = \trim( (string) \preg_replace( "/\s*" . \preg_quote( $x[1], '/' ) . '$/u', '', $x[0] ) );
			$n    = $x[1];
			if ( 'au' === $prep && ! \preg_match( '/^(Le|La|Les|L\') /u', $n ) ) {
				$n = 'Le ' . $n;
			} elseif ( 'aux' === $prep && ! \preg_match( '/^Les /u', $n ) ) {
				$n = 'Les ' . $n;
			}
			$out[] = $n;
		}
	}
	$stop = '/^(?:l[ae]s? )?(?:incendie|feu|feux|accident|explosion|alerte|video|videos|direct|photos?|info|infos|urgent|pompiers?|sapeurs?|sdis|bspp|bmpm|intervention|faits? divers|meteo|vigilance|france|europe|police|gendarmerie|samu|smur|prefecture|prefet|canadair|canadairs|dash|lundi|mardi|mercredi|jeudi|vendredi|samedi|dimanche|janvier|fevrier|mars|avril|mai|juin|juillet|aout|septembre|octobre|novembre|decembre|noel|toussaint|halloween|a\d+|n\d+|rn\d+|d\d+)$/';
	$out  = \array_values( \array_unique( \array_filter( \array_map( 'trim', $out ), static fn( string $n ): bool => \mb_strlen( $n ) >= 3 && ! \preg_match( $stop, plat( $n ) ) && ! \preg_match( $stop, (string) \strtok( (string) \preg_replace( '/^(?:l[ae]s? |l\')/', '', plat( $n ) ), ' -' ) ) && ! \preg_match( '/^(?:A|N|RN|D)\d+/', $n ) ) ) );
	return \array_slice( $out, 0, 4 );
}

/**
 * Département cité : « (13) » ou « dans le Var », « en Gironde ».
 *
 * @param string                              $t    Texte (casse d'origine).
 * @param array<string,array{0:string,1:string}> $deps Départements code => [nom, région].
 * @return string
 */
function dep_cite( string $t, array $deps ): string {
	if ( \preg_match( '/\((\d{2,3}|2A|2B)\)/i', $t, $m ) && isset( $deps[ \strtoupper( $m[1] ) ] ) ) {
		return \strtoupper( $m[1] );
	}
	$p = ' ' . plat( $t ) . ' ';
	foreach ( $deps as $code => [ $nom ] ) {
		$n = plat( (string) $nom );
		if ( \preg_match( '/\b(?:dans l[ae]s?|dans l\'|en|du|de la|des|dans le departement d[eu]s?|l\')\s?' . \preg_quote( $n, '/' ) . '\b/', $p ) ) {
			return (string) $code;
		}
	}
	return '';
}

/**
 * Mots significatifs d'un titre, pour comparer deux articles.
 *
 * @param string $t Titre.
 * @return string[]
 */
function mots( string $t ): array {
	$vides = array( 'dans', 'avec', 'pour', 'sans', 'sous', 'vers', 'apres', 'avant', 'depuis', 'pendant', 'entre', 'leur', 'leurs', 'cette', 'sont', 'plus', 'tres', 'tout', 'tous', 'toute', 'encore', 'deja', 'alors', 'ainsi', 'pompiers', 'sapeurs', 'sapeurs-pompiers', 'intervention', 'video', 'direct', 'photos', 'info', 'heures', 'hier', 'matin', 'soir', 'nuit', 'journee' );
	$m     = \preg_split( '/[^a-z0-9-]+/', plat( $t ) ) ?: array();
	return \array_values( \array_unique( \array_filter( $m, static fn( string $w ): bool => \strlen( $w ) >= 4 && ! \in_array( $w, $vides, true ) && ! \ctype_digit( $w ) ) ) );
}

/**
 * Indice de Jaccard entre deux listes de mots.
 *
 * @param string[] $a Mots.
 * @param string[] $b Mots.
 * @return float
 */
function similarite( array $a, array $b ): float {
	if ( ! $a || ! $b ) {
		return 0.0;
	}
	$i = \count( \array_intersect( $a, $b ) );
	$u = \count( \array_unique( \array_merge( $a, $b ) ) );
	return $u > 0 ? $i / $u : 0.0;
}

/**
 * Distance en kilomètres.
 *
 * @param float $la1 Latitude.
 * @param float $lo1 Longitude.
 * @param float $la2 Latitude.
 * @param float $lo2 Longitude.
 * @return float
 */
function km( float $la1, float $lo1, float $la2, float $lo2 ): float {
	$r = M_PI / 180;
	$a = \sin( ( $la2 - $la1 ) * $r / 2 ) ** 2 + \cos( $la1 * $r ) * \cos( $la2 * $r ) * \sin( ( $lo2 - $lo1 ) * $r / 2 ) ** 2;
	return 12742 * \asin( \min( 1.0, \sqrt( $a ) ) );
}

/**
 * Article vers signal, ou null s'il ne décrit pas une grosse intervention récente.
 *
 * @param array<string,string> $a    Article : titre, url, date (UTC), resume.
 * @param string               $type sdis|presse.
 * @param string               $nom  Nom de la source affiché.
 * @param string               $dep  Département connu de la source.
 * @param int                  $now  Horodatage courant.
 * @param array<string,array{0:string,1:string}> $deps Départements.
 * @return array<string,mixed>|null
 */
function signal( array $a, string $type, string $nom, string $dep, int $now, array $deps = array() ): ?array {
	$titre = \trim( (string) ( $a['titre'] ?? '' ) );
	$texte = $titre . '. ' . (string) ( $a['resume'] ?? '' );
	$p     = nettoyer( plat( $texte ) );
	$pt    = nettoyer( plat( $titre ) );
	if ( '' === $titre || hors_sujet( $pt ) ) {
		return null;
	}
	// Le titre doit annoncer un événement : sinon un article sur le SDIS (offre d'emploi, portrait) passerait.
	if ( 'autre' === nature( $pt ) && ! \preg_match( '/\binterventions?\b|\bengages?\b|\bmobilis|\brenforts?\b|\bevacu|\bsinistre|\bvictimes?\b|\bblesses?\b|\bsecours\b/', $pt ) ) {
		return null;
	}
	$date = \strtotime( (string) ( $a['date'] ?? '' ) . ' UTC' );
	$date = false === $date ? $now : \min( $now, $date );
	$nat  = nature( $p );
	$m    = moyens( $p );
	$fin  = termine( $p );
	if ( ! grosse( $p, $nat, $m ) || $now - $date > ttl( $nat ) ) {
		return null;
	}
	$lieux = lieux( $titre );
	if ( ! $lieux ) {
		$premiere = (string) \preg_replace( '/[.!?].*$/su', '', (string) ( $a['resume'] ?? '' ) );
		$lieux    = lieux( $premiere );
	}
	$cite = $deps ? dep_cite( $texte, $deps ) : '';
	return array(
		'type'   => $type,
		'source' => $nom,
		'url'    => (string) ( $a['url'] ?? '' ),
		'titre'  => $titre,
		'date'   => $date,
		'nature' => $nat,
		'moyens' => $m,
		'fin'    => $fin,
		'lieux'  => $lieux,
		'dep'    => '' !== $cite ? $cite : $dep,
		'mots'   => mots( $titre ),
	);
}

/**
 * Alerte existante décrivant le même événement, ou ''.
 *
 * @param array<string,array<string,mixed>> $alertes Alertes.
 * @param array<string,mixed>               $s       Signal géolocalisé (insee, lat, lng éventuels).
 * @return string
 */
function rattacher( array $alertes, array $s ): string {
	$meilleur = '';
	$score    = 69;
	foreach ( $alertes as $id => $a ) {
		foreach ( (array) $a['sources'] as $src ) {
			if ( $src['url'] === $s['url'] ) {
				return (string) $id;
			}
		}
		if ( \abs( (int) $s['date'] - (int) $a['maj'] ) > ttl( (string) $a['nature'] ) + 6 * 3600 ) {
			continue;
		}
		$nat  = famille( (string) $s['nature'] ) === famille( (string) $a['nature'] ) || 'autre' === $s['nature'] || 'autre' === $a['nature'];
		$lieu = ( '' !== (string) ( $s['insee'] ?? '' ) && $s['insee'] === $a['insee'] )
			|| ( ! empty( $s['lat'] ) && ! empty( $a['lat'] ) && km( (float) $s['lat'], (float) $s['lng'], (float) $a['lat'], (float) $a['lng'] ) < 10 );
		$sim  = similarite( (array) $s['mots'], (array) $a['mots'] );
		$pts  = 20 + ( $nat ? 30 : 0 ) + ( $lieu ? 40 : 0 ) + ( $sim >= 0.3 ? 10 : 0 );
		if ( ! $lieu && '' === (string) ( $s['insee'] ?? '' ) && empty( $s['lat'] ) && '' !== (string) $s['dep'] && $s['dep'] === $a['dep'] && $sim >= 0.5 ) {
			$pts += 40;
		}
		if ( $pts > $score ) {
			$score    = $pts;
			$meilleur = (string) $id;
		}
	}
	return $meilleur;
}

/**
 * Ordre d'affichage des sources : SDIS, presse, satellites.
 *
 * @param string $type Type.
 * @return int
 */
function rang( string $type ): int {
	return array( 'sdis' => 0, 'presse' => 1, 'firms' => 2 )[ $type ] ?? 3;
}

/**
 * Nouvelle alerte depuis un signal.
 *
 * @param array<string,mixed> $s Signal géolocalisé.
 * @return array<string,mixed>
 */
function nouvelle( array $s ): array {
	$a = array(
		'id'       => \substr( \hash( 'sha1', (string) $s['url'] ), 0, 12 ),
		'nature'   => $s['nature'],
		'titre'    => '',
		'titre_type' => '',
		'commune'  => (string) ( $s['commune'] ?? '' ),
		'insee'    => (string) ( $s['insee'] ?? '' ),
		'dep'      => (string) ( $s['dep'] ?? '' ),
		'lat'      => (float) ( $s['lat'] ?? 0 ),
		'lng'      => (float) ( $s['lng'] ?? 0 ),
		'debut'    => (int) $s['date'],
		'maj'      => (int) $s['date'],
		'fin'      => false,
		'fin_t'    => 0,
		'masque'   => false,
		'moyens'   => array(),
		'moyens_t' => array(),
		'sources'  => array(),
		'mots'     => array(),
	);
	return fusionner( $a, $s );
}

/**
 * Ajoute un signal à une alerte : sources, moyens les plus récents, fin.
 *
 * @param array<string,mixed> $a Alerte.
 * @param array<string,mixed> $s Signal.
 * @return array<string,mixed>
 */
function fusionner( array $a, array $s ): array {
	$t     = (int) $s['date'];
	$connu = false;
	foreach ( $a['sources'] as &$src ) {
		if ( $src['url'] === $s['url'] ) {
			$src['date'] = \max( (int) $src['date'], $t );
			$connu       = true;
		}
	}
	unset( $src );
	if ( ! $connu ) {
		$a['sources'][] = array(
			'type'  => $s['type'],
			'nom'   => $s['source'],
			'url'   => $s['url'],
			'titre' => $s['titre'],
			'date'  => $t,
		);
	}
	\usort( $a['sources'], static fn( array $x, array $y ): int => rang( (string) $x['type'] ) <=> rang( (string) $y['type'] ) ?: (int) $x['date'] <=> (int) $y['date'] );
	$a['sources'] = \array_slice( $a['sources'], 0, 8 );
	$a['maj']     = \max( (int) $a['maj'], $t );
	$a['debut']   = \min( (int) $a['debut'], $t );
	if ( ! empty( $s['fin'] ) ) {
		$a['fin']   = true;
		$a['fin_t'] = \max( (int) $a['fin_t'], $t );
	} elseif ( $t > (int) $a['fin_t'] ) {
		$a['fin'] = false;
	}
	foreach ( (array) $s['moyens'] as $k => $v ) {
		if ( $v > 0 && $t >= (int) ( $a['moyens_t'][ $k ] ?? 0 ) ) {
			$a['moyens'][ $k ]   = $v;
			$a['moyens_t'][ $k ] = $t;
		}
	}
	$precis = ! \in_array( $s['nature'], array( 'autre', 'satellite' ), true );
	if ( $precis && $s['nature'] !== $a['nature'] && ( 'autre' === $a['nature'] || ( \in_array( $a['nature'], array( 'satellite', 'incendie' ), true ) && famille( (string) $s['nature'] ) === famille( (string) $a['nature'] ) ) ) ) {
		$a['nature'] = $s['nature'];
	}
	if ( '' === (string) ( $a['titre_type'] ?? '' ) || rang( (string) $s['type'] ) < rang( (string) $a['titre_type'] ) ) {
		$a['titre']      = $s['titre'];
		$a['titre_type'] = $s['type'];
	}
	foreach ( array( 'commune', 'insee', 'dep' ) as $k ) {
		if ( '' === (string) $a[ $k ] && '' !== (string) ( $s[ $k ] ?? '' ) ) {
			$a[ $k ] = (string) $s[ $k ];
		}
	}
	if ( empty( $a['lat'] ) && ! empty( $s['lat'] ) ) {
		$a['lat'] = (float) $s['lat'];
		$a['lng'] = (float) $s['lng'];
	}
	$a['mots'] = \array_slice( \array_values( \array_unique( \array_merge( (array) $a['mots'], (array) $s['mots'] ) ) ), 0, 30 );
	return $a;
}

/**
 * L'alerte s'affiche-t-elle ?
 *
 * @param array<string,mixed> $a   Alerte.
 * @param int                 $now Horodatage.
 * @return bool
 */
function visible( array $a, int $now ): bool {
	return empty( $a['masque'] ) && empty( $a['fin'] ) && $now - (int) $a['maj'] < ttl( (string) $a['nature'] );
}

/**
 * Oubli des alertes terminées depuis plus de 24 h (gardées un jour pour
 * absorber les articles tardifs sans rouvrir l'alerte).
 *
 * @param array<string,array<string,mixed>> $alertes Alertes.
 * @param int                               $now     Horodatage.
 * @return array<string,array<string,mixed>>
 */
function purger( array $alertes, int $now ): array {
	return \array_filter( $alertes, static fn( array $a ): bool => $now - (int) $a['maj'] < ttl( (string) $a['nature'] ) + UN_JOUR );
}


/* -------------------------------------------------------------------------
 * Satellites NASA FIRMS
 * ---------------------------------------------------------------------- */

/**
 * Points chauds fiables d'un CSV FIRMS (VIIRS ou MODIS).
 *
 * @param string $csv CSV.
 * @return array<int,array{0:float,1:float,2:int,3:float}> lat, lng, horodatage, puissance radiative.
 */
function points_firms( string $csv ): array {
	$lignes = \preg_split( '/\R/', \trim( $csv ) ) ?: array();
	$tete   = \str_getcsv( (string) \array_shift( $lignes ), ',', '"', '' );
	$i      = \array_flip( $tete );
	if ( ! isset( $i['latitude'], $i['longitude'], $i['acq_date'], $i['acq_time'] ) ) {
		return array();
	}
	$out = array();
	foreach ( \array_slice( $lignes, 0, 5000 ) as $l ) {
		$c    = \str_getcsv( $l, ',', '"', '' );
		$conf = \strtolower( (string) ( $c[ $i['confidence'] ?? -1 ] ?? 'n' ) );
		if ( 'l' === $conf || 'low' === $conf || ( \is_numeric( $conf ) && (int) $conf < 50 ) ) {
			continue;
		}
		$h  = \str_pad( (string) $c[ $i['acq_time'] ], 4, '0', STR_PAD_LEFT );
		$ts = \strtotime( $c[ $i['acq_date'] ] . ' ' . \substr( $h, 0, 2 ) . ':' . \substr( $h, 2, 2 ) . ' UTC' );
		if ( false === $ts ) {
			continue;
		}
		$out[] = array( (float) $c[ $i['latitude'] ], (float) $c[ $i['longitude'] ], $ts, (float) ( $c[ $i['frp'] ?? -1 ] ?? 0 ) );
	}
	return $out;
}

/**
 * Regroupe les points chauds proches (moins de $km entre deux voisins).
 *
 * @param array<int,array{0:float,1:float,2:int,3:float}> $pts Points.
 * @param float                                            $km  Distance de liaison.
 * @return array<int,array{lat:float,lng:float,n:int,t:int,frp:float}>
 */
function amas( array $pts, float $km = 2.0 ): array {
	$n      = \count( $pts );
	$groupe = \array_fill( 0, $n, -1 );
	$g      = 0;
	for ( $a = 0; $a < $n; $a++ ) {
		if ( $groupe[ $a ] >= 0 ) {
			continue;
		}
		$groupe[ $a ] = $g;
		$pile         = array( $a );
		while ( $pile ) {
			$x = \array_pop( $pile );
			for ( $b = 0; $b < $n; $b++ ) {
				if ( $groupe[ $b ] < 0 && \abs( $pts[ $x ][0] - $pts[ $b ][0] ) < 0.05 && km( $pts[ $x ][0], $pts[ $x ][1], $pts[ $b ][0], $pts[ $b ][1] ) <= $km ) {
					$groupe[ $b ] = $g;
					$pile[]       = $b;
				}
			}
		}
		++$g;
	}
	$out = array();
	foreach ( $pts as $k => [ $la, $lo, $t, $frp ] ) {
		$o = &$out[ $groupe[ $k ] ];
		$o = $o ?? array( 'lat' => 0.0, 'lng' => 0.0, 'n' => 0, 't' => 0, 'frp' => 0.0 );
		$o['lat'] += $la;
		$o['lng'] += $lo;
		++$o['n'];
		$o['t']    = \max( $o['t'], $t );
		$o['frp'] += $frp;
		unset( $o );
	}
	foreach ( $out as &$o ) {
		$o['lat'] = \round( $o['lat'] / $o['n'], 4 );
		$o['lng'] = \round( $o['lng'] / $o['n'], 4 );
	}
	unset( $o );
	return \array_values( $out );
}

/**
 * Case de 0,05° (environ 5 km) d'un point.
 *
 * @param float $lat Latitude.
 * @param float $lng Longitude.
 * @return string
 */
function case_geo( float $lat, float $lng ): string {
	return \round( $lat * 20 ) . '_' . \round( $lng * 20 );
}

/**
 * Source de chaleur permanente (usine, torchère) : vue au moins 3 jours
 * différents avant aujourd'hui sur les 10 derniers jours.
 *
 * @param array<string,string[]> $hist  Case => dates (Y-m-d).
 * @param string                 $case  Case.
 * @param string                 $jour  Aujourd'hui (Y-m-d).
 * @return bool
 */
function permanent( array $hist, string $case, string $jour ): bool {
	$limite = \gmdate( 'Y-m-d', (int) \strtotime( $jour . ' -10 days' ) );
	$jours  = \array_filter( (array) ( $hist[ $case ] ?? array() ), static fn( string $d ): bool => $d < $jour && $d >= $limite );
	return \count( \array_unique( $jours ) ) >= 3;
}

/**
 * Signal satellite depuis un amas de points chauds.
 *
 * @param array{lat:float,lng:float,n:int,t:int,frp:float} $o Amas.
 * @return array<string,mixed>
 */
function signal_firms( array $o ): array {
	return array(
		'type'   => 'firms',
		'source' => 'Satellites NASA FIRMS',
		'url'    => 'https://firms.modaps.eosdis.nasa.gov/map/#d:24hrs;@' . \round( $o['lng'], 2 ) . ',' . \round( $o['lat'], 2 ) . ',11.0z',
		'titre'  => $o['n'] . ' points chauds repérés par satellite',
		'date'   => (int) $o['t'],
		'nature' => 'satellite',
		'moyens' => array(),
		'fin'    => false,
		'lieux'  => array(),
		'dep'    => '',
		'lat'    => $o['lat'],
		'lng'    => $o['lng'],
		'mots'   => array(),
	);
}
