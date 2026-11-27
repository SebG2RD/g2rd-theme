<?php
/**
 * Installation de la structure : termes, pages aux URL historiques,
 * réglages de lecture et de permaliens. Utilisé par l'écran
 * Outils › Préparer Rescue 18 et par la commande `wp rescue18 installer`.
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18;

\defined( 'ABSPATH' ) || exit;

/**
 * Crée les termes de base (idempotent).
 *
 * @return string[] Journal.
 */
function install_terms(): array {
	$log  = array();
	$sets = array(
		'r18_type_doc'  => array(
			'gdo'         => 'GDO',
			'gto'         => 'GTO',
			'gnr'         => 'GNR',
			'retex'       => 'RETEX / PEX',
			'referentiel' => 'Référentiels',
			'pio'         => 'PIO',
			'memento'     => 'Mémentos',
			'vademecum'   => 'Vadémécums',
			'pex'         => 'PEX',
			'etude'       => 'Études et rapports',
			'memoire'     => 'Mémoires',
		),
		'r18_organisme' => array(
			'dgscgc'      => 'DGSCGC',
			'ensosp-pnrs' => 'ENSOSP / PNRS',
			'legifrance'  => 'Légifrance',
		),
		'r18_public'    => array(
			'jsp' => 'JSP',
			'spv' => 'SPV',
			'spp' => 'SPP',
		),
	);
	foreach ( $sets as $tax => $terms ) {
		foreach ( $terms as $slug => $name ) {
			if ( ! \term_exists( $slug, $tax ) ) {
				\wp_insert_term( $name, $tax, array( 'slug' => $slug ) );
			}
		}
	}
	if ( ! \term_exists( 'actualites-des-organismes', 'category' ) ) {
		\wp_insert_term( 'Actualités des organismes', 'category', array( 'slug' => 'actualites-des-organismes', 'description' => 'Actualités de l\'ENSOSP et des organismes de la sécurité civile, résumées avec un lien vers la source.' ) );
	}
	foreach ( specialites_defaut() as $slug => [ $name, $desc, $icon ] ) {
		$t = \term_exists( $slug, 'r18_specialite' );
		if ( ! $t ) {
			$t = \wp_insert_term(
				$name,
				'r18_specialite',
				array(
					'slug'        => $slug,
					'description' => $desc,
				)
			);
		}
		if ( \is_array( $t ) ) {
			\update_term_meta( (int) $t['term_id'], 'r18_icone', $icon );
		}
	}
	$log[] = 'Termes : types de document, organismes, publics et 12 spécialités en place.';
	return $log;
}

/**
 * Pages gérées par le thème, aux URL historiques.
 *
 * @return array<int,array{0:string,1:string,2:string,3:string,4:string}> slug, titre, parent, composition, type
 */
