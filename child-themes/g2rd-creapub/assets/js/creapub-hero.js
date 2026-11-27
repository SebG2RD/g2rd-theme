/**
 * Créa Pub — séquence d'apparition du hero.
 * Le H1 complet est dans le DOM : on ne fait que révéler les caractères (SEO safe).
 */
( function () {
	var hero = document.querySelector( '.creapub-hero' );
	if ( ! hero ) return;
	var video = hero.querySelector( 'video' );

	// Animations réduites : vidéo arrêtée sur sa dernière image (logo complet), texte affiché d'emblée.
	if ( window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
		if ( video ) {
			video.removeAttribute( 'autoplay' );
			video.pause();
			var toEnd = function () { video.currentTime = Math.max( 0, video.duration - 0.1 ); };
			if ( video.readyState >= 1 ) { toEnd(); } else { video.addEventListener( 'loadedmetadata', toEnd ); }
		}
		return;
	}
	document.body.classList.add( 'creapub-anim' );

	var h1 = hero.querySelector( 'h1' );
	if ( ! h1 ) return;

	// Curseur
	var caret = document.createElement( 'span' );
	caret.className = 'creapub-caret';
	caret.setAttribute( 'aria-hidden', 'true' );
	h1.appendChild( caret );

	// Enrobe chaque caractère dans un span.ch (les nœuds texte existants uniquement)
	( function wrap( node ) {
		Array.prototype.slice.call( node.childNodes ).forEach( function ( n ) {
			if ( n.nodeType === 3 ) {
				var frag = document.createDocumentFragment();
				n.textContent.split( '' ).forEach( function ( c ) {
					var s = document.createElement( 'span' );
					s.className = 'ch';
					s.textContent = c;
					frag.appendChild( s );
				} );
				n.parentNode.replaceChild( frag, n );
			} else if ( n.nodeType === 1 && n !== caret ) {
				wrap( n );
			}
		} );
	} )( h1 );

	var chars = h1.querySelectorAll( '.ch' ),
		brush = h1.querySelector( '.creapub-brush' ),
		// Avec la vidéo d'origine : la frappe démarre quand « Agence de communication » apparaît (≈ 3,8 s).
		start = video ? 3800 : 1000,
		step  = video ? 40 : 45,
		timers = [];

	// Une passe de frappe : caractères révélés un à un, puis pinceau tracé.
	function type( delay ) {
		timers.forEach( clearTimeout );
		timers = [];
		chars.forEach( function ( c ) { c.style.visibility = ''; } );
		if ( brush ) brush.classList.remove( 'drawn' );
		if ( ! caret.isConnected ) h1.appendChild( caret );
		chars.forEach( function ( c, i ) {
			timers.push( setTimeout( function () {
				c.style.visibility = 'visible';
				if ( i === chars.length - 1 ) {
					if ( brush ) brush.classList.add( 'drawn' );
					timers.push( setTimeout( function () { caret.remove(); }, 900 ) );
				}
			}, delay + i * step ) );
		} );
	}

	type( start );

	// Boucle comme sur le site d'origine : la vidéo repart, le H1 se retape en même temps.
	// Le texte reste toujours présent dans le DOM (SEO) : seule sa visibilité change.
	if ( video ) {
		video.loop = false;
		video.addEventListener( 'ended', function () {
			setTimeout( function () {
				video.currentTime = 0;
				var p = video.play();
				if ( p && p.catch ) p.catch( function () {} );
				type( start );
			}, 1500 );
		} );
	}
} )();
