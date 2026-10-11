<?php
/**
 * Installation des contenus : crée les pages et les guides à partir des
 * fichiers de gutenberg-pages/.
 *
 * Outil facultatif (Apparence › Installation TCGForge). Chaque fichier reste
 * copiable à la main dans l'éditeur de code Gutenberg. L'outil ne remplace
 * jamais un contenu existant : une page dont le slug existe déjà est ignorée.
 *
 * @package G2RD_TCGForge
 */

declare( strict_types = 1 );

namespace G2RD_TCGForge;

\defined( 'ABSPATH' ) || exit;

/**
 * Plan des contenus : fichier → titre, slug, type, modèle, extrait.
 *
 * @return array<string,array<string,string>>
 */
function content_map(): array {
	$licence = 'page-licence';
	$guide   = 'page-guide';

	return array(
		// Pages parentes.
		'cartes-a-collectionner'  => array( 'title' => 'Cartes à collectionner', 'slug' => 'cartes-a-collectionner', 'type' => 'page' ),
		'aide'                    => array( 'title' => 'Aide et service client', 'slug' => 'aide', 'type' => 'page' ),
		'informations-legales'    => array( 'title' => 'Informations légales', 'slug' => 'informations-legales', 'type' => 'page' ),

		// Pages commerciales.
		'accueil'                 => array( 'title' => 'Accueil', 'slug' => 'accueil', 'type' => 'page', 'template' => 'page-libre', 'role' => 'front' ),
		'boutique'                => array( 'title' => 'Boutique', 'slug' => 'boutique', 'type' => 'page', 'role' => 'shop' ),
		'nouveautes'              => array( 'title' => 'Nouveautés', 'slug' => 'nouveautes', 'type' => 'page' ),
		'precommandes-rayon'      => array( 'title' => 'Précommandes', 'slug' => 'precommandes', 'type' => 'page' ),
		'promotions'              => array( 'title' => 'Promotions', 'slug' => 'promotions', 'type' => 'page' ),
		'pokemon'                 => array( 'title' => 'Pokémon TCG : boosters, displays et coffrets', 'slug' => 'pokemon', 'type' => 'page', 'template' => $licence, 'excerpt' => 'Cartes Pokémon en produits scellés : boosters, displays, Coffrets Dresseur d\'Élite, coffrets et decks. Les sorties récentes, les précommandes et tout le rayon classé par type de produit.' ),
		'yu-gi-oh'                => array( 'title' => 'Yu-Gi-Oh! : boosters, decks de structure et tins', 'slug' => 'yu-gi-oh', 'type' => 'page', 'template' => $licence, 'excerpt' => 'Boosters, displays, decks de structure, tins et coffrets Yu-Gi-Oh!, avec les protections au format japonais adaptées aux cartes.' ),
		'one-piece'               => array( 'title' => 'One Piece Card Game : boosters, displays et decks', 'slug' => 'one-piece', 'type' => 'page', 'template' => $licence, 'excerpt' => 'Les séries du One Piece Card Game : boosters, displays, decks de démarrage et éditions spéciales, en stock ou en précommande.' ),
		'magic'                   => array( 'title' => 'Magic: The Gathering : boosters, decks Commander et bundles', 'slug' => 'magic', 'type' => 'page', 'template' => $licence, 'excerpt' => 'Boosters de jeu, boosters collector, decks Commander, bundles et kits de démarrage Magic: The Gathering.' ),
		'lorcana'                 => array( 'title' => 'Disney Lorcana : boosters, decks et coffrets', 'slug' => 'lorcana', 'type' => 'page', 'template' => $licence, 'excerpt' => 'Les chapitres de Disney Lorcana : boosters, displays, decks de démarrage, coffrets cadeaux et Trésors des Illumineurs.' ),
		'autres-tcg'              => array( 'title' => 'Autres jeux de cartes à collectionner', 'slug' => 'autres-tcg', 'type' => 'page', 'template' => $licence, 'excerpt' => 'Les autres jeux de cartes à collectionner du catalogue : decks de démarrage, boosters et coffrets.' ),
		'accessoires'             => array( 'title' => 'Accessoires TCG : sleeves, deck box et classeurs', 'slug' => 'accessoires', 'type' => 'page', 'template' => $licence, 'excerpt' => 'Sleeves, top loaders, deck box, classeurs de cartes, tapis de jeu et boîtes de rangement pour protéger et transporter une collection.' ),
		'figurines'               => array( 'title' => 'Figurines et collectibles', 'slug' => 'figurines', 'type' => 'page', 'template' => $licence, 'excerpt' => 'Figurines manga, statuettes, peluches et objets de collection sous licence officielle.' ),
		'produits-derives'        => array( 'title' => 'Produits dérivés manga et gaming', 'slug' => 'produits-derives', 'type' => 'page', 'template' => $licence, 'excerpt' => 'Mugs, textile, papeterie, posters et accessoires sous licence pour les fans de manga, d\'anime et de jeu vidéo.' ),

		// Service.
		'a-propos'                => array( 'title' => 'À propos de TCGForge', 'slug' => 'a-propos', 'type' => 'page' ),
		'contact'                 => array( 'title' => 'Contact', 'slug' => 'contact', 'type' => 'page' ),
		'faq'                     => array( 'title' => 'Questions fréquentes', 'slug' => 'faq', 'type' => 'page' ),
		'livraison'               => array( 'title' => 'Livraison', 'slug' => 'livraison', 'type' => 'page', 'template' => $guide ),
		'retours-remboursements'  => array( 'title' => 'Retours et remboursements', 'slug' => 'retours-remboursements', 'type' => 'page', 'template' => $guide ),
		'authenticite'            => array( 'title' => 'Authenticité des produits', 'slug' => 'authenticite', 'type' => 'page' ),
		'etat-emballage'          => array( 'title' => 'État et emballage des produits', 'slug' => 'etat-emballage', 'type' => 'page' ),
		'precommandes'            => array( 'title' => 'Fonctionnement des précommandes', 'slug' => 'precommandes-fonctionnement', 'type' => 'page', 'template' => $guide ),
		'suivi-de-commande'       => array( 'title' => 'Suivi de commande', 'slug' => 'suivi-de-commande', 'type' => 'page' ),
		'conseils'                => array( 'title' => 'Conseils et guides', 'slug' => 'conseils', 'type' => 'page' ),

		// Guides (articles).
		'guide-debuter-tcg'       => array( 'title' => 'Bien débuter dans les TCG', 'slug' => 'bien-debuter-tcg', 'type' => 'post', 'excerpt' => 'Choisir son premier jeu de cartes à collectionner, acheter le bon premier produit et protéger ses cartes dès le départ.' ),
		'guide-proteger-cartes'   => array( 'title' => 'Comment protéger ses cartes', 'slug' => 'proteger-ses-cartes', 'type' => 'post', 'excerpt' => 'Frottement, pliure, humidité, lumière : la méthode complète pour protéger ses cartes, de l\'ouverture du booster au transport.' ),
		'guide-ranger-collection' => array( 'title' => 'Comment ranger une collection', 'slug' => 'ranger-collection-cartes', 'type' => 'post', 'excerpt' => 'Classeur, boîtes de rangement, ordre de tri, inventaire : organiser une collection de cartes selon sa taille et sa valeur.' ),
		'guide-protections'       => array( 'title' => 'Sleeves, top loaders et protections', 'slug' => 'sleeves-top-loaders-protections', 'type' => 'post', 'excerpt' => 'Tailles de cartes, sleeves de jeu, penny sleeves, top loaders, card savers et deck box : quelle protection pour quel usage.' ),
		'guide-produits-tcg'      => array( 'title' => 'Comprendre un display, un booster et un coffret', 'slug' => 'comprendre-display-booster-coffret', 'type' => 'post', 'excerpt' => 'Booster, display, coffret, tin, bundle, deck préconstruit : chaque format de produit TCG expliqué, avec un tableau pour choisir.' ),
		'guide-conserver-cartes'  => array( 'title' => 'Comment conserver des cartes de collection', 'slug' => 'conserver-cartes-collection', 'type' => 'post', 'excerpt' => 'Humidité, température, lumière, matériaux : les réglages pour conserver des cartes de collection pendant des décennies.' ),

		// Juridique.
		'mentions-legales'        => array( 'title' => 'Mentions légales', 'slug' => 'mentions-legales', 'type' => 'page' ),
		'cgv'                     => array( 'title' => 'Conditions générales de vente', 'slug' => 'cgv', 'type' => 'page', 'template' => $guide, 'role' => 'terms' ),
		'confidentialite'         => array( 'title' => 'Politique de confidentialité', 'slug' => 'politique-de-confidentialite', 'type' => 'page', 'template' => $guide, 'role' => 'privacy' ),
		'cookies'                 => array( 'title' => 'Politique de cookies', 'slug' => 'politique-de-cookies', 'type' => 'page' ),
		'retractation'            => array( 'title' => 'Droit de rétractation', 'slug' => 'droit-de-retractation', 'type' => 'page' ),
		'formulaire-retractation' => array( 'title' => 'Exercer mon droit de rétractation', 'slug' => 'formulaire-de-retractation', 'type' => 'page' ),
		'conditions-precommandes' => array( 'title' => 'Conditions de précommande', 'slug' => 'conditions-de-precommande', 'type' => 'page', 'template' => $guide ),
		'accessibilite'           => array( 'title' => 'Accessibilité', 'slug' => 'accessibilite', 'type' => 'page' ),
	);
}

