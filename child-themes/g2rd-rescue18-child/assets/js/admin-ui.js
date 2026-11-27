/* Rescue 18 · Back-office : éditeur visuel des questions de quiz.
 * Le champ JSON reste la seule donnée enregistrée : l'éditeur le réécrit à chaque saisie. */
( function () {
	'use strict';

	function el( tag, attrs, kids ) {
		var n = document.createElement( tag );
		Object.keys( attrs || {} ).forEach( function ( k ) {
			if ( 'text' === k ) {
				n.textContent = attrs[ k ];
			} else if ( 'on' === k.slice( 0, 2 ) ) {
				n.addEventListener( k.slice( 2 ), attrs[ k ] );
			} else {
				n.setAttribute( k, attrs[ k ] );
			}
		} );
		( kids || [] ).forEach( function ( c ) {
			if ( c ) {
				n.appendChild( c );
			}
		} );
		return n;
	}

	function quiz( champ ) {
		var data;
		try {
			data = JSON.parse( champ.value || '[]' );
		} catch ( e ) {
			return; // JSON illisible : on garde le champ brut pour le corriger.
		}
		if ( ! Array.isArray( data ) ) {
			return;
		}
		data = data.map( function ( q ) {
			return { q: String( q.q || '' ), c: Array.isArray( q.c ) ? q.c.map( String ) : [ '', '' ], a: parseInt( q.a, 10 ) || 0, why: String( q.why || '' ) };
		} );
		var uid = 0;
		var bloc = el( 'div', { class: 'r18qz' } );
		var compte = el( 'p', { class: 'r18qz__n', 'aria-live': 'polite' } );
		var liste = el( 'ol', { class: 'r18qz__liste' } );
		var brut = el( 'details', { class: 'r18qz__brut' }, [ el( 'summary', { text: 'Voir le code JSON (avancé)' } ) ] );

		champ.parentNode.insertBefore( bloc, champ );
		var lab = document.querySelector( 'label[for="' + champ.id + '"]' );
		if ( lab ) {
			lab.querySelector( 'strong' ) && ( lab.querySelector( 'strong' ).textContent = 'Questions du quiz' );
		}
		brut.appendChild( champ );

		function ecrire() {
			champ.value = JSON.stringify( data.map( function ( q ) {
				return { q: q.q.trim(), c: q.c.map( function ( x ) { return x.trim(); } ), a: q.a, why: q.why.trim() };
			} ) );
			compte.textContent = data.length + ( data.length > 1 ? ' questions' : ' question' );
		}

		function alerte( q ) {
			var m = [];
			if ( ! q.q.trim() ) {
				m.push( 'la question est vide' );
			}
			if ( q.c.filter( function ( x ) { return x.trim(); } ).length < 2 ) {
				m.push( 'il faut au moins 2 réponses' );
			}
			if ( ! q.c[ q.a ] || ! q.c[ q.a ].trim() ) {
				m.push( 'coche la bonne réponse' );
			}
			if ( ! q.why.trim() ) {
				m.push( 'ajoute l\'explication' );
			}
			return m.length ? 'À compléter : ' + m.join( ', ' ) + '.' : '';
		}

		function dessiner( focus ) {
			liste.textContent = '';
			data.forEach( function ( q, i ) {
				var id = 'r18qz-' + ( ++uid );
				var msg = el( 'p', { class: 'r18qz__alerte', role: 'status' } );
				function maj() {
					ecrire();
					msg.textContent = alerte( q );
					msg.hidden = ! msg.textContent;
				}
				var reps = el( 'fieldset', { class: 'r18qz__reps' }, [ el( 'legend', { text: 'Réponses : coche la bonne' } ) ] );
				q.c.forEach( function ( c, j ) {
					var rid = id + '-c' + j;
					reps.appendChild( el( 'div', { class: 'r18qz__rep' + ( j === q.a ? ' is-ok' : '' ) }, [
						el( 'input', { type: 'radio', name: id + '-ok', id: rid + '-ok', 'aria-label': 'Bonne réponse : choix ' + ( j + 1 ), onchange: function () { q.a = j; dessiner(); } } ),
						el( 'label', { class: 'screen-reader-text', for: rid, text: 'Choix ' + ( j + 1 ) } ),
						el( 'input', { type: 'text', id: rid, value: c, placeholder: 'Réponse ' + ( j + 1 ), oninput: function ( e ) { q.c[ j ] = e.target.value; maj(); } } ),
						q.c.length > 2 ? el( 'button', { type: 'button', class: 'r18qz__x', 'aria-label': 'Retirer la réponse ' + ( j + 1 ), text: '×', onclick: function () { q.c.splice( j, 1 ); if ( q.a >= q.c.length ) { q.a = 0; } else if ( q.a > j ) { q.a--; } dessiner(); } } ) : null,
					] ) );
					reps.lastChild.querySelector( 'input[type=radio]' ).checked = j === q.a;
				} );
				if ( q.c.length < 6 ) {
					reps.appendChild( el( 'button', { type: 'button', class: 'button r18qz__plus', text: 'Ajouter une réponse', onclick: function () { q.c.push( '' ); dessiner(); } } ) );
				}
				var li = el( 'li', { class: 'r18qz__q' }, [
					el( 'div', { class: 'r18qz__hd' }, [
						el( 'span', { class: 'r18qz__num', text: 'Question ' + ( i + 1 ) } ),
						el( 'span', { class: 'r18qz__act' }, [
							i > 0 ? el( 'button', { type: 'button', class: 'button', 'aria-label': 'Monter la question ' + ( i + 1 ), text: '↑', onclick: function () { data.splice( i - 1, 0, data.splice( i, 1 )[ 0 ] ); dessiner(); } } ) : null,
							i < data.length - 1 ? el( 'button', { type: 'button', class: 'button', 'aria-label': 'Descendre la question ' + ( i + 1 ), text: '↓', onclick: function () { data.splice( i + 1, 0, data.splice( i, 1 )[ 0 ] ); dessiner(); } } ) : null,
							el( 'button', { type: 'button', class: 'button r18qz__suppr', text: 'Supprimer', onclick: function () { if ( window.confirm( 'Supprimer la question ' + ( i + 1 ) + ' ?' ) ) { data.splice( i, 1 ); dessiner(); } } } ),
						] ),
					] ),
					el( 'label', { for: id + '-q', class: 'r18qz__lab', text: 'Question' } ),
					el( 'textarea', { id: id + '-q', rows: '2', oninput: function ( e ) { q.q = e.target.value; maj(); } } ),
					reps,
					el( 'label', { for: id + '-why', class: 'r18qz__lab', text: 'Explication affichée après la réponse' } ),
					el( 'textarea', { id: id + '-why', rows: '2', oninput: function ( e ) { q.why = e.target.value; maj(); } } ),
					msg,
				] );
				li.querySelector( '#' + id + '-q' ).value = q.q;
				li.querySelector( '#' + id + '-why' ).value = q.why;
				liste.appendChild( li );
				msg.textContent = alerte( q );
				msg.hidden = ! msg.textContent;
			} );
			ecrire();
			if ( 'fin' === focus && liste.lastChild ) {
				liste.lastChild.querySelector( 'textarea' ).focus();
			}
		}

		bloc.appendChild( el( 'div', { class: 'r18qz__barre' }, [ compte, el( 'button', { type: 'button', class: 'button button-primary', text: 'Ajouter une question', onclick: function () { data.push( { q: '', c: [ '', '', '', '' ], a: 0, why: '' } ); dessiner( 'fin' ); } } ) ] ) );
		bloc.appendChild( liste );
		bloc.appendChild( brut );
		dessiner();
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		var q = document.querySelector( 'textarea[name="r18_questions"]' );
		if ( q ) {
			quiz( q );
		}
	} );
}() );

