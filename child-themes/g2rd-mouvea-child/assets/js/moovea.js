/**
 * Moovéa — comportements de page.
 *
 * 1. Mesure la hauteur de l'en-tête et de la barre Founders (variables CSS
 *    --mv-header-h, --mv-founders-h, --mv-chrome) : le hero tient dans
 *    l'écran et rien ne passe sous la barre fixe.
 * 2. En-tête : transparent en haut de page, voilé (bordeaux atténué + flou)
 *    et fixe dès qu'on défile. Sur une page qui ne commence pas par une
 *    section sombre, il garde son fond dès le départ (.is-solid).
 * 3. Curseur personnalisé (point + anneau), comme sur la maquette.
 *    Pointeur fin uniquement, jamais en « mouvement réduit ».
 * 4. Popup d'accueil : ouvre le bloc G2RD Modal marqué .mv-popup-auto
 *    après 5 secondes, une fois par session de navigation.
 * 5. Révélation lente au défilement (jamais de rebond), désactivée si
 *    l'utilisatrice a demandé « mouvement réduit ».
 *
 * @package G2RD_Moovea
 */
( function () {
	'use strict';

	var root = document.documentElement;
	var body = document.body;
	var reduceMotion =
		window.matchMedia &&
		window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	var finePointer =
		window.matchMedia &&
		window.matchMedia( '(hover: hover) and (pointer: fine)' ).matches;

	/* --- 1. Réservation de la place prise par le chrome ------------------ */

	function measureChrome() {
		var bar = document.querySelector( '.mv-founders' );
		var header = document.querySelector( '.mv-header' );
		var barHeight = bar ? Math.round( bar.getBoundingClientRect().height ) : 0;
		var headerHeight = header
			? Math.round( header.getBoundingClientRect().height )
			: 0;

		root.style.setProperty( '--mv-founders-h', barHeight + 'px' );

		// L'en-tête voilé est plus bas de quelques pixels : on ne relit sa
		// hauteur qu'en haut de page, sinon le hero bougerait au défilement.
		if ( ! header || ! header.classList.contains( 'is-scrolled' ) ) {
			root.style.setProperty( '--mv-header-h', headerHeight + 'px' );
			root.style.setProperty( '--mv-chrome', barHeight + headerHeight + 'px' );
		}
	}

	/* --- 2. En-tête transparent → voilé au défilement -------------------- */

	var DARK_START =
		'.mv-on-dark, .wp-block-cover, .has-bordeaux-background-color, ' +
		'.has-bordeaux-deep-background-color, .has-terracotta-vif-background-color';

	function setupHeader() {
		var header = document.querySelector( '.mv-header' );
		if ( ! header ) {
			return;
		}

		// Premier bloc de contenu : sombre → l'en-tête peut être transparent.
		var content =
			document.querySelector( '.wp-block-post-content' ) ||
			document.querySelector( 'main' );
		var first = content ? content.firstElementChild : null;
		if ( ! first || ! first.matches( DARK_START ) ) {
			header.classList.add( 'is-solid' );
			body.classList.add( 'mv-header-solid' );
		}

		var ticking = false;
		function onScroll() {
			if ( ticking ) {
				return;
			}
			ticking = true;
			window.requestAnimationFrame( function () {
				header.classList.toggle( 'is-scrolled', window.scrollY > 24 );
				ticking = false;
			} );
		}

		window.addEventListener( 'scroll', onScroll, { passive: true } );
		onScroll();
	}

	/* --- 3. Curseur personnalisé ----------------------------------------- */

	var HOVER_TARGETS =
		'a, button, input, select, textarea, label, summary, [role="button"], ' +
		'.mv-card, .mv-offre, .g2rd-faq__question, .leaflet-marker-icon';
	var LIGHT_ZONES =
		'.mv-on-dark, .wp-block-cover, .mv-header, .mv-founders, ' +
		'.has-bordeaux-background-color, .has-bordeaux-deep-background-color, ' +
		'.has-terracotta-vif-background-color, .g2rd-modal__backdrop, .mv-map-hero';

	function setupCursor() {
		if ( ! finePointer || reduceMotion ) {
			return;
		}

		var dot = document.createElement( 'div' );
		var ring = document.createElement( 'div' );
		dot.className = 'mv-cursor';
		ring.className = 'mv-cursor-ring';
		dot.setAttribute( 'aria-hidden', 'true' );
		ring.setAttribute( 'aria-hidden', 'true' );
		body.appendChild( dot );
		body.appendChild( ring );

		var x = -100;
		var y = -100;
		var rx = -100;
		var ry = -100;
		var started = false;

		function follow() {
			// L'anneau rattrape le point avec un léger retard (lissage).
			rx += ( x - rx ) * 0.18;
			ry += ( y - ry ) * 0.18;
			ring.style.left = rx + 'px';
			ring.style.top = ry + 'px';
			window.requestAnimationFrame( follow );
		}

		document.addEventListener(
			'mousemove',
			function ( e ) {
				x = e.clientX;
				y = e.clientY;
				dot.style.left = x + 'px';
				dot.style.top = y + 'px';
				if ( ! started ) {
					started = true;
					rx = x;
					ry = y;
					body.classList.add( 'mv-has-cursor' );
					follow();
				}
				dot.classList.remove( 'is-out' );
				ring.classList.remove( 'is-out' );
			},
			{ passive: true }
		);

		// Délégation : un seul écouteur pour tous les éléments, y compris
		// ceux ajoutés plus tard (marqueur Leaflet, formulaire Fluent Forms).
		document.addEventListener( 'mouseover', function ( e ) {
			var target = e.target;
			if ( ! ( target instanceof Element ) ) {
				return;
			}
			var interactive = !! target.closest( HOVER_TARGETS );
			var light = !! target.closest( LIGHT_ZONES );
			dot.classList.toggle( 'is-hover', interactive );
			ring.classList.toggle( 'is-hover', interactive );
			dot.classList.toggle( 'is-light', light );
			ring.classList.toggle( 'is-light', light );
		} );

		document.addEventListener( 'mouseleave', function () {
			dot.classList.add( 'is-out' );
			ring.classList.add( 'is-out' );
		} );

		document.addEventListener( 'mouseenter', function () {
			dot.classList.remove( 'is-out' );
			ring.classList.remove( 'is-out' );
		} );
	}

	/* --- 4. Popup d'accueil après 5 secondes ----------------------------- */

	var POPUP_DELAY = 5000;
	var POPUP_KEY = 'mv-popup-seen';

	function setupPopup() {
		var trigger = document.querySelector(
			'.g2rd-modal.mv-popup-auto [data-modal-trigger]'
		);
		if ( ! trigger ) {
			return;
		}

		// Une seule fois par session : retirer ce test pour l'ouvrir à chaque visite.
		try {
			if ( window.sessionStorage.getItem( POPUP_KEY ) ) {
				return;
			}
		} catch ( err ) {
			// Stockage indisponible (navigation privée stricte) : on ouvre quand même.
		}

		window.setTimeout( function () {
			trigger.click();
			try {
				window.sessionStorage.setItem( POPUP_KEY, '1' );
			} catch ( err ) {
				// Sans importance.
			}
		}, POPUP_DELAY );
	}

	/* --- 5. Révélation au défilement ------------------------------------- */

	function setupReveal() {
		if ( reduceMotion || ! ( 'IntersectionObserver' in window ) ) {
			return;
		}

		// Les sections de premier niveau de la page portent la révélation.
		var targets = document.querySelectorAll(
			'.mv-section, .mv-section-tight, .mv-band, .mv-cta, .mv-marquee'
		);

		if ( ! targets.length ) {
			return;
		}

		var observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						entry.target.classList.add( 'is-visible' );
						observer.unobserve( entry.target );
					}
				} );
			},
			{ rootMargin: '0px 0px -12% 0px', threshold: 0.05 }
		);

		targets.forEach( function ( el ) {
			// Le hero est visible d'emblée : pas de fondu à l'arrivée.
			if ( el.classList.contains( 'mv-hero' ) ) {
				return;
			}
			el.classList.add( 'mv-reveal' );
			observer.observe( el );
		} );
	}

	/* --- 6. Widgets bsport : un montage par bloc ------------------------- */

	// Chaque bloc HTML porte sa configuration en attributs data-, pour qu'un
	// changement d'identifiant ou de catégorie côté bsport se règle dans
	// l'éditeur, sans toucher à ce fichier.
	var BSPORT_CDN = 'https://cdn.bsport.io/scripts/widget.js';

	function chargerBsport( rappel ) {
		if ( window.BsportWidget ) {
			rappel();
			return;
		}
		var existant = document.getElementById( 'bsport-widget-cdn' );
		if ( ! existant ) {
			existant = document.createElement( 'script' );
			existant.id = 'bsport-widget-cdn';
			existant.src = BSPORT_CDN;
			document.head.appendChild( existant );
		}
		// Le script expose BsportWidget de façon asynchrone : on réessaie.
		var essais = 0;
		( function attendre() {
			if ( window.BsportWidget ) {
				rappel();
			} else if ( essais++ < 50 ) {
				window.setTimeout( attendre, 100 );
			}
		} )();
	}

	function setupBsport() {
		var blocs = document.querySelectorAll( '[data-bsport-widget]' );
		if ( ! blocs.length ) {
			return;
		}

		blocs.forEach( function ( bloc ) {
			var type = bloc.getAttribute( 'data-bsport-widget' );
			var id = bloc.getAttribute( 'data-bsport-id' );
			var societe = parseInt( bloc.getAttribute( 'data-bsport-company' ), 10 );
			var brutes = bloc.getAttribute( 'data-bsport-categories' ) || '';

			if ( ! id || ! societe || ! document.getElementById( id ) ) {
				return;
			}

			// Catégories : une liste de nombres, ou rien. Un gabarit non rempli
			// (« A_REMPLIR ») empêche le montage, sinon chaque section afficherait
			// le catalogue entier et la page montrerait quatre grilles identiques.
			var categories = brutes
				.split( ',' )
				.map( function ( n ) { return parseInt( n.trim(), 10 ); } )
				.filter( function ( n ) { return ! isNaN( n ); } );

			if ( brutes.trim() !== '' && ! categories.length ) {
				bloc.setAttribute( 'data-bsport-etat', 'categories-manquantes' );
				return;
			}

			var config = {
				parentElement: id,
				companyId: societe,
				franchiseId: null,
				dialogMode: 1,
				widgetType: type,
				showFab: false,
				fullScreenPopup: true,
				styles: undefined,
				config: {}
			};

			if ( 'pass' === type ) {
				config.config.pass = {
					paymentPackCategories: categories,
					privatePassCategories: []
				};
			} else if ( 'subscription' === type ) {
				config.config.subscription = {};
			} else if ( 'calendar' === type ) {
				config.config.calendar = { todayOnly: false, cardMode: false };
			}

			chargerBsport( function () {
				window.BsportWidget.mount( config );
			} );
		} );
	}

	/* --- Démarrage -------------------------------------------------------- */

	function init() {
		measureChrome();
		setupHeader();
		setupCursor();
		setupPopup();
		setupReveal();
		setupBsport();
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

	var resizeTimer;
	window.addEventListener( 'resize', function () {
		window.clearTimeout( resizeTimer );
		resizeTimer = window.setTimeout( measureChrome, 150 );
	} );

	// La barre Founders et l'en-tête peuvent changer de hauteur.
	if ( 'ResizeObserver' in window ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			var ro = new ResizeObserver( measureChrome );
			var bar = document.querySelector( '.mv-founders' );
			var header = document.querySelector( '.mv-header' );
			if ( bar ) {
				ro.observe( bar );
			}
			if ( header ) {
				ro.observe( header );
			}
		} );
	}
} )();
