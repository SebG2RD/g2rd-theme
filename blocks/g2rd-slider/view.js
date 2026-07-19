/* global wp */
( function () {
	"use strict";

	/**
	 * Initialise un seul bloc Slider.
	 *
	 * @param {HTMLElement} el
	 */
	function initSlider( el ) {
		// Éviter la double initialisation
		if ( el.classList.contains( "is-initialized" ) ) return;

		const track = el.querySelector( "[data-slider-track]" );
		const prevBtn = el.querySelector( "[data-slider-prev]" );
		const nextBtn = el.querySelector( "[data-slider-next]" );
		const pagination = el.querySelector( "[data-slider-pagination]" );

		if ( ! track ) return;

		/* ID unique pour aria-controls (RGAA 4.1) */
		if ( ! track.id ) track.id = 'g2rd-slider-track-' + Math.random().toString( 36 ).slice( 2, 8 );

		// Filtrer les enfants du track (exclure le bloc-appender de l'éditeur)
		const slides = Array.from( track.children ).filter(
			( slide ) => ! slide.hasAttribute( "data-block-appender" )
		);
		const total = slides.length;

		if ( total === 0 ) return;

		// Lire la configuration depuis les data-attributes
		const effect = el.dataset.transition || "slide";
		const duration = parseInt( el.dataset.duration || "", 10 ) || 500;
		const showPagination = "1" === el.dataset.showPagination;
		const paginationStyle = el.dataset.paginationStyle || "dots";
		const autoplay = "1" === el.dataset.autoplay;
		const autoplayInterval =
			parseInt( el.dataset.autoplayInterval || "", 10 ) || 5000;
		const loop = "0" !== el.dataset.loop;
		const pauseOnHover = "0" !== el.dataset.pauseOnHover;

		let currentIndex = 0;
		let autoplayTimer = null;
		let isTransitioning = false;
		let liveRegion = null;

		// ── Navigation ────────────────────────────────────────────────────────

		function goTo( index ) {
			if ( isTransitioning ) return;

			const prevIndex = currentIndex;
			currentIndex = index;

			if ( loop ) {
				if ( currentIndex < 0 ) currentIndex = total - 1;
				if ( currentIndex >= total ) currentIndex = 0;
			} else {
				if ( currentIndex < 0 || currentIndex >= total ) {
					currentIndex = prevIndex;
					return;
				}
				currentIndex = Math.max( 0, Math.min( total - 1, currentIndex ) );
			}

			isTransitioning = true;
			track.style.transition = `transform ${ duration }ms ease`;
			track.dataset.current = String( currentIndex );

			/* aria-current sur le slide actif (RGAA 4.1) */
			slides.forEach( ( slide, i ) => {
				if ( i === currentIndex ) slide.setAttribute( 'aria-current', 'true' );
				else slide.removeAttribute( 'aria-current' );
			} );
			if ( liveRegion ) liveRegion.textContent = 'Slide ' + ( currentIndex + 1 ) + ' sur ' + total;

			if ( effect === "fade" ) {
				slides.forEach( ( slide, i ) => {
					slide.classList.toggle( "is-active", i === currentIndex );
				} );
			} else {
				const offset = total > 0 ? ( 100 * currentIndex ) / total : 0;
				track.style.transform = `translateX(-${ offset }%)`;
			}

			if ( pagination ) renderPagination();
			setTimeout( () => {
				isTransitioning = false;
			}, duration );
		}

		// ── Pagination ────────────────────────────────────────────────────────

		function renderPagination() {
			if ( ! pagination ) return;
			pagination.innerHTML = "";

			const i18n =
				typeof wp !== "undefined" && wp?.i18n ? wp.i18n : null;

			for ( let i = 0; i < total; i++ ) {
				const btn = document.createElement( "button" );
				btn.type = "button";
				btn.className = "g2rd-slider__pagination-btn";
				btn.setAttribute( "role", "tab" );
				btn.setAttribute(
					"aria-selected",
					i === currentIndex ? "true" : "false"
				);
				const label = (
					i18n?.__?.( "Slide %d", "g2rd" ) || "Slide %d"
				).replace( "%d", String( i + 1 ) );
				btn.setAttribute( "aria-label", label );

				if ( paginationStyle === "numbers" ) {
					btn.textContent = String( i + 1 );
				} else if ( paginationStyle === "progress" ) {
					const bar = document.createElement( "span" );
					bar.className = "g2rd-slider__pagination-progress";
					if ( i === currentIndex ) bar.classList.add( "is-active" );
					btn.appendChild( bar );
				}

				btn.addEventListener( "click", () => goTo( i ) );
				pagination.appendChild( btn );
			}

			if ( paginationStyle === "progress" ) {
				const activeBar = pagination.querySelector(
					".g2rd-slider__pagination-progress.is-active"
				);
				if ( activeBar ) {
					activeBar.style.animationDuration = autoplay
						? autoplayInterval + "ms"
						: duration + "ms";
				}
			}
		}

		// ── Autoplay ──────────────────────────────────────────────────────────

		function startAutoplay() {
			stopAutoplay();
			if ( autoplay && ! window.matchMedia( "(prefers-reduced-motion: reduce)" ).matches ) {
				autoplayTimer = window.setInterval( () => {
					goTo( currentIndex + 1 );
				}, autoplayInterval );
			}
		}

		function stopAutoplay() {
			if ( autoplayTimer ) {
				window.clearInterval( autoplayTimer );
				autoplayTimer = null;
			}
		}

		// ── Setup ─────────────────────────────────────────────────────────────

		el.style.setProperty( "--wrb-slider-duration", duration + "ms" );

		prevBtn?.addEventListener( "click", () => goTo( currentIndex - 1 ) );
		nextBtn?.addEventListener( "click", () => goTo( currentIndex + 1 ) );

		/* aria-controls sur les boutons de navigation (RGAA 4.1) */
		prevBtn?.setAttribute( 'aria-controls', track.id );
		nextBtn?.setAttribute( 'aria-controls', track.id );

		/* Région aria-live pour annoncer le changement de slide (RGAA 4.1) */
		liveRegion = document.createElement( 'div' );
		liveRegion.setAttribute( 'aria-live', 'polite' );
		liveRegion.setAttribute( 'aria-atomic', 'true' );
		liveRegion.className = 'screen-reader-text';
		el.appendChild( liveRegion );

		if ( pauseOnHover ) {
			el.addEventListener( "mouseenter", stopAutoplay );
			el.addEventListener( "mouseleave", startAutoplay );
		}

		/* WCAG 2.2.2 — pause de l'autoplay au focus clavier (indépendant de pauseOnHover) */
		if ( autoplay ) {
			el.addEventListener( "focusin", stopAutoplay );
			el.addEventListener( "focusout", startAutoplay );
		}

		// Appliquer les classes et la mise en page selon l'effet
		slides.forEach( ( slide, i ) => {
			if ( effect === "fade" ) {
				slide.classList.add( "g2rd-slider__slide" );
				slide.classList.toggle( "is-active", i === 0 );
			} else {
				slide.classList.add( "g2rd-slider__slide" );
			}
		} );

		if ( effect === "slide" ) {
			track.style.display = "flex";
			track.style.width = 100 * total + "%";
			slides.forEach( ( slide ) => {
				slide.style.flex = `0 0 ${ 100 / total }%`;
			} );
		}

		if ( showPagination ) renderPagination();
		goTo( 0 );
		startAutoplay();

		el.classList.add( "is-initialized" );
	}

	/**
	 * Initialise tous les sliders non encore initialisés.
	 *
	 * @param {Document|HTMLElement} [root]
	 */
	function initAllSliders( root ) {
		( root || document )
			.querySelectorAll( ".g2rd-slider:not(.is-initialized)" )
			.forEach( ( el ) => initSlider( /** @type {HTMLElement} */ ( el ) ) );
	}

	// Frontend : init au chargement du DOM (via wp.domReady si disponible)
	if ( typeof wp !== "undefined" && wp?.domReady ) {
		wp.domReady( () => initAllSliders() );
	} else if ( document.readyState === "loading" ) {
		document.addEventListener( "DOMContentLoaded", () => initAllSliders() );
	} else {
		initAllSliders();
	}

	// Éditeur Gutenberg (canvas iframe) : MutationObserver pour les blocs rendus par React
	if ( typeof MutationObserver !== "undefined" ) {
		var _sliderObserver = new MutationObserver( function () {
			initAllSliders();
		} );

		if ( document.body ) {
			_sliderObserver.observe( document.body, {
				childList: true,
				subtree: true,
			} );
		} else {
			document.addEventListener( "DOMContentLoaded", function () {
				_sliderObserver.observe( document.body, {
					childList: true,
					subtree: true,
				} );
			} );
		}
	}
} )();
