<?php
/**
 * Tests des alertes d'interventions (sans WordPress).
 *
 * Lancer : php tests/alertes-test.php
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18\Alertes;

use function G2RD_Rescue18\Veille\plat;

require __DIR__ . '/../inc/veille/normalisation.php';
require __DIR__ . '/../inc/annuaire/territoires.php';
require __DIR__ . '/../inc/alertes/analyse.php';

$GLOBALS['ok']   = 0;
$GLOBALS['fail'] = 0;

function eq( $a, $b, string $l ): void {
	if ( $a === $b ) {
		++$GLOBALS['ok'];
	} else {
		++$GLOBALS['fail'];
		echo "  ÉCHEC : $l (attendu " . \var_export( $a, true ) . ', obtenu ' . \var_export( $b, true ) . ")\n";
	}
}

$deps = \G2RD_Rescue18\Annuaire\departements();
$now  = \strtotime( '2026-07-20 16:00:00 UTC' );
$art  = static fn( string $t, string $r = '', string $d = '2026-07-20 14:10:00', string $u = '' ): array => array(
	'titre'  => $t,
	'resume' => $r,
	'date'   => $d,
	'url'    => '' !== $u ? $u : 'https://exemple.fr/' . \md5( $t ),
);

echo "Nature\n";
eq( 'feu_foret', nature( plat( 'Un feu de forêt menace des habitations à Martigues' ) ), 'feu de forêt' );
eq( 'feu_foret', nature( plat( 'Incendie de végétation : 3 Canadair engagés' ) ), 'végétation' );
eq( 'incendie_indus', nature( plat( 'Violent incendie dans un entrepôt de Gennevilliers' ) ), 'entrepôt' );
eq( 'explosion', nature( plat( 'Explosion dans un immeuble à Lyon' ) ), 'explosion' );
eq( 'risque_chimique', nature( plat( 'Fuite de gaz : 300 personnes évacuées' ) ), 'gaz' );
eq( 'accident', nature( plat( 'Carambolage sur l’A7 : 40 véhicules impliqués' ) ), 'accident' );
eq( 'incendie', nature( plat( 'Incendie d’un immeuble à Marseille' ) ), 'incendie' );

echo "Moyens\n";
$m = moyens( plat( 'Plus de 200 sapeurs-pompiers, 60 engins et 4 Canadair luttent contre le feu qui a parcouru 150 hectares.' ) );
eq( 200, $m['pompiers'], 'pompiers' );
eq( 60, $m['engins'], 'engins' );
eq( 4, $m['aeriens'], 'aériens' );
eq( 150.0, $m['hectares'], 'hectares' );
eq( 100, moyens( plat( 'Une centaine de pompiers mobilisés' ) )['pompiers'], 'centaine' );
eq( 1200, moyens( plat( '1 200 pompiers engagés' ) )['pompiers'], 'milliers' );
eq( 1, moyens( plat( 'Des bombardiers d’eau en renfort' ) )['aeriens'], 'aériens sans nombre' );
eq( 300, moyens( plat( '300 personnes ont été évacuées' ) )['evacues'], 'évacués' );
eq( 12.5, moyens( plat( 'Le feu a détruit 12,5 ha de garrigue' ) )['hectares'], 'ha décimal' );

echo "Grosse intervention\n";
$g = static function ( string $t ): bool {
	$p = plat( $t );
	return grosse( $p, nature( $p ), moyens( $p ) );
};
eq( true, $g( 'Feu de forêt : 4 Canadair engagés' ), 'aériens sur feu de forêt' );
eq( true, $g( '80 pompiers mobilisés pour un incendie d’entrepôt' ), '80 pompiers' );
eq( true, $g( 'Plan rouge déclenché après un accident de car' ), 'plan rouge' );
eq( true, $g( 'Violent incendie dans une usine de Rouen' ), 'ampleur industrielle' );
eq( false, $g( 'Violent incendie dans une maison, 12 pompiers sur place' ), 'maison' );
eq( false, $g( 'Feu de poubelle rue Pasteur' ), 'petit feu' );
eq( false, $g( 'Accident de la route : un blessé léger' ), 'petit accident' );

echo "Hors sujet\n";
eq( true, hors_sujet( plat( 'Exercice grandeur nature : 80 pompiers mobilisés' ) ), 'exercice' );
eq( true, hors_sujet( plat( 'Sainte-Barbe : 200 sapeurs-pompiers réunis' ) ), 'Sainte-Barbe' );
eq( true, hors_sujet( plat( 'Le SDIS recrute 150 sapeurs-pompiers volontaires' ) ), 'recrutement' );
eq( false, hors_sujet( plat( 'Feu de forêt à Martigues' ) ), 'feu réel' );

echo "Fin d'intervention\n";
eq( true, termine( plat( 'Le feu est désormais fixé' ) ), 'fixé' );
eq( true, termine( plat( 'Incendie maîtrisé en début de soirée' ) ), 'maîtrisé' );
eq( false, termine( plat( 'Le feu n’est toujours pas fixé' ) ), 'négation' );
eq( false, termine( plat( 'Le feu n’est pas encore maîtrisé, 300 pompiers sur place' ) ), 'pas encore' );
eq( false, termine( plat( 'Le feu progresse, 200 pompiers engagés' ) ), 'en cours' );

echo "Lieux\n";
eq( array( 'Martigues' ), lieux( 'Un feu de forêt menace des habitations à Martigues' ), 'à Martigues' );
eq( array( 'Martigues' ), lieux( 'Martigues : 200 pompiers mobilisés contre un incendie' ), 'préfixe' );
eq( array( 'Martigues' ), lieux( 'Martigues (13) : 200 pompiers mobilisés' ), 'préfixe et département' );
eq( array( 'Aix-en-Provence' ), lieux( 'Incendie près d’Aix-en-Provence' ), 'commune composée' );
eq( array( 'Le Havre' ), lieux( 'Explosion au Havre : 50 pompiers engagés' ), 'au Havre' );
eq( array( 'Fos-sur-Mer' ), lieux( 'Feu de forêt à Fos-sur-Mer, 3 Canadair' ), 'Fos-sur-Mer' );
eq( array( 'La Rochelle' ), lieux( 'Incendie à La Rochelle' ), 'La Rochelle' );
eq( array(), lieux( 'Incendie : 80 pompiers mobilisés' ), 'aucun lieu' );
eq( array( 'Villeneuve-d\'Ascq' ), lieux( 'Incendie d’entrepôt à Villeneuve-d’Ascq' ), 'apostrophe' );

echo "Département cité\n";
eq( '13', dep_cite( 'Martigues (13) : feu de forêt', $deps ), 'parenthèses' );
eq( '83', dep_cite( 'Feu de forêt dans le Var : 500 hectares brûlés', $deps ), 'dans le Var' );
eq( '33', dep_cite( 'Incendie en Gironde', $deps ), 'en Gironde' );

echo "Signal\n";
$s = signal( $art( 'Un feu de forêt menace des habitations à Martigues', '200 pompiers et 4 Canadair engagés.' ), 'presse', 'La Provence', '', $now, $deps );
eq( true, \is_array( $s ), 'signal créé' );
eq( 'feu_foret', $s['nature'] ?? '', 'nature du signal' );
eq( array( 'Martigues' ), $s['lieux'] ?? array(), 'lieu du signal' );
eq( null, signal( $art( 'Feu de forêt à Martigues, 4 Canadair', '', '2026-07-18 10:00:00' ), 'presse', 'X', '', $now, $deps ), 'article trop ancien' );
eq( null, signal( $art( 'Exercice : 4 Canadair engagés' ), 'presse', 'X', '', $now, $deps ), 'exercice ignoré' );

eq( null, signal( $art( 'Un(e) ingénieur(e) SI au groupement des systèmes d’information', 'Le service départemental d’incendie et de secours, 1 200 sapeurs-pompiers, recrute.' ), 'sdis', 'X', '', $now, $deps ), 'offre d’emploi ignorée' );
eq( null, signal( $art( 'Adrien décroche le titre de champion de France de trail', '300 sapeurs-pompiers au départ.' ), 'sdis', 'X', '', $now, $deps ), 'sport ignoré' );
eq( true, \is_array( signal( $art( 'Intervention en cours sur la commune de Brest', '60 sapeurs-pompiers engagés.' ), 'sdis', 'X', '29', $now, $deps ) ), 'intervention retenue' );

echo "Regroupement\n";
$a1 = $s + array( 'commune' => 'Martigues', 'insee' => '13056', 'lat' => 43.40, 'lng' => 5.05 );
$s2 = signal( $art( 'Martigues : 200 pompiers mobilisés contre un incendie', '', '2026-07-20 14:45:00' ), 'presse', 'France 3', '', $now, $deps );
$s2 += array( 'commune' => 'Martigues', 'insee' => '13056', 'lat' => 43.40, 'lng' => 5.05 );
$s3 = signal( $art( 'Feu de végétation en cours, 300 sapeurs-pompiers engagés, 6 moyens aériens', '', '2026-07-20 15:30:00', 'https://sdis13.fr/actu/1' ), 'sdis', 'SDIS 13', '13', $now, $deps );
$s3 += array( 'commune' => '', 'insee' => '', 'lat' => 43.43, 'lng' => 5.10 );
$al = array();
$x  = nouvelle( $a1 );
$al[ $x['id'] ] = $x;
eq( $x['id'], rattacher( $al, $s2 ), 'même commune rattachée' );
$al[ $x['id'] ] = fusionner( $al[ $x['id'] ], $s2 );
eq( $x['id'], rattacher( $al, $s3 ), 'SDIS à moins de 10 km rattaché' );
$al[ $x['id'] ] = fusionner( $al[ $x['id'] ], $s3 );
$y = $al[ $x['id'] ];
eq( 3, \count( $y['sources'] ), 'trois sources' );
eq( 'sdis', $y['sources'][0]['type'], 'SDIS en tête' );
eq( 300, $y['moyens']['pompiers'], 'moyens les plus récents' );
eq( 'Feu de végétation en cours, 300 sapeurs-pompiers engagés, 6 moyens aériens', $y['titre'], 'titre du SDIS' );
eq( true, visible( $y, $now ), 'alerte visible' );

$autre = signal( $art( 'Feu de forêt à Bordeaux, 4 Canadair', '', '2026-07-20 15:00:00' ), 'presse', 'Sud Ouest', '', $now, $deps );
$autre += array( 'commune' => 'Bordeaux', 'insee' => '33063', 'lat' => 44.84, 'lng' => -0.58 );
eq( '', rattacher( $al, $autre ), 'autre ville : nouvelle alerte' );
eq( $x['id'], rattacher( $al, $a1 ), 'même lien déjà connu' );

echo "Fin et expiration\n";
$f = signal( $art( 'Martigues : le feu de forêt est fixé, 200 pompiers restent sur place', '', '2026-07-20 15:50:00' ), 'presse', 'La Provence', '', $now, $deps );
$f += array( 'commune' => 'Martigues', 'insee' => '13056', 'lat' => 43.40, 'lng' => 5.05 );
$z = fusionner( $y, $f );
eq( false, visible( $z, $now ), 'masquée après « fixé »' );
eq( false, visible( $y, $now + 25 * 3600 ), 'feu de forêt expiré après 24 h' );
eq( true, visible( $y, $now + 20 * 3600 ), 'feu de forêt encore visible à 20 h' );
$y['masque'] = true;
eq( false, visible( $y, $now ), 'masquée à la main' );
eq( 0, \count( purger( array( 'a' => $y ), $now + 3 * 86400 ) ), 'purge après 24 h de plus' );

echo "Satellites\n";
$csv = "latitude,longitude,bright_ti4,scan,track,acq_date,acq_time,satellite,instrument,confidence,version,bright_ti5,frp,daynight\n"
	. "43.401,5.050,340.1,0.4,0.4,2026-07-20,1230,N,VIIRS,n,2.0NRT,300.2,12.5,D\n"
	. "43.405,5.055,345.1,0.4,0.4,2026-07-20,1231,N,VIIRS,h,2.0NRT,301.2,15.0,D\n"
	. "43.410,5.060,330.1,0.4,0.4,2026-07-20,1232,N,VIIRS,l,2.0NRT,299.2,5.0,D\n"
	. "47.000,2.000,330.1,0.4,0.4,2026-07-20,0130,N,VIIRS,n,2.0NRT,299.2,5.0,N\n";
$pts = points_firms( $csv );
eq( 3, \count( $pts ), 'confiance faible écartée' );
$am = amas( $pts );
eq( 2, \count( $am ), 'deux amas' );
eq( 2, $am[0]['n'], 'deux points proches' );
eq( true, permanent( array( 'c' => array( '2026-07-15', '2026-07-17', '2026-07-19' ) ), 'c', '2026-07-20' ), 'source permanente' );
eq( false, permanent( array( 'c' => array( '2026-07-19', '2026-07-20' ) ), 'c', '2026-07-20' ), 'feu nouveau' );
$sf = signal_firms( $am[0] ) + array( 'commune' => '', 'insee' => '' );
eq( $x['id'], rattacher( $al, $sf ), 'points chauds rattachés au feu' );

echo "\n" . $GLOBALS['ok'] . ' réussis, ' . $GLOBALS['fail'] . " échecs\n";
exit( $GLOBALS['fail'] ? 1 : 0 );
