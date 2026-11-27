<?php
/**
 * SEOPress (et SEOPress Pro) : configuration globale et métadonnées de
 * chaque contenu.
 *
 * - Configuration : modèles de titres et descriptions par type, archives,
 *   taxonomies, plan de site XML, Knowledge Graph, Open Graph, nettoyage.
 *   Avec SEOPress Pro : fil d'Ariane JSON-LD, schémas automatiques,
 *   réglages WooCommerce.
 * - Contenus : un titre (60 caractères au plus) et une description
 *   (155 caractères au plus) écrits à partir des vraies données de chaque
 *   contenu : organisme, version, date du JO, département, date limite…
 * - Nouveaux contenus : champs vides remplis à la publication, y compris
 *   pour les imports automatiques de la nuit. Un champ saisi à la main
 *   n'est jamais écrasé.
 *
 * Écran : Outils › SEO Rescue 18.
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18\SEO;

\defined( 'ABSPATH' ) || exit;

const SUFFIXE   = ' | Rescue 18';
const MAX_TITRE = 60;
const TOLERANCE = 65; // Titre gardé entier jusqu'ici plutôt que coupé.
const MAX_DESC  = 155;
const PAGE_SEO  = 'rescue18-seo';

/**
 * SEOPress est-il actif ?
 *
 * @return bool
 */
function actif(): bool {
	return \defined( 'SEOPRESS_VERSION' );
}

/* -------------------------------------------------------------------------
 * Outils de rédaction
 * ---------------------------------------------------------------------- */

/**
 * Texte brut sur une ligne.
 *
 * @param string $s Texte ou HTML.
 * @return string
 */
function plat( string $s ): string {
	$s = \html_entity_decode( \wp_strip_all_tags( \strip_shortcodes( $s ) ), ENT_QUOTES, 'UTF-8' );
	return \trim( (string) \preg_replace( '/\s+/u', ' ', $s ) );
}

/**
 * Coupe proprement à la limite, sur un mot.
 *
 * @param string $s   Texte.
 * @param int    $max Longueur maximale.
 * @return string
 */
function couper( string $s, int $max ): string {
	$s = plat( $s );
	if ( \mb_strlen( $s ) <= $max ) {
		return $s;
	}
	$c = \mb_substr( $s, 0, $max - 1 );
	$p = \mb_strrpos( $c, ' ' );
	$c = false !== $p && $p > $max * 0.6 ? \mb_substr( $c, 0, $p ) : $c;
	return \rtrim( $c, ' ,;:.-–(' ) . '…';
}

/**
 * Formulations longues raccourcies sans perdre le sens (titres seulement).
 *
 * @param string $s Titre.
 * @return string
 */
function abreger( string $s, int $max = 0 ): string {
	$s = plat( $s );
	$r = array(
		'/R[ée]f[ée]rentiel national d[’\'](activit[ée]s) et (de|des) comp[ée]tences/iu' => 'Référentiel d’activités et compétences',
		'/R[ée]f[ée]rentiel national d[’\']([ée]valuation)/iu'                            => 'Référentiel d’évaluation',
		'/^(PEX|RETEX)\s*:\s*Partage d[’\']Exp[ée]rience (du|de la|des|de l[’\']|d[’\'])\s*/iu' => '$1 $2 ',
		'/^Partage d[’\']Exp[ée]rience (du|de la|des|de l[’\']|d[’\'])\s*/iu'            => 'PEX $1 ',
		'/Service d[ée]partemental d[’\']incendie et de secours/iu'                       => 'SDIS',
		'/\s+[–—-]\s+/u'                                                                  => ' : ',
		'/\s+:\s+:\s+/u'                                                                 => ' : ',
	);
	foreach ( $r as $motif => $par ) {
		$n = \preg_replace( $motif, $par, $s );
		$s = \is_string( $n ) ? $n : $s;
	}
	$s = \trim( (string) \preg_replace( '/\s+/u', ' ', \str_replace( ' ’', '’', $s ) ) );
	if ( $max > 0 && \mb_strlen( $s ) > $max ) {
		// Trop long : formes courtes, seulement quand il le faut.
		$court = array(
			'/sapeurs?-pompiers? professionnels?/iu' => 'SPP',
			'/sapeurs?-pompiers? volontaires?/iu'    => 'SPV',
			'/R[ée]f[ée]rentiel d[’\']activit[ée]s et comp[ée]tences/iu' => 'Référentiel activités-compétences',
			'/Jeunes? sapeurs?-pompiers?/iu'          => 'JSP',
		);
		foreach ( $court as $motif => $par ) {
			$n = \preg_replace( $motif, $par, $s );
			$s = \is_string( $n ) ? $n : $s;
		}
	}
	return $s;
}

/**
 * Titre unique pour un nom trop long : début … fin (la fin distingue
 * souvent des documents d'une même série).
 *
 * @param string $s Titre.
 * @return string
 */
function tete_queue( string $s ): string {
	$s = plat( $s );
	if ( \mb_strlen( $s ) <= MAX_TITRE ) {
		return $s;
	}
	$tete = \mb_substr( $s, 0, 26 );
	$p    = \mb_strrpos( $tete, ' ' );
	$tete = \rtrim( false !== $p ? \mb_substr( $tete, 0, $p ) : $tete, ' ,;:.-–(' );
	$fin  = \mb_substr( $s, -( MAX_TITRE - \mb_strlen( $tete ) - 3 ) );
	$p    = \mb_strpos( $fin, ' ' );
	$fin  = \ltrim( false !== $p ? \mb_substr( $fin, $p ) : $fin, ' ,;:.-–)' );
	return $tete . ' … ' . $fin;
}

/**
 * Titre : suffixe « | Rescue 18 » ajouté s'il tient dans 60 caractères.
 *
 * @param string $base Titre.
 * @return string
 */
function titre( string $base ): string {
	$base = abreger( $base, MAX_TITRE );
	if ( false !== \mb_stripos( $base, 'Rescue 18' ) ) {
		return couper( $base, TOLERANCE );
	}
	if ( \mb_strlen( $base . SUFFIXE ) <= MAX_TITRE ) {
		return $base . SUFFIXE;
	}
	return couper( $base, TOLERANCE );
}

/**
 * Description : 155 caractères au plus, complétée si trop courte.
 *
 * @param string $texte      Texte principal.
 * @param string $complement Phrase ajoutée si le texte fait moins de 90 caractères.
 * @return string
 */
