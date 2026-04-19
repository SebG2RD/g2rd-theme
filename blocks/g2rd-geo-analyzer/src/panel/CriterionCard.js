/**
 * Composant CriterionCard
 *
 * Affiche un critère GEO avec :
 *  - Icône de statut (✓ / ⚠ / ✗)
 *  - Label + score numérique
 *  - Mini barre de progression colorée
 *  - Liste des détails (cliquable pour développer)
 */

import { useState } from '@wordpress/element';
import { Button }   from '@wordpress/components';
import { getScoreColor } from '../utils/analyzer';

// Icônes SVG inline (pas de dépendance Dashicons pour les couleurs custom)
const IconOk = () => (
	<svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
		<circle cx="8" cy="8" r="7.5" fill="#dcfce7" stroke="#22c55e" strokeWidth="1"/>
		<path d="M5 8l2.5 2.5L11 5.5" stroke="#22c55e" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
	</svg>
);

const IconWarning = () => (
	<svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
		<circle cx="8" cy="8" r="7.5" fill="#fef9c3" stroke="#f59e0b" strokeWidth="1"/>
		<path d="M8 5v4M8 11v.5" stroke="#f59e0b" strokeWidth="1.5" strokeLinecap="round"/>
	</svg>
);

const IconError = () => (
	<svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
		<circle cx="8" cy="8" r="7.5" fill="#fee2e2" stroke="#ef4444" strokeWidth="1"/>
		<path d="M5.5 5.5l5 5M10.5 5.5l-5 5" stroke="#ef4444" strokeWidth="1.5" strokeLinecap="round"/>
	</svg>
);

const DETAIL_ICONS = {
	ok:      <IconOk />,
	warning: <IconWarning />,
	error:   <IconError />,
};

/**
 * Retourne l'icône globale selon le ratio score/max.
 */
function statusIcon( score, max ) {
	const r = max > 0 ? score / max : 0;
	if ( r >= 0.8 ) return <IconOk />;
	if ( r >= 0.4 ) return <IconWarning />;
	return <IconError />;
}

const PRIORITY_ICONS = {
	high:   '🔴',
	medium: '🟡',
	low:    '⚪',
};

/**
 * @param {{
 *   criterionKey: string,
 *   label: string,
 *   data: { score: number, max: number, details: Array }
 * }} props
 */
export default function CriterionCard( { label, data } ) {
	const [ expanded, setExpanded ] = useState( false );
	const { score, max, details }   = data;
	const barColor                  = getScoreColor( score, max );
	const barWidth                  = max > 0 ? Math.round( ( score / max ) * 100 ) : 0;

	return (
		<div className="g2rd-geo__criterion">
			{/* En-tête cliquable */}
			<Button
				className="g2rd-geo__criterion-header"
				onClick={ () => setExpanded( ! expanded ) }
				aria-expanded={ expanded }
			>
				<span className="g2rd-geo__criterion-status">
					{ statusIcon( score, max ) }
				</span>
				<span className="g2rd-geo__criterion-label">{ label }</span>
				<span className="g2rd-geo__criterion-score" style={ { color: barColor } }>
					{ score }<span className="g2rd-geo__criterion-max">/ { max }</span>
				</span>
				<span
					className={ `g2rd-geo__criterion-chevron${ expanded ? ' is-open' : '' }` }
					aria-hidden="true"
				>
					▾
				</span>
			</Button>

			{/* Barre de progression */}
			<div className="g2rd-geo__criterion-bar-track">
				<div
					className="g2rd-geo__criterion-bar-fill"
					style={ {
						width:      `${ barWidth }%`,
						background: barColor,
						transition: 'width 0.5s ease',
					} }
					role="progressbar"
					aria-valuenow={ score }
					aria-valuemin={ 0 }
					aria-valuemax={ max }
				/>
			</div>

			{/* Détails (dépliables) */}
			{ expanded && (
				<ul className="g2rd-geo__criterion-details">
					{ details.map( ( d, i ) => (
						<li
							key={ i }
							className={ `g2rd-geo__detail g2rd-geo__detail--${ d.status }` }
						>
							<span className="g2rd-geo__detail-icon" aria-hidden="true">
								{ DETAIL_ICONS[ d.status ] }
							</span>
							<span className="g2rd-geo__detail-text">
								{ d.priority && d.status !== 'ok' && (
									<span
										className={ `g2rd-geo__detail-priority` }
										title={ d.priority }
										aria-hidden="true"
									>
										{ PRIORITY_ICONS[ d.priority ] }{ ' ' }
									</span>
								) }
								{ d.text }
							</span>
						</li>
					) ) }
				</ul>
			) }
		</div>
	);
}
