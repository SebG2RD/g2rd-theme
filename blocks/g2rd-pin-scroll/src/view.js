/**
 * G2RD Pin Scroll — Animation frontend
 *
 * Séquence d'images synchronisée au défilement (effet Apple pin scroll).
 * Dépendances runtime : GSAP + ScrollTrigger (enqueués par class-pin-scroll.php).
 * Pattern : MutationObserver + data-g2rd-init pour compatibilité éditeur Gutenberg.
 */

class PinScrollSequence {
	constructor( block ) {
		this.block      = block;
		this.images     = [];
		this.frameIndex = 0;
		this.stRef      = null;

		const rawImages     = block.dataset.images;
		this.scrollDistance = parseInt( block.dataset.scrollDistance, 10 ) || 3000;
		this.renderMode     = block.dataset.renderMode || 'canvas';
		this.canvasWidth    = parseInt( block.dataset.imageWidth, 10 ) || 1920;
		this.canvasHeight   = parseInt( block.dataset.imageHeight, 10 ) || 1080;

		try {
			this.imageData = JSON.parse( rawImages );
		} catch ( e ) {
			return;
		}

		if ( ! this.imageData || ! this.imageData.length ) return;

		// Respect prefers-reduced-motion
		if ( window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
			this.showFrame( 0 );
			return;
		}

		this.canvas  = block.querySelector( '.g2rd-pin-scroll__canvas' );
		this.imgEl   = block.querySelector( '.g2rd-pin-scroll__frame' );
		this.overlay = block.querySelector( '.g2rd-pin-scroll__overlay' );

		if ( this.canvas ) {
			this.ctx           = this.canvas.getContext( '2d' );
			this.canvas.width  = this.canvasWidth;
			this.canvas.height = this.canvasHeight;
		}

		this.preloadImages();
	}

	preloadImages() {
		let loaded     = 0;
		const total    = this.imageData.length;
		this.images    = new Array( total );

		this.imageData.forEach( ( data, i ) => {
			const img = new Image();

			img.onload = () => {
				this.images[ i ] = img;
				loaded++;
				// Afficher immédiatement la première image
				if ( i === 0 ) this.showFrame( 0 );
				if ( loaded === total ) this.initScrollTrigger();
			};

			img.onerror = () => {
				loaded++;
				if ( loaded === total ) this.initScrollTrigger();
			};

			img.src = data.url;
		} );
	}

	showFrame( index ) {
		const img = this.images[ index ];
		if ( ! img ) return;

		if ( this.canvas && this.ctx ) {
			this.ctx.clearRect( 0, 0, this.canvas.width, this.canvas.height );
			this.ctx.drawImage( img, 0, 0, this.canvas.width, this.canvas.height );
		} else if ( this.imgEl ) {
			this.imgEl.src = img.src;
			this.imgEl.alt = ( this.imageData[ index ] || {} ).alt || '';
		}

		this.frameIndex = index;
	}

	initScrollTrigger() {
		const gsap          = window.gsap;
		const ScrollTrigger = window.ScrollTrigger;

		if ( ! gsap || ! ScrollTrigger ) return;

		gsap.registerPlugin( ScrollTrigger );

		const totalFrames  = this.images.length - 1;
		const overlay      = this.overlay;
		const overlayPos   = overlay ? parseFloat( overlay.dataset.position || 30 ) / 100 : 0;
		const overlayFade  = overlay ? overlay.dataset.fade === '1' : false;
		const fadeWindow   = 0.2;

		// Proxy objet pour GSAP — évite l'animation directe sur un nœud DOM
		const state = { progress: 0 };

		this.stRef = gsap.to( state, {
			progress: 1,
			ease:     'none',
			scrollTrigger: {
				trigger:       this.block,
				start:         'top top',
				end:           '+=' + this.scrollDistance,
				pin:           true,
				scrub:         1,
				anticipatePin: 1,
				onUpdate: ( self ) => {
					// Mise à jour de la frame
					const frame = Math.round( self.progress * totalFrames );
					if ( frame !== this.frameIndex ) {
						this.showFrame( frame );
					}

					// Gestion de l'opacité du texte superposé
					if ( overlay ) {
						const p   = self.progress;
						const end = Math.min( overlayPos + fadeWindow, 0.95 );
						let opacity = 0;

						if ( overlayFade ) {
							if ( p >= overlayPos && p <= end ) {
								if ( p - overlayPos < 0.05 ) {
									// Fondu entrant
									opacity = ( p - overlayPos ) / 0.05;
								} else if ( end - p < 0.05 ) {
									// Fondu sortant
									opacity = ( end - p ) / 0.05;
								} else {
									opacity = 1;
								}
							}
						} else {
							opacity = ( p >= overlayPos && p <= end ) ? 1 : 0;
						}

						overlay.style.opacity = opacity;
					}
				},
			},
		} );
	}

	destroy() {
		if ( this.stRef && this.stRef.scrollTrigger ) {
			this.stRef.scrollTrigger.kill();
		}
	}
}

function initPinScroll( block ) {
	if ( block.dataset.g2rdInit ) return;
	block.dataset.g2rdInit = '1';
	new PinScrollSequence( block );
}

function initAllPinScrolls( root ) {
	( root || document )
		.querySelectorAll( '.g2rd-pin-scroll:not([data-g2rd-init])' )
		.forEach( initPinScroll );
}

// Attente de GSAP + ScrollTrigger (chargés de manière externe via PHP)
function waitForGSAP( callback, retries ) {
	var remaining = retries === undefined ? 30 : retries;
	if ( window.gsap && window.ScrollTrigger ) {
		callback();
	} else if ( remaining > 0 ) {
		setTimeout( function() {
			waitForGSAP( callback, remaining - 1 );
		}, 100 );
	}
}

document.addEventListener( 'DOMContentLoaded', function() {
	waitForGSAP( initAllPinScrolls );
} );

// Compatibilité éditeur Gutenberg (blocs ajoutés dynamiquement)
if ( typeof MutationObserver !== 'undefined' ) {
	var _psObserver = new MutationObserver( function() {
		if ( window.gsap && window.ScrollTrigger ) {
			initAllPinScrolls();
		}
	} );
	_psObserver.observe( document.body, { childList: true, subtree: true } );
}
