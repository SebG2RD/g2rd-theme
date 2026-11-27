<?php
/**
 * Tests de la lecture de Concours-Territorial (sans WordPress).
 *
 * Lancer : php tests/concours-test.php
 * Pages réelles relevées le 31 octobre 2026 (tests/fixtures/concours/).
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace {
	function add_action( ...$a ): void {}
	function add_filter( ...$a ): void {}
}

namespace G2RD_Rescue18\Concours {

	require __DIR__ . '/../inc/veille/normalisation.php';
	require __DIR__ . '/../inc/veille/http.php';
	require __DIR__ . '/../inc/concours.php';

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
	$fx = static fn( string $f ): string => (string) \file_get_contents( __DIR__ . '/fixtures/concours/' . $f );

	echo "Dates\n";
	eq( '2027-01-05', iso( '05/01/2027' ), 'iso' );
	$d = dates( "Inscriptions ouvertes jusqu'au 04/11/2026 - Dossier à renvoyer avant le 12/11/2026 - Date prévisionnelle des premières épreuves : le 25/03/2027" );
	eq( array( 'debut' => '', 'fin' => '2026-11-04', 'dossier' => '2026-11-12', 'epreuves' => '2027-03-25' ), $d, 'ligne complète' );
	eq( '2027-02-10', dates( 'Inscription ouvertes du 05/01/2027 au 10/02/2027 inclus' )['fin'], 'du … au' );
	eq( '2027-02-02', dates( 'Date indicative des premières épreuves : le 02/02/2027' )['epreuves'], 'épreuves' );

	echo "Recherche\n";
	$r1 = resultats( $fx( 'r1.json' ) );
	$r2 = resultats( $fx( 'r2.json' ) );
	eq( 10, \count( $r1 ), 'première page' );
	eq( 10, compte( $fx( 'r1.json' ) ), 'compte page 1' );
	eq( 6, \count( $r2 ), 'deuxième page' );
	$cap = null;
	foreach ( $r1 as $s ) {
		if ( '376' === $s['id'] ) {
			$cap = $s;
		}
	}
	eq( 'Caporal de sapeurs-pompiers professionnels', $cap['titre'] ?? '', 'titre caporal' );
	eq( '2027', $cap['session'] ?? '', 'session' );
	eq( 'C', $cap['categorie'] ?? '', 'catégorie' );
	eq( 'Concours', $cap['type'] ?? '', 'type' );
	eq( 'spp', $cap['cat'] ?? '', 'catégorie Rescue 18' );
	eq( '2027-01-05', $cap['dates']['debut'] ?? '', 'ouverture' );
	eq( '2027-02-10', $cap['dates']['fin'] ?? '', 'clôture' );
	eq( 'sante', $r1[5]['cat'], 'médecin : santé' );
	eq( 'examen', $r1[0]['cat'], 'examen professionnel' );
	eq( 1, $r1[0]['centres'], 'centres' );

	echo "Session\n";
	$s = session( $fx( 's375.html' ) );
	eq( 'Inscriptions terminées', $s['statut'], 'statut' );
	eq( '2027-04-20', $s['dates']['epreuves'], 'épreuves' );
	eq( 2, \count( $s['orgs'] ), 'deux centres' );
	eq( 'CENTRE DE GESTION DU RHONE ET DE LA METROPOLE DE LYON (69)', $s['orgs'][0]['nom'], 'nom du centre' );
	eq( 'INTERNE', $s['orgs'][0]['voies'], 'voies' );
	eq( 'https://www.agirhe-concours.fr/Index.aspx?dep=69', $s['orgs'][0]['url'], 'lien du centre' );
	eq( true, \str_contains( $s['orgs'][1]['note'], 'conventionnement' ), 'note de convention' );
	$c = session( $fx( 's376.html' ) );
	eq( 0, \count( $c['orgs'] ), 'centres à paraître' );
	eq( '2027-01-05', $c['dates']['debut'], 'ouverture sur la fiche' );
	eq( true, \str_contains( $c['info'], '05/01/2027' ), 'message d’ouverture' );

	echo "Onglets\n";
	$e = onglet( $fx( 'e376.html' ), 'DetailEpreuves' );
	eq( true, \str_contains( $e, 'natation' ), 'épreuves physiques' );
	eq( true, \str_contains( $e, 'Coefficient : 4' ), 'coefficient' );
	eq( false, \str_contains( $e, 'onclick' ), 'aucun script' );
	eq( false, \str_contains( $e, 'Note de cadrage' ), 'liens internes retirés' );
	eq( false, \str_contains( $e, 'class=' ), 'aucune classe' );
	$co = onglet( $fx( 'c376.html' ), 'DetailConditions' );
	eq( true, \str_contains( $co, 'niveau 3' ), 'diplôme' );
	eq( true, \str_contains( $co, 'href="https://www.legifrance.gouv.fr/' ), 'lien Légifrance gardé' );
	$mi = onglet( $fx( 'm376.html' ), 'DetailMissions' );
	eq( true, \str_contains( $mi, 'chef d’équipe' ) || \str_contains( $mi, "chef d'équipe" ), 'missions' );
	eq( '', onglet( '<p>rien</p>', 'DetailEpreuves' ), 'bloc absent' );

	echo "\n" . $GLOBALS['ok'] . ' réussis, ' . $GLOBALS['fail'] . " échecs\n";
	exit( $GLOBALS['fail'] ? 1 : 0 );
}
