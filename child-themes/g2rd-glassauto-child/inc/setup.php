<?php
/**
 * Installation des contenus : pages, navigation, réglages de lecture.
 *
 * Deux portes d'entrée, qui font la même chose :
 *   - Apparence › Installation GlassAuto (bouton « Créer les contenus manquants ») ;
 *   - WP-CLI : `wp glassauto install` (inc/cli.php).
 *
 * L'installation est idempotente : une page dont le chemin existe déjà est
 * laissée telle quelle (sauf --force), la navigation n'est créée qu'une fois,
 * les réglages ne sont posés que s'ils sont encore à leur valeur par défaut.
 * Rien n'est jamais supprimé ; deux exceptions prudentes : un brouillon
 * jamais retouché portant un slug du plan (« Politique de confidentialité »
 * créée par WordPress) est complété et publié, et l'article d'exemple de
 * WordPress, s'il n'a pas été modifié, est mis à la corbeille (restaurable).
 *
 * Les contenus viennent de gutenberg-pages/*.html (markup Gutenberg complet,
 * copiable à la main dans l'éditeur de code) ou, pour l'accueil, de
 * l'assemblage des compositions patterns/*.php.
 *
 * @package G2RD_GlassAuto
 */

declare( strict_types = 1 );

namespace G2RD_GlassAuto;

\defined( 'ABSPATH' ) || exit;

/**
 * Plan des contenus.
 *
 * Clé = slug. `parent` = slug de la page parente (créée avant).
 * `file` = fichier de gutenberg-pages/ ; `patterns` = compositions assemblées.
 * `seo_title` / `seo_desc` = métadonnées SEOPress initiales.
 *
 * @return array<string,array<string,mixed>>
 */
