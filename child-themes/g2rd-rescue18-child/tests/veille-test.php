<?php
/**
 * Tests automatisés de la veille documentaire (sans WordPress).
 *
 * Lancer : php tests/veille-test.php
 *
 * Couvre les analyseurs sur des extraits réels des sources (fixtures/,
 * relevés le 30 octobre 2026), la normalisation, le rapprochement entre
 * sources, la détection de versions et les 20 scénarios du cahier des
 * charges qui ne dépendent pas d'un serveur WordPress réel. Les scénarios
 * cron et URL historiques se vérifient sur le site (voir README).
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18\Veille;

require __DIR__ . '/../inc/veille/normalisation.php';
require __DIR__ . '/../inc/veille/http.php';
require __DIR__ . '/../inc/veille/connecteurs.php';
require __DIR__ . '/../inc/veille/moteur.php';

/* Mini-cadre de test --------------------------------------------------- */

$GLOBALS['r18_ok']   = 0;
$GLOBALS['r18_fail'] = array();

function ok( bool $cond, string $label ): void {
	if ( $cond ) {
		++$GLOBALS['r18_ok'];
	} else {
		$GLOBALS['r18_fail'][] = $label;
		echo "  ÉCHEC : $label\n";
	}
}

function eq( $attendu, $obtenu, string $label ): void {
	ok( $attendu === $obtenu, $label . ' (attendu ' . \var_export( $attendu, true ) . ', obtenu ' . \var_export( $obtenu, true ) . ')' );
}

function section( string $t ): void {
	echo "\n== $t ==\n";
}

function fx( string $f ): string {
	return (string) \file_get_contents( __DIR__ . '/fixtures/' . $f );
}

/* Doublures ------------------------------------------------------------- */

/**
 * Transport rejouant des réponses enregistrées.
 */
class Transport_Fixture implements Transport {

	/** @var array<string,array{0:int,1:string,2:array<string,string>}> */
	public array $routes = array();

	/** @var string[] */
	public array $appels = array();

	public float $attente = 0.0;

	public function get( string $url, array $entetes, int $delai, int $taille ): Reponse {
		$this->appels[] = $url;
		if ( ! isset( $this->routes[ $url ] ) ) {
			throw new Erreur_Acces( 'reseau', 'Source injoignable : ' . $url );
		}
		[ $s, $b, $h ] = $this->routes[ $url ];
		if ( isset( $entetes['If-None-Match'] ) && ( $h['etag'] ?? '' ) === $entetes['If-None-Match'] ) {
			return new Reponse( 304, '', $h, $url );
		}
		return new Reponse( $s, $b, $h, $url );
	}

	public function pause( float $s ): void {
		$this->attente += $s;
	}
}

/**
 * Magasin en mémoire.
 */
class Store_Memoire implements Store {

	/** @var array<int,array<string,mixed>> */
	public array $docs = array();

	/** @var array<int,array<string,mixed>> */
	public array $propositions = array();

	/** @var array<string,int|string> */
	public array $decisions = array();

	private int $seq = 100;

	public function par_ref( string $source, string $ref ): int {
		foreach ( $this->docs as $id => $d ) {
			if ( ( $d['meta'][ 'r18_ref_' . $source ] ?? null ) === $ref ) {
				return $id;
			}
		}
		return 0;
	}

	public function par_cle( string $cle ): int {
		foreach ( $this->docs as $id => $d ) {
			if ( 'document' === $d['kind'] && ( $d['meta']['r18_cle_doc'] ?? '' ) === $cle ) {
				return $id;
			}
		}
		return 0;
	}

	public function cles( string $type ): array {
		$out = array();
		foreach ( $this->docs as $id => $d ) {
			$c = (string) ( $d['meta']['r18_cle_doc'] ?? '' );
			if ( 'document' === $d['kind'] && \str_starts_with( $c, $type . '|' ) ) {
				$out[ $id ] = $c;
			}
		}
		return $out;
	}

