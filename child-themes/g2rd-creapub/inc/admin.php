<?php
/**
 * Saisie simplifiée dans l'administration.
 *
 * Objectif : ajouter un contenu en un minimum de gestes, sans connaître les blocs.
 *   - Réalisation (Portfolio) : un titre, une photo à la une, une catégorie.
 *     Elle s'affiche seule sur la page de la prestation de même catégorie et
 *     sur /realisations/.
 *   - Membre (Qui sommes-nous) : un nom, une photo, une mini-bio, un ordre.
 *   - Prestation : photo à la une (grilles), texte dans les zones prévues ;
 *     les réalisations se remplissent depuis le portfolio.
 *
 * @package G2RD_CreaPub
 */

declare( strict_types = 1 );

namespace G2RD_CreaPub;

\defined( 'ABSPATH' ) || exit;

/**
 * Nom de la taxonomie des catégories de projets du portfolio (réglage du parent).
 *
 * @return string
 */
function portfolio_taxonomy(): string {
	$taxes = \get_object_taxonomies( 'portfolio' );
	foreach ( $taxes as $tax ) {
		if ( ! \in_array( $tax, array( 'post_tag', 'category' ), true ) ) {
			return $tax;
		}
	}
	return 'type-projets';
}

/**
 * Libellés parlants pour les trois CPT.
 */
\add_action(
	'init',
	static function (): void {
		// Ordre d'affichage manuel des membres (champ « Ordre » dans Attributs).
		\add_post_type_support( 'qui-sommes-nous', 'page-attributes' );
	},
	20
);

\add_filter(
	'post_type_labels_portfolio',
	static function ( $labels ) {
		$labels->name                  = 'Réalisations';
		$labels->singular_name         = 'Réalisation';
		$labels->menu_name             = 'Réalisations';
		$labels->add_new_item          = 'Ajouter une réalisation';
		$labels->add_new               = 'Ajouter une réalisation';
		$labels->edit_item             = 'Modifier la réalisation';
		$labels->all_items             = 'Toutes les réalisations';
		$labels->featured_image        = 'Photo de la réalisation';
		$labels->set_featured_image    = 'Choisir la photo';
		$labels->remove_featured_image = 'Retirer la photo';
		$labels->use_featured_image    = 'Utiliser comme photo';
		return $labels;
	}
);
\add_filter(
	'post_type_labels_qui-sommes-nous',
	static function ( $labels ) {
		$labels->name                  = 'La Team';
		$labels->singular_name         = 'Membre';
		$labels->menu_name             = 'La Team';
		$labels->add_new_item          = 'Ajouter un membre';
		$labels->add_new               = 'Ajouter un membre';
		$labels->edit_item             = 'Modifier le membre';
		$labels->all_items             = 'Tous les membres';
		$labels->featured_image        = 'Photo du membre';
		$labels->set_featured_image    = 'Choisir la photo';
		$labels->remove_featured_image = 'Retirer la photo';
		$labels->use_featured_image    = 'Utiliser comme photo';
		return $labels;
	}
);
\add_filter(
	'post_type_labels_prestations',
	static function ( $labels ) {
		$labels->featured_image     = 'Photo de la prestation (grilles et page Prestations)';
		$labels->set_featured_image = 'Choisir la photo';
		return $labels;
	}
);

/**
 * Texte d'aide dans le champ titre.
 *
 * @param string   $text Texte par défaut.
 * @param \WP_Post $post Contenu.
 * @return string
 */
\add_filter(
	'enter_title_here',
	static function ( $text, $post ) {
		switch ( $post->post_type ) {
			case 'portfolio':
				return 'Titre de la réalisation (ex. : Covering utilitaire, SARL Hardy)';
			case 'qui-sommes-nous':
				return 'Prénom du membre';
			case 'prestations':
				return 'Nom de la prestation';
		}
		return $text;
	},
	10,
	2
);

/**
 * Colonnes « Photo » et « Catégorie » / « Ordre » dans les listes.
 */
