<?php
/**
 * Pont SEO : ce que le thème fait et ne fait pas.
 *
 * SEOPress (et SEOPress PRO) gère titles, meta descriptions, canoniques,
 * sitemaps, Open Graph, fil d'Ariane et données structurées (Local Business,
 * FAQ, Service). Le thème n'émet donc AUCUN JSON-LD : il fournit seulement
 * les métadonnées initiales à l'installation (inc/setup.php écrit
 * `_seopress_titles_title` et `_seopress_titles_desc`) et les informations du
 * centre à recopier dans SEOPress PRO › Local Business.
 *
 * Deux garde-fous vivent ici :
 *  - le bloc g2rd/breadcrumb du parent émet son propre JSON-LD BreadcrumbList ;
 *    si SEOPress PRO a déjà activé les fils d'Ariane structurés, on retire
 *    celui du bloc pour éviter le doublon ;
 *  - le bloc g2rd/faq en mode GEO émet un JSON-LD FAQPage : même règle.
 *
 * @package G2RD_GlassAuto
 */

declare( strict_types = 1 );

namespace G2RD_GlassAuto;

\defined( 'ABSPATH' ) || exit;

/**
 * SEOPress PRO émet-il des données structurées de fil d'Ariane ?
 *
 * @return bool
 */
function seopress_breadcrumbs_schema(): bool {
	// SEOPress PRO n'accroche cette action que lorsqu'il va réellement émettre le
	// JSON-LD (module fil d'Ariane actif et option « JSON-LD » cochée, clé
	// seopress_breadcrumbs_json_enable). Tester l'action plutôt que l'option
	// évite de dépendre d'un nom de clé ou d'un état de licence.
	return false !== \has_action( 'wp_head', 'seopress_jsonld_breadcrumbs' );
}

/**
 * Retire un script JSON-LD d'un rendu de bloc.
 *
 * @param string $html Rendu.
 * @return string
 */
function retirer_jsonld( string $html ): string {
	return (string) \preg_replace( '#<script[^>]+application/ld\+json[^>]*>.*?</script>#is', '', $html );
}

/**
 * JSON-LD BreadcrumbList construit depuis le HTML du bloc g2rd/breadcrumb.
 *
 * Le bloc du parent n'émet pas son JSON-LD dès qu'un plugin SEO est actif
 * (il suppose que le plugin s'en charge). Avec SEOPress gratuit, personne ne
 * l'émet ; avec SEOPress PRO, seulement si « JSON-LD fil d'Ariane » est coché.
 * Ce repli lit les éléments rendus (liens + élément courant) et produit la
 * liste, pour qu'il y ait toujours un BreadcrumbList, et un seul.
 *
 * @param string $html Rendu du bloc.
 * @return string Script JSON-LD, ou chaîne vide si le HTML n'est pas lisible.
 */
function breadcrumb_jsonld( string $html ): string {
	if ( ! \preg_match_all( '#<a href="([^"]*)" class="g2rd-breadcrumb__link">(.*?)</a>|<span class="g2rd-breadcrumb__current"[^>]*>(.*?)</span>#s', $html, $m, PREG_SET_ORDER ) ) {
		return '';
	}
	$items = array();
	foreach ( $m as $i => $hit ) {
		$current = isset( $hit[3] ) && '' !== $hit[3];
		$url     = $current ? \home_url( \add_query_arg( array() ) ) : \html_entity_decode( $hit[1], ENT_QUOTES, 'UTF-8' );
		$name    = \html_entity_decode( \wp_strip_all_tags( $current ? $hit[3] : $hit[2] ), ENT_QUOTES, 'UTF-8' );
		$item    = array(
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'name'     => \trim( $name ),
		);
		if ( '' !== $url ) {
			$item['item'] = $url;
		}
		$items[] = $item;
	}
	if ( empty( $items ) ) {
		return '';
	}
	$data = array(
		'@context'        => 'https://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $items,
	);
	return '<script type="application/ld+json">' . \wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>';
}

\add_filter(
	'render_block_g2rd/breadcrumb',
	static function ( $html ) {
		$html = (string) $html;
		if ( seopress_breadcrumbs_schema() ) {
			return retirer_jsonld( $html ); // SEOPress PRO l'émet dans le <head>.
		}
		if ( \defined( 'SEOPRESS_VERSION' ) && false === \strpos( $html, 'BreadcrumbList' ) ) {
			return breadcrumb_jsonld( $html ) . $html; // Ni le bloc ni SEOPress : repli.
		}
		return $html;
	},
	20
);

/**
 * Complète un tableau d'options sans écraser ce qui est déjà saisi.
 *
 * Une clé est posée seulement si elle est absente ou vide ; les tableaux
 * imbriqués (titres par type de contenu, horaires) sont parcourus feuille
 * par feuille selon la même règle.
 *
 * @param array<string,mixed>  $base   Options existantes.
 * @param array<string,mixed>  $values Valeurs proposées.
 * @param array<string,string> $report  Rapport clé → « posé » ou « conservé » (par référence).
 * @param string               $prefix  Préfixe du rapport.
 * @return array<string,mixed>
 */