/**
 * Version de la structure : la changer relance l'harmonisation une fois.
 */
const STRUCTURE_VERSION = '1.1.0';

/**
 * Arborescence : slug de la page enfant → slug de la page parente.
 *
 * @return array<string,string>
 */
function structure_map(): array {
	return array(
		'pokemon' => 'cartes-a-collectionner',
		'yu-gi-oh' => 'cartes-a-collectionner',
		'one-piece' => 'cartes-a-collectionner',
		'magic' => 'cartes-a-collectionner',
		'lorcana' => 'cartes-a-collectionner',
		'autres-tcg' => 'cartes-a-collectionner',
		'faq' => 'aide',
		'livraison' => 'aide',
		'retours-remboursements' => 'aide',
		'suivi-de-commande' => 'aide',
		'authenticite' => 'aide',
		'etat-emballage' => 'aide',
		'precommandes-fonctionnement' => 'aide',
		'mentions-legales' => 'informations-legales',
		'cgv' => 'informations-legales',
		'politique-de-confidentialite' => 'informations-legales',
		'politique-de-cookies' => 'informations-legales',
		'droit-de-retractation' => 'informations-legales',
		'formulaire-de-retractation' => 'informations-legales',
		'conditions-de-precommande' => 'informations-legales',
		'accessibilite' => 'informations-legales',
	);
}

/**
 * SEO : slug → titre et méta description.
 *
 * @return array<string,string[]>
 */
