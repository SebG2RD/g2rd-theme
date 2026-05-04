<?php
/**
 * Render template pour le bloc Fil d'Ariane — G2RD
 *
 * Compatible avec les plugins SEO : Yoast SEO, RankMath, SEOPress, AIOSEO,
 * The SEO Framework, Breadcrumb NavXT, WooCommerce.
 *
 * Ordre de priorité pour la catégorie primaire :
 *   Yoast SEO → RankMath → SEOPress → première catégorie WordPress.
 *
 * Le JSON-LD BreadcrumbList est omis si un plugin SEO actif l'injecte
 * déjà dans le <head> (évite les données structurées en double).
 *
 * Filtre disponible : `g2rd_breadcrumb_items` permet de remplacer ou
 * enrichir le trail depuis un thème enfant ou une extension.
 *
 * @var array    $attributes Attributs du bloc.
 * @var string   $content    Contenu du bloc (vide — bloc dynamique).
 * @var WP_Block $block      Instance du bloc.
 *
 * @package G2RD
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─── Attributs du bloc ────────────────────────────────────────────────────────
$show_home  = $attributes['showHome']  ?? true;
$separator  = $attributes['separator'] ?? 'chevron';
$home_label = $attributes['homeLabel'] ?? '';
$text_color = $attributes['textColor'] ?? '';
$link_color = $attributes['linkColor'] ?? '';

$separators = [
	'chevron' => '›',
	'slash'   => '/',
	'arrow'   => '→',
	'dot'     => '•',
];
$sep = $separators[ $separator ] ?? '›';

if ( empty( $home_label ) ) {
	$home_label = __( 'Accueil', 'g2rd' );
}

// ─── Helpers (définis une seule fois par requête) ─────────────────────────────

if ( ! function_exists( 'g2rd_bc_primary_category' ) ) {
	/**
	 * Retourne le terme de catégorie principale d'un article.
	 * Respecte le réglage "catégorie primaire" de Yoast SEO, RankMath et SEOPress.
	 *
	 * @param  int $post_id
	 * @return WP_Term|null
	 */
	function g2rd_bc_primary_category( int $post_id ): ?WP_Term {
		// Yoast SEO — méta _yoast_wpseo_primary_category
		if ( defined( 'WPSEO_VERSION' ) ) {
			$id = (int) get_post_meta( $post_id, '_yoast_wpseo_primary_category', true );
			if ( $id ) {
				$t = get_term( $id, 'category' );
				if ( $t && ! is_wp_error( $t ) ) {
					return $t;
				}
			}
		}

		// RankMath — méta rank_math_primary_category
		if ( defined( 'RANK_MATH_VERSION' ) ) {
			$id = (int) get_post_meta( $post_id, 'rank_math_primary_category', true );
			if ( $id ) {
				$t = get_term( $id, 'category' );
				if ( $t && ! is_wp_error( $t ) ) {
					return $t;
				}
			}
		}

		// SEOPress — méta _seopress_robots_primary_cat
		if ( defined( 'SEOPRESS_VERSION' ) ) {
			$id = (int) get_post_meta( $post_id, '_seopress_robots_primary_cat', true );
			if ( $id ) {
				$t = get_term( $id, 'category' );
				if ( $t && ! is_wp_error( $t ) ) {
					return $t;
				}
			}
		}

		// AIOSEO — méta _aioseo_primary_category (AIOSEO Pro)
		if ( defined( 'AIOSEO_VERSION' ) ) {
			$id = (int) get_post_meta( $post_id, '_aioseo_primary_category', true );
			if ( $id ) {
				$t = get_term( $id, 'category' );
				if ( $t && ! is_wp_error( $t ) ) {
					return $t;
				}
			}
		}

		// Fallback — première catégorie WordPress
		$cats = get_the_category( $post_id );
		return ! empty( $cats ) ? $cats[0] : null;
	}
}

if ( ! function_exists( 'g2rd_bc_term_ancestors' ) ) {
	/**
	 * Retourne les termes ancêtres d'un terme donné sous forme d'items breadcrumb.
	 *
	 * @param  WP_Term $term
	 * @return array<int, array{url: string, name: string, type: string}>
	 */
	function g2rd_bc_term_ancestors( WP_Term $term ): array {
		$ancestors = [];
		$parent_id = $term->parent;

		while ( $parent_id ) {
			$parent = get_term( $parent_id, $term->taxonomy );
			if ( ! $parent || is_wp_error( $parent ) ) {
				break;
			}
			array_unshift( $ancestors, [
				'url'  => (string) get_term_link( $parent ),
				'name' => $parent->name,
				'type' => 'link',
			] );
			$parent_id = $parent->parent;
		}

		return $ancestors;
	}
}

