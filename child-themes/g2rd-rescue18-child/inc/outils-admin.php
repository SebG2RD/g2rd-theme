<?php
/**
 * Back-office de la boîte à outils : menu « Boîte à outils ».
 *
 * - Liste des outils : état, ordre, mission, page publique, lien de réglage.
 * - Réglages communs : avertissement, libellés des missions.
 * - Fiche d'un outil : textes, référencement (aperçu Google), champs du
 *   formulaire (libellés, aides, unités, valeurs par défaut, bornes),
 *   méthode, sources, questions fréquentes.
 *
 * Un champ laissé vide reprend la valeur du thème, affichée en indication.
 * Les coefficients réglementaires (grille DPS) et les types de message
 * restent figés : seuls leurs libellés se modifient.
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18;

\defined( 'ABSPATH' ) || exit;

const OUTILS_PAGE = 'rescue18-outils';

/**
 * Menu.
 */
\add_action(
	'admin_menu',
	static function (): void {
		\add_menu_page( 'Boîte à outils', 'Boîte à outils', 'manage_options', OUTILS_PAGE, __NAMESPACE__ . '\render_outils_admin', 'dashicons-calculator', 26 );
	}
);

/**
 * Lien vers l'écran.
 *
 * @param array<string,string> $args Paramètres.
 * @return string
 */
function outils_admin_url( array $args = array() ): string {
	return \add_query_arg( \array_merge( array( 'page' => OUTILS_PAGE ), $args ), \admin_url( 'admin.php' ) );
}

/**
 * Icônes proposées.
 *
 * @return string[]
 */
function outils_icones(): array {
	return array( 'users', 'clock', 'truck', 'radio', 'flame', 'tree', 'med', 'car', 'heart', 'shield', 'alert', 'compass', 'layers', 'box', 'book', 'file', 'tool', 'grid', 'search', 'info' );
}

/**
 * Liste « gauche | droite » → texte, pour les zones de saisie.
 *
 * @param array<int,array{0:string,1:string}> $rows Lignes.
 * @return string
 */
function outils_lines_to_text( array $rows ): string {
	return \implode( "\n", \array_map( static fn( array $r ): string => $r[0] . ' | ' . $r[1], $rows ) );
}

/**
 * Texte → liste « gauche | droite ».
 *
 * @param string $text Saisie.
 * @param bool   $url  La partie droite est une URL.
 * @return array<int,array{0:string,1:string}>
 */
function outils_text_to_lines( string $text, bool $url = false ): array {
	$out = array();
	foreach ( \preg_split( '/\R/u', $text ) as $line ) {
		$parts = \array_map( 'trim', \explode( '|', $line, 2 ) );
		if ( \count( $parts ) < 2 || '' === $parts[0] || '' === $parts[1] ) {
			continue;
		}
		$out[] = array( \sanitize_text_field( $parts[0] ), $url ? \esc_url_raw( $parts[1] ) : \sanitize_textarea_field( $parts[1] ) );
	}
	return $out;
}

/**
 * Message d'état après une action.
 */
