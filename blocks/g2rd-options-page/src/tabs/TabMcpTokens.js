import { useState, useEffect, useCallback, Fragment } from '@wordpress/element';
import { Button, Spinner, TextControl, SelectControl } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

const { restBase, nonce } = window.G2RDOptionsData || {};
const API          = `${ restBase }g2rd/v1`;
const MCP_ENDPOINT = `${ restBase }g2rd/v1/mcp`;

function formatDate( iso ) {
	if ( ! iso ) return '—';
	return new Date( iso ).toLocaleString( 'fr-FR', {
		day: '2-digit', month: '2-digit', year: 'numeric',
		hour: '2-digit', minute: '2-digit',
	} );
}

/**
 * Dérive une clé de serveur MCP propre au site depuis l'URL de l'API REST.
 *
 * Sans cela tous les sites partagent la clé « g2rd » : coller le snippet d'un
 * second site écrase silencieusement la configuration du premier, et les deux
 * ne peuvent pas coexister dans le même fichier de configuration.
 *
 * L'extension de domaine est retirée pour la lisibilité — deux sites homonymes
 * sur des extensions différentes produiraient donc la même clé.
 *
 * @param {string} base URL de base de l'API REST (ex. https://exemple.fr/wp-json/).
 * @return {string} Clé du type « g2rd-exemple », ou « g2rd » si le domaine est inexploitable.
 */
function buildServerKey( base ) {
	let url;

	try {
		url = new URL( base );
	} catch ( e ) {
		return 'g2rd';
	}

	const host   = url.hostname.toLowerCase().replace( /^www\./, '' );
	const labels = host.split( '.' );

	// Retire l'extension, sauf pour une IP (dernier segment numérique) ou un
	// hostname sans point comme « localhost ».
	if ( labels.length > 1 && ! /^\d+$/.test( labels[ labels.length - 1 ] ) ) {
		labels.pop();
	}

	/*
	 * Le chemin fait partie de l'identité du site : sur un multisite en
	 * sous-répertoire, deux sites distincts partagent l'hôte et ne se
	 * distinguent que par lui. S'en tenir au hostname leur donnerait la même
	 * clé, donc la collision que cette dérivation doit justement éviter.
	 */
	const segments = url.pathname.split( '/' ).filter( Boolean );

	// « wp-json » est commun à tous les sites et n'apporte aucune distinction.
	if ( segments.length && /^wp-json$/i.test( segments[ segments.length - 1 ] ) ) {
		segments.pop();
	}

	// Les domaines accentués arrivent déjà en punycode via URL().
	const slug = labels
		.concat( segments )
		.join( '-' )
		.toLowerCase()
		.replace( /[^a-z0-9-]+/g, '-' )
		.replace( /-{2,}/g, '-' )
		.replace( /^-+|-+$/g, '' );

	return slug ? `g2rd-${ slug }` : 'g2rd';
}

const SERVER_KEY = buildServerKey( restBase );

// Config stdio via le bridge npm-linked (g2rd-mcp-bridge doit être installé : npm link depuis le thème).
function buildClaudeDesktopConfig( token ) {
	return JSON.stringify( {
		mcpServers: {
			[ SERVER_KEY ]: {
				command: 'g2rd-mcp-bridge',
				env:     {
					G2RD_MCP_URL:   MCP_ENDPOINT,
					G2RD_MCP_TOKEN: token,
				},
			},
		},
	}, null, 2 );
}

// Config HTTP directe pour Claude Code CLI et clients MCP supportant Streamable HTTP.
function buildClaudeCodeConfig( token ) {
	return JSON.stringify( {
		mcpServers: {
			[ SERVER_KEY ]: {
				type:    'http',
				url:     MCP_ENDPOINT,
				headers: {
					Authorization: `Bearer ${ token }`,
				},
			},
		},
	}, null, 2 );
}