function description( string $texte, string $complement = '' ): string {
	$texte = plat( $texte );
	if ( '' !== $complement && \mb_strlen( $texte ) < 90 ) {
		$texte = \trim( \rtrim( $texte, '. ' ) . ( '' !== $texte ? '. ' : '' ) . $complement );
	}
	return couper( $texte, MAX_DESC );
}

/**
 * Premier paragraphe utile du contenu (surtitres et titres retirés).
 *
 * @param \WP_Post $p Contenu.
 * @return string
 */
function extrait( \WP_Post $p ): string {
	if ( '' !== \trim( $p->post_excerpt ) ) {
		return plat( $p->post_excerpt );
	}
	$html = \excerpt_remove_blocks( $p->post_content );
	$html = (string) \preg_replace( array( '#<h[1-6][^>]*>.*?</h[1-6]>#is', '#<p[^>]*class="[^"]*(r18-eyebrow|r18-src-note)[^"]*"[^>]*>.*?</p>#is', '#<figure.*?</figure>#is' ), '', $html );
	if ( \preg_match_all( '#<p[^>]*>(.*?)</p>#is', $html, $m ) ) {
		foreach ( $m[1] as $para ) {
			$t = plat( $para );
			if ( \mb_strlen( $t ) >= 60 ) {
				return $t;
			}
		}
	}
	return plat( $html );
}

/**
 * Premier terme d'une taxonomie.
 *
 * @param int    $id  Contenu.
 * @param string $tax Taxonomie.
 * @return string
 */
function terme( int $id, string $tax ): string {
	$t = \get_the_terms( $id, $tax );
	return \is_array( $t ) && $t ? (string) $t[0]->name : '';
}

/**
 * Date AAAA-MM-JJ en français.
 *
 * @param string $ymd Date.
 * @return string
 */
function date_fr( string $ymd ): string {
	$t = '' !== $ymd ? \strtotime( $ymd . ' 12:00:00' ) : false;
	return false !== $t ? \wp_date( 'j F Y', $t ) : '';
}

/* -------------------------------------------------------------------------
 * Pages rédigées à la main (mots-clés de l'audit SEO)
 * ---------------------------------------------------------------------- */

/**
 * Titre et description des pages clés, par chemin.
 *
 * @return array<string,array{0:string,1:string}>
 */