function managed_pages(): array {
	return array(
		array( 'accueil', 'Accueil', '', '', '' ),
		array( 'articles', 'Actualités', '', '', '' ),
		array( 'documentations-referentiels', 'Documentation et référentiels', '', 'rescue18/page-bibliotheque', '' ),
		array( 'juridique', 'Juridique sapeurs-pompiers', 'documentations-referentiels', 'rescue18/page-juridique', '' ),
		array( 'referentiels-formations', 'Référentiels de formation', 'documentations-referentiels', 'rescue18/page-bibliotheque', 'referentiel' ),
		array( 'referentiels-techniques', 'Référentiels techniques', 'documentations-referentiels', 'rescue18/page-bibliotheque', 'referentiel' ),
		array( 'gnr-vademecum', 'GNR et vadémécum', 'documentations-referentiels', 'rescue18/page-bibliotheque', 'gnr' ),
		array( 'formation-secours-durgence-aux-personnes-suap', 'Formation secours d\'urgence aux personnes (SUAP)', 'documentations-referentiels', 'rescue18/page-bibliotheque', 'referentiel' ),
		array( 'guides-doctrines-operationnelles-gdo-guides-techniques-operationnelles-gto', 'GDO / GTO', '', 'rescue18/page-bibliotheque', 'gdo,gto' ),
		array( 'guides-doctrines-operationnelles-gdo', 'GDO, guides de doctrine opérationnelle', 'guides-doctrines-operationnelles-gdo-guides-techniques-operationnelles-gto', 'rescue18/page-bibliotheque', 'gdo' ),
		array( 'guides-techniques-operationnelles-gto', 'GTO, guides de techniques opérationnelles', 'guides-doctrines-operationnelles-gdo-guides-techniques-operationnelles-gto', 'rescue18/page-bibliotheque', 'gto' ),
		array( 'retex', 'Documents PEX et RETEX', '', 'rescue18/page-bibliotheque', 'retex' ),
		array( 'formation', 'Formation', '', 'rescue18/page-formation', '' ),
		array( 'quiz', 'Tous les quiz', 'formation', 'rescue18/page-quiz', '' ),
		array( 'fiches-memo', 'Fiches mémo', 'formation', 'rescue18/page-fiches-memo', '' ),
		array( 'sujets', 'Sujets des concours', 'recrutement/concours', 'rescue18/page-annales', '' ),
		array( 'ssuap-le-podcast', 'SSUAP Le Podcast', '', 'rescue18/page-podcasts', '' ),
		array( 'outils', 'Boîte à outils', '', 'outils', '' ),
		array( 'dimensionnement-dps', 'Calcul du RIS et dimensionnement d\'un DPS', 'outils', 'outil:dimensionnement-dps', '' ),
		array( 'autonomie-ari', 'Calcul d\'autonomie ARI', 'outils', 'outil:autonomie-ari', '' ),
		array( 'noria', 'Dimensionnement d\'une noria', 'outils', 'outil:noria', '' ),
		array( 'messages-operationnels', 'Générateur de messages opérationnels', 'outils', 'outil:messages-operationnels', '' ),
		array( 'mousse', 'Calcul mousse et émulseur', 'outils', 'outil:mousse', '' ),
		array( 'hydraulique', 'Calcul hydraulique : pression de refoulement', 'outils', 'outil:hydraulique', '' ),
		array( 'indemnites-spv', 'Simulateur d\'indemnités SPV', 'outils', 'outil:indemnites-spv', '' ),
		array( 'aide-memoire-ssuap', 'Aide-mémoire SSUAP : Glasgow et constantes', 'outils', 'outil:aide-memoire-ssuap', '' ),
		array( 'actualites-des-sdis', 'Actualités des SDIS', '', 'rescue18/page-actus-sdis', '' ),
		array( 'carte-des-casernes', 'Carte des casernes de France', '', 'rescue18/page-carte-casernes', '' ),
		array( 'casernes-du-monde', 'Les casernes du monde', '', 'rescue18/page-casernes-monde', '' ),
		array( 'recrutement', 'Recrutement', '', 'rescue18/page-recrutement', '' ),
		array( 'offres-emploi', 'Offres d\'emploi des SDIS', 'recrutement', 'rescue18/page-offres', '' ),
		array( 'concours', 'Calendrier des concours', 'recrutement', 'rescue18/page-concours', '' ),
		array( 'devenir-pompier', 'Devenir sapeur-pompier', '', 'rescue18/page-devenir-pompier', '' ),
		array( 'partenaires', 'Nos partenaires', '', 'rescue18/page-partenaires', '' ),
		array( 'accessibilite', 'Déclaration d\'accessibilité', '', 'rescue18/page-accessibilite', '' ),
	);
}

/**
 * Contenu initial d'une page gérée (composition, boîte à outils, outil).
 *
 * @param string $pattern Composition ou « outils » / « outil:slug ».
 * @param string $type    Type documentaire présélectionné.
 * @return string
 */
function managed_page_content( string $pattern, string $type = '' ): string {
	if ( 'outils' === $pattern ) {
		return outils_page_content();
	}
	if ( \str_starts_with( $pattern, 'outil:' ) ) {
		return outil_page_content( \substr( $pattern, 6 ) );
	}
	if ( '' === $pattern ) {
		return '';
	}
	$p       = \WP_Block_Patterns_Registry::get_instance()->get_registered( $pattern );
	$content = \is_array( $p ) ? (string) $p['content'] : '';
	if ( '' !== $type ) {
		$content = \str_replace( '"variante":"page"', '"variante":"page","type":"' . $type . '"', $content );
	}
	return $content;
}

/**
 * Crée les pages gérées absentes, sans toucher aux pages existantes ni aux
 * réglages du site. Appelé à chaque nouvelle version du thème.
 *
 * @return string[] Chemins créés.
 */
