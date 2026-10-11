<?php
/**
 * Fonctionnalité de rétractation en ligne.
 *
 * Bloc « tcgforge/withdrawal » : le client déclare sa rétractation en deux
 * écrans (saisie, puis récapitulatif et bouton « Confirmer la rétractation »),
 * sans compte et sans motif. Chaque notification est horodatée, enregistrée,
 * envoyée à la boutique et confirmée au client par e-mail.
 *
 * La notification ne déclenche aucun remboursement : la boutique contrôle
 * ensuite les conditions (délai, produits, retour).
 *
 * @package G2RD_TCGForge
 */

declare( strict_types = 1 );

namespace G2RD_TCGForge;

\defined( 'ABSPATH' ) || exit;

const WITHDRAWAL_CPT = 'tcgf_withdrawal';

/**
 * Adresse de la page qui porte la fonctionnalité.
 *
 * @return string
 */
function withdrawal_url(): string {
	$page = \function_exists( __NAMESPACE__ . '\find_content' ) ? find_content( 'formulaire-de-retractation', 'page' ) : null;
	$url  = $page ? (string) \get_permalink( $page ) : \home_url( '/informations-legales/formulaire-de-retractation/' );
	return (string) \apply_filters( 'tcgforge_withdrawal_url', $url );
}

/**
 * Destinataire interne des notifications.
 *
 * @return string
 */
function withdrawal_recipient(): string {
	return (string) \apply_filters( 'tcgforge_withdrawal_recipient', (string) \get_option( 'admin_email' ) );
}

/**
 * Empreinte de l'adresse IP : sert au seul plafond d'envois, jamais stockée en clair.
 *
 * @return string
 */
