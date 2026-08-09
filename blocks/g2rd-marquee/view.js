/**
 * Rendu frontend du bloc Marquee.
 *
 * Le réglage « Afficher le bouton pause » (attribut `showPauseButton`) commande
 * la visibilité *permanente* du bouton. Il est désactivé par défaut : à l'écran,
 * rien ne s'affiche.
 *
 * Le bouton existe pourtant toujours, mais escamoté tant qu'il n'a pas le focus.
 * Un contenu en mouvement de plus de cinq secondes doit offrir un moyen de
 * l'arrêter (RGAA 13.8, WCAG 2.2.2), et la pause au survol n'y suffit pas :
 * elle est hors de portée du clavier. L'escamotage suit la technique du lien
 * d'évitement — invisible, mais dans le flux et dans l'ordre de tabulation, donc
 * révélé à la tabulation puis remasqué.
 *
 * Le rendu par défaut est ainsi inchangé, et le critère est satisfait dans les
 * deux positions du réglage.
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
			var button = buildPauseButton( track );

			/*
			 * Sans le réglage, le bouton reste escamoté jusqu'au focus : l'écran
			 * est identique à ce qu'il était, mais la commande demeure atteignable
			 * au clavier. Avec le réglage, il est visible en permanence.
			 */
			if ( ! marquee.classList.contains( 'has-pause-button' ) ) {
				button.classList.add( 'g2rd-marquee__pause--until-focus' );
			}

			marquee.insertBefore( button, track );
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