	public function lire( int $id ): array {
		$d = $this->docs[ $id ];
		return array(
			'titre'        => $d['titre'],
			'extrait'      => $d['extrait'],
			'sync_titre'   => (string) ( $d['meta']['r18_sync_titre'] ?? '' ),
			'sync_extrait' => (string) ( $d['meta']['r18_sync_extrait'] ?? '' ),
			'refs'         => (array) ( $d['meta']['r18_refs'] ?? array() ),
			'versions'     => (array) ( $d['meta']['r18_versions'] ?? array() ),
			'statut_post'  => $d['statut'],
			'editeur'      => (string) ( $d['meta']['r18_editeur'] ?? '' ),
		);
	}

	public function creer( array $doc ): int {
		$id                = ++$this->seq;
		$this->docs[ $id ] = $doc;
		return $id;
	}

	public function ecrire( int $id, array $champs ): void {
		foreach ( array( 'titre', 'extrait' ) as $k ) {
			if ( isset( $champs[ $k ] ) ) {
				$this->docs[ $id ][ $k ] = $champs[ $k ];
			}
		}
		foreach ( (array) ( $champs['meta'] ?? array() ) as $k => $v ) {
			$this->docs[ $id ]['meta'][ $k ] = $v;
		}
		foreach ( (array) ( $champs['termes'] ?? array() ) as $tax => $slugs ) {
			$this->docs[ $id ]['termes'][ $tax ] = \array_values( \array_unique( \array_merge( (array) ( $this->docs[ $id ]['termes'][ $tax ] ?? array() ), $slugs ) ) );
		}
	}

	public function decision( string $source, string $ref ) {
		return $this->decisions[ $source . ':' . $ref ] ?? null;
	}

	public function proposer( array $p ): void {
		foreach ( $this->propositions as $q ) {
			if ( $q['source'] === $p['source'] && $q['ref'] === $p['ref'] ) {
				return;
			}
		}
		$this->propositions[] = $p;
	}
}

$DG  = 'https://www.securite-civile.interieur.gouv.fr/documentation/doctrines-et-techniques-professionnelles/';
$GTO = $DG . 'sapeurs-pompiers-guides-de-techniques-operationnelles.html';
$GDO = $DG . 'sapeurs-pompiers-guide-de-doctrine-operationnelle.html';
$PN  = 'https://pnrs.ensosp.fr/default/gdo-gto.aspx?_lg=fr-FR';
$F1  = 'https://pnrs.ensosp.fr/Portal/Recherche/Search.svc/SearchRss?key=1c430757128651816fc793c693f832f4&useSearchSort=false&useSearchResultSize=true';
$F2  = 'https://pnrs.ensosp.fr/Portal/Recherche/Search.svc/SearchRss?key=b33897e54ae9ba671aa070e2dbeacd51&useSearchSort=false&useSearchResultSize=true';
$XML = array( 'content-type' => 'application/xml' );
$HTML = array( 'content-type' => 'text/html; charset=UTF-8' );

function http_pour( Connecteur $k, Transport $t ): Http {
	return new Http( $t, $k->domaines(), $k->pause(), $k->plafond() );
}

/* Normalisation --------------------------------------------------------- */

