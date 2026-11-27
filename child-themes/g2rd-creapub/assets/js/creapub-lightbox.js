/**
 * Créa Pub — visionneuse des réalisations (photos à la une du portfolio).
 *
 * Reprend la visionneuse du site actuel : fond noir, flèches roses, compteur,
 * fermeture par la croix, Échap ou clic hors de l'image. Clavier : ← → Échap,
 * focus piégé dans la visionneuse puis rendu à la vignette.
 */
( function () {
	var grids = document.querySelectorAll( '.creapub-realisations-auto' );
	if ( ! grids.length ) return;

	var box, img, counter, items = [], index = 0, lastFocus = null;

	function bigSrc( el ) {
		// Plus grande source du srcset, sinon la source courante.
		var set = el.getAttribute( 'srcset' );
		if ( set ) {
			var best = set.split( ',' ).map( function ( s ) {
				var p = s.trim().split( /\s+/ );
				return { url: p[0], w: parseInt( p[1], 10 ) || 0 };
			} ).sort( function ( a, b ) { return b.w - a.w; } )[0];
			if ( best && best.url ) return best.url;
		}
		return el.currentSrc || el.src;
	}

	function build() {
		box = document.createElement( 'div' );
		box.className = 'creapub-lb';
		box.setAttribute( 'role', 'dialog' );
		box.setAttribute( 'aria-modal', 'true' );
		box.setAttribute( 'aria-label', 'Réalisation agrandie' );
		box.hidden = true;
		box.innerHTML =
			'<button type="button" class="creapub-lb__close" aria-label="Fermer">✕</button>' +
			'<button type="button" class="creapub-lb__arrow creapub-lb__prev" aria-label="Photo précédente">←</button>' +
			'<figure class="creapub-lb__fig"><img alt=""></figure>' +
			'<button type="button" class="creapub-lb__arrow creapub-lb__next" aria-label="Photo suivante">→</button>' +
			'<p class="creapub-lb__count" aria-live="polite"></p>';
		document.body.appendChild( box );
		img = box.querySelector( 'img' );
		counter = box.querySelector( '.creapub-lb__count' );
		box.querySelector( '.creapub-lb__close' ).addEventListener( 'click', close );
		box.querySelector( '.creapub-lb__prev' ).addEventListener( 'click', function () { show( index - 1 ); } );
		box.querySelector( '.creapub-lb__next' ).addEventListener( 'click', function () { show( index + 1 ); } );
		box.addEventListener( 'click', function ( e ) { if ( e.target === box ) close(); } );
		box.addEventListener( 'keydown', function ( e ) {
			if ( 'Escape' === e.key ) close();
			if ( 'ArrowLeft' === e.key ) show( index - 1 );
			if ( 'ArrowRight' === e.key ) show( index + 1 );
			if ( 'Tab' === e.key ) {
				var f = box.querySelectorAll( 'button' ), first = f[0], last = f[ f.length - 1 ];
				if ( e.shiftKey && document.activeElement === first ) { e.preventDefault(); last.focus(); }
				else if ( ! e.shiftKey && document.activeElement === last ) { e.preventDefault(); first.focus(); }
			}
		} );
	}

	function show( i ) {
		index = ( i + items.length ) % items.length;
		var el = items[ index ];
		img.src = bigSrc( el );
		img.alt = el.alt || '';
		counter.textContent = ( index + 1 ) + ' / ' + items.length;
	}

	function open( list, i ) {
		if ( ! box ) build();
		items = list;
		lastFocus = document.activeElement;
		box.hidden = false;
		document.documentElement.classList.add( 'creapub-lb-open' );
		show( i );
		box.querySelector( '.creapub-lb__close' ).focus();
	}

	function close() {
		box.hidden = true;
		document.documentElement.classList.remove( 'creapub-lb-open' );
		if ( lastFocus ) lastFocus.focus();
	}

	grids.forEach( function ( grid ) {
		var list = Array.prototype.slice.call( grid.querySelectorAll( '.wp-block-post-featured-image img' ) );
		list.forEach( function ( el, i ) {
			// Vignette activable au clavier.
			el.setAttribute( 'tabindex', '0' );
			el.setAttribute( 'role', 'button' );
			el.setAttribute( 'aria-label', 'Agrandir : ' + ( el.alt || 'réalisation ' + ( i + 1 ) ) );
			el.addEventListener( 'click', function () { open( list, i ); } );
			el.addEventListener( 'keydown', function ( e ) {
				if ( 'Enter' === e.key || ' ' === e.key ) { e.preventDefault(); open( list, i ); }
			} );
		} );
	} );
} )();
