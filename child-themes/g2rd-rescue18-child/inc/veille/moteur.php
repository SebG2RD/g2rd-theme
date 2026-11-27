<?php
/**
 * Veille documentaire : décisions de synchronisation.
 *
 * Une référence collectée devient : une fiche créée, une fiche actualisée
 * (nouvelle version), un rapprochement avec une fiche d'une autre source,
 * une proposition à valider (rapprochement ambigu), un écart (filtre
 * éditorial) ou rien (identique).
 *
 * Règles :
 * - Jamais de suppression. Une référence absente d'une source ne retire
 *   aucune fiche.
 * - Une seule fiche par document : rapprochement par identifiant de source,
 *   puis par clé (type + intitulé réduit). Ressemblance forte mais pas
 *   exacte = proposition à valider, aucune fiche créée.
 * - La DGSCGC reste l'éditeur officiel d'un guide qu'elle publie, le PNRS
 *   est gardé comme source complémentaire.
 * - Les corrections éditoriales sont conservées : un titre ou un extrait
 *   retouché dans WordPress n'est plus réécrit par la synchronisation.
 * - En mode simulation, rien n'est écrit : le moteur décrit ce qu'il ferait.
 *
 * La logique ne touche WordPress qu'au travers de l'interface Store :
 * tests/ la vérifie avec un magasin en mémoire.
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18\Veille;

/**
 * Accès aux fiches.
 */
interface Store {

	/** Fiche suivie par une source. */
	public function par_ref( string $source, string $ref ): int;

	/** Fiche documentaire de même clé. */
	public function par_cle( string $cle ): int;

	/**
	 * Clés des fiches documentaires d'un type.
	 *
	 * @return array<int,string> id => clé
	 */
	public function cles( string $type ): array;

	/**
	 * Lecture d'une fiche.
	 *
	 * @return array{titre:string,extrait:string,sync_titre:string,sync_extrait:string,refs:array<string,array<string,string>>,versions:array<int,array<string,string>>,statut_post:string,editeur:string}
	 */
	public function lire( int $id ): array;

	/**
	 * Création.
	 *
	 * @param array<string,mixed> $doc kind, titre, extrait, contenu, statut, date, meta, termes.
	 */
	public function creer( array $doc ): int;

	/**
	 * Écriture partielle : titre, extrait, meta, termes (ajout seulement).
	 *
	 * @param array<string,mixed> $champs Champs.
	 */
	public function ecrire( int $id, array $champs ): void;

	/**
	 * Décision humaine déjà prise sur une référence ambiguë : id de fiche,
	 * « distinct », « ignore » ou null.
	 *
	 * @return int|string|null
	 */
	public function decision( string $source, string $ref );

	/**
	 * Enregistre une proposition de rapprochement (une seule par référence).
	 *
	 * @param array<string,mixed> $p Proposition.
	 */
	public function proposer( array $p ): void;
}

/**
 * Contexte d'un traitement.
 */
class Contexte {

	/**
	 * Constructeur.
	 *
	 * @param Store  $store       Magasin.
	 * @param string $publication auto|validation.
	 * @param bool   $simulation  Ne rien écrire.
	 * @param string $jour        Date du jour AAAA-MM-JJ.
	 * @param float  $seuil       Ressemblance à partir de laquelle on propose un rapprochement.
	 */
	public function __construct( public Store $store, public string $publication = 'auto', public bool $simulation = false, public string $jour = '', public float $seuil = 72.0 ) {
		if ( '' === $this->jour ) {
			$this->jour = \gmdate( 'Y-m-d' );
		}
	}

	/**
	 * Traitements propres à d'autres types que les documents (annuaire,
	 * actualités des SDIS, offres d'emploi…) : type => callable( array, Contexte ): array.
	 *
	 * @var array<string,callable>
	 */
	public array $gestionnaires = array();
}

/**
 * Priorité des sources pour l'éditeur et le lien principal.
 *
 * @param string $source Source.
 * @return int
 */
