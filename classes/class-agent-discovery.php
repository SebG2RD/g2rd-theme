<?php
/**
 * Agent Discovery — RFC 8288 Link Headers, RFC 9727 API Catalog,
 * négociation Markdown et Content Signals (robots.txt).
 *
 * Centralise les fonctionnalités de découverte par les agents IA :
 * - Link headers (RFC 8288) sur toutes les pages frontend
 * - Endpoint /.well-known/api-catalog (RFC 9727, application/linkset+json)
 * - Négociation de contenu Markdown sur Accept: text/markdown
 * - Directive Content-Signal dans robots.txt
 *
 * @package G2RD
 * @since   1.7.2
 * @license EUPL-1.2
 * @link    https://www.rfc-editor.org/rfc/rfc8288
 * @link    https://www.rfc-editor.org/rfc/rfc9727
 */

namespace G2RD;

/**
 * Classe de découverte pour les agents IA et les crawlers automatisés.
 */
class AgentDiscovery {

	/**
	 * Enregistre tous les hooks de découverte.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		// RFC 8288 — Link headers de découverte sur les pages frontend
		\add_action( 'send_headers', [ $this, 'addLinkHeaders' ] );

		// RFC 9727 — Catalogue d'API + négociation Markdown (priorité 1 : avant template)
		\add_action( 'template_redirect', [ $this, 'serveWellKnown' ], 1 );
		\add_action( 'template_redirect', [ $this, 'serveMarkdown' ], 2 );

		// Content Signals — directives IA dans robots.txt
		\add_filter( 'robots_txt', [ $this, 'addContentSignals' ], 10, 2 );
	}

	// -------------------------------------------------------------------------
	// RFC 8288 — Link headers
	// -------------------------------------------------------------------------

	/**
	 * Émet les Link headers HTTP pour la découverte par les agents IA.
	 *
	 * Hooké sur send_headers, avant tout output HTML.
	 *
	 * @return void
	 */
	public function addLinkHeaders(): void {
		if ( \is_admin() ) {
			return;
		}

		$home = \esc_url_raw( \home_url() );

		// Service description : WP REST API (application/json)
		\header( 'Link: <' . $home . '/wp-json>; rel="service-desc"', false );

		// Catalogue d'API (RFC 9727, application/linkset+json)
		\header( 'Link: <' . $home . '/.well-known/api-catalog>; rel="api-catalog"', false );
	}

	// -------------------------------------------------------------------------
	// RFC 9727 — /.well-known/api-catalog
	// -------------------------------------------------------------------------

	/**
	 * Intercepte et sert les endpoints /.well-known/* avant le chargement du template.
	 *
	 * @return void
	 */
	public function serveWellKnown(): void {
		$raw_uri = \wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- wp_parse_url l'isole
		$path    = \wp_parse_url( $raw_uri, PHP_URL_PATH );

		if ( ! \is_string( $path ) ) {
			return;
		}

		switch ( $path ) {
			case '/.well-known/api-catalog':
				$this->outputApiCatalog();
				break;
		}
	}

