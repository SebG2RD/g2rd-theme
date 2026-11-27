/**
 * Boîte à outils Rescue 18 : catalogue filtrable et calculs en direct.
 *
 * Aucune donnée n'est envoyée. Les valeurs numériques et les choix sont
 * recopiés dans l'URL (lien partageable). Les textes libres restent locaux.
 */
( function () {
	'use strict';

	/* Outils communs ------------------------------------------------------ */

	var NF = {};
	function nf( n, d ) {
		var k = d || 0;
		if ( ! NF[ k ] ) {
			NF[ k ] = new Intl.NumberFormat( 'fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: k } );
		}
		return NF[ k ].format( n );
	}
	function esc( s ) {
		return String( s ).replace( /[&<>"']/g, function ( c ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ];
		} );
	}
	function minutes( m ) {
		if ( ! isFinite( m ) ) {
			return '–';
		}
		var t = Math.floor( m );
		var s = Math.round( ( m - t ) * 60 );
		if ( s === 60 ) {
			t += 1;
			s = 0;
		}
		if ( t >= 60 ) {
			return Math.floor( t / 60 ) + ' h ' + String( t % 60 ).padStart( 2, '0' );
		}
		return t + ' min' + ( s ? ' ' + String( s ).padStart( 2, '0' ) + ' s' : '' );
	}
	function clock( hhmm, add ) {
		var p = /^(\d{1,2}):(\d{2})/.exec( hhmm || '' );
		if ( ! p ) {
			return '';
		}
		var total = ( +p[ 1 ] * 60 + +p[ 2 ] + Math.floor( add ) ) % 1440;
		return String( Math.floor( total / 60 ) ).padStart( 2, '0' ) + ':' + String( total % 60 ).padStart( 2, '0' );
	}

	/**
	 * Afficheur : bloc principal + lignes.
	 *
	 * @param {Object} r { tone, k, v, unit, note, rows:[[label, value]], extra }
	 */
	function screen( r ) {
		var rows = ( r.rows || [] ).map( function ( row ) {
			return '<div><dt>' + esc( row[ 0 ] ) + '</dt><dd>' + esc( row[ 1 ] ) + '</dd></div>';
		} ).join( '' );
		return '<div class="r18-scr__main' + ( r.tone ? ' is-' + r.tone : '' ) + '">' +
			'<p class="r18-scr__k">' + esc( r.k ) + '</p>' +
			'<p class="r18-scr__v">' + esc( r.v ) + ( r.unit ? '<span>' + esc( r.unit ) + '</span>' : '' ) + '</p>' +
			( r.note ? '<p class="r18-scr__note">' + esc( r.note ) + '</p>' : '' ) +
			'</div>' + ( r.extra || '' ) + ( rows ? '<dl class="r18-scr__rows">' + rows + '</dl>' : '' );
	}
	function summary( title, r ) {
		var lines = [ title, r.k + ' : ' + r.v + ( r.unit ? ' ' + r.unit : '' ) ];
		if ( r.note ) {
			lines.push( r.note );
		}
		( r.rows || [] ).forEach( function ( row ) {
			lines.push( row[ 0 ] + ' : ' + row[ 1 ] );
		} );
		return lines.join( '\n' );
	}

	/* Calculs ------------------------------------------------------------- */

	var TOOLS = {
		'dimensionnement-dps': function ( v ) {
			var p1 = v.p1;
			var p = p1 <= 100000 ? p1 : 100000 + ( p1 - 100000 ) / 2;
			var i = v.p2 + v.e1 + v.e2;
			var ris = Math.round( i * p / 1000 * 1000 ) / 1000;
			var type;
			var tone;
			var n;
			if ( ris <= 0.25 ) {
				type = 'À l\'appréciation de l\'autorité de police';
				tone = 'ok';
				n = 0;
			} else if ( ris <= 1.125 ) {
				type = 'Point d\'alerte et de premiers secours (PAPS)';
				tone = 'ok';
				n = 2;
			} else {
				n = Math.ceil( ris - 1e-9 );
				if ( n % 2 ) {
					n += 1;
				}
				n = Math.max( 4, n );
				if ( ris <= 12 ) {
					type = 'DPS de petite envergure';
					tone = 'wait';
				} else if ( ris <= 36 ) {
					type = 'DPS de moyenne envergure';
					tone = 'alert';
				} else {
					type = 'DPS de grande envergure';
					tone = 'alert';
				}
			}
			var rows = [
				[ 'Ratio d\'intervenants secouristes (RIS)', nf( ris, 3 ) ],
				[ 'Indicateur de risque i = P2 + E1 + E2', nf( i, 2 ) ],
				[ 'Effectif pris en compte P', nf( p ) + ' personnes' ],
			];
			if ( n > 4 ) {
				rows.push( [ 'Postes de secours, au minimum', String( Math.max( ris > 36 ? 4 : ris > 12 ? 2 : 1, Math.ceil( n / 12 ) ) ) ] );
			}
			return {
				tone: tone,
				k: type,
				v: n ? String( n ) : '0',
				unit: n ? 'intervenants secouristes' : 'secouriste imposé',
				note: ris > 0.25 ? 'Les acteurs et participants font l\'objet d\'un calcul séparé.' : 'Sous 0,25, la grille n\'impose pas de dispositif.',
				rows: rows,
			};
		},

		'autonomie-ari': function ( v, raw, fail ) {
			if ( v.sifflet >= v.p ) {
				fail( 'sifflet', 'Le sifflet doit se déclencher sous la pression de départ.' );
				return null;
			}
			var air = v.p * v.v;
			var total = air / v.q;
			var retourP = v.p / 2;
			var toRetour = ( v.p - retourP ) * v.v / v.q;
			var toSifflet = ( v.p - v.sifflet ) * v.v / v.q;
			var pct = function ( x ) {
				return ( 100 * x / v.p ).toFixed( 2 ) + '%';
			};
			var gauge = '<div class="r18-gauge" role="img" aria-label="Jauge : pression de départ ' + nf( v.p ) + ' bar, retour à ' + nf( retourP ) + ' bar, sifflet à ' + nf( v.sifflet ) + ' bar">' +
				'<div class="r18-gauge__bar"><span class="r18-gauge__ret" style="left:' + pct( retourP ) + '"></span><span class="r18-gauge__sif" style="left:' + pct( v.sifflet ) + '"></span></div>' +
				'<div class="r18-gauge__lbl"><span style="left:' + pct( v.sifflet ) + '">Sifflet ' + nf( v.sifflet ) + '</span><span style="left:' + pct( retourP ) + '">Retour ' + nf( retourP ) + '</span><span style="left:100%">' + nf( v.p ) + ' bar</span></div></div>';
			var rows = [
				[ 'Pression de retour (moitié du départ)', nf( retourP ) + ' bar' ],
				[ 'Temps avant la pression de retour', minutes( toRetour ) ],
				[ 'Temps avant le sifflet', minutes( toSifflet ) ],
				[ 'Air disponible', nf( air ) + ' litres' ],
			];
			if ( raw.heure ) {
				rows.push( [ 'Heure de retour au plus tard', clock( raw.heure, toRetour ) ] );
				rows.push( [ 'Heure du sifflet', clock( raw.heure, toSifflet ) ] );
				rows.push( [ 'Fin théorique de l\'air', clock( raw.heure, total ) ] );
			}
			return {
				tone: v.p < 280 ? 'alert' : '',
				k: 'Autonomie théorique totale',
				v: minutes( total ),
				note: v.p < 280 ? 'Pression sous 280 bar : contrôle avant engagement non conforme.' : 'Valeur théorique. Le manomètre fait foi.',
				rows: rows,
				extra: gauge,
			};
		},

		noria: function ( v, raw, fail ) {
			if ( v.vit <= 0 ) {
				fail( 'vit', 'Saisis une vitesse supérieure à 0.' );
				return null;
			}
			var tRemp = v.cap / v.remp;
			var tVid = v.cap / v.vid;
			var tRoute = 2 * v.dist / v.vit * 60;
			var cycle = tRemp + tVid + tRoute + v.man;
			var parEngin = v.cap / cycle;
			var tenu = v.n * parEngin;
			var besoinN = v.besoin > 0 ? Math.ceil( v.besoin * cycle / v.cap - 1e-9 ) : 0;
			var ok = tenu >= v.besoin;
			var note = v.besoin <= 0 ? 'Indique un débit à tenir pour connaître le nombre d\'engins.'
				: ok ? 'Le besoin est couvert avec ' + v.n + ' engin' + ( v.n > 1 ? 's' : '' ) + '.'
					: 'Il manque ' + ( besoinN - v.n ) + ' engin' + ( besoinN - v.n > 1 ? 's' : '' ) + ' pour tenir ' + nf( v.besoin ) + ' L/min.';
			return {
				tone: v.besoin > 0 ? ( ok ? 'ok' : 'alert' ) : '',
				k: 'Débit moyen tenu par la noria',
				v: nf( tenu ),
				unit: 'L/min',
				note: note,
				rows: [
					[ 'Engins nécessaires pour le besoin', besoinN ? String( besoinN ) : '–' ],
					[ 'Durée d\'une rotation', minutes( cycle ) ],
					[ 'dont route aller-retour', minutes( tRoute ) ],
					[ 'dont remplissage et vidange', minutes( tRemp + tVid ) ],
					[ 'Débit apporté par engin', nf( parEngin ) + ' L/min' ],
					[ 'Intervalle entre deux arrivées', minutes( cycle / v.n ) ],
				],
			};
		},

		mousse: function ( v, raw, fail ) {
			var taux = parseFloat( raw.taux );
			var conc = parseFloat( raw.conc );
			var fois = parseFloat( raw.fois );
			if ( ! isFinite( taux ) || taux <= 0 ) {
				fail( 'taux', 'Choisis un taux d\'application.' );
				return null;
			}
			var q = v.surf * taux;
			var qe = q * conc / 100;
			var sol = q * v.duree;
			var emu = qe * v.duree;
			var mousse = sol * ( isFinite( fois ) ? fois : 0 ) / 1000;
			var lances = Math.ceil( q / v.lance - 1e-9 );
			var rows = [
				[ 'Débit d\'émulseur', nf( qe, 1 ) + ' L/min' ],
				[ 'Débit d\'eau', nf( q - qe, 1 ) + ' L/min' ],
				[ 'Émulseur pour ' + nf( v.duree ) + ' min', nf( Math.ceil( emu ) ) + ' litres' ],
				[ 'Eau pour ' + nf( v.duree ) + ' min', nf( Math.ceil( sol - emu ) ) + ' litres' ],
				[ 'Mousse produite', nf( mousse, 1 ) + ' m³' ],
				[ 'Lances ou générateurs à ' + nf( v.lance ) + ' L/min', String( lances ) ],
			];
			var tone = '';
			var note = 'Débit à tenir sans interruption pendant toute l\'application.';
			if ( v.stock > 0 ) {
				var auto = v.stock / qe;
				rows.push( [ 'Autonomie de la réserve', minutes( auto ) ] );
				tone = auto >= v.duree ? 'ok' : 'alert';
				note = auto >= v.duree ? 'La réserve couvre la durée d\'application.' : 'Réserve insuffisante : il manque ' + nf( Math.ceil( emu - v.stock ) ) + ' litres d\'émulseur.';
			}
			return { tone: tone, k: 'Débit de solution moussante', v: nf( q ), unit: 'L/min', note: note, rows: rows };
		},

		hydraulique: function ( v, raw, fail ) {
			var p = String( raw.tuyau || '' ).split( '/' );
			var jref = parseFloat( p[ 0 ] );
			var qref = parseFloat( p[ 1 ] );
			if ( ! isFinite( jref ) || ! isFinite( qref ) || qref <= 0 ) {
				fail( 'tuyau', 'Tuyau mal réglé au back-office : écris « pertes/débit », par exemple 1.5/250.' );
				return null;
			}
			var j = jref * Math.pow( v.debit / qref, 2 ) * v.long / 100;
			var z = v.deniv / 10;
			var pr = v.plance + j + z;
			var trop = pr > v.pmax;
			var rows = [
				[ 'Pertes dans les tuyaux', nf( j, 2 ) + ' bar' ],
				[ 'Pertes pour 100 m à ce débit', nf( jref * Math.pow( v.debit / qref, 2 ), 2 ) + ' bar' ],
				[ v.deniv >= 0 ? 'Dénivelé à vaincre' : 'Gain dû à la descente', nf( Math.abs( z ), 1 ) + ' bar' ],
				[ 'Pression à la lance', nf( v.plance, 1 ) + ' bar' ],
			];
			if ( v.debit > qref * 1.5 ) {
				rows.push( [ 'Débit nominal du tuyau', nf( qref ) + ' L/min, dépassé' ] );
			}
			return {
				tone: trop ? 'alert' : ( pr < v.plance ? 'wait' : 'ok' ),
				k: 'Pression à afficher au refoulement',
				v: nf( Math.max( 0, pr ), 1 ),
				unit: 'bar',
				note: trop ? 'Au-delà de la pression maximale de la pompe : réduis le débit, passe sur un tuyau plus gros ou ajoute une pompe en relais.' : 'Divisions, retenues et coudes ajoutent des pertes non comptées.',
				rows: rows,
			};
		},

		'indemnites-spv': function ( v ) {
			var base = v.grade;
			var inter = v.hinter * base;
			var form = v.hform * base;
			var garde = v.hgarde * base * v.tgarde / 100;
			var astr = v.hastr * base * v.tastr / 100;
			var total = inter + form + garde + astr;
			var eur2 = new Intl.NumberFormat( 'fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 } );
			var eur = function ( n ) {
				return eur2.format( n ) + ' €';
			};
			return {
				tone: 'ok',
				k: 'Indemnités estimées',
				v: eur2.format( total ),
				unit: '€',
				note: 'Exonérées d\'impôt sur le revenu et de cotisations sociales. Majorations de ton SDIS non comptées.',
				rows: [
					[ 'Taux horaire de base', eur( base ) ],
					[ 'Interventions', eur( inter ) ],
					[ 'Formation', eur( form ) ],
					[ 'Gardes', eur( garde ) ],
					[ 'Astreintes', eur( astr ) ],
				],
			};
		},

		'aide-memoire-ssuap': function ( v, raw ) {
			var g = v.y + v.v + v.m;
			var parts = String( raw.age || '' ).split( '/' );
			var range = function ( s ) {
				var m = /^\s*(\d+)\s*-\s*(\d+)\s*$/.exec( s || '' );
				return m ? [ +m[ 1 ], +m[ 2 ] ] : null;
			};
			var rfr = range( parts[ 0 ] );
			var rfc = range( parts[ 1 ] );
			var etat = function ( val, r ) {
				if ( ! r ) {
					return '';
				}
				var norme = ' (norme ' + r[ 0 ] + ' à ' + r[ 1 ] + ')';
				if ( val < r[ 0 ] ) {
					return val + '/min, basse' + norme;
				}
				if ( val > r[ 1 ] ) {
					return val + '/min, élevée' + norme;
				}
				return val + '/min, dans la norme' + norme;
			};
			var hors = ( rfr && ( v.fr < rfr[ 0 ] || v.fr > rfr[ 1 ] ) ) || ( rfc && ( v.fc < rfc[ 0 ] || v.fc > rfc[ 1 ] ) );
			var grave = g <= 8;
			return {
				tone: grave ? 'alert' : ( g < 15 || hors ? 'wait' : 'ok' ),
				k: 'Score de Glasgow',
				v: String( g ),
				unit: 'sur 15',
				note: grave ? 'Score de 8 ou moins : coma grave. Bilan d\'urgence vitale immédiat au médecin régulateur.' : 'Transmets le détail Y' + v.y + ' V' + v.v + ' M' + v.m + ' avec le total.',
				rows: [
					[ 'Détail', 'Y' + v.y + ' V' + v.v + ' M' + v.m ],
					[ 'Fréquence respiratoire', etat( v.fr, rfr ) ],
					[ 'Fréquence cardiaque', etat( v.fc, rfc ) ],
				],
			};
		},

		'messages-operationnels': function ( v, raw, fail, form ) {
			var labels = {};
			form.querySelectorAll( '[data-when]' ).forEach( function ( wrap ) {
				var input = wrap.querySelector( 'textarea, input' );
				var label = wrap.querySelector( 'label' );
				if ( input && label && ! wrap.hidden ) {
					labels[ input.name ] = label.textContent.trim();
				}
			} );
			var titles = {
				ambiance: 'MESSAGE D\'AMBIANCE',
				'compte-rendu': 'MESSAGE DE COMPTE RENDU',
				soiec: 'ORDRE INITIAL',
				patracdr: 'ORDRE PRÉPARATOIRE',
				dpif: 'ORDRE DE MOUVEMENT',
			};
			var head = [];
			if ( raw.de ) {
				head.push( 'DE ' + raw.de );
			}
			if ( raw.a ) {
				head.push( 'À ' + raw.a );
			}
			if ( raw.gh ) {
				head.push( 'GH ' + raw.gh.replace( ':', 'h' ) );
			}
			var lines = [ titles[ raw.type ] || 'MESSAGE' ];
			if ( head.length ) {
				lines.push( head.join( ' · ' ) );
			}
			var html = '<p class="r18-msg__t">' + esc( lines[ 0 ] ) + '</p>' + ( head.length ? '<p class="r18-msg__h">' + esc( lines[ 1 ] ) + '</p>' : '' ) + '<dl class="r18-msg">';
			var filled = 0;
			Object.keys( labels ).forEach( function ( name ) {
				var val = ( raw[ name ] || '' ).trim();
				if ( val ) {
					filled++;
				}
				html += '<dt>' + esc( labels[ name ].toUpperCase() ) + '</dt><dd' + ( val ? '' : ' class="is-empty"' ) + '>' + ( val ? esc( val ) : 'À compléter' ) + '</dd>';
				lines.push( labels[ name ].toUpperCase() + ' : ' + ( val || '…' ) );
			} );
			html += '</dl>';
			return {
				html: html,
				text: lines.join( '\n' ),
				label: filled + ' rubrique' + ( filled > 1 ? 's' : '' ) + ' sur ' + Object.keys( labels ).length,
			};
		},
	};

	window.rescue18Outils = TOOLS;

	/* Formulaire d'outil -------------------------------------------------- */

	function initTool( root ) {
		var slug = root.getAttribute( 'data-tool' );
		var compute = TOOLS[ slug ];
		var form = root.querySelector( '[data-tool-form]' );
		var out = root.querySelector( '[data-tool-screen]' );
		var status = root.querySelector( '[data-tool-status]' );
		if ( ! compute || ! form || ! out ) {
			return;
		}
		var title = ( document.querySelector( 'h1' ) || {} ).textContent || '';
		var lastText = '';
		var statusTimer;

		function say( msg ) {
			status.textContent = msg;
			clearTimeout( statusTimer );
			statusTimer = setTimeout( function () {
				status.textContent = '';
			}, 4000 );
		}

		function syncWhen() {
			var type = form.elements.type ? form.elements.type.value : '';
			form.querySelectorAll( '[data-when]' ).forEach( function ( w ) {
				w.hidden = w.getAttribute( 'data-when' ).split( ',' ).indexOf( type ) === -1;
			} );
		}

		function clearErrors() {
			form.querySelectorAll( '.r18-tf__err' ).forEach( function ( e ) {
				e.hidden = true;
				e.textContent = '';
			} );
			form.querySelectorAll( '[aria-invalid]' ).forEach( function ( e ) {
				e.removeAttribute( 'aria-invalid' );
			} );
		}

		function fail( name, msg ) {
			var el = form.elements[ name ];
			if ( ! el ) {
				return;
			}
			el.setAttribute( 'aria-invalid', 'true' );
			var err = el.closest( '.r18-tf' ).querySelector( '.r18-tf__err' );
			err.textContent = msg;
			err.hidden = false;
		}

		function read() {
			var raw = {};
			var num = {};
			var ok = true;
			Array.prototype.forEach.call( form.elements, function ( el ) {
				if ( ! el.name || el.closest( '[hidden]' ) ) {
					return;
				}
				raw[ el.name ] = el.value;
				if ( el.type === 'number' ) {
					var n = el.valueAsNumber;
					var min = el.min !== '' ? +el.min : -Infinity;
					var max = el.max !== '' ? +el.max : Infinity;
					if ( ! isFinite( n ) ) {
						fail( el.name, 'Saisis un nombre.' );
						ok = false;
					} else if ( n < min || n > max ) {
						fail( el.name, 'Saisis une valeur entre ' + nf( min ) + ' et ' + ( isFinite( max ) ? nf( max ) : 'plus' ) + '.' );
						ok = false;
					}
					num[ el.name ] = n;
				} else if ( el.tagName === 'SELECT' && /^-?\d+(\.\d+)?$/.test( el.value ) ) {
					num[ el.name ] = parseFloat( el.value );
				}
			} );
			return ok ? { raw: raw, num: num } : null;
		}

		var defaults = {};
		Array.prototype.forEach.call( form.elements, function ( el ) {
			if ( el.name ) {
				defaults[ el.name ] = el.value;
			}
		} );

		function syncUrl() {
			var params = new URLSearchParams();
			Array.prototype.forEach.call( form.elements, function ( el ) {
				if ( ! el.name || el.type === 'text' || el.tagName === 'TEXTAREA' ) {
					return;
				}
				if ( el.value !== defaults[ el.name ] ) {
					params.set( el.name, el.value );
				}
			} );
			var q = params.toString();
			history.replaceState( null, '', location.pathname + ( q ? '?' + q : '' ) + location.hash );
		}

		function update() {
			syncWhen();
			clearErrors();
			var data = read();
			if ( ! data ) {
				out.classList.add( 'is-stale' );
				return;
			}
			var r = compute( data.num, data.raw, fail, form );
			if ( ! r ) {
				out.classList.add( 'is-stale' );
				return;
			}
			out.classList.remove( 'is-stale' );
			if ( r.html ) {
				out.innerHTML = r.html;
				lastText = r.text;
			} else {
				out.innerHTML = screen( r );
				lastText = summary( title.trim(), r ) + '\n' + location.href;
			}
			syncUrl();
		}

		function copy( text, done ) {
			if ( navigator.clipboard && window.isSecureContext ) {
				navigator.clipboard.writeText( text ).then( function () {
					say( done );
				}, function () {
					fallback( text, done );
				} );
			} else {
				fallback( text, done );
			}
		}
		function fallback( text, done ) {
			var t = document.createElement( 'textarea' );
			t.value = text;
			t.setAttribute( 'readonly', '' );
			t.style.position = 'fixed';
			t.style.opacity = '0';
			document.body.appendChild( t );
			t.select();
			var ok = false;
			try {
				ok = document.execCommand( 'copy' );
			} catch ( e ) {}
			document.body.removeChild( t );
			say( ok ? done : 'Copie impossible. Sélectionne le texte à la main.' );
		}

		// Valeurs reçues par l'URL (lien partagé).
		var params = new URLSearchParams( location.search );
		params.forEach( function ( value, key ) {
			var el = form.elements[ key ];
			if ( el && el.type !== 'text' && el.tagName !== 'TEXTAREA' ) {
				el.value = value;
			}
		} );

		form.addEventListener( 'input', update );
		form.addEventListener( 'change', update );
		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
		} );
		form.addEventListener( 'reset', function () {
			setTimeout( update, 0 );
		} );
		root.querySelector( '[data-tool-copy]' ).addEventListener( 'click', function () {
			copy( lastText, slug === 'messages-operationnels' ? 'Message copié.' : 'Résultat copié.' );
		} );
		root.querySelector( '[data-tool-share]' ).addEventListener( 'click', function () {
			copy( location.href, 'Lien copié. Il rouvre l\'outil avec tes valeurs.' );
		} );
		root.querySelector( '[data-tool-print]' ).addEventListener( 'click', function () {
			window.print();
		} );
		update();
	}

	/* Catalogue ----------------------------------------------------------- */

	function initCatalogue( root ) {
		var input = root.querySelector( '[data-tk-search]' );
		var chips = root.querySelectorAll( '[data-mission]:not(li)' );
		var rows = root.querySelectorAll( '.r18-tk-row' );
		var count = root.querySelector( '[data-tk-count]' );
		var empty = root.querySelector( '[data-tk-empty]' );
		var mission = '';
		function norm( s ) {
			return s.toLowerCase().normalize( 'NFD' ).replace( /[̀-ͯ]/g, '' );
		}
		function apply() {
			var q = norm( input.value.trim() );
			var shown = 0;
			rows.forEach( function ( r ) {
				var hit = ( ! mission || r.getAttribute( 'data-mission' ) === mission ) && ( ! q || r.getAttribute( 'data-search' ).indexOf( q ) !== -1 );
				r.hidden = ! hit;
				shown += hit ? 1 : 0;
			} );
			count.textContent = shown + ' outil' + ( shown > 1 ? 's' : '' );
			empty.hidden = shown > 0;
		}
		input.addEventListener( 'input', apply );
		chips.forEach( function ( c ) {
			c.addEventListener( 'click', function () {
				mission = c.getAttribute( 'data-mission' );
				chips.forEach( function ( o ) {
					o.setAttribute( 'aria-pressed', o === c ? 'true' : 'false' );
				} );
				apply();
			} );
		} );
	}

	function boot() {
		document.querySelectorAll( '[data-tool]' ).forEach( initTool );
		document.querySelectorAll( '.r18-tk' ).forEach( initCatalogue );
	}
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
} )();
