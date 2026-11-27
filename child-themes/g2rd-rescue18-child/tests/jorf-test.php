<?php
/**
 * Tests du connecteur Journal officiel (sans WordPress). Lancer : php tests/jorf-test.php
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18\Juridique;

use G2RD_Rescue18\Veille\Http;
use G2RD_Rescue18\Veille\Reponse;
use G2RD_Rescue18\Veille\Transport;
use G2RD_Rescue18\Veille\Erreur_Acces;

require __DIR__ . '/../inc/veille/normalisation.php';
require __DIR__ . '/../inc/veille/http.php';
require __DIR__ . '/../inc/veille/connecteurs.php';
require __DIR__ . '/../inc/veille/moteur.php';
require __DIR__ . '/../inc/annuaire/territoires.php';
require __DIR__ . '/../inc/annuaire/connecteurs.php';
require __DIR__ . '/../inc/juridique/jorf.php';

$ok = 0; $ko = 0;
$eq = static function ( $a, $b, string $l ) use ( &$ok, &$ko ): void {
	if ( $a === $b ) { ++$ok; } else { ++$ko; echo "  ÉCHEC : $l (attendu " . \var_export( $a, true ) . ', obtenu ' . \var_export( $b, true ) . ")\n"; }
};

/** Entrée tar (ustar). */
$entree = static function ( string $nom, string $data, string $type = '0' ): string {
	$pref = '';
	if ( \strlen( $nom ) > 100 ) {
		$pos  = \strrpos( \substr( $nom, 0, 155 ), '/' );
		$pref = \substr( $nom, 0, $pos );
		$nom  = \substr( $nom, $pos + 1 );
	}
	$h = \str_pad( $nom, 100, "\0" ) . \str_pad( '0000644', 8, "\0" ) . \str_pad( '0', 8, "\0" ) . \str_pad( '0', 8, "\0" )
		. \str_pad( \decoct( \strlen( $data ) ), 11, '0', STR_PAD_LEFT ) . "\0" . \str_pad( '0', 12, "\0" ) . '        ' . $type
		. \str_pad( '', 100, "\0" ) . "ustar\0" . '00' . \str_pad( '', 80, "\0" ) . \str_pad( $pref, 155, "\0" );
	$h   = \str_pad( $h, 512, "\0" );
	$sum = 0;
	for ( $i = 0; $i < 512; $i++ ) { $sum += \ord( $h[ $i ] ); }
	$h = \substr_replace( $h, \str_pad( \decoct( $sum ), 6, '0', STR_PAD_LEFT ) . "\0 ", 148, 8 );
	return $h . \str_pad( $data, (int) \ceil( \strlen( $data ) / 512 ) * 512, "\0" );
};
$xml = static fn( string $id, string $nature, string $titre, string $num = '' ): string => "<?xml version=\"1.0\"?>\n<TEXTE_VERSION><META><META_COMMUN><ID>$id</ID><NATURE>$nature</NATURE></META_COMMUN><META_SPEC><META_TEXTE_CHRONICLE><NUM>$num</NUM><NOR>INTE2600001D</NOR><DATE_PUBLI>2026-10-30</DATE_PUBLI><DATE_TEXTE>2026-10-28</DATE_TEXTE><ORIGINE_PUBLI>JORF n°0254 du 30 octobre 2026</ORIGINE_PUBLI></META_TEXTE_CHRONICLE><META_TEXTE_VERSION><TITRE>x</TITRE><TITREFULL>$titre</TITREFULL><MINISTERE>Ministère de l'intérieur</MINISTERE></META_TEXTE_VERSION></META_SPEC></META></TEXTE_VERSION>";
$base = '20261030-214909/jorf/global/texte/version/JORF/TEXT/00/00/55/02/93/';
$tar  = $entree( '20261030-214909/jorf/global/conteneur/JORF/CONT/00/00/JORFCONT000001.xml', \str_repeat( 'x', 2000 ) )
	. $entree( $base . 'JORFTEXT000055000001.xml', $xml( 'JORFTEXT000055000001', 'DECRET', 'Décret n° 2026-990 du 28 octobre 2026 relatif aux sapeurs-pompiers volontaires (1)', '2026-990' ) )
	. $entree( $base . 'JORFTEXT000055000002.xml', $xml( 'JORFTEXT000055000002', 'LOI', 'LOI n° 2026-1008 du 29 octobre 2026 portant reconnaissance de la Nation envers les rapatriés', '2026-1008' ) )
	. $entree( $base . 'JORFTEXT000055000003.xml', $xml( 'JORFTEXT000055000003', 'ARRETE', 'Arrêté du 27 octobre 2026 portant nomination au grade de colonel de sapeurs-pompiers professionnels' ) )
	. $entree( '20261030-214909/jorf/global/texte/struct/JORF/TEXT/00/00/55/02/93/JORFTEXT000055000001.xml', '<TEXTELR/>' )
	. $entree( $base . 'JORFTEXT000040000009.xml', \str_replace( '2026-10-30', '2021-12-28', $xml( 'JORFTEXT000040000009', 'ARRETE', 'Arrêté du 23 décembre 2021 relatif aux sapeurs-pompiers' ) ) )
	. $entree( $base . 'JORFTEXT000055000004.xml', $xml( 'JORFTEXT000055000004', 'ARRETE', 'Arrêté du 28 octobre 2026 relatif à la sécurité contre les risques d\'incendie dans les établissements recevant du public' ) )
	. \str_repeat( "\0", 1024 );