function pages_cles(): array {
	return array(
		''                                       => array( 'Rescue 18 | Le webmédia des sapeurs-pompiers', 'Rescue 18, le webmédia des sapeurs-pompiers : actualités, bibliothèque GDO et GTO, veille juridique, outils opérationnels, podcast et formation.' ),
		'articles'                               => array( 'Actualités des sapeurs-pompiers | Rescue 18', 'Opérations, doctrine, matériel, formation et vie des casernes : toute l’actualité des sapeurs-pompiers, analysée par Rescue 18.' ),
		'documentations-referentiels'            => array( 'Bibliothèque GDO, GTO et référentiels pompiers', 'GDO, GTO, GNR, référentiels de formation, RETEX et textes juridiques des sapeurs-pompiers : chaque document avec sa version en vigueur et sa source officielle.' ),
		'documentations-referentiels/juridique'  => array( 'Textes juridiques des sapeurs-pompiers | Rescue 18', 'Lois, décrets et arrêtés qui encadrent les SDIS et les sapeurs-pompiers, suivis au Journal officiel : état, date et lien Légifrance pour chaque texte.' ),
		'documentations-referentiels/referentiels-formations' => array( 'Référentiels de formation sapeur-pompier | Rescue 18', 'Référentiels nationaux de formation des sapeurs-pompiers : formation initiale, avancement de grade et spécialités, en version officielle.' ),
		'documentations-referentiels/referentiels-techniques' => array( 'Référentiels techniques sapeurs-pompiers | Rescue 18', 'Référentiels et notes techniques nationaux des sapeurs-pompiers : matériels, engins et équipements, avec la version en vigueur et la source officielle.' ),
		'documentations-referentiels/gnr-vademecum' => array( 'GNR et vadémécums des sapeurs-pompiers | Rescue 18', 'Guides nationaux de référence (GNR) et vadémécums des sapeurs-pompiers : sauvetage, NRBC, plongée, montagne. Versions officielles et PDF.' ),
		'documentations-referentiels/formation-secours-durgence-aux-personnes-suap' => array( 'Formation SUAP des sapeurs-pompiers | Rescue 18', 'Recommandations et référentiels du secours d’urgence aux personnes (SUAP) : PSE, bilans, gestes et protocoles de référence pour les sapeurs-pompiers.' ),
		'guides-doctrines-operationnelles-gdo-guides-techniques-operationnelles-gto' => array( 'GDO et GTO sapeurs-pompiers : tous les guides', 'Tous les guides de doctrine (GDO) et de techniques opérationnelles (GTO) des sapeurs-pompiers, avec leur version en vigueur et le PDF officiel.' ),
		'guides-doctrines-operationnelles-gdo-guides-techniques-operationnelles-gto/guides-doctrines-operationnelles-gdo' => array( 'GDO : guides de doctrine opérationnelle | Rescue 18', 'Les guides de doctrine opérationnelle (GDO) de la DGSCGC : incendie, secours routier, NRBC, commandement. Version en vigueur et PDF officiel.' ),
		'guides-doctrines-operationnelles-gdo-guides-techniques-operationnelles-gto/guides-techniques-operationnelles-gto' => array( 'GTO : guides de techniques opérationnelles', 'Les guides de techniques opérationnelles (GTO) des sapeurs-pompiers : sauvetages, établissements, ventilation. Version en vigueur et PDF officiel.' ),
		'retex'                                  => array( 'RETEX et PEX des sapeurs-pompiers | Rescue 18', 'Retours d’expérience (RETEX) et partages d’expérience (PEX) publiés par les SDIS et l’ENSOSP : analyses d’interventions pour progresser.' ),
		'formation'                              => array( 'Formation sapeur-pompier : quiz et fiches', 'Parcours JSP, SPV et SPP, quiz corrigés, fiches mémo et référentiels : tout pour réviser et progresser en formation de sapeur-pompier.' ),
		'formation/quiz'                         => array( 'Quiz sapeur-pompier corrigés | Rescue 18', 'Quiz gratuits pour sapeurs-pompiers et JSP : incendie, secours à personne, secours routier, grades. Chaque réponse est corrigée et expliquée.' ),
		'formation/fiches-memo'                  => array( 'Fiches mémo sapeur-pompier | Rescue 18', 'Fiches mémo pour réviser les fondamentaux du sapeur-pompier : incendie, secours à personne, matériel et procédures.' ),
		'ssuap-le-podcast'                       => array( 'SSUAP Le Podcast : le secours à personne', 'SSUAP Le Podcast : des épisodes sur le secours d’urgence aux personnes, avec des pompiers, médecins et infirmiers. À écouter gratuitement.' ),
		'acronyme'                               => array( 'Sigles des sapeurs-pompiers : 645 définitions', 'Lexique de 645 sigles et acronymes des sapeurs-pompiers : engins, grades, secours à personne, commandement. Recherche instantanée, de VSAV à NRBC.' ),
		'devenir-pompier'                        => array( 'Devenir sapeur-pompier : SPV, SPP, JSP | Rescue 18', 'Conditions, étapes, formation et concours pour devenir sapeur-pompier volontaire, professionnel ou jeune sapeur-pompier. Le guide de Rescue 18.' ),
		'recrutement'                            => array( 'Recrutement sapeur-pompier : offres et concours', 'Offres d’emploi des SDIS, concours de sapeur-pompier, recrutements de volontaires : toutes les annonces en cours, mises à jour chaque jour.' ),
		'recrutement/offres-emploi'              => array( 'Offres d’emploi sapeur-pompier des SDIS', 'Les offres d’emploi publiées par les SDIS : sapeurs-pompiers professionnels, officiers, personnels administratifs et techniques. Mises à jour chaque jour.' ),
		'recrutement/concours'                   => array( 'Concours sapeur-pompier : dates et épreuves', 'Calendrier des concours de sapeur-pompier professionnel, caporal, lieutenant et capitaine : inscriptions, épreuves, sujets officiels et quiz pour t’entraîner.' ),
		'recrutement/concours/sujets'            => array( 'Sujets des concours sapeur-pompier | Rescue 18', 'Sujets officiels des concours de sapeur-pompier professionnel, caporal et lieutenant, classés par session, avec des quiz d’entraînement.' ),
		'actualites-des-sdis'                    => array( 'Actualités des SDIS : interventions et recrutements', 'Interventions, recrutements et événements publiés par les SDIS de France, réunis au même endroit et filtrables par région et département.' ),
		'carte-des-casernes'                     => array( 'Carte des casernes de pompiers de France', 'Les casernes de sapeurs-pompiers de France sur une carte interactive : centres d’incendie et de secours, adresses et SDIS de rattachement.' ),
		'casernes-du-monde'                      => array( 'Carte des casernes de pompiers du monde', 'Plus de 128 000 casernes de pompiers dans le monde sur une carte interactive, à partir des données ouvertes d’OpenStreetMap.' ),
		'outils'                                 => array( 'Boîte à outils du sapeur-pompier | Rescue 18', 'Outils gratuits pour sapeurs-pompiers : calcul du RIS d’un DPS, autonomie ARI, noria, hydraulique, mousse, messages opérationnels. Méthode et sources officielles.' ),
		'partenaires'                            => array( 'Partenaires officiels et soutiens | Rescue 18', 'Les partenaires officiels et les soutiens qui permettent à Rescue 18 de rester gratuit pour tous les pompiers. Deviens partenaire du webmédia.' ),
		'boutique'                               => array( 'Boutique officielle Rescue 18', 'T-shirts, sweats et accessoires Rescue 18 pour les sapeurs-pompiers et leurs proches. Chaque achat soutient le webmédia.' ),
		'contactez-nous'                         => array( 'Contacter Rescue 18', 'Une question, une erreur à signaler, un partenariat ou un article à proposer : écris à l’équipe de Rescue 18, le webmédia des sapeurs-pompiers.' ),
		'plan-du-site'                           => array( 'Plan du site | Rescue 18', 'Toutes les rubriques de rescue18.com : actualités, documentation, formation, recrutement, outils, podcast et boutique.' ),
		'mentions-legales'                       => array( 'Mentions légales | Rescue 18', 'Éditeur, hébergeur et conditions d’utilisation du site rescue18.com, le webmédia de l’association Rescue 18.' ),
		'politique-de-confidentialite'           => array( 'Politique de confidentialité | Rescue 18', 'Données personnelles collectées par rescue18.com, finalités, durées de conservation et exercice de tes droits (RGPD).' ),
		'accessibilite'                          => array( 'Déclaration d’accessibilité | Rescue 18', 'Déclaration d’accessibilité de rescue18.com : niveau de conformité au RGAA, contenus concernés et moyens de nous signaler un problème.' ),
		// Articles phares de l'audit SEO.
		'grades-pompiers-bspp-bmpm'              => array( 'Grades des sapeurs-pompiers : insignes et hiérarchie', 'Tous les grades des sapeurs-pompiers en France, du sapeur au colonel : insignes, hiérarchie et équivalences SPP, SPV, BSPP et BMPM.' ),
		'sainte-barbe-sapeurs-pompiers-patronne-protectrice-fete-histoire-tradition' => array( 'Sainte-Barbe : la fête des sapeurs-pompiers', 'Le 4 décembre, les sapeurs-pompiers fêtent sainte Barbe, leur patronne. Histoire, traditions et déroulé des célébrations dans les casernes.' ),
	);
}

/**
 * Pages à ne pas indexer (fonctionnelles ou d'exemple).
 *
 * @return string[]
 */
function pages_noindex(): array {
	return array( 'panier', 'commander', 'mon-compte', 'sample-page' );
}

/**
 * Chemin d'un contenu, sans barres.
 *
 * @param \WP_Post $p Contenu.
 * @return string
 */
function chemin( \WP_Post $p ): string {
	if ( (int) \get_option( 'page_on_front' ) === $p->ID ) {
		return '';
	}
	if ( 'page' === $p->post_type ) {
		return \trim( (string) \get_page_uri( $p ), '/' );
	}
	return \trim( (string) \wp_parse_url( (string) \get_permalink( $p ), PHP_URL_PATH ), '/' );
}

/* -------------------------------------------------------------------------
 * Métadonnées d'un contenu
 * ---------------------------------------------------------------------- */

/**
 * Titre, description et indexation proposés pour un contenu.
 *
 * @param \WP_Post $p Contenu.
 * @return array{titre:string,desc:string,noindex:bool}
 */
