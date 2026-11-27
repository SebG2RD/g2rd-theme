/**
 * Rescue 18 · Automatisations.
 *
 * Synchronisation 100 % asynchrone : le clic lance le cycle en AJAX,
 * le serveur le traite en arrière-plan, l'écran suit la progression
 * puis se met à jour sans rechargement. Test de connexion, filtres du
 * journal et confirmations passent par délégation d'événements, pour
 * survivre au rafraîchissement du contenu.
 */
( function () {
	'use strict';
	var cfg = window.r18Veille;
	if ( ! cfg ) {
		return;
	}
	var suiviActif = false;
	var niveau = '';

	function post( action, data ) {
		var body = new URLSearchParams( data || {} );
		body.set( 'action', action );
		body.set( '_ajax_nonce', cfg.nonce );
		return fetch( cfg.ajax, { method: 'POST', credentials: 'same-origin', body: body } ).then( function ( r ) {
			return r.json();
		} );
	}

	function zone() {
		return document.querySelector( '#wpbody-content .wrap' );
	}

	function urlPropre() {
		var u = new URL( window.location.href );
		u.searchParams.delete( 'r18msg' );
		u.hash = '';
		return u.toString();
	}

	// Boîte de progression : celle de l'écran, ou créée à la volée (annuaire).
	function boite() {
		var box = document.querySelector( '[data-r18v-progress]' );
		var w = zone();
		if ( box || ! w ) {
			return box;
		}
		box = document.createElement( 'section' );
		box.className = 'r18v-progress';
		box.setAttribute( 'data-r18v-progress', '' );
		box.setAttribute( 'aria-live', 'polite' );
		box.innerHTML = '<div class="r18v-progress__head"><strong data-r18v-progress-title></strong><span class="r18v-progress__right"><span data-r18v-progress-label></span><button type="button" class="r18v-btn is-stop" data-r18v-stop>Arrêter</button></span></div>' +
			'<div class="r18v-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><span style="width:0%"></span></div>' +
			'<p class="r18v-progress__src" data-r18v-progress-src></p>';
		var head = w.querySelector( ':scope > header' );
		if ( head ) {
			head.insertAdjacentElement( 'afterend', box );
		} else {
			w.insertBefore( box, w.firstChild );
		}
		return box;
	}

	function afficher( d, fini ) {
		var box = boite();
		if ( ! box ) {
			return;
		}
		box.hidden = false;
		var bar = box.querySelector( '.r18v-bar' );
		var pct = fini ? 100 : ( d.total ? Math.round( 100 * d.pos / d.total ) : 0 );
		bar.querySelector( 'span' ).style.width = pct + '%';
		bar.setAttribute( 'aria-valuenow', String( pct ) );
		var titre = box.querySelector( '[data-r18v-progress-title]' );
		if ( titre ) {
			titre.textContent = d.simulation ? 'Simulation en cours' : 'Synchronisation en cours';
		}
		var sans = d.inchanges ? ' · ' + d.inchanges + ' sans nouveauté' : '';
		var stop = box.querySelector( '[data-r18v-stop]' );
		if ( stop ) {
			stop.hidden = !! fini;
		}
		box.querySelector( '[data-r18v-progress-label]' ).textContent = fini ? 'Terminé' + sans : ( d.total ? d.pos + ' / ' + d.total + ' pages vérifiées' + sans : 'Préparation…' );
		box.querySelector( '[data-r18v-progress-src]' ).textContent = ! fini && d.source ? 'Source en cours : ' + d.source : '';
	}

	function message( texte, erreur ) {
		var w = zone();
		if ( ! w || ! texte ) {
			return;
		}
		var t = w.querySelector( '[data-r18v-toast]' );
		if ( ! t ) {
			t = document.createElement( 'div' );
			t.className = 'r18v-toast';
			t.setAttribute( 'data-r18v-toast', '' );
			t.setAttribute( 'role', 'status' );
			var box = boite();
			if ( box ) {
				box.insertAdjacentElement( 'beforebegin', t );
			} else {
				w.insertBefore( t, w.firstChild );
			}
		}
		t.innerHTML = '<span class="dashicons dashicons-' + ( erreur ? 'warning' : 'info' ) + '" aria-hidden="true"></span>';
		t.appendChild( document.createTextNode( texte ) );
	}

	function occuper( oui ) {
		document.querySelectorAll( 'form[data-r18v-sync] button' ).forEach( function ( b ) {
			b.disabled = oui;
			if ( oui ) {
				b.setAttribute( 'aria-disabled', 'true' );
				b.title = 'Un cycle est en cours : attends sa fin ou arrête-le.';
			} else {
				b.removeAttribute( 'aria-disabled' );
				b.removeAttribute( 'title' );
			}
		} );
	}

	// Recharge le contenu de l'écran (compteurs, cartes, journal) sans recharger la page.
	function rafraichir() {
		return fetch( urlPropre(), { credentials: 'same-origin' } ).then( function ( r ) {
			return r.text();
		} ).then( function ( html ) {
			var neuf = new DOMParser().parseFromString( html, 'text/html' ).querySelector( '#wpbody-content .wrap' );
			var w = zone();
			if ( neuf && w ) {
				w.innerHTML = neuf.innerHTML;
				niveau = '';
			}
		} );
	}

	function suivre() {
		if ( suiviActif ) {
			return;
		}
		suiviActif = true;
		occuper( true );
		var tick = function () {
			post( 'r18v_progres' ).then( function ( res ) {
				if ( ! res || ! res.success ) {
					throw new Error( 'progres' );
				}
				var d = res.data;
				if ( d.statut === 'en_cours' ) {
					afficher( d, false );
					setTimeout( tick, 2000 );
					return;
				}
				afficher( d, true );
				return rafraichir().then( function () {
					suiviActif = false;
					message( d.simulation ? 'Simulation terminée : le journal décrit les actions prévues.' : 'Synchronisation terminée : compteurs et journal à jour.' );
				} );
			} ).catch( function () {
				setTimeout( tick, 5000 );
			} );
		};
		tick();
	}

	// Lancement asynchrone : interception de tous les boutons de synchronisation.
	document.addEventListener( 'submit', function ( e ) {
		var f = e.target;
		if ( f.hasAttribute( 'data-confirm' ) && ! window.confirm( f.getAttribute( 'data-confirm' ) ) ) {
			e.preventDefault();
			return;
		}
		if ( ! f.hasAttribute( 'data-r18v-sync' ) ) {
			return;
		}
		e.preventDefault();
		if ( suiviActif ) {
			message( 'Un cycle est déjà en cours : la progression s\'affiche ci-dessous.' );
			return;
		}
		var donnees = new FormData( f );
		var simu = donnees.get( 'simulation' ) ? '1' : '';
		var btn = f.querySelector( 'button' );
		btn.setAttribute( 'aria-busy', 'true' );
		occuper( true );
		afficher( { pos: 0, total: 0, simulation: !! simu }, false );
		post( 'r18v_lancer', { cible: donnees.get( 'cible' ) || '', simulation: simu } ).then( function ( res ) {
			var d = ( res && res.data ) || {};
			message( d.message || 'Réponse inattendue.', ! res.success );
			if ( d.statut === 'en_cours' ) {
				suivre();
			} else {
				occuper( false );
				var box = document.querySelector( '[data-r18v-progress]' );
				if ( box ) {
					box.hidden = true;
				}
			}
		} ).catch( function () {
			message( 'Le lancement n\'a pas abouti. Réessaie dans un instant.', true );
			occuper( false );
		} ).finally( function () {
			btn.removeAttribute( 'aria-busy' );
		} );
	} );

	document.addEventListener( 'click', function ( e ) {
		// Arrêt du cycle en cours.
		var s = e.target.closest( '[data-r18v-stop]' );
		if ( s ) {
			if ( ! window.confirm( 'Arrêter le cycle en cours ? Le travail déjà fait est conservé, la suite reprendra au prochain cycle.' ) ) {
				return;
			}
			s.disabled = true;
			s.textContent = 'Arrêt…';
			post( 'r18v_arreter' ).then( function ( res ) {
				message( ( res && res.data && res.data.message ) || 'Arrêt demandé.' );
				if ( ! suiviActif ) {
					suivre();
				}
			} ).catch( function () {
				s.disabled = false;
				s.textContent = 'Arrêter';
				message( 'L\'arrêt n\'a pas abouti. Réessaie dans un instant.', true );
			} );
			return;
		}
		// Test de connexion.
		var b = e.target.closest( '[data-r18v-test]' );
		if ( b ) {
			var id = b.getAttribute( 'data-r18v-test' );
			var out = document.querySelector( '[data-r18v-test-out="' + id + '"]' );
			b.setAttribute( 'aria-busy', 'true' );
			out.hidden = false;
			out.className = 'r18v-test';
			out.textContent = 'Test en cours… (temporisation polie entre les requêtes)';
			post( 'r18v_tester', { connecteur: id } ).then( function ( res ) {
				var lignes = ( res.data && res.data.lignes ) || [ 'Réponse inattendue.' ];
				out.className = 'r18v-test ' + ( res.success ? 'is-ok' : 'is-err' );
				out.innerHTML = '';
				var ul = document.createElement( 'ul' );
				lignes.forEach( function ( l ) {
					var li = document.createElement( 'li' );
					li.textContent = l;
					ul.appendChild( li );
				} );
				out.appendChild( ul );
			} ).catch( function () {
				out.className = 'r18v-test is-err';
				out.textContent = 'Le test n\'a pas abouti (délai dépassé ou erreur serveur).';
			} ).finally( function () {
				b.removeAttribute( 'aria-busy' );
			} );
			return;
		}
		// Filtres du journal.
		var f = e.target.closest( '[data-r18v-log] [data-niv]:not(li)' );
		if ( f ) {
			niveau = f.getAttribute( 'data-niv' );
			f.parentNode.querySelectorAll( '[data-niv]' ).forEach( function ( o ) {
				o.setAttribute( 'aria-pressed', o === f ? 'true' : 'false' );
			} );
			filtrer();
		}
	} );

	document.addEventListener( 'change', function ( e ) {
		if ( e.target.matches( '[data-r18v-log] select[data-src]' ) ) {
			filtrer();
		}
	} );

	function filtrer() {
		var log = document.querySelector( '[data-r18v-log]' );
		if ( ! log ) {
			return;
		}
		var src = log.querySelector( 'select[data-src]' ).value;
		log.querySelectorAll( '.r18v-log__list li[data-niv]' ).forEach( function ( li ) {
			li.hidden = ! ( ( ! niveau || li.getAttribute( 'data-niv' ) === niveau ) && ( ! src || li.getAttribute( 'data-src' ) === src ) );
		} );
	}

	// Ancien paramètre de redirection : retiré de l'URL.
	if ( /[?&]r18msg=/.test( window.location.search ) && window.history.replaceState ) {
		window.history.replaceState( null, '', urlPropre() + window.location.hash );
	}

	// Cycle déjà en cours à l'ouverture de l'écran.
	var initiale = document.querySelector( '[data-r18v-progress]' );
	if ( initiale && ! initiale.hidden ) {
		message( 'Un cycle est en cours : les boutons de synchronisation se réactivent à sa fin. Tu peux aussi l\'arrêter.' );
		suivre();
	}
} )();
