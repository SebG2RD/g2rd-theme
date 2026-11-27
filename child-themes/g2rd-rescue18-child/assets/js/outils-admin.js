/**
 * Écran Boîte à outils : compteurs de caractères, aperçu Google, icône, confirmation.
 */
( function () {
	'use strict';
	var form = document.querySelector( '.r18-adm__form' );
	if ( ! form ) {
		return;
	}
	form.querySelectorAll( '[data-r18-count]' ).forEach( function ( el ) {
		var max = +el.getAttribute( 'data-r18-count' );
		var out = document.createElement( 'span' );
		out.className = 'r18-adm__count';
		out.setAttribute( 'aria-live', 'polite' );
		el.insertAdjacentElement( 'afterend', out );
		function upd() {
			var n = ( el.value || el.placeholder ).length;
			out.textContent = n + ' / ' + max + ' caractères' + ( el.value ? '' : ' (texte du thème)' );
			out.classList.toggle( 'is-over', n > max );
		}
		el.addEventListener( 'input', upd );
		upd();
	} );
	var t = form.querySelector( '[name="seo_title"]' );
	var d = form.querySelector( '[name="description"]' );
	var st = form.querySelector( '[data-serp-title]' );
	var sd = form.querySelector( '[data-serp-desc]' );
	function serp() {
		if ( st && t ) {
			st.textContent = ( t.value || t.placeholder ) + ' | Rescue 18';
		}
		if ( sd && d ) {
			var txt = d.value || d.placeholder;
			sd.textContent = txt.length > 158 ? txt.slice( 0, 155 ) + '…' : txt;
		}
	}
	[ t, d ].forEach( function ( el ) {
		if ( el ) {
			el.addEventListener( 'input', serp );
		}
	} );
	var icon = form.querySelector( '[data-r18-icon]' );
	var prev = form.querySelector( '[data-r18-icon-preview]' );
	if ( icon && prev ) {
		icon.addEventListener( 'change', function () {
			prev.className = 'r18-adm__ico r18-i--' + icon.value;
		} );
	}
	form.querySelectorAll( '[data-r18-confirm]' ).forEach( function ( b ) {
		b.addEventListener( 'click', function ( e ) {
			if ( ! window.confirm( b.getAttribute( 'data-r18-confirm' ) ) ) {
				e.preventDefault();
			}
		} );
	} );
} )();