function seo_map(): array {
	return array(
		'cartes-a-collectionner' => array( 'Cartes à collectionner : tous les jeux TCG | TCGForge', 'Pokémon, Yu-Gi-Oh!, One Piece, Magic, Lorcana : tous les jeux de cartes à collectionner en produits scellés officiels sur TCGForge.' ),
		'aide' => array( 'Aide et service client | TCGForge', 'Livraison, suivi de commande, retours, précommandes, authenticité : toutes les réponses du service client TCGForge.' ),
		'informations-legales' => array( 'Informations légales | TCGForge', 'Mentions légales, CGV, rétractation, confidentialité, cookies et accessibilité : les documents légaux de TCGForge.' ),
		'boutique' => array( 'Boutique TCG : cartes, accessoires et figurines | TCGForge', 'Tout le catalogue TCGForge : boosters, displays, coffrets, accessoires TCG, figurines et produits dérivés officiels.' ),
		'nouveautes' => array( 'Nouveautés TCG : dernières sorties | TCGForge', 'Les derniers produits mis en ligne : extensions récentes, réassorts, accessoires et objets de collection.' ),
		'precommandes' => array( 'Précommandes TCG : prochaines sorties | TCGForge', 'Réservez les prochaines extensions avant leur sortie. Date de sortie annoncée et période d\'expédition sur chaque fiche.' ),
		'promotions' => array( 'Promotions TCG et accessoires | TCGForge', 'Les produits TCG, accessoires et objets de collection à prix réduit en ce moment sur TCGForge.' ),
		'pokemon' => array( 'Cartes Pokémon : boosters, displays, coffrets | TCGForge', 'Cartes Pokémon en produits scellés officiels : boosters, displays, Coffrets Dresseur d\'Élite, coffrets et decks.' ),
		'yu-gi-oh' => array( 'Cartes Yu-Gi-Oh! : boosters, decks, tins | TCGForge', 'Boosters, displays, decks de structure, tins et coffrets Yu-Gi-Oh! en produits scellés officiels.' ),
		'one-piece' => array( 'One Piece Card Game : boosters et displays | TCGForge', 'Boosters, displays, decks de démarrage et éditions spéciales du One Piece Card Game, en stock ou en précommande.' ),
		'magic' => array( 'Magic: The Gathering : boosters et decks | TCGForge', 'Boosters de jeu, boosters collector, decks Commander, bundles et kits de démarrage Magic: The Gathering.' ),
		'lorcana' => array( 'Disney Lorcana : boosters, decks, coffrets | TCGForge', 'Boosters, displays, decks de démarrage et coffrets cadeaux Disney Lorcana en produits scellés officiels.' ),
		'autres-tcg' => array( 'Autres jeux de cartes à collectionner | TCGForge', 'Les autres jeux de cartes à collectionner du catalogue : decks de démarrage, boosters et coffrets.' ),
		'accessoires' => array( 'Accessoires TCG : sleeves, deck box, classeurs | TCGForge', 'Sleeves, top loaders, deck box, classeurs, tapis de jeu et boîtes de rangement pour protéger vos cartes.' ),
		'figurines' => array( 'Figurines manga et objets de collection | TCGForge', 'Figurines manga, statuettes, peluches et objets de collection sous licence officielle.' ),
		'produits-derives' => array( 'Produits dérivés manga et gaming | TCGForge', 'Mugs, textile, papeterie, posters et accessoires sous licence pour les fans de manga, d\'anime et de jeu vidéo.' ),
		'a-propos' => array( 'À propos de TCGForge, boutique TCG française', 'TCGForge, boutique en ligne française de cartes à collectionner, d\'accessoires TCG et d\'objets de collection.' ),
		'contact' => array( 'Contact et service client | TCGForge', 'Une question sur un produit, une commande ou une précommande ? Écrivez au service client TCGForge.' ),
		'faq' => array( 'Questions fréquentes | TCGForge', 'Produits, paiement, livraison, précommandes, retours : les réponses aux questions les plus posées.' ),
		'livraison' => array( 'Livraison : zones, délais et tarifs | TCGForge', 'Zones de livraison, transporteurs, délais, tarifs et démarches à la réception de votre colis TCGForge.' ),
		'retours-remboursements' => array( 'Retours et remboursements | TCGForge', 'Quatorze jours pour changer d\'avis : la démarche de retour et le délai de remboursement chez TCGForge.' ),
		'authenticite' => array( 'Authenticité des produits TCG | TCGForge', 'Produits officiels et scellés : notre engagement et les vérifications simples à faire à la réception.' ),
		'etat-emballage' => array( 'État et emballage des produits | TCGForge', 'État des produits scellés, défauts de fabrication et protection des colis : ce que garantit TCGForge.' ),
		'precommandes-fonctionnement' => array( 'Fonctionnement des précommandes | TCGForge', 'Paiement, date de sortie, expédition, annulation : chaque étape d\'une précommande sur TCGForge.' ),
		'suivi-de-commande' => array( 'Suivi de commande | TCGForge', 'Affichez l\'état de votre commande TCGForge avec votre numéro de commande et votre adresse e-mail.' ),
		'conseils' => array( 'Conseils et guides TCG | TCGForge', 'Guides pratiques pour débuter, protéger ses cartes, ranger une collection et comprendre les produits TCG.' ),
		'bien-debuter-tcg' => array( 'Bien débuter dans les TCG : le guide | TCGForge', 'Choisir son premier jeu de cartes à collectionner, acheter le bon premier produit et protéger ses cartes.' ),
		'proteger-ses-cartes' => array( 'Comment protéger ses cartes à collectionner | TCGForge', 'Frottement, pliure, humidité, lumière : la méthode complète pour protéger ses cartes au quotidien.' ),
		'ranger-collection-cartes' => array( 'Comment ranger une collection de cartes | TCGForge', 'Classeur, boîtes de rangement, tri, inventaire : organiser une collection de cartes selon sa taille.' ),
		'sleeves-top-loaders-protections' => array( 'Sleeves, top loaders : quelle protection choisir | TCGForge', 'Tailles de cartes, sleeves, penny sleeves, top loaders, card savers et deck box : quelle protection pour quel usage.' ),
		'comprendre-display-booster-coffret' => array( 'Display, booster, coffret : comprendre | TCGForge', 'Booster, display, coffret, tin, bundle, deck : chaque format de produit TCG expliqué, avec un tableau pour choisir.' ),
		'conserver-cartes-collection' => array( 'Conserver des cartes de collection | TCGForge', 'Humidité, température, lumière, matériaux : les bons réglages pour conserver des cartes pendant des décennies.' ),
		'mentions-legales' => array( 'Mentions légales | TCGForge', 'Éditeur, hébergeur et propriété intellectuelle du site TCGForge.' ),
		'cgv' => array( 'Conditions générales de vente | TCGForge', 'Les conditions générales de vente applicables aux commandes passées sur TCGForge.' ),
		'politique-de-confidentialite' => array( 'Politique de confidentialité | TCGForge', 'Données personnelles collectées par TCGForge, finalités, durées de conservation et droits.' ),
		'politique-de-cookies' => array( 'Politique de cookies | TCGForge', 'Les cookies utilisés par TCGForge et la gestion de votre choix.' ),
		'droit-de-retractation' => array( 'Droit de rétractation | TCGForge', 'Délai de quatorze jours, démarche et remboursement : votre droit de rétractation sur TCGForge.' ),
		'formulaire-de-retractation' => array( 'Exercer mon droit de rétractation | TCGForge', 'Rétractez-vous en ligne en deux étapes, sans compte et sans motif. Accusé de réception horodaté par e-mail.' ),
		'conditions-de-precommande' => array( 'Conditions de précommande | TCGForge', 'Les conditions applicables aux produits proposés avant leur date de sortie sur TCGForge.' ),
		'accessibilite' => array( 'Accessibilité du site | TCGForge', 'État de conformité du site TCGForge et moyens de signaler une difficulté d\'accès.' ),
	);
}

