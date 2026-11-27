/* Rescue 18 · suivi des clics sortants : documents, fichiers, partenaires,
   liens externes. Un clic par lien, visiteur et heure, sans cookie. Le lien
   s'ouvre normalement, le comptage part en arrière-plan. Les appels et les
   e-mails restent suivis par le thème parent. */
( function () {
	'use strict';
	var cfg = window.r18Suivi;
	if ( ! cfg || ! navigator.sendBeacon ) {
		return;
	}
	var site = location.hostname;
	var fichier = /\.(pdf|docx?|xlsx?|pptx?|odt|ods|odp|zip|csv)(\?|#|$)/i;
	function envoyer( a ) {
		var url;
		try {
			url = new URL( a.href, location.href );
		} catch ( e ) {
			return;
		}
		if ( ! /^https?:$/.test( url.protocol ) ) {
			return;
		}
		var pt = a.getAttribute( 'data-r18-pt' );
		var externe = url.hostname !== site;
		if ( ! pt && ! externe && ! fichier.test( url.pathname ) ) {
			return;
		}
		var data = new FormData();
		data.append( 'url', url.href.split( '#' )[ 0 ] );
		data.append( 'page', String( cfg.page || 0 ) );
		if ( pt ) {
			data.append( 'pt', pt );
		}
		navigator.sendBeacon( cfg.url, data );
	}
	document.addEventListener( 'click', function ( e ) {
		var a = e.target.closest ? e.target.closest( 'a[href]' ) : null;
		if ( a ) {
			envoyer( a );
		}
	} );
	// Clic molette : ouverture dans un nouvel onglet.
	document.addEventListener( 'auxclick', function ( e ) {
		if ( e.button !== 1 ) {
			return;
		}
		var a = e.target.closest ? e.target.closest( 'a[href]' ) : null;
		if ( a ) {
			envoyer( a );
		}
	} );
}() );