	/**
	 * Génère et émet le catalogue d'API au format application/linkset+json (RFC 9727).
	 *
	 * @return void
	 */
	private function outputApiCatalog(): void {
		$method = \strtoupper( \sanitize_text_field( \wp_unslash( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) );

		if ( ! \in_array( $method, [ 'GET', 'HEAD' ], true ) ) {
			\status_header( 405 );
			\header( 'Allow: GET, HEAD' );
			exit;
		}

		$home    = \home_url();
		$version = \wp_get_theme()->get( 'Version' );

		$catalog = [
			'linkset' => [
				[
					'anchor'       => $home . '/wp-json',
					'service-desc' => [ [ 'href' => $home . '/wp-json' ] ],
					'service-doc'  => [ [ 'href' => 'https://developer.wordpress.org/rest-api/' ] ],
				],
				[
					'anchor'       => $home,
					'service-doc'  => [ [ 'href' => $home . '/wp-json' ] ],
					'describedby'  => [ [ 'href' => $home . '/wp-json', 'type' => 'application/json' ] ],
				],
			],
			'_meta' => [
				'generator' => 'G2RD Theme ' . $version,
				'generated' => \gmdate( 'Y-m-d\TH:i:s\Z' ),
			],
		];

		\status_header( 200 );
		\header( 'Content-Type: application/linkset+json; charset=UTF-8' );
		\header( 'Cache-Control: public, max-age=3600' );

		if ( 'HEAD' !== $method ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- application/linkset+json, pas HTML
			echo \wp_json_encode( $catalog, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
		}

		exit;
	}

	// -------------------------------------------------------------------------
	// Négociation de contenu Markdown
	// -------------------------------------------------------------------------

	/**
	 * Retourne une représentation Markdown de la page quand Accept: text/markdown est présent.
	 *
	 * Conforme à la recommandation RFC 8288 / Markdown for Agents.
	 *
	 * @return void
	 */
	public function serveMarkdown(): void {
		if ( \is_admin() || ! \is_singular() ) {
			return;
		}

		$accept = \sanitize_text_field( \wp_unslash( $_SERVER['HTTP_ACCEPT'] ?? '' ) );

		if ( false === \strpos( $accept, 'text/markdown' ) ) {
			return;
		}

		global $post;
		\setup_postdata( $post );

		$title    = \get_the_title( $post );
		$content  = \apply_filters( 'the_content', \get_the_content( null, false, $post ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- hook WP natif, pas notre hook
		$markdown = $this->htmlToMarkdown( $title, $content );

		\status_header( 200 );
		\header( 'Content-Type: text/markdown; charset=UTF-8' );
		\header( 'X-Markdown-Tokens: ' . \str_word_count( $markdown ) );
		\header( 'Cache-Control: public, max-age=600' );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- text/markdown, pas HTML
		echo $markdown;
		exit;
	}

	/**
	 * Convertit du HTML WordPress en Markdown (sans dépendance externe).
	 *
	 * @param string $title   Titre de la page.
	 * @param string $html    Contenu HTML filtré.
	 * @return string         Contenu Markdown.
	 */
	private function htmlToMarkdown( string $title, string $html ): string {
		// Supprimer les commentaires de blocs Gutenberg
		// phpcs:ignore PHPCS_SecurityAudit.BadFunctions.PregReplace.PregReplaceDyn -- pattern statique interne
		$html = \preg_replace( '/<!--[\s\S]*?-->/U', '', $html ) ?? $html;

		// Liens : traités en premier pour préserver le texte des ancres
		$html = \preg_replace_callback(
			'/<a[^>]+href=["\']([^"\']*)["\'][^>]*>(.*?)<\/a>/is',
			static function ( array $m ): string {
				return '[' . \wp_strip_all_tags( $m[2] ) . '](' . $m[1] . ')';
			},
			$html
		) ?? $html;

		// Images
		$html = \preg_replace_callback(
			'/<img[^>]+>/is',
			static function ( array $m ): string {
				\preg_match( '/src=["\']([^"\']*)["\']/', $m[0], $src );
				\preg_match( '/alt=["\']([^"\']*)["\']/', $m[0], $alt );
				$src_val = isset( $src[1] ) ? $src[1] : '';
				$alt_val = isset( $alt[1] ) ? $alt[1] : '';
				return '![' . $alt_val . '](' . $src_val . ')';
			},
			$html
		) ?? $html;

		// Balises de structure (ordre important : du plus spécifique au plus général)
		$static_replacements = [
			// Titres
			[ '/<h1[^>]*>(.*?)<\/h1>/is', "\n# \$1\n" ],
			[ '/<h2[^>]*>(.*?)<\/h2>/is', "\n## \$1\n" ],
			[ '/<h3[^>]*>(.*?)<\/h3>/is', "\n### \$1\n" ],
			[ '/<h4[^>]*>(.*?)<\/h4>/is', "\n#### \$1\n" ],
			[ '/<h5[^>]*>(.*?)<\/h5>/is', "\n##### \$1\n" ],
			[ '/<h6[^>]*>(.*?)<\/h6>/is', "\n###### \$1\n" ],
			// Emphases
			[ '/<(strong|b)[^>]*>(.*?)<\/\1>/is', '**$2**' ],
			[ '/<(em|i)[^>]*>(.*?)<\/\1>/is', '*$2*' ],
			// Code
			[ '/<pre[^>]*>(.*?)<\/pre>/is', "\n```\n\$1\n```\n" ],
			[ '/<code[^>]*>(.*?)<\/code>/is', '`$1`' ],
			// Blocs
			[ '/<blockquote[^>]*>(.*?)<\/blockquote>/is', "\n> \$1\n" ],
			[ '/<li[^>]*>(.*?)<\/li>/is', "\n- \$1" ],
			[ '/<p[^>]*>(.*?)<\/p>/is', "\n\$1\n" ],
			[ '/<br\s*\/?>/is', "\n" ],
			[ '/<hr\s*\/?>/is', "\n---\n" ],
		];

		foreach ( $static_replacements as [ $pattern, $replacement ] ) {
			// phpcs:ignore PHPCS_SecurityAudit.BadFunctions.PregReplace.PregReplaceDyn -- tableau statique interne, pas d'entrée utilisateur
			$html = \preg_replace( $pattern, $replacement, $html ) ?? $html;
		}

		// Supprimer les balises résiduelles et décoder les entités HTML
		$html = \wp_strip_all_tags( $html );
		$html = \html_entity_decode( $html, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		// Normaliser les sauts de ligne multiples
		$html = \preg_replace( '/\n{3,}/', "\n\n", $html ) ?? $html;
		$html = \trim( $html );

		return "# {$title}\n\n{$html}";
	}

	// -------------------------------------------------------------------------
	// Content Signals — robots.txt
	// -------------------------------------------------------------------------

	/**
	 * Ajoute les directives Content-Signal au robots.txt WordPress.
	 *
	 * Placé dans un bloc User-agent: * dédié pour ne pas altérer les règles existantes.
	 * Spec : https://contentsignals.org/
	 *
	 * @param string $output  Contenu actuel du robots.txt.
	 * @param bool   $public  Vrai si le site est en mode public.
	 * @return string
	 */
	public function addContentSignals( string $output, bool $public ): string {
		if ( ! $public ) {
			return $output;
		}

		// ai-train=no  : contenu protégé, non utilisable pour l'entraînement de modèles
		// search=yes   : indexation par les moteurs de recherche autorisée
		// ai-input=yes : les agents IA peuvent lire et résumer le contenu (discovery clients)
		$output .= "\nUser-agent: *\nContent-Signal: ai-train=no, search=yes, ai-input=yes\n";

		return $output;
	}
}
