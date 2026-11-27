<?php
/**
 * Formulaire de contact Créa Pub : [creapub_formulaire].
 *
 * Reprend les champs du site actuel (entreprise, téléphone, nom, email, sujet,
 * message, fichiers joints, RGPD). Inséré dans la page Contact par le bloc
 * natif « Code court ». Aucune extension requise.
 *
 * Sécurité : nonce, champ piège (honeypot), délai minimal de saisie (3 s),
 * limite d'envois par adresse IP (5 par heure), pièces jointes filtrées
 * (types et poids), en-têtes de mail nettoyés. Redirection après envoi
 * (pas de double envoi au rechargement).
 *
 * Destinataire : option `creapub_form_to` (par défaut l'email du site actuel).
 *
 * @package G2RD_CreaPub
 */

declare( strict_types = 1 );

namespace G2RD_CreaPub;

\defined( 'ABSPATH' ) || exit;

const FORM_MAX_FILE  = 10 * 1024 * 1024; // 10 Mo par fichier.
const FORM_MAX_FILES = 5;

/**
 * Sujets proposés (identiques au site actuel).
 *
 * @return string[]
 */
function form_subjects(): array {
	return array(
		'Marquage de véhicule',
		"Pose d'une enseigne",
		'Réalisation de signalétique',
		'Impression tout format',
		'Textile personnalisé',
		'Réalisation de goodies',
		'Postuler ou demander un stage',
		'Autre',
	);
}

/**
 * Types de fichiers acceptés en pièce jointe.
 *
 * @return array<string,string>
 */
function form_mimes(): array {
	return array(
		'jpg|jpeg' => 'image/jpeg',
		'png'      => 'image/png',
		'webp'     => 'image/webp',
		'gif'      => 'image/gif',
		'pdf'      => 'application/pdf',
		'ai'       => 'application/postscript',
		'eps'      => 'application/postscript',
		'svg'      => 'image/svg+xml',
		'zip'      => 'application/zip',
		'doc'      => 'application/msword',
		'docx'     => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
	);
}

/**
 * Traitement de l'envoi (hook init, avant tout affichage).
 */
