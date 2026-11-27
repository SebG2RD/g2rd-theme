/* Rescue 18 · partenaires : compte les clics vers le site d'un partenaire
   (un clic par visiteur et par heure, sans cookie). Le lien s'ouvre
   normalement, le comptage part en arrière-plan. */
( function () {
	'use strict';
	if ( ! window.r18Pt || ! navigator.sendBeacon ) {
		return;
	}
	document.addEventListener( 'click', function ( e ) {
		var a = e.target.closest ? e.target.closest( 'a[data-r18-pt]' ) : null;
		if ( ! a ) {
			return;
		}
		var data = new FormData();
		data.append( 'id', a.getAttribute( 'data-r18-pt' ) );
		navigator.sendBeacon( window.r18Pt.url, data );
	} );
}() );
