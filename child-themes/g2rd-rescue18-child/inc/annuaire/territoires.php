<?php
/**
 * Référentiel des départements (code => nom, région) et des régions.
 * Fonctions pures, sans WordPress (testées dans tests/).
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18\Annuaire;

/**
 * Départements : code => [ nom, région ].
 *
 * @return array<string,array{0:string,1:string}>
 */
function departements(): array {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$ara = 'Auvergne-Rhône-Alpes';
	$bfc = 'Bourgogne-Franche-Comté';
	$bre = 'Bretagne';
	$cvl = 'Centre-Val de Loire';
	$cor = 'Corse';
	$ge  = 'Grand Est';
	$hdf = 'Hauts-de-France';
	$idf = 'Île-de-France';
	$nor = 'Normandie';
	$naq = 'Nouvelle-Aquitaine';
	$occ = 'Occitanie';
	$pdl = 'Pays de la Loire';
	$pac = 'Provence-Alpes-Côte d\'Azur';
	$om  = 'Outre-mer';
	$cache = array(
		'01' => array( 'Ain', $ara ), '02' => array( 'Aisne', $hdf ), '03' => array( 'Allier', $ara ),
		'04' => array( 'Alpes-de-Haute-Provence', $pac ), '05' => array( 'Hautes-Alpes', $pac ), '06' => array( 'Alpes-Maritimes', $pac ),
		'07' => array( 'Ardèche', $ara ), '08' => array( 'Ardennes', $ge ), '09' => array( 'Ariège', $occ ),
		'10' => array( 'Aube', $ge ), '11' => array( 'Aude', $occ ), '12' => array( 'Aveyron', $occ ),
		'13' => array( 'Bouches-du-Rhône', $pac ), '14' => array( 'Calvados', $nor ), '15' => array( 'Cantal', $ara ),
		'16' => array( 'Charente', $naq ), '17' => array( 'Charente-Maritime', $naq ), '18' => array( 'Cher', $cvl ),
		'19' => array( 'Corrèze', $naq ), '2A' => array( 'Corse-du-Sud', $cor ), '2B' => array( 'Haute-Corse', $cor ),
		'21' => array( 'Côte-d\'Or', $bfc ), '22' => array( 'Côtes-d\'Armor', $bre ), '23' => array( 'Creuse', $naq ),
		'24' => array( 'Dordogne', $naq ), '25' => array( 'Doubs', $bfc ), '26' => array( 'Drôme', $ara ),
		'27' => array( 'Eure', $nor ), '28' => array( 'Eure-et-Loir', $cvl ), '29' => array( 'Finistère', $bre ),
		'30' => array( 'Gard', $occ ), '31' => array( 'Haute-Garonne', $occ ), '32' => array( 'Gers', $occ ),
		'33' => array( 'Gironde', $naq ), '34' => array( 'Hérault', $occ ), '35' => array( 'Ille-et-Vilaine', $bre ),
		'36' => array( 'Indre', $cvl ), '37' => array( 'Indre-et-Loire', $cvl ), '38' => array( 'Isère', $ara ),
		'39' => array( 'Jura', $bfc ), '40' => array( 'Landes', $naq ), '41' => array( 'Loir-et-Cher', $cvl ),
		'42' => array( 'Loire', $ara ), '43' => array( 'Haute-Loire', $ara ), '44' => array( 'Loire-Atlantique', $pdl ),
		'45' => array( 'Loiret', $cvl ), '46' => array( 'Lot', $occ ), '47' => array( 'Lot-et-Garonne', $naq ),
		'48' => array( 'Lozère', $occ ), '49' => array( 'Maine-et-Loire', $pdl ), '50' => array( 'Manche', $nor ),
		'51' => array( 'Marne', $ge ), '52' => array( 'Haute-Marne', $ge ), '53' => array( 'Mayenne', $pdl ),
		'54' => array( 'Meurthe-et-Moselle', $ge ), '55' => array( 'Meuse', $ge ), '56' => array( 'Morbihan', $bre ),
		'57' => array( 'Moselle', $ge ), '58' => array( 'Nièvre', $bfc ), '59' => array( 'Nord', $hdf ),
		'60' => array( 'Oise', $hdf ), '61' => array( 'Orne', $nor ), '62' => array( 'Pas-de-Calais', $hdf ),
		'63' => array( 'Puy-de-Dôme', $ara ), '64' => array( 'Pyrénées-Atlantiques', $naq ), '65' => array( 'Hautes-Pyrénées', $occ ),
		'66' => array( 'Pyrénées-Orientales', $occ ), '67' => array( 'Bas-Rhin', $ge ), '68' => array( 'Haut-Rhin', $ge ),
		'69' => array( 'Rhône', $ara ), '70' => array( 'Haute-Saône', $bfc ), '71' => array( 'Saône-et-Loire', $bfc ),
		'72' => array( 'Sarthe', $pdl ), '73' => array( 'Savoie', $ara ), '74' => array( 'Haute-Savoie', $ara ),
		'75' => array( 'Paris', $idf ), '76' => array( 'Seine-Maritime', $nor ), '77' => array( 'Seine-et-Marne', $idf ),
		'78' => array( 'Yvelines', $idf ), '79' => array( 'Deux-Sèvres', $naq ), '80' => array( 'Somme', $hdf ),
		'81' => array( 'Tarn', $occ ), '82' => array( 'Tarn-et-Garonne', $occ ), '83' => array( 'Var', $pac ),
		'84' => array( 'Vaucluse', $pac ), '85' => array( 'Vendée', $pdl ), '86' => array( 'Vienne', $naq ),
		'87' => array( 'Haute-Vienne', $naq ), '88' => array( 'Vosges', $ge ), '89' => array( 'Yonne', $bfc ),
		'90' => array( 'Territoire de Belfort', $bfc ), '91' => array( 'Essonne', $idf ), '92' => array( 'Hauts-de-Seine', $idf ),
		'93' => array( 'Seine-Saint-Denis', $idf ), '94' => array( 'Val-de-Marne', $idf ), '95' => array( 'Val-d\'Oise', $idf ),
		'971' => array( 'Guadeloupe', $om ), '972' => array( 'Martinique', $om ), '973' => array( 'Guyane', $om ),
		'974' => array( 'La Réunion', $om ), '976' => array( 'Mayotte', $om ),
	);
	return $cache;
}

