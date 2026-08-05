import { useState, useEffect, useCallback } from '@wordpress/element';
import { Button, Spinner } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

const { restBase, nonce } = window.G2RDOptionsData || {};
const API = `${ restBase }g2rd/v1`;

// Taille du payload en unité lisible : « 61 ko » parle, « 62 464 » non.
function formatBytes( bytes ) {
	const n = Number( bytes ) || 0;

	if ( n < 1024 ) {
		return `${ n } o`;
	}

	if ( n < 1024 * 1024 ) {
		return `${ ( n / 1024 ).toFixed( 1 ).replace( '.', ',' ) } ko`;
	}

	return `${ ( n / ( 1024 * 1024 ) ).toFixed( 2 ).replace( '.', ',' ) } Mo`;
}

function formatDate( dt ) {
	if ( ! dt ) return '—';
	return new Date( dt ).toLocaleString( 'fr-FR', {
		day: '2-digit', month: '2-digit', year: 'numeric',
		hour: '2-digit', minute: '2-digit',
	} );
}

function ExpiryCountdown( { expiresAt } ) {
	const ms   = new Date( expiresAt ) - new Date();
	const secs = Math.max( 0, Math.floor( ms / 1000 ) );
	const mins = Math.floor( secs / 60 );
	const s    = secs % 60;
	const isUrgent = secs < 120;

	return (
		<span style={ { color: isUrgent ? '#d63638' : '#92400e', fontWeight: 600, fontSize: 12 } }>
			{ secs <= 0 ? 'Expiré' : `${ mins }m ${ String( s ).padStart( 2, '0' ) }s` }
		</span>
	);
}