function priorite( string $source ): int {
	return array(
		'dgscgc' => 3,
		'pnrs'   => 2,
		'ensosp' => 1,
	)[ $source ] ?? 0;
}

/**
 * Spécialité déduite d'un intitulé.
 *
 * @param string $titre Titre.
 * @return string Slug r18_specialite ou vide.
 */
function specialite_depuis( string $titre ): string {
	$t   = plat( $titre );
	$map = array(
		'feux-de-forets'    => array( 'foret', 'espaces naturels', 'fdf' ),
		'secours-routier'   => array( 'routier', 'desincarceration' ),
		'ssuap'             => array( 'ssuap', 'secours d\'urgence', 'soins d\'urgence', 'suap', 'secourisme' ),
		'nrbc'              => array( 'nrbc', 'radiolog', 'chimique', 'biolog', 'gaz', 'toxicite', 'electricite', 'technologique' ),
		'smpm-usar'         => array( 'milieu perilleux', 'montagne', 'effondre', 'instable', 'cynotechn', 'aquatique', 'sauvetage deblaiement', 'usar' ),
		'goc-cos'           => array( 'commandement', 'conduite des operations', 'goc', 'telepilote' ),
		'sic-cta-codis'     => array( 'transmission', 'cta', 'codis', 'information operationnelle' ),
		'incendie'          => array( 'incendie', 'extinction', 'ventilation', 'milieu vicie', 'fumees', 'silo', 'sauvegarde' ),
	);
	foreach ( $map as $slug => $mots ) {
		foreach ( $mots as $m ) {
			if ( \str_contains( $t, $m ) ) {
				return $slug;
			}
		}
	}
	return '';
}

/**
 * Traite une référence collectée.
 *
 * @param array<string,string> $r Référence normalisée.
 * @param Contexte             $c Contexte.
 * @return array{action:string,id:int,note:string}
 */
function traiter( array $r, Contexte $c ): array {
	$s = $c->store;
	if ( ! empty( $r['ecarte'] ) ) {
		return array( 'action' => 'ecarte', 'id' => 0, 'note' => 'Écarté : ' . $r['ecarte'] . '.' );
	}
	if ( isset( $c->gestionnaires[ $r['type'] ] ) ) {
		return \call_user_func( $c->gestionnaires[ $r['type'] ], $r, $c );
	}
	if ( 'actualite' === $r['type'] ) {
		return traiter_actualite( $r, $c );
	}
	if ( ! isset( types()[ $r['type'] ] ) ) {
		return array( 'action' => 'ecarte', 'id' => 0, 'note' => 'Écarté : type hors ligne éditoriale.' );
	}

	$cle = cle( $r['titre'], $r['type'] );
	$emp = empreinte( $r );
	$id  = $s->par_ref( $r['source'], $r['ref'] );
	$via = 'ref';
	if ( 0 === $id ) {
		$id  = $s->par_cle( $cle );
		$via = 'cle';
	}
	if ( 0 === $id ) {
		$d = $s->decision( $r['source'], $r['ref'] );
		if ( 'ignore' === $d ) {
			return array( 'action' => 'ecarte', 'id' => 0, 'note' => 'Écarté par une décision éditoriale.' );
		}
		if ( \is_int( $d ) && $d > 0 ) {
			$id  = $d;
			$via = 'decision';
		} elseif ( 'distinct' !== $d ) {
			$best = 0;
			$sc   = 0.0;
			foreach ( $s->cles( $r['type'] ) as $cid => $ccle ) {
				$v = ressemblance( $cle, $ccle );
				if ( $v > $sc ) {
					$sc   = $v;
					$best = $cid;
				}
			}
			if ( $best > 0 && $sc >= $c->seuil ) {
				if ( ! $c->simulation ) {
					$s->proposer(
						array(
							'source'   => $r['source'],
							'ref'      => $r['ref'],
							'candidat' => $best,
							'score'    => $sc,
							'item'     => $r,
							'date'     => $c->jour,
						)
					);
				}
				return array( 'action' => 'a_valider', 'id' => $best, 'note' => 'Rapprochement à valider (ressemblance ' . $sc . ' %).' );
			}
		}
	}

	if ( 0 === $id ) {
		return creer_document( $r, $cle, $emp, $c );
	}
	return fusionner_document( $id, $r, $cle, $emp, $via, $c );
}