function withdrawal_ip_hash(): string {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? \sanitize_text_field( \wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	return \substr( \wp_hash( 'tcgf-wd|' . $ip ), 0, 20 );
}

/**
 * Plafond d'envois par adresse IP et par heure.
 *
 * @param string $bucket Nom du compteur.
 * @param int    $max    Plafond.
 * @return bool Vrai si l'envoi est accepté.
 */
function withdrawal_rate_ok( string $bucket, int $max ): bool {
	$key   = 'tcgf_wd_' . $bucket . '_' . withdrawal_ip_hash();
	$count = (int) \get_transient( $key );
	if ( $count >= $max ) {
		return false;
	}
	\set_transient( $key, $count + 1, HOUR_IN_SECONDS );
	return true;
}

/**
 * Type de contenu interne : registre des notifications.
 */
\add_action(
	'init',
	function (): void {
		$cap = \class_exists( 'WooCommerce' ) ? 'manage_woocommerce' : 'manage_options';
		\register_post_type(
			WITHDRAWAL_CPT,
			array(
				'labels'              => array(
					'name'          => \__( 'Rétractations', 'g2rd-tcgforge-child' ),
					'singular_name' => \__( 'Rétractation', 'g2rd-tcgforge-child' ),
					'edit_item'     => \__( 'Notification de rétractation', 'g2rd-tcgforge-child' ),
					'search_items'  => \__( 'Rechercher une rétractation', 'g2rd-tcgforge-child' ),
					'not_found'     => \__( 'Aucune notification reçue.', 'g2rd-tcgforge-child' ),
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => \class_exists( 'WooCommerce' ) ? 'woocommerce' : true,
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'supports'            => false,
				'map_meta_cap'        => false,
				'capabilities'        => array(
					'edit_post'          => $cap,
					'read_post'          => $cap,
					'delete_post'        => $cap,
					'edit_posts'         => $cap,
					'edit_others_posts'  => $cap,
					'delete_posts'       => $cap,
					'publish_posts'      => $cap,
					'read_private_posts' => $cap,
					'create_posts'       => 'do_not_allow',
				),
			)
		);

		\register_block_type(
			'tcgforge/withdrawal',
			array(
				'api_version'     => 3,
				'title'           => \__( 'TCGForge — Rétractation en ligne', 'g2rd-tcgforge-child' ),
				'category'        => 'woocommerce',
				'icon'            => 'undo',
				'render_callback' => __NAMESPACE__ . '\render_withdrawal',
				'supports'        => array(
					'html'     => false,
					'multiple' => false,
				),
			)
		);
	}
);

/**
 * Champs du formulaire : clé → libellé, type, obligatoire, longueur maximale.
 *
 * @return array<string,array<string,mixed>>
 */
function withdrawal_fields(): array {
	return array(
		'prenom'   => array( 'label' => \__( 'Prénom', 'g2rd-tcgforge-child' ), 'type' => 'text', 'required' => true, 'max' => 80, 'auto' => 'given-name' ),
		'nom'      => array( 'label' => \__( 'Nom', 'g2rd-tcgforge-child' ), 'type' => 'text', 'required' => true, 'max' => 80, 'auto' => 'family-name' ),
		'email'    => array( 'label' => \__( 'Adresse e-mail', 'g2rd-tcgforge-child' ), 'type' => 'email', 'required' => true, 'max' => 190, 'auto' => 'email', 'help' => \__( 'L\'accusé de réception est envoyé à cette adresse.', 'g2rd-tcgforge-child' ) ),
		'commande' => array( 'label' => \__( 'Numéro de commande', 'g2rd-tcgforge-child' ), 'type' => 'text', 'required' => true, 'max' => 40, 'auto' => 'off', 'help' => \__( 'Il figure dans l\'e-mail de confirmation de commande.', 'g2rd-tcgforge-child' ) ),
		'produits' => array( 'label' => \__( 'Produits concernés', 'g2rd-tcgforge-child' ), 'type' => 'textarea', 'required' => false, 'max' => 1500, 'auto' => 'off', 'help' => \__( 'Facultatif. Laissez vide pour vous rétracter de toute la commande.', 'g2rd-tcgforge-child' ) ),
	);
}

/**
 * Lit et nettoie les champs envoyés.
 *
 * @return array{0:array<string,string>,1:array<string,string>} Valeurs, erreurs.
 */
function withdrawal_read_post(): array {
	$values = array();
	$errors = array();
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce vérifié par l'appelant.
	foreach ( withdrawal_fields() as $key => $field ) {
		$raw = isset( $_POST[ 'tcgf_' . $key ] ) ? \wp_unslash( $_POST[ 'tcgf_' . $key ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nettoyé juste après.
		$raw = \is_string( $raw ) ? $raw : '';
		if ( 'email' === $field['type'] ) {
			$value = \sanitize_email( $raw );
		} elseif ( 'textarea' === $field['type'] ) {
			$value = \sanitize_textarea_field( $raw );
		} else {
			$value = \sanitize_text_field( $raw );
		}
		$value          = \function_exists( 'mb_substr' ) ? \mb_substr( $value, 0, (int) $field['max'] ) : \substr( $value, 0, (int) $field['max'] );
		$values[ $key ] = $value;

		if ( 'email' === $field['type'] && '' === $value && '' !== \trim( $raw ) ) {
			$errors[ $key ] = \__( 'L\'adresse e-mail n\'est pas valide. Exemple : prenom@exemple.fr', 'g2rd-tcgforge-child' );
		} elseif ( $field['required'] && '' === $value ) {
			/* translators: %s: libellé du champ. */
			$errors[ $key ] = \sprintf( \__( 'Le champ « %s » est obligatoire.', 'g2rd-tcgforge-child' ), $field['label'] );
		}
	}
	// phpcs:enable
	if ( '' !== $values['email'] && ! \is_email( $values['email'] ) ) {
		$errors['email'] = \__( 'L\'adresse e-mail n\'est pas valide. Exemple : prenom@exemple.fr', 'g2rd-tcgforge-child' );
	}
	return array( $values, $errors );
}

/**
 * Adresse de retour vers la page du formulaire.
 *
 * @param array<string,string> $args Paramètres à ajouter.
 * @return string
 */
function withdrawal_back( array $args ): string {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- adresse validée par wp_validate_redirect.
	$ref = isset( $_POST['tcgf_ref'] ) ? \esc_url_raw( \wp_unslash( $_POST['tcgf_ref'] ) ) : '';
	$ref = \wp_validate_redirect( $ref, withdrawal_url() );
	$ref = \remove_query_arg( array( 'tcgf_wd', 'k', 'commande' ), $ref );
	return \add_query_arg( $args, $ref ) . '#retractation';
}

/**
 * Étape 1 : contrôle de la saisie, puis écran de confirmation.
 */
function withdrawal_handle_review(): void {
	if ( ! isset( $_POST['_tcgf_nonce'] ) || ! \wp_verify_nonce( \sanitize_key( \wp_unslash( $_POST['_tcgf_nonce'] ) ), 'tcgf_withdrawal' ) ) {
		\wp_safe_redirect( withdrawal_back( array( 'tcgf_wd' => 'expire' ) ) );
		exit;
	}

	list( $values, $errors ) = withdrawal_read_post();
	$key                     = \wp_generate_password( 24, false );

	// Pot de miel et délai minimal : un envoi automatique est écarté sans message.
	$trap = isset( $_POST['tcgf_site'] ) ? \sanitize_text_field( \wp_unslash( $_POST['tcgf_site'] ) ) : '';
	$time = isset( $_POST['tcgf_t'] ) ? \sanitize_text_field( \wp_unslash( $_POST['tcgf_t'] ) ) : '';
	$sign = isset( $_POST['tcgf_s'] ) ? \sanitize_text_field( \wp_unslash( $_POST['tcgf_s'] ) ) : '';
	$bot  = '' !== $trap || ! \hash_equals( \wp_hash( 'tcgf-wd-t|' . $time ), $sign ) || ( \time() - (int) $time ) < 3;

	if ( $bot || ! withdrawal_rate_ok( 'review', 20 ) ) {
		$errors['global'] = \__( 'L\'envoi n\'a pas abouti. Patientez quelques secondes, puis réessayez. En cas de blocage, écrivez-nous par e-mail : votre rétractation reste valable par ce moyen.', 'g2rd-tcgforge-child' );
	}

	if ( $errors ) {
		\set_transient( 'tcgf_wd_e_' . $key, array( 'values' => $values, 'errors' => $errors ), 10 * MINUTE_IN_SECONDS );
		\wp_safe_redirect( withdrawal_back( array( 'tcgf_wd' => 'erreur', 'k' => $key ) ) );
		exit;
	}

	\set_transient( 'tcgf_wd_p_' . $key, $values, 30 * MINUTE_IN_SECONDS );
	\wp_safe_redirect( withdrawal_back( array( 'tcgf_wd' => 'confirmer', 'k' => $key ) ) );
	exit;
}
\add_action( 'admin_post_nopriv_tcgforge_withdrawal_review', __NAMESPACE__ . '\withdrawal_handle_review' );
\add_action( 'admin_post_tcgforge_withdrawal_review', __NAMESPACE__ . '\withdrawal_handle_review' );

/**
 * Retrouve la commande WooCommerce et vérifie l'e-mail.
 *
 * @param string $number Numéro saisi.
 * @param string $email  E-mail saisi.
 * @return \WC_Order|null Commande si le numéro et l'e-mail concordent.
 */
function withdrawal_match_order( string $number, string $email ) {
	if ( ! \function_exists( 'wc_get_order' ) ) {
		return null;
	}
	$id    = (int) \apply_filters( 'woocommerce_shortcode_order_tracking_order_id', \absint( \preg_replace( '/\D+/', '', $number ) ) );
	$order = $id ? \wc_get_order( $id ) : null;
	if ( ! $order || ! \is_a( $order, 'WC_Order' ) ) {
		return null;
	}
	return \strtolower( (string) $order->get_billing_email() ) === \strtolower( $email ) ? $order : null;
}

/**
 * Texte de la déclaration.
 *
 * @param array<string,string> $v Valeurs.
 * @return string
 */
function withdrawal_statement( array $v ): string {
	$scope = '' !== $v['produits']
		? \__( 'Produits concernés :', 'g2rd-tcgforge-child' ) . "\n" . $v['produits']
		: \__( 'Produits concernés : toute la commande.', 'g2rd-tcgforge-child' );

	return \sprintf(
		/* translators: 1: prénom et nom, 2: numéro de commande, 3: produits concernés, 4: e-mail. */
		\__( "Je soussigné(e) %1\$s notifie par la présente ma rétractation du contrat de vente portant sur la commande n° %2\$s.\n%3\$s\nAdresse e-mail : %4\$s", 'g2rd-tcgforge-child' ),
		\trim( $v['prenom'] . ' ' . $v['nom'] ),
		$v['commande'],
		$scope,
		$v['email']
	);
}

/**
 * Étape 2 : enregistrement, notification interne, accusé de réception.
 */
function withdrawal_handle_confirm(): void {
	$key = isset( $_POST['tcgf_k'] ) ? \preg_replace( '/[^A-Za-z0-9]/', '', (string) \wp_unslash( $_POST['tcgf_k'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- filtré par l'expression régulière.

	if ( ! isset( $_POST['_tcgf_nonce'] ) || ! \wp_verify_nonce( \sanitize_key( \wp_unslash( $_POST['_tcgf_nonce'] ) ), 'tcgf_withdrawal_confirm_' . $key ) ) {
		\wp_safe_redirect( withdrawal_back( array( 'tcgf_wd' => 'expire' ) ) );
		exit;
	}

	$values = \get_transient( 'tcgf_wd_p_' . $key );
	if ( ! \is_array( $values ) || ! withdrawal_rate_ok( 'confirm', 5 ) ) {
		\wp_safe_redirect( withdrawal_back( array( 'tcgf_wd' => 'expire' ) ) );
		exit;
	}
	\delete_transient( 'tcgf_wd_p_' . $key );

	$now       = \time();
	$when      = \wp_date( 'd/m/Y à H:i:s (T)', $now );
	$statement = withdrawal_statement( $values );
	$order     = withdrawal_match_order( $values['commande'], $values['email'] );

	$post_id = \wp_insert_post(
		array(
			'post_type'    => WITHDRAWAL_CPT,
			'post_status'  => 'private',
			'post_title'   => \sprintf( 'Commande %s — %s', $values['commande'], \trim( $values['prenom'] . ' ' . $values['nom'] ) ),
			'post_content' => \wp_slash( $statement ),
			'post_date'    => \wp_date( 'Y-m-d H:i:s', $now ),
		)
	);

	if ( ! $post_id || \is_wp_error( $post_id ) ) {
		\set_transient( 'tcgf_wd_p_' . $key, $values, 30 * MINUTE_IN_SECONDS );
		\wp_safe_redirect( withdrawal_back( array( 'tcgf_wd' => 'echec', 'k' => $key ) ) );
		exit;
	}

	$ref = 'RET-' . \wp_date( 'Ymd', $now ) . '-' . $post_id;
	\wp_update_post( array( 'ID' => $post_id, 'post_title' => $ref . ' — commande ' . $values['commande'] ) );
	foreach ( $values as $name => $value ) {
		\update_post_meta( $post_id, '_tcgf_wd_' . $name, \wp_slash( $value ) );
	}
	\update_post_meta( $post_id, '_tcgf_wd_ref', $ref );
	\update_post_meta( $post_id, '_tcgf_wd_time', $now );
	\update_post_meta( $post_id, '_tcgf_wd_order_id', $order ? $order->get_id() : 0 );

	if ( $order ) {
		/* translators: 1: référence, 2: date et heure. */
		$order->add_order_note( \sprintf( \__( 'Notification de rétractation %1$s reçue le %2$s. À contrôler : délai, produits concernés, retour. Aucun remboursement automatique.', 'g2rd-tcgforge-child' ), $ref, $when ) );
	}

	$site    = \wp_specialchars_decode( (string) \get_bloginfo( 'name' ), ENT_QUOTES );
	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

	// Accusé de réception au client : support durable, contenu et horodatage.
	$ack = \sprintf(
		/* translators: 1: prénom, 2: site, 3: date et heure, 4: référence, 5: déclaration, 6: page retours. */
		\__( "Bonjour %1\$s,\n\n%2\$s accuse réception de votre notification de rétractation.\n\nReçue le : %3\$s\nRéférence : %4\$s\n\nContenu de votre déclaration :\n----------\n%5\$s\n----------\n\nSuite de la démarche :\n- Renvoyez les produits au plus tard quatorze jours après cette notification.\n- L'adresse et les modalités de retour figurent ici : %6\$s\n- Le remboursement intervient au plus tard quatorze jours après cette notification. Nous sommes en droit de le différer jusqu'à la réception des produits ou d'une preuve de leur expédition.\n\nConservez cet e-mail : il atteste de la date de votre rétractation.\n\n%2\$s", 'g2rd-tcgforge-child' ),
		$values['prenom'],
		$site,
		$when,
		$ref,
		$statement,
		\home_url( '/aide/retours-remboursements/' )
	);
	/* translators: 1: site, 2: référence. */
	$sent = \wp_mail( $values['email'], \sprintf( \__( '[%1$s] Accusé de réception de votre rétractation %2$s', 'g2rd-tcgforge-child' ), $site, $ref ), $ack, $headers );
	\update_post_meta( $post_id, '_tcgf_wd_ack_sent', $sent ? 1 : 0 );

	// Notification interne.
	$admin = \sprintf(
		"Notification de rétractation %1\$s\nReçue le : %2\$s\nCommande WooCommerce : %3\$s\nAccusé de réception client : %4\$s\n\n%5\$s\n\nFiche : %6\$s\n\nÀ faire : contrôler le délai de quatorze jours et les produits, puis traiter le retour et le remboursement. Aucun remboursement n'a été déclenché.",
		$ref,
		$when,
		$order ? 'trouvée, e-mail concordant (n° ' . $order->get_order_number() . ')' : 'non trouvée ou e-mail différent : à vérifier à la main',
		$sent ? 'envoyé' : 'ÉCHEC D\'ENVOI : à renvoyer à la main',
		$statement,
		\admin_url( 'post.php?post=' . $post_id . '&action=edit' )
	);
	\wp_mail( withdrawal_recipient(), \sprintf( '[%1$s] Rétractation %2$s — commande %3$s', $site, $ref, $values['commande'] ), $admin, $headers );

	\set_transient(
		'tcgf_wd_d_' . $key,
		array(
			'ref'   => $ref,
			'when'  => $when,
			'email' => $values['email'],
			'sent'  => $sent,
			'text'  => $statement,
		),
		30 * MINUTE_IN_SECONDS
	);
	\wp_safe_redirect( withdrawal_back( array( 'tcgf_wd' => 'envoye', 'k' => $key ) ) );
	exit;
}
\add_action( 'admin_post_nopriv_tcgforge_withdrawal_confirm', __NAMESPACE__ . '\withdrawal_handle_confirm' );
\add_action( 'admin_post_tcgforge_withdrawal_confirm', __NAMESPACE__ . '\withdrawal_handle_confirm' );

/**
 * Rendu du bloc.
 *
 * @return string
 */
function render_withdrawal(): string {
	if ( \is_admin() || ( \defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return '<p class="tcgf-editor-placeholder">' . \esc_html__( 'Rétractation en ligne : le formulaire s\'affiche sur le site.', 'g2rd-tcgforge-child' ) . '</p>';
	}

	// La page porte un jeton propre au visiteur : elle ne doit pas être mise en cache.
	if ( ! \defined( 'DONOTCACHEPAGE' ) ) {
		\define( 'DONOTCACHEPAGE', true );
	}
	\do_action( 'litespeed_control_set_nocache', 'tcgforge withdrawal' );

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- lecture d'état, aucune écriture.
	$state = isset( $_GET['tcgf_wd'] ) ? \sanitize_key( \wp_unslash( $_GET['tcgf_wd'] ) ) : '';
	$key   = isset( $_GET['k'] ) ? \preg_replace( '/[^A-Za-z0-9]/', '', (string) \wp_unslash( $_GET['k'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$pre   = isset( $_GET['commande'] ) ? \sanitize_text_field( \wp_unslash( $_GET['commande'] ) ) : '';
	// phpcs:enable

	$action = \esc_url( \admin_url( 'admin-post.php' ) );
	$ref    = \esc_url( (string) \get_permalink() );
	$out    = '<section id="retractation" class="tcgf-withdrawal" aria-labelledby="tcgf-wd-title">';

	// Écran final.
	if ( 'envoye' === $state ) {
		$done = \get_transient( 'tcgf_wd_d_' . $key );
		$out .= '<div class="tcgf-withdrawal__done" role="status" tabindex="-1">';
		$out .= '<h2 id="tcgf-wd-title">' . \esc_html__( 'Votre rétractation est enregistrée', 'g2rd-tcgforge-child' ) . '</h2>';
		if ( \is_array( $done ) ) {
			$out .= '<p>' . \sprintf(
				/* translators: 1: référence, 2: date et heure. */
				\esc_html__( 'Référence %1$s, reçue le %2$s.', 'g2rd-tcgforge-child' ),
				'<strong>' . \esc_html( $done['ref'] ) . '</strong>',
				'<strong>' . \esc_html( $done['when'] ) . '</strong>'
			) . '</p>';
			$out .= $done['sent']
				/* translators: %s: e-mail. */
				? '<p>' . \sprintf( \esc_html__( 'Un accusé de réception vient d\'être envoyé à %s. Conservez-le.', 'g2rd-tcgforge-child' ), \esc_html( $done['email'] ) ) . '</p>'
				: '<p><strong>' . \esc_html__( 'L\'e-mail d\'accusé de réception n\'a pas pu partir. Votre rétractation est bien enregistrée : notez la référence ci-dessus ou imprimez cette page. Nous vous renvoyons l\'accusé de réception à la main.', 'g2rd-tcgforge-child' ) . '</strong></p>';
			$out .= '<pre class="tcgf-withdrawal__text">' . \esc_html( $done['text'] ) . '</pre>';
		}
		$out .= '<p><a href="' . \esc_url( \home_url( '/aide/retours-remboursements/' ) ) . '">' . \esc_html__( 'Voir comment renvoyer les produits', 'g2rd-tcgforge-child' ) . '</a></p>';
		return $out . '</div></section>';
	}

	// Écran de confirmation.
	$pending = 'confirmer' === $state || 'echec' === $state ? \get_transient( 'tcgf_wd_p_' . $key ) : false;
	if ( \is_array( $pending ) ) {
		$out .= '<h2 id="tcgf-wd-title">' . \esc_html__( 'Vérifiez, puis confirmez votre rétractation', 'g2rd-tcgforge-child' ) . '</h2>';
		if ( 'echec' === $state ) {
			$out .= '<p class="tcgf-withdrawal__error" role="alert">' . \esc_html__( 'L\'enregistrement a échoué. Réessayez. Si l\'erreur persiste, écrivez-nous par e-mail : votre rétractation reste valable par ce moyen.', 'g2rd-tcgforge-child' ) . '</p>';
		}
		$out .= '<p>' . \esc_html__( 'Rien n\'est encore envoyé. Voici la déclaration qui nous sera transmise :', 'g2rd-tcgforge-child' ) . '</p>';
		$out .= '<pre class="tcgf-withdrawal__text">' . \esc_html( withdrawal_statement( $pending ) ) . '</pre>';
		$out .= '<form method="post" action="' . $action . '" class="tcgf-withdrawal__form">';
		$out .= '<input type="hidden" name="action" value="tcgforge_withdrawal_confirm">';
		$out .= '<input type="hidden" name="tcgf_k" value="' . \esc_attr( $key ) . '">';
		$out .= '<input type="hidden" name="tcgf_ref" value="' . $ref . '">';
		$out .= \wp_nonce_field( 'tcgf_withdrawal_confirm_' . $key, '_tcgf_nonce', false, false );
		$out .= '<div class="tcgf-withdrawal__actions"><button type="submit" class="wp-element-button tcgf-withdrawal__submit">' . \esc_html__( 'Confirmer la rétractation', 'g2rd-tcgforge-child' ) . '</button>';
		$out .= '<a href="' . \esc_url( \remove_query_arg( array( 'tcgf_wd', 'k' ) ) ) . '#retractation">' . \esc_html__( 'Modifier ma saisie', 'g2rd-tcgforge-child' ) . '</a></div>';
		return $out . '</form></section>';
	}

	// Écran de saisie.
	$values = array( 'prenom' => '', 'nom' => '', 'email' => '', 'commande' => $pre, 'produits' => '' );
	$errors = array();
	if ( 'erreur' === $state ) {
		$saved = \get_transient( 'tcgf_wd_e_' . $key );
		if ( \is_array( $saved ) ) {
			$values = \array_merge( $values, (array) $saved['values'] );
			$errors = (array) $saved['errors'];
		}
	} elseif ( 'expire' === $state || 'confirmer' === $state ) {
		$errors['global'] = \__( 'La session a expiré avant la confirmation. Rien n\'a été envoyé. Remplissez de nouveau le formulaire.', 'g2rd-tcgforge-child' );
	}
	if ( \is_user_logged_in() && '' === $values['email'] ) {
		$user             = \wp_get_current_user();
		$values['email']  = (string) $user->user_email;
		$values['prenom'] = (string) $user->first_name;
		$values['nom']    = (string) $user->last_name;
	}

	$out .= '<h2 id="tcgf-wd-title">' . \esc_html__( 'Renoncer au contrat ici', 'g2rd-tcgforge-child' ) . '</h2>';
	$out .= '<p>' . \esc_html__( 'Aucun compte et aucun motif ne sont demandés. Les champs marqués d\'un astérisque sont obligatoires.', 'g2rd-tcgforge-child' ) . '</p>';

	if ( $errors ) {
		$out .= '<div class="tcgf-withdrawal__error" role="alert" tabindex="-1"><p>' . \esc_html__( 'Le formulaire n\'a pas été envoyé :', 'g2rd-tcgforge-child' ) . '</p><ul>';
		foreach ( $errors as $name => $message ) {
			$out .= '<li>' . ( 'global' === $name ? \esc_html( $message ) : '<a href="#tcgf_' . \esc_attr( $name ) . '">' . \esc_html( $message ) . '</a>' ) . '</li>';
		}
		$out .= '</ul></div>';
	}

	$time = (string) \time();
	$out .= '<form method="post" action="' . $action . '" class="tcgf-withdrawal__form" novalidate>';
	$out .= '<input type="hidden" name="action" value="tcgforge_withdrawal_review">';
	$out .= '<input type="hidden" name="tcgf_ref" value="' . $ref . '">';
	$out .= '<input type="hidden" name="tcgf_t" value="' . \esc_attr( $time ) . '"><input type="hidden" name="tcgf_s" value="' . \esc_attr( \wp_hash( 'tcgf-wd-t|' . $time ) ) . '">';
	$out .= \wp_nonce_field( 'tcgf_withdrawal', '_tcgf_nonce', false, false );
	$out .= '<p class="tcgf-withdrawal__trap" aria-hidden="true"><label for="tcgf_site">Site web</label><input type="text" id="tcgf_site" name="tcgf_site" value="" tabindex="-1" autocomplete="off"></p>';

	foreach ( withdrawal_fields() as $name => $field ) {
		$id    = 'tcgf_' . $name;
		$error = $errors[ $name ] ?? '';
		$desc  = array();
		$attrs = ' id="' . \esc_attr( $id ) . '" name="' . \esc_attr( $id ) . '" maxlength="' . (int) $field['max'] . '" autocomplete="' . \esc_attr( $field['auto'] ) . '"';
		$attrs .= $field['required'] ? ' required aria-required="true"' : '';
		if ( ! empty( $field['help'] ) ) {
			$desc[] = $id . '_aide';
		}
		if ( $error ) {
			$desc[] = $id . '_erreur';
			$attrs .= ' aria-invalid="true"';
		}
		$attrs .= $desc ? ' aria-describedby="' . \esc_attr( \implode( ' ', $desc ) ) . '"' : '';

		$out .= '<p class="tcgf-withdrawal__field"><label for="' . \esc_attr( $id ) . '">' . \esc_html( $field['label'] ) . ( $field['required'] ? ' <span aria-hidden="true">*</span>' : '' ) . '</label>';
		$out .= 'textarea' === $field['type']
			? '<textarea rows="4"' . $attrs . '>' . \esc_textarea( $values[ $name ] ) . '</textarea>'
			: '<input type="' . \esc_attr( $field['type'] ) . '"' . $attrs . ' value="' . \esc_attr( $values[ $name ] ) . '">';
		if ( ! empty( $field['help'] ) ) {
			$out .= '<span class="tcgf-withdrawal__help" id="' . \esc_attr( $id ) . '_aide">' . \esc_html( $field['help'] ) . '</span>';
		}
		if ( $error ) {
			$out .= '<span class="tcgf-withdrawal__field-error" id="' . \esc_attr( $id ) . '_erreur">' . \esc_html( $error ) . '</span>';
		}
		$out .= '</p>';
	}

	$out .= '<p class="tcgf-withdrawal__help">' . \sprintf(
		/* translators: %s: lien vers la politique de confidentialité. */
		\esc_html__( 'Ces données servent à enregistrer votre rétractation, à vous en accuser réception et à traiter le retour. Détail et droits : %s.', 'g2rd-tcgforge-child' ),
		'<a href="' . \esc_url( \home_url( '/informations-legales/politique-de-confidentialite/' ) ) . '">' . \esc_html__( 'politique de confidentialité', 'g2rd-tcgforge-child' ) . '</a>'
	) . '</p>';
	$out .= '<div class="tcgf-withdrawal__actions"><button type="submit" class="wp-element-button tcgf-withdrawal__submit">' . \esc_html__( 'Continuer vers la confirmation', 'g2rd-tcgforge-child' ) . '</button></div>';

	return $out . '</form></section>';
}

/**
 * Fiche d'une notification dans l'administration (lecture seule).
 */
\add_action(
	'add_meta_boxes_' . WITHDRAWAL_CPT,
	function (): void {
		\add_meta_box(
			'tcgf-wd',
			\__( 'Notification reçue', 'g2rd-tcgforge-child' ),
			function ( \WP_Post $post ): void {
				$order_id = (int) \get_post_meta( $post->ID, '_tcgf_wd_order_id', true );
				$time     = (int) \get_post_meta( $post->ID, '_tcgf_wd_time', true );
				echo '<p><strong>' . \esc_html( (string) \get_post_meta( $post->ID, '_tcgf_wd_ref', true ) ) . '</strong> — ' . \esc_html( $time ? \wp_date( 'd/m/Y à H:i:s (T)', $time ) : '' ) . '</p>';
				echo '<pre style="white-space:pre-wrap">' . \esc_html( $post->post_content ) . '</pre>';
				echo '<p>' . \esc_html__( 'Accusé de réception : ', 'g2rd-tcgforge-child' ) . ( \get_post_meta( $post->ID, '_tcgf_wd_ack_sent', true ) ? \esc_html__( 'envoyé', 'g2rd-tcgforge-child' ) : '<strong>' . \esc_html__( 'échec d\'envoi, à renvoyer à la main', 'g2rd-tcgforge-child' ) . '</strong>' ) . '</p>';
				if ( $order_id ) {
					echo '<p><a href="' . \esc_url( (string) \get_edit_post_link( $order_id ) ?: \admin_url( 'admin.php?page=wc-orders&action=edit&id=' . $order_id ) ) . '">' . \esc_html__( 'Ouvrir la commande', 'g2rd-tcgforge-child' ) . '</a></p>';
				} else {
					echo '<p><strong>' . \esc_html__( 'Commande non rapprochée : numéro inconnu ou e-mail différent. À vérifier à la main.', 'g2rd-tcgforge-child' ) . '</strong></p>';
				}
				echo '<p>' . \esc_html__( 'Aucun remboursement n\'est déclenché par cette notification.', 'g2rd-tcgforge-child' ) . '</p>';
			},
			WITHDRAWAL_CPT,
			'normal',
			'high'
		);
		\remove_meta_box( 'submitdiv', WITHDRAWAL_CPT, 'side' );
	}
);

/**
 * Accès permanent à la fonctionnalité : détail de commande et e-mails client.
 *
 * @param mixed $order Commande.
 * @return string Adresse préremplie, ou chaîne vide.
 */
function withdrawal_order_link( $order ): string {
	if ( ! \is_object( $order ) || ! \method_exists( $order, 'get_order_number' ) ) {
		return '';
	}
	return \add_query_arg( 'commande', \rawurlencode( (string) $order->get_order_number() ), withdrawal_url() ) . '#retractation';
}

\add_action(
	'woocommerce_order_details_after_order_table',
	function ( $order ): void {
		$url = withdrawal_order_link( $order );
		if ( $url ) {
			echo '<p class="tcgf-withdrawal-link"><a href="' . \esc_url( $url ) . '">' . \esc_html__( 'Renoncer au contrat ici', 'g2rd-tcgforge-child' ) . '</a> — ' . \esc_html__( 'droit de rétractation de quatorze jours à compter de la réception.', 'g2rd-tcgforge-child' ) . '</p>';
		}
	}
);

\add_action(
	'woocommerce_email_after_order_table',
	function ( $order, $sent_to_admin = false, $plain_text = false ): void {
		$url = $sent_to_admin ? '' : withdrawal_order_link( $order );
		if ( ! $url ) {
			return;
		}
		$text = \__( 'Droit de rétractation : quatorze jours à compter de la réception. Renoncer au contrat ici :', 'g2rd-tcgforge-child' );
		if ( $plain_text ) {
			echo "\n" . \esc_html( $text ) . ' ' . \esc_url( $url ) . "\n";
		} else {
			echo '<p>' . \esc_html( $text ) . ' <a href="' . \esc_url( $url ) . '">' . \esc_html( $url ) . '</a></p>';
		}
	},
	20,
	3
);

/**
 * Outils de confidentialité de WordPress : export des notifications d'une personne.
 * L'effacement les conserve : elles prouvent la date d'exercice d'un droit.
 */
\add_filter(
	'wp_privacy_personal_data_exporters',
	function ( array $exporters ): array {
		$exporters['tcgforge-withdrawal'] = array(
			'exporter_friendly_name' => \__( 'Notifications de rétractation', 'g2rd-tcgforge-child' ),
			'callback'               => function ( string $email ): array {
				$items = array();
				$posts = \get_posts(
					array(
						'post_type'      => WITHDRAWAL_CPT,
						'post_status'    => 'private',
						'posts_per_page' => 100,
						'meta_key'       => '_tcgf_wd_email', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
						'meta_value'     => $email, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					)
				);
				foreach ( $posts as $post ) {
					$items[] = array(
						'group_id'    => 'tcgforge-withdrawal',
						'group_label' => \__( 'Notifications de rétractation', 'g2rd-tcgforge-child' ),
						'item_id'     => 'tcgf-wd-' . $post->ID,
						'data'        => array(
							array( 'name' => \__( 'Référence', 'g2rd-tcgforge-child' ), 'value' => (string) \get_post_meta( $post->ID, '_tcgf_wd_ref', true ) ),
							array( 'name' => \__( 'Date', 'g2rd-tcgforge-child' ), 'value' => $post->post_date ),
							array( 'name' => \__( 'Déclaration', 'g2rd-tcgforge-child' ), 'value' => $post->post_content ),
						),
					);
				}
				return array( 'data' => $items, 'done' => true );
			},
		);
		return $exporters;
	}
);

\add_filter(
	'wp_privacy_personal_data_erasers',
	function ( array $erasers ): array {
		$erasers['tcgforge-withdrawal'] = array(
			'eraser_friendly_name' => \__( 'Notifications de rétractation', 'g2rd-tcgforge-child' ),
			'callback'             => function ( string $email ): array {
				$kept = (bool) \get_posts(
					array(
						'post_type'      => WITHDRAWAL_CPT,
						'post_status'    => 'private',
						'posts_per_page' => 1,
						'fields'         => 'ids',
						'meta_key'       => '_tcgf_wd_email', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
						'meta_value'     => $email, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					)
				);
				return array(
					'items_removed'  => false,
					'items_retained' => $kept,
					'messages'       => $kept ? array( \__( 'Les notifications de rétractation sont conservées cinq ans : elles prouvent la date d\'exercice du droit.', 'g2rd-tcgforge-child' ) ) : array(),
					'done'           => true,
				);
			},
		);
		return $erasers;
	}
);

/**
 * Tunnel de commande : libellé du bouton et case d'acceptation des CGV.
 */
\add_filter(
	'woocommerce_order_button_text',
	function (): string {
		return \__( 'Commande avec obligation de paiement', 'g2rd-tcgforge-child' );
	}
);

\add_filter(
	'render_block',
	function ( $html, $block ) {
		$name = $block['blockName'] ?? '';
		if ( 'woocommerce/checkout-actions-block' !== $name && 'woocommerce/checkout-terms-block' !== $name ) {
			return $html;
		}
		if ( ! \is_string( $html ) || ! \class_exists( 'WP_HTML_Tag_Processor' ) ) {
			return $html;
		}
		$tags = new \WP_HTML_Tag_Processor( $html );
		if ( $tags->next_tag() ) {
			if ( 'woocommerce/checkout-actions-block' === $name ) {
				$tags->set_attribute( 'data-place-order-button-label', \__( 'Commande avec obligation de paiement', 'g2rd-tcgforge-child' ) );
			} elseif ( null === $tags->get_attribute( 'data-checkbox' ) ) {
				$tags->set_attribute( 'data-checkbox', 'true' );
			}
			return $tags->get_updated_html();
		}
		return $html;
	},
	10,
	2
);
