/**
 * G2RD Micro-interactions — Intersection Observer pour .scroll-animation
 *
 * Compatible avec le canvas Gutenberg (MutationObserver + data-g2rd-init).
 */
( function () {
	'use strict';

	// Ne pas s'exécuter dans l'éditeur Gutenberg
	if ( typeof window.wp !== 'undefined' && window.wp.blocks ) {
		return;
	}

	if ( ! ( 'IntersectionObserver' in window ) ) {
		// Fallback : rendre tous les éléments visibles immédiatement
		document.querySelectorAll( '.scroll-animation' ).forEach( function ( el ) {
			el.classList.add( 'is-visible' );
		} );
		return;
	}

	const observer = new IntersectionObserver(
		function ( entries ) {
			entries.forEach( function ( entry ) {
				if ( entry.isIntersecting ) {
					entry.target.classList.add( 'is-visible' );
					observer.unobserve( entry.target );
				}
			} );
		},
		{ threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
	);

	function initScrollAnimations( root ) {
		root = root || document;
		root.querySelectorAll( '.scroll-animation:not([data-g2rd-init])' ).forEach( function ( el ) {
			el.setAttribute( 'data-g2rd-init', '1' );
			observer.observe( el );
		} );
	}

	// Initialisation au chargement
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			initScrollAnimations();
		} );
	} else {
		initScrollAnimations();
	}

	// Compatibilité canvas Gutenberg
	const mutationObserver = new MutationObserver( function ( mutations ) {
		mutations.forEach( function ( mutation ) {
			mutation.addedNodes.forEach( function ( node ) {
				if ( node.nodeType === Node.ELEMENT_NODE ) {
					if ( node.classList.contains( 'scroll-animation' ) ) {
						if ( ! node.getAttribute( 'data-g2rd-init' ) ) {
							node.setAttribute( 'data-g2rd-init', '1' );
							observer.observe( node );
						}
					}
					initScrollAnimations( node );
				}
			} );
		} );
	} );

	mutationObserver.observe( document.body, { childList: true, subtree: true } );
} )();