$f = \tempnam( \sys_get_temp_dir(), 't' );
\file_put_contents( $f, \gzencode( $tar ) );

echo "== Archive ==\n";
$a = lire_archive( $f, static fn( array $t ): bool => pertinent( $t, mots_defaut(), exclus_defaut() ) );
$eq( 5, $a['lus'], 'Cinq textes lus (struct et conteneur ignorés)' );
$eq( 3, \count( $a['textes'] ), 'Deux textes retenus (loi hors sujet et nomination écartées)' );
$eq( 'JORFTEXT000055000001', $a['textes'][0]['id'], 'Identifiant JORF' );
$eq( '2026-990', $a['textes'][0]['num'], 'Numéro' );
$eq( 'Décret n° 2026-990 du 28 octobre 2026 relatif aux sapeurs-pompiers volontaires', $a['textes'][0]['titre'], 'Renvoi (1) retiré' );
$eq( 'Ministère de l\'intérieur', $a['textes'][0]['ministere'], 'Ministère' );
\file_put_contents( $f, $tar );
$eq( 3, \count( lire_archive( $f, static fn( array $t ): bool => pertinent( $t, mots_defaut(), exclus_defaut() ) )['textes'] ), 'Archive déjà décompressée lisible aussi' );


echo "== Tri des textes (titres réels) ==\n";
$eq( true, pertinent( array( 'nature' => 'ARRETE', 'titre' => 'Arrêté du 29 septembre 2026 fixant le montant de l\'indemnité horaire de base des sapeurs-pompiers volontaires' ), mots_defaut(), exclus_defaut() ), 'Gardé : Arrêté du 29 septembre 2026 fixant le montant de l\'indemnité horaire ' );
$eq( true, pertinent( array( 'nature' => 'ARRETE', 'titre' => 'Arrêté du 1er octobre 2026 portant ouverture du concours interne de colonel de sapeurs-pompiers professionnels (session 2027)' ), mots_defaut(), exclus_defaut() ), 'Gardé : Arrêté du 1er octobre 2026 portant ouverture du concours interne de co' );
$eq( true, pertinent( array( 'nature' => 'ARRETE', 'titre' => 'Arrêté du 31 juillet 2026 portant reconnaissance de sinistre de grande ampleur concernant les feux de forêt dans les départements de Gironde et des Landes' ), mots_defaut(), exclus_defaut() ), 'Gardé : Arrêté du 31 juillet 2026 portant reconnaissance de sinistre de grande' );
$eq( true, pertinent( array( 'nature' => 'ARRETE', 'titre' => 'Arrêté du 7 juillet 2026 fixant les références techniques nationales dans le domaine de la formation aux premiers secours' ), mots_defaut(), exclus_defaut() ), 'Gardé : Arrêté du 7 juillet 2026 fixant les références techniques nationales d' );
$eq( true, pertinent( array( 'nature' => 'ARRETE', 'titre' => 'Arrêté du 13 juillet 2026 modifiant l\'arrêté du 30 décembre 2011 portant règlement de sécurité pour la construction des immeubles de grande hauteur et leur protection contre les risques d\'incendie' ), mots_defaut(), exclus_defaut() ), 'Gardé : Arrêté du 13 juillet 2026 modifiant l\'arrêté du 30 décembre 2011 port' );
$eq( true, pertinent( array( 'nature' => 'DECRET', 'titre' => 'Décret n° 2025-1167 du 5 décembre 2025 relatif à l\'obligation d\'équipement des établissements recevant du public d\'un défibrillateur automatisé externe' ), mots_defaut(), exclus_defaut() ), 'Gardé : Décret n° 2025-1167 du 5 décembre 2025 relatif à l\'obligation d\'équi' );
$eq( true, pertinent( array( 'nature' => 'LOI', 'titre' => 'Loi n° 2026-247 du 7 avril 2026 relative aux missions des professionnels de santé, vétérinaires, psychothérapeutes et psychologues des services d\'incendie et de secours' ), mots_defaut(), exclus_defaut() ), 'Gardé : Loi n° 2026-247 du 7 avril 2026 relative aux missions des professionne' );
$eq( true, pertinent( array( 'nature' => 'ARRETE', 'titre' => 'Arrêté du 13 juillet 2026 modifiant l\'arrêté du 24 mai 2023 fixant le nombre maximal de primes de parcours professionnels des sous-officiers de la brigade de sapeurs-pompiers de Paris' ), mots_defaut(), exclus_defaut() ), 'Gardé : Arrêté du 13 juillet 2026 modifiant l\'arrêté du 24 mai 2023 fixant le' );
$eq( false, pertinent( array( 'nature' => 'ARRETE', 'titre' => 'Arrêté du 24 mars 2026 portant habilitation de la brigade de sapeurs-pompiers de Paris pour les formations aux premiers secours' ), mots_defaut(), exclus_defaut() ), 'Écarté : Arrêté du 24 mars 2026 portant habilitation de la brigade de sapeurs-p' );
$eq( false, pertinent( array( 'nature' => 'ARRETE', 'titre' => 'Arrêté du 16 septembre 2026 portant habilitation d\'un bureau de vérification pour l\'application du règlement de sécurité contre les risques d\'incendie et de panique dans les chapiteaux' ), mots_defaut(), exclus_defaut() ), 'Écarté : Arrêté du 16 septembre 2026 portant habilitation d\'un bureau de vérif' );
$eq( false, pertinent( array( 'nature' => 'ARRETE', 'titre' => 'Arrêté du 3 juin 2026 portant agrément interdépartemental de sécurité civile pour la Fédération nationale des sapeurs-pompiers de France' ), mots_defaut(), exclus_defaut() ), 'Écarté : Arrêté du 3 juin 2026 portant agrément interdépartemental de sécurité ' );
$eq( false, pertinent( array( 'nature' => 'ARRETE', 'titre' => 'Arrêté du 7 janvier 2026 portant dérogation au profit du service départemental d\'incendie et de secours de la Gironde' ), mots_defaut(), exclus_defaut() ), 'Écarté : Arrêté du 7 janvier 2026 portant dérogation au profit du service dépar' );
$eq( false, pertinent( array( 'nature' => 'ARRETE', 'titre' => 'Arrêté du 5 octobre 2026 fixant les montants de la prime de déminage versée aux personnels démineurs de la sécurité civile' ), mots_defaut(), exclus_defaut() ), 'Écarté : Arrêté du 5 octobre 2026 fixant les montants de la prime de déminage v' );
$eq( false, pertinent( array( 'nature' => 'ARRETE', 'titre' => 'Arrêté du 14 octobre 2026 fixant le tarif prévu à l\'article R. 543-310-2 du code de l\'environnement pour la redevance relative aux actions de communication pour la prévention des incendies de forêt' ), mots_defaut(), exclus_defaut() ), 'Écarté : Arrêté du 14 octobre 2026 fixant le tarif prévu à l\'article R. 543-31' );
$eq( false, pertinent( array( 'nature' => 'DECRET', 'titre' => 'Décret n° 2025-1134 du 27 novembre 2025 relatif aux qualifications en sauvetage aquatique, en natation et en secourisme requises des personnels relevant du ministre chargé de l\'éducation nationale' ), mots_defaut(), exclus_defaut() ), 'Écarté : Décret n° 2025-1134 du 27 novembre 2025 relatif aux qualifications en ' );
$eq( false, pertinent( array( 'nature' => 'ARRETE', 'titre' => 'Arrêté du 22 janvier 2026 portant mesures temporaires de dérogation aux règles d\'accessibilité applicables à la reconstruction, à Mayotte, des établissements recevant du public' ), mots_defaut(), exclus_defaut() ), 'Écarté : Arrêté du 22 janvier 2026 portant mesures temporaires de dérogation au' );
$eq( false, pertinent( array( 'nature' => 'ARRETE', 'titre' => 'Arrêté du 27 juillet 2026 portant dérogation temporaire aux règles en matière de temps de conduite et de repos pour les opérations de transport nécessaires à la lutte contre les feux de forêt' ), mots_defaut(), exclus_defaut() ), 'Écarté : Arrêté du 27 juillet 2026 portant dérogation temporaire aux règles en ' );
$eq( false, pertinent( array( 'nature' => 'LOI', 'titre' => 'LOI n° 2026-1008 du 29 octobre 2026 portant reconnaissance de la Nation envers les rapatriés d\'Indochine' ), mots_defaut(), exclus_defaut() ), 'Écarté : LOI n° 2026-1008 du 29 octobre 2026 portant reconnaissance de la Natio' );