/**
 * Création d'une fiche documentaire.
 *
 * @param array<string,string> $r   Référence.
 * @param string               $cle Clé.
 * @param string               $emp Empreinte.
 * @param Contexte             $c   Contexte.
 * @return array{action:string,id:int,note:string}
 */
function creer_document( array $r, string $cle, string $emp, Contexte $c ): array {
	$valider = 'validation' === $c->publication;
	$ref     = ref_source( $r, $emp, $c->jour );
	$termes  = array(
		'r18_type_doc'  => termes_type( $r['type'] ),
		'r18_organisme' => \array_values( \array_unique( \array_filter( array( $r['editeur'], organisme_source( $r['source'] ) ) ) ) ),
	);
	$spe = specialite_depuis( $r['titre'] );
	if ( '' !== $spe ) {
		$termes['r18_specialite'] = array( $spe );
	}
	$version = '' !== $r['version'] ? $r['version'] : ( '' !== $r['date'] ? 'Édition ' . \substr( $r['date'], 0, 4 ) : '' );
	$doc     = array(
		'kind'    => 'document',
		'titre'   => $r['titre'],
		'extrait' => $r['resume'],
		'contenu' => contenu_document( $r ),
		'statut'  => $valider ? 'pending' : 'publish',
		'date'    => '' !== $r['date'] ? $r['date'] : $r['date_maj'],
		'meta'    => array(
			'r18_source_id'      => $r['source'] . ':' . $r['ref'],
			'r18_ref_' . $r['source'] => $r['ref'],
			'r18_cle_doc'        => $cle,
			'r18_refs'           => array( $r['source'] => $ref ),
			'r18_source_url'     => $r['url'],
			'r18_pdf_url'        => $r['pdf'],
			'r18_version'        => $version,
			'r18_date_edition'   => $r['date'],
			'r18_editeur'        => $r['editeur'],
			'r18_statut'         => $valider ? 'en-validation' : 'nouveau',
			'r18_derniere_verif' => $c->jour,
			'r18_sync_titre'     => $r['titre'],
			'r18_sync_extrait'   => $r['resume'],
			'r18_versions'       => array(
				array(
					'date'  => '' !== $r['date'] ? $r['date'] : $c->jour,
					'label' => '' !== $version ? $version : 'Première référence',
					'note'  => 'Référencé depuis ' . nom_source( $r['source'] ) . '.',
				),
			),
		),
		'termes'  => $termes,
	);
	$id = $c->simulation ? 0 : $c->store->creer( $doc );
	return array( 'action' => 'nouveau', 'id' => $id, 'note' => ( $valider ? 'Créé en attente de validation : ' : 'Publié : ' ) . $r['titre'] );
}

/**
 * Mise à jour d'une fiche existante : ajout de la source, détection de
 * version, respect des corrections éditoriales.
 *
 * @param int                  $id  Fiche.
 * @param array<string,string> $r   Référence.
 * @param string               $cle Clé.
 * @param string               $emp Empreinte.
 * @param string               $via ref|cle|decision.
 * @param Contexte             $c   Contexte.
 * @return array{action:string,id:int,note:string}
 */
