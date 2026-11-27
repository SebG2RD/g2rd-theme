/**
 * Rescue 18 · quiz public.
 *
 * Une question à la fois, correction immédiate et explication, score final.
 * Aucun compte, aucun score enregistré ni envoyé. Sans JavaScript, la liste
 * statique des questions reste affichée par le serveur.
 *
 * Un groupe portant la classe « r18-quiz-steps » (colonne latérale) reçoit
 * l'indicateur de progression.
 */
( () => {
	'use strict';

	const ICON_ARROW = '<svg class="r18-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12h16m-7-7 7 7-7 7"/></svg>';
	const ICON_INFO = '<svg class="r18-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 11v6M12 7.5v.5"/></svg>';
	const KEYS = 'ABCDEFGH';

	const el = ( tag, cls, text ) => {
		const n = document.createElement( tag );
		if ( cls ) {
			n.className = cls;
		}
		if ( text !== undefined ) {
			n.textContent = text;
		}
		return n;
	};

	function mount( root ) {
		const cfgNode = root.querySelector( '[data-r18-quiz-config]' );
		const app = root.querySelector( '[data-r18-quiz-app]' );
		if ( ! cfgNode || ! app ) {
			return;
		}
		let cfg;
		try {
			cfg = JSON.parse( cfgNode.textContent );
		} catch ( e ) {
			return;
		}
		const Q = cfg.questions || [];
		const L = cfg.labels || {};
		const stepsHost = document.querySelector( '.r18-quiz-steps' );
		let steps = null;
		if ( stepsHost ) {
			steps = el( 'div', 'r18-steps' );
			steps.setAttribute( 'aria-hidden', 'true' );
			stepsHost.appendChild( steps );
		}
		const s = { i: 0, picked: null, score: 0, done: false };

		const drawSteps = () => {
			if ( ! steps ) {
				return;
			}
			steps.textContent = '';
			Q.forEach( ( _, n ) => {
				const cls = s.done || n < s.i ? 'is-done' : ( n === s.i ? 'is-cur' : '' );
				steps.appendChild( el( 'i', cls, String( n + 1 ) ) );
			} );
		};

		const render = () => {
			app.textContent = '';
			drawSteps();
			if ( s.done ) {
				const pct = Math.round( ( s.score / Q.length ) * 100 );
				const res = el( 'div', 'r18-result' );
				const sc = el( 'div', 'r18-score' );
				sc.style.setProperty( '--p', pct + '%' );
				sc.appendChild( el( 'b', '', s.score + '/' + Q.length ) );
				res.appendChild( sc );
				res.appendChild( el( 'h2', '', s.score === Q.length ? L.perfect : ( s.score / Q.length >= 0.6 ? L.fine : L.retry ) ) );
				res.appendChild( el( 'p', '', L.note ) );
				const acts = el( 'div', 'r18-result__acts' );
				const again = el( 'button', 'r18-btn r18-btn--red', L.restart );
				again.type = 'button';
				again.addEventListener( 'click', () => {
					Object.assign( s, { i: 0, picked: null, score: 0, done: false } );
					render();
				} );
				const share = el( 'button', 'r18-btn r18-btn--white', L.share );
				share.type = 'button';
				share.addEventListener( 'click', () => {
					const text = document.title + ' : ' + s.score + '/' + Q.length;
					if ( navigator.share ) {
						navigator.share( { title: document.title, text, url: window.location.href } ).catch( () => {} );
					} else if ( navigator.clipboard ) {
						navigator.clipboard.writeText( text + ' ' + window.location.href ).then( () => {
							share.textContent = L.copied;
						} );
					}
				} );
				const more = el( 'a', 'r18-btn r18-btn--white', L.others );
				more.href = cfg.more || '#';
				acts.append( again, share, more );
				res.appendChild( acts );
				app.appendChild( res );
				res.querySelector( 'h2' ).setAttribute( 'tabindex', '-1' );
				res.querySelector( 'h2' ).focus();
				return;
			}

			const cur = Q[ s.i ];
			const answered = s.picked !== null;
			const prog = el( 'div', 'r18-prog' );
			const bar = el( 'div', 'r18-prog__bar' );
			const fill = el( 'div', 'r18-prog__fill' );
			fill.style.width = Math.round( ( ( s.i + ( answered ? 1 : 0 ) ) / Q.length ) * 100 ) + '%';
			bar.appendChild( fill );
			prog.append( el( 'span', '', L.question + ' ' + ( s.i + 1 ) + ' / ' + Q.length ), bar, el( 'span', '', L.score + ' ' + s.score ) );
			app.appendChild( prog );

			const h = el( 'h2', 'r18-q', cur.q );
			h.setAttribute( 'tabindex', '-1' );
			app.appendChild( h );

			const opts = el( 'div', 'r18-opts' );
			cur.c.forEach( ( text, idx ) => {
				const b = el( 'button', 'r18-opt' );
				b.type = 'button';
				b.append( el( 'span', 'r18-opt__k', KEYS[ idx ] || String( idx + 1 ) ), document.createTextNode( text ) );
				if ( answered ) {
					b.disabled = true;
					b.classList.add( idx === cur.a ? 'is-correct' : ( idx === s.picked ? 'is-wrong' : 'is-dim' ) );
				}
				b.addEventListener( 'click', () => {
					if ( s.picked !== null ) {
						return;
					}
					s.picked = idx;
					s.score += idx === cur.a ? 1 : 0;
					render();
					app.querySelector( '.r18-fb' )?.focus();
				} );
				opts.appendChild( b );
			} );
			app.appendChild( opts );

			if ( answered ) {
				const fb = el( 'div', 'r18-fb' );
				fb.setAttribute( 'tabindex', '-1' );
				fb.innerHTML = ICON_INFO;
				const span = el( 'span' );
				span.append( el( 'b', '', ( s.picked === cur.a ? L.good : L.bad ) + ' ' ), document.createTextNode( cur.why || '' ) );
				fb.appendChild( span );
				app.appendChild( fb );
			}

			const acts = el( 'div', 'r18-qactions' );
			const last = s.i === Q.length - 1;
			const next = el( 'button', 'r18-btn r18-btn--red' );
			next.type = 'button';
			next.disabled = ! answered;
			next.innerHTML = ( last ? L.result : L.next ) + ' ' + ICON_ARROW;
			next.addEventListener( 'click', () => {
				if ( last ) {
					s.done = true;
				} else {
					s.i++;
					s.picked = null;
				}
				render();
				if ( ! s.done ) {
					app.querySelector( '.r18-q' ).focus();
				}
			} );
			acts.append( el( 'span', '', L.checked ), next );
			app.appendChild( acts );
		};

		render();
	}

	function init() {
		document.querySelectorAll( '[data-r18-quiz]' ).forEach( ( root ) => {
			if ( ! root.dataset.r18Init ) {
				root.dataset.r18Init = '1';
				mount( root );
			}
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
