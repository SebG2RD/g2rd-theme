<?php
/**
 * Blocs dynamiques du thème enfant : enregistrement et fonctions communes.
 *
 * Rendu PHP, aucun build. Côté éditeur, assets/js/editor-blocks.js les
 * déclare avec un aperçu serveur et des réglages générés depuis
 * block_definitions().
 *
 * Rendus détaillés :
 *   inc/blocks-docs.php   bibliothèque, nouveautés, veille juridique, fiches
 *   inc/blocks-media.php  spécialités, ticker, épisodes, quiz, produits, article
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18;

\defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/blocks-docs.php';
require_once __DIR__ . '/blocks-media.php';
require_once __DIR__ . '/outils.php';
require_once __DIR__ . '/annuaire/blocs.php';
require_once __DIR__ . '/partenaires.php';
require_once __DIR__ . '/clics.php';
require_once __DIR__ . '/quiz.php';

/**
 * Définition des blocs : titre, icône dashicon, attributs, contexte, rendu.
 *
 * Attribut : [ type, défaut, libellé, options ] ; options = liste => select.
 *
 * @return array<string,array<string,mixed>>
 */
function block_definitions(): array {
	$nombre = static fn( int $d ): array => array( 'number', $d, \__( 'Nombre d\'éléments', 'g2rd-rescue18-child' ) );
	return array(
		'bibliotheque'      => array(
			'title'  => \__( 'Bibliothèque documentaire', 'g2rd-rescue18-child' ),
			'icon'   => 'book-alt',
			'attrs'  => array(
				'variante' => array(
					'string',
					'accueil',
					\__( 'Variante', 'g2rd-rescue18-child' ),
					array(
						'accueil' => \__( 'Accueil (aperçu filtrable)', 'g2rd-rescue18-child' ),
						'page'    => \__( 'Page complète (recherche et filtres)', 'g2rd-rescue18-child' ),
					),
				),
				'type'     => array( 'string', '', \__( 'Type présélectionné (slug, ou « juridique »)', 'g2rd-rescue18-child' ) ),
				'nombre'   => $nombre( 8 ),
			),
			'render' => 'render_bibliotheque',
		),
		'nouveautes'        => array(
			'title'  => \__( 'Nouveautés documentaires', 'g2rd-rescue18-child' ),
			'icon'   => 'update',
			'attrs'  => array( 'nombre' => $nombre( 3 ) ),
			'render' => 'render_nouveautes',
		),
		'veille-juridique'  => array(
			'title'  => \__( 'Veille juridique', 'g2rd-rescue18-child' ),
			'icon'   => 'shield',
			'attrs'  => array(
				'variante' => array(
					'string',
					'compact',
					\__( 'Variante', 'g2rd-rescue18-child' ),
					array(
						'compact' => \__( 'Liste courte', 'g2rd-rescue18-child' ),
						'page'    => \__( 'Page complète (filtres)', 'g2rd-rescue18-child' ),
					),
				),
				'nombre'   => $nombre( 3 ),
			),
			'render' => 'render_veille',
		),
		'veille-statut'     => array(
			'title'  => \__( 'Statut de la veille (dernière synchronisation)', 'g2rd-rescue18-child' ),
			'icon'   => 'clock',
			'attrs'  => array(),
			'render' => 'render_veille_statut',
		),
		'fiche-etiquettes'  => array(
			'title'   => \__( 'Fiche · étiquettes et statut', 'g2rd-rescue18-child' ),
			'icon'    => 'tag',
			'attrs'   => array(),
			'context' => array( 'postId', 'postType' ),
			'render'  => 'render_fiche_etiquettes',
		),
		'fiche-actions'     => array(
			'title'   => \__( 'Fiche · boutons source et PDF', 'g2rd-rescue18-child' ),
			'icon'    => 'external',
			'attrs'   => array(),
			'context' => array( 'postId', 'postType' ),
			'render'  => 'render_fiche_actions',
		),
		'fiche-references'  => array(
			'title'   => \__( 'Fiche · références officielles', 'g2rd-rescue18-child' ),
			'icon'    => 'list-view',
			'attrs'   => array(),
			'context' => array( 'postId', 'postType' ),
			'render'  => 'render_fiche_references',
		),
		'etat-juridique'    => array(
			'title'   => \__( 'Fiche · état juridique', 'g2rd-rescue18-child' ),
			'icon'    => 'info',
			'attrs'   => array(),
			'context' => array( 'postId', 'postType' ),
			'render'  => 'render_etat_juridique',
		),
		'historique'        => array(
			'title'   => \__( 'Fiche · historique des versions', 'g2rd-rescue18-child' ),
			'icon'    => 'backup',
			'attrs'   => array(),
			'context' => array( 'postId', 'postType' ),
			'render'  => 'render_historique',
		),
		'source-officielle' => array(
			'title'   => \__( 'Fiche · encadré source officielle', 'g2rd-rescue18-child' ),
			'icon'    => 'admin-links',
			'attrs'   => array(),
			'context' => array( 'postId', 'postType' ),
			'render'  => 'render_source_officielle',
		),
		'lies'              => array(
			'title'   => \__( 'Contenus liés (même spécialité)', 'g2rd-rescue18-child' ),
			'icon'    => 'networking',
			'attrs'   => array(
				'type'     => array(
					'string',
					'r18_document',
					\__( 'Type de contenu', 'g2rd-rescue18-child' ),
					array(
						'r18_document' => \__( 'Documents', 'g2rd-rescue18-child' ),
						'r18_texte'    => \__( 'Textes juridiques', 'g2rd-rescue18-child' ),
						'post'         => \__( 'Articles', 'g2rd-rescue18-child' ),
						'r18_episode'  => \__( 'Épisodes', 'g2rd-rescue18-child' ),
						'r18_quiz'     => \__( 'Quiz', 'g2rd-rescue18-child' ),
					),
				),
				'titre'    => array( 'string', '', \__( 'Titre de l\'encadré', 'g2rd-rescue18-child' ) ),
				'variante' => array(
					'string',
					'liste',
					\__( 'Variante', 'g2rd-rescue18-child' ),
					array(
						'liste'  => \__( 'Liste en encadré', 'g2rd-rescue18-child' ),
						'cartes' => \__( 'Cartes', 'g2rd-rescue18-child' ),
					),
				),
				'nombre'   => $nombre( 3 ),
			),
			'context' => array( 'postId', 'postType' ),
			'render'  => 'render_lies',
		),
		'specialites'       => array(
			'title'  => \__( 'Spécialités', 'g2rd-rescue18-child' ),
			'icon'   => 'grid-view',
			'attrs'  => array(
				'variante' => array(
					'string',
					'grille',
					\__( 'Variante', 'g2rd-rescue18-child' ),
					array(
						'grille' => \__( 'Grille de tuiles', 'g2rd-rescue18-child' ),
						'puces'  => \__( 'Puces', 'g2rd-rescue18-child' ),
					),
				),
			),
			'render' => 'render_specialites',
		),
		'ticker'            => array(
			'title'  => \__( 'Bandeau des dernières publications', 'g2rd-rescue18-child' ),
			'icon'   => 'megaphone',
			'attrs'  => array( 'nombre' => $nombre( 6 ) ),
			'render' => 'render_ticker',
		),
		'episodes'          => array(
			'title'  => \__( 'Épisodes de podcast', 'g2rd-rescue18-child' ),
			'icon'   => 'microphone',
			'attrs'  => array(
				'variante' => array(
					'string',
					'accueil',
					\__( 'Variante', 'g2rd-rescue18-child' ),
					array(
						'accueil' => \__( 'Liste courte', 'g2rd-rescue18-child' ),
						'page'    => \__( 'Page complète (lecteur, filtres)', 'g2rd-rescue18-child' ),
					),
				),
				'nombre'   => $nombre( 4 ),
			),
			'render' => 'render_episodes',
		),
		'quiz'              => array(
			'title'   => \__( 'Quiz interactif', 'g2rd-rescue18-child' ),
			'icon'    => 'welcome-learn-more',
			'attrs'   => array( 'quizId' => array( 'number', 0, \__( 'ID du quiz (vide = quiz courant)', 'g2rd-rescue18-child' ) ) ),
			'context' => array( 'postId', 'postType' ),
			'render'  => 'render_quiz',
		),
		'produits'          => array(
			'title'   => \__( 'Produits de la boutique', 'g2rd-rescue18-child' ),
			'icon'    => 'store',
			'attrs'   => array(
				'variante' => array(
					'string',
					'grille',
					\__( 'Variante', 'g2rd-rescue18-child' ),
					array(
						'grille' => \__( 'Grille des derniers produits', 'g2rd-rescue18-child' ),
						'carte'  => \__( 'Carte du produit courant (boucle)', 'g2rd-rescue18-child' ),
					),
				),
				'nombre'   => $nombre( 4 ),
			),
			'context' => array( 'postId', 'postType' ),
			'render'  => 'render_produits',
		),
		'fil-ariane'        => array(
			'title'  => \__( 'Fil d\'Ariane', 'g2rd-rescue18-child' ),
			'icon'   => 'arrow-right-alt2',
			'attrs'  => array(),
			'render' => 'render_fil_ariane',
		),
		'sommaire'          => array(
			'title'  => \__( 'Sommaire de l\'article', 'g2rd-rescue18-child' ),
			'icon'   => 'editor-ol',
			'attrs'  => array(),
			'render' => 'render_sommaire',
		),
		'outils-article'    => array(
			'title'   => \__( 'Outils de l\'article (PDF, partage)', 'g2rd-rescue18-child' ),
			'icon'    => 'share',
			'attrs'   => array(),
			'context' => array( 'postId', 'postType' ),
			'render'  => 'render_outils_article',
		),
		'newsletter'        => array(
			'title'  => \__( 'Formulaire newsletter (MailPoet)', 'g2rd-rescue18-child' ),
			'icon'   => 'email',
			'attrs'  => array( 'formulaire' => array( 'number', 1, \__( 'ID du formulaire MailPoet', 'g2rd-rescue18-child' ) ) ),
			'render' => 'render_newsletter',
		),
		'outils'            => array(
			'title'  => \__( 'Boîte à outils (catalogue)', 'g2rd-rescue18-child' ),
			'icon'   => 'admin-tools',
			'attrs'  => array(),
			'render' => 'render_outils',
		),
		'outil'             => array(
			'title'  => \__( 'Outil opérationnel', 'g2rd-rescue18-child' ),
			'icon'   => 'calculator',
			'attrs'  => array(
				'outil' => array(
					'string',
					'dimensionnement-dps',
					\__( 'Outil', 'g2rd-rescue18-child' ),
					\array_map( static fn( array $o ): string => $o['title'], outils_defaut() ),
				),
			),
			'render' => 'render_outil',
		),
		'outil-entete'      => array(
			'title'  => \__( 'En-tête d\'outil', 'g2rd-rescue18-child' ),
			'icon'   => 'heading',
			'attrs'  => array(
				'outil' => array(
					'string',
					'dimensionnement-dps',
					\__( 'Outil', 'g2rd-rescue18-child' ),
					\array_map( static fn( array $o ): string => $o['title'], outils_defaut() ),
				),
			),
			'render' => 'render_outil_entete',
		),
		'panier'            => array(
			'title'  => \__( 'Icône panier', 'g2rd-rescue18-child' ),
			'icon'   => 'cart',
			'attrs'  => array(),
			'render' => 'render_panier',
		),
	) + annuaire_block_definitions() + partenaires_block_definitions() + quiz_block_definitions() + Alertes\block_definitions() + Concours\block_definitions() + Lexique\block_definitions() + Fiches\block_definitions() + Annales\block_definitions();
}

