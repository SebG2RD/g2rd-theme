<?php
/**
 * Performance Images — lazy loading intelligent, dimensions explicites,
 * détection WebP et preload de l'image LCP dans wp_head.
 *
 * @package G2RD
 * @since   1.9.4
 */

namespace G2RD;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Optimise le chargement des images pour les Core Web Vitals (LCP, CLS).
 *
 * - Première image du contenu : loading="eager" fetchpriority="high"
 * - Images suivantes         : loading="lazy" decoding="async"
 * - Dimensions manquantes    : ajoutées via wp_get_attachment_metadata()
 * - WebP                     : substitution src si un fichier .webp existe
 * - Preload LCP              : <link rel="preload"> pour le premier hero/cover
 */
class PerformanceImages {

	/**
	 * Compteur statique d'images traitées dans the_content().
	 *
	 * @var int
	 */
	private static int $image_count = 0;

	/**
	 * Enregistre les hooks WordPress.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		\add_filter( 'wp_content_img_tag', [ $this, 'optimize_image_tag' ], 10, 3 );
		\add_action( 'wp_head', [ $this, 'preload_lcp_image' ], 2 );
	}

	/**
	 * Optimise chaque balise <img> dans the_content() :
	 * dimensions, loading, fetchpriority, WebP.
	 *
	 * @param  string $filtered_image Balise <img> courante.
	 * @param  string $_context       Contexte (ex. 'the_content') — non utilisé.
	 * @param  int    $attachment_id  ID de l'attachement (0 si inconnu).
	 * @return string Balise <img> optimisée.
	 */
	public function optimize_image_tag( string $filtered_image, string $_context, int $attachment_id ): string {
		if ( \is_admin() ) {
			return $filtered_image;
		}

		++self::$image_count;

		$filtered_image = $this->add_dimensions( $filtered_image, $attachment_id );
		$filtered_image = $this->set_loading_strategy( $filtered_image );
		$filtered_image = $this->maybe_swap_webp( $filtered_image );

		return $filtered_image;
	}

	/**
	 * Ajoute width et height si absents — évite le CLS.
	 *
	 * @param  string $tag           Balise <img>.
	 * @param  int    $attachment_id ID de l'attachement.
	 * @return string Balise avec dimensions si disponibles.
	 */
	private function add_dimensions( string $tag, int $attachment_id ): string {
		if ( $attachment_id <= 0 ) {
			return $tag;
		}

		// Si width ET height sont déjà présents, rien à faire.
		if ( \preg_match( '/\bwidth=["\']/', $tag ) && \preg_match( '/\bheight=["\']/', $tag ) ) {
			return $tag;
		}

		$meta = \wp_get_attachment_metadata( $attachment_id );
		if ( empty( $meta['width'] ) || empty( $meta['height'] ) ) {
			return $tag;
		}

		$w = (int) $meta['width'];
		$h = (int) $meta['height'];

		// Insère width et height juste avant la fermeture de la balise.
		return \str_replace( '>', " width=\"{$w}\" height=\"{$h}\">", $tag );
	}

	/**
	 * Applique la stratégie de chargement :
	 * - Image #1 : loading="eager" + fetchpriority="high"
	 * - Images suivantes : loading="lazy" + decoding="async"
	 *
	 * @param  string $tag Balise <img>.
	 * @return string Balise modifiée.
	 */
	private function set_loading_strategy( string $tag ): string {
		if ( 1 === self::$image_count ) {
			// Première image — candidate LCP : priorité haute.
			$tag = $this->set_attr( $tag, 'loading', 'eager' );
			$tag = $this->ensure_attr( $tag, 'fetchpriority', 'high' );
		} else {
			// Images suivantes — below fold probable.
			$tag = $this->ensure_attr( $tag, 'loading', 'lazy' );
			$tag = $this->ensure_attr( $tag, 'decoding', 'async' );
		}

		return $tag;
	}