section( 'Normalisation' );
eq( cle( 'GDO - Interventions en présence de gaz', 'gdo' ), cle( 'Guide de doctrine opérationnelle (GDO) : interventions en présence de gaz', 'gdo' ), 'Même clé DGSCGC / PNRS (GDO gaz)' );
eq( cle( 'GTO - Secours en milieux effondrés ou instables', 'gto' ), cle( 'Guide de tehcniques opérationnelles (GTO) : secours en milieux effondrés ou instables', 'gto' ), 'Même clé malgré la coquille « tehcniques »' );
eq( 'gto', type_depuis_titre( 'Guide de tehcniques opérationnelles (GTO) : x' ), 'Type GTO malgré la coquille' );
eq( 'pex', type_depuis_titre( 'PIAS SIS 2026 : chute d\'un sapeur-pompier', 'retex' ), 'PIAS classé PEX' );
eq( 'pex', type_depuis_titre( 'Partage d\'Expérience du SDIS du Val d\'Oise', 'retex' ), 'Partage d\'expérience classé PEX' );
eq( 'autre', type_depuis_titre( 'Les GTO et GDO plus' === '' ? '' : 'Bilan annuel', 'autre' ), 'Titre neutre : type par défaut' );
eq( 'V2', version_depuis( 'GTO-Engagement-Milieux-Vicies-V2.pdf' ), 'Version dans un nom de fichier' );
eq( 'V4', version_depuis( 'GDO%20Sauvegarde%20du%20patrimoine%20V4%20juillet%202026%20sign%C3%A9.pdf' ), 'Version dans un nom encodé' );
eq( '', version_depuis( 'GTO-Ventilation-Operationnelle.pdf' ), 'Pas de version inventée' );
eq( 'dgscgc', editeur_depuis( 'Livre | Turci | Direction Générale de la Sécurité Civile et de la Gestion des Crises | 2026' ), 'Éditeur DGSCGC reconnu' );
eq( '2026-10-27', date_fr( ' 27 octobre 2026 ' ), 'Date française en toutes lettres' );
eq( '2024-03-19', date_fr( '19/03/2024' ), 'Date française numérique' );
ok( ressemblance( 'gdo|interventions incendies structures', 'gdo|incendies structures' ) >= 72, 'Ressemblance forte détectée' );
eq( 0.0, ressemblance( 'gdo|incendies structures', 'gto|incendies structures' ), 'Types différents jamais rapprochés' );

/* Analyseurs ------------------------------------------------------------ */

section( 'Analyseurs' );
$g = analyser_dgscgc( fx( 'dgscgc-gto.html' ), $GTO, 'gto' );
eq( 5, \count( $g ), 'DGSCGC : 5 guides lus, liens de partage ignorés' );
eq( 'GTO - Engagement en milieu vicié', $g[0]['titre'], 'DGSCGC : titre exact sans « Télécharger » ni « PDF »' );
eq( 'https://www.securite-civile.interieur.gouv.fr/sites/securitecivile/files/medias/documents/2026-07/GTO-Engagement-Milieux-Vicies-V2.pdf', $g[0]['pdf'], 'DGSCGC : URL du PDF absolue' );
eq( 'V2', $g[0]['version'], 'DGSCGC : version lue dans le fichier' );
eq( '2026-07-01', $g[0]['date_maj'], 'DGSCGC : mois de mise en ligne' );
eq( 'gto', $g[3]['type'], 'DGSCGC : type GTO' );

$flux = flux_pnrs( fx( 'pnrs-gdo-gto.html' ), $PN );
eq( array( $F1, $F2 ), $flux, 'PNRS : clés RSS relues sur la page, &amp; décodés' );
$p = analyser_flux_pnrs( fx( 'pnrs-flux-gdo.xml' ), 'auto' );
eq( 3, \count( $p ), 'PNRS : 3 éléments lus' );
eq( '104916', $p[0]['ref'], 'PNRS : identifiant SYRACUSE' );
eq( 'dgscgc', $p[0]['editeur'], 'PNRS : éditeur DGSCGC lu dans la notice' );
eq( '2026-10-16', $p[0]['date'], 'PNRS : date sans décalage de fuseau' );
ok( \str_starts_with( $p[0]['resume'], 'Chapitre 1' ), 'PNRS : résumé nettoyé de « Sommaire : »' );
eq( 'autre', $p[2]['type'], 'PNRS : article de présentation hors types suivis' );
$bad = false;
try {
	analyser_flux_pnrs( '<html><body>Erreur</body></html>', 'gdo' );
} catch ( Erreur_Acces $e ) {
	$bad = 'flux' === $e->categorie;
}
ok( $bad, 'PNRS : un XML qui n\'est pas un flux RSS est refusé' );
$h = analyser_page_pnrs( fx( 'pnrs-retex.html' ), 'https://pnrs.ensosp.fr/retex-pias.aspx', 'retex' );
eq( 2, \count( $h ), 'PNRS repli HTML : 2 notices' );
eq( 'pex', $h[0]['type'], 'PNRS repli HTML : PIAS classé PEX' );

