import { useState, useEffect, useCallback } from '@wordpress/element';
import { Button, Spinner, TextControl, SelectControl } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

const { restBase, nonce } = window.G2RDOptionsData || {};
const API = `${ restBase }g2rd/v1`;
const MCP_ENDPOINT = `${ restBase }g2rd/v1`;

function formatDate( iso ) {
	if ( ! iso ) return '—';
	return new Date( iso ).toLocaleString( 'fr-FR', {
		day: '2-digit', month: '2-digit', year: 'numeric',
		hour: '2-digit', minute: '2-digit',
	} );
}

function buildClaudeDesktopConfig( token ) {
	return JSON.stringify( {
		mcpServers: {
			g2rd: {
				url: MCP_ENDPOINT,
				headers: {
					Authorization: `Bearer ${ token }`,
				},
			},
		},
	}, null, 2 );
}

function buildClaudeCodeConfig( token ) {
	return JSON.stringify( {
		mcpServers: {
			g2rd: {
				command: 'npx',
				args: [ '-y', 'mcp-remote', MCP_ENDPOINT, '--header', `Authorization: Bearer ${ token }` ],
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
			<p style={ { margin: '0 0 16px', fontSize: 12, color: '#787c82' } }>
				Endpoint MCP : <code style={ { fontSize: 12 } }>{ MCP_ENDPOINT }</code>
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

	const showNotice = useCallback( ( type, message ) => {
		setNotice( { type, message } );
		setTimeout( () => setNotice( null ), 8000 );
	}, [] );

	const loadTokens = useCallback( async () => {
		setIsLoading( true );
		try {
			const res = await apiFetch( {
				url: `${ API }/mcp-tokens`,
				headers: { 'X-WP-Nonce': nonce },
			} );
			setTokens( res?.tokens || [] );
		} catch ( err ) {
			showNotice( 'error', err?.message || 'Erreur lors du chargement des tokens.' );
		} finally {
			setIsLoading( false );
		}
	}, [ showNotice ] );

	useEffect( () => { loadTokens(); }, [ loadTokens ] );

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
			await loadTokens();
		} catch ( err ) {
			showNotice( 'error', err?.message || 'Erreur lors de la création.' );
		} finally {
			setIsCreating( false );
		}
	}, [ name, scope, expiresIn, loadTokens, showNotice ] );

	const handleRevoke = useCallback( async ( id, tokenName ) => {
		// eslint-disable-next-line no-alert
		if ( ! window.confirm( `Révoquer le token « ${ tokenName } » ?` ) ) return;
		try {
			await apiFetch( {
				url: `${ API }/mcp-tokens/${ id }`,
				method: 'DELETE',
				headers: { 'X-WP-Nonce': nonce },
			} );
			showNotice( 'success', 'Token révoqué.' );
			setTokens( ( prev ) => prev.filter( ( t ) => t.id !== id ) );
			if ( expandedId === id ) setExpandedId( null );
		} catch ( err ) {
			showNotice( 'error', err?.message || 'Erreur lors de la révocation.' );
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
					<Button variant="secondary" isSmall onClick={ loadTokens } disabled={ isLoading }>
						<span className="dashicons dashicons-update" style={ { fontSize: 16, width: 16, height: 16, verticalAlign: 'middle', marginRight: 4 } }></span>
						Rafraîchir
					</Button>
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
					<p style={ { color: '#787c82', fontStyle: 'italic' } }>Aucun token actif.</p>
				) : (
					<table className="widefat striped">
						<thead>
							<tr>
								<th>Nom</th>
								<th>Portée</th>
								<th>Préfixe</th>
								<th>Créé le</th>
								<th>Expire le</th>
								<th>Intégration</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							{ tokens.map( ( t ) => (
								<>
									<tr key={ t.id }>
										<td><strong>{ t.token_name || t.name }</strong></td>
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
										<td style={ { fontSize: 12, color: new Date( t.expires_at ) < new Date() ? '#d63638' : 'inherit' } }>
											{ formatDate( t.expires_at ) }
										</td>
										<td>
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
										</td>
										<td>
											<Button variant="secondary" isDestructive isSmall onClick={ () => handleRevoke( t.id, t.token_name || t.name ) }>
												Révoquer
											</Button>
										</td>
									</tr>
									{ expandedId === t.id && (
										<tr key={ `${ t.id }-config` }>
											<td colSpan={ 7 } style={ { padding: '0 12px 16px' } }>
												<div style={ { background: '#f8f9fa', border: '1px solid #c3c4c7', borderRadius: 6, padding: '16px 20px' } }>
													<p style={ { margin: '0 0 12px', fontSize: 12, color: '#787c82' } }>
														Endpoint MCP : <code style={ { fontSize: 12 } }>{ MCP_ENDPOINT }</code>
														<br />
														<em>Le token complet n'est plus disponible. Utilisez les configs ci-dessous avec le préfixe affiché si vous avez conservé le token.</em>
													</p>
													<p style={ { margin: '0 0 8px', fontSize: 12, fontWeight: 600 } }>Claude Desktop <span style={ { fontWeight: 400, color: '#787c82' } }>(remplacer TOKEN par votre valeur)</span></p>
													<pre style={ { margin: '0 0 16px', background: '#1e1e2e', color: '#cdd6f4', padding: '10px 14px', borderRadius: 6, fontSize: 12, overflowX: 'auto' } }>{ JSON.stringify( {
														mcpServers: { g2rd: { url: MCP_ENDPOINT, headers: { Authorization: `Bearer ${ t.token_prefix }…(TOKEN)` } } }
													}, null, 2 ) }</pre>
													<p style={ { margin: '0 0 8px', fontSize: 12, fontWeight: 600 } }>Claude Code <span style={ { fontWeight: 400, color: '#787c82' } }>(remplacer TOKEN par votre valeur)</span></p>
													<pre style={ { margin: 0, background: '#1e1e2e', color: '#cdd6f4', padding: '10px 14px', borderRadius: 6, fontSize: 12, overflowX: 'auto' } }>{ JSON.stringify( {
														mcpServers: { g2rd: { command: 'npx', args: [ '-y', 'mcp-remote', MCP_ENDPOINT, '--header', `Authorization: Bearer ${ t.token_prefix }…(TOKEN)` ] } }
													}, null, 2 ) }</pre>
												</div>
											</td>
										</tr>
									) }
								</>
							) ) }
						</tbody>
					</table>
				) }
			</section>
		</div>
	);
}
