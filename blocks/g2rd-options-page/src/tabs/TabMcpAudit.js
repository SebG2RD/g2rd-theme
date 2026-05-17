import { useState, useEffect, useCallback } from '@wordpress/element';
import { Button, Spinner, SelectControl } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

const { restBase, nonce } = window.G2RDOptionsData || {};
const API = `${ restBase }g2rd/v1`;

const DECISION_LABELS = {
	allowed:     { label: 'Autorisé',    color: '#166534', bg: '#dcfce7' },
	denied:      { label: 'Refusé',      color: '#991b1b', bg: '#fee2e2' },
	pending:     { label: 'En attente',  color: '#92400e', bg: '#fef3c7' },
	rolled_back: { label: 'Annulé',      color: '#4b5563', bg: '#f3f4f6' },
};

function DecisionBadge( { decision } ) {
	const cfg = DECISION_LABELS[ decision ] || { label: decision, color: '#374151', bg: '#f3f4f6' };
	return (
		<span style={ {
			background: cfg.bg, color: cfg.color,
			borderRadius: 4, padding: '2px 8px', fontSize: 12, fontWeight: 600,
		} }>
			{ cfg.label }
		</span>
	);
}

function formatDate( dt ) {
	if ( ! dt ) return '—';
	return new Date( dt ).toLocaleString( 'fr-FR', {
		day: '2-digit', month: '2-digit', year: 'numeric',
		hour: '2-digit', minute: '2-digit', second: '2-digit',
	} );
}

export function TabMcpAudit() {
	const [ entries,    setEntries    ] = useState( [] );
	const [ total,      setTotal      ] = useState( 0 );
	const [ totalPages, setTotalPages ] = useState( 1 );
	const [ page,       setPage       ] = useState( 1 );
	const [ decision,   setDecision   ] = useState( '' );
	const [ isLoading,  setIsLoading  ] = useState( true );
	const [ notice,     setNotice     ] = useState( null );

	const showNotice = useCallback( ( type, message ) => {
		setNotice( { type, message } );
		setTimeout( () => setNotice( null ), 6000 );
	}, [] );

	const loadEntries = useCallback( async () => {
		setIsLoading( true );
		try {
			const params = new URLSearchParams( { page, per_page: 25 } );
			if ( decision ) params.set( 'decision', decision );
			const res = await apiFetch( {
				url: `${ API }/mcp-audit?${ params }`,
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
	}, [ page, decision, showNotice ] );

	useEffect( () => { loadEntries(); }, [ loadEntries ] );

	const handleDecisionChange = useCallback( ( val ) => {
		setDecision( val );
		setPage( 1 );
	}, [] );

	return (
		<div className="g2rd-tab-content">
			<section className="g2rd-section">
				<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 8 } }>
					<h2 className="g2rd-section__title" style={ { margin: 0 } }>
						<span className="dashicons dashicons-list-view"></span>
						Journal d'audit MCP
					</h2>
					<div style={ { display: 'flex', gap: 12, alignItems: 'center' } }>
						<SelectControl __next40pxDefaultSize __nextHasNoMarginBottom
							value={ decision }
							options={ [
								{ label: 'Toutes les décisions', value: '' },
								{ label: 'Autorisé',    value: 'allowed' },
								{ label: 'Refusé',      value: 'denied' },
								{ label: 'En attente',  value: 'pending' },
								{ label: 'Annulé',      value: 'rolled_back' },
							] }
							onChange={ handleDecisionChange }
							style={ { margin: 0 } }
						/>
						<Button variant="secondary" isSmall onClick={ loadEntries } disabled={ isLoading }>
							<span className="dashicons dashicons-update" style={ { fontSize: 16, width: 16, height: 16, verticalAlign: 'middle', marginRight: 4 } }></span>
							Rafraîchir
						</Button>
					</div>
				</div>
				<p className="g2rd-section__desc">
					Trace immuable de toutes les requêtes MCP — <strong>{ total }</strong> entrée{ total !== 1 ? 's' : '' } au total.
				</p>

				{ notice && (
					<div className={ `notice notice-${ notice.type } is-dismissible` } style={ { margin: '0 0 16px', padding: '8px 12px' } }>
						<p>{ notice.message }</p>
					</div>
				) }

				{ isLoading ? (
					<div style={ { textAlign: 'center', padding: 32 } }><Spinner /></div>
				) : entries.length === 0 ? (
					<p style={ { color: '#787c82', fontStyle: 'italic' } }>Aucune entrée.</p>
				) : (
					<table className="widefat striped" style={ { fontSize: 13 } }>
						<thead>
							<tr>
								<th style={ { width: 160 } }>Date (UTC)</th>
								<th>Outil</th>
								<th>Décision</th>
								<th>Utilisateur</th>
								<th>Adresse IP</th>
							</tr>
						</thead>
						<tbody>
							{ entries.map( ( e ) => (
								<tr key={ e.id }>
									<td style={ { color: '#787c82', whiteSpace: 'nowrap' } }>{ formatDate( e.created_at ) }</td>
									<td><code style={ { fontSize: 12 } }>{ e.ability_name || '—' }</code></td>
									<td><DecisionBadge decision={ e.decision } /></td>
									<td style={ { fontSize: 12, color: '#787c82' } }>{ e.user_id ? `#${ e.user_id }` : '—' }</td>
									<td style={ { fontSize: 12, fontFamily: 'monospace', color: '#787c82' } }>{ e.ip_address || '—' }</td>
								</tr>
							) ) }
						</tbody>
					</table>
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
