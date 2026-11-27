/* Rescue 18 · visionneuse d'images des articles.
   Les articles importés de l'ancien site contiennent le bouton « agrandir »
   de WordPress sans son script. Cette visionneuse prend le relais : clic sur
   l'image ou sur le bouton, fermeture par Échap, clic hors image ou bouton,
   flèches pour passer d'une image à l'autre, focus rendu au déclencheur. */
( function () {
	'use strict';
	if ( document.querySelector( '.wp-lightbox-overlay' ) ) {
		return; // La visionneuse native de WordPress est active.
	}
	var zone = document.querySelector( '.r18-prose, .wp-block-post-content' );
	if ( ! zone ) {
		return;
	}
	var figures = Array.prototype.slice.call( zone.querySelectorAll( 'figure.wp-block-image, figure.wp-lightbox-container' ) ).filter( function ( f ) {
		var img = f.querySelector( 'img' );
		return img && ! f.querySelector( 'a[href]:not([href$=".jpg"]):not([href$=".jpeg"]):not([href$=".png"]):not([href$=".webp"])' );
	} );
	if ( ! figures.length ) {
		return;
	}
	var source = function ( img ) {
		var a = img.closest( 'a[href]' );
		if ( a && /\.(jpe?g|png|webp|gif)(\?|$)/i.test( a.href ) ) {
			return a.href;
		}
		var set = img.getAttribute( 'srcset' );
		if ( set ) {
			var best = set.split( ',' ).map( function ( s ) {
				var p = s.trim().split( /\s+/ );
				return { u: p[ 0 ], w: parseInt( p[ 1 ], 10 ) || 0 };
			} ).sort( function ( x, y ) {
				return y.w - x.w;
			} )[ 0 ];
			if ( best ) {
				return best.u;
			}
		}
		return img.currentSrc || img.src;
	};

	var dlg = document.createElement( 'div' );
	dlg.className = 'r18-lb';
	dlg.setAttribute( 'role', 'dialog' );
	dlg.setAttribute( 'aria-modal', 'true' );
	dlg.setAttribute( 'aria-label', 'Image agrandie' );
	dlg.hidden = true;
	dlg.innerHTML = '<button type="button" class="r18-lb__close" aria-label="Fermer l\'image agrandie">&times;</button>'
		+ '<button type="button" class="r18-lb__nav is-prev" aria-label="Image précédente">&#8249;</button>'
		+ '<figure class="r18-lb__fig"><img alt=""><figcaption></figcaption></figure>'
		+ '<button type="button" class="r18-lb__nav is-next" aria-label="Image suivante">&#8250;</button>'
		+ '<p class="r18-lb__count" aria-live="polite"></p>';
	document.body.appendChild( dlg );
	var big = dlg.querySelector( 'img' );
	var cap = dlg.querySelector( 'figcaption' );
	var count = dlg.querySelector( '.r18-lb__count' );
	var closeBtn = dlg.querySelector( '.r18-lb__close' );
	var cur = 0, retour = null;

	var montrer = function ( i ) {
		cur = ( i + figures.length ) % figures.length;
		var img = figures[ cur ].querySelector( 'img' );
		var c = figures[ cur ].querySelector( 'figcaption' );
		big.src = source( img );
		big.alt = img.alt || '';
		cap.textContent = c ? c.textContent : '';
		cap.hidden = ! cap.textContent;
		count.textContent = figures.length > 1 ? ( cur + 1 ) + ' / ' + figures.length : '';
		dlg.querySelectorAll( '.r18-lb__nav' ).forEach( function ( b ) {
			b.hidden = figures.length < 2;
		} );
	};
	var ouvrir = function ( i, declencheur ) {
		retour = declencheur || document.activeElement;
		montrer( i );
		dlg.hidden = false;
		document.documentElement.classList.add( 'r18-lb-open' );
		closeBtn.focus();
	};
	var fermer = function () {
		dlg.hidden = true;
		document.documentElement.classList.remove( 'r18-lb-open' );
		big.removeAttribute( 'src' );
		if ( retour && retour.focus ) {
			retour.focus();
		}
	};

	figures.forEach( function ( f, i ) {
		var img = f.querySelector( 'img' );
		f.classList.add( 'r18-zoomable' );
		var btn = f.querySelector( '.lightbox-trigger' );
		if ( ! btn ) {
			btn = document.createElement( 'button' );
			btn.type = 'button';
			btn.className = 'lightbox-trigger';
			f.appendChild( btn );
		}
		btn.removeAttribute( 'style' );
		btn.setAttribute( 'aria-label', 'Agrandir l\'image' + ( img.alt ? ' : ' + img.alt : '' ) );
		btn.innerHTML = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>';
		btn.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			ouvrir( i, btn );
		} );
		img.addEventListener( 'click', function ( e ) {
			var a = img.closest( 'a[href]' );
			if ( a && ! /\.(jpe?g|png|webp|gif)(\?|$)/i.test( a.href ) ) {
				return;
			}
			e.preventDefault();
			ouvrir( i, btn );
		} );
	} );

	closeBtn.addEventListener( 'click', fermer );
	dlg.querySelector( '.is-prev' ).addEventListener( 'click', function () {
		montrer( cur - 1 );
	} );
	dlg.querySelector( '.is-next' ).addEventListener( 'click', function () {
		montrer( cur + 1 );
	} );
	dlg.addEventListener( 'click', function ( e ) {
		if ( e.target === dlg || e.target.classList.contains( 'r18-lb__fig' ) ) {
			fermer();
		}
	} );
	document.addEventListener( 'keydown', function ( e ) {
		if ( dlg.hidden ) {
			return;
		}
		if ( e.key === 'Escape' ) {
			fermer();
		} else if ( e.key === 'ArrowLeft' ) {
			montrer( cur - 1 );
		} else if ( e.key === 'ArrowRight' ) {
			montrer( cur + 1 );
		} else if ( e.key === 'Tab' ) {
			var f = Array.prototype.slice.call( dlg.querySelectorAll( 'button:not([hidden])' ) );
			var i = f.indexOf( document.activeElement );
			e.preventDefault();
			f[ ( i + ( e.shiftKey ? -1 : 1 ) + f.length ) % f.length ].focus();
		}
	} );
}() );
