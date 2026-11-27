/**
 * Rescue 18 · bibliothèque documentaire.
 *
 * - Accueil : filtre par type et recherche instantanée dans l'aperçu ;
 *   la soumission du formulaire ouvre la recherche complète (serveur).
 * - Page : bascule cartes / liste, mémorisée dans ce navigateur.
 * - Favoris locaux, sans compte : localStorage, rien n'est envoyé.
 */
( () => {
	'use strict';

	const FAV = 'r18-favori:';
	const VIEW = 'r18-vue-bibliotheque';
	const norm = ( s ) => ( s || '' ).normalize( 'NFD' ).replace( /[̀-ͯ]/g, '' ).toLowerCase();
	const store = {
		get: ( k ) => {
			try {
				return window.localStorage.getItem( k );
			} catch ( e ) {
				return null;
			}
		},
		set: ( k, v ) => {
			try {
				if ( v === null ) {
					window.localStorage.removeItem( k );
				} else {
					window.localStorage.setItem( k, v );
				}
				return true;
			} catch ( e ) {
				return false;
			}
		},
	};

	function accueil( root ) {
		const grid = root.querySelector( '[data-r18-grid]' );
		const input = root.querySelector( '[data-r18-query]' );
		const count = root.querySelector( '[data-r18-count]' );
		const empty = root.querySelector( '[data-r18-empty]' );
		const buttons = [ ...root.querySelectorAll( '.r18-fbtn' ) ];
		if ( ! grid ) {
			return;
		}
		let type = '';
		const apply = () => {
			const q = norm( input ? input.value.trim() : '' );
			let n = 0;
			grid.querySelectorAll( '.r18-doc' ).forEach( ( card ) => {
				const types = ( card.dataset.type || '' ).split( ',' );
				const ok = ( ! type || type.split( ',' ).some( ( t ) => types.includes( t ) ) ) && norm( card.dataset.search ).includes( q );
				card.hidden = ! ok;
				n += ok ? 1 : 0;
			} );
			if ( count ) {
				count.textContent = n + ( n > 1 ? ' documents affichés' : ' document affiché' );
			}
			if ( empty ) {
				empty.hidden = n !== 0;
			}
		};
		buttons.forEach( ( b ) => b.addEventListener( 'click', () => {
			type = b.dataset.filter || '';
			buttons.forEach( ( x ) => x.setAttribute( 'aria-pressed', String( x === b ) ) );
			apply();
		} ) );
		if ( input ) {
			input.addEventListener( 'input', apply );
		}
	}

	function page( root ) {
		const grid = root.querySelector( '[data-r18-grid]' );
		const btns = [ ...root.querySelectorAll( '[data-r18-view]' ) ];
		if ( ! grid || ! btns.length ) {
			return;
		}
		const set = ( v ) => {
			grid.classList.toggle( 'is-list', v === 'list' );
			btns.forEach( ( b ) => b.setAttribute( 'aria-pressed', String( b.dataset.r18View === v ) ) );
		};
		btns.forEach( ( b ) => b.addEventListener( 'click', () => {
			set( b.dataset.r18View );
			store.set( VIEW, b.dataset.r18View );
		} ) );
		set( store.get( VIEW ) === 'list' ? 'list' : 'grid' );
	}

	function favoris() {
		document.querySelectorAll( '.r18-fav' ).forEach( ( btn ) => {
			if ( btn.dataset.r18Init ) {
				return;
			}
			btn.dataset.r18Init = '1';
			const key = FAV + btn.dataset.fav;
			btn.setAttribute( 'aria-pressed', String( store.get( key ) === '1' ) );
			btn.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				const on = btn.getAttribute( 'aria-pressed' ) === 'true';
				if ( store.set( key, on ? null : '1' ) ) {
					btn.setAttribute( 'aria-pressed', String( ! on ) );
				}
			} );
		} );
	}

	function init() {
		document.querySelectorAll( '[data-r18-lib]' ).forEach( ( root ) => {
			if ( root.dataset.r18Init ) {
				return;
			}
			root.dataset.r18Init = '1';
			if ( root.dataset.r18Lib === 'page' ) {
				page( root );
			} else {
				accueil( root );
			}
		} );
		favoris();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