function IntegrationCode( { token } ) {
	const [ copiedKey, setCopiedKey ] = useState( null );

	const copy = useCallback( ( text, key ) => {
		navigator.clipboard.writeText( text );
		setCopiedKey( key );
		setTimeout( () => setCopiedKey( null ), 2000 );
	}, [] );

	const desktopConfig = buildClaudeDesktopConfig( token );
	const codeConfig    = buildClaudeCodeConfig( token );

	const block = ( label, hint, code, key ) => (
		<div style={ { marginBottom: 20 } }>
			<div style={ { display: 'flex', alignItems: 'baseline', justifyContent: 'space-between', marginBottom: 4 } }>
				<strong style={ { fontSize: 13 } }>{ label }</strong>
				<span style={ { fontSize: 11, color: '#787c82' } }>{ hint }</span>
			</div>
			<div style={ { position: 'relative' } }>
				<pre style={ {
					margin: 0,
					background: '#1e1e2e',
					color: '#cdd6f4',
					padding: '12px 40px 12px 14px',
					borderRadius: 6,
					fontSize: 12,
					lineHeight: 1.6,
					overflowX: 'auto',
					whiteSpace: 'pre',
				} }>{ code }</pre>
				<Button
					isSmall
					onClick={ () => copy( code, key ) }
					style={ {
						position: 'absolute', top: 6, right: 6,
						background: copiedKey === key ? '#22c55e' : 'rgba(255,255,255,0.15)',
						color: '#fff', border: 'none', borderRadius: 4,
						padding: '2px 8px', fontSize: 11,
					} }
				>
					{ copiedKey === key ? '✓ Copié' : 'Copier' }
				</Button>
			</div>
		</div>
	);

	return (
		<div style={ { background: '#f8f9fa', border: '1px solid #c3c4c7', borderRadius: 6, padding: '20px 24px', marginTop: 16 } }>
			<h4 style={ { margin: '0 0 4px', fontSize: 13, fontWeight: 600 } }>
				<span className="dashicons dashicons-editor-code" style={ { verticalAlign: 'middle', marginRight: 6 } }></span>
				Code d'intégration
			</h4>
			<p style={ { margin: '0 0 8px', fontSize: 12, color: '#787c82' } }>
				Endpoint MCP : <code style={ { fontSize: 12 } }>{ MCP_ENDPOINT }</code>
				<br />
				Clé du serveur : <code style={ { fontSize: 12 } }>{ SERVER_KEY }</code> — dérivée du domaine, pour que plusieurs sites G2RD coexistent dans la même configuration.
			</p>
			<p style={ { margin: '0 0 16px', fontSize: 12, color: '#787c82' } }>
				À fusionner dans l'objet <code style={ { fontSize: 12 } }>mcpServers</code> de votre fichier de configuration : ne remplacez pas le fichier entier, vos autres serveurs MCP seraient perdus.
			</p>

			{ block(
				'Claude Desktop',
				'~/Library/Application Support/Claude/claude_desktop_config.json (macOS)',
				desktopConfig,
				'desktop'
			) }
			{ block(
				'Claude Code (.mcp.json)',
				'Dossier racine du projet ou ~/.claude/.mcp.json',
				codeConfig,
				'code'
			) }
		</div>
	);
}

function TokenStatusBadge( { status } ) {
	const styles = {
		active:  { background: '#dcfce7', color: '#166534' },
		expired: { background: '#fef9c3', color: '#854d0e' },
		revoked: { background: '#fee2e2', color: '#991b1b' },
	};
	const labels = { active: 'Actif', expired: 'Expiré', revoked: 'Révoqué' };
	return (
		<span style={ {
			...( styles[ status ] || styles.revoked ),
			borderRadius: 4, padding: '2px 8px', fontSize: 11, fontWeight: 600,
		} }>
			{ labels[ status ] || status }
		</span>
	);
}

