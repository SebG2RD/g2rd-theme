/**
 * Rescue 18 · scène du casque (chargée seulement si la page contient le
 * groupe « r18-stage »).
 *
 * Perspective au curseur sur pointeur fin, décor non porteur de sens
 * (point lumineux, aide « relief 3D ») injecté en aria-hidden. Aucun
 * mouvement avec prefers-reduced-motion. Animation suspendue onglet caché
 * ou scène hors écran.
 *
 * Évolution prévue : un modèle glTF autorisé pourra remplacer l'image via
 * Three.js chargé à la demande ; ce script restera le repli statique.
 */
( () => {
	'use strict';

	const reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' );
	const fine = window.matchMedia( '(pointer: fine)' );

	function decorate( stage ) {
		stage.setAttribute( 'role', 'group' );
		if ( ! stage.hasAttribute( 'aria-label' ) ) {
			stage.setAttribute( 'aria-label', 'Casque de sapeur-pompier en relief et accès rapides' );
		}
		const dot = document.createElement( 'span' );
		dot.className = 'r18-dot';
		dot.setAttribute( 'aria-hidden', 'true' );
		stage.appendChild( dot );
		if ( fine.matches && ! reduced.matches ) {
			const guide = document.createElement( 'span' );
			guide.className = 'r18-guide';
			guide.setAttribute( 'aria-hidden', 'true' );
			guide.innerHTML = '<span class="r18-guide__mouse"></span>Déplace la souris · relief 3D';
			stage.appendChild( guide );
		}
	}

	function animate( stage ) {
		const rotor = stage.querySelector( '.r18-stage__rotor' );
		if ( ! rotor || reduced.matches || ! fine.matches ) {
			return;
		}
		let rx = 0, ry = -9, gx = 0, gy = -9, raf = 0, visible = true;
		const loop = () => {
			rx += ( gx - rx ) * 0.09;
			ry += ( gy - ry ) * 0.09;
			rotor.style.setProperty( '--r18-rx', rx.toFixed( 2 ) + 'deg' );
			rotor.style.setProperty( '--r18-ry', ry.toFixed( 2 ) + 'deg' );
			raf = Math.abs( gx - rx ) + Math.abs( gy - ry ) > 0.02 && visible && ! document.hidden
				? window.requestAnimationFrame( loop )
				: 0;
		};
		const kick = () => {
			if ( ! raf ) {
				raf = window.requestAnimationFrame( loop );
			}
		};
		stage.addEventListener( 'pointermove', ( e ) => {
			const r = stage.getBoundingClientRect();
			gx = -( ( e.clientY - r.top ) / r.height - 0.5 ) * 16;
			gy = ( ( e.clientX - r.left ) / r.width - 0.5 ) * 22 - 9;
			stage.classList.add( 'is-active' );
			kick();
		} );
		stage.addEventListener( 'pointerleave', () => {
			gx = 0;
			gy = -9;
			stage.classList.remove( 'is-active' );
			kick();
		} );
		if ( 'IntersectionObserver' in window ) {
			new IntersectionObserver( ( entries ) => {
				visible = entries[ 0 ].isIntersecting;
				stage.classList.toggle( 'is-paused', ! visible );
			} ).observe( stage );
		}
	}

	function init() {
		document.querySelectorAll( '.r18-stage' ).forEach( ( stage ) => {
			if ( stage.dataset.r18Init ) {
				return;
			}
			stage.dataset.r18Init = '1';
			decorate( stage );
			animate( stage );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