$l = analyser_liste_ensosp( fx( 'ensosp-liste.html' ), 'https://www.ensosp.fr/actualites' );
eq( 3, \count( $l ), 'ENSOSP : 3 actualités, lien « Lire l\'actualité » dédoublonné' );
eq( '2026-10-27', $l[0]['date'], 'ENSOSP : date de la carte' );
$c2 = completer_ensosp( $l[1], fx( 'ensosp-cotfer.html' ) );
eq( 'Les caissons feu du plateau technique de Vitrolles prennent du service !', $c2['resume'], 'ENSOSP : résumé public og:description' );
eq( '2026-09-25', $c2['date_maj'], 'ENSOSP : date de mise à jour' );

/* HTTP ------------------------------------------------------------------ */

section( 'Accès HTTP' );
$t  = new Transport_Fixture();
$hp = new Http( $t, array( 'pnrs.ensosp.fr' ), 4.0, 3 );
$err = static function ( callable $f ): string {
	try {
		$f();
	} catch ( Erreur_Acces $e ) {
		return $e->categorie;
	}
	return '';
};
eq( 'domaine', $err( fn() => $hp->get( 'https://evil.example/x' ) ), 'Domaine non autorisé refusé' );
eq( 'domaine', $err( fn() => $hp->get( 'http://pnrs.ensosp.fr/x' ) ), 'HTTP sans TLS refusé' );
$t->routes['https://pnrs.ensosp.fr/a'] = array( 302, '', array( 'location' => 'https://evil.example/b' ) );
eq( 'domaine', $err( fn() => $hp->get( 'https://pnrs.ensosp.fr/a' ) ), 'Redirection vers un domaine tiers refusée' );
$t->routes['https://pnrs.ensosp.fr/b'] = array( 200, '<x/>', array( 'content-type' => 'application/pdf' ) );
eq( 'type', $err( fn() => $hp->get( 'https://pnrs.ensosp.fr/b' ) ), 'Type MIME inattendu refusé' );
$hp2 = new Http( $t, array( 'pnrs.ensosp.fr' ), 4.0, 1 );
$t->routes['https://pnrs.ensosp.fr/c'] = array( 200, 'ok', $HTML );
$hp2->get( 'https://pnrs.ensosp.fr/c' );
eq( 'limite', $err( fn() => $hp2->get( 'https://pnrs.ensosp.fr/c' ) ), 'Plafond de requêtes par cycle' );
$t->attente = 0.0;
$hp3        = new Http( $t, array( 'pnrs.ensosp.fr' ), 4.0, 10 );
$hp3->get( 'https://pnrs.ensosp.fr/c' );
$hp3->get( 'https://pnrs.ensosp.fr/c' );
ok( $t->attente > 3.5, 'Temporisation de 4 s entre deux requêtes au même hôte' );
$t->routes['https://pnrs.ensosp.fr/e'] = array( 200, 'x', array( 'content-type' => 'text/html', 'etag' => '"abc"' ) );
$hp3->get( 'https://pnrs.ensosp.fr/e', array( 'text/html' ), true );
eq( 304, $hp3->get( 'https://pnrs.ensosp.fr/e', array( 'text/html' ), true )->statut, 'Requête conditionnelle ETag : 304' );

/* Scénarios ------------------------------------------------------------- */

$dg = connecteurs()['dgscgc'];
$pn = connecteurs()['pnrs'];
$en = connecteurs()['ensosp'];

