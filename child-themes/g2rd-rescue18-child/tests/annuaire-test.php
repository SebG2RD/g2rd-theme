<?php
/**
 * Tests de l'annuaire, des actualités des SDIS et des offres (sans WordPress).
 *
 * Lancer : php tests/annuaire-test.php
 * Extraits réels relevés le 30 octobre 2026 (tests/fixtures/annuaire/).
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18\Annuaire;

use G2RD_Rescue18\Veille\Erreur_Acces;
use G2RD_Rescue18\Veille\Http;
use G2RD_Rescue18\Veille\Reponse;
use G2RD_Rescue18\Veille\Transport;

require __DIR__ . '/../inc/veille/normalisation.php';
require __DIR__ . '/../inc/veille/http.php';
require __DIR__ . '/../inc/veille/connecteurs.php';
require __DIR__ . '/../inc/veille/moteur.php';
require __DIR__ . '/../inc/annuaire/territoires.php';
require __DIR__ . '/../inc/annuaire/connecteurs.php';

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

function fx( string $f ): string {
	return (string) \file_get_contents( __DIR__ . '/fixtures/annuaire/' . $f );
}

class T implements Transport {
	public array $routes = array();
	public array $appels = array();
	public function get( string $url, array $entetes, int $delai, int $taille ): Reponse {
		$this->appels[] = array( $url, $entetes );
		$r = $this->routes[ $url ] ?? null;
		if ( \is_callable( $r ) ) {
			return $r( $url, $entetes );
		}
		if ( null === $r ) {
			throw new Erreur_Acces( 'reseau', 'injoignable ' . $url );
		}
		return new Reponse( $r[0], $r[1], $r[2] ?? array(), $url );
	}
	public function pause( float $s ): void {}
}

echo "== Territoires ==\n";
eq( 101, \count( departements() ), '101 départements' );
eq( '2A', dep_depuis_insee( '2A004' ), 'INSEE Corse' );
eq( '974', dep_depuis_insee( '97411' ), 'INSEE La Réunion' );
eq( '36', dep_depuis_libelle( 'Indre (36)' ), 'Libellé Indre' );
eq( '2B', dep_depuis_libelle( 'Haute-Corse (2b)' ), 'Libellé Haute-Corse' );
eq( '', dep_depuis_libelle( 'Paris' ), 'Libellé sans code' );
eq( 'Normandie', region_dep( '27' ), 'Région de l\'Eure' );

echo "== Annuaire DILA ==\n";
$a   = analyser_annuaire( fx( 'annuaire.json' ) );
$par = array();
foreach ( $a as $s ) {
	$par[ $s['deps'] ] = $s;
}
eq( 6, \count( $a ), 'Six fiches lues' );
eq( 'SDIS 76 · Seine-Maritime', $par['76']['titre'], 'Titre SDIS 76' );
eq( 'seine-maritime-76', $par['76']['slug'], 'Slug SDIS 76' );
eq( '02 35 56 11 11', $par['76']['tel'], 'Le 18 n\'est jamais pris pour le standard' );
eq( '49.606813', $par['76']['lat'], 'Latitude de l\'adresse physique' );
eq( 2, \count( (array) \json_decode( $par['76']['reseaux'], true ) ), 'Réseaux sociaux' );
eq( 'BSPP · Brigade de sapeurs-pompiers de Paris', $par['75,92,93,94']['titre'], 'BSPP' );
eq( 'bspp', $par['75,92,93,94']['genre'], 'Genre BSPP' );
eq( 'SDMIS 69 · Rhône et Métropole de Lyon', $par['69']['titre'], 'SDMIS du Rhône' );
eq( 'SIS 67 · Bas-Rhin', $par['67']['titre'], 'SIS du Bas-Rhin' );
eq( 'SDIS 2A · Corse-du-Sud', $par['2A']['titre'], 'Corse-du-Sud' );
eq( 'corse-du-sud-2a', $par['2A']['slug'], 'Slug Corse' );
eq( 'Outre-mer', $par['974']['region'], 'Région outre-mer' );
eq( '06/03/2026 11:18:14', $par['67']['signal'], 'Signal de version = date de modification' );
$b = fiche_bmpm();
eq( '13', $b['deps'], 'BMPM sur le 13' );

$t = new T();
$k = new Connecteur_Annuaire();
$u = $k->unites( array( 'force' => true ) )[0];
$t->routes[ $u['url'] ] = array( 200, fx( 'annuaire.json' ), array( 'content-type' => 'application/json' ) );
try {
	$k->traiter( $u, new Http( $t, $k->domaines(), 0, 3 ), array(), static fn() => false );
	eq( true, false, 'Import partiel refusé' );
} catch ( Erreur_Acces $e ) {
	eq( 'structure', $e->categorie, 'Moins de 90 services : rien n\'est modifié' );
}
eq( true, \str_contains( $u['url'], 'where=pivot%20like%20%22sdis%22' ), 'Requête API filtrée sur les SDIS' );

echo "== Visuels et flux ==\n";
$v = visuels_page( fx( 'sdis27-home.html' ), 'https://www.sapeurspompiers27.fr/' );
eq( 'https://www.sapeurspompiers27.fr/wp-content/uploads/sdis-logo-1.png', $v['logo'], 'Logo du SDIS 27' );
eq( '', $v['image'], 'Pas d\'og:image sur le SDIS 27' );
eq( 'https://www.sapeurspompiers27.fr/feed/', flux_declare( fx( 'sdis27-home.html' ), 'https://www.sapeurspompiers27.fr/' ), 'Flux principal, pas celui des commentaires' );
eq( 'https://a.fr/x.jpg', image_valide( 'http://a.fr/x.jpg' ), 'Image passée en HTTPS' );
eq( '', image_valide( 'data:image/png;base64,AAA' ), 'Image data: refusée' );
eq( '', image_valide( 'https://a.fr/logo.svg' ), 'SVG externe refusé' );

$f = analyser_flux( fx( 'sdis76-feed.xml' ), 6, 3650 );
eq( 3, \count( $f ), 'Trois articles (ancienneté large)' );
$f = analyser_flux( fx( 'sdis76-feed.xml' ), 6, 45 );
eq( 2, \count( $f ), 'Article de 2023 écarté à 45 jours' );
eq( 'Incendie d’un hangar agricole à Angerville-l’Orcher', $f[0]['titre'], 'Titre RSS' );
eq( 'https://www.sdis76.fr/wp-content/uploads/2026/10/hangar-1024x683.jpg', $f[0]['image'], 'Image du contenu' );
eq( 'https://www.sdis76.fr/wp-content/uploads/2026/10/spv.jpg', $f[1]['image'], 'Image en pièce jointe (enclosure)' );
eq( false, \str_contains( $f[0]['resume'], 'appeared first' ) && \mb_strlen( $f[0]['resume'] ) > 245, 'Résumé court' );
eq( false, est_recrutement( $f[0]['titre'] . ' ' . $f[0]['resume'] ), 'Intervention : pas un recrutement' );
eq( true, est_recrutement( $f[1]['titre'] ), 'Campagne SPV repérée' );
$w = analyser_wpjson( fx( 'sdis91-wpjson.json' ), 3650 );
eq( 2, \count( $w ), 'Deux articles wp-json' );
eq( 'https://sdis91.fr/wp-content/uploads/2026/10/20261011_HYROPS-771-768x1152.jpg', $w[0]['image'], 'Image à la une, taille moyenne' );
eq( '', $w[1]['image'], 'Article sans image' );
try {
	analyser_flux( '<html><body>Not found</body></html>' );
	eq( true, false, 'Flux HTML refusé' );
} catch ( Erreur_Acces $e ) {
	eq( 'flux', $e->categorie, 'Une page HTML n\'est pas un flux' );
}

eq( 'Eau et énergie : les pompiers 13 à la rencontre', casse_titre( 'EAU ET ÉNERGIE : LES POMPIERS 13 À LA RENCONTRE' ), 'Capitales ramenées en casse de phrase' );
eq( 'Le SDIS recrute des SPV', casse_titre( 'LE SDIS RECRUTE DES SPV' ), 'Sigles conservés' );
eq( 'Mardi prévention', casse_titre( 'Mardi prévention' ), 'Titre normal inchangé' );
eq( '', nettoyer_resume( 'The post MARDI PRÉVENTION : l’automne appeared first on SDIS 03.' ), 'Mention automatique WordPress retirée' );
eq( 'Le sinistre…', nettoyer_resume( 'Le sinistre [&#8230;]' ), 'Points de suspension' );

echo "== robots.txt ==\n";
eq( true, robots_autorise( "User-agent: *\r\nDisallow:", '/feed/' ), 'Disallow vide = tout permis' );
eq( false, robots_autorise( "User-agent: *\nDisallow: /feed", '/feed/' ), 'Flux interdit' );
eq( true, robots_autorise( "User-agent: *\nDisallow: /\nAllow: /feed/", '/feed/' ), 'Allow plus précis' );
eq( false, robots_autorise( "User-agent: rescue18-veille\nDisallow: /\n\nUser-agent: *\nDisallow:", '/feed/' ), 'Groupe propre à notre robot' );
eq( false, robots_autorise( "User-agent: *\nDisallow: /*.json$", '/wp-json/x.json' ), 'Jokers' );

echo "== Actualités : connecteur complet ==\n";
$GLOBALS['r18_etat_test'] = array();
$t = new T();
$t->routes['https://www.sdis76.fr/']           = array( 200, '<html><head><meta property="og:image" content="/img/une.jpg"><link rel="alternate" type="application/rss+xml" href="https://www.sdis76.fr/feed/"></head></html>', array( 'content-type' => 'text/html' ) );
$t->routes['https://www.sdis76.fr/robots.txt'] = array( 200, "User-agent: *\r\nDisallow:", array( 'content-type' => 'text/plain' ) );
$t->routes['https://www.sdis76.fr/feed/']      = array( 200, fx( 'sdis76-feed.xml' ), array( 'content-type' => 'application/rss+xml; charset=UTF-8' ) );
$ka           = new Connecteur_Actus();
$ka->services = static fn() => array( array( 'id' => 5, 'titre' => 'SDIS 76 · Seine-Maritime', 'site' => 'https://www.sdis76.fr/', 'flux' => '', 'flux_type' => '', 'flux_verif' => 0, 'dep' => '76', 'jours' => 45, 'max' => 6 ) );
$un           = $ka->unites( array() );
eq( 1, \count( $un ), 'Un lot de services' );
$res = $ka->traiter( $un[0], new Http( $t, $ka->domaines(), 0, 30 ), array(), static fn() => false );
$types = \array_count_values( \array_column( $res['items'], 'type' ) );
eq( 1, $types['flux_sdis'] ?? 0, 'Flux découvert et mémorisé' );
eq( 2, $types['actu_sdis'] ?? 0, 'Deux actualités' );
eq( 'https://www.sdis76.fr/img/une.jpg', $res['items'][0]['image'], 'Visuel du service (og:image absolu)' );
eq( '1', $res['items'][2]['recrutement'], 'Drapeau recrutement' );
eq( '76', $res['items'][1]['dep'], 'Département rattaché' );

$t2 = new T();
$t2->routes['https://www.sdis76.fr/robots.txt'] = array( 200, "User-agent: *\nDisallow: /feed/", array( 'content-type' => 'text/plain' ) );
$GLOBALS['r18_etat_test'] = array();
$ka->services = static fn() => array( array( 'id' => 5, 'titre' => 'SDIS 76', 'site' => 'https://www.sdis76.fr/', 'flux' => 'https://www.sdis76.fr/feed/', 'flux_type' => 'rss', 'flux_verif' => \time(), 'dep' => '76' ) );
$res = $ka->traiter( $ka->unites( array() )[0], new Http( $t2, $ka->domaines(), 0, 30 ), array(), static fn() => false );
eq( 0, \count( $res['items'] ), 'robots.txt respecté : rien n\'est lu' );
eq( true, \str_contains( \implode( ' ', $res['notes'] ), 'robots.txt' ), 'Raison notée au journal' );

echo "== Sécurité HTTP (domaines ouverts) ==\n";
$h = new Http( new T(), array( '*' ), 0, 5 );
foreach ( array( 'https://127.0.0.1/x', 'https://localhost/x', 'https://intranet/x', 'https://site.local/x', 'http://sdis.fr/' ) as $bad ) {
	try {
		$h->verifier_url( $bad );
		eq( true, false, 'Refus ' . $bad );
	} catch ( Erreur_Acces $e ) {
		eq( 'domaine', $e->categorie, 'Refus ' . $bad );
	}
}
$h->verifier_url( 'https://www.sdis27.fr/feed/' );
eq( true, true, 'Site public HTTPS accepté' );

echo "== Offres (CSV par tranches) ==\n";
$cols   = array( 'Organisme de rattachement', 'Référence', 'Versant', 'Métier', 'Statut du poste', 'Nature de l\'emploi', 'Durée du contrat', 'Intitulé du poste', 'Localisation du poste', 'Lieu d\'affectation', 'Niveau d\'études', 'Niveau d\'expérience min. requis', 'Date de vacance de l\'emploi', 'Date de début de publication par défaut', 'Date de fin de publication par défaut', 'Date de première publication', 'Spécialisation', 'Télétravail', 'Management', 'Employeur', 'Catégorie', 'Temps Plein', 'Durée du poste', 'Langues', 'Niveaux', 'Lieu d\'affectation (sans géolocalisation)', 'Avis de vacances au JO', 'Compétences attendues', 'Documents à transmettre', 'Nature de contrat ' );
$ligne  = static function ( array $v ) use ( $cols ): string {
	$r = array();
	foreach ( $cols as $c ) {
		$x   = (string) ( $v[ $c ] ?? '' );
		$r[] = \preg_match( '/[;"\n]/', $x ) ? '"' . \str_replace( '"', '""', $x ) . '"' : $x;
	}
	return \implode( ';', $r ) . "\n";
};
$csv  = "\xEF\xBB\xBF" . \implode( ';', $cols ) . "\n";
$csv .= $ligne( array( 'Organisme de rattachement' => 'Ministère de l\'Intérieur', 'Référence' => '2026-1', 'Intitulé du poste' => 'Agent d\'accueil', 'Localisation du poste' => 'Paris (75)' ) );
$csv .= $ligne( array( 'Organisme de rattachement' => 'Service départemental d\'incendie et de secours', 'Référence' => '2026-2005418', 'Métier' => 'Mécanicien', 'Intitulé du poste' => 'Mécanicien poids lourds - SDIS 27', 'Localisation du poste' => 'Eure (27)', 'Lieu d\'affectation (sans géolocalisation)' => 'Évreux', 'Catégorie' => 'Catégorie C', 'Date de fin de publication par défaut' => '30/11/2026', 'Compétences attendues' => "Diagnostic ; réparation\nPermis C exigé", 'Temps Plein' => 'Oui' ) );
$csv .= $ligne( array( 'Organisme de rattachement' => 'Collectivité', 'Référence' => '2026-3', 'Intitulé du poste' => 'Chef de groupement - SDMIS', 'Localisation du poste' => 'Rhône (69)' ) );
// Découpage à une position qui coupe le champ multiligne.
$coupe = \strpos( $csv, 'Permis C' );
$t1    = analyser_tranche( \substr( $csv, 0, $coupe ), array(), false );
eq( 1, $t1['lignes'], 'Tranche 1 : la ligne coupée est reportée' );
$t2 = analyser_tranche( $t1['reste'] . \substr( $csv, $coupe ), $t1['entete'], true );
eq( 2, $t2['lignes'], 'Tranche 2 : la ligne recollée est lue' );
$o = \array_merge( $t1['offres'], $t2['offres'] );
eq( 2, \count( $o ), 'Deux offres SDIS sur trois lignes' );
eq( '27', $o[0]['dep'], 'Département de l\'offre' );
eq( '2026-11-30', $o[0]['fin'], 'Date limite au format ISO' );
eq( "Diagnostic ; réparation\nPermis C exigé", $o[0]['competences'], 'Champ multiligne intact' );
eq( 'SDIS 27', $o[0]['employeur'], 'Employeur tiré de l\'intitulé' );
eq( '1', $o[0]['sdis'], 'Offre rattachée à un SDIS' );
eq( 'https://choisirleservicepublic.gouv.fr/offre-emploi/reference-2026-2005418/', $o[0]['url'], 'Lien de candidature' );
eq( 'Mécanicien poids lourds - SDIS 27', $t1['entete'] ? $o[0]['titre'] : '', 'En-tête sans BOM' );

$GLOBALS['r18_etat_test'] = array();
$t   = new T();
$ko  = new Connecteur_Offres();
$url = 'https://static.data.gouv.fr/resources/x/offres.csv';
$t->routes['https://www.data.gouv.fr/api/1/datasets/' . DATASET_CSP . '/'] = array( 200, (string) \json_encode( array( 'resources' => array( array( 'type' => 'main', 'format' => 'csv', 'url' => $url, 'last_modified' => '2026-10-26T07:01:00', 'filesize' => \strlen( $csv ) ) ) ) ), array( 'content-type' => 'application/json' ) );
$t->routes[ $url ] = static function ( string $u, array $h ) use ( $csv ): Reponse {
	\preg_match( '/bytes=(\d+)-(\d+)/', $h['Range'], $m );
	$d = (int) $m[1];
	if ( $d >= \strlen( $csv ) ) {
		return new Reponse( 416, '', array(), $u );
	}
	$part = \substr( $csv, $d, (int) $m[2] - $d + 1 );
	return new Reponse( 206, $part, array( 'content-type' => 'text/csv', 'content-range' => 'bytes ' . $d . '-' . ( $d + \strlen( $part ) - 1 ) . '/' . \strlen( $csv ) ), $u );
};
$un = $ko->unites( array( 'force' => true ) );
eq( 'meta', $un[0]['type'], 'Première unité : métadonnées du jeu de données' );
$tous = array();
foreach ( $un as $x ) {
	$r    = $ko->traiter( $x, new Http( $t, $ko->domaines(), 0, 50 ), array(), static fn() => false );
	$tous = \array_merge( $tous, $r['items'] );
}
$ty = \array_count_values( \array_column( $tous, 'type' ) );
eq( 2, $ty['offre'] ?? 0, 'Import complet : deux offres' );
eq( 1, $ty['offres_fin'] ?? 0, 'Signal de fin d\'import (retraits possibles)' );
eq( 1, \count( \array_filter( $t->appels, static fn( $a ) => \str_contains( $a[0], 'offres.csv' ) ) ), 'Fichier minuscule : une seule tranche lue, les suivantes sautées' );

// Fichier inchangé : aucune tranche relue.
$t->appels = array();
$un        = $ko->unites( array() );
$ko->traiter( $un[0], new Http( $t, $ko->domaines(), 0, 50 ), array(), static fn() => false );
foreach ( \array_slice( $un, 1 ) as $x ) {
	$ko->traiter( $x, new Http( $t, $ko->domaines(), 0, 50 ), array(), static fn() => false );
}
eq( 0, \count( \array_filter( $t->appels, static fn( $a ) => \str_contains( $a[0], 'offres.csv' ) ) ), 'Fichier inchangé : lecture évitée' );

// Coupure au milieu d'un caractère accentué : la base n'accepte que de l'UTF-8 valide.
eq( true, \mb_check_encoding( (string) ( $GLOBALS['r18_etat_test']['r18_offres_import']['reste'] ?? '' ), 'UTF-8' ), 'Reste stocké sous forme sûre' );

// Synchronisation manuelle : relecture forcée. En-tête perdu : arrêt propre.
$GLOBALS['r18_etat_test']['r18_offres_import'] = array( 'url' => $url, 'version' => 'x', 'entete' => array( 'a', 'b' ), 'reste' => '', 'termine' => false, 'lignes' => 0, 'offres' => 0, 'import' => '1' );
try {
	$ko->traiter( array( 'url' => 'tranche 2', 'type' => 'tranche', 'tranche' => '1' ), new Http( $t, $ko->domaines(), 0, 50 ), array(), static fn() => false );
	eq( true, false, 'En-tête perdu refusé' );
} catch ( Erreur_Acces $e ) {
	eq( 'structure', $e->categorie, 'En-tête perdu : aucune ligne lue au hasard' );
}
$t->appels = array();
$un        = $ko->unites( array( 'force' => true ) );
foreach ( $un as $x ) {
	$ko->traiter( $x, new Http( $t, $ko->domaines(), 0, 50 ), array(), static fn() => false );
}
eq( 1, \count( \array_filter( $t->appels, static fn( $a ) => \str_contains( $a[0], 'offres.csv' ) ) ), 'Synchronisation manuelle : fichier relu' );

// Casernes : réponse Overpass CSV (tabulations), chemins avec leur centre.
$csv = "node\t123\t49.10000\t1.30000\tCentre d'incendie et de secours de Gaillon\tSDIS 27\t1\true Jacques Sellincourt\t27600\tGaillon\nway\t456\t48.85\t2.35\tCaserne Sévigné\tBrigade de sapeurs-pompiers de Paris\nrelation\tabc\t1\t2\tx\ty\nnode\t789\t43.3\t5.4\t\t\n";
$cas = analyser_casernes_csv( $csv );
eq( 3, \count( $cas ), 'Casernes CSV : trois lignes valides' );
eq( 'n123', $cas[0][3], 'Casernes CSV : identifiant OSM du nœud' );
eq( 'w456', $cas[1][3], 'Casernes CSV : identifiant OSM du chemin' );
eq( 'SDIS 27', $cas[0][4], 'Casernes CSV : exploitant' );
eq( '', $cas[2][2], 'Casernes CSV : caserne sans nom acceptée' );
eq( '1 rue Jacques Sellincourt, 27600 Gaillon', $cas[0][6], 'Casernes CSV : adresse lisible' );
eq( '', $cas[1][6], 'Casernes CSV : adresse absente' );
eq( 120, \count( Connecteur_Casernes_Monde::zones() ), 'Casernes du monde : 120 carreaux' );

echo "\n" . $GLOBALS['ok'] . ' vérifications réussies, ' . $GLOBALS['fail'] . " échec(s).\n";
exit( $GLOBALS['fail'] > 0 ? 1 : 0 );
