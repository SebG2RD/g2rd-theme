/**
 * TCGForge — interactions du front.
 *
 * Trois comportements, tous optionnels (le site fonctionne sans JavaScript) :
 *  1. En-tête : ombre au défilement.
 *  2. Navigation des licences : repères de défilement horizontal sur mobile.
 *  3. Fiche produit : barre d'achat fixe sur mobile quand le bouton sort de l'écran.
 *
 * Pattern MutationObserver + data-tcgf-init : compatible avec le canvas de
 * l'éditeur et les contenus injectés après coup.
 */
( function () {
	'use strict';

	var reduceMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	function once( el, key ) {
		var attr = 'data-tcgf-init';
		var done = ( el.getAttribute( attr ) || '' ).split( ' ' );
		if ( done.indexOf( key ) !== -1 ) {
			return false;
		}
		done.push( key );
		el.setAttribute( attr, done.join( ' ' ).trim() );
		return true;
	}

	/* 1. En-tête ----------------------------------------------------------- */
	function initHeader( header ) {
		if ( ! once( header, 'header' ) ) {
			return;
		}
		var ticking = false;
		var bar = header.querySelector( '.tcgf-header__bar' );
		var hasRail = !! header.querySelector( '.tcgf-header__nav' );

		// Mobile : seul le rail des univers reste collé. On mesure la barre
		// (logo, outils, recherche) pour faire remonter l'en-tête d'autant.
		function measure() {
			if ( ! bar || ! hasRail ) {
				return;
			}
			header.style.setProperty( '--tcgf-header-offset', bar.offsetHeight + 'px' );
			header.classList.add( 'is-rail-sticky' );
		}
		function update() {
			header.classList.toggle( 'is-scrolled', window.scrollY > 8 );
			ticking = false;
		}
		measure();
		window.addEventListener( 'resize', function () {
			window.requestAnimationFrame( measure );
		}, { passive: true } );
		window.addEventListener( 'load', measure );
		window.addEventListener(
			'scroll',
			function () {
				if ( ! ticking ) {
					ticking = true;
					window.requestAnimationFrame( update );
				}
			},
			{ passive: true }
		);
		update();
	}

	/* 2. Navigation défilante ---------------------------------------------- */
	function initNavRail( rail ) {
		if ( ! once( rail, 'rail' ) ) {
			return;
		}
		var scroller = rail.querySelector( '.wp-block-navigation__container' ) || rail;
		var ticking = false;

		function update() {
			var max = scroller.scrollWidth - scroller.clientWidth;
			rail.classList.toggle( 'has-overflow-start', scroller.scrollLeft > 4 );
			rail.classList.toggle( 'has-overflow-end', max > 4 && scroller.scrollLeft < max - 4 );
			ticking = false;
		}
		function request() {
			if ( ! ticking ) {
				ticking = true;
				window.requestAnimationFrame( update );
			}
		}

		scroller.addEventListener( 'scroll', request, { passive: true } );
		window.addEventListener( 'resize', request, { passive: true } );

		// Amène le lien de la page courante dans la zone visible.
		var current = scroller.querySelector( '.current-menu-item, [aria-current="page"]' );
		if ( current && current.scrollIntoView && scroller.scrollWidth > scroller.clientWidth ) {
			var left = current.offsetLeft - ( scroller.clientWidth - current.offsetWidth ) / 2;
			scroller.scrollLeft = Math.max( 0, left );
		}
		update();
	}

	/* 3. Barre d'achat mobile ----------------------------------------------- */
	function initBuyBar( form ) {
		if ( ! once( form, 'buybar' ) || ! ( 'IntersectionObserver' in window ) ) {
			return;
		}
		var button = form.querySelector( 'button[type="submit"], .single_add_to_cart_button' );
		var title = document.querySelector( '.tcgf-product__summary h1, .tcgf-product h1, main h1' );
		if ( ! button || ! title || button.disabled ) {
			return;
		}
		var price = document.querySelector( '.tcgf-product__summary .wc-block-components-product-price, .tcgf-product__summary .price' );

		var bar = document.createElement( 'div' );
		bar.className = 'tcgf-buybar';
		bar.setAttribute( 'hidden', '' );

		var info = document.createElement( 'div' );
		info.className = 'tcgf-buybar__info';

		var name = document.createElement( 'span' );
		name.className = 'tcgf-buybar__name';
		name.textContent = title.textContent.trim();
		info.appendChild( name );

		if ( price && price.textContent.trim() ) {
			var amount = document.createElement( 'span' );
			amount.className = 'tcgf-buybar__price';
			amount.textContent = price.textContent.replace( /\s+/g, ' ' ).trim();
			info.appendChild( amount );
		}

		var go = document.createElement( 'button' );
		go.type = 'button';
		go.className = 'tcgf-buybar__button wp-element-button';
		go.textContent = button.textContent.trim();
		go.addEventListener( 'click', function () {
			form.scrollIntoView( { behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' } );
			window.setTimeout( function () {
				button.focus( { preventScroll: true } );
			}, reduceMotion ? 0 : 350 );
		} );

		bar.appendChild( info );
		bar.appendChild( go );
		document.body.appendChild( bar );

		var observer = new IntersectionObserver(
			function ( entries ) {
				var entry = entries[ 0 ];
				// Visible seulement quand le formulaire est sorti par le haut.
				var past = ! entry.isIntersecting && entry.boundingClientRect.top < 0;
				if ( past ) {
					bar.removeAttribute( 'hidden' );
				} else {
					bar.setAttribute( 'hidden', '' );
				}
				document.body.classList.toggle( 'tcgf-has-buybar', past );
			},
			{ threshold: 0 }
		);
		observer.observe( form );
	}

	function scan( root ) {
		var scope = root && root.querySelectorAll ? root : document;
		Array.prototype.forEach.call( scope.querySelectorAll( '.tcgf-header' ), initHeader );
		Array.prototype.forEach.call( scope.querySelectorAll( '.tcgf-header__nav' ), initNavRail );
		if ( document.body.classList.contains( 'single-product' ) ) {
			Array.prototype.forEach.call( scope.querySelectorAll( '.tcgf-product__summary form.cart, .tcgf-product__summary .wp-block-add-to-cart-form form' ), initBuyBar );
		}
	}

	function start() {
		scan( document );
		if ( 'MutationObserver' in window ) {
			var pending = false;
			new MutationObserver( function () {
				if ( pending ) {
					return;
				}
				pending = true;
				window.requestAnimationFrame( function () {
					pending = false;
					scan( document );
				} );
			} ).observe( document.body, { childList: true, subtree: true } );
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
} )();