function content_map(): array {
	// Téléphone lu dans Réglages › Général : aucune copie en dur.
	$tel = (string) centre_get( 'tel' );

	return array(
		'accueil'                      => array(
			'title'     => 'Accueil',
			'patterns'  => array( 'hero-accueil', 'marquee-prestations', 'prestations-bento', 'diagnostic', 'services', 'assurances', 'parcours', 'centre', 'avis-google', 'zone', 'acces', 'faq', 'cta-final' ),
			'template'  => 'page-libre',
			'role'      => 'front',
			'seo_title' => 'Réparation et remplacement de pare-brise à Vernon (27) | GlassAuto Vernon',
			'seo_desc'  => "Centre GlassAuto à Vernon : réparation d'impact, remplacement de pare-brise, vitres et optiques. Agréé assurances, sans avance de frais selon votre contrat. 1 chemin du Virolet, " . $tel . ".",
		),
		'prestations'                  => array(
			'title'     => 'Prestations',
			'file'      => 'prestations',
			'template'  => 'page-prestation',
			'excerpt'   => "Réparation, remplacement ou rénovation : le centre GlassAuto Vernon intervient sur l'ensemble des vitrages et des optiques de votre véhicule, de la citadine au poids lourd.",
			'seo_title' => 'Prestations : pare-brise, vitres, optiques à Vernon | GlassAuto Vernon',
			'seo_desc'  => "Réparation d'impact, remplacement de pare-brise, vitres latérales, lunette arrière, toit ouvrant, rétroviseurs et optiques de phares au centre GlassAuto de Vernon (27).",
		),
		'reparation-impact'            => array(
			'title'     => "Réparation d'impact de pare-brise à Vernon",
			'file'      => 'reparation-impact',
			'parent'    => 'prestations',
			'template'  => 'page-prestation',
			'excerpt'   => 'Un impact petit et bien placé se répare par injection de résine, sans changer le pare-brise. Plus vous venez tôt, plus la réparation a de chances de réussir.',
			'seo_title' => "Réparation d'impact de pare-brise à Vernon (27) | GlassAuto Vernon",
			'seo_desc'  => 'Impact sur votre pare-brise ? Réparation par injection de résine au centre GlassAuto de Vernon, prise en charge par votre assurance bris de glace. Vérifiez si votre impact est réparable.',
		),
		'remplacement-pare-brise'      => array(
			'title'     => 'Remplacement de pare-brise à Vernon',
			'file'      => 'remplacement-pare-brise',
			'parent'    => 'prestations',
			'template'  => 'page-prestation',
			'excerpt'   => 'Fissure, impact trop grand ou pare-brise brisé : nos techniciens remplacent votre pare-brise au centre de Vernon et gèrent le dossier avec votre assurance.',
			'seo_title' => 'Remplacement de pare-brise à Vernon (27) | GlassAuto Vernon',
			'seo_desc'  => 'Pare-brise fissuré ou brisé ? Remplacement au centre GlassAuto de Vernon, agréé assurances, sans avance de frais selon votre contrat. 1 chemin du Virolet, ' . $tel . '.',
		),
		'vitres-laterales'             => array(
			'title'     => 'Remplacement de vitre latérale à Vernon',
			'file'      => 'vitres-laterales',
			'parent'    => 'prestations',
			'template'  => 'page-prestation',
			'excerpt'   => "Vitre de portière ou de custode brisée après un choc ou une effraction : nous remplaçons le vitrage et retirons les éclats de verre de l'habitacle.",
			'seo_title' => 'Remplacement de vitre latérale à Vernon (27) | GlassAuto Vernon',
			'seo_desc'  => "Vitre de portière ou de custode brisée ? Remplacement rapide au centre GlassAuto de Vernon, nettoyage des éclats de verre, prise en charge par l'assurance bris de glace.",
		),
		'lunette-arriere'              => array(
			'title'     => 'Remplacement de lunette arrière à Vernon',
			'file'      => 'lunette-arriere',
			'parent'    => 'prestations',
			'template'  => 'page-prestation',
			'excerpt'   => "Une lunette arrière brisée laisse entrer la pluie et réduit la visibilité. Nous la remplaçons, y compris lorsqu'elle intègre un dégivrage ou une antenne.",
			'seo_title' => 'Remplacement de lunette arrière à Vernon (27) | GlassAuto Vernon',
			'seo_desc'  => 'Lunette arrière fêlée ou brisée ? Remplacement au centre GlassAuto de Vernon, voitures, breaks, SUV et utilitaires, prise en charge par votre assurance bris de glace.',
		),
		'vitrages-specifiques'         => array(
			'title'     => 'Vitrages spécifiques : toit ouvrant, utilitaires, poids lourds',
			'file'      => 'vitrages-specifiques',
			'parent'    => 'prestations',
			'template'  => 'page-prestation',
			'excerpt'   => 'Toit ouvrant et panoramique, utilitaires, camping-cars, poids lourds, engins agricoles et de chantier : le centre intervient sur tous les vitrages.',
			'seo_title' => 'Vitrages spécifiques : toit ouvrant, utilitaires, poids lourds | GlassAuto Vernon',
			'seo_desc'  => 'Toit ouvrant, toit panoramique, vitrage de camping-car, de poids lourd ou d’engin agricole : remplacement au centre GlassAuto de Vernon (27), sur rendez-vous.',
		),
		'retroviseurs'                 => array(
			'title'     => 'Remplacement de rétroviseur à Vernon',
			'file'      => 'retroviseurs',
			'parent'    => 'prestations',
			'template'  => 'page-prestation',
			'excerpt'   => 'Miroir fêlé ou rétroviseur arraché : selon les dégâts, nous remplaçons le miroir seul ou le rétroviseur complet.',
			'seo_title' => 'Remplacement de rétroviseur à Vernon (27) | GlassAuto Vernon',
			'seo_desc'  => 'Rétroviseur cassé ou miroir fêlé ? Remplacement du miroir ou du rétroviseur complet au centre GlassAuto de Vernon, sur rendez-vous.',
		),
		'optiques-de-phares'           => array(
			'title'     => 'Rénovation et remplacement d’optiques de phares à Vernon',
			'file'      => 'optiques-de-phares',
			'parent'    => 'prestations',
			'template'  => 'page-prestation',
			'excerpt'   => "Des optiques jaunies ou opaques réduisent l'éclairage de la route. Nous les rénovons, ou remplaçons le phare lorsqu'il est fendu ou cassé.",
			'seo_title' => 'Rénovation et remplacement d’optiques de phares à Vernon | GlassAuto Vernon',
			'seo_desc'  => 'Optiques de phares ternies, jaunies ou cassées ? Rénovation ou remplacement au centre GlassAuto de Vernon (27), pour retrouver un éclairage conforme.',
		),
		'calibrage-adas'               => array(
			'title'     => 'Calibrage des caméras ADAS après remplacement de pare-brise',
			'file'      => 'calibrage-adas',
			'parent'    => 'prestations',
			'template'  => 'page-prestation',
			'excerpt'   => "Les caméras d'aide à la conduite fixées derrière le pare-brise doivent être recalibrées après un remplacement, pour que les assistances fonctionnent correctement.",
			'seo_title' => 'Calibrage caméra ADAS après remplacement de pare-brise | GlassAuto Vernon',
			'seo_desc'  => 'Après un remplacement de pare-brise, la caméra d’aide à la conduite (ADAS) doit être recalibrée. Ce qu’il faut savoir et comment le centre GlassAuto de Vernon procède.',
		),
		'assurances-prise-en-charge'   => array(
			'title'     => 'Assurances et prise en charge du bris de glace',
			'file'      => 'assurances-prise-en-charge',
			'excerpt'   => "Centre agréé par la plupart des assurances : si votre contrat couvre le bris de glace, nous gérons le dossier et vous n'avancez pas les frais.",
			'seo_title' => 'Assurance bris de glace et prise en charge | GlassAuto Vernon',
			'seo_desc'  => 'Comment fonctionne la prise en charge du bris de glace par votre assurance au centre GlassAuto de Vernon : agrément, franchise, documents à apporter, aucune avance de frais.',
		),
		'notre-centre'                 => array(
			'title'     => 'Notre centre',
			'file'      => 'notre-centre',
			'excerpt'   => 'Membre du réseau GlassAuto, le centre de Vernon accueille particuliers et professionnels zone du Virolet, du lundi au vendredi.',
			'seo_title' => 'Notre centre de vitrage automobile à Vernon | GlassAuto Vernon',
			'seo_desc'  => 'Le centre GlassAuto Vernon vous accueille zone du Virolet, 1 chemin du Virolet, du lundi au vendredi. Services, véhicules pris en charge, équipe et accès.',
		),
		'zone-intervention'            => array(
			'title'     => "Zone d'intervention",
			'file'      => 'zone-intervention',
			'excerpt'   => "Le centre GlassAuto est situé à Vernon, au 1 chemin du Virolet. Nos clients viennent de la vallée de la Seine, du Vexin normand, de la vallée de l'Eure et des Yvelines voisines.",
			'seo_title' => "Zone d'intervention : Vernon, Eure et Yvelines | GlassAuto Vernon",
			'seo_desc'  => 'Le centre GlassAuto Vernon accueille les automobilistes de Vernon, Gaillon, Pacy-sur-Eure, Les Andelys, Bonnières-sur-Seine et des communes voisines. Distances et temps de trajet.',
		),
		'prendre-rendez-vous'          => array(
			'title'     => 'Prendre rendez-vous',
			'file'      => 'prendre-rendez-vous',
			'excerpt'   => 'Envoyez votre demande en ligne ou appelez-nous. Nous vous recontactons pour confirmer le créneau et vérifier votre prise en charge.',
			'seo_title' => 'Prendre rendez-vous | GlassAuto Vernon',
			'seo_desc'  => 'Prenez rendez-vous au centre GlassAuto de Vernon pour une réparation ou un remplacement de vitrage : demande en ligne ou par téléphone au ' . $tel . '.',
		),
		'contact'                      => array(
			'title'     => 'Contact',
			'file'      => 'contact',
			'excerpt'   => "Une question sur une intervention, votre prise en charge ou un devis ? Appelez-nous ou écrivez-nous : l'équipe vous répond pendant les heures d'ouverture.",
			'seo_title' => 'Contact | GlassAuto Vernon, 1 chemin du Virolet',
			'seo_desc'  => 'Contactez le centre GlassAuto de Vernon : ' . $tel . ', 1 chemin du Virolet, 27200 Vernon. Ouvert du lundi au vendredi. Formulaire de contact et demande de rappel.',
		),
		'faq'                          => array(
			'title'     => 'Questions fréquentes',
			'file'      => 'faq',
			'excerpt'   => 'Documents, prise en charge, réparation ou remplacement, durée, véhicules acceptés : les réponses de l’équipe du centre.',
			'seo_title' => 'Questions fréquentes sur le vitrage automobile | GlassAuto Vernon',
			'seo_desc'  => 'Les réponses du centre GlassAuto Vernon : documents à apporter, avance de frais, impact réparable, durée d’une intervention, véhicules pris en charge, horaires.',
		),
		'actualites'                   => array(
			'title'     => 'Actualités',
			'content'   => '',
			'role'      => 'posts',
			'seo_title' => 'Actualités et conseils vitrage automobile | GlassAuto Vernon',
			'seo_desc'  => 'Conseils et actualités du centre GlassAuto Vernon : réparer ou remplacer un pare-brise, que faire après un impact, assurance bris de glace, calibrage ADAS.',
		),
		'espace-partenaires'           => array(
			'title'     => 'Espace partenaires',
			'file'      => 'espace-partenaires',
			'template'  => 'page-libre',
			'role'      => 'partner',
			'seo_title' => 'Espace partenaires | GlassAuto Vernon',
			'seo_desc'  => 'Accès réservé aux apporteurs d’affaires du centre GlassAuto Vernon.',
			'noindex'   => true,
		),
		'mentions-legales'             => array(
			'title'     => 'Mentions légales',
			'file'      => 'mentions-legales',
			'seo_title' => 'Mentions légales | GlassAuto Vernon',
			'seo_desc'  => 'Mentions légales du site GlassAuto Vernon.',
			'noindex'   => true,
		),
		'politique-de-confidentialite' => array(
			'title'     => 'Politique de confidentialité',
			'file'      => 'politique-de-confidentialite',
			'role'      => 'privacy',
			'seo_title' => 'Politique de confidentialité | GlassAuto Vernon',
			'seo_desc'  => 'Données personnelles collectées par le site GlassAuto Vernon, finalités, durées de conservation et droits.',
			'noindex'   => true,
		),
		'politique-de-cookies'         => array(
			'title'     => 'Politique de cookies',
			'file'      => 'politique-de-cookies',
			'seo_title' => 'Politique de cookies | GlassAuto Vernon',
			'seo_desc'  => 'Cookies et traceurs utilisés par le site GlassAuto Vernon, et comment les gérer.',
			'noindex'   => true,
		),
		'conditions-utilisation'       => array(
			'title'     => "Conditions d'utilisation",
			'file'      => 'conditions-utilisation',
			'seo_title' => "Conditions d'utilisation | GlassAuto Vernon",
			'seo_desc'  => "Conditions d'utilisation du site et de l'espace partenaires GlassAuto Vernon.",
			'noindex'   => true,
		),
		'accessibilite'                => array(
			'title'     => 'Accessibilité',
			'file'      => 'accessibilite',
			'seo_title' => 'Accessibilité | GlassAuto Vernon',
			'seo_desc'  => 'Déclaration d’accessibilité du site GlassAuto Vernon (RGAA) : état de conformité, contenus non accessibles, contact.',
			'noindex'   => true,
		),
	);
}

