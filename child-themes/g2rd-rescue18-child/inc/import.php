<?php
/**
 * Outils › Préparer Rescue 18 : construction du site à partir de rescue18.com.
 *
 * Le site local interroge l'API REST publique de rescue18.com et reprend :
 * structure (termes, pages, permaliens), articles (auteurs, catégories,
 * étiquettes, images à la une), images intégrées aux articles, fiches
 * documentaires (GDO, GTO, GNR, référentiels, RETEX), textes juridiques
 * (liens Légifrance), épisodes du podcast, un quiz de démonstration et,
 * si WooCommerce est actif, les produits de la boutique.
 *
 * Chaque étape avance par lots (AJAX, nonce, manage_options) et reste
 * relançable : un contenu déjà importé est reconnu par sa clé source
 * (méta r18_source_id) et n'est jamais dupliqué. Slugs et dates d'origine
 * sont conservés : les URL restent celles du site actuel.
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18;

\defined( 'ABSPATH' ) || exit;

/**
 * Site source.
 *
 * @return string
 */
function source_url(): string {
	return \untrailingslashit( (string) \apply_filters( 'rescue18_source_url', 'https://rescue18.com' ) );
}

/**
 * Étapes : clé => [ libellé, description ].
 *
 * @return array<string,array{0:string,1:string}>
 */
function import_steps(): array {
	return array(
		'structure'  => array( 'Structure', 'Permaliens, termes, spécialités, pages aux URL historiques, catégories, pages éditoriales du site actuel.' ),
		'articles'   => array( 'Articles', 'Articles avec auteurs, catégories, étiquettes, extraits et images à la une (lots de 10).' ),
		'documents'  => array( 'Documentation', 'Fiches GDO, GTO, GNR, référentiels et RETEX relevées sur les pages documentaires.' ),
		'juridique'  => array( 'Juridique', 'Textes juridiques et leurs liens Légifrance, état « À vérifier ».' ),
		'podcasts'   => array( 'Podcasts', 'Épisodes de SSUAP Le Podcast avec leur vidéo.' ),
		'quiz'       => array( 'Quiz', 'Quiz de démonstration « GDO, GTO, sigles ».' ),
		'images'     => array( 'Images des articles', 'Rapatrie dans la médiathèque les images intégrées au texte des articles (lots de 5).' ),
		'produits'   => array( 'Boutique', 'Produits de la boutique actuelle (nécessite WooCommerce).' ),
	);
}

/**
 * GET JSON sur le site source.
 *
 * @param string $path Chemin de l'API.
 * @return array{0:mixed,1:int} Corps décodé et nombre total de pages.
 */
function remote_json( string $path ): array {
	$res = \wp_remote_get(
		source_url() . $path,
		array(
			'timeout' => 40,
			'headers' => array( 'Accept' => 'application/json' ),
		)
	);
	if ( \is_wp_error( $res ) ) {
		throw new \RuntimeException( 'Source injoignable : ' . $res->get_error_message() );
	}
	$code = (int) \wp_remote_retrieve_response_code( $res );
	if ( $code >= 400 ) {
		throw new \RuntimeException( "Réponse $code pour $path" );
	}
	return array( \json_decode( (string) \wp_remote_retrieve_body( $res ), true ), (int) \wp_remote_retrieve_header( $res, 'x-wp-totalpages' ) );
}

/**
 * Contenu existant par clé source.
 *
 * @param string $key       Clé (ex. post:123).
 * @param string $post_type Type de contenu.
 * @return int
 */
function find_source( string $key, string $post_type ): int {
	$ids = \get_posts(
		array(
			'post_type'      => $post_type,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_key'       => 'r18_source_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- dédoublonnage de l'import.
			'meta_value'     => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- dédoublonnage de l'import.
		)
	);
	return (int) ( $ids[0] ?? 0 );
}

/**
 * Texte brut depuis du HTML rendu.
 *
 * @param string $html HTML.
 * @return string
 */