	/**
	 * Remplace le src par la version WebP si elle existe au même emplacement.
	 *
	 * @param  string $tag Balise <img>.
	 * @return string Balise avec src WebP si disponible, inchangée sinon.
	 */
	private function maybe_swap_webp( string $tag ): string {
		if ( ! \preg_match( '/\bsrc=["\']([^"\']+)["\']/', $tag, $matches ) ) {
			return $tag;
		}

		$src = $matches[1];

		// Ne traite que les images hébergées localement (uploads).
		$upload_dir = \wp_upload_dir();
		$base_url   = \trailingslashit( $upload_dir['baseurl'] );

		if ( false === \strpos( $src, $base_url ) ) {
			return $tag;
		}

		// Construit le chemin absolu et vérifie l'existence du .webp.
		$relative  = \str_replace( $base_url, '', $src );
		$base_path = \trailingslashit( $upload_dir['basedir'] );
		$orig_path = $base_path . $relative;
		$webp_path = \preg_replace( '/\.(jpe?g|png|gif)$/i', '.webp', $orig_path );

		if ( $webp_path === $orig_path || ! \file_exists( $webp_path ) ) {
			return $tag;
		}

		$webp_src = \preg_replace( '/\.(jpe?g|png|gif)$/i', '.webp', $src );

		return \str_replace(
			"src=\"{$src}\"",
			"src=\"{$webp_src}\"",
			$tag
		);
	}

	/**
	 * Émet un <link rel="preload"> pour l'image du premier bloc hero ou cover.
	 * Améliore le LCP en demandant le fetch de l'image avant le parser HTML.
	 *
	 * @return void
	 */
	public function preload_lcp_image(): void {
		if ( \is_admin() || ! \is_singular() ) {
			return;
		}

		$post = \get_post();
		if ( ! $post || empty( $post->post_content ) ) {
			return;
		}

		$blocks    = \parse_blocks( $post->post_content );
		$image_url = $this->find_first_hero_image( $blocks );

		if ( empty( $image_url ) ) {
			return;
		}

		echo '<link rel="preload" as="image" href="' . \esc_url( $image_url ) . '" fetchpriority="high">' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_url() appliqué
	}

	/**
	 * Recherche récursivement l'URL de la première image dans les blocs hero/cover/image.
	 *
	 * @param  array<int, array<string, mixed>> $blocks Blocs parsés par parse_blocks().
	 * @return string URL de l'image ou chaîne vide.
	 */
	private function find_first_hero_image( array $blocks ): string {
		$priority_blocks = [ 'g2rd/hero', 'core/cover', 'core/image' ];

		foreach ( $blocks as $block ) {
			$name = $block['blockName'] ?? '';

			if ( \in_array( $name, $priority_blocks, true ) ) {
				// core/cover et g2rd/hero stockent l'URL dans 'url' ou 'mediaUrl'.
				if ( ! empty( $block['attrs']['url'] ) ) {
					return (string) $block['attrs']['url'];
				}
				if ( ! empty( $block['attrs']['mediaUrl'] ) ) {
					return (string) $block['attrs']['mediaUrl'];
				}
			}

			// Récurser dans les blocs imbriqués.
			if ( ! empty( $block['innerBlocks'] ) ) {
				$found = $this->find_first_hero_image( $block['innerBlocks'] );
				if ( '' !== $found ) {
					return $found;
				}
			}
		}

		return '';
	}

	/**
	 * Définit (ou remplace) la valeur d'un attribut HTML dans une balise img.
	 *
	 * @param  string $tag   Balise <img>.
	 * @param  string $attr  Nom de l'attribut.
	 * @param  string $value Nouvelle valeur.
	 * @return string Balise modifiée.
	 */
	private function set_attr( string $tag, string $attr, string $value ): string {
		// Remplace si l'attribut existe déjà.
		if ( \preg_match( '/\b' . \preg_quote( $attr, '/' ) . '=["\'][^"\']*["\']/', $tag ) ) {
			return (string) \preg_replace(
				'/\b' . \preg_quote( $attr, '/' ) . '=["\'][^"\']*["\']/',
				$attr . '="' . \esc_attr( $value ) . '"',
				$tag
			);
		}

		return $this->ensure_attr( $tag, $attr, $value );
	}

	/**
	 * Ajoute un attribut uniquement s'il est absent.
	 *
	 * @param  string $tag   Balise <img>.
	 * @param  string $attr  Nom de l'attribut.
	 * @param  string $value Valeur à insérer.
	 * @return string Balise avec l'attribut ajouté si absent.
	 */
	private function ensure_attr( string $tag, string $attr, string $value ): string {
		if ( \preg_match( '/\b' . \preg_quote( $attr, '/' ) . '=/', $tag ) ) {
			return $tag;
		}

		return \str_replace( '>', ' ' . $attr . '="' . \esc_attr( $value ) . '">', $tag );
	}
}
