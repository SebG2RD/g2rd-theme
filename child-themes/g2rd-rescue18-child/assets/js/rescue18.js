/**
 * Rescue 18 · interactions communes (chargé partout, différé).
 *
 * - Apparition au défilement : aucune section n'est masquée sans JS ni
 *   avec prefers-reduced-motion (la classe r18-motion n'est posée que si
 *   l'animation est souhaitée et possible).
 * - Ombre de l'en-tête au défilement (écouteur passif + rAF).
 * - Bandeau partenaires : duplication décorative pour la boucle.
 * - Sommaire d'article construit depuis les H2.
 * - Copie de lien, envoi automatique des listes de tri.
 *
 * Chaque module est idempotent (garde data-r18-init).
 */
( () => {
	'use strict';

	const $$ = ( sel, root = document ) => [ ...root.querySelectorAll( sel ) ];
	const reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' );
	const once = ( el, key ) => {
		if ( el.dataset[ key ] ) {
			return false;
		}
		el.dataset[ key ] = '1';
		return true;
	};

	/* Apparition au défilement --------------------------------------------- */
	const REVEAL = [
		'.r18-head', '.r18-spec', '.r18-news .wp-block-post', '.r18-latest .wp-block-post',
		'.r18-lib--accueil', '.r18-col', '.r18-legal-vis', '.r18-law', '.r18-tool',
		'.r18-pod-panel', '.r18-ep', '.r18-prod', '.r18-rub', '.r18-panel', '.r18-card',
	].join( ',' );

	function reveal() {
		// GSAP du thème parent présent : animations.js prend le relais.
		if ( reduced.matches || ! ( 'IntersectionObserver' in window ) || ( window.gsap && window.ScrollTrigger ) ) {
			return;
		}
		document.documentElement.classList.add( 'r18-motion' );
		const io = new IntersectionObserver(
			( entries ) => entries.forEach( ( e ) => {
				if ( e.isIntersecting ) {
					e.target.classList.add( 'is-shown' );
					io.unobserve( e.target );
				}
			} ),
			{ rootMargin: '0px 0px 80px 0px', threshold: 0.03 }
		);
		$$( REVEAL ).forEach( ( el ) => {
			if ( ! once( el, 'r18Reveal' ) ) {
				return;
			}
			const r = el.getBoundingClientRect();
			if ( r.top < window.innerHeight ) {
				return; // Déjà visible : jamais de clignotement.
			}
			el.classList.add( 'r18-reveal' );
			io.observe( el );
		} );
	}

	/* En-tête --------------------------------------------------------------- */
	function header() {
		const hdr = document.querySelector( '.r18-hdr' );
		if ( ! hdr || ! once( hdr, 'r18Init' ) ) {
			return;
		}
		let ticking = false;
		const update = () => {
			hdr.classList.toggle( 'is-scrolled', window.scrollY > 8 );
			ticking = false;
		};
		window.addEventListener( 'scroll', () => {
			if ( ! ticking ) {
				ticking = true;
				window.requestAnimationFrame( update );
			}
		}, { passive: true } );
		update();
	}

	/* Bandeau partenaires --------------------------------------------------- */
	function partners() {
		$$( '.r18-partners__track' ).forEach( ( track ) => {
			if ( ! once( track, 'r18Init' ) || reduced.matches ) {
				return;
			}
			[ ...track.children ].forEach( ( child ) => {
				const clone = child.cloneNode( true );
				clone.setAttribute( 'aria-hidden', 'true' );
				[ clone, ...clone.querySelectorAll( 'a, button' ) ].filter( ( el ) => el.matches( 'a, button' ) ).forEach( ( a ) => a.setAttribute( 'tabindex', '-1' ) );
				track.appendChild( clone );
			} );
		} );
	}

	/* Sommaire -------------------------------------------------------------- */
	function slug( text ) {
		return text.normalize( 'NFD' ).replace( /[̀-ͯ]/g, '' ).toLowerCase()
			.replace( /[^a-z0-9]+/g, '-' ).replace( /^-|-$/g, '' ).slice( 0, 60 );
	}

	function toc() {
		$$( '[data-r18-toc]' ).forEach( ( list ) => {
			if ( ! once( list, 'r18Init' ) ) {
				return;
			}
			const prose = document.querySelector( '.r18-prose' );
			const heads = prose ? $$( 'h2', prose ) : [];
			if ( heads.length < 2 ) {
				return;
			}
			const links = heads.map( ( h ) => {
				if ( ! h.id ) {
					h.id = slug( h.textContent ) || 'section';
				}
				const li = document.createElement( 'li' );
				const a = document.createElement( 'a' );
				a.href = '#' + h.id;
				a.textContent = h.textContent;
				li.appendChild( a );
				list.appendChild( li );
				return a;
			} );
			list.closest( '.r18-toc' ).hidden = false;
			if ( 'IntersectionObserver' in window ) {
				const io = new IntersectionObserver( ( entries ) => {
					entries.forEach( ( e ) => {
						if ( e.isIntersecting ) {
							links.forEach( ( a ) => a.classList.toggle( 'is-on', a.hash === '#' + e.target.id ) );
						}
					} );
				}, { rootMargin: '0px 0px -70% 0px' } );
				heads.forEach( ( h ) => io.observe( h ) );
			}
		} );
	}

	/* Copie de lien, tri automatique --------------------------------------- */
	function misc() {
		document.addEventListener( 'click', ( e ) => {
			const btn = e.target.closest( '[data-r18-copy]' );
			if ( ! btn ) {
				return;
			}
			const url = btn.getAttribute( 'data-r18-copy' );
			if ( navigator.clipboard ) {
				navigator.clipboard.writeText( url ).then( () => {
					btn.classList.add( 'is-done' );
					btn.setAttribute( 'aria-label', 'Lien copié' );
				} );
			}
		} );
		document.addEventListener( 'change', ( e ) => {
			const sel = e.target.closest( '[data-r18-autosubmit]' );
			if ( sel ) {
				const form = sel.form || sel.closest( 'form' );
				if ( form ) {
					form.requestSubmit ? form.requestSubmit() : form.submit();
				}
			}
		} );
	}

	function init() {
		header();
		partners();
		toc();
		reveal();
	}

	misc();
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