function plain( string $html ): string {
	return \trim( \preg_replace( '/\s+/u', ' ', \html_entity_decode( \wp_strip_all_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
}

/**
 * Charge un fragment HTML dans un DOMXPath.
 *
 * @param string $html HTML.
 * @return \DOMXPath
 */
function xpath( string $html ): \DOMXPath {
	$doc = new \DOMDocument();
	\libxml_use_internal_errors( true );
	$doc->loadHTML( '<?xml encoding="utf-8"?><div>' . $html . '</div>' );
	\libxml_clear_errors();
	return new \DOMXPath( $doc );
}

/**
 * Corrige une URL source (doublons « http…http… », protocole).
 *
 * @param string $href URL.
 * @return string
 */
function clean_href( string $href ): string {
	$href = \trim( $href );
	$pos  = \strpos( $href, 'http', 4 );
	if ( false !== $pos ) {
		$href = \substr( $href, 0, $pos );
	}
	$href = \preg_replace( '/[?&]tmstv=\d+/', '', $href );
	return \str_replace( 'http://rescue18.com', 'https://rescue18.com', (string) $href );
}

/**
 * Télécharge une image dans la médiathèque (une seule fois par URL).
 *
 * @param string $url     URL distante.
 * @param int    $post_id Contenu parent.
 * @param string $alt     Texte alternatif.
 * @return int ID de la pièce jointe, 0 si échec.
 */
function sideload( string $url, int $post_id, string $alt = '' ): int {
	$url = \str_replace( 'http://', 'https://', $url );
	$ids = \get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_r18_source_url', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- une image n'est importée qu'une fois.
			'meta_value'     => $url, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
	if ( ! empty( $ids ) ) {
		return (int) $ids[0];
	}
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$id = \media_sideload_image( $url, $post_id, $alt, 'id' );
	if ( \is_wp_error( $id ) ) {
		return 0;
	}
	\update_post_meta( (int) $id, '_r18_source_url', $url );
	if ( '' !== $alt ) {
		\update_post_meta( (int) $id, '_wp_attachment_image_alt', \sanitize_text_field( $alt ) );
	}
	return (int) $id;
}

/**
 * Correspondance anciennes catégories → spécialités.
 *
 * @return array<string,string>
 */
function category_to_specialite(): array {
	return array(
		'incendie'                            => 'incendie',
		'fiches-feu'                          => 'incendie',
		'fiches-forcement'                    => 'incendie',
		'autres-materiels'                    => 'incendie',
		'prevention-appliquee-a-loperationnel' => 'incendie',
		'sauvetages-de-sauveteurs'            => 'incendie',
		'fiches-fdf'                          => 'feux-de-forets',
		'ssuap'                               => 'ssuap',
		'secours-routier-iuv'                 => 'secours-routier',
		'smpm-usar'                           => 'smpm-usar',
		'nrbc-menaces'                        => 'nrbc',
		'outils-daide-a-la-decision-du-cos-goc' => 'goc-cos',
		'sic-cta-codis'                       => 'sic-cta-codis',
		'aasc'                                => 'aasc',
		'opd-div'                             => 'opd-div',
		'qualite-sante-vie-travail-fh-pmo'    => 'qsvt-fh-pmo',
		'sport'                               => 'qsvt-fh-pmo',
		'vehicules-de-secours'                => 'vehicules-secours',
	);
}

/**
 * Spécialité devinée depuis un titre de document.
 *
 * @param string $title Titre.
 * @return string Slug ou vide.
 */
function guess_specialite( string $title ): string {
	$t   = \strtolower( \remove_accents( $title ) );
	$map = array(
		'feux-de-forets'    => array( 'foret', 'fdf', 'espaces naturels', 'debroussaillement' ),
		'secours-routier'   => array( 'routier', 'desincarceration', 'vehicule' ),
		'ssuap'             => array( 'suap', 'secours d\'urgence', 'secourisme', 'pse', 'psc', 'gestes qui sauvent', 'soins' ),
		'nrbc'              => array( 'nrbc', 'radiolog', 'chimique', 'biolog', 'gaz', 'hydrocarbure', 'risques technologiques' ),
		'smpm-usar'         => array( 'sauvetage deblaiement', 'milieu perilleux', 'smpm', 'usar', 'cynotechn', 'plongee', 'aquatique' ),
		'goc-cos'           => array( 'commandement', 'conduite des operations', 'goc', 'cos' ),
		'sic-cta-codis'     => array( 'transmission', 'communication', 'cta', 'codis', 'sic' ),
		'vehicules-secours' => array( 'engin', 'materiel', 'tenue', 'epi', 'equipement' ),
		'incendie'          => array( 'incendie', 'extinction', 'feu', 'ventilation', 'ari', 'electricite', 'sauvetage' ),
	);
	foreach ( $map as $slug => $words ) {
		foreach ( $words as $w ) {
			if ( \preg_match( '/\b' . \preg_quote( $w, '/' ) . '/', $t ) ) {
				return $slug;
			}
		}
	}
	return '';
}

/**
 * Exécute une étape (un lot).
 *
 * @param string $step Étape.
 * @param int    $page Lot (à partir de 1).
 * @return array{log:string[],next:int,total:int}
 */
function run_import_step( string $step, int $page ): array {
	\wp_raise_memory_limit( 'admin' );
	if ( \function_exists( 'set_time_limit' ) ) {
		\set_time_limit( 300 ); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- lots d'import.
	}
	switch ( $step ) {
		case 'structure':
			return import_structure();
		case 'articles':
			return import_articles( $page );
		case 'documents':
			return import_documents();
		case 'juridique':
			return import_juridique();
		case 'podcasts':
			return import_podcasts();
		case 'quiz':
			return import_quiz();
		case 'images':
			return import_inline_images( $page );
		case 'produits':
			return import_products( $page );
	}
	throw new \InvalidArgumentException( 'Étape inconnue' );
}

/**
 * Structure : installation + catégories + pages éditoriales.
 *
 * @return array{log:string[],next:int,total:int}
 */
function import_structure(): array {
	$log = \array_merge( purge_parent_template_copies(), install_terms(), install_pages( false ) );

	// Catégories d'articles, parents d'abord.
	[ $cats ] = remote_json( '/wp-json/wp/v2/categories?per_page=100&_fields=id,name,slug,parent,description' );
	$map      = array();
	\usort( $cats, static fn( $a, $b ) => (int) $a['parent'] <=> (int) $b['parent'] );
	foreach ( (array) $cats as $c ) {
		$parent = isset( $map[ (int) $c['parent'] ] ) ? $map[ (int) $c['parent'] ] : 0;
		$term   = \get_term_by( 'slug', $c['slug'], 'category' );
		if ( ! $term ) {
			$r    = \wp_insert_term(
				\html_entity_decode( (string) $c['name'], ENT_QUOTES, 'UTF-8' ),
				'category',
				array(
					'slug'        => $c['slug'],
					'parent'      => $parent,
					'description' => plain( (string) $c['description'] ),
				)
			);
			$tid = \is_array( $r ) ? (int) $r['term_id'] : 0;
		} else {
			$tid = (int) $term->term_id;
		}
		$map[ (int) $c['id'] ] = $tid;
	}
	\update_option( 'rescue18_import_catmap', $map, false );
	$log[] = \count( $map ) . ' catégories d\'articles.';

	// Pages éditoriales reprises telles quelles (les pages gérées, l'espace
	// membre, les jeux-concours terminés et WooCommerce sont exclus).
	$keep = array( 'mentions-legales', 'politique-de-confidentialite', 'contactez-nous', 'plan-du-site', 'acronyme', 'devenir-pompier' );
	foreach ( $keep as $slug ) {
		if ( \get_page_by_path( $slug ) ) {
			continue;
		}
		[ $rows ] = remote_json( '/wp-json/wp/v2/pages?slug=' . \rawurlencode( $slug ) . '&_fields=id,title,content,date' );
		if ( empty( $rows[0] ) ) {
			continue;
		}
		$id = \wp_insert_post(
			\wp_slash(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => \html_entity_decode( (string) $rows[0]['title']['rendered'], ENT_QUOTES, 'UTF-8' ),
					'post_name'    => $slug,
					'post_date'    => (string) $rows[0]['date'],
					'post_content' => (string) $rows[0]['content']['rendered'],
				)
			)
		);
		\update_post_meta( (int) $id, 'r18_source_id', 'page:' . (int) $rows[0]['id'] );
		$log[] = "/$slug/ : page reprise.";
	}
	\update_option( 'blogname', 'Rescue 18' );
	\update_option( 'blogdescription', 'Le webmédia des sapeurs-pompiers' );
	return array(
		'log'   => $log,
		'next'  => 0,
		'total' => 1,
	);
}

/**
 * Auteur local correspondant à un auteur source.
 *
 * @param array<string,mixed> $a Auteur embarqué.
 * @return int
 */
function author_id( array $a ): int {
	$slug  = \sanitize_user( (string) ( $a['slug'] ?? '' ), true );
	if ( '' === $slug ) {
		return (int) \get_current_user_id();
	}
	$login = 'r18-' . $slug;
	$user  = \get_user_by( 'login', $login );
	if ( $user ) {
		return (int) $user->ID;
	}
	// Compte auteur sans accès réel : mot de passe aléatoire, adresse non routable, aucun e-mail envoyé.
	$id = \wp_insert_user(
		array(
			'user_login'   => $login,
			'user_pass'    => \wp_generate_password( 32 ),
			'user_email'   => $login . '@rescue18.invalid',
			'display_name' => (string) ( $a['name'] ?? $slug ),
			'nickname'     => (string) ( $a['name'] ?? $slug ),
			'user_nicename' => $slug,
			'description'  => plain( (string) ( $a['description'] ?? '' ) ),
			'role'         => 'author',
		)
	);
	return \is_wp_error( $id ) ? (int) \get_current_user_id() : (int) $id;
}

/**
 * Articles par lots de 10.
 *
 * @param int $page Lot.
 * @return array{log:string[],next:int,total:int}
 */
function import_articles( int $page ): array {
	[ $rows, $total ] = remote_json( '/wp-json/wp/v2/posts?per_page=10&page=' . $page . '&orderby=date&order=asc&_embed=author,wp:featuredmedia,wp:term' );
	$catmap           = (array) \get_option( 'rescue18_import_catmap', array() );
	$spemap           = category_to_specialite();
	$log              = array();
	foreach ( (array) $rows as $p ) {
		$key = 'post:' . (int) $p['id'];
		if ( find_source( $key, 'post' ) ) {
			continue;
		}
		$author = author_id( (array) ( $p['_embedded']['author'][0] ?? array() ) );
		$id     = \wp_insert_post(
			\wp_slash(
				array(
					'post_type'     => 'post',
					'post_status'   => 'publish',
					'post_title'    => \html_entity_decode( (string) $p['title']['rendered'], ENT_QUOTES, 'UTF-8' ),
					'post_name'     => (string) $p['slug'],
					'post_date'     => (string) $p['date'],
					'post_modified' => (string) ( $p['modified'] ?? $p['date'] ),
					'post_author'   => $author,
					'post_content'  => (string) $p['content']['rendered'],
					'post_excerpt'  => plain( (string) $p['excerpt']['rendered'] ),
				)
			),
			true
		);
		if ( \is_wp_error( $id ) ) {
			$log[] = 'Échec : ' . $p['slug'];
			continue;
		}
		\update_post_meta( $id, 'r18_source_id', $key );

		$cats = array();
		$spes = array();
		foreach ( (array) $p['categories'] as $old ) {
			if ( isset( $catmap[ (int) $old ] ) ) {
				$cats[] = (int) $catmap[ (int) $old ];
				$term   = \get_term( (int) $catmap[ (int) $old ], 'category' );
				if ( $term instanceof \WP_Term && isset( $spemap[ $term->slug ] ) ) {
					$spes[] = $spemap[ $term->slug ];
				}
			}
		}
		if ( $cats ) {
			\wp_set_post_categories( $id, $cats );
		}
		if ( $spes ) {
			\wp_set_object_terms( $id, \array_unique( $spes ), 'r18_specialite' );
		}
		$tags = array();
		foreach ( (array) ( $p['_embedded']['wp:term'] ?? array() ) as $group ) {
			foreach ( (array) $group as $t ) {
				if ( 'post_tag' === ( $t['taxonomy'] ?? '' ) ) {
					$tags[] = \html_entity_decode( (string) $t['name'], ENT_QUOTES, 'UTF-8' );
				}
			}
		}
		if ( $tags ) {
			\wp_set_post_tags( $id, $tags );
		}
		$media = (array) ( $p['_embedded']['wp:featuredmedia'][0] ?? array() );
		if ( ! empty( $media['source_url'] ) ) {
			$att = sideload( (string) $media['source_url'], $id, (string) ( $media['alt_text'] ?? '' ) );
			if ( $att ) {
				\set_post_thumbnail( $id, $att );
			}
		}
		$log[] = 'Article : ' . \get_the_title( $id );
	}
	return array(
		'log'   => $log,
		'next'  => $page < $total ? $page + 1 : 0,
		'total' => $total,
	);
}

/**
 * Crée une fiche documentaire si absente.
 *
 * @param string              $title Titre.
 * @param string              $href  Lien source.
 * @param string              $type  Type (gdo, gto…).
 * @param string              $desc  Extrait.
 * @param array<string,mixed> $extra Termes supplémentaires.
 * @return bool Créée.
 */
function create_document( string $title, string $href, string $type, string $desc = '', array $extra = array() ): bool {
	$href = clean_href( $href );
	$key  = 'doc:' . \md5( $href );
	if ( '' === $title || '' === $href || find_source( $key, 'r18_document' ) ) {
		return false;
	}
	$id = \wp_insert_post(
		\wp_slash(
			array(
				'post_type'    => 'r18_document',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_excerpt' => $desc,
				'post_content' => '<!-- wp:paragraph --><p>' . \esc_html( '' !== $desc ? $desc : $title ) . '</p><!-- /wp:paragraph -->',
			)
		)
	);
	if ( ! $id ) {
		return false;
	}
	\update_post_meta( $id, 'r18_source_id', $key );
	\update_post_meta( $id, 'r18_source_url', \esc_url_raw( $href ) );
	if ( \str_ends_with( \strtolower( (string) \wp_parse_url( $href, PHP_URL_PATH ) ), '.pdf' ) ) {
		\update_post_meta( $id, 'r18_pdf_url', \esc_url_raw( $href ) );
	}
	\update_post_meta( $id, 'r18_statut', 'en-ligne' );
	\update_post_meta( $id, 'r18_derniere_verif', \wp_date( 'Y-m-d' ) );
	if ( \preg_match( '/(20\d{2})/', $title . ' ' . $href, $m ) ) {
		\update_post_meta( $id, 'r18_version', 'Édition ' . $m[1] );
	}
	\wp_set_object_terms( $id, $type, 'r18_type_doc' );
	\wp_set_object_terms( $id, 'retex' === $type ? 'ensosp-pnrs' : 'dgscgc', 'r18_organisme' );
	$spe = guess_specialite( $title );
	if ( '' !== $spe ) {
		\wp_set_object_terms( $id, $spe, 'r18_specialite' );
	}
	foreach ( $extra as $tax => $terms ) {
		\wp_set_object_terms( $id, (array) $terms, $tax );
	}
	return true;
}

/**
 * Fiches documentaires relevées sur les pages du site actuel.
 *
 * @return array{log:string[],next:int,total:int}
 */
function import_documents(): array {
	$sources = array(
		'guides-doctrines-operationnelles-gdo'           => 'gdo',
		'guides-techniques-operationnelles-gto'          => 'gto',
		'gnr-vademecum'                                  => 'gnr',
		'referentiels-techniques'                        => 'referentiel',
		'referentiels-formations'                        => 'referentiel',
		'formation-secours-durgence-aux-personnes-suap'  => 'referentiel',
		'retex'                                          => 'retex',
	);
	$log = array();
	foreach ( $sources as $slug => $type ) {
		[ $rows ] = remote_json( '/wp-json/wp/v2/pages?slug=' . $slug . '&_fields=content' );
		$html     = (string) ( $rows[0]['content']['rendered'] ?? '' );
		if ( '' === $html ) {
			continue;
		}
		$x     = xpath( $html );
		$count = 0;
		// Encadrés « info-box » : titre, description, lien de téléchargement.
		foreach ( $x->query( "//*[contains(concat(' ', normalize-space(@class), ' '), ' wp-block-uagb-info-box ')]" ) as $box ) {
			$t     = $x->query( ".//*[contains(@class,'uagb-ifb-title')]", $box )->item( 0 );
			$d     = $x->query( ".//*[contains(@class,'uagb-ifb-desc')]", $box )->item( 0 );
			$a     = $x->query( './/a[@href]', $box );
			$href  = '';
			foreach ( $a as $link ) {
				$h = (string) $link->getAttribute( 'href' );
				if ( '' !== $h && '#' !== $h[0] ) {
					$href = $h;
					break;
				}
			}
			$title = $t ? plain( $t->textContent ) : '';
			$extra = array();
			if ( 'referentiels-formations' === $slug ) {
				$pub = array();
				foreach ( array( 'jsp' => 'jeune', 'spv' => 'spv', 'spp' => 'spp' ) as $k => $w ) {
					if ( \str_contains( \strtolower( \remove_accents( $title ) ), $w ) ) {
						$pub[] = $k;
					}
				}
				$extra['r18_public'] = $pub;
			}
			if ( '' !== $href && create_document( $title, $href, $type, $d ? plain( $d->textContent ) : '', $extra ) ) {
				++$count;
			}
		}
		// Liens directs vers des PDF ou des téléchargements (pages sans encadrés).
		foreach ( $x->query( "//a[contains(@href,'telechargement') or contains(@href,'.pdf')]" ) as $link ) {
			$title = plain( $link->textContent );
			if ( \mb_strlen( $title ) < 6 || \preg_match( '/^(télécharger|voir|consulter|lire)/iu', $title ) ) {
				$title = \ucfirst( \str_replace( '-', ' ', \basename( (string) \wp_parse_url( clean_href( $link->getAttribute( 'href' ) ), PHP_URL_PATH ), '.pdf' ) ) );
			}
			if ( create_document( $title, (string) $link->getAttribute( 'href' ), $type ) ) {
				++$count;
			}
		}
		$log[] = "/$slug/ : $count fiche(s) créée(s).";
	}
	return array(
		'log'   => $log,
		'next'  => 0,
		'total' => 1,
	);
}

/**
 * Date française « 11 juin 2025 » → 2025-06-11.
 *
 * @param string $txt Texte.
 * @return string
 */
function parse_date_fr( string $txt ): string {
	$months = array( 'janvier' => 1, 'fevrier' => 2, 'mars' => 3, 'avril' => 4, 'mai' => 5, 'juin' => 6, 'juillet' => 7, 'aout' => 8, 'septembre' => 9, 'octobre' => 10, 'novembre' => 11, 'decembre' => 12 );
	if ( \preg_match( '/(\d{1,2})(?:er)?\s+([a-zéèûô]+)\s+(\d{4})/iu', $txt, $m ) ) {
		$mo = $months[ \strtolower( \remove_accents( $m[2] ) ) ] ?? 0;
		if ( $mo ) {
			return \sprintf( '%04d-%02d-%02d', (int) $m[3], $mo, (int) $m[1] );
		}
	}
	return '';
}

/**
 * Textes juridiques : liens Légifrance de la page Juridique.
 *
 * @return array{log:string[],next:int,total:int}
 */
function import_juridique(): array {
	[ $rows ] = remote_json( '/wp-json/wp/v2/pages?slug=juridique&_fields=content' );
	$x        = xpath( (string) ( $rows[0]['content']['rendered'] ?? '' ) );
	$theme    = '';
	$count    = 0;
	$natures  = array( 'loi' => 'loi', 'ordonnance' => 'ordonnance', 'décret' => 'decret', 'decret' => 'decret', 'arrêté' => 'arrete', 'arrete' => 'arrete', 'circulaire' => 'circulaire', 'instruction' => 'instruction', 'note' => 'instruction' );
	foreach ( $x->query( "//h2 | //a[contains(@href,'legifrance.gouv.fr')]" ) as $node ) {
		if ( 'h2' === $node->nodeName ) {
			$theme = \trim( plain( $node->textContent ), '[] ' );
			continue;
		}
		$href = clean_href( (string) $node->getAttribute( 'href' ) );
		$full = plain( $node->textContent );
		if ( '' === $full || ! \preg_match( '/(JORFTEXT|LEGITEXT)\d+/', $href, $idm ) ) {
			continue;
		}
		$key = 'jur:' . $idm[0];
		if ( find_source( $key, 'r18_texte' ) ) {
			continue;
		}
		$title = $full;
		$rest  = '';
		if ( \preg_match( '/^(.*?\bdu\s+\d{1,2}(?:er)?\s+\p{L}+\s+\d{4})[\s,]*(.*)$/u', $full, $m ) ) {
			$title = $m[1];
			$rest  = $m[2];
		}
		$first  = \mb_strtolower( (string) \strtok( $full, ' ' ) );
		$nature = $natures[ $first ] ?? '';
		$id     = \wp_insert_post(
			\wp_slash(
				array(
					'post_type'    => 'r18_texte',
					'post_status'  => 'publish',
					'post_title'   => $title,
					'post_excerpt' => \ucfirst( $rest ),
					'post_content' => '<!-- wp:paragraph --><p>' . \esc_html( $full ) . '</p><!-- /wp:paragraph -->' . ( '' !== $theme ? '<!-- wp:paragraph --><p>' . \esc_html( 'Thématique : ' . $theme ) . '</p><!-- /wp:paragraph -->' : '' ),
				)
			)
		);
		if ( ! $id ) {
			continue;
		}
		\update_post_meta( $id, 'r18_source_id', $key );
		\update_post_meta( $id, 'r18_source_url', \esc_url_raw( $href ) );
		\update_post_meta( $id, 'r18_etat', 'a-verifier' );
		\update_post_meta( $id, \str_starts_with( $idm[0], 'JORF' ) ? 'r18_jorf' : 'r18_legi', $idm[0] );
		if ( '' !== $nature ) {
			\update_post_meta( $id, 'r18_nature', $nature );
		}
		if ( \preg_match( '/n°\s*([\d-]+)/u', $full, $nm ) ) {
			\update_post_meta( $id, 'r18_numero', $nm[1] );
		}
		$date = parse_date_fr( $title );
		if ( '' !== $date ) {
			\update_post_meta( $id, 'r18_date_signature', $date );
			\wp_update_post(
				array(
					'ID'            => $id,
					'post_date'     => $date . ' 09:00:00',
					'post_date_gmt' => \get_gmt_from_date( $date . ' 09:00:00' ),
				)
			);
		}
		\wp_set_object_terms( $id, 'legifrance', 'r18_organisme' );
		++$count;
	}
	return array(
		'log'   => array( "$count texte(s) juridique(s) créé(s), état « À vérifier » en attente de la synchronisation Légifrance." ),
		'next'  => 0,
		'total' => 1,
	);
}

/**
 * Épisodes du podcast (liens YouTube « #NN titre »).
 *
 * @return array{log:string[],next:int,total:int}
 */
function import_podcasts(): array {
	[ $rows ] = remote_json( '/wp-json/wp/v2/pages?slug=ssuap-le-podcast&_fields=content' );
	$x        = xpath( (string) ( $rows[0]['content']['rendered'] ?? '' ) );
	$count    = 0;
	$base     = \strtotime( '2026-10-01 08:00:00' );
	foreach ( $x->query( "//a[contains(@href,'youtube.com/watch')]" ) as $a ) {
		$text = plain( $a->textContent );
		if ( ! \preg_match( '/^#\s*(HS\s*\d+|\d+)\s+(.+)$/iu', $text, $m ) ) {
			continue;
		}
		$num  = \strtoupper( \str_replace( ' ', '', $m[1] ) );
		$href = (string) $a->getAttribute( 'href' );
		$key  = 'ep:' . $num;
		if ( find_source( $key, 'r18_episode' ) ) {
			continue;
		}
		$title = \preg_replace( '/\s{2,}/u', ' : ', \trim( $m[2] ) );
		$embed = '<!-- wp:embed {"url":"' . \esc_url( $href ) . '","type":"video","providerNameSlug":"youtube","responsive":true,"className":"wp-embed-aspect-16-9 wp-has-aspect-ratio"} --><figure class="wp-block-embed is-type-video is-provider-youtube wp-block-embed-youtube wp-embed-aspect-16-9 wp-has-aspect-ratio"><div class="wp-block-embed__wrapper">' . "\n" . \esc_url( $href ) . "\n" . '</div></figure><!-- /wp:embed -->';
		$order = \ctype_digit( $num ) ? (int) $num : 0;
		$date  = \wp_date( 'Y-m-d H:i:s', $base - ( 120 - $order ) * DAY_IN_SECONDS );
		$id    = \wp_insert_post(
			\wp_slash(
				array(
					'post_type'    => 'r18_episode',
					'post_status'  => 'publish',
					'post_title'   => $title,
					'post_content' => $embed,
					'post_date'    => $date,
				)
			)
		);
		if ( ! $id ) {
			continue;
		}
		\update_post_meta( $id, 'r18_source_id', $key );
		\update_post_meta( $id, 'r18_numero_episode', $num );
		\update_post_meta( $id, 'r18_youtube_url', \esc_url_raw( $href ) );
		\wp_set_object_terms( $id, 'ssuap', 'r18_specialite' );
		++$count;
	}
	return array(
		'log'   => array( "$count épisode(s) créé(s)." ),
		'next'  => 0,
		'total' => 1,
	);
}

/**
 * Quiz de démonstration (questions génériques de la maquette).
 *
 * @return array{log:string[],next:int,total:int}
 */
function import_quiz(): array {
	if ( find_source( 'quiz:demo', 'r18_quiz' ) ) {
		return array(
			'log'   => array( 'Quiz déjà présent.' ),
			'next'  => 0,
			'total' => 1,
		);
	}
	$questions = array(
		array( 'q' => 'Quel sigle désigne un guide de doctrine opérationnelle ?', 'c' => array( 'GTO', 'GDO', 'RETEX' ), 'a' => 1, 'why' => 'GDO signifie guide de doctrine opérationnelle. Le GTO décrit les techniques.' ),
		array( 'q' => 'Quelle rubrique regroupe les retours et partages d\'expérience ?', 'c' => array( 'RETEX / PEX', 'Juridique', 'Boutique' ), 'a' => 0, 'why' => 'Les retours et partages d\'expérience sont classés dans RETEX / PEX.' ),
		array( 'q' => 'Où vérifier la version en vigueur d\'un décret ?', 'c' => array( 'Une copie sans date', 'Légifrance, la source officielle', 'Un commentaire non sourcé' ), 'a' => 1, 'why' => 'Légifrance publie le texte, son état et ses versions consolidées.' ),
	);
	$id        = \wp_insert_post(
		\wp_slash(
			array(
				'post_type'    => 'r18_quiz',
				'post_status'  => 'publish',
				'post_title'   => 'GDO, GTO, sigles : connais-tu tes documents ?',
				'post_name'    => 'gdo-gto-sigles',
				'post_excerpt' => 'Trois questions pour vérifier les bases de la documentation opérationnelle. Thème : documentation. Niveau : tous publics. Durée : 2 min.',
			)
		)
	);
	\update_post_meta( $id, 'r18_source_id', 'quiz:demo' );
	\update_post_meta( $id, 'r18_niveau', 'Tous publics' );
	\update_post_meta( $id, 'r18_duree', '2 min' );
	\update_post_meta( $id, 'r18_questions', \wp_slash( (string) \wp_json_encode( $questions, JSON_UNESCAPED_UNICODE ) ) );
	return array(
		'log'   => array( 'Quiz « GDO, GTO, sigles » créé.' ),
		'next'  => 0,
		'total' => 1,
	);
}

/**
 * Images intégrées aux articles : rapatriement par lots de 5 articles.
 *
 * @param int $page Lot.
 * @return array{log:string[],next:int,total:int}
 */
function import_inline_images( int $page ): array {
	$q     = new \WP_Query(
		array(
			'post_type'      => 'post',
			'posts_per_page' => 5,
			'paged'          => $page,
			'meta_key'       => 'r18_source_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- articles importés.
			'orderby'        => 'ID',
			'order'          => 'ASC',
		)
	);
	$log   = array();
	$host  = \preg_quote( (string) \wp_parse_url( source_url(), PHP_URL_HOST ), '#' );
	foreach ( $q->posts as $p ) {
		if ( \get_post_meta( $p->ID, 'r18_images_ok', true ) ) {
			continue;
		}
		$content = $p->post_content;
		$n       = 0;
		if ( \preg_match_all( '#<img[^>]+src="(https?://(?:www\.)?' . $host . '/wp-content/uploads/[^"]+)"[^>]*>#i', $content, $all, PREG_SET_ORDER ) ) {
			foreach ( $all as $m ) {
				$alt = \preg_match( '/alt="([^"]*)"/', $m[0], $am ) ? \html_entity_decode( $am[1] ) : '';
				$att = sideload( $m[1], $p->ID, $alt );
				if ( ! $att ) {
					continue;
				}
				$url     = (string) \wp_get_attachment_image_url( $att, 'large' );
				$tag     = \preg_replace( array( '/\s(srcset|sizes|data-src|data-srcset)="[^"]*"/', '/src="[^"]+"/' ), array( '', 'src="' . \esc_url( $url ) . '"' ), $m[0] );
				$content = \str_replace( $m[0], (string) $tag, $content );
				++$n;
			}
			\wp_update_post(
				array(
					'ID'           => $p->ID,
					'post_content' => \wp_slash( $content ),
				)
			);
		}
		\update_post_meta( $p->ID, 'r18_images_ok', '1' );
		if ( $n ) {
			$log[] = "$n image(s) : " . $p->post_title;
		}
	}
	return array(
		'log'   => $log,
		'next'  => $page < (int) $q->max_num_pages ? $page + 1 : 0,
		'total' => (int) $q->max_num_pages,
	);
}

/**
 * Produits via l'API Store de WooCommerce (lecture publique).
 *
 * @param int $page Lot.
 * @return array{log:string[],next:int,total:int}
 */
function import_products( int $page ): array {
	if ( ! \class_exists( 'WC_Product_Simple' ) ) {
		return array(
			'log'   => array( 'WooCommerce n\'est pas actif : étape à relancer après son installation.' ),
			'next'  => 0,
			'total' => 1,
		);
	}
	[ $rows, $total ] = remote_json( '/wp-json/wc/store/v1/products?per_page=10&page=' . $page );
	$log              = array();
	foreach ( (array) $rows as $r ) {
		$key = 'product:' . (int) $r['id'];
		if ( find_source( $key, 'product' ) ) {
			continue;
		}
		$product = new \WC_Product_Simple();
		$product->set_name( \html_entity_decode( (string) $r['name'], ENT_QUOTES, 'UTF-8' ) );
		$product->set_slug( (string) $r['slug'] );
		$product->set_description( (string) $r['description'] );
		$product->set_short_description( (string) $r['short_description'] );
		$minor = (int) ( $r['prices']['currency_minor_unit'] ?? 2 );
		$price = (int) ( $r['prices']['price'] ?? 0 ) / ( 10 ** $minor );
		$product->set_regular_price( (string) $price );
		$product->set_status( 'publish' );
		$cats = array();
		foreach ( (array) ( $r['categories'] ?? array() ) as $c ) {
			$t = \term_exists( (string) $c['slug'], 'product_cat' );
			if ( ! $t ) {
				$t = \wp_insert_term( \html_entity_decode( (string) $c['name'] ), 'product_cat', array( 'slug' => (string) $c['slug'] ) );
			}
			if ( \is_array( $t ) ) {
				$cats[] = (int) $t['term_id'];
			}
		}
		$product->set_category_ids( $cats );
		$id = $product->save();
		\update_post_meta( $id, 'r18_source_id', $key );
		$gallery = array();
		foreach ( (array) ( $r['images'] ?? array() ) as $i => $img ) {
			$att = sideload( (string) $img['src'], $id, (string) ( $img['alt'] ?? '' ) );
			if ( $att && 0 === $i ) {
				\set_post_thumbnail( $id, $att );
			} elseif ( $att ) {
				$gallery[] = $att;
			}
		}
		if ( $gallery ) {
			$product = \wc_get_product( $id );
			$product->set_gallery_image_ids( $gallery );
			$product->save();
		}
		$log[] = 'Produit : ' . $product->get_name() . ( ! empty( $r['has_options'] ) ? ' (options à reprendre via Printful)' : '' );
	}
	return array(
		'log'   => $log,
		'next'  => $page < $total ? $page + 1 : 0,
		'total' => $total,
	);
}

/**
 * Point d'entrée AJAX d'un lot.
 */
\add_action(
	'wp_ajax_rescue18_import',
	static function (): void {
		\check_ajax_referer( 'rescue18_import' );
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_send_json_error( array( 'message' => 'Action non autorisée.' ), 403 );
		}
		$step = \sanitize_key( \wp_unslash( $_POST['step'] ?? '' ) );
		$page = \max( 1, \absint( $_POST['page'] ?? 1 ) );
		if ( ! isset( import_steps()[ $step ] ) ) {
			\wp_send_json_error( array( 'message' => 'Étape inconnue.' ), 400 );
		}
		try {
			\wp_send_json_success( run_import_step( $step, $page ) );
		} catch ( \Throwable $e ) {
			\wp_send_json_error( array( 'message' => $e->getMessage() ), 500 );
		}
	}
);

/**
 * Écran Outils › Préparer Rescue 18.
 */
\add_action(
	'admin_menu',
	static function (): void {
		$hook = \add_management_page( 'Préparer Rescue 18', 'Préparer Rescue 18', 'manage_options', 'rescue18-preparer', __NAMESPACE__ . '\render_import_page' );
		\add_action(
			'admin_print_scripts-' . $hook,
			static function (): void {
				$a = asset( 'assets/js/import.js' );
				if ( null === $a ) {
					return;
				}
				\wp_enqueue_script( 'rescue18-import', $a[0], array(), $a[1], true );
				\wp_localize_script(
					'rescue18-import',
					'rescue18Import',
					array(
						'ajax'  => \admin_url( 'admin-ajax.php' ),
						'nonce' => \wp_create_nonce( 'rescue18_import' ),
					)
				);
			}
		);
	}
);

/**
 * Rendu de l'écran.
 */
function render_import_page(): void {
	echo '<div class="wrap"><h1>Préparer Rescue 18</h1>';
	echo '<p>Construit le site à partir de <strong>' . \esc_html( source_url() ) . '</strong> (lecture de son API publique). Chaque étape est relançable sans doublon. Ordre conseillé : de haut en bas.</p>';
	echo '<p><button type="button" class="button button-primary" data-r18-all>Tout lancer</button></p>';
	echo '<table class="widefat striped" style="max-width:980px"><tbody>';
	foreach ( import_steps() as $key => [ $label, $desc ] ) {
		echo '<tr><td style="width:190px"><strong>' . \esc_html( $label ) . '</strong></td><td>' . \esc_html( $desc ) . '</td><td style="width:150px"><button type="button" class="button" data-r18-step="' . \esc_attr( $key ) . '">Lancer</button></td><td style="width:160px" data-r18-status="' . \esc_attr( $key ) . '">—</td></tr>';
	}
	echo '</tbody></table><h2>Journal</h2><pre data-r18-log style="max-width:980px;max-height:420px;overflow:auto;background:#fff;border:1px solid #c3c4c7;padding:12px;white-space:pre-wrap"></pre></div>';
}