/* Choix d'une image dans la médiathèque à la place d'un identifiant à saisir. */
( function () {
	'use strict';
	document.addEventListener( 'DOMContentLoaded', function () {
		var champ = document.querySelector( 'input[name="r18_f_image2"]' );
		if ( ! champ || ! window.wp || ! wp.media ) {
			return;
		}
		champ.type = 'hidden';
		var zone = document.createElement( 'div' );
		zone.className = 'r18ui-media';
		var apercu = document.createElement( 'div' );
		apercu.className = 'r18ui-media__img';
		var choisir = document.createElement( 'button' );
		choisir.type = 'button';
		choisir.className = 'button';
		var retirer = document.createElement( 'button' );
		retirer.type = 'button';
		retirer.className = 'button-link r18ui-media__x';
		retirer.textContent = 'Retirer l\'image';
		zone.append( apercu, choisir, retirer );
		champ.insertAdjacentElement( 'afterend', zone );
		function montrer( url ) {
			apercu.innerHTML = '';
			if ( url ) {
				var i = document.createElement( 'img' );
				i.src = url;
				i.alt = '';
				apercu.appendChild( i );
			}
			apercu.hidden = ! url;
			retirer.hidden = ! champ.value;
			choisir.textContent = champ.value ? 'Changer l\'image' : 'Choisir une image';
		}
		if ( champ.value ) {
			wp.media.attachment( champ.value ).fetch().then( function ( a ) {
				montrer( ( a.sizes && a.sizes.medium && a.sizes.medium.url ) || a.url );
			} );
		}
		montrer( '' );
		var cadre;
		choisir.addEventListener( 'click', function () {
			cadre = cadre || wp.media( { title: 'Seconde image de la fiche', button: { text: 'Utiliser cette image' }, library: { type: 'image' }, multiple: false } );
			cadre.off( 'select' ).on( 'select', function () {
				var a = cadre.state().get( 'selection' ).first().toJSON();
				champ.value = a.id;
				montrer( ( a.sizes && a.sizes.medium && a.sizes.medium.url ) || a.url );
			} );
			cadre.open();
		} );
		retirer.addEventListener( 'click', function () {
			champ.value = '';
			montrer( '' );
		} );
	} );
}() );
