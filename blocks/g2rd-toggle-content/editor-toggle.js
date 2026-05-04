/**
 * ToggleContent — interactivité du bouton bascule dans l'éditeur Gutenberg.
 *
 * Ce script tourne dans le canvas iframe de l'éditeur (champ "script" de block.json).
 * Il rend le toggle cliquable et met à jour l'attribut `showLeft` via wp.data.
 */

( function () {
	"use strict";

	/**
	 * Active l'interactivité sur un seul bloc ToggleContent.
	 *
	 * @param {HTMLElement} el  Élément racine du bloc (.g2rd-toggle-content-editor)
	 */
	function initToggleEditor( el ) {
		if ( el.dataset.g2rdToggleInit ) return;
		el.dataset.g2rdToggleInit = "1";

		var switchEl = el.querySelector( ".g2rd-toggle-content-editor__switch" );
		if ( ! switchEl ) return;

		// Rendre le bouton visuellement cliquable
		switchEl.style.cursor = "pointer";
		switchEl.setAttribute( "role", "button" );
		switchEl.setAttribute( "tabindex", "0" );
		switchEl.setAttribute(
			"title",
			"Cliquer pour basculer entre les deux contenus"
		);

		function onToggle() {
			// Remonter jusqu'au wrapper du bloc pour récupérer le clientId
			var blockEl = el.closest( "[data-block]" );
			if ( ! blockEl ) return;
			var clientId = blockEl.getAttribute( "data-block" );

			var isShowLeft = el.classList.contains( "show-left" );

			// wp.data est disponible dans le canvas iframe en WP 6.x+.
			// Fallback sur window.parent si nécessaire (même origine).
			var wpData =
				( window.wp && window.wp.data ) ||
				( window.parent && window.parent.wp && window.parent.wp.data );

			if ( wpData ) {
				wpData
					.dispatch( "core/block-editor" )
					.updateBlockAttributes( clientId, { showLeft: ! isShowLeft } );
			}
		}

		switchEl.addEventListener( "click", onToggle );

		// Accessibilité clavier
		switchEl.addEventListener( "keydown", function ( e ) {
			if ( e.key === "Enter" || e.key === " " ) {
				e.preventDefault();
				onToggle();
			}
		} );
	}

	/**
	 * Parcourt le document à la recherche de blocs ToggleContent non initialisés.
	 *
	 * @param {Document|HTMLElement} [root]
	 */
	function initAllToggles( root ) {
		( root || document )
			.querySelectorAll(
				".g2rd-toggle-content-editor:not([data-g2rd-toggle-init])"
			)
			.forEach( function ( el ) {
				initToggleEditor( /** @type {HTMLElement} */ ( el ) );
			} );
	}

	// Init initiale
	if ( document.readyState === "loading" ) {
		document.addEventListener( "DOMContentLoaded", function () {
			initAllToggles();
		} );
	} else {
		initAllToggles();
	}

	// MutationObserver : capture les blocs insérés dynamiquement par React
	if ( typeof MutationObserver !== "undefined" ) {
		var observer = new MutationObserver( function () {
			initAllToggles();
		} );

		if ( document.body ) {
			observer.observe( document.body, { childList: true, subtree: true } );
		} else {
			document.addEventListener( "DOMContentLoaded", function () {
				observer.observe( document.body, {
					childList: true,
					subtree: true,
				} );
			} );
		}
	}
} )();