function fusionner_document( int $id, array $r, string $cle, string $emp, string $via, Contexte $c ): array {
	$doc    = $c->store->lire( $id );
	$refs   = $doc['refs'];
	$ancien = $refs[ $r['source'] ] ?? null;
	$refs[ $r['source'] ] = ref_source( $r, $emp, $c->jour );
	$meta   = array(
		'r18_refs'                => $refs,
		'r18_ref_' . $r['source'] => $r['ref'],
		'r18_derniere_verif'      => $c->jour,
	);
	$champs = array();
	$termes = array( 'r18_organisme' => \array_values( \array_unique( \array_filter( array( $r['editeur'], organisme_source( $r['source'] ) ) ) ) ) );

	// Source principale : la plus officielle présente sur la fiche.
	$principale = $r['source'];
	foreach ( \array_keys( $refs ) as $src ) {
		if ( priorite( $src ) > priorite( $principale ) ) {
			$principale = $src;
		}
	}
	if ( $principale === $r['source'] ) {
		$meta['r18_source_url'] = $r['url'];
		if ( '' !== $r['pdf'] ) {
			$meta['r18_pdf_url'] = $r['pdf'];
		}
		if ( '' !== $r['editeur'] && priorite( $r['source'] ) >= 2 ) {
			$meta['r18_editeur'] = $r['editeur'];
		}
	}
	if ( 'dgscgc' === $r['editeur'] ) {
		$meta['r18_editeur'] = 'dgscgc';
	}

	// Corrections éditoriales : on ne réécrit que ce qui n'a pas été retouché.
	$titre_libre   = '' !== $doc['sync_titre'] && $doc['titre'] === $doc['sync_titre'];
	$extrait_libre = '' === \trim( $doc['extrait'] ) || $doc['extrait'] === $doc['sync_extrait'];
	if ( $principale === $r['source'] && $titre_libre && $r['titre'] !== $doc['titre'] ) {
		$champs['titre']        = $r['titre'];
		$meta['r18_sync_titre'] = $r['titre'];
	}
	if ( $extrait_libre && '' !== $r['resume'] && $r['resume'] !== $doc['extrait'] ) {
		$champs['extrait']        = $r['resume'];
		$meta['r18_sync_extrait'] = $r['resume'];
	}

	$action = 'identique';
	$note   = 'Aucun changement.';
	if ( null === $ancien ) {
		$action               = 'rapproche';
		$note                 = 'Source ' . nom_source( $r['source'] ) . ' rattachée (' . ( 'cle' === $via ? 'même intitulé' : ( 'decision' === $via ? 'validation éditoriale' : 'même identifiant' ) ) . ').';
		$meta['r18_cle_doc']  = $cle;
	} elseif ( ( $ancien['empreinte'] ?? '' ) !== $emp ) {
		$fichier = ( $ancien['pdf'] ?? '' ) !== $r['pdf'] && '' !== $r['pdf'];
		$vers    = ( $ancien['version'] ?? '' ) !== $r['version'] && '' !== $r['version'];
		$label   = '' !== $r['version'] ? $r['version'] : ( $fichier ? 'Nouveau fichier' : 'Notice mise à jour' );
		$versions = $doc['versions'];
		\array_unshift(
			$versions,
			array(
				'date'  => '' !== $r['date_maj'] ? $r['date_maj'] : $c->jour,
				'label' => $label,
				'note'  => ( $fichier || $vers ? 'Nouvelle version publiée par ' : 'Notice modifiée par ' ) . nom_source( $r['source'] ) . '.',
			)
		);
		$meta['r18_versions'] = $versions;
		if ( $fichier || $vers ) {
			$meta['r18_version'] = $label;
		}
		$meta['r18_statut'] = 'validation' === $c->publication ? 'en-validation' : 'mis-a-jour';
		$action             = 'version';
		$note               = ( $fichier || $vers ? 'Nouvelle version détectée (' . $label . ').' : 'Notice mise à jour par la source.' );
	}
	$champs['meta']   = $meta;
	$champs['termes'] = $termes;
	if ( ! $c->simulation ) {
		$c->store->ecrire( $id, $champs );
	}
	return array( 'action' => $action, 'id' => $id, 'note' => $note );
}

/**
 * Actualité d'un organisme (article WordPress, rubrique dédiée).
 *
 * @param array<string,string> $r Référence.
 * @param Contexte             $c Contexte.
 * @return array{action:string,id:int,note:string}
 */