section( 'Scénario 1 : premier import' );
$store = new Store_Memoire();
$t     = new Transport_Fixture();
$t->routes[ $GTO ] = array( 200, fx( 'dgscgc-gto.html' ), $HTML );
$t->routes[ $GDO ] = array( 200, fx( 'dgscgc-gdo.html' ), $HTML );
$ctx = new Contexte( $store, 'auto', false, '2026-10-30' );
$r   = executer_unite( $dg, array( 'url' => $GTO, 'type' => 'gto' ), http_pour( $dg, $t ), array(), $ctx );
eq( 5, $r['stats']['nouveau'], '5 fiches GTO créées' );
$id = $store->par_ref( 'dgscgc', cle( 'GTO - Engagement en milieu vicié', 'gto' ) );
ok( $id > 0, 'Fiche retrouvée par sa référence DGSCGC' );
eq( 'publish', $store->docs[ $id ]['statut'], 'Publication automatique (scénario 18)' );
eq( array( 'gto' ), $store->docs[ $id ]['termes']['r18_type_doc'], 'Rubrique GTO' );
eq( 'incendie', $store->docs[ $id ]['termes']['r18_specialite'][0] ?? '', 'Spécialité déduite' );

section( 'Scénario 2 : synchronisation sans nouveauté' );
$r = executer_unite( $dg, array( 'url' => $GTO, 'type' => 'gto' ), http_pour( $dg, $t ), array(), $ctx );
eq( 5, $r['stats']['identique'], 'Aucune création, aucune version' );
eq( 5, \count( $store->docs ), 'Pas de doublon' );

section( 'Scénario 3 : nouveau GDO' );
$r = executer_unite( $dg, array( 'url' => $GDO, 'type' => 'gdo' ), http_pour( $dg, $t ), array(), $ctx );
eq( 3, $r['stats']['nouveau'], '3 GDO créés' );

section( 'Scénario 4 : nouvelle version d\'un GTO' );
$t->routes[ $GTO ] = array( 200, \str_replace( 'GTO-Ventilation-Operationnelle.pdf', 'GTO-Ventilation-Operationnelle-V3.pdf', fx( 'dgscgc-gto.html' ) ), $HTML );
$r   = executer_unite( $dg, array( 'url' => $GTO, 'type' => 'gto' ), http_pour( $dg, $t ), array(), $ctx );
eq( 1, $r['stats']['version'], 'Une version détectée' );
$vid = $store->par_ref( 'dgscgc', cle( 'GTO - Ventilation opérationnelle', 'gto' ) );
eq( 'V3', $store->docs[ $vid ]['meta']['r18_version'], 'Version V3 enregistrée' );
eq( 'mis-a-jour', $store->docs[ $vid ]['meta']['r18_statut'], 'Statut « Mis à jour »' );
eq( 2, \count( $store->docs[ $vid ]['meta']['r18_versions'] ), 'Historique des versions conservé' );
ok( \str_ends_with( $store->docs[ $vid ]['meta']['r18_pdf_url'], 'V3.pdf' ), 'Lien PDF mis à jour (lien seulement)' );