function metas( \WP_Post $p ): array {
	$id      = $p->ID;
	$nom     = plat( \get_the_title( $p ) );
	$ex      = extrait( $p );
	$noindex = false;
	$cles    = pages_cles();
	$cle     = chemin( $p );

	if ( isset( $cles[ $cle ] ) ) {
		[ $t, $d ] = $cles[ $cle ];
		return array(
			'titre'   => titre( $t ),
			'desc'    => couper( $d, MAX_DESC ),
			'noindex' => false,
		);
	}

	switch ( $p->post_type ) {
		case 'page':
			$outils = \function_exists( 'G2RD_Rescue18\\outils' ) ? \G2RD_Rescue18\outils() : array();
			if ( isset( $outils[ $p->post_name ] ) ) {
				$o = $outils[ $p->post_name ];
				return array(
					'titre'   => titre( (string) ( $o['seo_title'] ?? $nom ) ),
					'desc'    => couper( (string) ( $o['description'] ?? $ex ), MAX_DESC ),
					'noindex' => false,
				);
			}
			$noindex = \in_array( $p->post_name, pages_noindex(), true );
			$titre   = titre( $nom );
			$desc    = description( $ex, 'Rescue 18, le webmédia des sapeurs-pompiers.' );
			break;

		case 'r18_document':
			$type  = terme( $id, 'r18_type_doc' );
			$org   = terme( $id, 'r18_organisme' );
			$vers  = plat( (string) \get_post_meta( $id, 'r18_version', true ) );
			$ed    = date_fr( (string) \get_post_meta( $id, 'r18_date_edition', true ) );
			$deja  = '' === $type || 0 === \mb_stripos( $nom, \mb_substr( $type, 0, 8 ) ) || false !== \mb_stripos( $nom, $type );
			$base  = $deja || \mb_strlen( $type . ' : ' . $nom ) > MAX_TITRE ? $nom : $type . ' : ' . $nom;
			$titre = titre( $base );
			$info  = \trim( ( '' !== $org ? 'Publié par ' . $org : '' ) . ( '' !== $vers ? ', version ' . $vers : '' ) . ( '' !== $ed ? ', édition du ' . $ed : '' ), ', ' );
			$desc  = description( '' !== $ex ? $ex : $nom, ( '' !== $info ? \ucfirst( $info ) . '. ' : '' ) . 'Fiche, sources officielles et PDF sur Rescue 18.' );
			break;

		case 'r18_texte':
			$nat   = plat( (string) \get_post_meta( $id, 'r18_nature', true ) );
			$nat   = array(
				'arrete'        => 'arrêté',
				'decret'        => 'décret',
				'loi'           => 'loi',
				'ordonnance'    => 'ordonnance',
				'circulaire'    => 'circulaire',
				'instruction'   => 'instruction',
				'decision'      => 'décision',
			)[ \strtolower( \remove_accents( $nat ) ) ] ?? $nat;
			$jo    = date_fr( (string) \get_post_meta( $id, 'r18_date_jo', true ) );
			$objet = plat( $p->post_excerpt );
			// « Arrêté du 15 juin 2024 » seul ne dit rien : on ajoute l'objet du texte.
			$titre = titre( \mb_strlen( $nom ) < 40 && '' !== $objet ? $nom . ' ' . \lcfirst( $objet ) : $nom );
			$desc  = description( ( '' !== $nat ? \ucfirst( $nat ) : 'Texte officiel' ) . ( '' !== $jo ? ' publié au Journal officiel le ' . $jo : '' ) . ' : ' . ( '' !== $ex ? $ex : $nom ), 'Résumé et lien Légifrance pour les sapeurs-pompiers.' );
			break;

		case 'r18_episode':
			$n     = (string) \get_post_meta( $id, 'r18_numero_episode', true );
			$titre = titre( $nom . ( \mb_strlen( $nom ) < 30 ? ' · SSUAP Le Podcast' : '' ) );
			$desc  = description( $ex, ( '' !== $n ? 'Épisode ' . $n . ' de ' : '' ) . 'SSUAP Le Podcast, le podcast du secours d’urgence aux personnes. À écouter sur Rescue 18.' );
			break;

		case 'r18_service':
			$titre = titre( $nom . ( \mb_strlen( $nom ) <= 24 ? ' : casernes et recrutement' : '' ) );
			$desc  = description( $nom . ' : site officiel, contact, actualités, offres d’emploi et casernes du département', 'Fiche à jour sur Rescue 18.' );
			break;

		case 'r18_offre':
			$fin   = date_fr( (string) \get_post_meta( $id, 'r18_fin', true ) );
			$titre = titre( $nom );
			$desc  = description( 'Offre d’emploi : ' . $nom . ( '' !== $fin ? '. Candidature avant le ' . $fin : '' ), 'Missions, conditions et lien officiel pour postuler, sur Rescue 18.' );
			break;

		case 'r18_actu_sdis':
			// Résumés d'articles publiés ailleurs : utiles au fil, pas à l'index.
			$noindex = true;
			$titre   = titre( $nom );
			$desc    = description( $ex, 'Article publié par le service d’incendie et de secours.' );
			break;

		case 'r18_concours':
			$titre = titre( $nom );
			$desc  = description( '' !== $ex ? $ex : $nom, 'Dates d’inscription, épreuves et conditions. Calendrier des concours de sapeur-pompier sur Rescue 18.' );
			break;

		case 'r18_annale':
			$titre = titre( 'Sujet de concours : ' . $nom );
			$desc  = description( 'Sujet officiel : ' . $nom, 'Téléchargement et entraînement pour préparer ton concours de sapeur-pompier sur Rescue 18.' );
			break;

		case 'r18_quiz':
			$titre = titre( 'Quiz : ' . $nom );
			$desc  = description( '' !== $ex ? $ex : 'Teste tes connaissances : ' . $nom, 'Questions corrigées et expliquées, gratuites, pour sapeurs-pompiers et JSP.' );
			break;

		case 'r18_fiche':
			$titre = titre( 'Fiche mémo : ' . $nom );
			$desc  = description( $ex, 'Fiche mémo pour réviser, sur Rescue 18.' );
			break;

		case 'r18_partenaire':
			$conv    = 'convention' === \get_post_meta( $id, 'r18_p_niveau', true );
			$noindex = ! $conv; // Seuls les partenaires officiels ont une fiche publique.
			$titre   = titre( $nom . ( $conv ? ', partenaire officiel' : '' ) );
			$desc    = description( '' !== $ex ? $ex : $nom . ' soutient Rescue 18', 'Partenaire de Rescue 18, le webmédia gratuit des sapeurs-pompiers.' );
			break;

		case 'product':
			$titre = \mb_strlen( $nom . ' | Boutique Rescue 18' ) <= MAX_TITRE ? $nom . ' | Boutique Rescue 18' : titre( $nom );
			$desc  = description( $ex, 'En vente sur la boutique officielle Rescue 18. Chaque achat soutient le webmédia des sapeurs-pompiers.' );
			break;

		default: // Articles et autres types.
			$titre = titre( $nom );
			$desc  = description( $ex, 'À lire sur Rescue 18, le webmédia des sapeurs-pompiers.' );
	}
	return array(
		'titre'   => $titre,
		'desc'    => $desc,
		'noindex' => $noindex,
	);
}

