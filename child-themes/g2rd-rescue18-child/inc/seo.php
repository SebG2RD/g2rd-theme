<?php
/**
 * Référencement de base, actif tant qu'aucune extension SEO n'est installée.
 *
 * Sans SEOPress (ou Yoast, Rank Math, AIOSEO), le thème fournit :
 * description, Open Graph, carte Twitter et données structurées
 * (WebSite, Organization, BreadcrumbList, Article). Dès qu'une extension
 * SEO est active, ce module se retire et lui laisse la main.
 *
 * Les pages d'outils gardent leurs données propres (WebApplication et
 * FAQPage) dans tous les cas : aucune extension ne les produit.
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18;

\defined( 'ABSPATH' ) || exit;

/**
 * Une extension SEO gère-t-elle déjà les balises ?
 *
 * @return bool
 */
function seo_plugin_active(): bool {
	return \defined( 'SEOPRESS_VERSION' ) || \defined( 'WPSEO_VERSION' ) || \defined( 'RANK_MATH_VERSION' ) || \defined( 'AIOSEO_VERSION' );
}

/**
 * Coupe un texte proprement pour une description (155 caractères environ).
 *
 * @param string $text Texte.
 * @return string
 */
function seo_trim( string $text ): string {
	$text = \trim( (string) \preg_replace( '/\s+/u', ' ', \wp_strip_all_tags( \strip_shortcodes( $text ) ) ) );
	if ( \mb_strlen( $text ) <= 158 ) {
		return $text;
	}
	$cut = \mb_substr( $text, 0, 155 );
	$cut = \mb_substr( $cut, 0, (int) \mb_strrpos( $cut, ' ' ) );
	return \rtrim( $cut, ' ,;:.' ) . '…';
}

/**
 * Description de la page courante.
 *
 * @return string
 */
function seo_description(): string {
	$tool = current_outil();
	if ( '' !== $tool ) {
		return outils()[ $tool ]['description'];
	}
	if ( \is_front_page() ) {
		return 'Rescue 18, le webmédia des sapeurs-pompiers : actualités, bibliothèque GDO, GTO et référentiels, veille juridique, outils opérationnels, podcast et formation.';
	}
	if ( \is_singular() ) {
		$post = \get_queried_object();
		if ( $post instanceof \WP_Post ) {
			if ( 'outils' === $post->post_name ) {
				return 'Outils gratuits pour sapeurs-pompiers : calcul du RIS d\'un DPS, autonomie ARI, noria, messages opérationnels. Méthode et sources officielles pour chaque calcul.';
			}
			if ( '' !== $post->post_excerpt ) {
				return seo_trim( $post->post_excerpt );
			}
			// Sans rendu des blocs dynamiques : seuls les paragraphes et titres comptent.
			// Le surtitre (« Pompiers de France », « Lexique ») ne doit pas ouvrir la description.
			$text = \preg_replace( array( '#<h1[^>]*>.*?</h1>#is', '#<p[^>]*class="[^"]*r18-eyebrow[^"]*"[^>]*>.*?</p>#is' ), '', \excerpt_remove_blocks( $post->post_content ) );
			$text = seo_trim( (string) $text );
			return '' !== $text ? $text : (string) \get_bloginfo( 'description' );
		}
	}
	if ( \is_category() || \is_tag() || \is_tax() ) {
		$term = \get_queried_object();
		if ( $term instanceof \WP_Term ) {
			return '' !== $term->description ? seo_trim( $term->description ) : seo_trim( $term->name . ' : articles, fiches et documents pour les sapeurs-pompiers sur Rescue 18.' );
		}
	}
	if ( \is_home() ) {
		return 'Actualités des sapeurs-pompiers : opérations, doctrine, matériel, formation et vie des centres de secours.';
	}
	return (string) \get_bloginfo( 'description' );
}

/**
 * Image de partage : image à la une, sinon visuel du thème.
 *
 * @return array{0:string,1:int,2:int}
 */
function seo_image(): array {
	if ( \is_singular() && \has_post_thumbnail() ) {
		$img = \wp_get_attachment_image_src( (int) \get_post_thumbnail_id(), 'large' );
		if ( \is_array( $img ) ) {
			return array( (string) $img[0], (int) $img[1], (int) $img[2] );
		}
	}
	$a = asset( 'assets/img/casque-rescue18.webp' );
	return array( null !== $a ? $a[0] : '', 0, 0 );
}

