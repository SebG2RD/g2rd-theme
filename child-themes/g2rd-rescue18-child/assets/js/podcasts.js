/**
 * Rescue 18 · lecteur intégré des épisodes.
 *
 * Un seul élément <audio> par liste : écouter sans quitter le site.
 * Sans JavaScript, chaque bouton reste un lien vers la fiche de l'épisode
 * (ou le lecteur caché ne s'affiche jamais).
 */
( () => {
	'use strict';

	function mount( list ) {
		const player = list.querySelector( '[data-r18-player]' );
		const audio = player ? player.querySelector( 'audio' ) : null;
		const now = player ? player.querySelector( '.r18-player__now' ) : null;
		if ( ! audio ) {
			return;
		}
		let current = null;
		const reset = () => list.querySelectorAll( '.r18-play.is-playing' ).forEach( ( b ) => {
			b.classList.remove( 'is-playing' );
			b.setAttribute( 'aria-pressed', 'false' );
		} );

		list.addEventListener( 'click', ( e ) => {
			const btn = e.target.closest( 'button[data-r18-audio]' );
			if ( ! btn ) {
				return;
			}
			if ( current === btn && ! audio.paused ) {
				audio.pause();
				return;
			}
			if ( current !== btn ) {
				audio.src = btn.dataset.r18Audio;
				current = btn;
				if ( now ) {
					now.textContent = btn.dataset.r18Title || '';
				}
			}
			player.hidden = false;
			audio.play().catch( () => {} );
		} );
		audio.addEventListener( 'play', () => {
			reset();
			if ( current ) {
				current.classList.add( 'is-playing' );
				current.setAttribute( 'aria-pressed', 'true' );
			}
		} );
		audio.addEventListener( 'pause', reset );
		audio.addEventListener( 'ended', reset );
	}

	function init() {
		document.querySelectorAll( '.r18-eps' ).forEach( ( list ) => {
			if ( ! list.dataset.r18Init ) {
				list.dataset.r18Init = '1';
				mount( list );
			}
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