section( 'Scénario 7 : doublon DGSCGC et PNRS' );
$t->routes[ $PN ] = array( 200, fx( 'pnrs-gdo-gto.html' ), $HTML );
$t->routes[ $F1 ] = array( 200, fx( 'pnrs-flux-gdo.xml' ), $XML );
$t->routes[ $F2 ] = array( 200, fx( 'pnrs-flux-gto.xml' ), $XML );
$avant = \count( $store->docs );
$r     = executer_unite( $pn, array( 'url' => $PN, 'type' => 'auto' ), http_pour( $pn, $t ), array(), $ctx );
$gaz   = $store->par_ref( 'dgscgc', cle( 'GDO - Interventions en présence de gaz', 'gdo' ) );
eq( $gaz, $store->par_ref( 'pnrs', '40001' ), 'Le GDO gaz du PNRS rejoint la fiche DGSCGC' );
eq( 'dgscgc', $store->docs[ $gaz ]['meta']['r18_editeur'], 'DGSCGC reste éditeur officiel' );
ok( \str_contains( $store->docs[ $gaz ]['meta']['r18_source_url'], 'securite-civile' ), 'Lien principal DGSCGC conservé' );
ok( isset( $store->docs[ $gaz ]['meta']['r18_refs']['pnrs'] ), 'Référence PNRS ajoutée comme source complémentaire' );
ok( \in_array( 'ensosp-pnrs', $store->docs[ $gaz ]['termes']['r18_organisme'], true ), 'Organisme PNRS ajouté' );
eq( 1, $r['stats']['ecarte'], 'Article « Les GTO et GDO » écarté' );
$vic = $store->par_ref( 'dgscgc', cle( 'GTO - Engagement en milieu vicié', 'gto' ) );
eq( $vic, $store->par_ref( 'pnrs', '1658' ), 'GTO milieu vicié rapproché (flux GTO)' );
eq( 'GTO - Engagement en milieu vicié', $store->docs[ $vic ]['titre'], 'Titre DGSCGC conservé face au PNRS' );
ok( \str_contains( $store->docs[ $vic ]['extrait'], 'CONNAISSANCES' ), 'Résumé PNRS ajouté à une fiche sans extrait' );
$nouveaux = \count( $store->docs ) - $avant;
eq( 1, $nouveaux, 'Seul le GTO « milieux effondrés » (absent de la DGSCGC ici) crée une fiche' );
ok( $store->par_ref( 'pnrs', '104916' ) > 0, 'GDO patrimoine PNRS rapproché du GDO patrimoine DGSCGC' );

section( 'Rapprochement ambigu' );
$store2 = new Store_Memoire();
$ctx2   = new Contexte( $store2, 'auto', false, '2026-10-30' );
traiter( analyser_dgscgc( '<main><a class="fr-link--downloads" href="/a.pdf">GDO - Interventions sur les incendies de structures</a></main>', $GDO, 'gdo' )[0], $ctx2 );
$amb = array( 'source' => 'pnrs', 'ref' => '777', 'titre' => 'Guide de doctrine opérationnelle (GDO) : incendies de structures', 'type' => 'gdo', 'editeur' => 'dgscgc', 'url' => 'https://pnrs.ensosp.fr/Default/doc/SYRACUSE/777/x', 'pdf' => '', 'date' => '2020-01-01', 'date_maj' => '', 'version' => '', 'resume' => '', 'image' => '', 'categorie' => '', 'signal' => '' );
$res    = traiter( $amb, $ctx2 );
eq( 'a_valider', $res['action'], 'Ressemblance non exacte : proposée à validation' );
eq( 1, \count( $store2->docs ), 'Aucune fiche créée avant validation' );
eq( 1, \count( $store2->propositions ), 'Une proposition enregistrée' );
traiter( $amb, $ctx2 );
eq( 1, \count( $store2->propositions ), 'Pas de proposition en double' );
$store2->decisions['pnrs:777'] = \array_key_first( $store2->docs );
eq( 'rapproche', traiter( $amb, $ctx2 )['action'], 'Après validation : rattachée à la fiche choisie' );

section( 'Scénario 5 : nouveau RETEX (repli HTML)' );
$RX = 'https://pnrs.ensosp.fr/retex-pias.aspx';
$t->routes[ $RX ] = array( 200, fx( 'pnrs-retex.html' ), $HTML );
$r = executer_unite( $pn, array( 'url' => $RX, 'type' => 'retex' ), http_pour( $pn, $t ), array(), $ctx );
eq( 2, $r['stats']['nouveau'], '2 RETEX / PEX créés' );
eq( 'html', $r['methode'], 'Méthode « html » signalée' );
$pid = $store->par_ref( 'pnrs', '105186' );
eq( array( 'retex', 'pex' ), $store->docs[ $pid ]['termes']['r18_type_doc'], 'PEX visible dans la rubrique RETEX / PEX' );

