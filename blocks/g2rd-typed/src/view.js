/**
 * G2RD Typed — script frontend + éditeur Gutenberg
 * Initialise Typed.js sur les blocs présents dans la page ou dans le canvas éditeur.
 */

/**
 * Lance Typed.js sur un élément.
 *
 * @param {HTMLElement} typedElement
 * @param {HTMLElement} stringsElement
 * @param {Object}      config
 */
function initializeTyped( typedElement, stringsElement, config ) {
	// Éviter une double instance sur le même élément
	if ( typedElement.dataset.typedInit ) return;
	typedElement.dataset.typedInit = "1";

	const typedConfig = {
		stringsElement,
		typeSpeed: config.typeSpeed || 70,
		backSpeed: config.backSpeed || 35,
		loop: config.loop !== undefined ? config.loop : true,
		startDelay: config.startDelay || 0,
		backDelay: config.backDelay || 500,
		fadeOut: config.fadeOut || false,
		fadeOutClass: config.fadeOutClass || "typed-fade-out",
		fadeOutDelay: config.fadeOutDelay || 500,
		smartBackspace:
			config.smartBackspace !== undefined ? config.smartBackspace : true,
		shuffle: config.shuffle || false,
		showCursor: config.showCursor !== undefined ? config.showCursor : true,
		cursorChar: config.cursorChar || "|",
		autoInsertCss:
			config.autoInsertCss !== undefined ? config.autoInsertCss : true,
		attr: config.attr || "",
		bindInputFocusEvents: config.bindInputFocusEvents || false,
		contentType: config.contentType || "html",
	};

	new Typed( typedElement, typedConfig );
}

/**
 * Charge Typed.js (CDN) puis initialise l'élément.
 *
 * @param {HTMLElement} typedElement
 * @param {HTMLElement} stringsElement
 * @param {Object}      config
 */
function loadAndInit( typedElement, stringsElement, config ) {
	if ( typeof Typed !== "undefined" ) {
		initializeTyped( typedElement, stringsElement, config );
		return;
	}

	// Typed.js déjà en cours de chargement : attendre
	if ( window._g2rdTypedLoading ) {
		window.addEventListener( "g2rd:typed-ready", () =>
			initializeTyped( typedElement, stringsElement, config )
		);
		return;
	}

	window._g2rdTypedLoading = true;
	const script = document.createElement( "script" );
	script.src = "https://cdn.jsdelivr.net/npm/typed.js@2.0.12";
	script.onload = () => {
		window._g2rdTypedLoading = false;
		window.dispatchEvent( new CustomEvent( "g2rd:typed-ready" ) );
		initializeTyped( typedElement, stringsElement, config );
	};
	document.head.appendChild( script );
}

/**
 * Initialise un seul bloc G2RD Typed.
 *
 * @param {HTMLElement} block
 */
function initTypedBlock( block ) {
	if ( block.dataset.g2rdInit ) return;
	block.dataset.g2rdInit = "1";

	const typedElement = /** @type {HTMLElement|null} */ (
		block.querySelector( "#typed" )
	);
	const stringsElement = /** @type {HTMLElement|null} */ (
		block.querySelector( "#typed-strings" )
	);
	const configData = block.getAttribute( "data-typed-config" );

	if ( ! typedElement || ! stringsElement || ! configData ) return;

	try {
		const config = JSON.parse( configData );
		loadAndInit( typedElement, stringsElement, config );
	} catch ( error ) {
		console.error( "Erreur lors de l'initialisation de Typed.js:", error );
	}
}

/**
 * Initialise tous les blocs Typed non encore initialisés.
 *
 * @param {Document|HTMLElement} [root]
 */
function initAllTyped( root ) {
	( root || document )
		.querySelectorAll( ".g2rd-typed:not([data-g2rd-init])" )
		.forEach( ( el ) => initTypedBlock( /** @type {HTMLElement} */ ( el ) ) );
}

// Frontend : init au chargement du DOM
document.addEventListener( "DOMContentLoaded", () => initAllTyped() );

// Éditeur Gutenberg (canvas iframe) : MutationObserver pour les blocs rendus par React
if ( typeof MutationObserver !== "undefined" ) {
	var _typedObserver = new MutationObserver( function () {
		initAllTyped();
	} );

	if ( document.body ) {
		_typedObserver.observe( document.body, { childList: true, subtree: true } );
	} else {
		document.addEventListener( "DOMContentLoaded", function () {
			_typedObserver.observe( document.body, { childList: true, subtree: true } );
		} );
	}
}
