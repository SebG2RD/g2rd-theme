<?php
/**
 * Formulaires publics : pont vers Fluent Forms.
 *
 * Shortcode [glassauto_formulaire type="rdv|contact"] : affiche le formulaire
 * Fluent Forms dont l'identifiant est saisi dans Réglages › Général ›
 * GlassAuto Vernon. Tant qu'aucun identifiant n'est renseigné, un message
 * d'installation s'affiche côté éditeur et administrateur ; rien côté visiteur
 * (le téléphone reste visible partout).
 *
 * Les formulaires eux-mêmes (champs, antispam, notifications FluentSMTP,
 * mentions RGPD) se construisent dans Fluent Forms, voir README.md.
 *
 * @package G2RD_GlassAuto
 */

declare( strict_types = 1 );

namespace G2RD_GlassAuto;

\defined( 'ABSPATH' ) || exit;

\add_shortcode(
	'glassauto_formulaire',
	static function ( $atts ): string {
		$atts = \shortcode_atts( array( 'type' => 'contact' ), (array) $atts, 'glassauto_formulaire' );
		$type = 'rdv' === $atts['type'] ? 'rdv' : 'contact';
		$id   = \absint( centre_get( 'form_' . $type ) );

		if ( $id > 0 && \shortcode_exists( 'fluentform' ) ) {
			return '<div class="ga-form ga-form--' . \esc_attr( $type ) . '">' . \do_shortcode( '[fluentform id="' . $id . '"]' ) . '</div>';
		}

		if ( \current_user_can( 'edit_pages' ) ) {
			$msg = $id > 0
				? \__( 'Fluent Forms n’est pas actif : le formulaire ne peut pas s’afficher.', 'g2rd-glassauto-child' )
				: \sprintf(
					/* translators: %s : libellé du formulaire. */
					\__( 'Formulaire « %s » : créez-le dans Fluent Forms puis saisissez son identifiant dans Réglages › Général › GlassAuto Vernon.', 'g2rd-glassauto-child' ),
					'rdv' === $type ? \__( 'Demande de rendez-vous', 'g2rd-glassauto-child' ) : \__( 'Contact', 'g2rd-glassauto-child' )
				);
			return '<p class="ga-editor-placeholder">' . \esc_html( $msg ) . '</p>';
		}
		return '';
	}
);
