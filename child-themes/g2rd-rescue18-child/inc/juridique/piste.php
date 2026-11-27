<?php
/**
 * API Légifrance (DILA) par le portail PISTE : identifiants, jeton OAuth, test.
 *
 * Accès gratuit après inscription sur https://piste.gouv.fr (licence ouverte 2.0,
 * CGU PISTE et CGU de l'API Légifrance). Authentification OAuth 2 « client
 * credentials » : un jeton court, mis en cache, accompagne chaque appel.
 *
 * Usage prévu : état juridique des textes (en vigueur, abrogé, modifié) et
 * rattrapage des textes antérieurs à la fenêtre des archives ouvertes du JO.
 * Ces traitements s'activeront une fois la connexion testée avec de vraies clés.
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18\Juridique;

use G2RD_Rescue18\Veille\Erreur_Acces;

\defined( 'ABSPATH' ) || exit;

const OPT_PISTE = 'r18_piste';

/**
 * Adresses par environnement.
 *
 * @return array<string,array{jeton:string,api:string}>
 */
function piste_env(): array {
	return array(
		'prod'    => array(
			'jeton' => 'https://oauth.piste.gouv.fr/api/oauth/token',
			'api'   => 'https://api.piste.gouv.fr/dila/legifrance/lf-engine-app/',
		),
		'sandbox' => array(
			'jeton' => 'https://sandbox-oauth.piste.gouv.fr/api/oauth/token',
			'api'   => 'https://sandbox-api.piste.gouv.fr/dila/legifrance/lf-engine-app/',
		),
	);
}

/**
 * Réglages PISTE.
 *
 * @return array{id:string,secret:string,env:string,test:array<string,mixed>}
 */
function piste(): array {
	$o = (array) \get_option( OPT_PISTE, array() );
	return array(
		'id'     => (string) ( $o['id'] ?? '' ),
		'secret' => (string) ( $o['secret'] ?? '' ),
		'env'    => 'sandbox' === ( $o['env'] ?? '' ) ? 'sandbox' : 'prod',
		'test'   => (array) ( $o['test'] ?? array() ),
	);
}

/**
 * Jeton d'accès (mis en cache jusqu'à une minute avant expiration).
 *
 * @return string
 */
function jeton(): string {
	$p = piste();
	if ( '' === $p['id'] || '' === $p['secret'] ) {
		throw new Erreur_Acces( 'config', 'Identifiants PISTE absents.' );
	}
	$cle = 'r18_piste_jeton_' . \md5( $p['env'] . $p['id'] );
	$j   = \get_transient( $cle );
	if ( \is_string( $j ) && '' !== $j ) {
		return $j;
	}
	$r = \wp_safe_remote_post(
		piste_env()[ $p['env'] ]['jeton'],
		array(
			'timeout'     => 20,
			'redirection' => 0,
			'body'        => array(
				'grant_type'    => 'client_credentials',
				'client_id'     => $p['id'],
				'client_secret' => $p['secret'],
				'scope'         => 'openid',
			),
		)
	);
	if ( \is_wp_error( $r ) ) {
		throw new Erreur_Acces( 'reseau', 'PISTE injoignable : ' . $r->get_error_message() );
	}
	$code = (int) \wp_remote_retrieve_response_code( $r );
	$d    = \json_decode( (string) \wp_remote_retrieve_body( $r ), true );
	if ( 200 !== $code || empty( $d['access_token'] ) ) {
		throw new Erreur_Acces( 'http', 'Jeton PISTE refusé (HTTP ' . $code . ( ! empty( $d['error'] ) ? ', ' . \sanitize_text_field( (string) $d['error'] ) : '' ) . '). Vérifie les identifiants et l\'environnement choisi.' );
	}
	\set_transient( $cle, (string) $d['access_token'], \max( 60, (int) ( $d['expires_in'] ?? 3600 ) - 60 ) );
	return (string) $d['access_token'];
}

/**
 * Appel POST JSON à l'API Légifrance.
 *
 * @param string              $chemin Chemin (ex. consult/lastNJo).
 * @param array<string,mixed> $corps  Corps JSON.
 * @return array{0:int,1:array<mixed>}
 */
function legifrance( string $chemin, array $corps ): array {
	$p = piste();
	$r = \wp_safe_remote_post(
		piste_env()[ $p['env'] ]['api'] . \ltrim( $chemin, '/' ),
		array(
			'timeout'     => 25,
			'redirection' => 0,
			'headers'     => array(
				'Authorization' => 'Bearer ' . jeton(),
				'Content-Type'  => 'application/json',
				'Accept'        => 'application/json',
			),
			'body'        => (string) \wp_json_encode( $corps ),
		)
	);
	if ( \is_wp_error( $r ) ) {
		throw new Erreur_Acces( 'reseau', 'API Légifrance injoignable : ' . $r->get_error_message() );
	}
	$d = \json_decode( (string) \wp_remote_retrieve_body( $r ), true );
	return array( (int) \wp_remote_retrieve_response_code( $r ), \is_array( $d ) ? $d : array() );
}

/**
 * Panneau « API Légifrance » dans la carte du Journal officiel.
 */