/**
 * Types de contenu traités.
 *
 * @return string[]
 */
function types(): array {
	$t = \get_post_types( array( 'public' => true ), 'names' );
	unset( $t['attachment'], $t['mailpoet_page'] ); // Page technique de MailPoet : ni titre, ni plan de site.
	return \array_values( $t );
}

/**
 * Écrit les métadonnées d'un contenu.
 *
 * @param \WP_Post $p      Contenu.
 * @param bool     $forcer Réécrire les champs déjà remplis.
 * @return string[] Champs écrits.
 */
function appliquer( \WP_Post $p, bool $forcer = false ): array {
	$m    = metas( $p );
	$fait = array();
	if ( $forcer || '' === (string) \get_post_meta( $p->ID, '_seopress_titles_title', true ) ) {
		\update_post_meta( $p->ID, '_seopress_titles_title', $m['titre'] );
		$fait[] = 'titre';
	}
	if ( $forcer || '' === (string) \get_post_meta( $p->ID, '_seopress_titles_desc', true ) ) {
		\update_post_meta( $p->ID, '_seopress_titles_desc', $m['desc'] );
		$fait[] = 'desc';
	}
	if ( $m['noindex'] && '' === (string) \get_post_meta( $p->ID, '_seopress_robots_index', true ) ) {
		\update_post_meta( $p->ID, '_seopress_robots_index', 'yes' );
		$fait[] = 'noindex';
	}
	return $fait;
}

/* Nouveaux contenus et imports de la nuit : champs vides remplis à la publication. */
\add_action(
	'wp_after_insert_post',
	static function ( int $id, \WP_Post $p ): void {
		if ( ! actif() || 'publish' !== $p->post_status || \wp_is_post_revision( $id ) || ! \in_array( $p->post_type, types(), true ) ) {
			return;
		}
		appliquer( $p );
	},
	20,
	2
);

/* -------------------------------------------------------------------------
 * Configuration globale de SEOPress
 * ---------------------------------------------------------------------- */

/**
 * Applique la configuration globale. Seules les clés gérées ici sont
 * réécrites : les autres réglages de SEOPress sont conservés.
 *
 * @return string[] Journal.
 */