echo "== Titres ==\n";
$eq( array( 'Loi n° 2026-1008 du 29 octobre 2026', 'Portant reconnaissance' ), couper_titre( 'LOI n° 2026-1008 du 29 octobre 2026 portant reconnaissance' ), 'Loi en casse normale, objet séparé' );
$eq( array( 'Arrêté du 1er juin 2026', 'Relatif aux SDIS' ), couper_titre( 'Arrêté du 1er juin 2026 relatif aux SDIS' ), 'Premier du mois' );

echo "== Index et connecteur ==\n";
$idx = '<a href="JORF_20261030-214909.tar.gz">x</a><a href="JORF_20261031-002427.tar.gz">x</a><a href="JORF_20250713-002503.tar.gz">x</a><a href="JORF_20250501-002503.tar.gz">x</a>';
$l   = archives( $idx );
$eq( 'JORF_20261031-002427.tar.gz', $l[0]['nom'], 'Plus récente d\'abord' );
$eq( '2026-10-31', $l[0]['date'], 'Date de l\'archive' );

$GLOBALS['r18_etat_test'] = array();
class T implements Transport {
	public array $r = array();
	public function get( string $url, array $e, int $d, int $t ): Reponse {
		if ( ! isset( $this->r[ $url ] ) ) { throw new Erreur_Acces( 'reseau', $url ); }
		return new Reponse( 200, $this->r[ $url ][0], array( 'content-type' => $this->r[ $url ][1] ), $url );
	}
	public function pause( float $s ): void {}
}
$t = new T();
$t->r[ INDEX_JORF ] = array( $idx, 'text/html' );
$t->r[ INDEX_JORF . 'JORF_20261031-002427.tar.gz' ] = array( \gzencode( \str_repeat( "\0", 1024 ) ), 'application/x-gzip' );
$t->r[ INDEX_JORF . 'JORF_20261030-214909.tar.gz' ] = array( \gzencode( $tar ), 'application/x-gzip' );
$t->r[ INDEX_JORF . 'JORF_20250713-002503.tar.gz' ] = array( \gzencode( \str_repeat( "\0", 1024 ) ), 'application/x-gzip' );
$k  = new Connecteur_JORF();
$un = $k->unites( array() );
$items = array();
foreach ( $un as $u ) {
	$items = \array_merge( $items, $k->traiter( $u, new Http( $t, $k->domaines(), 0, 80 ), array( 'depuis' => '2025-06-01' ), static fn() => false )['items'] );
}
$eq( 2, \count( $items ), 'Deux fiches proposées (texte de 2021 republié écarté)' );
$eq( 'decret', $items[0]['nature'], 'Nature du site' );
$eq( 'https://www.legifrance.gouv.fr/jorf/id/JORFTEXT000055000001', $items[0]['url'], 'Lien Légifrance' );
$eq( 3, \count( $GLOBALS['r18_etat_test']['r18_jorf']['faites'] ), 'Trois archives marquées lues (celle de mai 2025 hors période)' );
$items = array();
foreach ( $un as $u ) {
	$items = \array_merge( $items, $k->traiter( $u, new Http( $t, $k->domaines(), 0, 80 ), array( 'depuis' => '2025-06-01' ), static fn() => false )['items'] );
}
$eq( 0, \count( $items ), 'Second passage : aucune archive relue' );

@\unlink( $f );
echo "\n$ok vérifications réussies, $ko échec(s).\n";
exit( $ko > 0 ? 1 : 0 );
