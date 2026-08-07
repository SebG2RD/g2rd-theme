/**
 * Rendu frontend du bloc Marquee.
 *
 * Accessibilité (RGAA 13.8 / WCAG 2.2.2) : un contenu en mouvement de plus de
 * cinq secondes doit offrir un moyen de l'arrêter. Le bouton de pause est donc
 * créé systématiquement, sans tenir compte de l'attribut `showPauseButton` :
 * ce réglage permettait de retirer un contrôle obligatoire, et il valait
 * `false` par défaut — tout marquee posé sans y penser défilait sans arrêt
 * possible. L'attribut est conservé dans block.json pour ne pas invalider le
 * contenu déjà publié, mais il ne pilote plus l'affichage du bouton.
 *
 * Le dédoublement du contenu (nécessaire à la boucle) est marqué
 * `aria-hidden` : la copie ne doit pas être lue deux fois.
 */
( function () {
	'use strict';

	var LABEL_PAUSE = 'Mettre le défilement en pause';
	var LABEL_PLAY = 'Reprendre le défilement';

	function buildPauseButton( track ) {
		var button = document.createElement( 'button' );

		button.type = 'button';
		button.className = 'g2rd-marquee__pause';
		button.setAttribute( 'aria-label', LABEL_PAUSE );
		button.setAttribute( 'aria-pressed', 'false' );
		// L'icône est décorative : le nom accessible vient d'aria-label.
		button.innerHTML = '<span aria-hidden="true">⏸</span>';

		button.addEventListener( 'click', function () {
			var paused = 'true' === button.getAttribute( 'aria-pressed' );

			track.style.animationPlayState = paused ? 'running' : 'paused';
			button.setAttribute( 'aria-pressed', String( ! paused ) );
			button.setAttribute( 'aria-label', paused ? LABEL_PAUSE : LABEL_PLAY );
			button.firstChild.textContent = paused ? '⏸' : '▶';
		} );

		return button;
	}

	function initMarquee( marquee ) {
		if ( marquee.dataset.g2rdInit ) {
			return;
		}
		marquee.dataset.g2rdInit = '1';

		var content = marquee.querySelector( '.g2rd-marquee__content' );
		if ( ! content ) {
			return;
		}

		var direction = marquee.dataset.direction || 'left';
		var speed = parseInt( marquee.dataset.speed || '', 10 ) || 30;
		var gap = marquee.dataset.gap || '40px';

		var track = document.createElement( 'div' );
		track.className = 'g2rd-marquee__track';
		marquee.appendChild( track );
		track.appendChild( content );

		var clone = content.cloneNode( true );
		clone.setAttribute( 'aria-hidden', 'true' );
		track.appendChild( clone );

		marquee.style.setProperty( '--marquee-gap', gap );
		marquee.style.setProperty( '--marquee-duration', speed + 's' );
		marquee.style.setProperty(
			'--marquee-direction',
			'right' === direction ? 'reverse' : 'normal'
		);
		marquee.classList.add( 'is-initialized' );

		/*
		 * Quand l'internaute a demandé à réduire les animations, la feuille de
		 * styles neutralise déjà le défilement : il n'y a plus de mouvement à
		 * arrêter, donc pas de bouton à proposer.
		 */
		var reduced =
			window.matchMedia &&
			window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

		if ( ! reduced ) {
			marquee.insertBefore( buildPauseButton( track ), track );
		}
	}

	function init() {
		document.querySelectorAll( '.g2rd-marquee' ).forEach( initMarquee );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