/**
 * URL canonique de la page courante.
 *
 * @return string
 */
function seo_url(): string {
	if ( \is_singular() ) {
		return (string) \wp_get_canonical_url();
	}
	if ( \is_front_page() ) {
		return \home_url( '/' );
	}
	$obj = \get_queried_object();
	if ( $obj instanceof \WP_Term ) {
		return (string) \get_term_link( $obj );
	}
	if ( \is_home() ) {
		return (string) \get_permalink( (int) \get_option( 'page_for_posts' ) );
	}
	return \home_url( (string) \wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '/' ), PHP_URL_PATH ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- seul le chemin est repris, puis échappé à l'affichage.
}

/**
 * Titre des pages d'outils (balise title).
 */
\add_filter(
	'pre_get_document_title',
	static function ( $title ) {
		$tool = current_outil();
		if ( '' === $tool || seo_plugin_active() ) {
			return $title;
		}
		return outils()[ $tool ]['seo_title'] . ' | Rescue 18';
	},
	20
);

/**
 * Séparateur de titre lisible.
 */
\add_filter( 'document_title_separator', static fn(): string => '|' );

/**
 * Balises meta et Open Graph.
 */
\add_action(
	'wp_head',
	static function (): void {
		if ( seo_plugin_active() || \is_404() || \is_search() ) {
			return;
		}
		$desc  = seo_description();
		$url   = seo_url();
		$title = \wp_get_document_title();
		[ $img, $w, $h ] = seo_image();
		$type  = \is_singular( 'post' ) ? 'article' : 'website';

		$tags = array(
			array( 'name', 'description', $desc ),
			array( 'property', 'og:locale', 'fr_FR' ),
			array( 'property', 'og:site_name', 'Rescue 18' ),
			array( 'property', 'og:type', $type ),
			array( 'property', 'og:title', $title ),
			array( 'property', 'og:description', $desc ),
			array( 'property', 'og:url', $url ),
			array( 'property', 'og:image', $img ),
			array( 'property', 'og:image:width', $w ? (string) $w : '' ),
			array( 'property', 'og:image:height', $h ? (string) $h : '' ),
			array( 'name', 'twitter:card', 'summary_large_image' ),
		);
		if ( 'article' === $type ) {
			$tags[] = array( 'property', 'article:published_time', (string) \get_the_date( 'c' ) );
			$tags[] = array( 'property', 'article:modified_time', (string) \get_the_modified_date( 'c' ) );
		}
		echo "\n<!-- Rescue 18 : référencement de base (remplacé par SEOPress dès son activation) -->\n";
		foreach ( $tags as [ $attr, $key, $value ] ) {
			if ( '' !== $value ) {
				\printf( '<meta %s="%s" content="%s">' . "\n", $attr, \esc_attr( $key ), \esc_attr( $value ) );
			}
		}
	},
	3
);

/**
 * Données structurées JSON-LD.
 */