function traiter_actualite( array $r, Contexte $c ): array {
	$s   = $c->store;
	$emp = \hash( 'sha256', $r['titre'] . "\n" . $r['resume'] . "\n" . $r['signal'] );
	$id  = $s->par_ref( $r['source'], $r['ref'] );
	if ( 0 === $id ) {
		$valider = 'validation' === $c->publication;
		$doc     = array(
			'kind'    => 'actualite',
			'titre'   => $r['titre'],
			'extrait' => $r['resume'],
			'contenu' => contenu_actualite( $r ),
			'statut'  => $valider ? 'pending' : 'publish',
			'date'    => $r['date'],
			'meta'    => array(
				'r18_source_id'           => $r['source'] . ':' . $r['ref'],
				'r18_ref_' . $r['source'] => $r['ref'],
				'r18_refs'                => array( $r['source'] => ref_source( $r, $emp, $c->jour ) ),
				'r18_source_url'          => $r['url'],
				'r18_editeur'             => $r['editeur'],
				'r18_sync_titre'          => $r['titre'],
				'r18_sync_extrait'        => $r['resume'],
				'r18_image_source'        => $r['image'],
			),
			'termes'  => array( 'category' => array( 'actualites-des-organismes' ) ),
		);
		$new = $c->simulation ? 0 : $s->creer( $doc );
		return array( 'action' => 'nouveau', 'id' => $new, 'note' => ( $valider ? 'Proposée à la validation : ' : 'Publiée : ' ) . $r['titre'] );
	}
	$doc = $s->lire( $id );
	$old = $doc['refs'][ $r['source'] ]['empreinte'] ?? '';
	if ( $old === $emp || '' === $r['resume'] ) {
		if ( ! $c->simulation ) {
			$s->ecrire( $id, array( 'meta' => array( 'r18_derniere_verif' => $c->jour ) ) );
		}
		return array( 'action' => 'identique', 'id' => $id, 'note' => 'Aucun changement.' );
	}
	$champs = array(
		'meta' => array(
			'r18_refs'           => \array_merge( $doc['refs'], array( $r['source'] => ref_source( $r, $emp, $c->jour ) ) ),
			'r18_derniere_verif' => $c->jour,
		),
	);
	if ( $doc['titre'] === $doc['sync_titre'] && $doc['titre'] !== $r['titre'] ) {
		$champs['titre']                  = $r['titre'];
		$champs['meta']['r18_sync_titre'] = $r['titre'];
	}
	if ( $doc['extrait'] === $doc['sync_extrait'] && $doc['extrait'] !== $r['resume'] ) {
		$champs['extrait']                  = $r['resume'];
		$champs['meta']['r18_sync_extrait'] = $r['resume'];
	}
	if ( ! $c->simulation ) {
		$s->ecrire( $id, $champs );
	}
	return array( 'action' => 'version', 'id' => $id, 'note' => 'Actualité modifiée à la source.' );
}

/**
 * Bloc de référence d'une source enregistré sur la fiche.
 *
 * @param array<string,string> $r    Référence.
 * @param string               $emp  Empreinte.
 * @param string               $jour Date.
 * @return array<string,string>
 */
function ref_source( array $r, string $emp, string $jour ): array {
	return array(
		'ref'       => $r['ref'],
		'url'       => $r['url'],
		'pdf'       => $r['pdf'],
		'version'   => $r['version'],
		'date'      => $r['date'],
		'empreinte' => $emp,
		'vu'        => $jour,
	);
}

/**
 * Organisme r18_organisme qui porte une source.
 *
 * @param string $source Source.
 * @return string
 */
function organisme_source( string $source ): string {
	return array(
		'dgscgc' => 'dgscgc',
		'pnrs'   => 'ensosp-pnrs',
		'ensosp' => 'ensosp-pnrs',
	)[ $source ] ?? '';
}

