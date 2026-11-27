/**
 * GlassAuto Vernon — comportements du front (sans dépendance).
 *
 *  - en-tête : classe .is-scrolled au défilement ;
 *  - diagnostic « Réparer ou remplacer ? » : déplace l'impact et change le verdict ;
 *  - plan d'accès : charge le plan interactif après clic ;
 *  - initialisation idempotente (data-ga-init) pour rester compatible avec
 *    le canvas de l'éditeur et les mutations du DOM.
 */
( function () {
	'use strict';

	var REDUCED = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	/* --- En-tête ---------------------------------------------------------- */
	function initHeader() {
		var header = document.querySelector( '.ga-header' );
		if ( ! header || header.hasAttribute( 'data-ga-init' ) ) {
			return;
		}
		header.setAttribute( 'data-ga-init', '1' );
		var ticking = false;
		function update() {
			header.classList.toggle( 'is-scrolled', window.scrollY > 24 );
			ticking = false;
		}
		window.addEventListener( 'scroll', function () {
			if ( ! ticking ) {
				window.requestAnimationFrame( update );
				ticking = true;
			}
		}, { passive: true } );
		update();
	}

	/* --- Diagnostic -------------------------------------------------------- */
	function initDiag( diag ) {
		if ( diag.hasAttribute( 'data-ga-init' ) ) {
			return;
		}
		diag.setAttribute( 'data-ga-init', '1' );
		var verdicts;
		try {
			verdicts = JSON.parse( diag.getAttribute( 'data-diag' ) || '{}' );
		} catch ( e ) {
			return;
		}
		var svg = diag.querySelector( '.ga-diag__svg' );
		var dot = diag.querySelector( '.ga-diag__impact' );
		var out = diag.querySelector( '[data-verdict]' );
		if ( ! svg || ! dot || ! out ) {
			return;
		}
		var label = out.querySelector( '.ga-verdict__label' );
		var title = out.querySelector( '.ga-verdict__title' );
		var text = out.querySelector( '[data-verdict-text]' );

		function choose( zone, fromSvg ) {
			var v = verdicts[ zone ];
			if ( ! v ) {
				return;
			}
			svg.classList.add( 'has-choice' );
			svg.querySelectorAll( '[data-zone]' ).forEach( function ( z ) {
				z.classList.toggle( 'is-active', z.getAttribute( 'data-zone' ) === zone );
			} );
			dot.setAttribute( 'transform', 'translate(' + v.x + ' ' + v.y + ')' );
			label.className = 'ga-chip ga-chip--' + v.chip + ' ga-verdict__label';
			label.textContent = v.label;
			title.textContent = v.titre;
			text.textContent = v.texte;
			if ( fromSvg ) {
				var r = diag.querySelector( 'input[value="' + zone + '"]' );
				if ( r ) {
					r.checked = true;
				}
			}
		}

		diag.querySelectorAll( '.ga-diag__options input[type="radio"]' ).forEach( function ( r ) {
			r.addEventListener( 'change', function () {
				choose( r.value, false );
			} );
		} );
		svg.querySelectorAll( '[data-zone]' ).forEach( function ( z ) {
			z.addEventListener( 'click', function () {
				choose( z.getAttribute( 'data-zone' ), true );
			} );
		} );
	}

	/* --- Plan interactif (après consentement) --------------------------------- */
	function initPlan( btn ) {
		if ( btn.hasAttribute( 'data-ga-init' ) ) {
			return;
		}
		btn.setAttribute( 'data-ga-init', '1' );
		btn.addEventListener( 'click', function () {
			var box = document.querySelector( btn.getAttribute( 'data-load-map' ) );
			if ( ! box ) {
				return;
			}
			var f = document.createElement( 'iframe' );
			f.title = box.getAttribute( 'data-iframe-title' ) || 'Plan';
			f.src = box.getAttribute( 'data-iframe-src' ) || '';
			f.loading = 'lazy';
			f.className = 'ga-plan__iframe';
			box.appendChild( f );
			btn.remove();
		} );
	}

	/* --- Bandeau défilant : bouton pause (WCAG 2.2.2) ----------------------- */
	function initMarquee( m ) {
		if ( m.hasAttribute( 'data-ga-mq' ) || REDUCED || document.querySelector( '.editor-styles-wrapper' ) ) {
			return;
		}
		m.setAttribute( 'data-ga-mq', '1' );
		var ICON_PAUSE = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 4h4v16H6zM14 4h4v16h-4z"/></svg>';
		var ICON_PLAY = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 4l13 8-13 8z"/></svg>';
		var b = document.createElement( 'button' );
		b.type = 'button';
		b.className = 'ga-marquee__toggle';
		var set = function ( paused ) {
			m.classList.toggle( 'is-paused', paused );
			b.setAttribute( 'aria-pressed', paused ? 'true' : 'false' );
			b.setAttribute( 'aria-label', paused ? 'Reprendre le défilement des prestations' : 'Mettre en pause le défilement des prestations' );
			b.innerHTML = paused ? ICON_PLAY : ICON_PAUSE;
		};
		b.addEventListener( 'click', function () {
			set( ! m.classList.contains( 'is-paused' ) );
		} );
		set( false );
		m.appendChild( b );
	}

	/* --- Animations automatiques au défilement ------------------------------ */
	/* Ajoute .ga-reveal aux blocs de contenu situés sous la ligne de flottaison,
	   sur toutes les pages, sans toucher au contenu. Les éléments déjà visibles
	   au chargement ne sont pas masqués (pas de clignotement, pas d'impact LCP).
	   Inactif dans l'éditeur et si l'utilisateur réduit les animations. */
	var REVEAL_SEL = [
		'.ga-section-head', '.ga-prose > *', '.ga-callout', '.ga-bento > *', '.ga-presta',
		'.ga-services > *', '.ga-svc-grid > *', '.ga-steps > *', '.ga-flow > li',
		'.ga-sectors > *', '.ga-towns > *', '.ga-table-wrap', '.ga-map-card', '.ga-side-card',
		'.ga-coords > *', '.ga-form-card', '.ga-locate__card', '.ga-cta__inner', '.ga-diag',
		'.ga-pledges > *', '.ga-facts > *', '.ga-veh-grid > *', '.ga-posts__grid > *',
		'.ga-post-card', '.g2rd-faq__item', '.ga-collage', '.ga-team > *', '.ga-reviews-head',
		'.ga-conseils > *', '.ga-partenaires', '.ga-lire-aussi', '.ga-article__media', '.wp-block-post'
	].join( ',' );

	function initAutoReveal() {
		var html = document.documentElement;
		if ( REDUCED || html.hasAttribute( 'data-ga-reveal' ) || ! ( 'IntersectionObserver' in window ) ||
			document.querySelector( '.editor-styles-wrapper' ) || document.body.classList.contains( 'wp-admin' ) ) {
			return;
		}
		html.setAttribute( 'data-ga-reveal', '1' );
		var main = document.querySelector( 'main' ) || document.body;
		var fold = window.innerHeight * 0.92;
		main.querySelectorAll( REVEAL_SEL ).forEach( function ( el ) {
			if ( el.classList.contains( 'ga-reveal' ) || el.closest( '.ga-reveal, .ga-hero, .ga-page-hero, .ga-marquee' ) ) {
				return;
			}
			if ( el.getBoundingClientRect().top < fold ) {
				return;
			}
			el.classList.add( 'ga-reveal' );
			var p = el.parentElement;
			var d = p ? window.getComputedStyle( p ).display : '';
			if ( /grid|flex/.test( d ) ) {
				var i = Array.prototype.indexOf.call( p.children, el ) % 4;
				if ( i > 0 ) {
					el.style.setProperty( '--ga-delay', ( i * 90 ) + 'ms' );
				}
			}
		} );
	}

	/* --- Révélation douce au défilement -------------------------------------- */
	function initReveal() {
		if ( REDUCED || ! ( 'IntersectionObserver' in window ) ) {
			document.querySelectorAll( '.ga-reveal' ).forEach( function ( e ) {
				e.classList.add( 'is-visible' );
			} );
			return;
		}
		var io = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( en ) {
				if ( en.isIntersecting ) {
					en.target.classList.add( 'is-visible' );
					io.unobserve( en.target );
				}
			} );
		}, { rootMargin: '0px 0px -10% 0px' } );
		document.querySelectorAll( '.ga-reveal:not([data-ga-rv])' ).forEach( function ( e ) {
			e.setAttribute( 'data-ga-rv', '1' );
			io.observe( e );
		} );
	}

	/* --- Panneau d'accessibilité du parent : boutons sans nom accessible ------- */
	/* Les boutons « A- » / « A+ » du panneau portent un libellé
	   (.a11y-btn__label) masqué en CSS (display:none), donc ignoré des lecteurs
	   d'écran, et leur SVG est aria-hidden : axe signale « button-name ».
	   Signalé au parent ; en attendant on recopie ce libellé en aria-label. */
	function initA11yLabels( root ) {
		var fallback = { 'decrease-text': 'Réduire le texte', 'increase-text': 'Agrandir le texte' };
		root.querySelectorAll( '.a11y-panel button[data-action]' ).forEach( function ( b ) {
			if ( b.getAttribute( 'aria-label' ) || b.getAttribute( 'aria-labelledby' ) ) {
				return;
			}
			var span = b.querySelector( '.a11y-btn__label' );
			var l = ( span && span.textContent.trim() ) || fallback[ b.getAttribute( 'data-action' ) ] || '';
			if ( l && ( ! span || 'none' === window.getComputedStyle( span ).display ) ) {
				b.setAttribute( 'aria-label', l );
			}
		} );
	}

	function initAll( root ) {
		root = root || document;
		initHeader();
		initA11yLabels( root );
		root.querySelectorAll( '.ga-diag[data-diag]' ).forEach( initDiag );
		root.querySelectorAll( '[data-load-map]' ).forEach( initPlan );
		root.querySelectorAll( '.ga-marquee' ).forEach( initMarquee );
		initAutoReveal();
		initReveal();
	}

	/* Suivi des contacts (GA4 via Site Kit) : appels, rendez-vous, contact.
	 * gtag respecte le mode consentement : sans accord, Google ne reçoit qu'un signal anonyme. */
	function track( name, params ) {
		if ( 'function' === typeof window.gtag ) {
			window.gtag( 'event', name, params || {} );
		}
	}
	function initTracking() {
		if ( document.documentElement.hasAttribute( 'data-ga-track' ) ) {
			return;
		}
		document.documentElement.setAttribute( 'data-ga-track', '1' );
		document.addEventListener( 'click', function ( e ) {
			var a = e.target.closest ? e.target.closest( 'a[href^="tel:"]' ) : null;
			if ( a ) {
				track( 'clic_telephone', { link_url: a.getAttribute( 'href' ), page_path: location.pathname } );
			}
		}, true );
		var bound = false;
		function bindForms() {
			if ( bound || ! window.jQuery ) {
				return;
			}
			bound = true;
			window.jQuery( document ).on( 'fluentform_submission_success', function ( ev, data ) {
				var form = data && data.form ? data.form : ( ev.target || null );
				var id   = form && form.attr ? form.attr( 'data-form_id' ) : '';
				var name = '3' === String( id ) ? 'rendez_vous' : ( '4' === String( id ) ? 'contact' : 'formulaire_' + id );
				track( 'generate_lead', { form_name: name, page_path: location.pathname } );
				track( 'rendez_vous' === name ? 'demande_rendez_vous' : 'envoi_' + name, { page_path: location.pathname } );
			} );
		}
		// jQuery (Fluent Forms) peut arriver après ce script, ou être différé par LiteSpeed.
		bindForms();
		window.addEventListener( 'load', bindForms );
	}

	function start() {
		initTracking();
		initAll( document );
		if ( 'MutationObserver' in window ) {
			new MutationObserver( function ( muts ) {
				for ( var i = 0; i < muts.length; i++ ) {
					if ( muts[ i ].addedNodes.length ) {
						initAll( document );
						return;
					}
				}
			} ).observe( document.body, { childList: true, subtree: true } );
		}
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
} )();