if ( ! function_exists( 'g2rd_bc_cpt_archive' ) ) {
	/**
	 * Retourne l'URL et le titre de la page d'archive d'un post type, ou null
	 * si le post type n'a pas d'archive publique.
	 *
	 * Tente de lire le titre personnalisé depuis Yoast SEO ou RankMath, sinon
	 * utilise le label `archives` ou `name` du post type.
	 *
	 * @param  string $post_type
	 * @return array{url: string, name: string}|null
	 */
	function g2rd_bc_cpt_archive( string $post_type ): ?array {
		$pt = get_post_type_object( $post_type );
		if ( ! $pt || ! $pt->has_archive ) {
			return null;
		}

		$archive_url = get_post_type_archive_link( $post_type );
		if ( ! $archive_url ) {
			return null;
		}

		// Yoast SEO — titre d'archive personnalisé (API YoastSEO() stable depuis v14)
		if ( defined( 'WPSEO_VERSION' ) && function_exists( 'YoastSEO' ) ) {
			try {
				$meta  = YoastSEO()->meta->for_post_type_archive( $post_type );
				$title = wp_strip_all_tags( $meta->title ?? '' );
				if ( ! empty( $title ) ) {
					return [ 'url' => $archive_url, 'name' => $title ];
				}
			} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			}
		}

		// RankMath — titre d'archive
		if ( defined( 'RANK_MATH_VERSION' ) && class_exists( '\RankMath\Helper' ) ) {
			$title = wp_strip_all_tags(
				(string) \RankMath\Helper::get_settings( "titles.pt_{$post_type}_title" )
			);
			if ( ! empty( $title ) ) {
				return [ 'url' => $archive_url, 'name' => $title ];
			}
		}

		// SEOPress — titre d'archive (option globale)
		if ( defined( 'SEOPRESS_VERSION' ) ) {
			$options = get_option( 'seopress_titles_option_name', [] );
			$title   = wp_strip_all_tags(
				(string) ( $options[ 'seopress_titles_cpt_' . $post_type . '_title' ] ?? '' )
			);
			if ( ! empty( $title ) ) {
				return [ 'url' => $archive_url, 'name' => $title ];
			}
		}

		// Fallback — label WordPress
		$name = $pt->labels->archives ?: $pt->labels->name;
		return [ 'url' => $archive_url, 'name' => $name ];
	}
}

if ( ! function_exists( 'g2rd_bc_is_seo_plugin_active' ) ) {
	/**
	 * Indique si un plugin SEO connu est actif.
	 * Ces plugins injectent leur propre JSON-LD BreadcrumbList dans le <head>.
	 *
	 * @return bool
	 */
	function g2rd_bc_is_seo_plugin_active(): bool {
		return defined( 'WPSEO_VERSION' )    // Yoast SEO
			|| defined( 'RANK_MATH_VERSION' ) // RankMath
			|| defined( 'SEOPRESS_VERSION' )  // SEOPress
			|| defined( 'AIOSEO_VERSION' )    // All in One SEO
			|| function_exists( 'tsf' );      // The SEO Framework
	}
}

// ─── Construction du trail ────────────────────────────────────────────────────
$items = [];

if ( $show_home ) {
	$items[] = [
		'url'  => home_url( '/' ),
		'name' => $home_label,
		'type' => 'link',
	];
}