function fill_empty( array $base, array $values, array &$report, string $prefix = '' ): array {
	foreach ( $values as $key => $value ) {
		$label = '' !== $prefix ? $prefix . '.' . $key : (string) $key;
		if ( \is_array( $value ) ) {
			$base[ $key ] = fill_empty( \is_array( $base[ $key ] ?? null ) ? $base[ $key ] : array(), $value, $report, $label );
			continue;
		}
		if ( isset( $base[ $key ] ) && '' !== $base[ $key ] ) {
			$report[ $label ] = 'conservé';
			continue;
		}
		$base[ $key ]     = $value;
		$report[ $label ] = 'posé';
	}
	return $base;
}

/**
 * Horaires du centre au format de SEOPress PRO › Local Business.
 *
 * Chaque jour est saisi en texte dans Réglages › Général (« 8 h 30 – 12 h ·
 * 13 h 30 – 18 h ») : on en extrait les heures, deux plages au plus (matin,
 * après-midi). Sans heure lisible, le jour est marqué fermé.
 *
 * @return array<int,array<string,mixed>>
 */
function seopress_opening_hours(): array {
	$jours = array( 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche' );
	$out   = array();
	$c     = centre();
	foreach ( $jours as $i => $jour ) {
		$texte = (string) ( $c['horaires'][ $jour ] ?? '' );
		\preg_match_all( '/(\d{1,2})\s*h\s*(\d{2})?/u', $texte, $m, PREG_SET_ORDER );
		$heures = \array_map(
			static fn( array $h ): array => array(
				'hours' => \str_pad( $h[1], 2, '0', STR_PAD_LEFT ),
				'mins'  => $h[2] ?? '00',
			),
			$m
		);
		if ( \count( $heures ) < 2 ) {
			$out[ $i ] = array( 'open' => '1' ); // Fermé toute la journée.
			continue;
		}
		$out[ $i ] = array(
			'am' => array(
				'open'  => '1',
				'start' => $heures[0],
				'end'   => $heures[1],
			),
		);
		if ( \count( $heures ) >= 4 ) {
			$out[ $i ]['pm'] = array(
				'open'  => '1',
				'start' => $heures[2],
				'end'   => $heures[3],
			);
		}
	}
	return $out;
}

/**
 * Préremplit SEOPress et SEOPress PRO avec les informations du centre.
 *
 * Rien n'est écrasé : une option déjà renseignée dans SEOPress est conservée
 * (voir fill_empty). Les clés d'options sont celles de SEOPress 9.x
 * (inc/admin/settings/*.php du plugin) ; si une version future les renomme,
 * les valeurs posées ici sont simplement ignorées par SEOPress.
 *
 * @return array<string,string> clé → « posé », « conservé » ou message.
 */
function seopress_prefill(): array {
	if ( ! \defined( 'SEOPRESS_VERSION' ) ) {
		return array( 'seopress' => 'extension inactive : rien à faire' );
	}
	$report = array();
	$c      = centre();

	// Modules SEOPress à activer.
	$toggle = \get_option( 'seopress_toggle', array() );
	$toggle = fill_empty(
		\is_array( $toggle ) ? $toggle : array(),
		array(
			'toggle-titles'         => '1',
			'toggle-xml-sitemap'    => '1',
			'toggle-social'         => '1',
			'toggle-robots'         => '1',
			'toggle-breadcrumbs'    => '1',
			'toggle-local-business' => '1',
			'toggle-rich-snippets'  => '1',
		),
		$report,
		'modules'
	);
	\update_option( 'seopress_toggle', $toggle );

	// Titres et métas : modèles par défaut, archives inutiles hors index.
	$titles = \get_option( 'seopress_titles_option_name', array() );
	$titles = fill_empty(
		\is_array( $titles ) ? $titles : array(),
		array(
			'seopress_titles_sep'                     => '|',
			'seopress_titles_home_site_title'         => '%%sitetitle%% %%sep%% %%tagline%%',
			'seopress_titles_home_site_desc'          => 'Centre GlassAuto de Vernon (Eure) : réparation d’impact, remplacement de pare-brise, vitres, lunette arrière, rétroviseurs et optiques, calibrage ADAS. Prise en charge assurance.',
			'seopress_titles_single_titles'           => array(
				'post' => array(
					'title'       => '%%post_title%% %%sep%% %%sitetitle%%',
					'description' => '%%post_excerpt%%',
				),
				'page' => array(
					'title'       => '%%post_title%% %%sep%% %%sitetitle%%',
					'description' => '%%post_excerpt%%',
				),
			),
			'seopress_titles_tax_titles'              => array(
				'category' => array(
					'title'       => '%%_category_title%% %%sep%% %%sitetitle%%',
					'description' => '%%_category_description%%',
				),
				'post_tag' => array( 'noindex' => '1' ),
			),
			'seopress_titles_archives_author_noindex' => '1',
			'seopress_titles_archives_date_noindex'   => '1',
			'seopress_titles_archives_search_title_noindex' => '1',
			'seopress_titles_attachments_noindex'     => '1',
			'seopress_titles_paged_rel'               => '1',
		),
		$report,
		'titres'
	);
	\update_option( 'seopress_titles_option_name', $titles );

	// Sitemaps XML : pages, articles, catégories, images.
	$sitemap = \get_option( 'seopress_xml_sitemap_option_name', array() );
	$sitemap = fill_empty(
		\is_array( $sitemap ) ? $sitemap : array(),
		array(
			'seopress_xml_sitemap_general_enable'  => '1',
			'seopress_xml_sitemap_img_enable'      => '1',
			'seopress_xml_sitemap_post_types_list' => array(
				'post' => array( 'include' => '1' ),
				'page' => array( 'include' => '1' ),
			),
			'seopress_xml_sitemap_taxonomies_list' => array(
				'category' => array( 'include' => '1' ),
			),
		),
		$report,
		'sitemaps'
	);
	\update_option( 'seopress_xml_sitemap_option_name', $sitemap );

	// Réseaux sociaux et Knowledge Graph (Organization).
	$social = \get_option( 'seopress_social_option_name', array() );
	$social = fill_empty(
		\is_array( $social ) ? $social : array(),
		array(
			'seopress_social_knowledge_type'         => 'Organization',
			'seopress_social_knowledge_name'         => (string) $c['nom'],
			'seopress_social_knowledge_phone'        => (string) $c['tel'],
			'seopress_social_knowledge_contact_type' => 'customer support',
			'seopress_social_facebook_og'            => '1',
			'seopress_social_twitter_card'           => '1',
		),
		$report,
		'social'
	);
	\update_option( 'seopress_social_option_name', $social );

	// SEOPress PRO : Local Business et fil d'Ariane.
	if ( ! \defined( 'SEOPRESS_PRO_VERSION' ) ) {
		$report['seopress-pro'] = 'extension inactive : Local Business à saisir plus tard (wp glassauto seo)';
		return $report;
	}
	$pro = \get_option( 'seopress_pro_option_name', array() );
	$pro = fill_empty(
		\is_array( $pro ) ? $pro : array(),
		array(
			'seopress_local_business_type'             => 'AutoRepair',
			'seopress_local_business_street_address'   => (string) $c['rue'],
			'seopress_local_business_address_locality' => (string) $c['ville'],
			'seopress_local_business_address_region'   => 'Normandie',
			'seopress_local_business_postal_code'      => (string) $c['cp'],
			'seopress_local_business_address_country'  => 'FR',
			'seopress_local_business_lat'              => (string) $c['lat'],
			'seopress_local_business_lon'              => (string) $c['lon'],
			'seopress_local_business_place_id'         => (string) $c['place_id'],
			'seopress_local_business_url'              => \home_url( '/' ),
			'seopress_local_business_phone'            => (string) $c['tel'],
			'seopress_local_business_opening_hours'    => seopress_opening_hours(),
		),
		$report,
		'pro'
	);
	\update_option( 'seopress_pro_option_name', $pro );
	return $report;
}

/**
 * Informations du centre au format attendu par SEOPress PRO › Local Business,
 * affichées dans la page d'installation (contrôle visuel après
 * `wp glassauto seo` ou le bouton « Préremplir SEOPress »).
 *
 * @return array<string,string>
 */
function local_business_resume(): array {
	$c = centre();
	return array(
		\__( 'Type', 'g2rd-glassauto-child' )        => 'AutoRepair (ou AutomotiveBusiness)',
		\__( 'Nom', 'g2rd-glassauto-child' )         => (string) $c['nom'],
		\__( 'Rue', 'g2rd-glassauto-child' )         => (string) $c['rue'],
		\__( 'Code postal', 'g2rd-glassauto-child' ) => (string) $c['cp'],
		\__( 'Ville', 'g2rd-glassauto-child' )       => (string) $c['ville'],
		\__( 'Pays', 'g2rd-glassauto-child' )        => 'FR',
		\__( 'Téléphone', 'g2rd-glassauto-child' )   => (string) $c['tel'],
		\__( 'Latitude', 'g2rd-glassauto-child' )    => (string) $c['lat'],
		\__( 'Longitude', 'g2rd-glassauto-child' )   => (string) $c['lon'],
		\__( 'Horaires', 'g2rd-glassauto-child' )    => \implode( ' ; ', \array_map( static fn( $j, $h ) => $j . ' ' . $h, \array_keys( $c['horaires'] ), $c['horaires'] ) ),
	);
}