/**
 * Markup d'une composition, PHP évalué, en-tête ignoré.
 *
 * @param string $slug Nom du fichier sans extension dans patterns/.
 * @return string
 */
function pattern_markup( string $slug ): string {
	$file = \get_stylesheet_directory() . '/patterns/' . \basename( $slug ) . '.php';
	if ( ! \is_readable( $file ) ) {
		return '';
	}
	\ob_start();
	include $file; // phpcs:ignore PHPCS_SecurityAudit.Misc.IncludeMismatch.ErrMiscIncludeMismatchNoExt -- fichier patterns/<basename>.php du thème, jamais une entrée utilisateur.
	return \trim( (string) \ob_get_clean() );
}

/**
 * Contenu Gutenberg d'une entrée du plan.
 *
 * @param array<string,mixed> $entry Entrée.
 * @return string
 */
function content_markup( array $entry ): string {
	if ( isset( $entry['content'] ) ) {
		return (string) $entry['content'];
	}
	if ( ! empty( $entry['patterns'] ) ) {
		return \implode( "\n\n", \array_filter( \array_map( __NAMESPACE__ . '\pattern_markup', (array) $entry['patterns'] ) ) );
	}
	if ( ! empty( $entry['file'] ) ) {
		$file = \get_stylesheet_directory() . '/gutenberg-pages/' . \basename( (string) $entry['file'] ) . '.html';
		if ( \is_readable( $file ) ) {
			return \trim( (string) \file_get_contents( $file ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- fichier du thème.
		}
	}
	return '';
}

/**
 * Trouve un contenu par chemin (slug, avec parent).
 *
 * @param string $slug   Slug.
 * @param string $parent_slug Slug du parent, ou chaîne vide.
 * @return \WP_Post|null
 */
function find_content( string $slug, string $parent_slug = '' ) {
	$path = '' !== $parent_slug ? $parent_slug . '/' . $slug : $slug;
	$post = \get_page_by_path( $path, OBJECT, 'page' );
	return $post instanceof \WP_Post ? $post : null;
}

/**
 * Crée les contenus manquants.
 *
 * @param bool $force Réécrire le contenu des pages existantes (jamais leur titre ni leur statut).
 * @return array<string,string> slug → « créée », « existante », « mise à jour » ou « erreur : … ».
 */
function install_contents( bool $force = false ): array {
	$report = array();
	$ids    = array();

	foreach ( content_map() as $slug => $entry ) {
		$parent_slug = (string) ( $entry['parent'] ?? '' );
		$parent_id   = $ids[ $parent_slug ] ?? 0;
		$existing    = find_content( $slug, $parent_slug );

		if ( $existing ) {
			$ids[ $slug ] = $existing->ID;
			// Brouillon jamais retouché (ex. « Politique de confidentialité » créée
			// par WordPress à l'installation, avec son texte d'exemple) : on le
			// complète et on le publie. Un brouillon modifié depuis est respecté.
			$contenu   = \trim( (string) $existing->post_content );
			$content   = content_markup( $entry );
			$untouched = '' === $contenu
				|| $contenu === $content
				|| $existing->post_modified_gmt === $existing->post_date_gmt
				|| ( \class_exists( '\WP_Privacy_Policy_Content' ) && \trim( (string) \WP_Privacy_Policy_Content::get_default_content() ) === $contenu );
			if ( 'draft' === $existing->post_status && $untouched ) {
				if ( '' !== $content ) {
					\wp_update_post(
						array(
							'ID'           => $existing->ID,
							'post_status'  => 'publish',
							'post_content' => $content,
							'post_excerpt' => (string) ( $entry['excerpt'] ?? '' ),
						)
					);
					apply_meta( $existing->ID, $entry );
					$report[ $slug ] = 'brouillon complété et publié';
					continue;
				}
			}
			if ( $force ) {
				$content = content_markup( $entry );
				if ( '' !== $content ) {
					\wp_update_post(
						array(
							'ID'           => $existing->ID,
							'post_content' => $content,
						)
					);
					apply_meta( $existing->ID, $entry );
					$report[ $slug ] = 'mise à jour';
					continue;
				}
			}
			$report[ $slug ] = 'existante';
			continue;
		}

		$id = \wp_insert_post(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'post_title'     => (string) $entry['title'],
				'post_name'      => $slug,
				'post_parent'    => (int) $parent_id,
				'post_content'   => content_markup( $entry ),
				'post_excerpt'   => (string) ( $entry['excerpt'] ?? '' ),
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			),
			true
		);
		if ( \is_wp_error( $id ) ) {
			$report[ $slug ] = 'erreur : ' . $id->get_error_message();
			continue;
		}
		$ids[ $slug ] = (int) $id;
		apply_meta( (int) $id, $entry );
		$report[ $slug ] = 'créée';
	}

	install_settings( $ids );
	return $report;
}

/**
 * Métadonnées d'une page : modèle, SEOPress, robots.
 *
 * @param int                 $id    Identifiant.
 * @param array<string,mixed> $entry Entrée du plan.
 */
function apply_meta( int $id, array $entry ): void {
	if ( ! empty( $entry['template'] ) ) {
		\update_post_meta( $id, '_wp_page_template', (string) $entry['template'] );
	}
	// Métadonnées SEOPress : posées seulement si vides, pour ne pas écraser un travail éditorial.
	if ( ! empty( $entry['seo_title'] ) && '' === (string) \get_post_meta( $id, '_seopress_titles_title', true ) ) {
		\update_post_meta( $id, '_seopress_titles_title', (string) $entry['seo_title'] );
	}
	if ( ! empty( $entry['seo_desc'] ) && '' === (string) \get_post_meta( $id, '_seopress_titles_desc', true ) ) {
		\update_post_meta( $id, '_seopress_titles_desc', (string) $entry['seo_desc'] );
	}
	if ( ! empty( $entry['noindex'] ) ) {
		\update_post_meta( $id, '_seopress_robots_index', 'yes' );
	}
}

/**
 * Réglages de lecture et permaliens, posés seulement s'ils sont encore par défaut.
 *
 * @param array<string,int> $ids Identifiants par slug.
 */
function install_settings( array $ids ): void {
	if ( 'page' !== \get_option( 'show_on_front' ) ) {
		\update_option( 'show_on_front', 'page' );
	}
	if ( isset( $ids['accueil'] ) && ! (int) \get_option( 'page_on_front' ) ) {
		\update_option( 'page_on_front', $ids['accueil'] );
	}
	if ( isset( $ids['actualites'] ) && ! (int) \get_option( 'page_for_posts' ) ) {
		\update_option( 'page_for_posts', $ids['actualites'] );
	}
	if ( isset( $ids['politique-de-confidentialite'] ) && ! (int) \get_option( 'wp_page_for_privacy_policy' ) ) {
		\update_option( 'wp_page_for_privacy_policy', $ids['politique-de-confidentialite'] );
	}
	// Articles sous /actualites/ (la page des articles), pages à la racine.
	// Posé si la structure est encore vide ou à la valeur générique.
	if ( \in_array( (string) \get_option( 'permalink_structure' ), array( '', '/%postname%/' ), true ) ) {
		\update_option( 'permalink_structure', '/actualites/%postname%/' );
		\delete_option( 'rewrite_rules' ); // Régénérées à la prochaine requête, aussi en WP-CLI.
		\flush_rewrite_rules( false );
	}
	if ( '' === (string) \get_option( 'timezone_string' ) ) {
		\update_option( 'timezone_string', 'Europe/Paris' );
	}
	if ( 'Un site utilisant WordPress' === \get_option( 'blogdescription' ) || 'Just another WordPress site' === \get_option( 'blogdescription' ) ) {
		\update_option( 'blogdescription', 'Réparation et remplacement de pare-brise à Vernon' );
	}
	\update_option( 'default_comment_status', 'closed' );
	\update_option( 'g2rd_glassauto_installed', VERSION );
}

/**
 * Navigation principale (bloc Navigation du modèle d'en-tête).
 *
 * Sans `ref`, le bloc Navigation utilise la navigation la plus récente :
 * on n'en crée qu'une. Si une navigation existe déjà, rien n'est touché.
 *
 * @return string « créée » ou « existante ».
 */
function install_navigation(): string {
	// On cherche NOTRE navigation (par slug) : WordPress peut déjà en avoir
	// fabriqué une de secours (« Navigation », liste des pages) qu'il ne faut
	// pas prendre pour la nôtre.
	$existing = \get_posts(
		array(
			'post_type'   => 'wp_navigation',
			'name'        => 'navigation-principale',
			'post_status' => 'publish',
			'numberposts' => 1,
			'fields'      => 'ids',
		)
	);
	if ( ! empty( $existing ) ) {
		return 'existante';
	}
	$link        = static function ( string $label, string $slug, string $parent_slug = '' ): string {
		$page = find_content( $slug, $parent_slug );
		$url  = $page ? \get_permalink( $page ) : \home_url( '/' . ( '' !== $parent_slug ? $parent_slug . '/' : '' ) . $slug . '/' );
		$id   = $page ? ',"id":' . $page->ID . ',"kind":"post-type","type":"page"' : '';
		return \sprintf( '<!-- wp:navigation-link {"label":"%s","url":"%s"%s} /-->', \esc_attr( $label ), \esc_url( (string) $url ), $id );
	};
	$prestations = find_content( 'prestations' );
	$sub         = \implode(
		"\n",
		array(
			$link( "Réparation d'impact", 'reparation-impact', 'prestations' ),
			$link( 'Remplacement de pare-brise', 'remplacement-pare-brise', 'prestations' ),
			$link( 'Vitres latérales', 'vitres-laterales', 'prestations' ),
			$link( 'Lunette arrière', 'lunette-arriere', 'prestations' ),
			$link( 'Vitrages spécifiques', 'vitrages-specifiques', 'prestations' ),
			$link( 'Rétroviseurs', 'retroviseurs', 'prestations' ),
			$link( 'Optiques de phares', 'optiques-de-phares', 'prestations' ),
			$link( 'Calibrage des caméras', 'calibrage-adas', 'prestations' ),
		)
	);
	$content     = \sprintf(
		'<!-- wp:navigation-submenu {"label":"Prestations","url":"%1$s"%2$s} -->' . "\n%3\$s\n" . '<!-- /wp:navigation-submenu -->' . "\n%4\$s\n%5\$s\n%6\$s\n%7\$s\n%8\$s",
		\esc_url( $prestations ? (string) \get_permalink( $prestations ) : \home_url( '/prestations/' ) ),
		$prestations ? ',"id":' . $prestations->ID . ',"kind":"post-type","type":"page"' : '',
		$sub,
		$link( 'Assurances', 'assurances-prise-en-charge' ),
		$link( 'Notre centre', 'notre-centre' ),
		$link( "Zone d'intervention", 'zone-intervention' ),
		$link( 'Actualités', 'actualites' ),
		$link( 'Contact', 'contact' )
	);
	$id          = \wp_insert_post(
		array(
			'post_type'    => 'wp_navigation',
			'post_status'  => 'publish',
			'post_title'   => 'Navigation principale',
			'post_name'    => 'navigation-principale',
			'post_content' => $content,
		),
		true
	);
	return \is_wp_error( $id ) ? 'erreur : ' . $id->get_error_message() : 'créée';
}

/**
 * Le thème parent recopie en base ses propres modèles et éléments (header,
 * footer, page-contact…) sous le nom du thème actif (FseSync). Avec un thème
 * enfant, ces copies masquent les fichiers de l'enfant. On retire les copies
 * restées identiques au fichier du parent ; une version modifiée dans
 * l'éditeur de site est conservée.
 *
 * @return int Nombre de copies retirées.
 */
function purge_parent_template_copies(): int {
	$removed = 0;
	$posts   = \get_posts(
		array(
			'post_type'      => array( 'wp_template', 'wp_template_part' ),
			'post_status'    => array( 'publish', 'draft', 'auto-draft' ),
			'posts_per_page' => -1,
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'wp_theme',
					'field'    => 'name',
					'terms'    => array( \get_stylesheet() ),
				),
			),
		)
	);
	$clean   = static function ( string $html ): string {
		return (string) \preg_replace( '~[\\\\\s]+~', '', $html );
	};

	foreach ( $posts as $post ) {
		$dir  = 'wp_template' === $post->post_type ? 'templates' : 'parts';
		$file = \get_template_directory() . '/' . $dir . '/' . $post->post_name . '.html';
		if ( ! \is_readable( $file ) ) {
			continue;
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- fichier local du thème parent.
		if ( $clean( (string) \file_get_contents( $file ) ) === $clean( $post->post_content ) ) {
			\wp_delete_post( $post->ID, true );
			++$removed;
		}
	}

	if ( $removed ) {
		\wp_clean_themes_cache();
	}
	return $removed;
}

