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
		<span className={ `g2rd-request__countdown${ isUrgent ? ' g2rd-request__countdown--urgent' : '' }` }>
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
				<div className="g2rd-section__head">
					<h2 className="g2rd-section__title">
						<span className="dashicons dashicons-clock"></span>
						File d'approbation MCP
						{ total > 0 && (
							<span className="g2rd-count">{ total }</span>
						) }
					</h2>
					<Button variant="secondary" isSmall onClick={ loadEntries } disabled={ isLoading }>
						<span className="dashicons dashicons-update g2rd-ico"></span>
						Rafraîchir
					</Button>
				</div>
				<p className="g2rd-section__desc">
					Opérations d'écriture soumises par des agents MCP en attente de votre approbation.
					Chaque demande expire après 60 minutes.
				</p>

				{ notice && (
					<div className={ `notice notice-${ notice.type } is-dismissible g2rd-notice-inline` }>
						<p>{ notice.message }</p>
					</div>
				) }

				{ isLoading ? (
					<div className="g2rd-loading"><Spinner /></div>
				) : entries.length === 0 ? (
					<div className="g2rd-empty g2rd-empty--ok">
						<span className="dashicons dashicons-yes-alt"></span>
						<p className="g2rd-empty__title">Aucune demande en attente</p>
						<p className="g2rd-empty__hint">Les opérations d'écriture soumises par un agent MCP apparaîtront ici.</p>
					</div>
				) : (
					<div className="g2rd-stack">
						{ entries.map( ( e ) => {
							const isBusy = !! acting[ e.id ];
							return (
								<div key={ e.id } className="g2rd-request">
									<div className="g2rd-row g2rd-row--between g2rd-row--top g2rd-row--wrap">
										<div>
											<div className="g2rd-row g2rd-request__head">
												<span className="dashicons dashicons-warning g2rd-ico g2rd-ico--lg g2rd-request__icon"></span>
												<strong className="g2rd-request__name">
													<code>{ e.ability_name }</code>
												</strong>
												<span className="g2rd-muted">— #{ e.id }</span>
											</div>
											{ e.target && (
												<div className="g2rd-request__target">
													Cible : <code>{ e.target }</code>
												</div>
											) }
											<div className="g2rd-meta">
												<span>Utilisateur : <strong>#{ e.user_id }</strong></span>
												<span>IP : <code>{ e.ip_address }</code></span>
												<span>Soumis le : { formatDate( e.created_at ) }</span>
												<span>Expire dans : <ExpiryCountdown expiresAt={ e.expires_at } /></span>
												{ e.payload_bytes > 0 && (
													<span>Taille : { formatBytes( e.payload_bytes ) }</span>
												) }
											</div>
											{ e.superseded_by && (
												<div className="g2rd-request__note">
													<span className="dashicons dashicons-info-outline g2rd-ico g2rd-ico--sm"></span>
													Une demande plus récente (<strong>#{ e.superseded_by }</strong>) vise la même cible avec le même outil.
													Les deux restent en attente : confirmer celle-ci appliquera une version antérieure.
												</div>
											) }
										</div>
										<div className="g2rd-row g2rd-row--nogrow">
											<Button
												variant="primary"
												className="g2rd-btn-confirm"
												onClick={ () => handleAction( e.id, 'confirm' ) }
												disabled={ isBusy }
											>
												{ acting[ e.id ] === 'confirm' ? <Spinner /> : (
													<>
														<span className="dashicons dashicons-yes-alt g2rd-ico g2rd-ico--btn g2rd-ico--gap"></span>
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
														<span className="dashicons dashicons-no-alt g2rd-ico g2rd-ico--btn g2rd-ico--gap"></span>
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
					<div className="g2rd-pagination">
						<Button
							variant="secondary" isSmall
							onClick={ () => setPage( ( p ) => Math.max( 1, p - 1 ) ) }
							disabled={ page <= 1 || isLoading }
						>
							← Précédent
						</Button>
						<span className="g2rd-muted">
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