function panneau_piste(): void {
	$p  = piste();
	$t  = $p['test'];
	$ok = ! empty( $t['ok'] );
	echo '<details class="r18v-more"' . ( isset( $_GET['r18piste'] ) ? ' open' : '' ) . '><summary>API Légifrance (PISTE)' . ( '' !== $p['id'] ? ( $ok ? ' · connectée' : ' · à tester' ) : ' · non configurée' ) . '</summary><form method="post" action="' . \esc_url( \admin_url( 'admin-post.php' ) ) . '" class="r18v-form">'; // phpcs:ignore WordPress.Security.NonceVerification
	\wp_nonce_field( 'r18_piste' );
	echo '<input type="hidden" name="action" value="r18_piste">';
	echo '<p class="r18v-hint">Complète les archives ouvertes du Journal officiel : état juridique des textes (en vigueur, abrogé, modifié) et textes antérieurs à 2025. Accès gratuit après inscription sur <a href="https://piste.gouv.fr/" target="_blank" rel="noopener">piste.gouv.fr</a>.</p>';
	echo '<ol class="r18v-steps"><li>Crée un compte sur PISTE, puis accepte les CGU de l\'API Légifrance (onglet CGU API).</li><li>Crée une application, abonne-la à l\'API Légifrance et copie les identifiants OAuth.</li><li>Colle-les ici, enregistre, puis teste.</li></ol>';
	echo '<fieldset class="r18v-seg"><legend>Environnement</legend><label><input type="radio" name="env" value="prod"' . \checked( $p['env'], 'prod', false ) . '><span>Production</span></label><label><input type="radio" name="env" value="sandbox"' . \checked( $p['env'], 'sandbox', false ) . '><span>Bac à sable</span></label></fieldset>';
	echo '<label for="r18-piste-id">Client ID</label><input id="r18-piste-id" type="text" name="id" value="' . \esc_attr( $p['id'] ) . '" autocomplete="off" spellcheck="false">';
	echo '<label for="r18-piste-sec">Client secret</label><input id="r18-piste-sec" type="password" name="secret" value="" autocomplete="new-password" placeholder="' . ( '' !== $p['secret'] ? 'Enregistré, laisse vide pour le garder' : '' ) . '">';
	if ( ! empty( $t['t'] ) ) {
		echo '<p class="r18v-alert is-' . ( $ok ? 'ok' : 'err' ) . '"><span class="dashicons dashicons-' . ( $ok ? 'yes-alt' : 'warning' ) . '" aria-hidden="true"></span>' . \esc_html( 'Test du ' . \wp_date( 'j/m à G \h i', (int) $t['t'] ) . ' : ' . (string) $t['msg'] ) . '</p>';
	}
	echo '<div class="r18v-card__act"><button type="submit" name="faire" value="enregistrer" class="r18v-btn is-primary">Enregistrer</button><button type="submit" name="faire" value="tester" class="r18v-btn">Enregistrer et tester</button>' . ( '' !== $p['id'] ? '<button type="submit" name="faire" value="oublier" class="r18v-btn is-text">Retirer les identifiants</button>' : '' ) . '</div></form></details>';
}

\add_action(
	'admin_post_r18_piste',
	static function (): void {
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( 'Accès refusé.', 403 );
		}
		\check_admin_referer( 'r18_piste' );
		$p     = piste();
		$faire = \sanitize_key( (string) \wp_unslash( $_POST['faire'] ?? 'enregistrer' ) );
		if ( 'oublier' === $faire ) {
			\delete_option( OPT_PISTE );
		} else {
			$sec = (string) \wp_unslash( $_POST['secret'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nettoyé ci-dessous.
			$sec = '' !== \trim( $sec ) ? \preg_replace( '/[^A-Za-z0-9._~+\/=-]/', '', $sec ) : $p['secret'];
			$o   = array(
				'id'     => \preg_replace( '/[^A-Za-z0-9._~+\/=-]/', '', (string) \wp_unslash( $_POST['id'] ?? '' ) ),
				'secret' => (string) $sec,
				'env'    => 'sandbox' === \sanitize_key( (string) \wp_unslash( $_POST['env'] ?? '' ) ) ? 'sandbox' : 'prod',
				'test'   => array(),
			);
			\update_option( OPT_PISTE, $o, false );
			if ( 'tester' === $faire ) {
				try {
					jeton();
					[ $code, $d ] = legifrance( 'consult/lastNJo', array( 'nbElement' => 1 ) );
					$o['test'] = array(
						't'   => \time(),
						'ok'  => 200 === $code,
						'msg' => 200 === $code ? 'jeton obtenu et API Légifrance joignable (dernier Journal officiel lu).' : 'jeton obtenu, mais l\'API répond HTTP ' . $code . '. Vérifie l\'abonnement de l\'application à l\'API Légifrance et l\'acceptation des CGU.',
						'cles' => \array_slice( \array_keys( $d ), 0, 12 ),
					);
				} catch ( Erreur_Acces $e ) {
					$o['test'] = array(
						't'   => \time(),
						'ok'  => false,
						'msg' => $e->getMessage(),
					);
				}
				\update_option( OPT_PISTE, $o, false );
			}
		}
		\wp_safe_redirect( \admin_url( 'admin.php?page=rescue18-automatisations&r18piste=1#src-jorf' ) );
		exit;
	}
);