function outils_admin_notice(): void {
	$msg = \sanitize_key( \wp_unslash( $_GET['r18msg'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- affichage seul.
	$map = array(
		'enregistre' => array( 'success', 'Réglages enregistrés. La page publique est à jour.' ),
		'retabli'    => array( 'success', 'Valeurs du thème rétablies pour cet outil.' ),
		'communs'    => array( 'success', 'Réglages communs enregistrés.' ),
		'pages'      => array( 'success', 'Pages des outils créées ou complétées.' ),
		'bornes'     => array( 'warning', 'Réglages enregistrés. Une valeur par défaut sort de ses bornes : le visiteur verra un message d\'erreur. Vérifie les champs signalés.' ),
	);
	if ( isset( $map[ $msg ] ) ) {
		\printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', \esc_attr( $map[ $msg ][0] ), \esc_html( $map[ $msg ][1] ) );
	}
}

/**
 * Page de l'outil (objet WP_Post) ou null.
 *
 * @param string $slug Outil.
 * @return \WP_Post|null
 */
function outil_page( string $slug ): ?\WP_Post {
	$page = \get_page_by_path( 'outils/' . $slug, \OBJECT, 'page' );
	return $page instanceof \WP_Post ? $page : null;
}

/**
 * Rendu de l'écran.
 */
function render_outils_admin(): void {
	if ( ! \current_user_can( 'manage_options' ) ) {
		return;
	}
	$slug = \sanitize_key( \wp_unslash( $_GET['outil'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation.
	echo '<div class="wrap r18-adm">';
	if ( '' !== $slug && isset( outils_defaut()[ $slug ] ) ) {
		render_outil_admin( $slug );
	} else {
		render_outils_liste();
	}
	echo '</div>';
}

/**
 * Liste des outils et réglages communs.
 */
function render_outils_liste(): void {
	echo '<h1 class="wp-heading-inline">Boîte à outils</h1>';
	$hub = \get_page_by_path( 'outils' );
	if ( $hub instanceof \WP_Post ) {
		echo ' <a class="page-title-action" href="' . \esc_url( (string) \get_permalink( $hub ) ) . '" target="_blank" rel="noopener">Voir le catalogue</a>';
		echo ' <a class="page-title-action" href="' . \esc_url( (string) \get_edit_post_link( $hub->ID ) ) . '">Modifier l\'en-tête du catalogue</a>';
	}
	echo '<hr class="wp-header-end">';
	outils_admin_notice();
	echo '<p class="r18-adm__intro">Règle les textes, les valeurs par défaut et l\'ordre des outils. Un champ vide reprend la valeur prévue par le thème. Les formules de calcul restent dans le thème.</p>';

	$missions = outil_missions();
	$missing  = 0;
	echo '<table class="widefat striped r18-adm__table"><thead><tr><th scope="col">Ordre</th><th scope="col">Outil</th><th scope="col">Mission</th><th scope="col">État</th><th scope="col">Page publique</th></tr></thead><tbody>';
	foreach ( outils() as $slug => $o ) {
		$page = outil_page( $slug );
		$missing += $page ? 0 : 1;
		$edit = outils_admin_url( array( 'outil' => $slug ) );
		echo '<tr><td>' . (int) $o['ordre'] . '</td>'
			. '<td><strong><a class="row-title" href="' . \esc_url( $edit ) . '">' . \esc_html( $o['title'] ) . '</a></strong>'
			. '<div class="row-actions"><span><a href="' . \esc_url( $edit ) . '">Régler</a></span></div></td>'
			. '<td>' . \esc_html( $missions[ $o['mission'] ] ?? '' ) . '</td>'
			. '<td>' . ( $o['actif'] ? '<span class="r18-adm__on">Visible</span>' : '<span class="r18-adm__off">Masqué</span>' ) . '</td>'
			. '<td>' . ( $page ? '<a href="' . \esc_url( (string) \get_permalink( $page ) ) . '" target="_blank" rel="noopener">' . \esc_html( '/outils/' . $slug . '/' ) . '</a>' : '<em>Page absente</em>' ) . '</td></tr>';
	}
	echo '</tbody></table>';

	if ( $missing || ! $hub instanceof \WP_Post ) {
		echo '<form method="post" action="' . \esc_url( \admin_url( 'admin-post.php' ) ) . '" class="r18-adm__pages">';
		\wp_nonce_field( 'rescue18_outils_pages' );
		echo '<input type="hidden" name="action" value="rescue18_outils_pages">';
		echo '<p>' . ( $missing ? \esc_html( $missing . ' page(s) d\'outil manquante(s).' ) . ' ' : '' ) . '<button class="button button-primary">Créer les pages manquantes</button></p></form>';
	}

	$g = (array) ( outils_reglages()['_global'] ?? array() );
	echo '<h2>Réglages communs</h2>';
	echo '<form method="post" action="' . \esc_url( \admin_url( 'admin-post.php' ) ) . '">';
	\wp_nonce_field( 'rescue18_outils_communs' );
	echo '<input type="hidden" name="action" value="rescue18_outils_communs">';
	echo '<table class="form-table" role="presentation"><tbody>';
	echo '<tr><th scope="row"><label for="r18-avert">Avertissement sous chaque outil</label></th><td><textarea id="r18-avert" name="avertissement" rows="3" class="large-text" placeholder="' . \esc_attr( outil_avertissement_defaut() ) . '">' . \esc_textarea( (string) ( $g['avertissement'] ?? '' ) ) . '</textarea><p class="description">Vide : texte du thème.</p></td></tr>';
	foreach ( outil_missions_defaut() as $key => $label ) {
		echo '<tr><th scope="row"><label for="r18-mission-' . \esc_attr( $key ) . '">Filtre « ' . \esc_html( $label ) . ' »</label></th><td><input type="text" class="regular-text" id="r18-mission-' . \esc_attr( $key ) . '" name="missions[' . \esc_attr( $key ) . ']" value="' . \esc_attr( (string) ( $g['missions'][ $key ] ?? '' ) ) . '" placeholder="' . \esc_attr( $label ) . '"></td></tr>';
	}
	echo '</tbody></table>';
	\submit_button( 'Enregistrer les réglages communs' );
	echo '</form>';
}

/**
 * Ligne de formulaire.
 *
 * @param string $id    Identifiant.
 * @param string $label Libellé.
 * @param string $input Champ HTML.
 * @param string $desc  Description.
 * @return string
 */
function outils_row( string $id, string $label, string $input, string $desc = '' ): string {
	return '<tr><th scope="row"><label for="' . \esc_attr( $id ) . '">' . \esc_html( $label ) . '</label></th><td>' . $input . ( '' !== $desc ? '<p class="description" id="' . \esc_attr( $id ) . '-desc">' . \esc_html( $desc ) . '</p>' : '' ) . '</td></tr>';
}

/**
 * Fiche de réglage d'un outil.
 *
 * @param string $slug Outil.
 */
function render_outil_admin( string $slug ): void {
	$def = outils_defaut()[ $slug ];
	$o   = outils()[ $slug ];
	$r   = (array) ( outils_reglages()[ $slug ] ?? array() );
	$v   = static fn( string $k ): string => (string) ( $r[ $k ] ?? '' );
	$page = outil_page( $slug );

	echo '<p><a href="' . \esc_url( outils_admin_url() ) . '">← Tous les outils</a></p>';
	echo '<h1 class="wp-heading-inline">' . \esc_html( $o['title'] ) . '</h1>';
	if ( $page ) {
		echo ' <a class="page-title-action" href="' . \esc_url( (string) \get_permalink( $page ) ) . '" target="_blank" rel="noopener">Voir la page</a>';
	}
	echo '<hr class="wp-header-end">';
	outils_admin_notice();

	echo '<form method="post" action="' . \esc_url( \admin_url( 'admin-post.php' ) ) . '" class="r18-adm__form">';
	\wp_nonce_field( 'rescue18_outil_' . $slug );
	echo '<input type="hidden" name="action" value="rescue18_outil_save"><input type="hidden" name="outil" value="' . \esc_attr( $slug ) . '">';

	// Navigation interne.
	echo '<nav class="r18-adm__toc" aria-label="Sections"><a href="#r18-s-aff">Affichage</a><a href="#r18-s-seo">Référencement</a><a href="#r18-s-champs">Champs</a><a href="#r18-s-doc">Méthode et sources</a><a href="#r18-s-faq">Questions fréquentes</a></nav>';

	// Affichage.
	$missions = '';
	foreach ( outil_missions() as $k => $l ) {
		$missions .= '<option value="' . \esc_attr( $k ) . '"' . \selected( $o['mission'], $k, false ) . '>' . \esc_html( $l ) . '</option>';
	}
	$icons = '';
	foreach ( outils_icones() as $ic ) {
		$icons .= '<option value="' . \esc_attr( $ic ) . '"' . \selected( $o['icon'], $ic, false ) . '>' . \esc_html( $ic ) . '</option>';
	}
	echo '<h2 id="r18-s-aff">Affichage</h2><table class="form-table" role="presentation"><tbody>';
	echo outils_row( 'r18-actif', 'Visible sur le site', '<label><input type="checkbox" id="r18-actif" name="actif" value="1"' . \checked( $o['actif'], true, false ) . '> Afficher l\'outil dans le catalogue et publier sa page</label>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construit et échappé dans outils_row.
	echo outils_row( 'r18-ordre', 'Ordre dans le catalogue', '<input type="number" id="r18-ordre" name="ordre" class="small-text" step="1" value="' . \esc_attr( $v( 'ordre' ) ) . '" placeholder="' . (int) $o['ordre'] . '">', 'Du plus petit au plus grand.' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo outils_row( 'r18-mission', 'Mission (filtre)', '<select id="r18-mission" name="mission">' . $missions . '</select>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo outils_row( 'r18-icon', 'Icône', '<select id="r18-icon" name="icon" data-r18-icon>' . $icons . '</select> <span class="r18-adm__ico r18-i--' . \esc_attr( $o['icon'] ) . '" aria-hidden="true" data-r18-icon-preview></span>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	foreach ( array(
		'title' => array( 'Titre de la page (H1)', 'text', '' ),
		'short' => array( 'Nom court', 'text', 'Liens « Autres outils ».' ),
		'lead'  => array( 'Chapeau', 'textarea', 'Sous le titre et dans le catalogue.' ),
		'gives' => array( 'Résultat annoncé', 'text', 'Colonne « Résultat » du catalogue.' ),
		'ref'   => array( 'Référence', 'text', 'Surtitre et colonne « Référence ».' ),
	) as $k => [ $label, $type, $desc ] ) {
		$id    = 'r18-' . $k;
		$input = 'textarea' === $type
			? '<textarea id="' . $id . '" name="' . $k . '" rows="3" class="large-text" placeholder="' . \esc_attr( $def[ $k ] ) . '">' . \esc_textarea( $v( $k ) ) . '</textarea>'
			: '<input type="text" id="' . $id . '" name="' . $k . '" class="large-text" value="' . \esc_attr( $v( $k ) ) . '" placeholder="' . \esc_attr( $def[ $k ] ) . '">';
		echo outils_row( $id, $label, $input, $desc ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	echo '</tbody></table>';

	// Référencement.
	echo '<h2 id="r18-s-seo">Référencement</h2><table class="form-table" role="presentation"><tbody>';
	echo outils_row( 'r18-seo_title', 'Titre pour Google', '<input type="text" id="r18-seo_title" name="seo_title" class="large-text" value="' . \esc_attr( $v( 'seo_title' ) ) . '" placeholder="' . \esc_attr( $def['seo_title'] ) . '" data-r18-count="60" aria-describedby="r18-seo_title-desc">', 'Visé : 60 caractères au plus. « | Rescue 18 » s\'ajoute à la fin.' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo outils_row( 'r18-description', 'Description pour Google', '<textarea id="r18-description" name="description" rows="3" class="large-text" placeholder="' . \esc_attr( $def['description'] ) . '" data-r18-count="155" aria-describedby="r18-description-desc">' . \esc_textarea( $v( 'description' ) ) . '</textarea>', 'Visé : 120 à 155 caractères.' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	$url = $page ? (string) \get_permalink( $page ) : \home_url( '/outils/' . $slug . '/' );
	echo '<tr><th scope="row">Aperçu</th><td><div class="r18-adm__serp" data-r18-serp><span class="r18-adm__serp-url">' . \esc_html( $url ) . '</span><span class="r18-adm__serp-t" data-serp-title>' . \esc_html( $o['seo_title'] . ' | Rescue 18' ) . '</span><span class="r18-adm__serp-d" data-serp-desc>' . \esc_html( $o['description'] ) . '</span></div><p class="description">Simulation du résultat Google. SEOPress prend le relais dès son activation, sauf pour les données structurées de l\'outil.</p></td></tr>';
	echo '</tbody></table>';

	// Champs.
	echo '<h2 id="r18-s-champs">Champs du formulaire</h2><p>Libellé, aide et valeur par défaut de chaque champ. Les champs de rédaction (messages) gardent leur ordre réglementaire.</p>';
	echo '<div class="r18-adm__fields">';
	foreach ( $def['fields'] as $f ) {
		$name = $f['name'];
		$cur  = null;
		foreach ( $o['fields'] as $of ) {
			if ( $of['name'] === $name ) {
				$cur = $of;
			}
		}
		$rf   = (array) ( $r['fields'][ $name ] ?? array() );
		$base = 'fields[' . $name . ']';
		$id   = 'r18-f-' . $name;
		$fv   = static fn( string $k ): string => (string) ( $rf[ $k ] ?? '' );
		echo '<fieldset class="r18-adm__field"><legend>' . \esc_html( $cur['label'] ) . ' <code>' . \esc_html( $name ) . '</code></legend><div class="r18-adm__grid">';
		echo '<p><label for="' . $id . '-label">Libellé</label><input type="text" id="' . $id . '-label" name="' . $base . '[label]" value="' . \esc_attr( $fv( 'label' ) ) . '" placeholder="' . \esc_attr( $f['label'] ) . '"></p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $id et $base viennent du registre.
		echo '<p class="is-wide"><label for="' . $id . '-help">Aide</label><input type="text" id="' . $id . '-help" name="' . $base . '[help]" value="' . \esc_attr( $fv( 'help' ) ) . '" placeholder="' . \esc_attr( (string) ( $f['help'] ?? '' ) ) . '"></p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		if ( 'number' === $f['type'] ) {
			foreach ( array(
				'default' => 'Par défaut',
				'min'     => 'Minimum',
				'max'     => 'Maximum',
				'step'    => 'Pas',
			) as $k => $l ) {
				echo '<p><label for="' . $id . '-' . $k . '">' . \esc_html( $l ) . '</label><input type="number" step="any" id="' . $id . '-' . $k . '" name="' . $base . '[' . $k . ']" value="' . \esc_attr( $fv( $k ) ) . '" placeholder="' . \esc_attr( isset( $f[ $k ] ) ? (string) $f[ $k ] : '' ) . '"></p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			echo '<p><label for="' . $id . '-unit">Unité</label><input type="text" id="' . $id . '-unit" name="' . $base . '[unit]" value="' . \esc_attr( $fv( 'unit' ) ) . '" placeholder="' . \esc_attr( (string) ( $f['unit'] ?? '' ) ) . '"></p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} elseif ( 'select' === $f['type'] ) {
			$opts = '';
			foreach ( $cur['options'] as $val => $l ) {
				$opts .= '<option value="' . \esc_attr( (string) $val ) . '"' . \selected( (string) $cur['default'], (string) $val, false ) . '>' . \esc_html( $l ) . '</option>';
			}
			echo '<p><label for="' . $id . '-default">Choix par défaut</label><select id="' . $id . '-default" name="' . $base . '[default]">' . $opts . '</select></p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			if ( ! empty( $f['lock'] ) ) {
				echo '<div class="is-wide r18-adm__locked"><p class="description">Valeurs réglementaires figées. Seuls les libellés se modifient.</p>';
				foreach ( $f['options'] as $val => $l ) {
					echo '<p><label for="' . $id . '-o-' . \esc_attr( \sanitize_key( (string) $val ) ) . '"><code>' . \esc_html( (string) $val ) . '</code></label><input type="text" id="' . $id . '-o-' . \esc_attr( \sanitize_key( (string) $val ) ) . '" name="' . $base . '[options][' . \esc_attr( (string) $val ) . ']" value="' . \esc_attr( (string) ( $rf['options'][ $val ] ?? '' ) ) . '" placeholder="' . \esc_attr( $l ) . '"></p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				echo '</div>';
			} else {
				$text = isset( $rf['options'] ) && \is_array( $rf['options'] ) ? outils_lines_to_text( \array_map( null, \array_map( 'strval', \array_keys( $rf['options'] ) ), \array_values( $rf['options'] ) ) ) : '';
				$ph   = outils_lines_to_text( \array_map( null, \array_map( 'strval', \array_keys( $f['options'] ) ), \array_values( $f['options'] ) ) );
				echo '<p class="is-wide"><label for="' . $id . '-options">Choix proposés, un par ligne : <code>valeur | libellé</code></label><textarea id="' . $id . '-options" name="' . $base . '[options]" rows="4" placeholder="' . \esc_attr( $ph ) . '">' . \esc_textarea( $text ) . '</textarea><span class="description">La valeur entre dans le calcul (point décimal). Vide : choix du thème.</span></p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		} elseif ( 'textarea' !== $f['type'] && 'time' !== $f['type'] ) {
			echo '<p><label for="' . $id . '-default">Par défaut</label><input type="text" id="' . $id . '-default" name="' . $base . '[default]" value="' . \esc_attr( $fv( 'default' ) ) . '" placeholder="' . \esc_attr( (string) $f['default'] ) . '"></p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</div></fieldset>';
	}
	echo '</div>';

	// Méthode, sources, FAQ.
	echo '<h2 id="r18-s-doc">Méthode et sources</h2><table class="form-table" role="presentation"><tbody>';
	echo outils_row( 'r18-method', 'Comment le calcul est fait', '<textarea id="r18-method" name="method" rows="8" class="large-text" placeholder="' . \esc_attr( outils_lines_to_text( $def['method'] ) ) . '">' . \esc_textarea( isset( $r['method'] ) ? outils_lines_to_text( (array) $r['method'] ) : '' ) . '</textarea>', 'Une étape par ligne : Titre | Explication. Vide : texte du thème.' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo outils_row( 'r18-sources', 'Sources', '<textarea id="r18-sources" name="sources" rows="4" class="large-text" placeholder="' . \esc_attr( outils_lines_to_text( $def['sources'] ) ) . '">' . \esc_textarea( isset( $r['sources'] ) ? outils_lines_to_text( (array) $r['sources'] ) : '' ) . '</textarea>', 'Une source par ligne : Libellé | https://adresse. Vide : sources du thème.' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</tbody></table>';
	echo '<h2 id="r18-s-faq">Questions fréquentes</h2><table class="form-table" role="presentation"><tbody>';
	echo outils_row( 'r18-faq', 'Questions et réponses', '<textarea id="r18-faq" name="faq" rows="6" class="large-text" placeholder="' . \esc_attr( outils_lines_to_text( $def['faq'] ) ) . '">' . \esc_textarea( isset( $r['faq'] ) ? outils_lines_to_text( (array) $r['faq'] ) : '' ) . '</textarea>', 'Une question par ligne : Question ? | Réponse. Elles alimentent aussi les données structurées FAQ.' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</tbody></table>';

	echo '<div class="r18-adm__bar">';
	\submit_button( 'Enregistrer', 'primary', 'submit', false );
	echo ' <button type="submit" name="retablir" value="1" class="button" data-r18-confirm="Rétablir tous les textes et valeurs du thème pour cet outil ?">Rétablir les valeurs du thème</button></div>';
	echo '</form>';
}

/**
 * Enregistrement d'un outil.
 */
\add_action(
	'admin_post_rescue18_outil_save',
	static function (): void {
		$slug = \sanitize_key( \wp_unslash( $_POST['outil'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- vérifié juste après.
		if ( ! isset( outils_defaut()[ $slug ] ) || ! \current_user_can( 'manage_options' ) ) {
			\wp_die( 'Action non autorisée.', '', array( 'response' => 403 ) );
		}
		\check_admin_referer( 'rescue18_outil_' . $slug );
		$all = outils_reglages();
		$def = outils_defaut()[ $slug ];
		$msg = 'enregistre';

		if ( ! empty( $_POST['retablir'] ) ) {
			unset( $all[ $slug ] );
			$msg = 'retabli';
		} else {
			$in  = \wp_unslash( $_POST );
			$new = array(
				'actif' => ! empty( $in['actif'] ) ? 1 : 0,
			);
			if ( '' !== \trim( (string) ( $in['ordre'] ?? '' ) ) ) {
				$new['ordre'] = (int) $in['ordre'];
			}
			foreach ( array( 'title', 'short', 'gives', 'ref', 'seo_title' ) as $k ) {
				$new[ $k ] = \sanitize_text_field( (string) ( $in[ $k ] ?? '' ) );
			}
			foreach ( array( 'lead', 'description' ) as $k ) {
				$new[ $k ] = \sanitize_textarea_field( (string) ( $in[ $k ] ?? '' ) );
			}
			$mission        = \sanitize_key( (string) ( $in['mission'] ?? '' ) );
			$new['mission'] = isset( outil_missions_defaut()[ $mission ] ) && $mission !== $def['mission'] ? $mission : '';
			$icon           = \sanitize_key( (string) ( $in['icon'] ?? '' ) );
			$new['icon']    = \in_array( $icon, outils_icones(), true ) && $icon !== $def['icon'] ? $icon : '';

			$fields = array();
			foreach ( $def['fields'] as $f ) {
				$src = (array) ( $in['fields'][ $f['name'] ] ?? array() );
				$out = array(
					'label' => \sanitize_text_field( (string) ( $src['label'] ?? '' ) ),
					'help'  => \sanitize_text_field( (string) ( $src['help'] ?? '' ) ),
				);
				if ( 'number' === $f['type'] ) {
					foreach ( array( 'default', 'min', 'max', 'step' ) as $k ) {
						$raw       = \str_replace( ',', '.', \trim( (string) ( $src[ $k ] ?? '' ) ) );
						$out[ $k ] = \is_numeric( $raw ) ? (string) ( 0 + $raw ) : '';
					}
					$out['unit'] = \sanitize_text_field( (string) ( $src['unit'] ?? '' ) );
					$d           = '' !== $out['default'] ? (float) $out['default'] : (float) $f['default'];
					$min         = '' !== $out['min'] ? (float) $out['min'] : ( isset( $f['min'] ) ? (float) $f['min'] : -INF );
					$max         = '' !== $out['max'] ? (float) $out['max'] : ( isset( $f['max'] ) ? (float) $f['max'] : INF );
					if ( $d < $min || $d > $max ) {
						$msg = 'bornes';
					}
				} elseif ( 'select' === $f['type'] ) {
					if ( ! empty( $f['lock'] ) ) {
						$opts = array();
						foreach ( $f['options'] as $val => $l ) {
							$t = \sanitize_text_field( (string) ( $src['options'][ $val ] ?? '' ) );
							if ( '' !== $t ) {
								$opts[ (string) $val ] = $t;
							}
						}
						$valid = \array_map( 'strval', \array_keys( $f['options'] ) );
					} else {
						$opts = array();
						foreach ( outils_text_to_lines( (string) ( $src['options'] ?? '' ) ) as [ $val, $l ] ) {
							$val = \str_replace( ',', '.', $val );
							if ( \is_numeric( $val ) || ! \is_numeric( (string) \array_key_first( $f['options'] ) ) ) {
								$opts[ (string) $val ] = $l;
							}
						}
						$valid = \array_map( 'strval', \array_keys( $opts ? $opts : $f['options'] ) );
					}
					if ( $opts ) {
						$out['options'] = $opts;
					}
					$dv             = (string) ( $src['default'] ?? '' );
					$out['default'] = \in_array( $dv, $valid, true ) && $dv !== (string) $f['default'] ? $dv : '';
				} elseif ( 'text' === $f['type'] ) {
					$out['default'] = \sanitize_text_field( (string) ( $src['default'] ?? '' ) );
				}
				$fields[ $f['name'] ] = \array_filter( $out, static fn( $x ): bool => '' !== $x && array() !== $x );
			}
			$new['fields'] = \array_filter( $fields );

			foreach ( array(
				'method'  => false,
				'sources' => true,
				'faq'     => false,
			) as $k => $is_url ) {
				$text = (string) ( $in[ $k ] ?? '' );
				if ( '' !== \trim( $text ) ) {
					$new[ $k ] = outils_text_to_lines( $text, $is_url );
				}
			}
			$all[ $slug ] = \array_filter( $new, static fn( $x ): bool => '' !== $x && array() !== $x );
			$all[ $slug ]['actif'] = $new['actif'];
		}
		\update_option( 'rescue18_outils', $all, false );
		outil_sync_page( $slug );
		\wp_safe_redirect( outils_admin_url( array( 'outil' => $slug, 'r18msg' => $msg ) ) );
		exit;
	}
);

/**
 * Aligne la page publique sur les réglages : titre, statut, extrait.
 *
 * @param string $slug Outil.
 */
function outil_sync_page( string $slug ): void {
	$page = outil_page( $slug );
	$o    = outils()[ $slug ] ?? null;
	if ( null === $page || null === $o ) {
		return;
	}
	$status = $o['actif'] ? 'publish' : 'draft';
	if ( $page->post_title !== $o['title'] || $page->post_status !== $status || $page->post_excerpt !== $o['description'] ) {
		\wp_update_post(
			\wp_slash(
				array(
					'ID'           => $page->ID,
					'post_title'   => $o['title'],
					'post_status'  => $status,
					'post_excerpt' => $o['description'],
				)
			)
		);
	}
}

/**
 * Réglages communs.
 */
\add_action(
	'admin_post_rescue18_outils_communs',
	static function (): void {
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( 'Action non autorisée.', '', array( 'response' => 403 ) );
		}
		\check_admin_referer( 'rescue18_outils_communs' );
		$all      = outils_reglages();
		$in       = \wp_unslash( $_POST );
		$missions = array();
		foreach ( outil_missions_defaut() as $key => $label ) {
			$t = \sanitize_text_field( (string) ( $in['missions'][ $key ] ?? '' ) );
			if ( '' !== $t ) {
				$missions[ $key ] = $t;
			}
		}
		$all['_global'] = \array_filter(
			array(
				'avertissement' => \sanitize_textarea_field( (string) ( $in['avertissement'] ?? '' ) ),
				'missions'      => $missions,
			)
		);
		\update_option( 'rescue18_outils', $all, false );
		\wp_safe_redirect( outils_admin_url( array( 'r18msg' => 'communs' ) ) );
		exit;
	}
);

/**
 * Création des pages manquantes (catalogue et outils).
 */
\add_action(
	'admin_post_rescue18_outils_pages',
	static function (): void {
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( 'Action non autorisée.', '', array( 'response' => 403 ) );
		}
		\check_admin_referer( 'rescue18_outils_pages' );
		install_pages( false );
		foreach ( \array_keys( outils_defaut() ) as $slug ) {
			outil_sync_page( $slug );
		}
		\wp_safe_redirect( outils_admin_url( array( 'r18msg' => 'pages' ) ) );
		exit;
	}
);

/**
 * Styles et script de l'écran (compteurs, aperçu Google, icône).
 */
\add_action(
	'admin_enqueue_scripts',
	static function ( string $hook ): void {
		if ( 'toplevel_page_' . OUTILS_PAGE !== $hook ) {
			return;
		}
		$icons = asset( 'assets/css/icons.css' );
		if ( null !== $icons ) {
			\wp_enqueue_style( 'rescue18-icons', $icons[0], array(), $icons[1] );
		}
		$a = asset( 'assets/css/outils-admin.css' );
		if ( null !== $a ) {
			\wp_enqueue_style( 'rescue18-outils-admin', $a[0], array(), $a[1] );
		}
		$js = asset( 'assets/js/outils-admin.js' );
		if ( null !== $js ) {
			\wp_enqueue_script( 'rescue18-outils-admin', $js[0], array(), $js[1], true );
		}
	}
);