\add_action(
	'wp_head',
	static function (): void {
		if ( \is_404() || \is_search() ) {
			return;
		}
		$graph = array();
		$home  = \home_url( '/' );
		$org   = array(
			'@type' => 'Organization',
			'@id'   => $home . '#organisation',
			'name'  => 'Rescue 18',
			'url'   => $home,
		);
		$logo = \get_theme_mod( 'custom_logo' ) ? \wp_get_attachment_image_url( (int) \get_theme_mod( 'custom_logo' ), 'full' ) : '';
		if ( $logo ) {
			$org['logo'] = $logo;
		}

		if ( ! seo_plugin_active() ) {
			if ( \is_front_page() ) {
				$graph[] = $org;
				$graph[] = array(
					'@type'           => 'WebSite',
					'@id'             => $home . '#site',
					'name'            => 'Rescue 18',
					'url'             => $home,
					'inLanguage'      => 'fr-FR',
					'publisher'       => array( '@id' => $home . '#organisation' ),
					'potentialAction' => array(
						'@type'       => 'SearchAction',
						'target'      => $home . '?s={search_term_string}',
						'query-input' => 'required name=search_term_string',
					),
				);
			} else {
				$items = array();
				foreach ( breadcrumb_items() as $i => [ $u, $label ] ) {
					$items[] = array_filter(
						array(
							'@type'    => 'ListItem',
							'position' => $i + 1,
							'name'     => $label,
							'item'     => '' !== $u ? $u : seo_url(),
						)
					);
				}
				if ( \count( $items ) > 1 ) {
					$graph[] = array(
						'@type'           => 'BreadcrumbList',
						'itemListElement' => $items,
					);
				}
			}
			if ( \is_singular( 'post' ) ) {
				[ $img ] = seo_image();
				$graph[] = array(
					'@type'            => 'Article',
					'headline'         => \get_the_title(),
					'description'      => seo_description(),
					'datePublished'    => \get_the_date( 'c' ),
					'dateModified'     => \get_the_modified_date( 'c' ),
					'author'           => array(
						'@type' => 'Person',
						'name'  => \get_the_author_meta( 'display_name', (int) \get_post_field( 'post_author' ) ),
					),
					'image'            => $img,
					'mainEntityOfPage' => seo_url(),
					'inLanguage'       => 'fr-FR',
					'publisher'        => $org,
				);
			}
		}

		$tool = current_outil();
		if ( '' !== $tool ) {
			$o       = outils()[ $tool ];
			$graph[] = array(
				'@type'               => 'WebApplication',
				'name'                => $o['title'],
				'description'         => $o['description'],
				'url'                 => seo_url(),
				'applicationCategory' => 'UtilitiesApplication',
				'operatingSystem'     => 'Tous (navigateur web)',
				'inLanguage'          => 'fr-FR',
				'isAccessibleForFree' => true,
				'offers'              => array(
					'@type'         => 'Offer',
					'price'         => '0',
					'priceCurrency' => 'EUR',
				),
				'audience'            => array(
					'@type'        => 'Audience',
					'audienceType' => 'Sapeurs-pompiers, secouristes, organisateurs d\'événements',
				),
				'publisher'           => $org,
			);
			$faq = array();
			foreach ( $o['faq'] as [ $q, $a ] ) {
				$faq[] = array(
					'@type'          => 'Question',
					'name'           => $q,
					'acceptedAnswer' => array(
						'@type' => 'Answer',
						'text'  => $a,
					),
				);
			}
			if ( $faq ) {
				$graph[] = array(
					'@type'      => 'FAQPage',
					'mainEntity' => $faq,
				);
			}
		} elseif ( \is_page( 'outils' ) ) {
			$list = array();
			$i    = 0;
			foreach ( outils() as $slug => $o ) {
				$list[] = array(
					'@type'    => 'ListItem',
					'position' => ++$i,
					'url'      => outil_url( $slug ),
					'name'     => $o['title'],
				);
			}
			$graph[] = array(
				'@type'      => 'CollectionPage',
				'name'       => 'Boîte à outils du sapeur-pompier',
				'url'        => seo_url(),
				'mainEntity' => array(
					'@type'           => 'ItemList',
					'itemListElement' => $list,
				),
			);
		}

		if ( $graph ) {
			echo '<script type="application/ld+json">' . \wp_json_encode(
				array(
					'@context' => 'https://schema.org',
					'@graph'   => $graph,
				),
				JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG
			) . "</script>\n";
		}
	},
	4
);

/**
 * Plan du site natif : on retire les pages d'utilisateurs (comptes auteurs
 * importés) tant qu'aucune extension SEO ne gère le plan du site.
 */
\add_filter(
	'wp_sitemaps_add_provider',
	static function ( $provider, string $name ) {
		return 'users' === $name ? false : $provider;
	},
	10,
	2
);

/**
 * Anciennes adresses « __trashed » encore classées dans Google (ex. fiche ARI) :
 * redirection 301 vers l'article publié au même nom, s'il existe.
 */
\add_action(
	'template_redirect',
	static function (): void {
		if ( ! \is_404() ) {
			return;
		}
		$path = (string) \wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( ! \preg_match( '#^/([a-z0-9-]+)__trashed/?$#', $path, $m ) ) {
			return;
		}
		$id = (int) \url_to_postid( \home_url( '/' . $m[1] . '/' ) );
		if ( $id > 0 && 'publish' === \get_post_status( $id ) ) {
			\wp_safe_redirect( (string) \get_permalink( $id ), 301 );
			exit;
		}
	}
);