function configurer(): array {
	$log = array();
	$pro = \defined( 'SEOPRESS_PRO_VERSION' );

	// Modules.
	$tog = (array) \get_option( 'seopress_toggle', array() );
	$mod = array( 'titles', 'xml-sitemap', 'social', 'advanced' );
	if ( $pro ) {
		$mod = \array_merge( $mod, array( 'rich-snippets', 'breadcrumbs', 'woocommerce' ) );
	}
	foreach ( $mod as $k ) {
		$tog[ 'toggle-' . $k ] = '1';
	}
	\update_option( 'seopress_toggle', $tog );
	$log[] = 'Modules activés : titres et métas, plan de site XML, réseaux sociaux, avancé' . ( $pro ? ', schémas, fil d’Ariane, WooCommerce' : '' ) . '.';

	// Titres et métas.
	$ti                                    = (array) \get_option( 'seopress_titles_option_name', array() );
	$ti['seopress_titles_sep']             = '|';
	$ti['seopress_titles_home_site_title'] = 'Rescue 18 %%sep%% Le webmédia des sapeurs-pompiers';
	$ti['seopress_titles_home_site_desc']  = pages_cles()[''][1];
	$modeles                               = array(
		'post'           => array( '%%post_title%% %%sep%% %%sitetitle%%', '%%post_excerpt%%' ),
		'page'           => array( '%%post_title%% %%sep%% %%sitetitle%%', '%%post_excerpt%%' ),
		'product'        => array( '%%post_title%% %%sep%% Boutique %%sitetitle%%', '%%wc_single_short_desc%%' ),
		'r18_episode'    => array( '%%post_title%% %%sep%% SSUAP Le Podcast', '%%post_excerpt%%' ),
		'r18_quiz'       => array( 'Quiz : %%post_title%% %%sep%% %%sitetitle%%', '%%post_excerpt%%' ),
		'r18_fiche'      => array( 'Fiche mémo : %%post_title%% %%sep%% %%sitetitle%%', '%%post_excerpt%%' ),
		'r18_service'    => array( '%%post_title%% %%sep%% %%sitetitle%%', '%%post_title%% : site officiel, contact, actualités, offres d’emploi et casernes.' ),
		'r18_offre'      => array( '%%post_title%% %%sep%% %%sitetitle%%', 'Offre d’emploi : %%post_title%%. Conditions et lien officiel pour postuler.' ),
		'r18_annale'     => array( 'Sujet de concours : %%post_title%% %%sep%% %%sitetitle%%', 'Sujet officiel : %%post_title%%. Prépare ton concours de sapeur-pompier.' ),
	);
	$single = (array) ( $ti['seopress_titles_single_titles'] ?? array() );
	foreach ( types() as $t ) {
		[ $mt, $md ]  = $modeles[ $t ] ?? array( '%%post_title%% %%sep%% %%sitetitle%%', '%%post_excerpt%%' );
		$single[ $t ] = \array_merge(
			(array) ( $single[ $t ] ?? array() ),
			array(
				'title'       => $mt,
				'description' => $md,
			)
		);
		if ( 'r18_actu_sdis' === $t ) {
			$single[ $t ]['noindex'] = '1';
		} else {
			unset( $single[ $t ]['noindex'] );
		}
	}
	$ti['seopress_titles_single_titles'] = $single;

	$arch = (array) ( $ti['seopress_titles_archive_titles'] ?? array() );
	foreach ( \get_post_types( array( 'public' => true, 'has_archive' => true ), 'names' ) as $t ) {
		$sdis       = 'r18_service' === $t;
		$arch[ $t ] = \array_merge(
			(array) ( $arch[ $t ] ?? array() ),
			array(
				'title'       => $sdis ? 'Annuaire des SDIS de France %%sep%% %%sitetitle%%' : '%%cpt_plural%% %%sep%% %%sitetitle%%',
				'description' => $sdis ? 'Les services départementaux d’incendie et de secours (SDIS) : site officiel, contact, actualités, offres d’emploi et casernes de chaque département.' : '%%cpt_plural%% : toutes les publications pour les sapeurs-pompiers sur Rescue 18.',
			)
		);
		if ( 'r18_actu_sdis' === $t ) {
			$arch[ $t ]['noindex'] = '1';
		}
	}
	$ti['seopress_titles_archive_titles'] = $arch;

	$taxm = array(
		'category'       => array( '%%term_title%% : actualités et fiches pompiers %%sep%% %%sitetitle%%', false ),
		'r18_specialite' => array( '%%term_title%% : documents, articles et quiz %%sep%% %%sitetitle%%', false ),
		'r18_type_doc'   => array( '%%term_title%% : bibliothèque des sapeurs-pompiers %%sep%% %%sitetitle%%', false ),
		'r18_organisme'  => array( 'Documents %%term_title%% pour les sapeurs-pompiers %%sep%% %%sitetitle%%', false ),
		'r18_public'     => array( '%%term_title%% : ressources sapeurs-pompiers %%sep%% %%sitetitle%%', false ),
		'product_cat'    => array( '%%term_title%% %%sep%% Boutique %%sitetitle%%', false ),
		'post_tag'       => array( '%%term_title%% %%sep%% %%sitetitle%%', true ),
		'product_tag'    => array( '%%term_title%% %%sep%% %%sitetitle%%', true ),
		'product_brand'  => array( '%%term_title%% %%sep%% %%sitetitle%%', true ),
	);
	$tax  = (array) ( $ti['seopress_titles_tax_titles'] ?? array() );
	foreach ( $taxm as $t => [ $mt, $ni ] ) {
		if ( ! \taxonomy_exists( $t ) ) {
			continue;
		}
		$tax[ $t ] = \array_merge(
			(array) ( $tax[ $t ] ?? array() ),
			array(
				'title'       => $mt,
				'description' => '%%term_description%%',
			)
		);
		if ( $ni ) {
			$tax[ $t ]['noindex'] = '1';
		} else {
			unset( $tax[ $t ]['noindex'] );
		}
	}
	$ti['seopress_titles_tax_titles'] = $tax;

	$ti['seopress_titles_archives_author_title']   = '%%post_author%% %%sep%% %%sitetitle%%';
	$ti['seopress_titles_archives_author_noindex'] = '1';
	$ti['seopress_titles_archives_date_disable']   = '1';
	$ti['seopress_titles_archives_search_title']   = 'Recherche : %%search_keywords%% %%sep%% %%sitetitle%%';
	$ti['seopress_titles_archives_404_title']      = 'Page introuvable %%sep%% %%sitetitle%%';
	$ti['seopress_titles_paged_rel']               = '1';
	if ( \post_type_exists( 'mailpoet_page' ) ) {
		$ti['seopress_titles_single_titles']['mailpoet_page'] = array(
			'title'   => '%%post_title%% %%sep%% %%sitetitle%%',
			'noindex' => '1',
		);
	}
	\update_option( 'seopress_titles_option_name', $ti );
	$log[] = 'Titres et métas : modèles pour ' . \count( $single ) . ' types de contenu, ' . \count( $tax ) . ' taxonomies et les archives. Actualités des SDIS, étiquettes et archives d’auteur en noindex, archives par date désactivées.';

	// Plan de site XML.
	$sm                                        = (array) \get_option( 'seopress_xml_sitemap_option_name', array() );
	$sm['seopress_xml_sitemap_general_enable'] = '1';
	$sm['seopress_xml_sitemap_img_enable']     = '1';
	$sm['seopress_xml_sitemap_author_enable']  = '0';
	$pt                                        = array();
	foreach ( types() as $t ) {
		if ( 'r18_actu_sdis' !== $t ) {
			$pt[ $t ] = array( 'include' => '1' );
		}
	}
	$sm['seopress_xml_sitemap_post_types_list'] = $pt;
	$tl                                         = array();
	foreach ( array( 'category', 'r18_specialite', 'r18_type_doc', 'r18_organisme', 'product_cat' ) as $t ) {
		if ( \taxonomy_exists( $t ) ) {
			$tl[ $t ] = array( 'include' => '1' );
		}
	}
	$sm['seopress_xml_sitemap_taxonomies_list'] = $tl;
	\update_option( 'seopress_xml_sitemap_option_name', $sm );
	$log[] = 'Plan de site XML : ' . \count( $pt ) . ' types et ' . \count( $tl ) . ' taxonomies, avec les images. Adresse : ' . \home_url( '/sitemaps.xml' ) . '.';

	// Réseaux sociaux et Knowledge Graph.
	$so   = (array) \get_option( 'seopress_social_option_name', array() );
	$logo = (string) \wp_get_attachment_image_url( (int) \get_theme_mod( 'custom_logo' ), 'full' );
	if ( '' === $logo ) {
		$logo = \get_stylesheet_directory_uri() . '/assets/img/logo-rescue18.png';
	}
	$so['seopress_social_knowledge_type'] = 'Organization';
	$so['seopress_social_knowledge_name'] = 'Rescue 18';
	$so['seopress_social_knowledge_desc'] = 'Le webmédia des sapeurs-pompiers : actualités, documentation, formation et outils opérationnels.';
	if ( '' !== $logo ) {
		$so['seopress_social_knowledge_img'] = $logo;
		if ( empty( $so['seopress_social_facebook_img'] ) ) {
			$so['seopress_social_facebook_img'] = $logo;
		}
	}
	$so['seopress_social_facebook_og']           = '1';
	$so['seopress_social_twitter_card']          = '1';
	$so['seopress_social_twitter_card_og']       = '1';
	$so['seopress_social_twitter_card_img_size'] = 'large';
	\update_option( 'seopress_social_option_name', $so );
	$log[] = 'Réseaux sociaux : Knowledge Graph (organisation Rescue 18 avec logo), Open Graph et cartes X activés.';

	// Avancé : nettoyage.
	$ad = (array) \get_option( 'seopress_advanced_option_name', array() );
	foreach ( array( 'attachments', 'replytocom', 'wp_generator', 'wp_shortlink', 'wp_wlw' ) as $k ) {
		$ad[ 'seopress_advanced_advanced_' . $k ] = '1';
	}
	\update_option( 'seopress_advanced_option_name', $ad );
	$log[] = 'Avancé : pages de pièces jointes redirigées vers leur contenu, liens courts, version de WordPress et paramètre replytocom retirés.';

	// SEOPress Pro.
	if ( $pro ) {
		$po                                             = (array) \get_option( 'seopress_pro_option_name', array() );
		$po['seopress_breadcrumbs_enable']              = '1';
		$po['seopress_breadcrumbs_json_enable']         = '1';
		$po['seopress_rich_snippets_enable']            = '1';
		$po['seopress_woocommerce_cart_page_no_index']  = '1';
		$po['seopress_woocommerce_checkout_page_no_index']         = '1';
		$po['seopress_woocommerce_customer_account_page_no_index'] = '1';
		$po['seopress_woocommerce_product_og_price']    = '1';
		$po['seopress_woocommerce_product_og_currency'] = '1';
		$po['seopress_woocommerce_meta_generator']      = '1';
		\update_option( 'seopress_pro_option_name', $po );
		$log[] = 'SEOPress Pro : fil d’Ariane en JSON-LD, schémas automatiques, panier, commande et compte en noindex, prix et devise des produits dans Open Graph.';
	}

	\flush_rewrite_rules( false );
	return $log;
}

