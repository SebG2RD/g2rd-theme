/* Rescue 18 · carte des casernes (France ou monde).
   Données : OpenStreetMap, corrigées au back-office. Regroupement par
   Supercluster. Recherche par nom, casernes les plus proches de toi, liste
   lisible au clavier et par les lecteurs d'écran à côté de la carte. */
( function () {
	'use strict';

	function esc( s ) {
		return String( s ).replace( /[&<>"']/g, function ( c ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ];
		} );
	}
	function norm( s ) {
		return String( s || '' ).toLowerCase().normalize( 'NFD' ).replace( /[̀-ͯ]/g, '' );
	}
	function km( a, b, c, d ) {
		var r = Math.PI / 180;
		var x = Math.sin( ( c - a ) * r / 2 );
		var y = Math.sin( ( d - b ) * r / 2 );
		return 12742 * Math.asin( Math.sqrt( x * x + Math.cos( a * r ) * Math.cos( c * r ) * y * y ) );
	}
	var nf = new Intl.NumberFormat( 'fr-FR' );

	function init( root ) {
		var cfg = JSON.parse( root.getAttribute( 'data-r18-cc' ) || '{}' );
		var el = root.querySelector( '[data-cc-map]' );
		var res = root.querySelector( '[data-cc-res]' );
		var compte = root.querySelector( '[data-cc-compte]' );
		if ( ! window.L || ! window.Supercluster || ! el ) {
			return;
		}
		var map = L.map( el, { scrollWheelZoom: false, worldCopyJump: true } ).setView( [ cfg.centre[ 0 ], cfg.centre[ 1 ] ], cfg.centre[ 2 ] );
		L.tileLayer( cfg.tuiles, { maxZoom: cfg.max, attribution: cfg.attrib } ).addTo( map );
		map.on( 'focus', function () {
			map.scrollWheelZoom.enable();
		} );
		map.on( 'blur', function () {
			map.scrollWheelZoom.disable();
		} );
		var calque = L.layerGroup().addTo( map );
		var rendu = L.canvas( { padding: 0.5 } );
		var points = [];
		var index = null;
		var zones = null; // Monde : comptes par carreau.
		var charges = {};

		function popup( p ) {
			var nom = p[ 2 ] || 'Centre de secours';
			var html = '<strong class="r18-cc__nom">' + esc( nom ) + '</strong>';
			if ( p[ 4 ] ) {
				html += '<br><span>' + esc( p[ 4 ] ) + '</span>';
			}
			html += '<p class="r18-cc__adr" data-cc-adr>' + ( p[ 6 ] ? esc( p[ 6 ] ) : 'Recherche de l\'adresse…' ) + '</p>';
			html += '<p class="r18-cc__liens"><a href="https://www.openstreetmap.org/directions?to=' + p[ 0 ] + '%2C' + p[ 1 ] + '" target="_blank" rel="noopener">Itinéraire</a>';
			var id = String( p[ 3 ] || '' );
			var osm = /^([nwr])(\d+)$/.exec( id );
			if ( osm ) {
				html += ' · <a href="https://www.openstreetmap.org/' + { n: 'node', w: 'way', r: 'relation' }[ osm[ 1 ] ] + '/' + osm[ 2 ] + '" target="_blank" rel="noopener">Fiche OpenStreetMap</a>';
			}
			if ( p[ 5 ] && cfg.fiche ) {
				html += ' · <a href="' + cfg.fiche + p[ 5 ] + '">Modifier</a>';
			} else if ( osm && cfg.corriger ) {
				html += ' · <a href="' + cfg.corriger + id + '">Corriger</a>';
			}
			return html + '</p>';
		}

		function construire() {
			index = new Supercluster( { radius: 60, maxZoom: 15 } );
			index.load( points.map( function ( p, i ) {
				return { type: 'Feature', properties: { i: i }, geometry: { type: 'Point', coordinates: [ p[ 1 ], p[ 0 ] ] } };
			} ) );
		}

		function dessiner() {
			calque.clearLayers();
			var z = map.getZoom();
			if ( cfg.monde && z < 5 && zones ) {
				Object.keys( zones ).forEach( function ( k ) {
					var zz = zones[ k ];
					bulle( zz[ 1 ], zz[ 2 ], zz[ 0 ], function () {
						map.setView( [ zz[ 1 ], zz[ 2 ] ], 5 );
					} );
				} );
				return;
			}
			if ( ! index ) {
				return;
			}
			var b = map.getBounds();
			var w = Math.max( -180, b.getWest() );
			var e = Math.min( 180, b.getEast() );
			index.getClusters( [ w, Math.max( -85, b.getSouth() ), e, Math.min( 85, b.getNorth() ) ], z ).forEach( function ( f ) {
				var c = f.geometry.coordinates;
				if ( f.properties.cluster ) {
					bulle( c[ 1 ], c[ 0 ], f.properties.point_count, function () {
						map.setView( [ c[ 1 ], c[ 0 ] ], Math.min( index.getClusterExpansionZoom( f.properties.cluster_id ), cfg.max ) );
					} );
				} else {
					var p = points[ f.properties.i ];
					L.circleMarker( [ p[ 0 ], p[ 1 ] ], { renderer: rendu, radius: 6, color: '#fff', weight: 2, fillColor: p[ 5 ] ? '#11643B' : '#E21422', fillOpacity: 1 } )
						.on( 'click', function () {
							ouvrir( p );
						} ).addTo( calque );
				}
			} );
		}

		function bulle( lat, lng, n, clic ) {
			var t = n >= 1000 ? Math.round( n / 100 ) / 10 + ' k' : String( n );
			var s = n >= 1000 ? 'is-l' : n >= 100 ? 'is-m' : 'is-s';
			L.marker( [ lat, lng ], {
				icon: L.divIcon( { className: 'r18-cc__bulle ' + s, html: '<span>' + t + '</span>', iconSize: null } ),
				keyboard: true,
				title: nf.format( n ) + ' casernes, zoomer',
			} ).on( 'click', clic ).addTo( calque );
		}

		// Monde : chargement des carreaux visibles à partir du zoom 5.
		function carreauxVisibles() {
			var b = map.getBounds();
			var out = [];
			Object.keys( zones || {} ).forEach( function ( k ) {
				var m = /^g(\d+)_(\d+)$/.exec( k );
				var s = +m[ 1 ] - 60;
				var o = +m[ 2 ] - 180;
				if ( b.intersects( L.latLngBounds( [ s, o ], [ s + 15, o + 30 ] ) ) ) {
					out.push( k );
				}
			} );
			return out;
		}
		function chargerMonde() {
			if ( map.getZoom() < 5 ) {
				dessiner();
				return;
			}
			var manquants = carreauxVisibles().filter( function ( k ) {
				return ! charges[ k ];
			} );
			if ( ! manquants.length ) {
				dessiner();
				return;
			}
			Promise.all( manquants.map( function ( k ) {
				charges[ k ] = true;
				return fetch( cfg.data + k + '.json?v=' + cfg.v ).then( function ( r ) {
					return r.ok ? r.json() : [];
				} ).then( function ( pts ) {
					points = points.concat( pts );
				} ).catch( function () {
					charges[ k ] = false;
				} );
			} ) ).then( function () {
				construire();
				dessiner();
			} );
		}

		map.on( 'moveend', cfg.monde ? chargerMonde : dessiner );

		// Adresse absente d'OpenStreetMap : adresse la plus proche du point.
		var adresses = {};
		function remplirAdresse( pop, p ) {
			var box = pop.getElement();
			var adr = box && box.querySelector( '[data-cc-adr]' );
			if ( ! adr || p[ 6 ] ) {
				return;
			}
			var cle = p[ 0 ] + ',' + p[ 1 ];
			var poser = function ( texte, ville ) {
				var q = p.slice();
				q[ 6 ] = texte;
				if ( ville && ! q[ 2 ] ) {
					q[ 2 ] = 'Centre de secours ' + ( /^[aeiouyàâéèêîïôûh]/i.test( ville ) ? 'd\'' : 'de ' ) + ville;
				}
				if ( pop.isOpen() ) {
					pop.setContent( popup( q ) );
				}
			};
			if ( adresses[ cle ] ) {
				poser( adresses[ cle ][ 0 ], adresses[ cle ][ 1 ] );
				return;
			}
			var fr = ! cfg.monde || ( p[ 0 ] > 41 && p[ 0 ] < 51.2 && p[ 1 ] > -5.5 && p[ 1 ] < 9.7 );
			var url = fr
				? 'https://api-adresse.data.gouv.fr/reverse/?limit=1&lat=' + p[ 0 ] + '&lon=' + p[ 1 ]
				: 'https://photon.komoot.io/reverse?limit=1&lat=' + p[ 0 ] + '&lon=' + p[ 1 ];
			fetch( url ).then( function ( r ) {
				return r.json();
			} ).then( function ( d ) {
				var f = d && d.features && d.features[ 0 ];
				if ( ! f ) {
					poser( 'Adresse non renseignée.', '' );
					return;
				}
				var pr = f.properties;
				var ville = pr.city || '';
				var texte = fr ? pr.label : [ [ pr.housenumber, pr.street ].filter( Boolean ).join( ' ' ), [ pr.postcode, pr.city ].filter( Boolean ).join( ' ' ), pr.country ].filter( Boolean ).join( ', ' );
				adresses[ cle ] = [ 'Adresse la plus proche : ' + texte, ville ];
				poser( adresses[ cle ][ 0 ], ville );
			} ).catch( function () {
				poser( 'Adresse indisponible pour le moment.', '' );
			} );
		}
		function ouvrir( p ) {
			var pop = L.popup( { maxWidth: 300 } ).setLatLng( [ p[ 0 ], p[ 1 ] ] ).setContent( popup( p ) ).openOn( map );
			remplirAdresse( pop, p );
		}

		if ( cfg.monde ) {
			fetch( cfg.data + 'index.json?v=' + cfg.v ).then( function ( r ) {
				return r.ok ? r.json() : null;
			} ).then( function ( d ) {
				if ( d && d.zones ) {
					zones = d.zones;
					chargerMonde();
				}
			} );
		} else if ( cfg.n ) {
			fetch( cfg.data + '?v=' + cfg.v ).then( function ( r ) {
				return r.ok ? r.json() : [];
			} ).then( function ( pts ) {
				points = pts;
				construire();
				dessiner();
			} );
		}

		// Liste de résultats, utilisable sans la carte.
		function liste( titre, pts, distances ) {
			if ( ! pts.length ) {
				res.innerHTML = '<p>' + esc( titre ) + ' : aucune caserne trouvée.</p>';
				return;
			}
			var h = '<h2 class="r18-cc__titre">' + esc( titre ) + '</h2><ol class="r18-cc__liste">';
			pts.forEach( function ( p, i ) {
				h += '<li><button type="button" data-cc-i="' + i + '"><strong>' + esc( p[ 2 ] || 'Centre de secours' ) + '</strong>' +
					( p[ 6 ] ? '<span>' + esc( p[ 6 ] ) + '</span>' : p[ 4 ] ? '<span>' + esc( p[ 4 ] ) + '</span>' : '' ) +
					( distances ? '<span>' + distances[ i ].toFixed( 1 ).replace( '.', ',' ) + ' km</span>' : '' ) + '</button></li>';
			} );
			res.innerHTML = h + '</ol>';
			res.querySelectorAll( '[data-cc-i]' ).forEach( function ( btn ) {
				btn.addEventListener( 'click', function () {
					var p = pts[ +btn.getAttribute( 'data-cc-i' ) ];
					map.setView( [ p[ 0 ], p[ 1 ] ], 15, { animate: false } );
					ouvrir( p );
					el.scrollIntoView( { behavior: 'smooth', block: 'center' } );
				} );
			} );
		}

		root.querySelector( '[data-cc-form]' ).addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			var q = norm( root.querySelector( '[data-cc-q]' ).value.trim() );
			if ( q.length < 2 ) {
				return;
			}
			var brut = root.querySelector( '[data-cc-q]' ).value.trim();
			var found = points.filter( function ( p ) {
				return norm( p[ 2 ] + ' ' + p[ 4 ] ).indexOf( q ) !== -1;
			} ).slice( 0, 30 );
			if ( found.length >= 3 ) {
				liste( 'Casernes dont le nom contient « ' + brut + ' »', found );
				return;
			}
			// Sinon : la commune ou la ville, puis les casernes les plus proches.
			res.innerHTML = '<p>Recherche de « ' + esc( brut ) + ' »…</p>';
			var url = cfg.monde
				? 'https://photon.komoot.io/api/?limit=1&q=' + encodeURIComponent( brut )
				: 'https://api-adresse.data.gouv.fr/search/?type=municipality&limit=1&q=' + encodeURIComponent( brut );
			fetch( url ).then( function ( r ) {
				return r.json();
			} ).then( function ( d ) {
				var f = d && d.features && d.features[ 0 ];
				if ( ! f ) {
					liste( 'Résultats pour « ' + brut + ' »', found );
					return;
				}
				var lo = f.geometry.coordinates[ 0 ];
				var la = f.geometry.coordinates[ 1 ];
				var nom = ( f.properties.city || f.properties.name || brut ) + ( f.properties.postcode ? ' (' + f.properties.postcode + ')' : f.properties.country ? ', ' + f.properties.country : '' );
				map.setView( [ la, lo ], 11 );
				setTimeout( function () {
					proches( la, lo, 'Casernes les plus proches de ' + nom );
				}, cfg.monde ? 1500 : 0 );
			} ).catch( function () {
				liste( 'Résultats pour « ' + brut + ' »', found );
			} );
		} );

		function proches( la, lo, titre ) {
			var tri = points.map( function ( p ) {
				return [ p, km( la, lo, p[ 0 ], p[ 1 ] ) ];
			} ).sort( function ( a, b ) {
				return a[ 1 ] - b[ 1 ];
			} ).slice( 0, 8 );
			liste( titre, tri.map( function ( t ) {
				return t[ 0 ];
			} ), tri.map( function ( t ) {
				return t[ 1 ];
			} ) );
		}

		root.querySelector( '[data-cc-moi]' ).addEventListener( 'click', function () {
			if ( ! navigator.geolocation ) {
				res.innerHTML = '<p>Ton navigateur ne donne pas ta position.</p>';
				return;
			}
			res.innerHTML = '<p>Recherche de ta position…</p>';
			navigator.geolocation.getCurrentPosition( function ( pos ) {
				var la = pos.coords.latitude;
				var lo = pos.coords.longitude;
				map.setView( [ la, lo ], 11 );
				var fin = function () {
					proches( la, lo, 'Les casernes les plus proches de toi' );
				};
				// Monde : laisse le temps aux carreaux de se charger.
				setTimeout( fin, cfg.monde ? 1500 : 0 );
			}, function () {
				res.innerHTML = '<p>Position refusée ou indisponible. Cherche une commune dans le champ ci-dessus.</p>';
			}, { timeout: 10000, maximumAge: 600000 } );
		} );

		root.querySelectorAll( '[data-cc-saut]' ).forEach( function ( b ) {
			b.addEventListener( 'click', function () {
				var v = b.getAttribute( 'data-cc-saut' ).split( ',' );
				map.setView( [ +v[ 0 ], +v[ 1 ] ], +v[ 2 ] );
			} );
		} );

		if ( compte && cfg.n ) {
			compte.textContent = nf.format( cfg.n ) + ' casernes sur la carte';
		}
	}

	function boot() {
		document.querySelectorAll( '[data-r18-cc]' ).forEach( init );
	}
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
