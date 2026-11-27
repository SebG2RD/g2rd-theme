/**
 * Outils › Préparer Rescue 18 : enchaîne les lots d'une étape jusqu'à la fin.
 */
( function () {
	'use strict';

	var cfg = window.rescue18Import;
	if ( ! cfg ) {
		return;
	}
	var log = document.querySelector( '[data-r18-log]' );
	var buttons = document.querySelectorAll( '[data-r18-step], [data-r18-all]' );

	function write( line ) {
		log.textContent += line + '\n';
		log.scrollTop = log.scrollHeight;
	}

	function status( step, text ) {
		var cell = document.querySelector( '[data-r18-status="' + step + '"]' );
		if ( cell ) {
			cell.textContent = text;
		}
	}

	function lock( on ) {
		buttons.forEach( function ( b ) {
			b.disabled = on;
		} );
	}

	function call( step, page ) {
		var body = new URLSearchParams();
		body.set( 'action', 'rescue18_import' );
		body.set( '_ajax_nonce', cfg.nonce );
		body.set( 'step', step );
		body.set( 'page', String( page ) );
		return fetch( cfg.ajax, {
			method: 'POST',
			credentials: 'same-origin',
			body: body,
		} ).then( function ( r ) {
			return r.json().catch( function () {
				throw new Error( 'Réponse illisible (HTTP ' + r.status + ')' );
			} );
		} );
	}

	function run( step ) {
		var page = 1;
		status( step, 'En cours…' );
		write( '— ' + step + ' —' );
		function next() {
			return call( step, page ).then( function ( res ) {
				if ( ! res || ! res.success ) {
					throw new Error( ( res && res.data && res.data.message ) || 'Erreur inconnue' );
				}
				( res.data.log || [] ).forEach( write );
				if ( res.data.next ) {
					status( step, 'Lot ' + page + ' / ' + res.data.total );
					page = res.data.next;
					return next();
				}
				status( step, 'Terminé ✓' );
			} );
		}
		return next().catch( function ( e ) {
			status( step, 'Erreur' );
			write( 'Erreur (' + step + ', lot ' + page + ') : ' + e.message + '. Relance l\'étape pour reprendre.' );
			throw e;
		} );
	}

	document.addEventListener( 'click', function ( e ) {
		var one = e.target.closest( '[data-r18-step]' );
		var all = e.target.closest( '[data-r18-all]' );
		if ( ! one && ! all ) {
			return;
		}
		lock( true );
		var chain;
		if ( one ) {
			chain = run( one.getAttribute( 'data-r18-step' ) );
		} else {
			var steps = Array.prototype.map.call( document.querySelectorAll( '[data-r18-step]' ), function ( b ) {
				return b.getAttribute( 'data-r18-step' );
			} );
			chain = steps.reduce( function ( p, s ) {
				return p.then( function () {
					return run( s );
				} );
			}, Promise.resolve() );
		}
		chain
			.catch( function () {} )
			.then( function () {
				lock( false );
				write( 'Fin.' );
			} );
	} );
} )();