/**
 * Régions dans l'ordre d'affichage.
 *
 * @return string[]
 */
function regions(): array {
	$r = array();
	foreach ( departements() as [ , $reg ] ) {
		$r[ $reg ] = true;
	}
	$r = \array_keys( $r );
	\sort( $r );
	return $r;
}

/**
 * Code département depuis un code INSEE de commune.
 *
 * @param string $insee Code INSEE (5 caractères).
 * @return string
 */
function dep_depuis_insee( string $insee ): string {
	$insee = \strtoupper( \trim( $insee ) );
	if ( \preg_match( '/^97\d/', $insee ) ) {
		return \substr( $insee, 0, 3 );
	}
	return \substr( $insee, 0, 2 );
}

/**
 * Code département depuis un libellé « Indre (36) », « Corse du Sud (2A) ».
 *
 * @param string $texte Libellé.
 * @return string
 */
function dep_depuis_libelle( string $texte ): string {
	if ( \preg_match( '/\((\d{2,3}|2A|2B)\)\s*$/i', \trim( $texte ), $m ) ) {
		$c = \strtoupper( $m[1] );
		return isset( departements()[ $c ] ) ? $c : '';
	}
	return '';
}

/**
 * Nom d'un département.
 *
 * @param string $code Code.
 * @return string
 */
function nom_dep( string $code ): string {
	return departements()[ $code ][0] ?? $code;
}

/**
 * Région d'un département.
 *
 * @param string $code Code.
 * @return string
 */
function region_dep( string $code ): string {
	return departements()[ $code ][1] ?? '';
}