/**
 * Retrouve un contenu par son slug, quel que soit son parent.
 *
 * @param string $slug Slug.
 * @param string $type Type de contenu.
 * @return \WP_Post|null
 */
function find_content( string $slug, string $type ) {
	$found = \get_posts(
		array(
			'name'             => $slug,
			'post_type'        => $type,
			'post_status'      => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'numberposts'      => 1,
			'suppress_filters' => true,
		)
	);
	return $found ? $found[0] : null;
}

/**
 * Remplace les anciens liens à plat par les liens hiérarchiques.
 *
 * @param string $content Contenu.
 * @return string
 */
function rewrite_links( string $content ): string {
	$map   = structure_map();
	$slugs = \implode( '|', \array_map( 'preg_quote', \array_keys( $map ) ) );
	$out   = \preg_replace_callback(
		'~(?<=["\'])/(' . $slugs . ')/(?=["\'#?])~',
		static function ( array $m ) use ( $map ): string {
			return '/' . $map[ $m[1] ] . '/' . $m[1] . '/';
		},
		$content
	);
	return \is_string( $out ) ? $out : $content;
}

/**
 * Harmonise le site : pages parentes, rattachement des pages enfants, liens
 * internes, titres et descriptions SEO (champs SEOPress, remplis s'ils sont vides).
 *
 * @return array<string,int> Compteurs.
 */
function sync_structure(): array {
	$report = array( 'parents' => 0, 'moved' => 0, 'links' => 0, 'seo' => 0 );
	$map    = content_map();
	$dir    = \get_stylesheet_directory() . '/gutenberg-pages/';

	foreach ( \array_unique( \array_values( structure_map() ) ) as $slug ) {
		if ( find_content( $slug, 'page' ) || ! \is_readable( $dir . $slug . '.html' ) ) {
			continue;
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- fichier local du thème.
		$content = (string) \file_get_contents( $dir . $slug . '.html' );
		$post_id = \wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $map[ $slug ]['title'],
				'post_name'    => $slug,
				'post_content' => \wp_slash( $content ),
			)
		);
		if ( $post_id ) {
			++$report['parents'];
		}
	}

	foreach ( structure_map() as $child => $parent ) {
		$page = find_content( $child, 'page' );
		$top  = find_content( $parent, 'page' );
		if ( $page && $top && (int) $page->post_parent !== (int) $top->ID ) {
			\wp_update_post( array( 'ID' => $page->ID, 'post_parent' => $top->ID ) );
			++$report['moved'];
		}
	}

	$seo = seo_map();
	foreach ( $map as $item ) {
		$post = find_content( $item['slug'], $item['type'] );
		if ( ! $post ) {
			continue;
		}
		$content = rewrite_links( $post->post_content );
		if ( $content !== $post->post_content ) {
			\wp_update_post( array( 'ID' => $post->ID, 'post_content' => \wp_slash( $content ) ) );
			++$report['links'];
		}
		if ( isset( $seo[ $item['slug'] ] ) ) {
			$done = false;
			foreach ( array( '_seopress_titles_title' => 0, '_seopress_titles_desc' => 1 ) as $key => $i ) {
				if ( '' === (string) \get_post_meta( $post->ID, $key, true ) ) {
					\update_post_meta( $post->ID, $key, \wp_slash( $seo[ $item['slug'] ][ $i ] ) );
					$done = true;
				}
			}
			$report['seo'] += $done ? 1 : 0;
		}
	}

	\flush_rewrite_rules( false );
	\update_option( 'tcgforge_structure_version', STRUCTURE_VERSION );
	return $report;
}

/**
 * Harmonisation automatique, une fois, à la mise à jour du thème.
 */