if ( is_front_page() ) {
	// Page d'accueil : le lien "Accueil" est déjà l'item courant, rien à ajouter.

} elseif ( is_home() && ! is_front_page() ) {
	// Blog page
	$blog_page_id = (int) get_option( 'page_for_posts' );
	$items[] = [
		'url'  => $blog_page_id ? (string) get_permalink( $blog_page_id ) : '',
		'name' => $blog_page_id ? get_the_title( $blog_page_id ) : __( 'Blog', 'g2rd' ),
		'type' => 'current',
	];

} elseif ( is_singular() ) {
	$post      = get_queried_object();
	$post_type = $post instanceof WP_Post ? $post->post_type : '';

	if ( $post instanceof WP_Post ) {

		// — WooCommerce : page produit —
		if ( 'product' === $post_type && function_exists( 'WC' ) ) {
			$terms = get_the_terms( $post->ID, 'product_cat' );
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				usort( $terms, fn( $a, $b ) => $b->count <=> $a->count );
				$cat = $terms[0];
				foreach ( g2rd_bc_term_ancestors( $cat ) as $anc ) {
					$items[] = $anc;
				}
				$items[] = [
					'url'  => (string) get_term_link( $cat ),
					'name' => $cat->name,
					'type' => 'link',
				];
			}

		// — Pages hiérarchiques —
		} elseif ( 'page' === $post_type && $post->post_parent ) {
			$ancestors = array_reverse( get_post_ancestors( $post ) );
			foreach ( $ancestors as $ancestor_id ) {
				$ancestor = get_post( $ancestor_id );
				if ( $ancestor ) {
					$items[] = [
						'url'  => (string) get_permalink( $ancestor ),
						'name' => get_the_title( $ancestor ),
						'type' => 'link',
					];
				}
			}

		// — Articles standard : catégorie primaire avec hiérarchie —
		} elseif ( 'post' === $post_type ) {
			$cat = g2rd_bc_primary_category( $post->ID );
			if ( $cat ) {
				foreach ( g2rd_bc_term_ancestors( $cat ) as $anc ) {
					$items[] = $anc;
				}
				$items[] = [
					'url'  => (string) get_category_link( $cat->term_id ),
					'name' => $cat->name,
					'type' => 'link',
				];
			}

		// — CPT personnalisé : archive + taxonomie principale —
		} elseif ( 'page' !== $post_type && 'post' !== $post_type && 'product' !== $post_type ) {
			// Archive du CPT
			$cpt_archive = g2rd_bc_cpt_archive( $post_type );
			if ( $cpt_archive ) {
				$items[] = [
					'url'  => $cpt_archive['url'],
					'name' => $cpt_archive['name'],
					'type' => 'link',
				];
			}

			// Taxonomie primaire du CPT (premier terme public)
			$taxonomies = get_object_taxonomies( $post_type, 'objects' );
			foreach ( $taxonomies as $tax_obj ) {
				if ( ! $tax_obj->public ) {
					continue;
				}
				$terms = wp_get_post_terms( $post->ID, $tax_obj->name, [
					'orderby' => 'count',
					'order'   => 'DESC',
				] );
				if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
					$primary_term = $terms[0];
					foreach ( g2rd_bc_term_ancestors( $primary_term ) as $anc ) {
						$items[] = $anc;
					}
					$items[] = [
						'url'  => (string) get_term_link( $primary_term ),
						'name' => $primary_term->name,
						'type' => 'link',
					];
					break;
				}
			}
		}

		// Page courante
		$items[] = [
			'url'  => (string) get_permalink( $post ),
			'name' => get_the_title( $post ),
			'type' => 'current',
		];
	}

} elseif ( is_category() ) {
	$cat = get_queried_object();
	if ( $cat instanceof WP_Term ) {
		foreach ( g2rd_bc_term_ancestors( $cat ) as $anc ) {
			$items[] = $anc;
		}
		$items[] = [
			'url'  => (string) get_category_link( $cat->term_id ),
			'name' => $cat->name,
			'type' => 'current',
		];
	}

} elseif ( is_tag() ) {
	$tag = get_queried_object();
	if ( $tag instanceof WP_Term ) {
		$items[] = [
			'url'  => (string) get_tag_link( $tag->term_id ),
			'name' => $tag->name,
			'type' => 'current',
		];
	}

} elseif ( is_tax() ) {
	// Taxonomie custom : ajouter l'archive du CPT associé si disponible
	$term = get_queried_object();
	if ( $term instanceof WP_Term ) {
		$tax_obj = get_taxonomy( $term->taxonomy );
		if ( $tax_obj && ! empty( $tax_obj->object_type ) ) {
			$cpt_archive = g2rd_bc_cpt_archive( $tax_obj->object_type[0] );
			if ( $cpt_archive ) {
				$items[] = [
					'url'  => $cpt_archive['url'],
					'name' => $cpt_archive['name'],
					'type' => 'link',
				];
			}
		}
		foreach ( g2rd_bc_term_ancestors( $term ) as $anc ) {
			$items[] = $anc;
		}
		$items[] = [
			'url'  => (string) get_term_link( $term ),
			'name' => $term->name,
			'type' => 'current',
		];
	}

} elseif ( is_post_type_archive() ) {
	$pt_obj = get_queried_object();
	$title  = '';

	if ( defined( 'WPSEO_VERSION' ) && function_exists( 'YoastSEO' ) && $pt_obj ) {
		try {
			$title = wp_strip_all_tags( YoastSEO()->meta->for_post_type_archive( $pt_obj->name )->title ?? '' );
		} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		}
	}

	if ( empty( $title ) ) {
		$title = get_the_archive_title();
		$title = (string) preg_replace( '/^[^:]+:\s*/', '', $title );
	}

	$items[] = [
		'url'  => '',
		'name' => $title,
		'type' => 'current',
	];

} elseif ( is_archive() ) {
	$title = get_the_archive_title();
	$title = (string) preg_replace( '/^[^:]+:\s*/', '', $title );
	$items[] = [
		'url'  => '',
		'name' => $title,
		'type' => 'current',
	];

} elseif ( is_search() ) {
	$items[] = [
		'url'  => '',
		'name' => sprintf(
			/* translators: %s: terme recherché. */
			__( 'Résultats pour "%s"', 'g2rd' ),
			get_search_query()
		),
		'type' => 'current',
	];

} elseif ( is_404() ) {
	$items[] = [
		'url'  => '',
		'name' => __( 'Page non trouvée', 'g2rd' ),
		'type' => 'current',
	];

} else {
	$items[] = [
		'url'  => '',
		'name' => get_the_title() ?: get_bloginfo( 'name' ),
		'type' => 'current',
	];
}