/**
 * Descriptions des termes vides (catégories, spécialités, types, organismes).
 *
 * @return int Termes complétés.
 */
function completer_termes(): int {
	$n       = 0;
	$modeles = array(
		'category'       => '%s : articles, analyses et fiches pour les sapeurs-pompiers, sur Rescue 18.',
		'r18_specialite' => '%s : documents officiels, articles, quiz et outils pour les sapeurs-pompiers, réunis sur Rescue 18.',
		'r18_type_doc'   => '%s des sapeurs-pompiers : chaque document avec sa version en vigueur, sa source officielle et son PDF.',
		'r18_organisme'  => 'Documents publiés par %s pour les sapeurs-pompiers : versions en vigueur, sources officielles et PDF.',
		'r18_public'     => 'Ressources pour %s : documents, quiz et fiches de révision des sapeurs-pompiers sur Rescue 18.',
		'product_cat'    => '%s : produits de la boutique officielle Rescue 18, au profit du webmédia des sapeurs-pompiers.',
	);
	foreach ( $modeles as $tax => $modele ) {
		if ( ! \taxonomy_exists( $tax ) ) {
			continue;
		}
		$termes = \get_terms(
			array(
				'taxonomy'   => $tax,
				'hide_empty' => true,
			)
		);
		if ( ! \is_array( $termes ) ) {
			continue;
		}
		foreach ( $termes as $t ) {
			if ( ! $t instanceof \WP_Term || '' !== (string) \get_term_meta( $t->term_id, '_seopress_titles_desc', true ) ) {
				continue;
			}
			$d = '' !== \trim( $t->description ) ? $t->description : \sprintf( $modele, $t->name );
			\update_term_meta( $t->term_id, '_seopress_titles_desc', couper( $d, MAX_DESC ) );
			++$n;
		}
	}
	return $n;
}

/* -------------------------------------------------------------------------
 * Écran Outils › SEO Rescue 18 (traitement par lots en AJAX)
 * ---------------------------------------------------------------------- */

\add_action(
	'admin_menu',
	static function (): void {
		\add_management_page( 'SEO Rescue 18', 'SEO Rescue 18', 'manage_options', PAGE_SEO, __NAMESPACE__ . '\ecran' );
	}
);

\add_action(
	'wp_ajax_r18_seo_config',
	static function (): void {
		\check_ajax_referer( 'r18_seo' );
		if ( ! \current_user_can( 'manage_options' ) || ! actif() ) {
			\wp_send_json_error( array( 'message' => 'SEOPress inactif ou droits insuffisants.' ), 403 );
		}
		$log   = configurer();
		$log[] = completer_termes() . ' description(s) de catégories et de termes ajoutée(s).';
		\update_option( 'r18_seo_config', \time(), false );
		\wp_send_json_success( array( 'lignes' => $log ) );
	}
);

\add_action(
	'wp_ajax_r18_seo_lot',
	static function (): void {
		\check_ajax_referer( 'r18_seo' );
		if ( ! \current_user_can( 'manage_options' ) || ! actif() ) {
			\wp_send_json_error( array( 'message' => 'SEOPress inactif ou droits insuffisants.' ), 403 );
		}
		$offset = \max( 0, (int) ( $_POST['offset'] ?? 0 ) );
		$forcer = ! empty( $_POST['forcer'] );
		$q      = new \WP_Query(
			array(
				'post_type'      => types(),
				'post_status'    => 'publish',
				'posts_per_page' => 40,
				'offset'         => $offset,
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);
		$n  = 0;
		$ex = array();
		foreach ( $q->posts as $p ) {
			if ( appliquer( $p, $forcer ) ) {
				++$n;
			}
			if ( \count( $ex ) < 3 ) {
				$ex[] = array(
					'type'  => $p->post_type,
					'titre' => (string) \get_post_meta( $p->ID, '_seopress_titles_title', true ),
					'desc'  => (string) \get_post_meta( $p->ID, '_seopress_titles_desc', true ),
				);
			}
		}
		\wp_send_json_success(
			array(
				'traites'  => \count( $q->posts ),
				'modifies' => $n,
				'total'    => (int) $q->found_posts,
				'suivant'  => $offset + \count( $q->posts ),
				'exemples' => $ex,
			)
		);
	}
);

/**
 * Rend uniques les titres en double (titres coupés d'une même série).
 *
 * @return int Titres réécrits.
 */
function dedoublonner(): int {
	global $wpdb;
	$types = "'" . \implode( "','", \array_map( 'esc_sql', types() ) ) . "'";
	$rows  = $wpdb->get_results( "SELECT p.ID, m.meta_value AS titre FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_seopress_titles_title' WHERE p.post_status = 'publish' AND p.post_type IN ($types)" ); // phpcs:ignore
	$groupes = array();
	foreach ( $rows as $o ) {
		$groupes[ (string) $o->titre ][] = (int) $o->ID;
	}
	$n = 0;
	foreach ( $groupes as $ids ) {
		if ( \count( $ids ) < 2 ) {
			continue;
		}
		$pris = array();
		$cles = pages_cles();
		// Les pages clés gardent leur titre : ce sont les autres qui changent.
		\usort( $ids, static fn( $a, $b ) => (int) isset( $cles[ chemin( \get_post( $b ) ) ] ) <=> (int) isset( $cles[ chemin( \get_post( $a ) ) ] ) );
		foreach ( $ids as $id ) {
			$p = \get_post( $id );
			if ( ! $p ) {
				continue;
			}
			if ( 'page' === $p->post_type && isset( $cles[ chemin( $p ) ] ) ) {
				appliquer( $p, true );
				$pris[ (string) \get_post_meta( $id, '_seopress_titles_title', true ) ] = true;
				continue;
			}
			$t = tete_queue( abreger( \get_the_title( $p ), MAX_TITRE ) );
			$lib = (string) ( \get_post_type_object( $p->post_type )->labels->singular_name ?? '' );
			if ( \mb_strlen( $t . SUFFIXE ) <= MAX_TITRE && ! isset( $pris[ $t . SUFFIXE ] ) ) {
				$t .= SUFFIXE;
			} elseif ( isset( $pris[ $t ] ) || isset( $pris[ $t . SUFFIXE ] ) ) {
				$t = couper( $t, TOLERANCE - \mb_strlen( $lib ) - 3 ) . ' · ' . $lib;
				$k = 2;
				while ( isset( $pris[ $t ] ) ) {
					$t = couper( tete_queue( abreger( \get_the_title( $p ), MAX_TITRE ) ), TOLERANCE - 5 ) . ' (' . $k++ . ')';
				}
			}
			$pris[ $t ] = true;
			\update_post_meta( $id, '_seopress_titles_title', $t );
			++$n;
		}
	}
	return $n;
}

\add_action(
	'wp_ajax_r18_seo_uniques',
	static function (): void {
		\check_ajax_referer( 'r18_seo' );
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_send_json_error( array(), 403 );
		}
		\wp_send_json_success( array( 'reecrits' => dedoublonner() ) );
	}
);