\add_action(
	'admin_init',
	function (): void {
		$mark = \get_option( 'g2rd_recreate_version' ) . '|' . VERSION;
		if ( \get_option( 'g2rd_glassauto_fse_clean' ) !== $mark && \current_user_can( 'manage_options' ) && ! \wp_doing_ajax() ) {
			purge_parent_template_copies();
			\update_option( 'g2rd_glassauto_fse_clean', $mark, false );
		}
	},
	25
);

/**
 * Tout installer.
 *
 * @param bool $force Réécrire le contenu des pages existantes.
 * @return array<string,string>
 */
function install_all( bool $force = false ): array {
	$report                       = install_contents( $force );
	$report                       = \array_merge( $report, install_posts( $force ) );
	$report['navigation']         = install_navigation();
	$report['gabarits du parent'] = \sprintf( '%d copie(s) retirée(s)', purge_parent_template_copies() );
	return $report;
}

// Articles (rubrique Conseils).

/**
 * Plan des articles : six conseils rédigés pour le référencement local,
 * contenus dans gutenberg-posts/<slug>.html. Les dates sont fixes pour que
 * l'ordre d'affichage soit le même sur tous les environnements.
 *
 * @return array<string,array<string,string>>
 */
function posts_map(): array {
	return array(
		'reparer-ou-remplacer-son-pare-brise'             => array(
			'title'     => 'Réparer ou remplacer son pare-brise : comment décider',
			'excerpt'   => 'Taille, position, distance au bord : les trois critères qui décident entre une réparation d’impact et un remplacement de pare-brise.',
			'image'     => 'pare-brise-fissure.webp',
			'date'      => '2026-10-02 09:00:00',
			'seo_title' => 'Réparer ou remplacer son pare-brise ? Les critères | GlassAuto Vernon',
			'seo_desc'  => 'Impact réparable ou pare-brise à remplacer ? Taille, position et distance au bord : les critères du technicien, et pourquoi venir vite au centre de Vernon.',
		),
		'que-faire-apres-un-impact-sur-le-pare-brise'     => array(
			'title'     => 'Que faire après un impact sur le pare-brise',
			'excerpt'   => 'Protéger l’impact, éviter la fissure, déclarer le sinistre et prendre rendez-vous : les six gestes qui comptent dans les jours qui suivent.',
			'image'     => 'essuie-glace.webp',
			'date'      => '2026-10-06 09:00:00',
			'seo_title' => 'Impact sur le pare-brise : que faire ? | GlassAuto Vernon',
			'seo_desc'  => 'Après un gravillon : protéger l’impact, éviter qu’il fissure, déclarer le bris de glace dans les délais et prendre rendez-vous à Vernon. Les gestes utiles.',
		),
		'pare-brise-fissure-et-controle-technique'        => array(
			'title'     => 'Pare-brise fissuré et contrôle technique : ce qui passe, ce qui ne passe pas',
			'excerpt'   => 'Défaillance mineure, majeure ou critique : les seuils de l’instruction technique pour le pare-brise, et comment éviter la contre-visite.',
			'image'     => 'technicien-carrosserie.webp',
			'date'      => '2026-10-10 09:00:00',
			'seo_title' => 'Pare-brise fissuré et contrôle technique : les règles | GlassAuto Vernon',
			'seo_desc'  => 'Impact de 30 mm, fissure de 300 mm, zone des essuie-glaces : les seuils du contrôle technique pour le pare-brise, les niveaux de défaillance et le délai de contre-visite.',
		),
		'assurance-bris-de-glace-ce-qu-il-faut-savoir'    => array(
			'title'     => 'Assurance bris de glace : ce qu’il faut savoir',
			'excerpt'   => 'Ce que couvre la garantie, la franchise, l’absence de malus, le délai de déclaration et le libre choix du réparateur, expliqués simplement.',
			'image'     => 'remise-cles.webp',
			'date'      => '2026-10-14 09:00:00',
			'seo_title' => 'Assurance bris de glace : garantie, franchise, malus | GlassAuto Vernon',
			'seo_desc'  => 'Garantie bris de glace : vitrages couverts, franchise, aucun malus, déclaration sous cinq jours ouvrés, libre choix du réparateur. Le centre agréé de Vernon vous explique.',
		),
		'calibrage-adas-apres-remplacement-du-pare-brise' => array(
			'title'     => 'Calibrage ADAS : pourquoi la caméra doit être réglée après un pare-brise neuf',
			'excerpt'   => 'Caméra de pare-brise, calibrage statique ou dynamique, signes d’un mauvais réglage et prise en charge : ce qu’il faut savoir avant de changer le vitrage.',
			'image'     => 'atelier-technicien.webp',
			'date'      => '2026-10-18 09:00:00',
			'seo_title' => 'Calibrage ADAS après remplacement de pare-brise | GlassAuto Vernon',
			'seo_desc'  => 'Pourquoi recalibrer la caméra ADAS après un pare-brise neuf, calibrage statique ou dynamique, symptômes d’un mauvais réglage et prise en charge par l’assurance.',
		),
		'preparer-son-rendez-vous-pare-brise-a-vernon'    => array(
			'title'     => 'Préparer son rendez-vous pare-brise à Vernon',
			'excerpt'   => 'Les informations à préparer, les documents à apporter, l’accès au centre du Virolet et les précautions après un pare-brise neuf.',
			'image'     => 'vernon-chateau-tourelles.webp',
			'date'      => '2026-10-22 09:00:00',
			'seo_title' => 'Préparer son rendez-vous pare-brise à Vernon | GlassAuto Vernon',
			'seo_desc'  => 'Immatriculation, photo, documents, accès au centre GlassAuto de Vernon (chemin du Virolet) et précautions après la pose : tout pour un rendez-vous sans accroc.',
		),
	);
}

