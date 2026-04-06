/**
 * Frontend JavaScript for G2RD Counter Block
 * Handles animations and interactions
 */

/**
 * Counter Animation Class
 */
class CounterAnimation {
	constructor( element ) {
		this.element = element;
		this.numberElement = element.querySelector( ".counter-number" );
		this.circleProgress = element.querySelector( ".counter-circle-progress" );
		this.barFill = element.querySelector( ".counter-bar-fill" );

		if ( ! this.numberElement ) return;

		// Get data attributes
		this.startValue = parseFloat( this.element.dataset.start ) || 0;
		this.endValue = parseFloat( this.element.dataset.end ) || 100;
		this.decimals = parseInt( this.element.dataset.decimals ) || 0;
		this.duration = parseInt( this.element.dataset.duration ) || 2000;
		this.prefix = this.element.dataset.prefix || "";
		this.suffix = this.element.dataset.suffix || "";
		this.thousands = this.element.dataset.thousands || "none";

		this.hasAnimated = false;
		this.setupIntersectionObserver();
	}

	/**
	 * Setup Intersection Observer for scroll-triggered animation
	 */
	setupIntersectionObserver() {
		const prefersReducedMotion = window.matchMedia(
			"(prefers-reduced-motion: reduce)"
		).matches;

		if ( prefersReducedMotion ) {
			this.showFinalValue();
			return;
		}

		const observer = new IntersectionObserver(
			( entries ) => {
				entries.forEach( ( entry ) => {
					if ( entry.isIntersecting && ! this.hasAnimated ) {
						this.startAnimation();
						this.hasAnimated = true;
					}
				} );
			},
			{
				threshold: 0.5,
				rootMargin: "0px 0px -10% 0px",
			}
		);

		observer.observe( this.element );
	}

	/**
	 * Show final value without animation
	 */
	showFinalValue() {
		const formattedValue = this.formatNumber( this.endValue );
		this.numberElement.textContent = formattedValue;

		if ( this.circleProgress ) {
			const circumference = 2 * Math.PI * 50;
			const progress = ( this.endValue / 100 ) * circumference;
			this.circleProgress.style.strokeDasharray = `${ progress } ${ circumference }`;
		}

		if ( this.barFill ) {
			this.barFill.style.width = `${ this.endValue }%`;
		}
	}

	/**
	 * Start the counter animation
	 */
	startAnimation() {
		const startTime = performance.now();
		const range = this.endValue - this.startValue;

		const animate = ( currentTime ) => {
			const elapsed = currentTime - startTime;
			const progress = Math.min( elapsed / this.duration, 1 );
			const easedProgress = this.easeOutQuart( progress );
			const currentValue = this.startValue + range * easedProgress;

			this.numberElement.textContent = this.formatNumber( currentValue );

			if ( this.circleProgress ) {
				this.animateCircle( currentValue );
			}

			if ( this.barFill ) {
				this.animateBar( currentValue );
			}

			if ( progress < 1 ) {
				requestAnimationFrame( animate );
			} else {
				this.showFinalValue();
			}
		};

		requestAnimationFrame( animate );
	}

	animateCircle( currentValue ) {
		if ( ! this.circleProgress ) return;
		const circumference = 2 * Math.PI * 50;
		const progress = ( currentValue / 100 ) * circumference;
		this.circleProgress.style.strokeDasharray = `${ progress } ${ circumference }`;
	}

	animateBar( currentValue ) {
		if ( ! this.barFill ) return;
		this.barFill.style.width = `${ currentValue }%`;
	}

	formatNumber( value ) {
		let formatted = value.toFixed( this.decimals );

		if ( this.thousands === "comma" ) {
			formatted = formatted.replace( /\B(?=(\d{3})+(?!\d))/g, "," );
		} else if ( this.thousands === "space" ) {
			formatted = formatted.replace( /\B(?=(\d{3})+(?!\d))/g, " " );
		}

		return formatted;
	}

	easeOutQuart( t ) {
		return 1 - Math.pow( 1 - t, 4 );
	}
}

/**
 * Initialise un seul bloc compteur.
 *
 * @param {HTMLElement} block
 */
function initCounter( block ) {
	if ( block.dataset.g2rdInit ) return;
	block.dataset.g2rdInit = "1";
	new CounterAnimation( block );
}

/**
 * Initialise tous les blocs compteurs non encore initialisés.
 *
 * @param {Document|HTMLElement} [root]
 */
function initAllCounters( root ) {
	( root || document )
		.querySelectorAll( ".wp-block-g2rd-counter:not([data-g2rd-init])" )
		.forEach( ( el ) => initCounter( /** @type {HTMLElement} */ ( el ) ) );
}

/**
 * Ajustement responsive des cercles SVG sur mobile.
 */
function handleResize() {
	document.querySelectorAll( ".wp-block-g2rd-counter" ).forEach( ( block ) => {
		const circle = block.querySelector( ".counter-circle" );
		if ( circle && window.innerWidth < 480 ) {
			const svg = circle.querySelector( "svg" );
			if ( svg ) {
				svg.setAttribute( "width", "80" );
				svg.setAttribute( "height", "80" );
				svg.querySelectorAll( "circle" ).forEach( ( c ) => {
					c.setAttribute( "cx", "40" );
					c.setAttribute( "cy", "40" );
					c.setAttribute( "r", "30" );
				} );
			}
		}
	} );
}

// Frontend : init au chargement du DOM
document.addEventListener( "DOMContentLoaded", function () {
	initAllCounters();
	handleResize();
	window.addEventListener( "resize", handleResize );
} );

// Éditeur Gutenberg (canvas iframe) : MutationObserver pour les blocs rendus par React
if ( typeof MutationObserver !== "undefined" ) {
	var _counterObserver = new MutationObserver( function () {
		initAllCounters();
	} );

	if ( document.body ) {
		_counterObserver.observe( document.body, { childList: true, subtree: true } );
	} else {
		document.addEventListener( "DOMContentLoaded", function () {
			_counterObserver.observe( document.body, { childList: true, subtree: true } );
		} );
	}
}

/**
 * Expose animation trigger for manual use
 */
window.G2RDCounter = {
	triggerAnimation: function ( selector ) {
		document
			.querySelectorAll( selector || ".wp-block-g2rd-counter" )
			.forEach( ( el ) => {
				const animation = new CounterAnimation( /** @type {HTMLElement} */ ( el ) );
				animation.startAnimation();
			} );
	},
};