\add_action(
	'admin_init',
	function (): void {
		if ( STRUCTURE_VERSION !== \get_option( 'tcgforge_structure_version' ) && \current_user_can( 'manage_options' ) && ! \wp_doing_ajax() ) {
			sync_structure();
		}
	}
);

/**
 * Un changement de structure des permaliens régénère les règles de réécriture.
 */
\add_action(
	'update_option_permalink_structure',
	function (): void {
		\delete_option( 'rewrite_rules' );
	}
);

/**
 * Bouton « Harmoniser » de l'écran d'installation.
 */
\add_action(
	'admin_post_tcgforge_sync_structure',
	function (): void {
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( \esc_html__( 'Action non autorisée.', 'g2rd-tcgforge-child' ), 403 );
		}
		\check_admin_referer( 'tcgforge_sync_structure' );
		$r = sync_structure();
		purge_parent_template_copies();
		\wp_safe_redirect( \admin_url( 'themes.php?page=tcgforge-installation&sync=' . \rawurlencode( \implode( '-', $r ) ) ) );
		exit;
	}
);

/**
 * Crée les contenus manquants.
 *
 * @param string $status Statut des contenus créés (draft ou publish).
 * @return array<string,string[]> Titres créés, ignorés, en erreur.
 */
function install_content( string $status = 'draft' ): array {
	$status = 'publish' === $status ? 'publish' : 'draft';
	$report = array(
		'created' => array(),
		'skipped' => array(),
		'failed'  => array(),
	);
	$dir    = \get_stylesheet_directory() . '/gutenberg-pages/';

	foreach ( content_map() as $file => $item ) {
		$path = $dir . $file . '.html';
		if ( ! \is_readable( $path ) ) {
			$report['failed'][] = $item['title'];
			continue;
		}

		$existing = find_content( $item['slug'], $item['type'] );
		if ( $existing ) {
			$report['skipped'][] = $item['title'];
			continue;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- fichier local du thème.
		$content = (string) \file_get_contents( $path );
		$post_id = \wp_insert_post(
			array(
				'post_type'    => $item['type'],
				'post_status'  => $status,
				'post_title'   => $item['title'],
				'post_name'    => $item['slug'],
				'post_content' => \wp_slash( $content ),
				'post_excerpt' => $item['excerpt'] ?? '',
			),
			true
		);

		if ( \is_wp_error( $post_id ) || ! $post_id ) {
			$report['failed'][] = $item['title'];
			continue;
		}

		if ( ! empty( $item['template'] ) ) {
			\update_post_meta( (int) $post_id, '_wp_page_template', $item['template'] );
		}
		$report['created'][] = $item['title'];
	}

	if ( $report['created'] ) {
		sync_structure();
	}

	return $report;
}

/**
 * Les pages acceptent un extrait : il sert de chapeau sur les pages de licence.
 */
\add_action(
	'init',
	function (): void {
		\add_post_type_support( 'page', 'excerpt' );
	}
);

/**
 * Sans extrait saisi, WordPress en fabrique un à partir du contenu : le
 * chapeau d'une page répéterait alors son premier paragraphe. On ne l'affiche
 * que s'il a été rédigé.
 *
 * @param string              $html  Rendu du bloc.
 * @param array<string,mixed> $block Bloc analysé.
 * @return string
 */
\add_filter(
	'render_block_core/post-excerpt',
	static function ( $html, $block ) {
		$post = \get_post();
		if ( $post && 'page' === $post->post_type && ! \has_excerpt( $post ) ) {
			return '';
		}
		return $html;
	},
	10,
	2
);

/**
 * Écran Apparence › Installation TCGForge.
 */
\add_action(
	'admin_menu',
	function (): void {
		\add_theme_page(
			\__( 'Installation TCGForge', 'g2rd-tcgforge-child' ),
			\__( 'Installation TCGForge', 'g2rd-tcgforge-child' ),
			'manage_options',
			'tcgforge-installation',
			__NAMESPACE__ . '\render_setup_page'
		);
	}
);

/**
 * Traitement du formulaire d'installation.
 */
\add_action(
	'admin_post_tcgforge_install_content',
	function (): void {
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( \esc_html__( 'Action non autorisée.', 'g2rd-tcgforge-child' ), 403 );
		}
		\check_admin_referer( 'tcgforge_install_content' );

		$status = isset( $_POST['tcgforge_status'] ) && 'publish' === \sanitize_key( \wp_unslash( $_POST['tcgforge_status'] ) ) ? 'publish' : 'draft';
		$report = install_content( $status );

		\set_transient( 'tcgforge_install_report_' . \get_current_user_id(), $report, 5 * MINUTE_IN_SECONDS );
		\wp_safe_redirect( \admin_url( 'themes.php?page=tcgforge-installation&done=1' ) );
		exit;
	}
);

/**
 * Affiche l'écran d'installation.
 */