/**
 * Catégorie « Rescue 18 » dans l'inserteur.
 */
\add_filter(
	'block_categories_all',
	static function ( array $cats ): array {
		\array_unshift(
			$cats,
			array(
				'slug'  => 'rescue18',
				'title' => \__( 'Rescue 18', 'g2rd-rescue18-child' ),
			)
		);
		return $cats;
	}
);

/**
 * Enregistrement des blocs et du script d'éditeur.
 */
\add_action(
	'init',
	static function (): void {
		$editor = array();
		foreach ( block_definitions() as $slug => $def ) {
			$attributes = array();
			foreach ( $def['attrs'] as $key => $a ) {
				$attributes[ $key ] = array(
					'type'    => $a[0],
					'default' => $a[1],
				);
			}
			$render = __NAMESPACE__ . '\\' . $def['render'];
			\register_block_type(
				'rescue18/' . $slug,
				array(
					'api_version'     => 3,
					'title'           => $def['title'],
					'category'        => 'rescue18',
					'icon'            => $def['icon'],
					'attributes'      => $attributes,
					'uses_context'    => $def['context'] ?? array(),
					'supports'        => array(
						'html'      => false,
						'align'     => array( 'wide', 'full' ),
						'className' => true,
					),
					'editor_script'   => 'rescue18-editor-blocks',
					'render_callback' => static fn( array $attrs, string $content, \WP_Block $block ): string => (string) \call_user_func( $render, $attrs, $block ),
				)
			);
			$editor[ $slug ] = array(
				'title' => $def['title'],
				'icon'  => $def['icon'],
				'attrs' => \array_map(
					static fn( array $a ): array => array(
						'type'    => $a[0],
						'default' => $a[1],
						'label'   => $a[2],
						'options' => $a[3] ?? null,
					),
					$def['attrs']
				),
			);
		}

		$a = asset( 'assets/js/editor-blocks.js' );
		if ( null !== $a ) {
			\wp_register_script( 'rescue18-editor-blocks', $a[0], array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n' ), $a[1], true );
			\wp_add_inline_script( 'rescue18-editor-blocks', 'window.rescue18Blocks = ' . \wp_json_encode( $editor ) . ';', 'before' );
		}
	},
	20
);

/**
 * Enveloppe un rendu dans les attributs du bloc (classes, alignement).
 *
 * @param string               $class_name Classe du bloc.
 * @param string               $inner      Contenu.
 * @param string               $tag        Balise.
 * @param array<string,string> $extra      Attributs supplémentaires.
 * @return string
 */
function wrap( string $class_name, string $inner, string $tag = 'div', array $extra = array() ): string {
	if ( '' === $inner ) {
		return '';
	}
	$attrs = \get_block_wrapper_attributes( \array_merge( array( 'class' => $class_name ), $extra ) );
	return \sprintf( '<%1$s %2$s>%3$s</%1$s>', $tag, $attrs, $inner );
}

/**
 * ID du contenu courant : contexte du bloc, sinon requête principale.
 *
 * @param \WP_Block|null $block Bloc.
 * @return int
 */
function current_id( ?\WP_Block $block ): int {
	$id = (int) ( $block->context['postId'] ?? 0 );
	if ( $id <= 0 && \defined( 'REST_REQUEST' ) && \REST_REQUEST && isset( $_GET['post_id'] ) && \current_user_can( 'edit_posts' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- aperçu éditeur en lecture seule.
		$id = \absint( $_GET['post_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	return $id > 0 ? $id : (int) \get_queried_object_id();
}

/**
 * URL d'une page par son chemin, avec repli sur l'URL attendue.
 * Les rubriques historiques sont des pages : on ne casse aucune URL.
 *
 * @param string $path Chemin de la page.
 * @return string
 */
function page_url( string $path ): string {
	$page = \get_page_by_path( $path );
	return $page instanceof \WP_Post ? (string) \get_permalink( $page ) : \home_url( '/' . \trim( $path, '/' ) . '/' );
}

/**
 * Chemins des rubriques historiques à conserver (consignes, § 6).
 *
 * @return array<string,string>
 */
function rubriques(): array {
	return array(
		'documentation' => 'documentations-referentiels',
		'juridique'     => 'documentations-referentiels/juridique',
		'gdo-gto'       => 'guides-doctrines-operationnelles-gdo-guides-techniques-operationnelles-gto',
		'retex'         => 'retex',
		'articles'      => 'articles',
		'formation'     => 'formation',
		'podcasts'      => 'ssuap-le-podcast',
		'boutique'      => 'boutique',
	);
}

/**
 * URL d'une rubrique.
 *
 * @param string $key Clé de rubriques().
 * @return string
 */
function rubrique_url( string $key ): string {
	return page_url( rubriques()[ $key ] ?? $key );
}

/**
 * Pastille de statut.
 *
 * @param string $label  Libellé.
 * @param string $statut Clé de statut (couleur).
 * @return string
 */
function pill( string $label, string $statut ): string {
	$map = array(
		'nouveau'       => '',
		'mis-a-jour'    => ' r18-pill--ok',
		'en-ligne'      => ' r18-pill--ok',
		'en-vigueur'    => ' r18-pill--ok',
		'en-validation' => ' r18-pill--wait',
		'modifie'       => ' r18-pill--wait',
		'abroge'        => ' r18-pill--grey',
		'a-verifier'    => ' r18-pill--grey',
	);
	return '' === $label ? '' : '<span class="r18-pill' . ( $map[ $statut ] ?? ' r18-pill--grey' ) . '">' . \esc_html( $label ) . '</span>';
}

/**
 * Premier terme d'une taxonomie.
 *
 * @param int    $post_id  ID du contenu.
 * @param string $taxonomy Taxonomie.
 * @return \WP_Term|null
 */
function first_term( int $post_id, string $taxonomy ): ?\WP_Term {
	$terms = \get_the_terms( $post_id, $taxonomy );
	return \is_array( $terms ) && isset( $terms[0] ) ? $terms[0] : null;
}

/**
 * Lien fléché.
 *
 * @param string $url   URL.
 * @param string $label Libellé.
 * @param string $class Classe.
 * @return string
 */
function arrow_link( string $url, string $label, string $class_name = 'r18-link' ): string {
	return '<a class="' . \esc_attr( $class_name ) . '" href="' . \esc_url( $url ) . '">' . \esc_html( $label ) . ' ' . icone( 'arrow' ) . '</a>';
}