section( 'Scénarios 8 et 9 : flux PNRS invalide ou clé expirée' );
$t->routes[ $F1 ] = array( 200, '<html>Clé inconnue</html>', array( 'content-type' => 'text/html' ) );
$t->routes[ $F2 ] = array( 404, 'Not found', $HTML );
$r = executer_unite( $pn, array( 'url' => $PN, 'type' => 'auto' ), http_pour( $pn, $t ), array(), $ctx );
eq( 'html', $r['methode'], 'Repli HTML quand aucun flux n\'est valide' );
ok( (bool) \preg_grep( '/repli/i', $r['notes'] ), 'Le repli est signalé dans le journal' );
ok( (bool) \preg_grep( '/Flux ignoré/', $r['notes'] ), 'Chaque flux invalide est signalé' );

section( 'Scénario 6 : nouvel article ENSOSP, filtre éditorial, validation' );
$EN = 'https://www.ensosp.fr/actualites';
$t->routes[ $EN ] = array( 200, fx( 'ensosp-liste.html' ), $HTML );
$t->routes['https://www.ensosp.fr/cotfer-premiers-tests-du-caisson-xxl'] = array( 200, fx( 'ensosp-cotfer.html' ), $HTML );
$t->routes['https://www.ensosp.fr/lensosp-et-taiwan-renforcent-leur-cooperation'] = array( 200, fx( 'ensosp-taiwan.html' ), $HTML );
$t->routes['https://www.ensosp.fr/ceremonie-des-couleurs-du-12-octobre'] = array( 200, fx( 'ensosp-ceremonie.html' ), $HTML );
$ctxv = new Contexte( $store, 'validation', false, '2026-10-30' );
$r    = executer_unite( $en, array( 'url' => $EN, 'type' => 'actualite' ), http_pour( $en, $t ), array(), $ctxv );
eq( 2, $r['stats']['nouveau'], '2 actualités retenues' );
eq( 1, $r['stats']['ecarte'], 'Cérémonie écartée (institutionnel)' );
$aid = $store->par_ref( 'ensosp', '/cotfer-premiers-tests-du-caisson-xxl' );
eq( 'pending', $store->docs[ $aid ]['statut'], 'Actualité soumise à validation (scénario 19)' );
eq( array( 'actualites-des-organismes' ), $store->docs[ $aid ]['termes']['category'], 'Catégorie « Actualités des organismes »' );
ok( \str_contains( $store->docs[ $aid ]['contenu'], 'ensosp.fr' ) && ! \str_contains( $store->docs[ $aid ]['contenu'], 'Vitrolles prennent du service ! Les' ), 'Résumé et lien, aucune copie intégrale' );
$appels = \count( $t->appels );
executer_unite( $en, array( 'url' => $EN, 'type' => 'actualite' ), http_pour( $en, $t ), array(), $ctxv );
eq( 2, \count( $t->appels ) - $appels, 'Second passage : seuls la liste et l\'article écarté sont relus' );

section( 'Scénarios 11 à 13 : source inaccessible, erreur HTTP, limitation' );
$t->routes[ $GDO ] = array( 403, fx( 'dgscgc-cloudflare.html' ), array( 'content-type' => 'text/html', 'cf-mitigated' => 'challenge' ) );
eq( 'bloque', $err( fn() => executer_unite( $dg, array( 'url' => $GDO, 'type' => 'gdo' ), http_pour( $dg, $t ), array(), $ctx ) ), 'Protection Cloudflare : « bloqué », aucun contournement' );
$t->routes[ $GDO ] = array( 500, 'Erreur', $HTML );
eq( 'http', $err( fn() => executer_unite( $dg, array( 'url' => $GDO, 'type' => 'gdo' ), http_pour( $dg, $t ), array(), $ctx ) ), 'Erreur HTTP 500 classée' );
$t->routes[ $PN ] = array( 429, 'Trop de requêtes', array( 'content-type' => 'text/html', 'retry-after' => '120' ) );
eq( 'limite', $err( fn() => executer_unite( $pn, array( 'url' => $PN, 'type' => 'auto' ), http_pour( $pn, $t ), array(), $ctx ) ), 'HTTP 429 : arrêt poli de la source' );
unset( $t->routes['https://www.ensosp.fr/actualites'] );
eq( 'reseau', $err( fn() => executer_unite( $en, array( 'url' => $EN, 'type' => 'actualite' ), http_pour( $en, $t ), array(), $ctx ) ), 'Source injoignable classée' );
$n = \count( $store->docs );
ok( $n > 0 && \count( $store->docs ) === $n, 'Aucune fiche supprimée après erreur de collecte' );