/**
 * Importe une photo du thème dans la médiathèque (une seule fois).
 *
 * @param string $file Nom du fichier dans assets/img/photos/.
 * @param string $alt  Texte alternatif.
 * @return int Identifiant du média, 0 si impossible.
 */
function install_image( string $file, string $alt ): int {
	$found = \get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_g2rd_glassauto_source', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- une requête à l'installation.
			'meta_value'     => $file, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- idem.
		)
	);
	if ( ! empty( $found ) ) {
		return (int) $found[0];
	}
	$src = \get_stylesheet_directory() . '/assets/img/photos/' . \basename( $file );
	if ( ! \is_readable( $src ) ) {
		return 0;
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$upload = \wp_upload_bits( \basename( $file ), null, (string) \file_get_contents( $src ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- fichier du thème.
	if ( ! empty( $upload['error'] ) ) {
		return 0;
	}
	$type = \wp_check_filetype( $upload['file'] );
	$id   = \wp_insert_attachment(
		array(
			'post_mime_type' => (string) $type['type'],
			'post_title'     => $alt,
			'post_status'    => 'inherit',
		),
		$upload['file']
	);
	if ( \is_wp_error( $id ) || ! $id ) {
		return 0;
	}
	\wp_update_attachment_metadata( (int) $id, \wp_generate_attachment_metadata( (int) $id, $upload['file'] ) );
	\update_post_meta( (int) $id, '_wp_attachment_image_alt', $alt );
	\update_post_meta( (int) $id, '_g2rd_glassauto_source', $file );
	return (int) $id;
}

/**
 * Crée les articles manquants dans la catégorie « Conseils ».
 *
 * @param bool $force Réécrire le contenu des articles existants (titre, date et statut conservés).
 * @return array<string,string> slug → état.
 */
function install_posts( bool $force = false ): array {
	$report = array();
	$cat    = \get_term_by( 'slug', 'conseils', 'category' );
	$cat_id = $cat instanceof \WP_Term ? (int) $cat->term_id : 0;
	if ( ! $cat_id ) {
		$term   = \wp_insert_term(
			'Conseils',
			'category',
			array(
				'slug'        => 'conseils',
				'description' => 'Les conseils du centre GlassAuto Vernon : impacts, pare-brise, assurance, contrôle technique, calibrage.',
			)
		);
		$cat_id = \is_wp_error( $term ) ? 0 : (int) $term['term_id'];
	}

	// Article d'exemple de WordPress (« Bonjour tout le monde ! »), jamais
	// modifié : mis à la corbeille pour ne pas apparaître dans les Actualités.
	// Il reste restaurable ; un article d'exemple retouché est conservé.
	$exemple = \get_post( 1 );
	if ( $exemple instanceof \WP_Post && 'post' === $exemple->post_type && 'publish' === $exemple->post_status
		&& \in_array( $exemple->post_name, array( 'hello-world', 'bonjour-tout-le-monde' ), true )
		&& $exemple->post_modified_gmt === $exemple->post_date_gmt ) {
		\wp_trash_post( 1 );
		$report['article exemple WordPress'] = 'mis à la corbeille';
	}

	foreach ( posts_map() as $slug => $entry ) {
		$file     = \get_stylesheet_directory() . '/gutenberg-posts/' . \basename( $slug ) . '.html';
		$content  = \is_readable( $file ) ? \trim( (string) \file_get_contents( $file ) ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- fichier du thème.
		$existing = \get_page_by_path( $slug, OBJECT, 'post' );

		if ( $existing instanceof \WP_Post ) {
			if ( $force && '' !== $content ) {
				\wp_update_post(
					array(
						'ID'           => $existing->ID,
						'post_content' => $content,
						'post_excerpt' => $entry['excerpt'],
					)
				);
				apply_meta( $existing->ID, $entry );
				$report[ 'article ' . $slug ] = 'mis à jour';
				continue;
			}
			$report[ 'article ' . $slug ] = 'existant';
			continue;
		}
		if ( '' === $content ) {
			$report[ 'article ' . $slug ] = 'erreur : fichier gutenberg-posts/' . $slug . '.html introuvable';
			continue;
		}
		$id = \wp_insert_post(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'post_title'     => $entry['title'],
				'post_name'      => $slug,
				'post_content'   => $content,
				'post_excerpt'   => $entry['excerpt'],
				'post_date'      => $entry['date'],
				'post_category'  => $cat_id ? array( $cat_id ) : array(),
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			),
			true
		);
		if ( \is_wp_error( $id ) ) {
			$report[ 'article ' . $slug ] = 'erreur : ' . $id->get_error_message();
			continue;
		}
		apply_meta( (int) $id, $entry );
		$image = install_image( $entry['image'], $entry['title'] );
		if ( $image ) {
			\set_post_thumbnail( (int) $id, $image );
		}
		$report[ 'article ' . $slug ] = 'créé';
	}
	return $report;
}

// Apparence › Installation GlassAuto.

\add_action(
	'admin_menu',
	function (): void {
		\add_theme_page(
			\__( 'Installation GlassAuto', 'g2rd-glassauto-child' ),
			\__( 'Installation GlassAuto', 'g2rd-glassauto-child' ),
			'manage_options',
			'glassauto-installation',
			__NAMESPACE__ . '\render_install_page'
		);
	}
);

/**
 * Page d'installation.
 */
function render_install_page(): void {
	if ( ! \current_user_can( 'manage_options' ) ) {
		return;
	}
	$report = null;
	if ( isset( $_POST['glassauto_install'] ) && \check_admin_referer( 'glassauto_install' ) ) {
		$report = install_all( ! empty( $_POST['glassauto_force'] ) );
	} elseif ( isset( $_POST['glassauto_seo'] ) && \check_admin_referer( 'glassauto_seo' ) ) {
		$report = seopress_prefill();
	}
	echo '<div class="wrap"><h1>' . \esc_html__( 'Installation GlassAuto Vernon', 'g2rd-glassauto-child' ) . '</h1>';
	echo '<p>' . \esc_html__( "Crée les pages manquantes depuis gutenberg-pages/ et patterns/, les articles Conseils depuis gutenberg-posts/, la navigation principale et les réglages de lecture. Un contenu existant n'est jamais remplacé, sauf si vous cochez la case ci-dessous (son titre et son statut sont conservés).", 'g2rd-glassauto-child' ) . '</p>';

	if ( \is_array( $report ) ) {
		echo '<h2>' . \esc_html__( 'Résultat', 'g2rd-glassauto-child' ) . '</h2><table class="widefat striped" style="max-width:40rem"><tbody>';
		foreach ( $report as $slug => $state ) {
			echo '<tr><td><code>' . \esc_html( $slug ) . '</code></td><td>' . \esc_html( $state ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}

	echo '<form method="post">';
	\wp_nonce_field( 'glassauto_install' );
	echo '<p><label><input type="checkbox" name="glassauto_force" value="1"> ' . \esc_html__( 'Réécrire aussi le contenu des pages existantes (perte des modifications faites dans l’éditeur)', 'g2rd-glassauto-child' ) . '</label></p>';
	\submit_button( \__( 'Créer les contenus manquants', 'g2rd-glassauto-child' ), 'primary', 'glassauto_install' );
	echo '</form>';

	echo '<h2>' . \esc_html__( 'SEOPress', 'g2rd-glassauto-child' ) . '</h2>';
	echo '<p>' . \esc_html__( "Le thème n'émet aucune donnée structurée : SEOPress porte les titres, sitemaps et le Knowledge Graph, SEOPress PRO le schéma LocalBusiness et le fil d'Ariane. Le bouton ci-dessous préremplit ces réglages avec les informations du centre ; une valeur déjà saisie dans SEOPress n'est jamais écrasée.", 'g2rd-glassauto-child' ) . '</p>';
	echo '<form method="post">';
	\wp_nonce_field( 'glassauto_seo' );
	\submit_button( \__( 'Préremplir SEOPress', 'g2rd-glassauto-child' ), 'secondary', 'glassauto_seo' );
	echo '</form>';
	echo '<h3>' . \esc_html__( 'Valeurs attendues dans SEOPress PRO › Local Business', 'g2rd-glassauto-child' ) . '</h3>';
	echo '<p>' . \esc_html__( 'Issues de Réglages › Général › GlassAuto Vernon, pour contrôle :', 'g2rd-glassauto-child' ) . '</p>';
	echo '<table class="widefat striped" style="max-width:40rem"><tbody>';
	foreach ( local_business_resume() as $k => $v ) {
		echo '<tr><th scope="row">' . \esc_html( $k ) . '</th><td>' . \esc_html( $v ) . '</td></tr>';
	}
	echo '</tbody></table></div>';
}
