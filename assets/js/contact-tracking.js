/**
 * Suivi des clics de contact — téléphone et e-mail.
 *
 * Émet un événement « clic_telephone » ou « clic_email » vers Google Analytics
 * (gtag.js ou Google Tag Manager) et signale le clic au compteur local du
 * thème via l'endpoint REST g2rd/v1/contact-click.
 *
 * Chargé uniquement si la fonctionnalité « contact_tracking » est activée et
 * la licence G2RD valide (classes/class-contact-tracking.php).
 *
 * @package G2RD
 * @since   1.42.0
 */
( function () {
	'use strict';

	// Garde anti-double-écoute : certains constructeurs de page chargent les
	// scripts du pied de page deux fois, ce qui compterait chaque clic double.
	if ( window.g2rdContactClicksBound ) {
		return;
	}
	window.g2rdContactClicksBound = true;

	/**
	 * URL de l'endpoint, lue au moment du clic et non au chargement : un
	 * optimiseur (LiteSpeed…) peut réordonner le script de données injecté
	 * avant celui-ci.
	 *
	 * @return {string} URL de l'endpoint, ou chaîne vide si indisponible.
	 */
	const endpoint = () =>
		( window.g2rdContactTracking && window.g2rdContactTracking.endpoint ) || '';

	/**
	 * Envoie l'événement à la couche analytics disponible : gtag.js si
	 * présent, sinon dataLayer (GTM). Le return évite de compter deux fois
	 * sur un site qui a les deux.
	 *
	 * @param {string} name   Nom de l'événement.
	 * @param {Object} params Paramètres de l'événement.
	 */
	const send = ( name, params ) => {
		if ( typeof window.gtag === 'function' ) {
			window.gtag( 'event', name, params );
			return;
		}
		window.dataLayer = window.dataLayer || [];
		window.dataLayer.push( Object.assign( { event: name }, params ) );
	};

	/**
	 * Tronque et normalise pour la limite GA4 de 100 caractères.
	 *
	 * @param {*} value Valeur brute.
	 * @return {string} Valeur nettoyée.
	 */
	const clamp = ( value ) =>
		String( value || '' ).trim().replace( /\s+/g, ' ' ).slice( 0, 100 );

	// Phase de CAPTURE (3e argument à true) : un stopPropagation() émis par un
	// bloc Modal, Slider ou Sliding Panel empêcherait l'événement d'atteindre
	// document en phase de bouillonnement.
	document.addEventListener(
		'click',
		( event ) => {
			const target = event.target;
			if ( ! target || typeof target.closest !== 'function' ) {
				return;
			}

			const link = target.closest( 'a[href]' );
			if ( ! link ) {
				return;
			}

			// trim() : un href collé depuis un traitement de texte garde
			// souvent une espace de tête. Les schémas d'URL sont insensibles
			// à la casse : « MAILTO: » est valide.
			const href  = ( link.getAttribute( 'href' ) || '' ).trim();
			const lower = href.toLowerCase();

			// Cloudflare « Email Address Obfuscation » et plusieurs extensions
			// anti-spam remplacent mailto: par un lien encodé, restauré en JS.
			const isObfuscatedMail =
				lower.includes( '/cdn-cgi/l/email-protection' ) ||
				link.hasAttribute( 'data-cfemail' );

			const isPhone = lower.startsWith( 'tel:' );
			const isMail  = lower.startsWith( 'mailto:' ) || isObfuscatedMail;
			if ( ! isPhone && ! isMail ) {
				return;
			}

			send( isPhone ? 'clic_telephone' : 'clic_email', {
				link_url: clamp( href ),
				link_text: clamp( link.textContent ),
			} );

			// sendBeacon survit à la navigation déclenchée par tel: et mailto:,
			// contrairement à fetch(). Échec silencieux : le suivi ne doit
			// jamais gêner l'ouverture de l'appel ou de la messagerie.
			const url = endpoint();
			if ( url && navigator.sendBeacon ) {
				navigator.sendBeacon(
					url,
					new URLSearchParams( { type: isPhone ? 'tel' : 'mail' } )
				);
			}
		},
		true
	);
}() );
