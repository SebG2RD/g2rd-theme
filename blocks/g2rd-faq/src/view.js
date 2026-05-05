/**
 * FAQ G2RD — script frontend + éditeur Gutenberg
 * Gère l'accordéon en mode standard (button/ARIA) et mode GEO (details/summary).
 */

const iconMap = {
	"plus-minus": { open: "−", closed: "+" },
	chevron: { open: "▲", closed: "▼" },
	arrow: { open: "▲", closed: "▶" },
};

/**
 * Initialise un seul bloc FAQ.
 *
 * @param {HTMLElement} faq
 */
function initFaq( faq ) {
	if ( faq.dataset.g2rdInit ) return;
	faq.dataset.g2rdInit = "1";

	const allowMultiple = faq.dataset.allowMultiple === "true";
	const iconType      = faq.dataset.iconType || "plus-minus";
	const icons         = iconMap[ iconType ] || iconMap[ "plus-minus" ];
	const isGeo         = faq.classList.contains( "g2rd-faq--geo" );

	if ( isGeo ) {
		// ── Mode GEO : <details>/<summary> — état géré par l'attribut "open" ──
		faq.querySelectorAll( ".g2rd-faq__details" ).forEach( ( details ) => {
			const icon = details.querySelector( ".g2rd-faq__icon" );

			// Initialiser l'icône selon l'état open initial (ex. openFirst).
			if ( icon ) icon.textContent = details.open ? icons.open : icons.closed;

			details.addEventListener( "toggle", () => {
				// Mettre à jour l'icône de cet élément.
				if ( icon ) icon.textContent = details.open ? icons.open : icons.closed;

				// Fermer les autres si allowMultiple est false.
				if ( details.open && ! allowMultiple ) {
					faq.querySelectorAll( ".g2rd-faq__details[open]" ).forEach( ( other ) => {
						if ( other !== details ) {
							// removeAttribute("open") déclenche un "toggle" sur "other",
							// qui mettra à jour son icône automatiquement.
							other.removeAttribute( "open" );
						}
					} );
				}
			} );
		} );
	} else {
		// ── Mode standard : <button> + classe CSS "is-open" ──

		// Initialiser les icônes selon l'état initial.
		faq.querySelectorAll( ".g2rd-faq__icon" ).forEach( ( icon ) => {
			const item = /** @type {HTMLElement|null} */ ( icon.closest( ".g2rd-faq__item" ) );
			if ( ! item ) return;
			icon.textContent = item.classList.contains( "is-open" )
				? icons.open
				: icons.closed;
		} );

		faq.querySelectorAll( ".g2rd-faq__question" ).forEach( ( btn ) => {
			btn.addEventListener( "click", () => {
				const item = /** @type {HTMLElement|null} */ ( btn.closest( ".g2rd-faq__item" ) );
				if ( ! item ) return;
				const isOpen = item.classList.contains( "is-open" );
				const icon   = btn.querySelector( ".g2rd-faq__icon" );

				// Fermer tous les autres si allowMultiple est false.
				if ( ! allowMultiple ) {
					faq.querySelectorAll( ".g2rd-faq__item.is-open" ).forEach( ( openItem ) => {
						if ( openItem !== item ) {
							openItem.classList.remove( "is-open" );
							openItem
								.querySelector( ".g2rd-faq__question" )
								?.setAttribute( "aria-expanded", "false" );
							const otherIcon = openItem.querySelector( ".g2rd-faq__icon" );
							if ( otherIcon ) otherIcon.textContent = icons.closed;
						}
					} );
				}

				// Basculer l'item courant.
				item.classList.toggle( "is-open", ! isOpen );
				btn.setAttribute( "aria-expanded", ( ! isOpen ).toString() );
				if ( icon ) icon.textContent = ! isOpen ? icons.open : icons.closed;
			} );
		} );
	}
}

/**
 * Initialise tous les blocs FAQ non encore initialisés dans un conteneur donné.
 *
 * @param {Document|HTMLElement} [root]
 */
function initAllFaqs( root ) {
	( root || document )
		.querySelectorAll( ".g2rd-faq:not([data-g2rd-init])" )
		.forEach( ( el ) => initFaq( /** @type {HTMLElement} */ ( el ) ) );
}

// Frontend : init au chargement du DOM
document.addEventListener( "DOMContentLoaded", () => initAllFaqs() );

// Éditeur Gutenberg (canvas iframe) : MutationObserver pour les blocs rendus par React
if ( typeof MutationObserver !== "undefined" ) {
	var _faqObserver = new MutationObserver( function () {
		initAllFaqs();
	} );

	if ( document.body ) {
		_faqObserver.observe( document.body, { childList: true, subtree: true } );
	} else {
		document.addEventListener( "DOMContentLoaded", function () {
			_faqObserver.observe( document.body, { childList: true, subtree: true } );
		} );
	}
}