section( 'Scénario 10 : page HTML modifiée' );
$t->routes[ $GDO ] = array( 200, '<html><body><main><h1>Refonte</h1><p>Contenu déplacé</p></main></body></html>', $HTML );
eq( 'structure', $err( fn() => executer_unite( $dg, array( 'url' => $GDO, 'type' => 'gdo' ), http_pour( $dg, $t ), array(), $ctx ) ), 'Structure inconnue : erreur, jamais « succès à 0 document »' );

section( 'Scénario 20 : conservation des corrections éditoriales' );
$store->docs[ $vic ]['titre']   = 'GTO : engagement en milieu vicié (titre corrigé)';
$store->docs[ $vic ]['extrait'] = 'Résumé rédigé par la rédaction.';
$t->routes[ $GTO ] = array( 200, \str_replace( 'GTO - Engagement en milieu vicié', 'GTO - Engagement en milieu vicié (mise à jour)', fx( 'dgscgc-gto.html' ) ), $HTML );
executer_unite( $dg, array( 'url' => $GTO, 'type' => 'gto' ), http_pour( $dg, $t ), array(), $ctx );
eq( 'GTO : engagement en milieu vicié (titre corrigé)', $store->docs[ $vic ]['titre'], 'Titre corrigé conservé' );
eq( 'Résumé rédigé par la rédaction.', $store->docs[ $vic ]['extrait'], 'Extrait corrigé conservé' );

section( 'Mode simulation' );
$store3 = new Store_Memoire();
$t->routes[ $GTO ] = array( 200, fx( 'dgscgc-gto.html' ), $HTML );
$r = executer_unite( $dg, array( 'url' => $GTO, 'type' => 'gto' ), http_pour( $dg, $t ), array(), new Contexte( $store3, 'auto', true, '2026-10-30' ) );
eq( 5, $r['stats']['nouveau'], 'La simulation annonce 5 créations' );
eq( 0, \count( $store3->docs ), 'La simulation n\'écrit rien' );

section( 'Scénarios 15 à 17 : import partiel, interruption, reprise' );
$store4 = new Store_Memoire();
$ctx4   = new Contexte( $store4, 'auto', false, '2026-10-30' );
$t->routes[ $GDO ] = array( 200, fx( 'dgscgc-gdo.html' ), $HTML );
$units = $dg->unites( array( 'pages_txt' => "$GTO | gto\n$GDO | gdo" ) );
eq( 2, \count( $units ), 'Unités lues depuis le réglage des pages' );
executer_unite( $dg, $units[0], http_pour( $dg, $t ), array(), $ctx4 );
$apres1 = \count( $store4->docs );
executer_unite( $dg, $units[0], http_pour( $dg, $t ), array(), $ctx4 );
executer_unite( $dg, $units[1], http_pour( $dg, $t ), array(), $ctx4 );
eq( $apres1 + 3, \count( $store4->docs ), 'Reprise sur l\'unité suivante sans doublon' );

/* Bilan ----------------------------------------------------------------- */

$f = \count( $GLOBALS['r18_fail'] );
echo "\n" . $GLOBALS['r18_ok'] . ' vérifications réussies, ' . $f . " échec(s).\n";
exit( $f > 0 ? 1 : 0 );
