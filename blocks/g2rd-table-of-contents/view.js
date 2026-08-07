/**
 * Rendu frontend du bloc Sommaire.
 *
 * Accessibilité :
 *
 * - Le sommaire est un ensemble de liens de navigation : il porte donc
 *   role="navigation" et un nom accessible (RGAA 12.3). Sans nom, plusieurs
 *   régions de navigation dans une page deviennent indiscernables au clavier.
 *
 * - À l'activation d'un lien, le défilement était fait à la main
 *   (preventDefault + scrollIntoView) sans jamais déplacer le focus. La vue
 *   bougeait, mais la tabulation suivante repartait du sommaire au lieu de la
 *   section atteinte : au clavier, le lien semblait sans effet. Le titre visé
 *   reçoit désormais le focus, rendu focusable par programme via tabindex="-1".
 */
( function () {
	'use strict';

	function translate( text ) {
		if ( 'undefined' !== typeof wp && wp && wp.i18n && wp.i18n.__ ) {
			return wp.i18n.__( text, 'g2rd' );
		}
		return text;
	}

	/** Garantit un identifiant stable sur un titre, pour pouvoir le cibler. */
	function ensureId( heading ) {
		if ( heading.id ) {
			return heading.id;
		}

		var slug = ( heading.textContent || '' )
			.toLowerCase()
			.trim()
			.replace( /\s+/g, '-' )
			.replace( /[^\p{L}\p{N}-]/gu, '' );

		heading.id = slug || 'heading-' + Math.random().toString( 36 ).slice( 2, 9 );

		return heading.id;
	}

	function contentRoot() {
		return (
			document.querySelector( '.wp-block-post-content' ) ||
			document.querySelector( '.entry-content' ) ||
			document.querySelector( '[class*="post-content"]' ) ||
			document.querySelector( 'main' ) ||
			document.body
		);
	}

	function collectHeadings( toc, levels ) {
		var selector = levels
			.map( function ( level ) {
				return 'h' + level;
			} )
			.join( ', ' );

		var found = [];

		contentRoot()
			.querySelectorAll( selector )
			.forEach( function ( heading ) {
				// Ne pas s'indexer soi-même si le sommaire contient des titres.
				if ( toc.contains( heading ) ) {
					return;
				}

				var text = ( heading.textContent || '' ).trim();
				if ( ! text ) {
					return;
				}

				found.push( {
					id: ensureId( heading ),
					text: text,
					level: parseInt( heading.tagName.charAt( 1 ), 10 ),
				} );
			} );

		return found;
	}

	/**
	 * Déplace la vue puis le focus sur le titre visé.
	 *
	 * `tabindex="-1"` est posé à la volée : sans lui, un titre n'est pas
	 * focusable et le focus resterait sur le lien du sommaire.
	 */
	function goToHeading( id ) {
		var target = document.getElementById( id );
		if ( ! target ) {
			return;
		}

		if ( ! target.hasAttribute( 'tabindex' ) ) {
			target.setAttribute( 'tabindex', '-1' );
		}

		target.scrollIntoView( { behavior: 'smooth', block: 'start' } );
		target.focus( { preventScroll: true } );
	}

	function buildList( headings, style ) {
		var list = document.createElement( 'ul' );
		list.className = 'g2rd-toc__list';

		headings.forEach( function ( heading, index ) {
			var item = document.createElement( 'li' );
			item.className = 'g2rd-toc__item g2rd-toc__item--h' + heading.level;

			var link = document.createElement( 'a' );
			link.href = '#' + heading.id;
			link.className = 'g2rd-toc__link';
			link.textContent = heading.text;

			link.addEventListener( 'click', function ( event ) {
				event.preventDefault();
				goToHeading( heading.id );
			} );

			if ( 'numbered' === style ) {
				var number = document.createElement( 'span' );
				number.className = 'g2rd-toc__number';
				// Décoratif : la numérotation est déjà portée par l'ordre de la liste.
				number.setAttribute( 'aria-hidden', 'true' );
				number.textContent = index + 1 + '. ';
				link.insertBefore( number, link.firstChild );
			}

			item.appendChild( link );
			list.appendChild( item );
		} );

		return list;
	}

	function initToc( toc ) {
		var placeholder = toc.querySelector( '[data-toc-placeholder]' );
		if ( ! placeholder ) {
			return;
		}

		var levels = ( toc.dataset.levels || '2,3,4' )
			.split( ',' )
			.map( function ( value ) {
				return parseInt( value.trim(), 10 );
			} );

		var headings = collectHeadings( toc, levels );

		if ( 0 === headings.length ) {
			placeholder.innerHTML =
				'<p class="g2rd-toc__empty">' + translate( 'Aucun titre trouvé.' ) + '</p>';
			toc.classList.add( 'is-initialized' );
			return;
		}

		// Ensemble de liens de navigation : le signaler comme tel, et le nommer.
		if ( ! toc.hasAttribute( 'role' ) ) {
			toc.setAttribute( 'role', 'navigation' );
		}
		if ( ! toc.hasAttribute( 'aria-label' ) ) {
			toc.setAttribute( 'aria-label', translate( 'Sommaire' ) );
		}

		placeholder.innerHTML = '';
		placeholder.appendChild( buildList( headings, toc.dataset.style || 'bullet' ) );
		toc.classList.add( 'is-initialized' );
	}

	function initAll() {
		document
			.querySelectorAll( '.g2rd-toc:not(.is-initialized)' )
			.forEach( initToc );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', initAll );
	} else {
		initAll();
	}

	if ( 'undefined' !== typeof wp && wp && wp.domReady ) {
		wp.domReady( initAll );
	}
} )();