function render_setup_page(): void {
	if ( ! \current_user_can( 'manage_options' ) ) {
		return;
	}

	$report = \get_transient( 'tcgforge_install_report_' . \get_current_user_id() );
	if ( \is_array( $report ) ) {
		\delete_transient( 'tcgforge_install_report_' . \get_current_user_id() );
	}

	echo '<div class="wrap"><h1>' . \esc_html__( 'Installation TCGForge', 'g2rd-tcgforge-child' ) . '</h1>';

	if ( \is_array( $report ) ) {
		\printf(
			'<div class="notice notice-success"><p>%s</p></div>',
			\esc_html(
				\sprintf(
					/* translators: 1: créés, 2: déjà présents, 3: en erreur. */
					\__( '%1$d contenus créés, %2$d déjà présents (non modifiés), %3$d en erreur.', 'g2rd-tcgforge-child' ),
					\count( $report['created'] ?? array() ),
					\count( $report['skipped'] ?? array() ),
					\count( $report['failed'] ?? array() )
				)
			)
		);
	}

	echo '<p>' . \esc_html__( 'Crée les pages et les guides à partir des fichiers du dossier gutenberg-pages/ du thème. Un contenu dont le slug existe déjà n\'est jamais modifié.', 'g2rd-tcgforge-child' ) . '</p>';
	echo '<p>' . \esc_html__( 'Les pages juridiques contiennent des champs entre crochets à compléter avant publication.', 'g2rd-tcgforge-child' ) . '</p>';

	echo '<form method="post" action="' . \esc_url( \admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="tcgforge_install_content">';
	\wp_nonce_field( 'tcgforge_install_content' );
	echo '<p><label><input type="radio" name="tcgforge_status" value="draft" checked> ' . \esc_html__( 'Créer en brouillon (conseillé)', 'g2rd-tcgforge-child' ) . '</label><br>';
	echo '<label><input type="radio" name="tcgforge_status" value="publish"> ' . \esc_html__( 'Publier directement', 'g2rd-tcgforge-child' ) . '</label></p>';
	\submit_button( \__( 'Créer les contenus manquants', 'g2rd-tcgforge-child' ) );
	echo '</form>';

	if ( isset( $_GET['sync'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- affichage seul.
		$n = \array_map( 'absint', \explode( '-', \sanitize_text_field( \wp_unslash( $_GET['sync'] ) ) ) + array( 0, 0, 0, 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		\printf( '<div class="notice notice-success"><p>%s</p></div>', \esc_html( \sprintf( 'Pages parentes ajoutées : %1$d. Pages rattachées : %2$d. Contenus aux liens corrigés : %3$d. Fiches SEO remplies : %4$d.', $n[0], $n[1], $n[2], $n[3] ) ) );
	}
	echo '<h2>' . \esc_html__( 'Arborescence et SEO', 'g2rd-tcgforge-child' ) . '</h2>';
	echo '<p>' . \esc_html__( 'Rattache chaque page à sa page parente, corrige les liens internes des contenus et remplit les titres et descriptions SEOPress restés vides.', 'g2rd-tcgforge-child' ) . '</p>';
	echo '<form method="post" action="' . \esc_url( \admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="tcgforge_sync_structure">';
	\wp_nonce_field( 'tcgforge_sync_structure' );
	\submit_button( \__( 'Harmoniser les pages', 'g2rd-tcgforge-child' ), 'secondary' );
	echo '</form>';

	if ( isset( $_GET['refreshed'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- affichage seul.
		/* translators: %d: nombre de pages. */
		\printf( '<div class="notice notice-success"><p>%s</p></div>', \esc_html( \sprintf( \__( '%d pages mises à jour depuis les fichiers du thème.', 'g2rd-tcgforge-child' ), \absint( $_GET['refreshed'] ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	if ( isset( $_GET['seo'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- affichage seul.
		/* translators: %d: nombre de réglages. */
		\printf( '<div class="notice notice-success"><p>%s</p></div>', \esc_html( \sprintf( \__( '%d réglages SEOPress posés. Les valeurs déjà saisies sont conservées.', 'g2rd-tcgforge-child' ), \absint( $_GET['seo'] ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	echo '<h2>' . \esc_html__( 'Configurer SEOPress', 'g2rd-tcgforge-child' ) . '</h2>';
	echo '<p>' . \esc_html__( 'Pose les réglages globaux : titre et description de l\'accueil, modèles de titres, sitemap XML (pages, guides, produits, catégories), archives d\'auteur, de date et d\'étiquettes hors index, Open Graph, panier, commande et compte hors index. Une valeur déjà saisie dans SEOPress n\'est pas remplacée.', 'g2rd-tcgforge-child' ) . '</p>';
	echo '<form method="post" action="' . \esc_url( \admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="tcgforge_configure_seopress">';
	\wp_nonce_field( 'tcgforge_configure_seopress' );
	\submit_button( \__( 'Configurer SEOPress', 'g2rd-tcgforge-child' ), 'secondary' );
	echo '</form>';

	echo '<h2>' . \esc_html__( 'Mettre à jour les pages légales', 'g2rd-tcgforge-child' ) . '</h2>';
	echo '<p>' . \esc_html__( 'Remplace le titre et le contenu des pages cochées par la version des fichiers du thème. Le texte actuel reste disponible dans les révisions de chaque page. Les champs entre crochets sont à compléter ensuite.', 'g2rd-tcgforge-child' ) . '</p>';
	echo '<form method="post" action="' . \esc_url( \admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="tcgforge_refresh_content">';
	\wp_nonce_field( 'tcgforge_refresh_content' );
	echo '<p>';
	foreach ( refreshable_content() as $key ) {
		$item = content_map()[ $key ] ?? null;
		if ( $item && find_content( $item['slug'], $item['type'] ) ) {
			echo '<label><input type="checkbox" name="tcgforge_keys[]" value="' . \esc_attr( $key ) . '" checked> ' . \esc_html( $item['title'] ) . '</label><br>';
		}
	}
	echo '</p><p><label><input type="checkbox" name="tcgforge_confirm" value="1" required> ' . \esc_html__( 'Je remplace le contenu actuel de ces pages.', 'g2rd-tcgforge-child' ) . '</label></p>';
	\submit_button( \__( 'Mettre à jour les pages cochées', 'g2rd-tcgforge-child' ), 'secondary' );
	echo '</form>';

	echo '<h2>' . \esc_html__( 'Plan des contenus', 'g2rd-tcgforge-child' ) . '</h2>';
	echo '<table class="widefat striped"><thead><tr><th>' . \esc_html__( 'Fichier', 'g2rd-tcgforge-child' ) . '</th><th>' . \esc_html__( 'Titre', 'g2rd-tcgforge-child' ) . '</th><th>' . \esc_html__( 'Slug', 'g2rd-tcgforge-child' ) . '</th><th>' . \esc_html__( 'Type', 'g2rd-tcgforge-child' ) . '</th><th>' . \esc_html__( 'Modèle', 'g2rd-tcgforge-child' ) . '</th><th>' . \esc_html__( 'État', 'g2rd-tcgforge-child' ) . '</th></tr></thead><tbody>';
	foreach ( content_map() as $file => $item ) {
		$existing = find_content( $item['slug'], $item['type'] );
		$parent   = structure_map()[ $item['slug'] ] ?? '';
		$item['slug'] = 'post' === $item['type'] ? 'conseils/' . $item['slug'] : ( $parent ? $parent . '/' . $item['slug'] : $item['slug'] );
		\printf(
			'<tr><td><code>%1$s.html</code></td><td>%2$s</td><td><code>/%3$s/</code></td><td>%4$s</td><td>%5$s</td><td>%6$s</td></tr>',
			\esc_html( $file ),
			\esc_html( $item['title'] ),
			\esc_html( $item['slug'] ),
			'post' === $item['type'] ? \esc_html__( 'Article', 'g2rd-tcgforge-child' ) : \esc_html__( 'Page', 'g2rd-tcgforge-child' ),
			\esc_html( $item['template'] ?? '' ),
			$existing ? \esc_html__( 'Existe', 'g2rd-tcgforge-child' ) : \esc_html__( 'À créer', 'g2rd-tcgforge-child' )
		);
	}
	echo '</tbody></table></div>';
}

/**
 * Pages dont le texte suit les fichiers du thème : contenus légaux et de service.
 *
 * @return string[] Clés de content_map().
 */
function refreshable_content(): array {
	return array( 'mentions-legales', 'cgv', 'confidentialite', 'cookies', 'retractation', 'formulaire-retractation', 'conditions-precommandes', 'accessibilite', 'livraison', 'retours-remboursements', 'precommandes', 'contact' );
}

/**
 * Remplace le contenu de pages existantes par celui des fichiers du thème.
 * WordPress garde une révision : l'ancien texte reste récupérable.
 *
 * @param string[] $keys Clés de content_map() à mettre à jour.
 * @return int Nombre de pages mises à jour.
 */
function refresh_content( array $keys ): int {
	$map   = content_map();
	$dir   = \get_stylesheet_directory() . '/gutenberg-pages/';
	$count = 0;

	foreach ( \array_intersect( $keys, refreshable_content() ) as $key ) {
		$item = $map[ $key ] ?? null;
		$post = $item ? find_content( $item['slug'], $item['type'] ) : null;
		if ( ! $post || ! \is_readable( $dir . $key . '.html' ) ) {
			continue;
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- fichier local du thème.
		$content = (string) \file_get_contents( $dir . $key . '.html' );
		$done    = \wp_update_post(
			array(
				'ID'           => $post->ID,
				'post_title'   => $item['title'],
				'post_content' => \wp_slash( $content ),
			)
		);
		$count  += $done ? 1 : 0;
	}
	return $count;
}

\add_action(
	'admin_post_tcgforge_refresh_content',
	function (): void {
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( \esc_html__( 'Action non autorisée.', 'g2rd-tcgforge-child' ), 403 );
		}
		\check_admin_referer( 'tcgforge_refresh_content' );
		$keys  = isset( $_POST['tcgforge_keys'] ) ? \array_map( 'sanitize_key', (array) \wp_unslash( $_POST['tcgforge_keys'] ) ) : array();
		$count = empty( $_POST['tcgforge_confirm'] ) ? 0 : refresh_content( $keys );
		\wp_safe_redirect( \admin_url( 'themes.php?page=tcgforge-installation&refreshed=' . $count ) );
		exit;
	}
);

/**
 * Réglages globaux de SEOPress : titres, sitemap, archives, réseaux sociaux.
 * Une valeur déjà saisie dans SEOPress n'est jamais remplacée.
 *
 * @return int Nombre de réglages posés.
 */
function configure_seopress(): int {
	$name  = 'TCGForge';
	$desc  = 'Boutique française de cartes à collectionner : boosters, displays et coffrets Pokémon, Yu-Gi-Oh!, One Piece, Magic et Lorcana, accessoires TCG et figurines.';
	$count = 0;
	$fill  = static function ( array &$target, array $values ) use ( &$fill, &$count ): void {
		foreach ( $values as $key => $value ) {
			if ( \is_array( $value ) ) {
				if ( ! isset( $target[ $key ] ) || ! \is_array( $target[ $key ] ) ) {
					$target[ $key ] = array();
				}
				$fill( $target[ $key ], $value );
			} elseif ( ! isset( $target[ $key ] ) || '' === $target[ $key ] ) {
				$target[ $key ] = $value;
				++$count;
			}
		}
	};

	$sets = array(
		'seopress_titles_option_name'      => array(
			'seopress_titles_sep'                    => '|',
			'seopress_titles_home_site_title'        => 'TCGForge | Cartes à collectionner, accessoires TCG et figurines',
			'seopress_titles_home_site_desc'         => $desc,
			'seopress_titles_single_titles'          => array(
				'post'    => array( 'title' => '%%post_title%% %%sep%% %%sitetitle%%', 'description' => '%%post_excerpt%%' ),
				'page'    => array( 'title' => '%%post_title%% %%sep%% %%sitetitle%%', 'description' => '%%post_excerpt%%' ),
				'product' => array( 'title' => '%%post_title%% %%sep%% %%sitetitle%%', 'description' => '%%post_excerpt%%' ),
			),
			'seopress_titles_tax_titles'             => array(
				'product_cat' => array( 'title' => '%%term_title%% %%sep%% %%sitetitle%%', 'description' => '%%_category_description%%' ),
				'category'    => array( 'title' => '%%_category_title%% %%sep%% %%sitetitle%%', 'description' => '%%_category_description%%' ),
				'post_tag'    => array( 'noindex' => '1' ),
				'product_tag' => array( 'noindex' => '1' ),
			),
			'seopress_titles_archives_author_noindex' => '1',
			'seopress_titles_archives_date_noindex'   => '1',
			'seopress_titles_archives_search_title_noindex' => '1',
			'seopress_titles_paged_rel'               => '1',
		),
		'seopress_xml_sitemap_option_name' => array(
			'seopress_xml_sitemap_general_enable'  => '1',
			'seopress_xml_sitemap_img_enable'      => '1',
			'seopress_xml_sitemap_post_types_list' => array(
				'post'    => array( 'include' => '1' ),
				'page'    => array( 'include' => '1' ),
				'product' => array( 'include' => '1' ),
			),
			'seopress_xml_sitemap_taxonomies_list' => array(
				'category'    => array( 'include' => '1' ),
				'product_cat' => array( 'include' => '1' ),
			),
		),
		'seopress_social_option_name'      => array(
			'seopress_social_knowledge_type'  => 'Organization',
			'seopress_social_knowledge_name'  => $name,
			'seopress_social_facebook_og'     => '1',
			'seopress_social_twitter_card'    => '1',
			'seopress_social_twitter_card_img_size' => 'large',
		),
		'seopress_toggle'                  => array(
			'toggle-titles'      => '1',
			'toggle-xml-sitemap' => '1',
			'toggle-social'      => '1',
		),
	);

	foreach ( $sets as $option => $values ) {
		$current = \get_option( $option );
		$current = \is_array( $current ) ? $current : array();
		$fill( $current, $values );
		\update_option( $option, $current );
	}

	// Pages du tunnel : hors index.
	foreach ( array( 'woocommerce_cart_page_id', 'woocommerce_checkout_page_id', 'woocommerce_myaccount_page_id' ) as $option ) {
		$page_id = (int) \get_option( $option );
		if ( $page_id && 'yes' !== \get_post_meta( $page_id, '_seopress_robots_index', true ) ) {
			\update_post_meta( $page_id, '_seopress_robots_index', 'yes' );
			++$count;
		}
	}

	// Titre SEO resté sur l'ancien nom de la page de rétractation.
	$page = find_content( 'formulaire-de-retractation', 'page' );
	if ( $page && 0 === \strpos( (string) \get_post_meta( $page->ID, '_seopress_titles_title', true ), 'Formulaire de rétractation' ) ) {
		$seo = seo_map()['formulaire-de-retractation'];
		\update_post_meta( $page->ID, '_seopress_titles_title', \wp_slash( $seo[0] ) );
		\update_post_meta( $page->ID, '_seopress_titles_desc', \wp_slash( $seo[1] ) );
		++$count;
	}

	\flush_rewrite_rules( false );
	return $count;
}

\add_action(
	'admin_post_tcgforge_configure_seopress',
	function (): void {
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( \esc_html__( 'Action non autorisée.', 'g2rd-tcgforge-child' ), 403 );
		}
		\check_admin_referer( 'tcgforge_configure_seopress' );
		\wp_safe_redirect( \admin_url( 'themes.php?page=tcgforge-installation&seo=' . configure_seopress() ) );
		exit;
	}
);

/**
 * Le thème parent recopie en base ses propres modèles et éléments (header,
 * footer, page…) sous le nom du thème actif. Avec un thème enfant, ces copies
 * masquent les fichiers de l'enfant. On retire les copies restées identiques
 * au fichier du parent : une version modifiée dans l'éditeur de site est gardée.
 *
 * @return int Nombre de copies retirées.
 */
function purge_parent_template_copies(): int {
	$removed = 0;
	$posts   = \get_posts(
		array(
			'post_type'      => array( 'wp_template', 'wp_template_part' ),
			'post_status'    => array( 'publish', 'draft', 'auto-draft' ),
			'posts_per_page' => -1,
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'wp_theme',
					'field'    => 'name',
					'terms'    => array( \get_stylesheet() ),
				),
			),
		)
	);
	$clean   = static function ( string $html ): string {
		return (string) \preg_replace( '~[\\\\\s]+~', '', $html );
	};

	foreach ( $posts as $post ) {
		$dir  = 'wp_template' === $post->post_type ? 'templates' : 'parts';
		$file = \get_template_directory() . '/' . $dir . '/' . $post->post_name . '.html';
		if ( ! \is_readable( $file ) ) {
			continue;
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- fichier local du thème parent.
		if ( $clean( (string) \file_get_contents( $file ) ) === $clean( $post->post_content ) ) {
			\wp_delete_post( $post->ID, true );
			++$removed;
		}
	}

	if ( $removed ) {
		\wp_clean_themes_cache();
	}
	return $removed;
}

\add_action(
	'admin_init',
	function (): void {
		$mark = \get_option( 'g2rd_recreate_version' ) . '|' . VERSION;
		if ( $mark !== \get_option( 'tcgforge_fse_clean' ) && \current_user_can( 'manage_options' ) && ! \wp_doing_ajax() ) {
			purge_parent_template_copies();
			\update_option( 'tcgforge_fse_clean', $mark, false );
		}
	},
	25
);