export function TabMcpTokens() {
	const [ tokens,      setTokens      ] = useState( [] );
	const [ isLoading,   setIsLoading   ] = useState( true );
	const [ isCreating,  setIsCreating  ] = useState( false );
	const [ newToken,    setNewToken    ] = useState( null );
	const [ notice,      setNotice      ] = useState( null );
	const [ name,        setName        ] = useState( '' );
	const [ scope,       setScope       ] = useState( 'read_only' );
	const [ expiresIn,   setExpiresIn   ] = useState( 30 );
	const [ expandedId,  setExpandedId  ] = useState( null );
	const [ showInactive, setShowInactive ] = useState( false );

	const showNotice = useCallback( ( type, message ) => {
		setNotice( { type, message } );
		setTimeout( () => setNotice( null ), 8000 );
	}, [] );

	const loadTokens = useCallback( async ( withInactive ) => {
		setIsLoading( true );
		try {
			const url = withInactive ? `${ API }/mcp-tokens?include_inactive=1` : `${ API }/mcp-tokens`;
			const res = await apiFetch( {
				url,
				headers: { 'X-WP-Nonce': nonce },
			} );
			setTokens( res?.tokens || [] );
		} catch ( err ) {
			showNotice( 'error', err?.message || 'Erreur lors du chargement des tokens.' );
		} finally {
			setIsLoading( false );
		}
	}, [ showNotice ] );

	const toggleInactive = useCallback( () => {
		const next = ! showInactive;
		setShowInactive( next );
		loadTokens( next );
	}, [ showInactive, loadTokens ] );

	useEffect( () => { loadTokens( false ); }, [ loadTokens ] );

	const handleCreate = useCallback( async () => {
		if ( ! name.trim() ) {
			showNotice( 'error', 'Le nom du token est obligatoire.' );
			return;
		}
		setIsCreating( true );
		setNewToken( null );
		try {
			const res = await apiFetch( {
				url: `${ API }/mcp-tokens`,
				method: 'POST',
				headers: { 'X-WP-Nonce': nonce },
				data: { name: name.trim(), scope, expires_in_days: expiresIn },
			} );
			setNewToken( res );
			setName( '' );
			setScope( 'read_only' );
			setExpiresIn( 30 );
			await loadTokens( showInactive );
		} catch ( err ) {
			showNotice( 'error', err?.message || 'Erreur lors de la création.' );
		} finally {
			setIsCreating( false );
		}
	}, [ name, scope, expiresIn, loadTokens, showNotice ] );

	const handleRevoke = useCallback( async ( id, tokenName ) => {
		// eslint-disable-next-line no-alert
		if ( ! window.confirm( `Révoquer le token « ${ tokenName } » ?` ) ) return;
		// Suppression optimiste immédiate.
		setTokens( ( prev ) => prev.filter( ( t ) => t.id !== id ) );
		if ( expandedId === id ) setExpandedId( null );
		try {
			await apiFetch( {
				url: `${ API }/mcp-tokens/${ id }`,
				method: 'DELETE',
				headers: { 'X-WP-Nonce': nonce },
			} );
			showNotice( 'success', 'Token révoqué.' );
			loadTokens( showInactive );
		} catch ( err ) {
			showNotice( 'error', err?.message || 'Erreur lors de la révocation.' );
			loadTokens( showInactive );
		}
	}, [ showNotice, expandedId, showInactive, loadTokens ] );

	const handlePurge = useCallback( async ( id, tokenName ) => {
		// eslint-disable-next-line no-alert
		if ( ! window.confirm( `Supprimer définitivement le token « ${ tokenName } » ?\nCette action est irréversible.` ) ) return;
		try {
			await apiFetch( {
				url: `${ API }/mcp-tokens/${ id }/purge`,
				method: 'DELETE',
				headers: { 'X-WP-Nonce': nonce },
			} );
			showNotice( 'success', 'Token supprimé.' );
			setTokens( ( prev ) => prev.filter( ( t ) => t.id !== id ) );
			if ( expandedId === id ) setExpandedId( null );
		} catch ( err ) {
			showNotice( 'error', err?.message || 'Erreur lors de la suppression.' );
		}
	}, [ showNotice, expandedId ] );

	const copyToken = useCallback( ( token ) => {
		navigator.clipboard.writeText( token );
		showNotice( 'success', 'Token copié dans le presse-papier.' );
	}, [ showNotice ] );

	return (
		<div className="g2rd-tab-content">
			<section className="g2rd-section">
				<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 8 } }>
					<h2 className="g2rd-section__title" style={ { margin: 0 } }>
						<span className="dashicons dashicons-shield"></span>
						Tokens API MCP
					</h2>
					<div style={ { display: 'flex', gap: 8 } }>
						<Button variant="secondary" isSmall onClick={ toggleInactive }>
							<span className={ `dashicons dashicons-${ showInactive ? 'hidden' : 'visibility' }` }
								style={ { fontSize: 16, width: 16, height: 16, verticalAlign: 'middle', marginRight: 4 } }></span>
							{ showInactive ? 'Masquer inactifs' : 'Afficher inactifs' }
						</Button>
						<Button variant="secondary" isSmall onClick={ () => loadTokens( showInactive ) } disabled={ isLoading }>
							<span className="dashicons dashicons-update" style={ { fontSize: 16, width: 16, height: 16, verticalAlign: 'middle', marginRight: 4 } }></span>
							Rafraîchir
						</Button>
					</div>
				</div>
				<p className="g2rd-section__desc">
					Créez des tokens d'accès pour les agents MCP. Le token en clair n'est affiché qu'une seule fois à la création.
				</p>

				{ notice && (
					<div className={ `notice notice-${ notice.type } is-dismissible` } style={ { margin: '0 0 16px', padding: '8px 12px' } }>
						<p>{ notice.message }</p>
					</div>
				) }

				{ newToken && (
					<div style={ { background: '#f0fdf4', border: '1px solid #86efac', borderRadius: 6, padding: '16px 20px', marginBottom: 24 } }>
						<strong style={ { display: 'block', marginBottom: 8, color: '#166534' } }>
							<span className="dashicons dashicons-yes-alt" style={ { verticalAlign: 'middle', marginRight: 6 } }></span>
							Token créé — copiez-le maintenant, il ne sera plus affiché.
						</strong>
						<div style={ { display: 'flex', alignItems: 'center', gap: 8, marginBottom: 8 } }>
							<code style={ { flex: 1, wordBreak: 'break-all', background: '#dcfce7', padding: '6px 10px', borderRadius: 4, fontSize: 13 } }>
								{ newToken.token }
							</code>
							<Button variant="secondary" isSmall onClick={ () => copyToken( newToken.token ) }>
								<span className="dashicons dashicons-clipboard" style={ { fontSize: 16, width: 16, height: 16 } }></span>
							</Button>
						</div>
						<p style={ { margin: '0 0 4px', fontSize: 12, color: '#166534' } }>
							Portée : <strong>{ newToken.scope }</strong> — Expire le : <strong>{ formatDate( newToken.expires_at ) }</strong>
						</p>
						<IntegrationCode token={ newToken.token } />
					</div>
				) }

				{ /* ── Formulaire de création ── */ }
				<div style={ { background: '#f9f9f9', border: '1px solid #ddd', borderRadius: 6, padding: '20px 24px', marginBottom: 24 } }>
					<h3 style={ { marginTop: 0, marginBottom: 20, fontSize: 14, fontWeight: 600 } }>Créer un nouveau token</h3>
					<div style={ { display: 'grid', gridTemplateColumns: 'minmax(200px, 2fr) 140px 100px auto', gap: 16, alignItems: 'end' } }>
						<div>
							<TextControl
								label="Nom du token"
								value={ name }
								onChange={ setName }
								placeholder="Ex : Claude Desktop prod"
							/>
						</div>
						<div>
							<SelectControl __next40pxDefaultSize __nextHasNoMarginBottom
								label="Portée"
								value={ scope }
								options={ [
									{ label: 'Lecture seule', value: 'read_only' },
									{ label: 'Éditeur', value: 'editor' },
								] }
								onChange={ setScope }
							/>
						</div>
						<div>
							<TextControl
								label="Durée (jours)"
								type="number"
								min={ 1 }
								max={ 365 }
								value={ expiresIn }
								onChange={ ( v ) => setExpiresIn( Math.min( 365, Math.max( 1, parseInt( v, 10 ) || 30 ) ) ) }
							/>
						</div>
						<div style={ { paddingBottom: 8 } }>
							<Button variant="primary" onClick={ handleCreate } disabled={ isCreating }>
								{ isCreating ? <Spinner /> : (
									<>
										<span className="dashicons dashicons-plus-alt" style={ { verticalAlign: 'middle', marginTop: -2 } }></span>
										{ ' ' }Créer
									</>
								) }
							</Button>
						</div>
					</div>
				</div>

				{ /* ── Tableau des tokens ── */ }
				{ isLoading ? (
					<div style={ { textAlign: 'center', padding: 32 } }><Spinner /></div>
				) : tokens.length === 0 ? (
					<p style={ { color: '#787c82', fontStyle: 'italic' } }>
						{ showInactive ? 'Aucun token.' : 'Aucun token actif.' }
					</p>
				) : (
					<table className="widefat striped">
						<thead>
							<tr>
								<th>Nom</th>
								<th>Statut</th>
								<th>Portée</th>
								<th>Préfixe</th>
								<th>Créé le</th>
								<th>Expire le</th>
								<th>Intégration</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							{ tokens.map( ( t ) => {
								const isInactive = t.status !== 'active';
								const tokenName  = t.token_name || t.name;
								return (
									<Fragment key={ t.id }>
										<tr style={ isInactive ? { opacity: 0.6 } : {} }>
											<td><strong>{ tokenName }</strong></td>
											<td><TokenStatusBadge status={ t.status } /></td>
											<td>
												<span style={ {
													background: t.scope === 'editor' ? '#fef3c7' : '#e0f2fe',
													color: t.scope === 'editor' ? '#92400e' : '#075985',
													borderRadius: 4, padding: '2px 8px', fontSize: 12, fontWeight: 600,
												} }>
													{ t.scope === 'editor' ? 'Éditeur' : 'Lecture seule' }
												</span>
											</td>
											<td><code style={ { fontSize: 12 } }>{ t.token_prefix }…</code></td>
											<td style={ { fontSize: 12, color: '#787c82' } }>{ formatDate( t.created_at ) }</td>
											<td style={ { fontSize: 12, color: isInactive ? '#d63638' : 'inherit' } }>
												{ formatDate( t.expires_at ) }
											</td>
											<td>
												{ ! isInactive && (
													<Button
														variant="link"
														isSmall
														onClick={ () => setExpandedId( expandedId === t.id ? null : t.id ) }
														style={ { textDecoration: 'none', fontSize: 12 } }
													>
														<span className={ `dashicons dashicons-${ expandedId === t.id ? 'arrow-up-alt2' : 'editor-code' }` }
															style={ { fontSize: 14, width: 14, height: 14, verticalAlign: 'middle', marginRight: 4 } }></span>
														{ expandedId === t.id ? 'Fermer' : 'Voir config' }
													</Button>
												) }
											</td>
											<td>
												{ isInactive ? (
													<Button variant="secondary" isDestructive isSmall onClick={ () => handlePurge( t.id, tokenName ) }>
														<span className="dashicons dashicons-trash" style={ { fontSize: 14, width: 14, height: 14, verticalAlign: 'middle', marginRight: 4 } }></span>
														Supprimer
													</Button>
												) : (
													<Button variant="secondary" isDestructive isSmall onClick={ () => handleRevoke( t.id, tokenName ) }>
														Révoquer
													</Button>
												) }
											</td>
										</tr>
										{ expandedId === t.id && ! isInactive && (
											<tr key={ `${ t.id }-config` }>
												<td colSpan={ 8 } style={ { padding: '0 12px 16px' } }>
													<div style={ { background: '#f8f9fa', border: '1px solid #c3c4c7', borderRadius: 6, padding: '16px 20px' } }>
														<p style={ { margin: '0 0 12px', fontSize: 12, color: '#787c82' } }>
															Endpoint MCP : <code style={ { fontSize: 12 } }>{ MCP_ENDPOINT }</code>
															<br />
															<em>Le token complet n'est plus disponible. Utilisez les configs ci-dessous avec le préfixe affiché si vous avez conservé le token.</em>
														</p>
														<p style={ { margin: '0 0 12px', fontSize: 12, color: '#787c82' } }>
															À fusionner dans l'objet <code style={ { fontSize: 12 } }>mcpServers</code> de votre fichier de configuration : ne remplacez pas le fichier entier, vos autres serveurs MCP seraient perdus.
														</p>
														<p style={ { margin: '0 0 8px', fontSize: 12, fontWeight: 600 } }>Claude Desktop <span style={ { fontWeight: 400, color: '#787c82' } }>(remplacer VOTRE_TOKEN par votre valeur)</span></p>
														<pre style={ { margin: '0 0 16px', background: '#1e1e2e', color: '#cdd6f4', padding: '10px 14px', borderRadius: 6, fontSize: 12, overflowX: 'auto' } }>{ buildClaudeDesktopConfig( `${ t.token_prefix }…VOTRE_TOKEN` ) }</pre>
														<p style={ { margin: '0 0 8px', fontSize: 12, fontWeight: 600 } }>Claude Code <span style={ { fontWeight: 400, color: '#787c82' } }>(remplacer VOTRE_TOKEN par votre valeur)</span></p>
														<pre style={ { margin: 0, background: '#1e1e2e', color: '#cdd6f4', padding: '10px 14px', borderRadius: 6, fontSize: 12, overflowX: 'auto' } }>{ buildClaudeCodeConfig( `${ t.token_prefix }…VOTRE_TOKEN` ) }</pre>
													</div>
												</td>
											</tr>
										) }
								</Fragment>
							);
						} ) }
						</tbody>
					</table>
				) }
			</section>
		</div>
	);
}
