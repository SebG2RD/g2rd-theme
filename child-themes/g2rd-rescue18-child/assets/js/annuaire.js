/* Rescue 18 · Annuaire des SDIS, fil des actualités, offres, concours.
   Sans dépendance hormis Leaflet (carte). Tout fonctionne aussi sans carte. */
( function () {
	'use strict';

	var plat = function ( s ) {
		return String( s || '' ).toLowerCase().normalize( 'NFD' ).replace( /[̀-ͯ]/g, '' ).replace( /[’']/g, "'" ).trim();
	};
	var pluriel = function ( n, un, plusieurs ) {
		return n + ' ' + ( n > 1 ? plusieurs : un );
	};

	/* Images externes : repli si le site d'origine ne répond pas. */
	var casse = function ( img ) {
		img.classList.add( 'is-broken' );
	};
	document.addEventListener( 'error', function ( e ) {
		var t = e.target;
		if ( t && t.tagName === 'IMG' && t.hasAttribute( 'data-r18-img' ) ) {
			casse( t );
		}
	}, true );
	document.addEventListener( 'load', function ( e ) {
		var t = e.target;
		if ( t && t.tagName === 'IMG' && t.hasAttribute( 'data-r18-img' ) ) {
			t.classList.add( t.naturalWidth > 0 ? 'is-loaded' : 'is-broken' );
		}
	}, true );
	document.querySelectorAll( 'img[data-r18-img]' ).forEach( function ( img ) {
		if ( img.complete ) {
			img.classList.add( img.naturalWidth > 0 ? 'is-loaded' : 'is-broken' );
		}
	} );

	/* Carte -------------------------------------------------------------- */
	var fond = function ( L, carte ) {
		if ( ! carte.tuiles ) {
			return null;
		}
		return L.tileLayer( carte.tuiles, { maxZoom: carte.max || 18, attribution: carte.attrib, crossOrigin: true } );
	};
	var ZONES = {
		metro: [ [ 41.2, -5.3 ], [ 51.2, 9.7 ] ],
		971: [ [ 15.8, -61.85 ], [ 16.55, -61.0 ] ],
		972: [ [ 14.38, -61.25 ], [ 14.9, -60.8 ] ],
		973: [ [ 2.1, -54.6 ], [ 5.8, -51.6 ] ],
		974: [ [ -21.4, 55.2 ], [ -20.85, 55.85 ] ],
		976: [ [ -13.05, 45.0 ], [ -12.6, 45.3 ] ]
	};
	var distance = function ( a, b, c, d ) {
		var r = Math.PI / 180, x = ( d - b ) * r * Math.cos( ( a + c ) * r / 2 ), y = ( c - a ) * r;
		return Math.sqrt( x * x + y * y ) * 6371;
	};

	/* Annuaire ----------------------------------------------------------- */
	var annuaire = function ( root ) {
		var data;
		try {
			data = JSON.parse( root.querySelector( '[data-r18-data]' ).textContent );
		} catch ( e ) {
			return;
		}
		var svc = data.services || [];
		var q = root.querySelector( '[data-r18-q]' );
		var reg = root.querySelector( '[data-r18-region]' );
		var list = root.querySelector( '[data-r18-list]' );
		var count = root.querySelector( '[data-r18-count]' );
		var none = root.querySelector( '[data-r18-none]' );
		var geo = root.querySelector( '[data-r18-geo]' );
		var zones = root.querySelector( '[data-r18-zones]' );
		var mapEl = root.querySelector( '[data-r18-map]' );
		var map = null, pins = {}, actif = null, L = window.L;
		var parId = {};
		svc.forEach( function ( s ) {
			parId[ s.id ] = s;
		} );

		var activer = function ( id, centrer ) {
			if ( actif && pins[ actif ] ) {
				var e0 = pins[ actif ].getElement();
				if ( e0 ) {
					e0.classList.remove( 'is-active' );
				}
			}
			if ( list ) {
				list.querySelectorAll( '.is-active' ).forEach( function ( li ) {
					li.classList.remove( 'is-active' );
				} );
				var li = list.querySelector( '[data-id="' + id + '"]' );
				if ( li ) {
					li.classList.add( 'is-active' );
					if ( ! centrer ) {
						li.scrollIntoView( { block: 'nearest', behavior: 'smooth' } );
					}
				}
			}
			actif = id;
			if ( map && pins[ id ] ) {
				var el = pins[ id ].getElement();
				if ( el ) {
					el.classList.add( 'is-active' );
				}
				if ( centrer ) {
					map.flyTo( pins[ id ].getLatLng(), 10, { duration: 0.8 } );
					pins[ id ].openPopup();
				}
			}
		};

		var filtrer = function () {
			var t = plat( q ? q.value : '' );
			var r = reg ? reg.value : '';
			var n = 0, visibles = [];
			// Un numéro seul (27, 2A, 974) cible le département exact.
			var num = /^(\d{2,3}|2a|2b)$/.test( t ) ? t.toUpperCase() : '';
			svc.forEach( function ( s ) {
				var ok = ( ! r || s.r === r ) && ( ! t || ( num ? s.d.indexOf( num ) !== -1 : s.k.indexOf( t ) !== -1 ) );
				if ( list ) {
					var li = list.querySelector( '[data-id="' + s.id + '"]' );
					if ( li ) {
						li.hidden = ! ok;
					}
				}
				if ( map && pins[ s.id ] ) {
					var el = pins[ s.id ].getElement();
					if ( el ) {
						el.style.display = ok ? '' : 'none';
					}
				}
				if ( ok ) {
					n++;
					visibles.push( s );
				}
			} );
			if ( count ) {
				count.textContent = pluriel( n, 'service', 'services' );
			}
			if ( none ) {
				none.hidden = n > 0;
			}
			if ( map && ( t || r ) && visibles.length ) {
				var pts = visibles.filter( function ( s ) {
					return s.la && s.ln;
				} ).map( function ( s ) {
					return [ s.la, s.ln ];
				} );
				if ( pts.length === 1 ) {
					map.flyTo( pts[ 0 ], 9, { duration: 0.6 } );
				} else if ( pts.length ) {
					map.flyToBounds( pts, { padding: [ 40, 40 ], maxZoom: 9, duration: 0.6 } );
				}
			}
			if ( visibles.length === 1 ) {
				activer( visibles[ 0 ].id, false );
			}
		};
		var minuteur;
		if ( q ) {
			q.addEventListener( 'input', function () {
				clearTimeout( minuteur );
				minuteur = setTimeout( filtrer, 160 );
			} );
			q.addEventListener( 'keydown', function ( e ) {
				if ( e.key === 'Enter' ) {
					e.preventDefault();
					filtrer();
					var first = list && list.querySelector( '.r18-ann__item:not([hidden]) a' );
					if ( first && svc.filter( function ( s ) {
						return ! list.querySelector( '[data-id="' + s.id + '"]' ).hidden;
					} ).length === 1 ) {
						first.focus();
					}
				}
			} );
		}
		if ( reg ) {
			reg.addEventListener( 'change', filtrer );
		}

		// Carte (si Leaflet est chargé).
		if ( L && mapEl ) {
			map = L.map( mapEl, { scrollWheelZoom: false, zoomControl: true, preferCanvas: false, attributionControl: true } );
			map.attributionControl.setPrefix( '<a href="https://leafletjs.com">Leaflet</a>' );
			var f = fond( L, data.carte || {} );
			if ( f ) {
				f.addTo( map );
			}
			map.fitBounds( ZONES.metro );
			mapEl.addEventListener( 'click', function () {
				map.scrollWheelZoom.enable();
			} );
			mapEl.addEventListener( 'mouseleave', function () {
				map.scrollWheelZoom.disable();
			} );
			svc.forEach( function ( s ) {
				if ( ! s.la || ! s.ln ) {
					return;
				}
				var code = s.g === 'bspp' ? 'P' : ( s.g === 'bmpm' ? 'M' : ( s.d[ 0 ] || '' ) );
				var icon = L.divIcon( { className: 'r18-pin is-' + s.g, html: '<span>' + code + '</span>', iconSize: [ 34, 34 ] } );
				var m = L.marker( [ s.la, s.ln ], { icon: icon, title: s.t, alt: s.t, keyboard: true, riseOnHover: true } ).addTo( map );
				var pop = document.createElement( 'div' );
				pop.className = 'r18-pop';
				var st = document.createElement( 'strong' );
				st.textContent = s.t;
				pop.appendChild( st );
				if ( s.v ) {
					var pv = document.createElement( 'p' );
					pv.textContent = s.v;
					pop.appendChild( pv );
				}
				if ( s.tel ) {
					var pt = document.createElement( 'p' );
					var at = document.createElement( 'a' );
					at.href = 'tel:' + s.tel.replace( /[^\d+]/g, '' );
					at.textContent = s.tel;
					pt.appendChild( at );
					pop.appendChild( pt );
				}
				var pa = document.createElement( 'p' );
				var a = document.createElement( 'a' );
				a.href = s.u;
				a.textContent = 'Ouvrir la fiche →';
				pa.appendChild( a );
				pop.appendChild( pa );
				m.bindPopup( pop );
				m.on( 'click', function () {
					activer( s.id, false );
				} );
				pins[ s.id ] = m;
			} );

			// Casernes OpenStreetMap, chargées à la demande.
			var cas = data.carte && data.carte.casernes;
			if ( cas ) {
				var Ctl = L.Control.extend( {
					onAdd: function () {
						var d = L.DomUtil.create( 'div', 'r18-ctl' );
						var b = L.DomUtil.create( 'button', '', d );
						b.type = 'button';
						b.setAttribute( 'aria-pressed', 'false' );
						b.textContent = 'Casernes (' + cas.n.toLocaleString( 'fr-FR' ) + ')';
						L.DomEvent.disableClickPropagation( d );
						var couche = null, charge = false;
						b.addEventListener( 'click', function () {
							var on = b.getAttribute( 'aria-pressed' ) !== 'true';
							b.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
							if ( ! on ) {
								if ( couche ) {
									map.removeLayer( couche );
								}
								return;
							}
							if ( couche ) {
								couche.addTo( map );
								return;
							}
							if ( charge ) {
								return;
							}
							charge = true;
							b.textContent = 'Chargement…';
							fetch( cas.url ).then( function ( r ) {
								return r.json();
							} ).then( function ( pts ) {
								var rend = L.canvas( { padding: 0.5 } );
								couche = L.layerGroup();
								pts.forEach( function ( p ) {
									var c = L.circleMarker( [ p[ 0 ], p[ 1 ] ], { renderer: rend, radius: 3.5, weight: 1, color: '#fff', fillColor: '#C90E1B', fillOpacity: 0.9 } );
									if ( p[ 2 ] ) {
										c.bindTooltip( p[ 2 ] );
									}
									couche.addLayer( c );
								} );
								couche.addTo( map );
								map.attributionControl.addAttribution( cas.attrib );
								b.textContent = 'Casernes (' + pts.length.toLocaleString( 'fr-FR' ) + ')';
							} ).catch( function () {
								b.textContent = 'Casernes indisponibles';
								b.setAttribute( 'aria-pressed', 'false' );
								charge = false;
							} );
						} );
						return d;
					}
				} );
				new Ctl( { position: 'topright' } ).addTo( map );
			}

			if ( zones ) {
				zones.hidden = false;
				zones.addEventListener( 'click', function ( e ) {
					var b = e.target.closest( '[data-r18-zone]' );
					if ( b && ZONES[ b.dataset.r18Zone ] ) {
						map.flyToBounds( ZONES[ b.dataset.r18Zone ], { duration: 0.8 } );
					}
				} );
			}
			if ( list ) {
				list.querySelectorAll( '[data-r18-voir]' ).forEach( function ( b ) {
					b.hidden = false;
					b.addEventListener( 'click', function () {
						activer( Number( b.dataset.r18Voir ), true );
						mapEl.scrollIntoView( { block: 'nearest', behavior: 'smooth' } );
					} );
				} );
			}
		}

		// Autour de moi : service le plus proche.
		if ( geo && 'geolocation' in navigator ) {
			geo.hidden = false;
			geo.addEventListener( 'click', function () {
				geo.setAttribute( 'aria-busy', 'true' );
				navigator.geolocation.getCurrentPosition( function ( pos ) {
					geo.removeAttribute( 'aria-busy' );
					var la = pos.coords.latitude, ln = pos.coords.longitude, best = null, bd = Infinity;
					svc.forEach( function ( s ) {
						if ( s.la && s.ln && s.g !== 'bmpm' ) {
							var d = distance( la, ln, s.la, s.ln );
							if ( d < bd ) {
								bd = d;
								best = s;
							}
						}
					} );
					if ( best ) {
						if ( q ) {
							q.value = best.d[ 0 ] || '';
						}
						filtrer();
						activer( best.id, true );
						if ( count ) {
							count.textContent = 'Le service le plus proche : ' + best.t;
						}
					}
				}, function () {
					geo.removeAttribute( 'aria-busy' );
					if ( count ) {
						count.textContent = 'Position non disponible. Tape ton numéro de département.';
					}
				}, { timeout: 10000, maximumAge: 600000 } );
			} );
		}
	};

	/* Mini-carte d'une fiche --------------------------------------------- */
	var mini = function ( el ) {
		var L = window.L;
		if ( ! L ) {
			return;
		}
		var d;
		try {
			d = JSON.parse( el.getAttribute( 'data-r18-mini' ) );
		} catch ( e ) {
			return;
		}
		var m = L.map( el, { scrollWheelZoom: false, dragging: ! L.Browser.mobile, zoomControl: true } ).setView( [ d.la, d.ln ], 14 );
		m.attributionControl.setPrefix( false );
		var f = fond( L, d.carte || {} );
		if ( f ) {
			f.addTo( m );
		}
		L.marker( [ d.la, d.ln ], { icon: L.divIcon( { className: 'r18-pin', html: '<span>18</span>', iconSize: [ 34, 34 ] } ), alt: d.t, keyboard: false } ).addTo( m );
	};

	/* Filtres génériques (fil, offres, concours) -------------------------- */
	var filtres = function ( root, cfg ) {
		var items = Array.prototype.slice.call( root.querySelectorAll( cfg.item ) );
		var count = root.querySelector( cfg.count );
		var none = root.querySelector( cfg.none );
		var more = root.querySelector( '[data-r18-fs-more]' );
		var pas = 24, montre = pas;
		var get = function ( sel ) {
			return root.querySelector( sel );
		};
		var applique = function () {
			var v = {
				q: cfg.q && get( cfg.q ) ? plat( get( cfg.q ).value ) : '',
				r: cfg.region && get( cfg.region ) ? get( cfg.region ).value : '',
				d: cfg.dep && get( cfg.dep ) ? get( cfg.dep ).value : '',
				rec: cfg.rec && get( cfg.rec ) ? get( cfg.rec ).checked : false,
				cat: ''
			};
			if ( cfg.cat ) {
				var c = root.querySelector( cfg.cat + ':checked' );
				v.cat = c ? c.value : '';
			}
			var n = 0;
			items.forEach( function ( it ) {
				var ok = ( ! v.r || it.dataset.region === v.r ) && ( ! v.d || it.dataset.dep === v.d ) && ( ! v.rec || it.dataset.rec === '1' ) && ( ! v.cat || it.dataset.cat === v.cat ) && ( ! v.q || ( it.dataset.k || '' ).indexOf( v.q ) !== -1 );
				if ( ok ) {
					n++;
				}
				it.hidden = ! ok || ( more ? n > montre : false );
			} );
			if ( count ) {
				count.textContent = cfg.label( n );
			}
			if ( none ) {
				none.hidden = n > 0;
			}
			if ( more ) {
				more.parentNode.hidden = n <= montre;
			}
		};
		[ cfg.q, cfg.region, cfg.dep, cfg.rec ].forEach( function ( s ) {
			var el = s && get( s );
			if ( el ) {
				el.addEventListener( el.tagName === 'INPUT' && el.type === 'search' ? 'input' : 'change', function () {
					montre = pas;
					applique();
				} );
			}
		} );
		if ( cfg.cat ) {
			root.querySelectorAll( cfg.cat ).forEach( function ( r ) {
				r.addEventListener( 'change', applique );
			} );
		}
		if ( more ) {
			more.addEventListener( 'click', function () {
				var avant = items.filter( function ( i ) {
					return ! i.hidden;
				} ).length;
				montre += pas;
				applique();
				var nouv = items.filter( function ( i ) {
					return ! i.hidden;
				} )[ avant ];
				var a = nouv && nouv.querySelector( 'a' );
				if ( a ) {
					a.focus();
				}
			} );
		}
		applique();
	};

	/* Fil des actualités SDIS : recherche, région et département liés,
	   type avec compteurs, filtres actifs effaçables, état dans l'URL. ---- */
	var fil = function ( root ) {
		var $ = function ( s ) {
			return root.querySelector( s );
		};
		var items = Array.prototype.slice.call( root.querySelectorAll( '.r18-sa' ) );
		var q = $( '[data-r18-fs-q]' ), reg = $( '[data-r18-fs-region]' ), hint = $( '[data-r18-fs-hint]' );
		var depBox = Array.prototype.slice.call( root.querySelectorAll( '[data-r18-fs-dep]' ) );
		var types = Array.prototype.slice.call( root.querySelectorAll( '[data-r18-fs-type]' ) );
		var count = $( '[data-r18-fs-count]' ), none = $( '[data-r18-fs-none]' ), chips = $( '[data-r18-fs-chips]' );
		var more = $( '[data-r18-fs-more]' ), list = $( '[data-r18-fs-list]' );
		var pas = 24, montre = pas, minuteur;
		var type = function () {
			var c = types.filter( function ( t ) {
				return t.checked;
			} )[ 0 ];
			return c ? c.value : '';
		};
		var etat = function () {
			return { q: q ? plat( q.value ) : '', r: reg ? reg.value : '', d: depBox.filter( function ( c ) {
				return c.checked;
			} ).map( function ( c ) {
				return c.value;
			} ), t: type() };
		};
		var passe = function ( it, v, sansType ) {
			return ( ! v.r || it.dataset.region === v.r ) && ( ! v.d.length || v.d.indexOf( it.dataset.dep ) !== -1 ) &&
				( sansType || ! v.t || it.dataset.rec === '1' ) &&
				( ! v.q || v.q.split( /\s+/ ).every( function ( m ) {
					return ( it.dataset.k || '' ).indexOf( m ) !== -1;
				} ) );
		};
		var remplirDeps = function () {
			var r = reg ? reg.value : '';
			depBox.forEach( function ( c ) {
				var lab = c.closest( 'label' );
				lab.hidden = !! r && lab.getAttribute( 'data-region' ) !== r;
				if ( lab.hidden ) {
					c.checked = false;
				}
			} );
			if ( hint ) {
				hint.hidden = true;
			}
		};
		var puce = function ( texte, efface ) {
			var li = document.createElement( 'li' );
			var b = document.createElement( 'button' );
			b.type = 'button';
			b.className = 'r18-fs__chip';
			b.textContent = texte;
			b.setAttribute( 'aria-label', 'Retirer le filtre ' + texte );
			b.addEventListener( 'click', function () {
				efface();
				change();
				( q || reg ).focus();
			} );
			li.appendChild( b );
			return li;
		};
		var url = function ( v ) {
			if ( ! window.history.replaceState ) {
				return;
			}
			var u = new URL( window.location.href );
			[ [ 'q', q ? q.value.trim() : '' ], [ 'region', v.r ], [ 'dep', v.d.join( ',' ) ], [ 'type', v.t ? 'recrutement' : '' ] ].forEach( function ( p ) {
				if ( p[ 1 ] ) {
					u.searchParams.set( p[ 0 ], p[ 1 ] );
				} else {
					u.searchParams.delete( p[ 0 ] );
				}
			} );
			window.history.replaceState( null, '', u.toString() );
		};
		var applique = function () {
			var v = etat(), n = 0, nTout = 0, nRec = 0;
			items.forEach( function ( it ) {
				if ( passe( it, v, true ) ) {
					nTout++;
					if ( it.dataset.rec === '1' ) {
						nRec++;
					}
				}
				var ok = passe( it, v, false );
				if ( ok ) {
					n++;
				}
				it.hidden = ! ok || ( more ? n > montre : false );
			} );
			root.querySelectorAll( '[data-r18-fs-n]' ).forEach( function ( b ) {
				var val = b.getAttribute( 'data-r18-fs-n' ) === '1' ? nRec : nTout;
				b.textContent = String( val );
				b.closest( 'label' ).classList.toggle( 'is-vide', val === 0 );
			} );
			if ( count ) {
				count.textContent = pluriel( n, 'article', 'articles' ) + ( n > 0 && more && n > montre ? ', ' + montre + ' affichés' : '' );
			}
			if ( none ) {
				none.hidden = n > 0;
			}
			if ( list ) {
				list.hidden = n === 0;
			}
			if ( more ) {
				more.parentNode.hidden = n <= montre;
			}
			// Filtres actifs.
			if ( chips ) {
				chips.innerHTML = '';
				if ( q && q.value.trim() ) {
					chips.appendChild( puce( '« ' + q.value.trim() + ' »', function () {
						q.value = '';
					} ) );
				}
				if ( v.r ) {
					chips.appendChild( puce( v.r, function () {
						reg.value = '';
						remplirDeps();
					} ) );
				}
				depBox.forEach( function ( c ) {
					if ( c.checked ) {
						chips.appendChild( puce( c.nextElementSibling.firstChild.textContent.trim(), function () {
							c.checked = false;
						} ) );
					}
				} );
				if ( v.t ) {
					chips.appendChild( puce( 'Recrutements', function () {
						types[ 0 ].checked = true;
					} ) );
				}
			}
			var actif = !! ( ( q && q.value.trim() ) || v.r || v.d.length || v.t );
			root.querySelectorAll( '.r18-fs__state [data-r18-fs-reset]' ).forEach( function ( b ) {
				b.hidden = ! actif;
			} );
			url( v );
		};
		var change = function () {
			montre = pas;
			applique();
		};
		var reset = function () {
			if ( q ) {
				q.value = '';
			}
			if ( reg ) {
				reg.value = '';
			}
			remplirDeps();
			if ( types[ 0 ] ) {
				types[ 0 ].checked = true;
			}
			change();
			( q || reg ).focus();
		};

		if ( q ) {
			q.addEventListener( 'input', function () {
				clearTimeout( minuteur );
				minuteur = setTimeout( change, 150 );
			} );
			q.addEventListener( 'keydown', function ( e ) {
				if ( e.key === 'Escape' && q.value ) {
					q.value = '';
					change();
				}
			} );
		}
		if ( reg ) {
			reg.addEventListener( 'change', function () {
				remplirDeps();
				change();
			} );
		}
		depBox.forEach( function ( c ) {
			c.addEventListener( 'change', change );
		} );
		types.forEach( function ( t ) {
			t.addEventListener( 'change', change );
		} );
		root.querySelectorAll( '[data-r18-fs-reset]' ).forEach( function ( b ) {
			b.addEventListener( 'click', reset );
		} );
		if ( more ) {
			more.addEventListener( 'click', function () {
				var avant = items.filter( function ( i ) {
					return ! i.hidden;
				} ).length;
				montre += pas;
				applique();
				var nouv = items.filter( function ( i ) {
					return ! i.hidden;
				} )[ avant ];
				var a = nouv && nouv.querySelector( 'a' );
				if ( a ) {
					a.focus();
				}
			} );
		}

		// État initial depuis l'URL (lien partagé, retour arrière).
		var p = new URLSearchParams( window.location.search );
		if ( q && p.get( 'q' ) ) {
			q.value = p.get( 'q' );
		}
		if ( reg && p.get( 'region' ) ) {
			reg.value = p.get( 'region' );
		}
		remplirDeps();
		( p.get( 'dep' ) || '' ).split( ',' ).forEach( function ( d ) {
			depBox.forEach( function ( c ) {
				if ( c.value === d && ! c.closest( 'label' ).hidden ) {
					c.checked = true;
				}
			} );
		} );
		if ( p.get( 'type' ) === 'recrutement' && types[ 1 ] ) {
			types[ 1 ].checked = true;
		}
		applique();
	};

	var init = function () {
		document.querySelectorAll( '[data-r18-ann]' ).forEach( annuaire );
		document.querySelectorAll( '[data-r18-mini]' ).forEach( mini );
		document.querySelectorAll( '.r18-fs.is-page' ).forEach( fil );
		document.querySelectorAll( '.r18-ofs.is-page' ).forEach( function ( r ) {
			filtres( r, {
				item: '.r18-of', count: '[data-r18-of-count]', none: '[data-r18-of-none]',
				q: '[data-r18-of-q]', region: '[data-r18-of-region]', dep: '[data-r18-of-dep]', cat: '[data-r18-of-cat]',
				label: function ( n ) {
					return pluriel( n, 'offre', 'offres' );
				}
			} );
		} );
		document.querySelectorAll( '.r18-ccs.is-page' ).forEach( function ( r ) {
			filtres( r, { item: '.r18-cc__item', cat: '[data-r18-cc-cat]', label: function () {
				return '';
			} } );
		} );
	};
	// Les scripts différés (Leaflet compris) sont tous exécutés avant DOMContentLoaded.
	if ( document.readyState === 'complete' ) {
		init();
	} else {
		document.addEventListener( 'DOMContentLoaded', init );
	}
}() );