function install_missing_pages(): array {
	$crees = array();
	foreach ( managed_pages() as [ $slug, $title, $parent, $pattern, $type ] ) {
		$path = '' !== $parent ? $parent . '/' . $slug : $slug;
		if ( \get_page_by_path( $path ) instanceof \WP_Post ) {
			continue;
		}
		$pp = '' !== $parent ? \get_page_by_path( $parent ) : null;
		if ( '' !== $parent && ! $pp instanceof \WP_Post ) {
			continue;
		}
		$id = (int) \wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_parent'  => $pp instanceof \WP_Post ? $pp->ID : 0,
				'post_content' => \wp_slash( managed_page_content( $pattern, $type ) ),
			)
		);
		if ( $id > 0 ) {
			if ( '' !== $pattern ) {
				\update_post_meta( $id, '_wp_page_template', 'page-libre' );
			}
			$crees[] = '/' . $path . '/';
		}
	}
	return $crees;
}

/**
 * Remplace, une seule fois, le contenu importé d'une page par sa nouvelle
 * composition. L'ancien contenu reste dans les révisions de la page.
 *
 * @param string $path    Chemin de la page.
 * @param string $pattern Composition.
 * @param string $marque  Classe présente dans la nouvelle composition.
 * @return bool
 */
function refondre_page( string $path, string $pattern, string $marque ): bool {
	$page = \get_page_by_path( $path );
	if ( ! $page instanceof \WP_Post || \str_contains( $page->post_content, $marque ) ) {
		return false;
	}
	$contenu = managed_page_content( $pattern );
	if ( '' === $contenu ) {
		return false;
	}
	\wp_save_post_revision( $page->ID );
	\wp_update_post(
		array(
			'ID'           => $page->ID,
			'post_content' => \wp_slash( $contenu ),
		)
	);
	\update_post_meta( $page->ID, '_wp_page_template', 'page-libre' );
	return true;
}

/**
 * Crée ou complète les pages gérées.
 *
 * @param bool $force Remplacer le contenu existant par les compositions.
 * @return string[] Journal.
 */
function install_pages( bool $force = false ): array {
	$log      = array();
	$registry = \WP_Block_Patterns_Registry::get_instance();
	$ids      = array();
	foreach ( managed_pages() as [ $slug, $title, $parent, $pattern, $type ] ) {
		$path     = '' !== $parent ? $parent . '/' . $slug : $slug;
		$existing = \get_page_by_path( $path );
		$content  = '';
		if ( 'outils' === $pattern ) {
			$content = outils_page_content();
		} elseif ( \str_starts_with( $pattern, 'outil:' ) ) {
			$content = outil_page_content( \substr( $pattern, 6 ) );
		} elseif ( '' !== $pattern ) {
			$p       = $registry->get_registered( $pattern );
			$content = \is_array( $p ) ? (string) $p['content'] : '';
			if ( '' !== $type ) {
				$content = \str_replace( '"variante":"page"', '"variante":"page","type":"' . $type . '"', $content );
			}
		}
		$postarr = array(
			'post_type'   => 'page',
			'post_status' => 'publish',
			'post_title'  => $title,
			'post_name'   => $slug,
		);
		if ( '' !== $parent ) {
			$pp = \get_page_by_path( $parent );
			if ( $pp instanceof \WP_Post ) {
				$postarr['post_parent'] = $pp->ID;
			}
		}
		if ( $existing instanceof \WP_Post ) {
			$id = $existing->ID;
			if ( $force && '' !== $content ) {
				\wp_update_post(
					array(
						'ID'           => $id,
						'post_content' => \wp_slash( $content ),
					)
				);
				$log[] = "/$path/ : contenu remplacé.";
			}
		} else {
			$postarr['post_content'] = \wp_slash( $content );
			$id                      = (int) \wp_insert_post( $postarr );
			$log[]                   = "/$path/ : page créée.";
		}
		if ( '' !== $pattern ) {
			\update_post_meta( $id, '_wp_page_template', 'page-libre' );
		}
		$ids[ $slug ] = $id;
	}
	\update_option( 'show_on_front', 'page' );
	\update_option( 'page_on_front', $ids['accueil'] );
	\update_option( 'page_for_posts', $ids['articles'] );
	if ( '/%postname%/' !== \get_option( 'permalink_structure' ) ) {
		\update_option( 'permalink_structure', '/%postname%/' );
		$log[] = 'Permaliens réglés sur /%postname%/ (URL identiques à rescue18.com).';
	}
	\flush_rewrite_rules( false );
	$log[] = 'Accueil et page des articles réglés.';
	return $log;
}
