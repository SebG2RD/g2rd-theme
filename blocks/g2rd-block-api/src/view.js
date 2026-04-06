/**
 * G2RD Block API — Script frontend (viewScript)
 *
 * Gère la récupération des données API et le rendu des résultats dans le navigateur.
 * Supporte : connecteur côté client (fetch direct) et côté serveur (proxy WP REST),
 * chargement Ajax, mode chat, streaming SSE et espaces réservés dynamiques.
 *
 * Pattern MutationObserver + data-g2rd-init pour la compatibilité avec le canvas Gutenberg.
 */
( function () {
	'use strict';

	// Compteurs d'incrément par bloc (pour l'espace réservé {{INCREMENT:step}})
	const incrementCounters = {};

	// Historique des messages de chat par bloc
	const chatHistories = {};

	// -------------------------------------------------------------------------
	// Résolution des espaces réservés dynamiques côté client
	// -------------------------------------------------------------------------

	/**
	 * Remplace les espaces réservés {{...}} dans une chaîne.
	 *
	 * @param {string} str Chaîne source.
	 * @return {string} Chaîne avec les espaces réservés résolus.
	 */
	function resolvePlaceholders( str ) {
		if ( ! str ) return str;

		// {{VALUE:.selector}} — valeur d'un champ de formulaire
		str = str.replace( /\{\{VALUE:([^}]+)\}\}/g, ( _, selector ) => {
			const el = document.querySelector( selector );
			return el ? ( el.value || '' ) : '';
		} );

		// {{TEXT:.selector}} — contenu textuel d'un élément
		str = str.replace( /\{\{TEXT:([^}]+)\}\}/g, ( _, selector ) => {
			const el = document.querySelector( selector );
			return el ? ( el.textContent || '' ) : '';
		} );

		// {{COOKIE:name}} — valeur d'un cookie
		str = str.replace( /\{\{COOKIE:([^}]+)\}\}/g, ( _, name ) => {
			// Échapper les caractères spéciaux regex dans le nom du cookie
			// pour éviter tout comportement inattendu si le nom contient ".", "+", etc.
			const escaped = name.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );
			const match   = document.cookie.match( new RegExp( '(?:^|; )' + escaped + '=([^;]*)' ) );
			return match ? decodeURIComponent( match[ 1 ] ) : '';
		} );

		// {{STORAGE:key}} — valeur dans localStorage
		str = str.replace( /\{\{STORAGE:([^}]+)\}\}/g, ( _, key ) => {
			return localStorage.getItem( key ) || '';
		} );

		// {{ATTR:attribute|selector}} — valeur d'un attribut HTML
		str = str.replace( /\{\{ATTR:([^|]+)\|([^}]+)\}\}/g, ( _, attr, selector ) => {
			const el = document.querySelector( selector );
			return el ? ( el.getAttribute( attr ) || '' ) : '';
		} );

		// {{SESSION_ID}} — identifiant de session unique par chargement de page
		if ( ! window._g2rdSessionId ) {
			window._g2rdSessionId = Math.random().toString( 36 ).substring( 2, 18 );
		}
		str = str.replace( /\{\{SESSION_ID\}\}/g, window._g2rdSessionId );

		// {{RANDOM:0-100}} ou {{RANDOM:bleu|gris|jaune}}
		str = str.replace( /\{\{RANDOM:([^}]+)\}\}/g, ( _, range ) => {
			if ( range.includes( '|' ) ) {
				const values = range.split( '|' );
				return values[ Math.floor( Math.random() * values.length ) ];
			}
			if ( range.includes( '-' ) ) {
				const parts = range.split( '-' );
				const min   = parseInt( parts[ 0 ], 10 );
				const max   = parseInt( parts[ 1 ], 10 );
				return String( Math.floor( Math.random() * ( max - min + 1 ) ) + min );
			}
			return range;
		} );

		return str;
	}

	// -------------------------------------------------------------------------
	// Utilitaires de données
	// -------------------------------------------------------------------------

	/**
	 * Extrait une valeur imbriquée à partir d'un chemin de type "field[subfield][0]".
	 *
	 * @param {*}      obj  Objet source.
	 * @param {string} path Chemin vers la valeur.
	 * @return {*} Valeur extraite ou null.
	 */
	function extractValue( obj, path ) {
		if ( ! path || obj === null || obj === undefined ) return obj;
		const keys = path.replace( /\[(\w+)\]/g, '.$1' ).split( '.' );
		let value = obj;
		for ( const key of keys ) {
			if ( value !== null && value !== undefined && value[ key ] !== undefined ) {
				value = value[ key ];
			} else {
				return null;
			}
		}
		return value;
	}

	/**
	 * Mappe les données d'un item API vers les éléments DOM d'un nœud cloné.
	 * Utilise la liste des mappages configurés dans le panneau éditeur.
	 *
	 * @param {Element} node     Nœud DOM cloné.
	 * @param {Object}  item     Objet de données de l'API.
	 * @param {Array}   mappings Tableau de {selector, key}.
	 */
	function mapDataToNode( node, item, mappings ) {
		if ( ! mappings || mappings.length === 0 ) return;

		mappings.forEach( ( { selector, key } ) => {
			if ( ! selector || ! key ) return;

			// Le sélecteur peut commencer par "." ou non.
			const cssSelector = selector.startsWith( '.' ) ? selector : '.' + selector;
			const el          = node.querySelector( cssSelector );
			if ( ! el ) return;

			const value = extractValue( item, key );
			if ( value === null || value === undefined ) return;

			const tag = el.tagName.toLowerCase();
			if ( tag === 'img' || tag === 'video' || tag === 'source' ) {
				el.src = value;
			} else if ( tag === 'a' ) {
				el.href = value;
			} else if ( tag === 'input' || tag === 'textarea' ) {
				el.value = value;
			} else {
				el.textContent = value;
			}
			el.classList.add( 'loaded', 'active' );
		} );
	}

	/**
	 * Injecte les items dans le conteneur de résultats en clonant le template.
	 *
	 * @param {Element} resultsContainer Conteneur cible.
	 * @param {Element} templateEl       Nœud template source.
	 * @param {Array}   items            Tableau d'objets de données.
	 * @param {Array}   mappings         Tableau de mappages champ/sélecteur.
	 */
	function renderResults( resultsContainer, templateEl, items, mappings ) {
		resultsContainer.innerHTML = '';
		resultsContainer.classList.remove( 'loaded', 'active', 'error' );
		resultsContainer.classList.add( 'loading' );

		if ( ! Array.isArray( items ) ) items = [ items ];

		items.forEach( ( item ) => {
			const clone = templateEl.cloneNode( true );
			clone.removeAttribute( 'style' );
			clone.removeAttribute( 'aria-hidden' );
			clone.classList.add( 'g2rd-api-item', 'result-loaded' );
			mapDataToNode( clone, item, mappings );
			resultsContainer.appendChild( clone );
		} );

		resultsContainer.classList.remove( 'loading' );
		resultsContainer.classList.add( 'loaded', 'active' );
	}

	// -------------------------------------------------------------------------
	// Connecteur côté client
	// -------------------------------------------------------------------------

	/**
	 * Effectue un appel API directement depuis le navigateur (fetch).
	 *
	 * @param {Element} blockEl     Élément racine du bloc.
	 * @param {Object}  config      Configuration du bloc.
	 */
	async function fetchClientApi( blockEl, config ) {
		const {
			apiUrl,
			apiMethod,
			apiHeaders,
			apiBody,
			responseField,
			singleItem,
			resultMappings,
			enableChat,
			chatAddField,
			chatUserFormat,
			chatAssistantFormat,
			chatResponseField,
			enableStreaming,
			blockId: bid,
		} = config;

		const url     = resolvePlaceholders( apiUrl );
		const headers = { 'Content-Type': 'application/json' };

		if ( apiHeaders ) {
			apiHeaders.forEach( ( { key, value } ) => {
				if ( key ) headers[ key ] = resolvePlaceholders( value );
			} );
		}

		let body = apiBody ? resolvePlaceholders( apiBody ) : undefined;

		// Mode chat : enrichit le corps avec l'historique des messages.
		if ( enableChat && body ) {
			try {
				const bodyObj = JSON.parse( body );
				if ( ! chatHistories[ bid ] ) {
					chatHistories[ bid ] = bodyObj[ chatAddField ] ? [ ...bodyObj[ chatAddField ] ] : [];
				}
				if ( chatUserFormat ) {
					const userMsg = JSON.parse( resolvePlaceholders( chatUserFormat ) );
					chatHistories[ bid ].push( userMsg );
					bodyObj[ chatAddField ] = chatHistories[ bid ];
				}
				body = JSON.stringify( bodyObj );
			} catch ( e ) {
				console.error( 'G2RD API : erreur de composition du corps chat', e );
			}
		}

		const fetchOptions = { method: apiMethod || 'GET', headers };
		if ( body && ! [ 'GET', 'HEAD' ].includes( ( apiMethod || 'GET' ).toUpperCase() ) ) {
			fetchOptions.body = body;
		}

		activateLoader( config );

		const resultsContainer = blockEl.querySelector( '.g2rd-api-results' );
		const templateEl       = blockEl.querySelector( '.g2rd-api-template' );
		if ( resultsContainer ) resultsContainer.classList.add( 'loading' );

		try {
			const response = await fetch( url, fetchOptions );

			if ( enableStreaming ) {
				// --- Traitement Server-Sent Events ---
				const reader      = response.body.getReader();
				const decoder     = new TextDecoder();
				let   streamedText = '';

				if ( resultsContainer ) {
					// Utiliser createElement plutôt qu'innerHTML (fix 9 anticipé ici).
					resultsContainer.innerHTML = '';
					const outputEl = document.createElement( 'div' );
					outputEl.className = 'g2rd-api-stream-output';
					resultsContainer.appendChild( outputEl );

					// Boucle while — remplace la récursion pour éviter le stack overflow
					// sur des streams longs (milliers de chunks).
					try {
						while ( true ) { // eslint-disable-line no-constant-condition
							const { done, value } = await reader.read(); // eslint-disable-line no-await-in-loop
							if ( done ) {
								outputEl.classList.add( 'loaded', 'active' );
								if ( enableChat && chatAssistantFormat && streamedText ) {
									pushAssistantMessage( bid, chatAssistantFormat, chatAddField, streamedText );
								}
								break;
							}
							const chunk = decoder.decode( value, { stream: true } );
							chunk.split( '\n' ).forEach( ( line ) => {
								if ( ! line.startsWith( 'data: ' ) ) return;
								const raw = line.substring( 6 ).trim();
								if ( raw === '[DONE]' ) return;
								try {
									const json  = JSON.parse( raw );
									const delta = chatResponseField ? extractValue( json, chatResponseField ) : json;
									if ( delta ) {
										streamedText        += delta;
										outputEl.textContent = streamedText;
									}
								} catch ( _ ) { /* fragment SSE incomplet, ignoré */ }
							} );
						}
					} catch ( streamErr ) {
						console.error( 'G2RD API Stream : erreur de lecture', streamErr );
					}
				}
			} else {
				// --- Traitement JSON standard ---
				const data = await response.json();

				let result = data;
				if ( responseField ) {
					result = extractValue( data, responseField ) ?? data;
				}

				// Événement pour les développeurs.
				document.dispatchEvent( new CustomEvent( 'GSPB_API_RESPONSE', {
					detail: { resultElem: resultsContainer, responseData: result },
				} ) );

				// Sauvegarde de la réponse assistant dans l'historique.
				if ( enableChat && chatAssistantFormat && chatResponseField ) {
					const responseText = extractValue( data, chatResponseField );
					if ( responseText ) {
						pushAssistantMessage( bid, chatAssistantFormat, chatAddField, responseText );
					}
				}

				if ( templateEl && resultsContainer ) {
					const items = ( singleItem || ! Array.isArray( result ) ) ? [ result ] : result;
					renderResults( resultsContainer, templateEl, items, resultMappings );
				}
			}
		} catch ( err ) {
			console.error( 'G2RD API Client : erreur', err );
			if ( resultsContainer ) {
				resultsContainer.classList.remove( 'loading' );
				resultsContainer.classList.add( 'error' );
			}
		} finally {
			deactivateLoader( config );
			if ( resultsContainer ) resultsContainer.classList.remove( 'loading' );
		}
	}

	// -------------------------------------------------------------------------
	// Connecteur côté serveur
	// -------------------------------------------------------------------------

	/**
	 * Effectue un appel API via le proxy WordPress REST (côté serveur).
	 *
	 * @param {Element} blockEl Élément racine du bloc.
	 * @param {Object}  config  Configuration du bloc.
	 */
	async function fetchServerApi( blockEl, config ) {
		const {
			apiUrl,
			apiMethod,
			responseField,
			singleItem,
			resultMappings,
		} = config;

		// Les credentials (headers, body) ne sont pas dans data-config (sécurité).
		// Ils sont transmis via wp_localize_script dans g2rdApiData.credentials[blockId].
		const bid         = blockEl.dataset.blockId || '';
		const creds       = ( window.g2rdApiData && window.g2rdApiData.credentials && window.g2rdApiData.credentials[ bid ] ) || {};
		const apiHeaders  = creds.apiHeaders || [];
		const apiBody     = creds.apiBody    || '';

		const resultsContainer = blockEl.querySelector( '.g2rd-api-results' );
		const templateEl       = blockEl.querySelector( '.g2rd-api-template' );
		if ( resultsContainer ) resultsContainer.classList.add( 'loading' );

		activateLoader( config );

		const restUrl = ( window.g2rdApiData && window.g2rdApiData.restUrl )
			? window.g2rdApiData.restUrl
			: '/wp-json/';
		const nonce = ( window.g2rdApiData && window.g2rdApiData.nonce )
			? window.g2rdApiData.nonce
			: '';

		try {
			const response = await fetch( restUrl + 'g2rd/v1/api-proxy', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce':    nonce,
				},
				body: JSON.stringify( {
					url:     resolvePlaceholders( apiUrl ),
					method:  apiMethod || 'GET',
					headers: apiHeaders || [],
					body:    apiBody ? resolvePlaceholders( apiBody ) : '',
				} ),
			} );

			if ( ! response.ok ) {
				throw new Error( `Proxy HTTP ${ response.status }` );
			}

			const json   = await response.json();

			// Vérifier aussi le statut upstream retourné dans le wrapper.
			if ( json.status && json.status >= 400 ) {
				throw new Error( `API distante HTTP ${ json.status }` );
			}

			let result = json.data !== undefined ? json.data : json;

			if ( responseField ) {
				result = extractValue( result, responseField ) ?? result;
			}

			document.dispatchEvent( new CustomEvent( 'GSPB_API_RESPONSE', {
				detail: { resultElem: resultsContainer, responseData: result },
			} ) );

			if ( templateEl && resultsContainer ) {
				const items = ( singleItem || ! Array.isArray( result ) ) ? [ result ] : result;
				renderResults( resultsContainer, templateEl, items, resultMappings );
			}
		} catch ( err ) {
			console.error( 'G2RD API Serveur : erreur', err );
			if ( resultsContainer ) {
				resultsContainer.classList.remove( 'loading' );
				resultsContainer.classList.add( 'error' );
			}
		} finally {
			deactivateLoader( config );
			if ( resultsContainer ) resultsContainer.classList.remove( 'loading' );
		}
	}

	// -------------------------------------------------------------------------
	// Helpers internes
	// -------------------------------------------------------------------------

	function activateLoader( config ) {
		if ( config.customLoaderSelector ) {
			const loader = document.querySelector( config.customLoaderSelector );
			if ( loader ) loader.classList.add( 'active' );
		}
	}

	function deactivateLoader( config ) {
		if ( config.customLoaderSelector ) {
			const loader = document.querySelector( config.customLoaderSelector );
			if ( loader ) loader.classList.remove( 'active' );
		}
	}

	function pushAssistantMessage( bid, assistantFormat, addField, responseText ) {
		try {
			const assistantMsg = JSON.parse( assistantFormat.replace( '{{RESPONSE}}', responseText ) );
			if ( ! chatHistories[ bid ] ) chatHistories[ bid ] = [];
			chatHistories[ bid ].push( assistantMsg );
		} catch ( e ) {
			console.error( 'G2RD API : erreur de composition du message assistant', e );
		}
	}

	function doFetch( blockEl, config ) {
		if ( config.connectorType === 'server' ) {
			fetchServerApi( blockEl, config );
		} else {
			fetchClientApi( blockEl, config );
		}
	}

	/**
	 * Résout l'espace réservé {{INCREMENT:step}} dans la config puis déclenche le fetch.
	 *
	 * @param {Element} blockEl Élément racine du bloc.
	 * @param {Object}  config  Configuration d'origine.
	 * @param {string}  bid     ID du bloc.
	 */
	function doFetchWithIncrement( blockEl, config, bid ) {
		if ( incrementCounters[ bid ] === undefined ) incrementCounters[ bid ] = 0;
		const counter = incrementCounters[ bid ]++;

		const resolvedConfigStr = JSON.stringify( config ).replace(
			/\{\{INCREMENT:(\d+)\}\}/g,
			( _, step ) => String( counter * parseInt( step, 10 ) )
		);
		doFetch( blockEl, JSON.parse( resolvedConfigStr ) );
	}

	// -------------------------------------------------------------------------
	// Initialisation d'un bloc
	// -------------------------------------------------------------------------

	/**
	 * Initialise un bloc .g2rd-block-api.
	 * Attache les déclencheurs selon la configuration et effectue le premier appel.
	 *
	 * @param {Element} blockEl Élément racine du bloc.
	 */
	function initApiBlock( blockEl ) {
		if ( blockEl.dataset.g2rdInit ) return;
		blockEl.dataset.g2rdInit = '1';

		let config;
		try {
			config = JSON.parse( blockEl.dataset.config || '{}' );
		} catch ( e ) {
			console.error( 'G2RD API : configuration invalide', e );
			return;
		}

		// Garantir un ID unique même si blockId n'est pas encore persisté
		// (évite le croisement de compteurs/historiques entre blocs sans ID).
		const bid = blockEl.dataset.blockId || ( 'g2rd-api-tmp-' + Math.random().toString( 36 ).substring( 2, 9 ) );

		// Chargement immédiat (mode non-Ajax).
		if ( ! config.enableAjax ) {
			doFetchWithIncrement( blockEl, config, bid );
			return;
		}

		// Déclencheur : soumission de formulaire.
		if ( config.ajaxTrigger === 'form' && config.formSelector ) {
			const form = document.querySelector( config.formSelector );
			if ( form ) {
				form.addEventListener( 'submit', ( e ) => {
					e.preventDefault();
					doFetchWithIncrement( blockEl, config, bid );
				} );
			}
		}

		// Déclencheur : interaction personnalisée (événement custom).
		if ( config.ajaxTrigger === 'interaction' ) {
			// Écoute via l'ID du bloc.
			document.addEventListener( 'g2rd-api-trigger-' + bid, () => {
				doFetchWithIncrement( blockEl, config, bid );
			} );
			// Écoute via l'ID d'interaction personnalisé.
			if ( config.customInteractionId ) {
				document.addEventListener(
					'g2rd-api-trigger-' + config.customInteractionId,
					() => doFetchWithIncrement( blockEl, config, bid )
				);
			}
		}

		// Déclencheur : intervalle.
		if ( config.ajaxTrigger === 'interval' && config.intervalTime > 0 ) {
			let callCount  = 0;
			const intervalId = setInterval( () => {
				doFetchWithIncrement( blockEl, config, bid );
				callCount++;
				if ( config.intervalCount > 0 && callCount >= config.intervalCount ) {
					clearInterval( intervalId );
				}
			}, config.intervalTime );
		}

		// Pagination via bouton dans un conteneur dédié.
		if ( config.paginationSelector ) {
			const paginationEl = document.querySelector( config.paginationSelector );
			if ( paginationEl ) {
				let currentPage = 1;
				const btn = paginationEl.querySelector( 'button, a' );
				if ( btn ) {
					btn.addEventListener( 'click', () => {
						currentPage++;
						const pagedConfig = JSON.parse(
							JSON.stringify( config ).replace( /\{\{PAGE\}\}/g, String( currentPage ) )
						);
						doFetch( blockEl, pagedConfig );
					} );
				}
			}
		}
	}

	// -------------------------------------------------------------------------
	// Bootstrap
	// -------------------------------------------------------------------------

	function initAllApiBlocks() {
		document.querySelectorAll( '.g2rd-block-api' ).forEach( initApiBlock );
	}

	// Observer les mutations DOM pour initialiser les blocs ajoutés dynamiquement.
	// Debounce à 50 ms pour éviter des centaines d'appels par seconde sur les pages
	// avec animations ou rendu dynamique intensif.
	let _observerTimer;
	const observer = new MutationObserver( () => {
		clearTimeout( _observerTimer );
		_observerTimer = setTimeout( () => {
			document.querySelectorAll( '.g2rd-block-api:not([data-g2rd-init])' ).forEach( initApiBlock );
		}, 50 );
	} );

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', () => {
			initAllApiBlocks();
			observer.observe( document.body, { childList: true, subtree: true } );
		} );
	} else {
		initAllApiBlocks();
		observer.observe( document.body, { childList: true, subtree: true } );
	}
} )();