foreach ( array( 'portfolio', 'qui-sommes-nous', 'prestations' ) as $creapub_type ) {
	\add_filter(
		"manage_{$creapub_type}_posts_columns",
		static function ( array $columns ) use ( $creapub_type ): array {
			$new = array();
			foreach ( $columns as $key => $label ) {
				if ( 'title' === $key ) {
					$new['creapub_photo'] = 'Photo';
				}
				$new[ $key ] = $label;
				if ( 'title' === $key && 'portfolio' === $creapub_type ) {
					$new['creapub_cat'] = 'Prestation';
				}
				if ( 'title' === $key && 'portfolio' !== $creapub_type ) {
					$new['creapub_order'] = 'Ordre';
				}
			}
			return $new;
		}
	);
	\add_action(
		"manage_{$creapub_type}_posts_custom_column",
		static function ( string $column, int $post_id ): void {
			if ( 'creapub_photo' === $column ) {
				$thumb = \get_the_post_thumbnail( $post_id, array( 60, 60 ), array( 'style' => 'width:60px;height:60px;object-fit:cover;border-radius:6px' ) );
				echo $thumb ? $thumb : '<span style="color:#b32d2e">Photo manquante</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			if ( 'creapub_cat' === $column ) {
				$terms = \get_the_terms( $post_id, portfolio_taxonomy() );
				echo $terms && ! \is_wp_error( $terms )
					? \esc_html( \implode( ', ', \wp_list_pluck( $terms, 'name' ) ) )
					: '<span style="color:#b32d2e">À classer</span>';
			}
			if ( 'creapub_order' === $column ) {
				echo (int) \get_post_field( 'menu_order', $post_id );
			}
		},
		10,
		2
	);
}
unset( $creapub_type );

/**
 * Listes : membres et prestations triés par ordre d'affichage.
 *
 * @param \WP_Query $query Requête.
 */
\add_action(
	'pre_get_posts',
	static function ( $query ): void {
		if ( ! \is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( \in_array( $query->get( 'post_type' ), array( 'qui-sommes-nous', 'prestations' ), true ) && ! $query->get( 'orderby' ) ) {
			$query->set( 'orderby', 'menu_order' );
			$query->set( 'order', 'ASC' );
		}
	}
);

/**
 * Widget du tableau de bord : raccourcis et mode d'emploi en 3 lignes.
 */
\add_action(
	'wp_dashboard_setup',
	static function (): void {
		\wp_add_dashboard_widget(
			'creapub_raccourcis',
			'Créa Pub — ajouter du contenu',
			static function (): void {
				$links = array(
					array( \admin_url( 'post-new.php?post_type=portfolio' ), 'Ajouter une réalisation', 'Un titre, une photo à la une, une catégorie (Print, Signalétique…). Elle apparaît seule sur la bonne page prestation et sur Nos réalisations.' ),
					array( \admin_url( 'post-new.php?post_type=qui-sommes-nous' ), 'Ajouter un membre', 'Prénom, photo, mini-bio. Le champ « Ordre » règle la position sur la page La Team.' ),
					array( \admin_url( 'edit.php?post_type=prestations' ), 'Modifier une prestation', 'Textes dans les zones prévues. Les réalisations viennent du portfolio, rien à faire.' ),
					array( \admin_url( 'post-new.php' ), 'Écrire une actualité', 'Choisir une catégorie : elle relie l’article à la prestation concernée.' ),
				);
				echo '<ul style="margin:0">';
				foreach ( $links as $link ) {
					\printf(
						'<li style="margin-bottom:12px"><a class="button button-primary" href="%1$s">%2$s</a><br><span style="color:#50575e">%3$s</span></li>',
						\esc_url( $link[0] ),
						\esc_html( $link[1] ),
						\esc_html( $link[2] )
					);
				}
				echo '</ul>';
			}
		);
	}
);

/**
 * Rappel dans l'éditeur d'une réalisation : photo + catégorie suffisent.
 */
\add_action(
	'admin_notices',
	static function (): void {
		$screen = \function_exists( 'get_current_screen' ) ? \get_current_screen() : null;
		if ( ! $screen || 'portfolio' !== $screen->post_type || 'post' !== $screen->base ) {
			return;
		}
		echo '<div class="notice notice-info"><p><strong>Réalisation :</strong> renseignez le titre, la <strong>photo de la réalisation</strong> (panneau de droite) et la <strong>catégorie</strong> (Print, Signalétique…). La description est facultative.</p></div>';
	}
);