/**
 * Filtre les items du fil d'Ariane avant le rendu.
 *
 * Permet aux extensions ou thèmes enfants de modifier, ajouter ou remplacer
 * complètement le trail. Chaque item doit avoir les clés : url, name, type
 * (type = 'link' ou 'current').
 *
 * @param array[] $items      Liste d'items [url, name, type].
 * @param array   $attributes Attributs du bloc Gutenberg.
 */
$items = (array) apply_filters( 'g2rd_breadcrumb_items', $items, $attributes );

if ( empty( $items ) ) {
	return '';
}

// ─── Rendu HTML ───────────────────────────────────────────────────────────────
$nav_style = '';
if ( $text_color ) {
	$nav_style .= '--wrb-breadcrumb-text:' . esc_attr( $text_color ) . ';';
}
if ( $link_color ) {
	$nav_style .= '--wrb-breadcrumb-link:' . esc_attr( $link_color ) . ';';
}

$wrapper_attrs = $nav_style ? [ 'style' => $nav_style ] : [];
$wrapper_attrs = get_block_wrapper_attributes( $wrapper_attrs );
?>
<div <?php echo $wrapper_attrs; ?>>
	<nav class="g2rd-breadcrumb" aria-label="<?php esc_attr_e( 'Fil d\'Ariane', 'g2rd' ); ?>">

		<?php
		/*
		 * JSON-LD BreadcrumbList — omis si un plugin SEO actif l'injecte déjà
		 * dans le <head> pour éviter les données structurées en double.
		 */
		if ( ! g2rd_bc_is_seo_plugin_active() ) :
			$schema_parts = [];
			$base_url     = home_url( '/' );

			foreach ( $items as $idx => $item ) {
				$item_url = $item['url'] ?: $base_url;
				if ( empty( $item_url ) && is_singular() ) {
					$item_url = (string) get_permalink();
				} elseif ( empty( $item_url ) && is_category() ) {
					$item_url = (string) get_category_link( get_queried_object_id() );
				} elseif ( empty( $item_url ) && is_tag() ) {
					$item_url = (string) get_tag_link( get_queried_object_id() );
				}

				$schema_parts[] = sprintf(
					'{"@type":"ListItem","position":%d,"name":"%s","item":"%s"}',
					$idx + 1,
					esc_js( $item['name'] ),
					esc_url( $item_url )
				);
			}
			?>
			<script type="application/ld+json">
			{
				"@context": "https://schema.org",
				"@type": "BreadcrumbList",
				"itemListElement": [<?php echo implode( ',', $schema_parts ); ?>]
			}
			</script>
		<?php endif; ?>

		<?php foreach ( $items as $idx => $item ) : ?>
			<?php if ( $idx > 0 ) : ?>
				<span class="g2rd-breadcrumb__separator" aria-hidden="true"><?php echo esc_html( $sep ); ?></span>
			<?php endif; ?>

			<?php if ( 'current' === $item['type'] ) : ?>
				<span class="g2rd-breadcrumb__current" aria-current="page"><?php echo esc_html( $item['name'] ); ?></span>
			<?php else : ?>
				<a href="<?php echo esc_url( $item['url'] ); ?>" class="g2rd-breadcrumb__link"><?php echo esc_html( $item['name'] ); ?></a>
			<?php endif; ?>
		<?php endforeach; ?>

	</nav>
</div>