/**
 * Nom lisible d'une source.
 *
 * @param string $source Source.
 * @return string
 */
function nom_source( string $source ): string {
	return array(
		'dgscgc' => 'la DGSCGC',
		'pnrs'   => 'le PNRS',
		'ensosp' => 'l\'ENSOSP',
	)[ $source ] ?? $source;
}

/**
 * Contenu d'une fiche créée : résumé public et mention de la source.
 * Aucun contenu inventé : sans résumé, la fiche s'en tient aux métadonnées.
 *
 * @param array<string,string> $r Référence.
 * @return string
 */
function contenu_document( array $r ): string {
	$h = '';
	if ( '' !== $r['resume'] ) {
		$h .= '<!-- wp:paragraph --><p>' . \htmlspecialchars( $r['resume'], ENT_QUOTES, 'UTF-8' ) . '</p><!-- /wp:paragraph -->';
	}
	return $h . '<!-- wp:paragraph {"className":"r18-src-note"} --><p class="r18-src-note">Document référencé depuis ' . \htmlspecialchars( nom_source( $r['source'] ), ENT_QUOTES, 'UTF-8' ) . '. La publication officielle fait foi.</p><!-- /wp:paragraph -->';
}

/**
 * Contenu d'une actualité reprise : résumé public, lien vers l'article.
 *
 * @param array<string,string> $r Référence.
 * @return string
 */
function contenu_actualite( array $r ): string {
	$e = static fn( string $s ): string => \htmlspecialchars( $s, ENT_QUOTES, 'UTF-8' );
	$h = '' !== $r['resume'] ? '<!-- wp:paragraph --><p>' . $e( $r['resume'] ) . '</p><!-- /wp:paragraph -->' : '';
	return $h . '<!-- wp:paragraph {"className":"r18-src-note"} --><p class="r18-src-note">Source : ENSOSP. <a href="' . $e( $r['url'] ) . '" rel="noopener" target="_blank">Lire l\'article complet sur ensosp.fr</a></p><!-- /wp:paragraph -->';
}

/**
 * Exécute une unité d'un connecteur : collecte puis décision pour chaque
 * référence. Les erreurs d'accès remontent à l'appelant, les références
 * déjà traitées restent acquises.
 *
 * @param Connecteur          $k        Connecteur.
 * @param array<string,string> $unite   Unité.
 * @param Http                $http     Client.
 * @param array<string,mixed> $reglages Réglages du connecteur.
 * @param Contexte            $c        Contexte.
 * @return array{stats:array<string,int>,lignes:array<int,array<string,mixed>>,methode:string,notes:string[]}
 */
function executer_unite( Connecteur $k, array $unite, Http $http, array $reglages, Contexte $c ): array {
	$connu = static fn( string $ref ): bool => $c->store->par_ref( $k->id(), $ref ) > 0;
	$lot   = $k->traiter( $unite, $http, $reglages, $connu );
	$stats = array( 'nouveau' => 0, 'version' => 0, 'rapproche' => 0, 'identique' => 0, 'a_valider' => 0, 'ecarte' => 0, 'erreur' => 0 );
	$lignes = array();
	foreach ( $lot['items'] as $r ) {
		try {
			$res = traiter( $r, $c );
		} catch ( \Throwable $e ) {
			$res = array( 'action' => 'erreur', 'id' => 0, 'note' => 'Erreur de traitement : ' . $e->getMessage() );
		}
		$stats[ $res['action'] ] = ( $stats[ $res['action'] ] ?? 0 ) + 1;
		if ( 'identique' !== $res['action'] ) {
			$lignes[] = array(
				'action' => $res['action'],
				'id'     => $res['id'],
				'titre'  => $r['titre'],
				'note'   => $res['note'],
			);
		}
	}
	return array(
		'stats'   => $stats,
		'lignes'  => $lignes,
		'methode' => $lot['methode'],
		'notes'   => $lot['notes'],
		'reste'   => ! empty( $lot['reste'] ),
	);
}
