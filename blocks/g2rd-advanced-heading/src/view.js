/**
 * G2RD Titre avancé — script frontend
 * Gère le cycle d'animation des mots dans le titre.
 */

const ANIM_MS = 400; // durée de la transition CSS

class AdvancedHeading {
	constructor( wrapper ) {
		this.wrapper = wrapper;
		this.effect  = wrapper.dataset.effect || "sliding";
		this.speed   = parseInt( wrapper.dataset.speed, 10 ) || 2500;
		this.wrap    = wrapper.querySelector( ".g2rd-adv-heading__animated-wrap" );
		this.words   = Array.from( wrapper.querySelectorAll( ".g2rd-adv-heading__word" ) );
		this.current = 0;

		if ( ! this.wrap || this.words.length <= 1 ) {
			// Rien à animer : rend le 1er mot visible en mode statique
			if ( this.words[ 0 ] ) {
				this.words[ 0 ].style.opacity = "1";
			}
			return;
		}

		this._setup();
		this._cycle();
	}

	// ── Initialise les dimensions et les états de départ
	_setup() {
		const { wrap, words, effect } = this;

		// Prépare la perspective pour l'effet rotation
		if ( effect === "rotation" ) {
			wrap.style.perspective = "400px";
		}

		// Positionne tous les mots en absolu pour mesure
		wrap.style.display        = "inline-block";
		wrap.style.position       = "relative";
		wrap.style.overflow       = "hidden";
		wrap.style.verticalAlign  = "middle";

		words.forEach( ( w ) => {
			w.style.position   = "absolute";
			w.style.top        = "0";
			w.style.left       = "0";
			w.style.visibility = "hidden";
			w.style.whiteSpace = "nowrap";
		} );

		// Force un reflow pour mesurer
		wrap.offsetWidth; // eslint-disable-line no-unused-expressions

		let maxW = 0;
		let maxH = 0;
		words.forEach( ( w ) => {
			const r = w.getBoundingClientRect();
			if ( r.width  > maxW ) maxW = r.width;
			if ( r.height > maxH ) maxH = r.height;
		} );

		wrap.style.width  = maxW + "px";
		wrap.style.height = maxH + "px";

		// Applique l'état initial à chaque mot
		words.forEach( ( w, i ) => {
			w.style.visibility = "";
			this._setState( w, i === 0 ? "visible" : "before" );
		} );
	}

	// ── Définit les styles CSS de chaque état selon l'effet
	_setState( el, state ) {
		const { effect } = this;
		el.style.transition = state === "visible"
			? `opacity ${ ANIM_MS }ms ease, transform ${ ANIM_MS }ms cubic-bezier(0.23,1,0.32,1), clip-path ${ ANIM_MS }ms ease`
			: "none";

		// Réinitialise
		el.style.opacity   = "";
		el.style.transform = "";
		el.style.clipPath  = "";

		if ( state === "visible" ) {
			el.style.opacity   = "1";
			el.style.transform = "none";
			el.style.clipPath  = "none";
			return;
		}

		// États cachés (avant l'entrée ou après la sortie)
		const isBefore = state === "before";

		if ( effect === "sliding" ) {
			el.style.opacity   = "0";
			el.style.transform = `translateY(${ isBefore ? "100%" : "-100%" })`;

		} else if ( effect === "pushing" ) {
			el.style.opacity   = "0";
			el.style.transform = `translateX(${ isBefore ? "100%" : "-100%" })`;

		} else if ( effect === "rotation" ) {
			el.style.opacity   = "0";
			el.style.transform = `rotateX(${ isBefore ? "90deg" : "-90deg" })`;

		} else if ( effect === "zoom" ) {
			el.style.opacity   = "0";
			el.style.transform = `scale(${ isBefore ? "0.4" : "1.6" })`;

		} else if ( effect === "loader" ) {
			// Révélation gauche → droite via clip-path
			el.style.opacity  = "1";
			el.style.clipPath = isBefore ? "inset(0 100% 0 0)" : "inset(0 0 0 100%)";

		} else {
			// Fallback : simple fondu
			el.style.opacity = "0";
		}
	}

	// ── Lance le cycle d'animation
	_cycle() {
		setInterval( () => {
			const leaving = this.words[ this.current ];
			this.current  = ( this.current + 1 ) % this.words.length;
			const entering = this.words[ this.current ];

			// Mot sortant
			this._setState( leaving, "after" );

			// Mot entrant : positionne en "before" sans transition, puis bascule sur "visible"
			entering.style.transition = "none";
			this._setState( entering, "before" );

			// Double rAF pour garantir que le navigateur applique l'état "before" avant la transition
			requestAnimationFrame( () => {
				requestAnimationFrame( () => {
					this._setState( entering, "visible" );
				} );
			} );
		}, this.speed );
	}
}

// ── Initialisation au chargement du DOM
document.addEventListener( "DOMContentLoaded", () => {
	document.querySelectorAll( ".g2rd-adv-heading-wrap[data-effect]" ).forEach( ( el ) => {
		new AdvancedHeading( el );
	} );
} );