function form_handle(): void {
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || empty( $_POST['creapub_form'] ) ) {
		return;
	}

	$back = \wp_get_referer() ?: \home_url( '/contact/' );
	$back = \remove_query_arg( array( 'envoi' ), $back );
	$fail = static function ( string $code ) use ( $back ): void {
		\wp_safe_redirect( \add_query_arg( 'envoi', $code, $back ) . '#creapub-formulaire' );
		exit;
	};

	if ( ! isset( $_POST['creapub_nonce'] ) || ! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['creapub_nonce'] ) ), 'creapub_form' ) ) {
		$fail( 'expire' );
	}
	// Champ piège : rempli = robot. On répond « envoyé » sans rien envoyer.
	if ( ! empty( $_POST['site_web'] ) ) {
		$fail( 'ok' );
	}
	// Délai minimal : un humain met plus de 3 secondes à remplir le formulaire.
	$started = (int) ( $_POST['creapub_t'] ?? 0 );
	if ( $started <= 0 || \time() - $started < 3 ) {
		$fail( 'ok' );
	}
	// Limite : 5 envois par heure et par adresse IP.
	$ip    = \sanitize_text_field( \wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
	$lock  = 'creapub_form_' . \md5( $ip );
	$count = (int) \get_transient( $lock );
	if ( $count >= 5 ) {
		$fail( 'limite' );
	}

	$field = static function ( string $name, bool $multiline = false ): string {
		$value = \wp_unslash( $_POST[ $name ] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		return $multiline ? \sanitize_textarea_field( (string) $value ) : \sanitize_text_field( (string) $value );
	};

	$data = array(
		'entreprise' => $field( 'entreprise' ),
		'telephone'  => $field( 'telephone' ),
		'nom'        => $field( 'nom' ),
		'email'      => \sanitize_email( $field( 'email' ) ),
		'sujet'      => $field( 'sujet' ),
		'message'    => $field( 'message', true ),
	);

	if ( \in_array( '', array( $data['entreprise'], $data['telephone'], $data['nom'], $data['message'] ), true )
		|| ! \is_email( $data['email'] )
		|| ! \in_array( $data['sujet'], form_subjects(), true )
		|| empty( $_POST['rgpd'] ) ) {
		$fail( 'incomplet' );
	}

	// Pièces jointes.
	$attachments = array();
	if ( ! empty( $_FILES['fichiers']['name'] ) && \is_array( $_FILES['fichiers']['name'] ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		$files = $_FILES['fichiers']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$total = \min( \count( $files['name'] ), FORM_MAX_FILES );
		for ( $i = 0; $i < $total; $i++ ) {
			if ( UPLOAD_ERR_NO_FILE === (int) $files['error'][ $i ] ) {
				continue;
			}
			if ( UPLOAD_ERR_OK !== (int) $files['error'][ $i ] || (int) $files['size'][ $i ] > FORM_MAX_FILE ) {
				$fail( 'fichier' );
			}
			$check = \wp_check_filetype_and_ext( $files['tmp_name'][ $i ], $files['name'][ $i ], form_mimes() );
			if ( empty( $check['ext'] ) ) {
				$fail( 'fichier' );
			}
			$moved = \wp_handle_upload(
				array(
					'name'     => \sanitize_file_name( $files['name'][ $i ] ),
					'type'     => $check['type'],
					'tmp_name' => $files['tmp_name'][ $i ],
					'error'    => 0,
					'size'     => $files['size'][ $i ],
				),
				array(
					'test_form' => false,
					'mimes'     => form_mimes(),
				)
			);
			if ( empty( $moved['file'] ) ) {
				$fail( 'fichier' );
			}
			$attachments[] = $moved['file'];
		}
	}

	$to      = \sanitize_email( (string) \get_option( 'creapub_form_to', 'contact@creapubcommunication.fr' ) );
	$subject = \sprintf( '[Site] %s — %s (%s)', $data['sujet'], $data['nom'], $data['entreprise'] );
	$body    = \implode(
		"\n",
		array(
			'Nouveau message depuis le formulaire de contact du site.',
			'',
			'Entreprise : ' . $data['entreprise'],
			'Nom : ' . $data['nom'],
			'Téléphone : ' . $data['telephone'],
			'Email : ' . $data['email'],
			'Sujet : ' . $data['sujet'],
			'',
			'Message :',
			$data['message'],
			'',
			'Consentement RGPD : oui (' . \wp_date( 'd/m/Y H:i' ) . ')',
		)
	);
	$headers = array( 'Reply-To: ' . \str_replace( array( "\r", "\n" ), '', $data['nom'] ) . ' <' . $data['email'] . '>' );

	$sent = \wp_mail( $to, $subject, $body, $headers, $attachments );

	foreach ( $attachments as $file ) {
		\wp_delete_file( $file );
	}
	\set_transient( $lock, $count + 1, HOUR_IN_SECONDS );

	$fail( $sent ? 'ok' : 'erreur' );
}
\add_action( 'init', __NAMESPACE__ . '\form_handle' );

/**
 * Rendu du formulaire.
 *
 * @return string
 */
function form_render(): string {
	$state    = isset( $_GET['envoi'] ) ? \sanitize_key( \wp_unslash( $_GET['envoi'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$messages = array(
		'ok'        => array( 'ok', 'Merci, votre message est bien envoyé. Nous vous répondons sous 48 h.' ),
		'incomplet' => array( 'err', 'Merci de remplir tous les champs obligatoires et d’accepter la politique de confidentialité.' ),
		'fichier'   => array( 'err', 'Un fichier joint n’est pas accepté : 10 Mo maximum, formats image, PDF, AI, EPS, SVG, ZIP ou Word.' ),
		'limite'    => array( 'err', 'Trop d’envois depuis votre connexion. Merci de réessayer dans une heure ou de nous appeler au 02 32 21 31 16.' ),
		'expire'    => array( 'err', 'La page a expiré. Merci de renvoyer votre message.' ),
		'erreur'    => array( 'err', 'L’envoi a échoué. Merci de nous écrire à contact@creapubcommunication.fr ou de nous appeler au 02 32 21 31 16.' ),
	);

	\ob_start();
	?>
	<form class="creapub-form" id="creapub-formulaire" method="post" enctype="multipart/form-data" action="">
		<?php if ( isset( $messages[ $state ] ) ) : ?>
			<p class="creapub-form__alert creapub-form__alert--<?php echo \esc_attr( $messages[ $state ][0] ); ?>" role="<?php echo 'ok' === $messages[ $state ][0] ? 'status' : 'alert'; ?>"><?php echo \esc_html( $messages[ $state ][1] ); ?></p>
		<?php endif; ?>
		<input type="hidden" name="creapub_form" value="1">
		<input type="hidden" name="creapub_t" value="<?php echo \esc_attr( (string) \time() ); ?>">
		<?php \wp_nonce_field( 'creapub_form', 'creapub_nonce' ); ?>
		<p class="creapub-form__hp" aria-hidden="true"><label for="creapub-site-web">Ne pas remplir</label><input type="text" id="creapub-site-web" name="site_web" tabindex="-1" autocomplete="off"></p>

		<div class="creapub-form__row">
			<p class="creapub-form__group"><label for="creapub-entreprise">Entreprise<span class="req" aria-hidden="true">*</span></label><input type="text" id="creapub-entreprise" name="entreprise" placeholder="Nom de votre entreprise" autocomplete="organization" required></p>
			<p class="creapub-form__group"><label for="creapub-telephone">Téléphone<span class="req" aria-hidden="true">*</span></label><input type="tel" id="creapub-telephone" name="telephone" placeholder="06 32 45 67 89" autocomplete="tel" required></p>
		</div>
		<div class="creapub-form__row">
			<p class="creapub-form__group"><label for="creapub-nom">Nom complet<span class="req" aria-hidden="true">*</span></label><input type="text" id="creapub-nom" name="nom" placeholder="Votre nom" autocomplete="name" required></p>
			<p class="creapub-form__group"><label for="creapub-email">Email<span class="req" aria-hidden="true">*</span></label><input type="email" id="creapub-email" name="email" placeholder="votre@email.com" autocomplete="email" required></p>
		</div>
		<p class="creapub-form__group"><label for="creapub-sujet">Sujet<span class="req" aria-hidden="true">*</span></label>
			<select id="creapub-sujet" name="sujet" required>
				<option value="" disabled selected>Sélectionner un sujet</option>
				<?php foreach ( form_subjects() as $subject ) : ?>
					<option><?php echo \esc_html( $subject ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p class="creapub-form__group"><label for="creapub-message">Message<span class="req" aria-hidden="true">*</span></label><textarea id="creapub-message" name="message" placeholder="Décrivez votre projet…" required></textarea></p>
		<p class="creapub-form__group"><label for="creapub-fichiers">Fichiers joints <span class="creapub-form__hint">(logo, maquette, photo : 5 fichiers, 10 Mo chacun)</span></label><input type="file" id="creapub-fichiers" name="fichiers[]" multiple accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.ai,.eps,.svg,.zip,.doc,.docx"></p>
		<p class="creapub-form__rgpd"><input type="checkbox" id="creapub-rgpd" name="rgpd" value="1" required><label for="creapub-rgpd">J'accepte que mes données soient utilisées pour traiter ma demande, conformément à la <a href="<?php echo \esc_url( \home_url( '/politique-de-confidentialite/' ) ); ?>">politique de confidentialité</a>.<span class="req" aria-hidden="true">*</span></label></p>
		<p class="creapub-form__legend"><span class="req" aria-hidden="true">*</span> Champs obligatoires</p>
		<p><button type="submit" class="creapub-form__submit">Envoyer le message</button></p>
	</form>
	<?php
	return (string) \ob_get_clean();
}
\add_shortcode( 'creapub_formulaire', __NAMESPACE__ . '\form_render' );