export function TabMcpQueue() {
	const [ entries,    setEntries    ] = useState( [] );
	const [ total,      setTotal      ] = useState( 0 );
	const [ totalPages, setTotalPages ] = useState( 1 );
	const [ page,       setPage       ] = useState( 1 );
	const [ isLoading,  setIsLoading  ] = useState( true );
	const [ notice,     setNotice     ] = useState( null );
	const [ acting,     setActing     ] = useState( {} );

	const showNotice = useCallback( ( type, message ) => {
		setNotice( { type, message } );
		setTimeout( () => setNotice( null ), 6000 );
	}, [] );

	const loadEntries = useCallback( async () => {
		setIsLoading( true );
		try {
			const params = new URLSearchParams( { page, per_page: 20 } );
			const res = await apiFetch( {
				url: `${ API }/mcp-queue?${ params }`,
				headers: { 'X-WP-Nonce': nonce },
			} );
			setEntries( res?.entries || [] );
			setTotal( res?.total || 0 );
			setTotalPages( res?.total_pages || 1 );
		} catch ( err ) {
			showNotice( 'error', err?.message || 'Erreur lors du chargement.' );
		} finally {
			setIsLoading( false );
		}
	}, [ page, showNotice ] );

	useEffect( () => { loadEntries(); }, [ loadEntries ] );

	const handleAction = useCallback( async ( id, action ) => {
		setActing( ( prev ) => ( { ...prev, [ id ]: action } ) );
		try {
			await apiFetch( {
				url: `${ API }/mcp-queue/${ id }/${ action }`,
				method: 'POST',
				headers: { 'X-WP-Nonce': nonce },
			} );
			showNotice(
				'success',
				action === 'confirm'
					? 'Opération confirmée et exécutée.'
					: 'Opération refusée.'
			);
			setEntries( ( prev ) => prev.filter( ( e ) => e.id !== id ) );
			setTotal( ( t ) => Math.max( 0, t - 1 ) );
		} catch ( err ) {
			showNotice( 'error', err?.message || 'Erreur lors de l\'action.' );
		} finally {
			setActing( ( prev ) => { const n = { ...prev }; delete n[ id ]; return n; } );
		}
	}, [ showNotice ] );

	return (
		<div className="g2rd-tab-content">
			<section className="g2rd-section">
				<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 8 } }>
					<h2 className="g2rd-section__title" style={ { margin: 0 } }>
						<span className="dashicons dashicons-clock"></span>
						File d'approbation MCP
						{ total > 0 && (
							<span style={ {
								marginLeft: 8, background: '#d63638', color: '#fff',
								borderRadius: 12, padding: '2px 8px', fontSize: 12, fontWeight: 700,
							} }>
								{ total }
							</span>
						) }
					</h2>
					<Button variant="secondary" isSmall onClick={ loadEntries } disabled={ isLoading }>
						<span className="dashicons dashicons-update" style={ { fontSize: 16, width: 16, height: 16, verticalAlign: 'middle', marginRight: 4 } }></span>
						Rafraîchir
					</Button>
				</div>
				<p className="g2rd-section__desc">
					Opérations d'écriture soumises par des agents MCP en attente de votre approbation.
					Chaque demande expire après 15 minutes.
				</p>

				{ notice && (
					<div className={ `notice notice-${ notice.type } is-dismissible` } style={ { margin: '0 0 16px', padding: '8px 12px' } }>
						<p>{ notice.message }</p>
					</div>
				) }

				{ isLoading ? (
					<div style={ { textAlign: 'center', padding: 32 } }><Spinner /></div>
				) : entries.length === 0 ? (
					<div style={ { textAlign: 'center', padding: '40px 0', color: '#787c82' } }>
						<span className="dashicons dashicons-yes-alt" style={ { fontSize: 32, width: 32, height: 32, display: 'block', margin: '0 auto 8px', color: '#00a32a' } }></span>
						<p style={ { margin: 0, fontStyle: 'italic' } }>Aucune demande en attente.</p>
					</div>
				) : (
					<div style={ { display: 'flex', flexDirection: 'column', gap: 16 } }>
						{ entries.map( ( e ) => {
							const isBusy = !! acting[ e.id ];
							return (
								<div key={ e.id } style={ {
									border: '1px solid #e0a800', borderRadius: 6,
									padding: '16px 20px', background: '#fffbeb',
								} }>
									<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', flexWrap: 'wrap', gap: 8 } }>
										<div>
											<div style={ { display: 'flex', alignItems: 'center', gap: 8, marginBottom: 6 } }>
												<span className="dashicons dashicons-warning" style={ { color: '#92400e', fontSize: 18, width: 18, height: 18 } }></span>
												<strong style={ { fontSize: 14 } }>
													<code>{ e.ability_name }</code>
												</strong>
												<span style={ { fontSize: 12, color: '#787c82' } }>— #{ e.id }</span>
											</div>
											{ e.target && (
												<div style={ { fontSize: 13, marginBottom: 6 } }>
													Cible : <code>{ e.target }</code>
												</div>
											) }
											<div style={ { fontSize: 12, color: '#787c82', display: 'flex', gap: 16, flexWrap: 'wrap' } }>
												<span>Utilisateur : <strong>#{ e.user_id }</strong></span>
												<span>IP : <code>{ e.ip_address }</code></span>
												<span>Soumis le : { formatDate( e.created_at ) }</span>
												<span>Expire dans : <ExpiryCountdown expiresAt={ e.expires_at } /></span>
												{ e.payload_bytes > 0 && (
													<span>Taille : { formatBytes( e.payload_bytes ) }</span>
												) }
											</div>
											{ e.superseded_by && (
												<div style={ {
													marginTop: 8, padding: '8px 12px', borderRadius: 4,
													background: '#fef3c7', border: '1px solid #d97706',
													fontSize: 12, color: '#92400e',
												} }>
													<span className="dashicons dashicons-info-outline" style={ { fontSize: 14, width: 14, height: 14, verticalAlign: 'middle', marginRight: 4 } }></span>
													Une demande plus récente (<strong>#{ e.superseded_by }</strong>) vise la même cible avec le même outil.
													Les deux restent en attente : confirmer celle-ci appliquera une version antérieure.
												</div>
											) }
										</div>
										<div style={ { display: 'flex', gap: 8, flexShrink: 0 } }>
											<Button
												variant="primary"
												onClick={ () => handleAction( e.id, 'confirm' ) }
												disabled={ isBusy }
												style={ { background: '#00a32a', borderColor: '#00a32a' } }
											>
												{ acting[ e.id ] === 'confirm' ? <Spinner /> : (
													<>
														<span className="dashicons dashicons-yes-alt" style={ { verticalAlign: 'middle', marginTop: -2, marginRight: 4 } }></span>
														Confirmer
													</>
												) }
											</Button>
											<Button
												variant="secondary"
												isDestructive
												onClick={ () => handleAction( e.id, 'reject' ) }
												disabled={ isBusy }
											>
												{ acting[ e.id ] === 'reject' ? <Spinner /> : (
													<>
														<span className="dashicons dashicons-no-alt" style={ { verticalAlign: 'middle', marginTop: -2, marginRight: 4 } }></span>
														Refuser
													</>
												) }
											</Button>
										</div>
									</div>
								</div>
							);
						} ) }
					</div>
				) }

				{ /* ── Pagination ── */ }
				{ totalPages > 1 && (
					<div style={ { display: 'flex', gap: 8, alignItems: 'center', marginTop: 16 } }>
						<Button
							variant="secondary" isSmall
							onClick={ () => setPage( ( p ) => Math.max( 1, p - 1 ) ) }
							disabled={ page <= 1 || isLoading }
						>
							← Précédent
						</Button>
						<span style={ { fontSize: 13, color: '#787c82' } }>
							Page { page } / { totalPages }
						</span>
						<Button
							variant="secondary" isSmall
							onClick={ () => setPage( ( p ) => Math.min( totalPages, p + 1 ) ) }
							disabled={ page >= totalPages || isLoading }
						>
							Suivant →
						</Button>
					</div>
				) }
			</section>
		</div>
	);
}
