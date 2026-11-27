/**
 * Rescue 18 · animations d'apparition (GSAP et ScrollTrigger, importés par
 * le thème parent G2RD).
 *
 * - En-têtes de page : leurs éléments arrivent en cascade au chargement.
 * - Titres de section, cartes, listes, tableaux, cartes géographiques,
 *   encadrés : ils apparaissent par lots, en cascade, quand ils entrent à l'écran.
 * - Rien n'est masqué sans JavaScript, sans GSAP ou avec « réduire les
 *   animations » : le contenu reste toujours lisible.
 * - Seules l'opacité et la translation bougent (aucun décalage de mise en page),
 *   et les styles posés sont retirés à la fin pour rendre la main aux effets
 *   de survol du thème.
 */
( function () {
	'use strict';

	if ( ! window.gsap || ! window.ScrollTrigger ) {
		return;
	}
	var gsap = window.gsap;
	var ST = window.ScrollTrigger;
	gsap.registerPlugin( ST );

	// En-têtes de page : enfants directs, sauf les fonds décoratifs.
	var HEROS = [
		'.r18-hero__copy > *',
		'.r18-phero > :not(.alignfull):not(.r18-svc__bg)',
		'.r18-dhero > :not(.alignfull):not(.r18-svc__bg):not(.r18-dhero__in)',
		'.r18-dhero__in > *',
		'.r18-ahero > :not(.alignfull)',
		'.r18-shero > :not(.alignfull):not(.r18-svc__bg)',
		'.r18-qpage > .r18-crumbs',
		'.r18-qpage > .r18-eyebrow',
		'.r18-qpage > h1',
		'.r18-404 > *',
	].join( ',' );

	// Blocs qui apparaissent au défilement, sur toutes les pages du site.
	var BLOCS = [
		// Titres de section et têtes de liste.
		'.r18-head', '.r18-section > .r18-eyebrow', '.r18-section > .r18-h2', '.r18-section > .r18-lead',
		// Accueil.
		'.r18-spec', '.r18-news .wp-block-post', '.r18-latest .wp-block-post', '.r18-lib--accueil', '.r18-col',
		'.r18-legal-vis', '.r18-law', '.r18-tool', '.r18-pod-panel', '.r18-ep', '.r18-prod', '.r18-rub', '.r18-fc',
		'.r18-pt--vitrine', '.r18-pt-actions',
		// Cartes et grilles génériques.
		'.r18-card', '.r18-panel', '.r18-box', '.r18-row-card', '.r18-doc', '.r18-sa', '.r18-fact', '.r18-change', '.r18-rel',
		// Documentation, juridique, articles.
		'.r18-lib__bar', '.r18-lawfilters', '.r18-prose > figure', '.r18-prose > h2', '.r18-authorbox', '.r18-toc',
		'.r18-pt--encart', '.r18-statebox', '.r18-timeline > *', '.r18-steps > *',
		// SDIS, actualités, recrutement.
		'.r18-ann__map', '.r18-ann__side', '.r18-of', '.r18-cc__item', '.r18-svc__map',
		'.r18-voie', '.r18-voies-nav', '.r18-voies-table', '.r18-voies__suite',
		// Partenaires.
		'.r18-pt__carte', '.r18-pt--grille > li', '.r18-pt-fiche', '.r18-pt-det',
		// Cartes des casernes.
		'.r18-cc__barre', '.r18-cc__sauts', '.r18-cc__map',
		// Outils et quiz.
		'.r18-tk-bar', '.r18-tk-row', '.r18-tool__form', '.r18-tool__out', '.r18-tool__doc > section', '.r18-tool__rel',
		'.r18-qcard', '.r18-quiz-src', '.r18-qcat__chips', '.r18-qcat__item',
		// Concours et lexique.
		'.r18-ccg__card', '.r18-ccf__sec', '.r18-ccf-steps > li', '.r18-ccs-parcours > li', '.r18-ccs-faq > .wp-block-details', '.r18-lex-def__card',
		// Boutique.
		'.r18-shop-grid > *', '.r18-product',
		// Bas de page.
		'.r18-nl__in', '.r18-ftr__pt', '.r18-ftr__grid > *',
	].join( ',' );

	var mm = gsap.matchMedia();
	mm.add( '(prefers-reduced-motion: no-preference)', function () {
		document.documentElement.classList.add( 'r18-gsap' );

		// 1. En-têtes de page, au chargement.
		var heros = gsap.utils.toArray( HEROS ).filter( visible );
		if ( heros.length ) {
			gsap.from( heros, {
				opacity: 0,
				y: 26,
				duration: 0.8,
				ease: 'power3.out',
				stagger: 0.08,
				clearProps: 'opacity,transform',
			} );
		}

		// 2. Blocs au défilement, par lots, en cascade.
		var blocs = gsap.utils.toArray( BLOCS ).filter( function ( el ) {
			return visible( el ) && ! el.closest( '.r18-partners, .r18-lb, [hidden]' ) && heros.indexOf( el ) === -1;
		} );
		// Un bloc contenu dans un autre bloc animé ne bouge pas deux fois.
		blocs = blocs.filter( function ( el ) {
			return ! blocs.some( function ( autre ) {
				return autre !== el && autre.contains( el );
			} );
		} );
		gsap.set( blocs, { opacity: 0, y: 32 } );
		document.documentElement.setAttribute( 'data-r18-anim', heros.length + '+' + blocs.length );
		ST.batch( blocs, {
			start: 'top 92%',
			once: true,
			onEnter: function ( lot ) {
				gsap.to( lot, {
					opacity: 1,
					y: 0,
					duration: 0.7,
					ease: 'power2.out',
					stagger: 0.07,
					overwrite: true,
					clearProps: 'opacity,transform',
				} );
			},
		} );

		// Contenus qui changent de taille (filtres, cartes, images) : positions recalculées.
		var recalc = debounce( function () {
			ST.refresh();
		}, 200 );
		document.addEventListener( 'click', recalc, true );
		window.addEventListener( 'load', recalc );

		// Bas de page atteint : tout ce qui reste apparaît (page allongée par des images).
		var fin = function () {
			if ( window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 8 ) {
				var reste = blocs.filter( function ( el ) {
					return parseFloat( getComputedStyle( el ).opacity ) < 0.05;
				} );
				if ( reste.length ) {
					gsap.to( reste, { opacity: 1, y: 0, duration: 0.6, stagger: 0.05, clearProps: 'opacity,transform' } );
				}
			}
		};
		window.addEventListener( 'scroll', debounce( fin, 120 ), { passive: true } );

		// Filet de sécurité : rien ne reste invisible (impression, ancre, navigateur lent).
		window.addEventListener( 'beforeprint', function () {
			gsap.set( blocs, { opacity: 1, y: 0 } );
		} );
		setTimeout( function () {
			blocs.forEach( function ( el ) {
				var r = el.getBoundingClientRect();
				if ( r.top < window.innerHeight && r.bottom > 0 && parseFloat( getComputedStyle( el ).opacity ) < 0.05 ) {
					gsap.to( el, { opacity: 1, y: 0, duration: 0.4, clearProps: 'opacity,transform' } );
				}
			} );
		}, 2500 );

		return function () {
			document.documentElement.classList.remove( 'r18-gsap' );
			gsap.set( blocs.concat( heros ), { clearProps: 'opacity,transform' } );
		};
	} );

	function visible( el ) {
		return !! ( el.offsetWidth || el.offsetHeight || el.getClientRects().length );
	}
	function debounce( fn, ms ) {
		var t;
		return function () {
			clearTimeout( t );
			t = setTimeout( fn, ms );
		};
	}
}() );
