( function () {
	'use strict';

	function initIconBox( el ) {
		if ( el.dataset.g2rdInit ) return;
		el.dataset.g2rdInit = '1';

		const link = el.querySelector( 'a[href]' );
		if ( ! link ) return;

		/* Calcule le texte accessible du lien en excluant les enfants aria-hidden */
		function getAccessibleText( node ) {
			let text = '';
			node.childNodes.forEach( function ( child ) {
				if ( child.nodeType === Node.TEXT_NODE ) {
					text += child.textContent;
				} else if ( child.nodeType === Node.ELEMENT_NODE ) {
					if ( 'true' !== child.getAttribute( 'aria-hidden' ) ) {
						text += getAccessibleText( child );
					}
				}
			} );
			return text.trim();
		}

		if ( getAccessibleText( link ) ) return; /* Lien déjà accessible */

		/* Cherche un titre dans le bloc parent */
		const title = el.querySelector( '.g2rd-iconbox__title, h2, h3, h4, h5, h6' );
		const label = title ? title.textContent.trim() : '';

		if ( label ) {
			link.setAttribute( 'aria-label', label );
		} else {
			link.setAttribute( 'aria-label', 'Lien' );
		}
	}

	function initAll() {
		document.querySelectorAll( '.g2rd-iconbox' ).forEach( initIconBox );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', initAll );
	} else {
		initAll();
	}
} )();
