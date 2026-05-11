( function () {
	'use strict';

	function initToggle( el ) {
		if ( el.dataset.g2rdInit ) return;
		el.dataset.g2rdInit = '1';

		const checkbox = el.querySelector( '.g2rd-toggle-content__checkbox' );
		const label    = el.querySelector( '.g2rd-toggle-content__switch' );
		const content  = el.querySelector( '.g2rd-toggle-content__content' );
		if ( ! checkbox || ! content ) return;

		const sections = Array.from( content.children );
		if ( sections.length < 2 ) return;

		/* IDs uniques pour aria-controls */
		const base   = checkbox.id || ( 'g2rd-tc-' + Math.random().toString( 36 ).slice( 2 ) );
		const leftId  = base + '-left';
		const rightId = base + '-right';
		sections[ 0 ].id = leftId;
		sections[ 1 ].id = rightId;

		if ( label ) {
			label.setAttribute( 'aria-controls', leftId + ' ' + rightId );
		}

		function updateAria() {
			const showLeft = checkbox.checked;
			sections[ 0 ].setAttribute( 'aria-hidden', showLeft ? 'false' : 'true' );
			sections[ 1 ].setAttribute( 'aria-hidden', showLeft ? 'true' : 'false' );
		}

		updateAria();
		checkbox.addEventListener( 'change', updateAria );
	}

	function initAll() {
		document.querySelectorAll( '.g2rd-toggle-content' ).forEach( initToggle );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', initAll );
	} else {
		initAll();
	}
} )();
