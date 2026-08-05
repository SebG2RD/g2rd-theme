import { useState, useEffect, useCallback } from '@wordpress/element';
import { Button, Spinner, SelectControl } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

const { restBase, nonce } = window.G2RDOptionsData || {};
const API = `${ restBase }g2rd/v1`;

const DECISION_LABELS = {
	allowed:     { label: 'Autorisé',   tone: 'success' },
	denied:      { label: 'Refusé',     tone: 'danger' },
	pending:     { label: 'En attente', tone: 'warning' },
	rolled_back: { label: 'Annulé',     tone: 'neutral' },
};

function DecisionBadge( { decision } ) {
	const cfg = DECISION_LABELS[ decision ] || { label: decision, tone: 'neutral' };
	return (
		<span className={ `g2rd-tag g2rd-tag--${ cfg.tone }` }>
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
				<div className="g2rd-section__head">
					<h2 className="g2rd-section__title">
						<span className="dashicons dashicons-list-view"></span>
						Journal d'audit MCP
					</h2>
					<div className="g2rd-row g2rd-row--loose">
						<SelectControl __next40pxDefaultSize __nextHasNoMarginBottom
							label="Filtrer par décision"
							hideLabelFromVision
							value={ decision }
							options={ [
								{ label: 'Toutes les décisions', value: '' },
								{ label: 'Autorisé',    value: 'allowed' },
								{ label: 'Refusé',      value: 'denied' },
								{ label: 'En attente',  value: 'pending' },
								{ label: 'Annulé',      value: 'rolled_back' },
							] }
							onChange={ handleDecisionChange }
						/>
						<Button variant="secondary" isSmall onClick={ loadEntries } disabled={ isLoading }>
							<span className="dashicons dashicons-update g2rd-ico"></span>
							Rafraîchir
						</Button>
					</div>
				</div>
				<p className="g2rd-section__desc">
					Trace immuable de toutes les requêtes MCP — <strong>{ total }</strong> entrée{ total !== 1 ? 's' : '' } au total.
				</p>

				{ notice && (
					<div className={ `notice notice-${ notice.type } is-dismissible g2rd-notice-inline` }>
						<p>{ notice.message }</p>
					</div>
				) }

				{ isLoading ? (
					<div className="g2rd-loading"><Spinner /></div>
				) : entries.length === 0 ? (
					<p className="g2rd-muted g2rd-muted--italic">Aucune entrée.</p>
				) : (
					<table className="widefat striped g2rd-table">
						<thead>
							<tr>
								<th className="col-date">Date (UTC)</th>
								<th>Outil</th>
								<th>Décision</th>
								<th>Utilisateur</th>
								<th>Adresse IP</th>
							</tr>
						</thead>
						<tbody>
							{ entries.map( ( e ) => (
								<tr key={ e.id }>
									<td className="is-date">{ formatDate( e.created_at ) }</td>
									<td><code className="g2rd-code-inline">{ e.ability_name || '—' }</code></td>
									<td><DecisionBadge decision={ e.decision } /></td>
									<td className="is-date">{ e.user_id ? `#${ e.user_id }` : '—' }</td>
									<td className="is-mono">{ e.ip_address || '—' }</td>
								</tr>
							) ) }
						</tbody>
					</table>
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