\add_action(
	'wp_ajax_r18_seo_audit',
	static function (): void {
		\check_ajax_referer( 'r18_seo' );
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_send_json_error( array(), 403 );
		}
		global $wpdb;
		$types = "'" . \implode( "','", \array_map( 'esc_sql', types() ) ) . "'";
		$rows  = $wpdb->get_results( "SELECT p.ID, p.post_type, t.meta_value AS titre, d.meta_value AS descr FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} t ON t.post_id = p.ID AND t.meta_key = '_seopress_titles_title' LEFT JOIN {$wpdb->postmeta} d ON d.post_id = p.ID AND d.meta_key = '_seopress_titles_desc' WHERE p.post_status = 'publish' AND p.post_type IN ($types)" ); // phpcs:ignore
		$r = array( 'total' => \count( $rows ), 'sans_titre' => 0, 'sans_desc' => 0, 'titre_long' => array(), 'desc_longue' => array(), 'desc_courte' => array(), 'titres_doublons' => 0 );
		$vus = array();
		foreach ( $rows as $o ) {
			$t = (string) $o->titre;
			$d = (string) $o->descr;
			'' === $t && ++$r['sans_titre'];
			'' === $d && ++$r['sans_desc'];
			\mb_strlen( $t ) > TOLERANCE && $r['titre_long'][] = $o->ID . ' ' . $t;
			\mb_strlen( $d ) > MAX_DESC && $r['desc_longue'][] = $o->ID;
			'' !== $d && \mb_strlen( $d ) < 70 && \count( $r['desc_courte'] ) < 15 && $r['desc_courte'][] = $o->post_type . ' ' . $o->ID . ' ' . $d;
			if ( '' !== $t ) {
				$vus[ $t ] = ( $vus[ $t ] ?? 0 ) + 1;
			}
		}
		$dbl = \array_filter( $vus, static fn( $n ) => $n > 1 );
		$r['titres_doublons'] = \array_slice( $dbl, 0, 20, true );
		$r['titre_long']      = \array_slice( $r['titre_long'], 0, 15 );
		\wp_send_json_success( $r );
	}
);

/**
 * Écran d'administration.
 */
function ecran(): void {
	if ( ! \current_user_can( 'manage_options' ) ) {
		return;
	}
	$cfg = (int) \get_option( 'r18_seo_config', 0 );
	echo '<div class="wrap"><h1>SEO Rescue 18</h1>';
	if ( ! actif() ) {
		echo '<div class="notice notice-warning"><p>Active SEOPress pour utiliser cet écran.</p></div></div>';
		return;
	}
	echo '<p>Configure SEOPress et écrit un titre (60 caractères visés, 65 au plus) et une description (155 caractères au plus) pour chaque contenu publié. Un champ déjà rempli à la main n’est jamais écrasé, sauf si tu coches « Réécrire ».</p>';
	echo '<p><strong>Configuration globale :</strong> ' . ( $cfg ? 'appliquée le ' . \esc_html( \wp_date( 'j F Y à H:i', $cfg ) ) : 'jamais appliquée' ) . '.</p>';
	echo '<p><button type="button" class="button button-primary" id="r18-seo-config">Appliquer la configuration SEOPress</button> ';
	echo '<button type="button" class="button button-primary" id="r18-seo-lots">Optimiser tous les contenus</button> ';
	echo '<label><input type="checkbox" id="r18-seo-forcer"> Réécrire aussi les champs déjà remplis</label></p>';
	echo '<div id="r18-seo-out" aria-live="polite" style="max-width:900px"></div></div>';
	$nonce = \wp_create_nonce( 'r18_seo' );
	?>
	<script>
	( function () {
		var out = document.getElementById( 'r18-seo-out' );
		var post = function ( d ) {
			var b = new URLSearchParams( d );
			b.set( '_ajax_nonce', <?php echo \wp_json_encode( $nonce ); ?> );
			return fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: b } ).then( function ( r ) { return r.json(); } );
		};
		var ligne = function ( t ) { var p = document.createElement( 'p' ); p.textContent = t; out.appendChild( p ); return p; };
		document.getElementById( 'r18-seo-config' ).addEventListener( 'click', function () {
			out.innerHTML = '';
			post( { action: 'r18_seo_config' } ).then( function ( r ) { ( r.data.lignes || [ r.data.message ] ).forEach( ligne ); } );
		} );
		document.getElementById( 'r18-seo-lots' ).addEventListener( 'click', function () {
			out.innerHTML = '';
			var etat = ligne( 'Démarrage…' ), mod = 0, forcer = document.getElementById( 'r18-seo-forcer' ).checked ? '1' : '';
			var lot = function ( off ) {
				post( { action: 'r18_seo_lot', offset: off, forcer: forcer } ).then( function ( r ) {
					if ( ! r.success ) { etat.textContent = r.data.message; return; }
					mod += r.data.modifies;
					etat.textContent = r.data.suivant + ' / ' + r.data.total + ' contenus traités, ' + mod + ' mis à jour.';
					if ( off === 0 ) { r.data.exemples.forEach( function ( e ) { ligne( e.type + ' · ' + e.titre + ' · ' + e.desc ); } ); }
					if ( r.data.traites > 0 && r.data.suivant < r.data.total ) { lot( r.data.suivant ); } else { post( { action: 'r18_seo_uniques' } ).then( function ( u ) { etat.textContent += ' ' + ( u.data.reecrits || 0 ) + ' titre(s) en double rendus uniques. Terminé.'; } ); }
				} );
			};
			lot( 0 );
		} );
	} )();
	</script>
	<?php
}
