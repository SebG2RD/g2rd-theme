<?php
/**
 * Quiz : import des quiz du thème, catalogue par thème, suivi de la relecture.
 *
 * Les quiz livrés dans data/quiz/*.json arrivent « en attente de relecture ».
 * Ils ne s'affichent qu'après publication par la rédaction. Un quiz déjà
 * importé n'est jamais réécrit : les corrections faites au back-office restent.
 *
 * @package G2RD_Rescue18
 */

namespace G2RD_Rescue18;

\defined( 'ABSPATH' ) || exit;

/**
 * Importe les quiz du thème absents du site.
 *
 * @return string[] Titres importés.
 */
function importer_quiz_pack(): array {
	$fichiers = \glob( \get_stylesheet_directory() . '/data/quiz/*.json' );
	$faits    = array();
	foreach ( \is_array( $fichiers ) ? $fichiers : array() as $f ) {
		$d = \json_decode( (string) \file_get_contents( $f ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( ! \is_array( $d ) || empty( $d['slug'] ) || empty( $d['questions'] ) ) {
			continue;
		}
		$src = 'quiz:pack:' . \sanitize_key( (string) $d['slug'] );
		$deja = \get_posts(
			array(
				'post_type'      => 'r18_quiz',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => 'r18_source_id', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'     => $src, // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);
		if ( $deja ) {
			continue;
		}
		$qs = array();
		foreach ( (array) $d['questions'] as $q ) {
			if ( ! empty( $q['q'] ) && ! empty( $q['c'] ) && \is_array( $q['c'] ) ) {
				$qs[] = array(
					'q'   => (string) $q['q'],
					'c'   => \array_values( \array_map( 'strval', $q['c'] ) ),
					'a'   => (int) ( $q['a'] ?? 0 ),
					'why' => (string) ( $q['why'] ?? '' ),
				);
			}
		}
		$sources = '';
		foreach ( (array) ( $d['sources'] ?? array() ) as $s ) {
			if ( \is_array( $s ) && ! empty( $s[0] ) ) {
				$lien     = ! empty( $s[1] ) ? '<a href="' . \esc_url( (string) $s[1] ) . '">' . \esc_html( (string) $s[0] ) . '</a>' : \esc_html( (string) $s[0] );
				$sources .= "<!-- wp:list-item -->\n<li>" . $lien . "</li>\n<!-- /wp:list-item -->\n";
			}
		}
		$contenu = '' !== $sources ? "<!-- wp:list {\"className\":\"r18-quiz-sources\"} -->\n<ul class=\"wp-block-list r18-quiz-sources\">" . $sources . "</ul>\n<!-- /wp:list -->" : '';
		$niveau  = (string) ( $d['niveau'] ?? 'Tous publics' );
		$duree   = (string) ( $d['duree'] ?? '' );
		$id      = (int) \wp_insert_post(
			\wp_slash(
				array(
					'post_type'    => 'r18_quiz',
					'post_status'  => 'pending',
					'post_title'   => (string) $d['titre'],
					'post_name'    => \sanitize_title( (string) $d['slug'] ),
					'post_excerpt' => \trim( (string) ( $d['resume'] ?? '' ) . ' Niveau : ' . $niveau . '. ' . \count( $qs ) . ' questions' . ( '' !== $duree ? ', ' . $duree : '' ) . '.' ),
					'post_content' => $contenu,
				)
			)
		);
		if ( $id <= 0 ) {
			continue;
		}
		\update_post_meta( $id, 'r18_source_id', $src );
		\update_post_meta( $id, 'r18_theme', (string) ( $d['theme'] ?? 'Culture pompier' ) );
		\update_post_meta( $id, 'r18_niveau', $niveau );
		\update_post_meta( $id, 'r18_duree', $duree );
		\update_post_meta( $id, 'r18_questions', \wp_slash( (string) \wp_json_encode( $qs, JSON_UNESCAPED_UNICODE ) ) );
		$faits[] = (string) $d['titre'];
	}
	return $faits;
}

/* Import une fois par jeu de fichiers : un nouveau fichier déclenche un nouveau passage. */
\add_action(
	'wp_loaded',
	static function (): void {
		$fichiers = \glob( \get_stylesheet_directory() . '/data/quiz/*.json' );
		$empreinte = \md5( \implode( '|', \is_array( $fichiers ) ? \array_map( 'basename', $fichiers ) : array() ) );
		if ( \get_option( 'r18_quiz_pack' ) === $empreinte || \wp_installing() ) {
			return;
		}
		\update_option( 'r18_quiz_pack', $empreinte, false );
		$faits = importer_quiz_pack();
		if ( $faits && \function_exists( 'G2RD_Rescue18\\Veille\\journal' ) ) {
			\G2RD_Rescue18\Veille\journal( 'cycle', 'info', \count( $faits ) . ' quiz à relire : ' . \implode( ', ', $faits ) );
		}
	},
	40
);

/* Rappel de relecture sur la liste des quiz et le tableau de bord. */
\add_action(
	'admin_notices',
	static function (): void {
		$ecran = \get_current_screen();
		if ( ! $ecran || ! \in_array( $ecran->id, array( 'dashboard', 'edit-r18_quiz' ), true ) || ! \current_user_can( 'edit_posts' ) ) {
			return;
		}
		$n = (int) ( \wp_count_posts( 'r18_quiz' )->pending ?? 0 );
		if ( $n < 1 ) {
			return;
		}
		echo '<div class="notice notice-info"><p><strong>' . (int) $n . ' quiz en attente de relecture.</strong> Vérifie chaque question et sa réponse, puis publie le quiz. <a href="' . \esc_url( \admin_url( 'edit.php?post_type=r18_quiz&post_status=pending' ) ) . '">Voir les quiz à relire</a>. Pour en publier plusieurs : cases à cocher, Actions groupées, Modifier, État « Publié ».</p></div>';
	}
);

/* Colonnes utiles dans la liste des quiz. */
\add_filter(
	'manage_r18_quiz_posts_columns',
	static function ( array $c ): array {
		$c['r18_theme'] = 'Thème';
		$c['r18_nb']    = 'Questions';
		return $c;
	}
);
\add_action(
	'manage_r18_quiz_posts_custom_column',
	static function ( string $col, int $id ): void {
		if ( 'r18_theme' === $col ) {
			echo \esc_html( (string) \get_post_meta( $id, 'r18_theme', true ) );
		} elseif ( 'r18_nb' === $col ) {
			echo (int) \count( quiz_questions( $id ) );
		}
	},
	10,
	2
);

/**
 * Bloc rescue18/quiz-catalogue : tous les quiz publiés, filtrables par thème.
 *
 * @return string
 */
function render_quiz_catalogue(): string {
	$ids = \get_posts(
		array(
			'post_type'      => 'r18_quiz',
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'fields'         => 'ids',
		)
	);
	if ( ! $ids ) {
		return '<p class="r18-qcat-vide">Les quiz arrivent bientôt.</p>';
	}
	$themes = array();
	$cartes = '';
	foreach ( $ids as $id ) {
		$theme = (string) \get_post_meta( $id, 'r18_theme', true );
		$theme = '' !== $theme ? $theme : 'Culture pompier';
		$cle   = \sanitize_title( $theme );
		$themes[ $cle ] = $theme;
		$n     = \count( quiz_questions( $id ) );
		$niv   = (string) \get_post_meta( $id, 'r18_niveau', true );
		$dur   = (string) \get_post_meta( $id, 'r18_duree', true );
		$meta  = \array_filter( array( $n . ' questions', $dur, $niv ) );
		$cartes .= '<li class="r18-qcat__item" data-theme="' . \esc_attr( $cle ) . '"><a class="r18-qcat__card" href="' . \esc_url( (string) \get_permalink( $id ) ) . '"><span class="r18-qcat__theme">' . \esc_html( $theme ) . '</span><strong>' . \esc_html( \get_the_title( $id ) ) . '</strong><span class="r18-qcat__meta">' . \esc_html( \implode( ' · ', $meta ) ) . '</span></a></li>';
	}
	\asort( $themes );
	$chips = '<button type="button" class="r18-tk-chip" aria-pressed="true" data-theme="">Tous (' . \count( $ids ) . ')</button>';
	foreach ( $themes as $cle => $nom ) {
		$chips .= '<button type="button" class="r18-tk-chip" aria-pressed="false" data-theme="' . \esc_attr( $cle ) . '">' . \esc_html( $nom ) . '</button>';
	}
	$js = '<script>(function(){var r=document.currentScript.closest(".r18-qcat");if(!r)return;var b=r.querySelectorAll("[data-theme]:not(li)"),l=r.querySelectorAll("li[data-theme]"),s=r.querySelector("[data-qcat-count]");b.forEach(function(x){x.addEventListener("click",function(){var t=x.getAttribute("data-theme"),n=0;b.forEach(function(o){o.setAttribute("aria-pressed",o===x?"true":"false")});l.forEach(function(i){var v=!t||i.getAttribute("data-theme")===t;i.hidden=!v;n+=v?1:0});s.textContent=n+" quiz affiché"+(n>1?"s":"")})})})();</script>';
	return '<div class="r18-qcat"><div class="r18-qcat__chips" role="group" aria-label="Filtrer par thème">' . $chips . '</div><p class="r18-qcat__count" data-qcat-count aria-live="polite">' . \count( $ids ) . ' quiz affichés</p><ul class="r18-qcat__list" role="list">' . $cartes . '</ul>' . $js . '</div>';
}

/**
 * Définition du bloc catalogue.
 *
 * @return array<string,array<string,mixed>>
 */
function quiz_block_definitions(): array {
	return array(
		'quiz-catalogue' => array(
			'title'  => 'Catalogue des quiz',
			'icon'   => 'welcome-learn-more',
			'attrs'  => array(),
			'render' => 'render_quiz_catalogue',
		),
	);
}
