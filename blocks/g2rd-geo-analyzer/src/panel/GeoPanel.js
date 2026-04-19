/**
 * Composant GeoPanel
 *
 * Panneau principal du plugin sidebar GEO Analyzer.
 * Analyse le contenu Gutenberg en temps réel et affiche le score GEO.
 */

import { useMemo, useCallback, useState, useEffect } from '@wordpress/element';
import { useSelect, useDispatch }                    from '@wordpress/data';
import { PanelBody, Button }                         from '@wordpress/components';
import { store as blockEditorStore }                 from '@wordpress/block-editor';
import { store as editorStore }                      from '@wordpress/editor';
import { createBlock }                               from '@wordpress/blocks';

import ScoreGauge    from './ScoreGauge';
import CriterionCard from './CriterionCard';
import {
	analyzeContent,
	getGlobalColor,
	CRITERIA_LABELS,
	PAGE_TYPE_LABELS,
	DOMAIN_LABELS,
} from '../utils/analyzer';

const PRIORITY_LABELS = {
	high:   'Haute priorité',
	medium: 'Priorité moyenne',
	low:    'Faible priorité',
};

const PRIORITY_ORDER = { high: 0, medium: 1, low: 2 };

export default function GeoPanel() {
	const [ manualRefresh, setManualRefresh ] = useState( 0 );

	const { insertBlocks } = useDispatch( blockEditorStore );

	const { blocks, title, postType, postId } = useSelect( ( select ) => ( {
		blocks:   select( blockEditorStore ).getBlocks(),
		title:    select( editorStore ).getEditedPostAttribute( 'title' ) ?? '',
		postType: select( editorStore ).getCurrentPostType() ?? 'page',
		postId:   select( editorStore ).getCurrentPostId() ?? 0,
	} ), [ manualRefresh ] );

	const analysis = useMemo( () => {
		const textBlocks = blocks.filter( ( b ) =>
			[ 'core/paragraph', 'core/heading', 'core/list' ].includes( b.name )
		);
		if ( textBlocks.length === 0 ) return null;
		return analyzeContent( blocks, title, postType );
	}, [ blocks, title, postType ] );

	const handleRefresh = useCallback( () => {
		setManualRefresh( ( n ) => n + 1 );
	}, [] );

	// Sauvegarde le score en post meta via REST (debounce 3s)
	useEffect( () => {
		if ( ! analysis || ! postId ) return;
		const timer = setTimeout( () => {
			window.fetch( `${ window.wpApiSettings?.root ?? '/wp-json/' }g2rd/v1/geo-score`, {
				method:  'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce':   window.wpApiSettings?.nonce ?? '',
				},
				body: JSON.stringify( {
					post_id:   postId,
					score:     analysis.score,
					page_type: analysis.pageType ?? '',
				} ),
			} ).catch( () => {} );
		}, 3000 );
		return () => clearTimeout( timer );
	}, [ analysis?.score, postId ] ); // eslint-disable-line react-hooks/exhaustive-deps

	const handleInsertBlock = useCallback( ( blockDef ) => {
		const block = createBlock( blockDef.name, blockDef.attributes ?? {} );
		insertBlocks( block );
	}, [ insertBlocks ] );

	if ( ! analysis ) {
		return (
			<div className="g2rd-geo g2rd-geo--empty">
				<div className="g2rd-geo__empty-icon" aria-hidden="true">🤖</div>
				<p className="g2rd-geo__empty-text">
					Ajoutez du contenu pour analyser le score GEO de cette page.
				</p>
			</div>
		);
	}

	const { score, criteria, pageType, domain, aiSuggestions } = analysis;
	const color = getGlobalColor( score );

	// Recommandations triées : haute → moyenne → faible priorité
	const recommendations = Object.values( criteria )
		.flatMap( ( c ) => c.details )
		.filter( ( d ) => d.status !== 'ok' )
		.sort( ( a, b ) => ( PRIORITY_ORDER[ a.priority ] ?? 3 ) - ( PRIORITY_ORDER[ b.priority ] ?? 3 ) );

	return (
		<div className="g2rd-geo">

			{/* ── Type de page + domaine détecté ───────────────────── */}
			<div className="g2rd-geo__meta-row">
				{ pageType && (
					<span className="g2rd-geo__page-type">
						<span className="g2rd-geo__page-type-icon" aria-hidden="true">📄</span>
						{ PAGE_TYPE_LABELS[ pageType ] ?? pageType }
					</span>
				) }
				{ domain && (
					<span className="g2rd-geo__domain-badge">
						{ DOMAIN_LABELS[ domain ] }
					</span>
				) }
			</div>

			{/* ── Score global ──────────────────────────────────────── */}
			<div className="g2rd-geo__header">
				<ScoreGauge score={ score } />

				<div className="g2rd-geo__global-bar-track">
					<div
						className="g2rd-geo__global-bar-fill"
						style={ {
							width:      `${ score }%`,
							background: `linear-gradient(90deg, ${ color }cc, ${ color })`,
						} }
					/>
				</div>

				<Button
					className="g2rd-geo__refresh-btn"
					variant="tertiary"
					onClick={ handleRefresh }
					size="small"
				>
					↻ Rafraîchir
				</Button>
			</div>

			{/* ── Critères ──────────────────────────────────────────── */}
			<PanelBody
				title={ `Critères (${ Object.values( criteria ).filter( ( c ) => c.score / c.max >= 0.8 ).length }/${ Object.keys( criteria ).length } validés)` }
				initialOpen={ true }
				className="g2rd-geo__panel-criteria"
			>
				{ Object.entries( criteria ).map( ( [ key, data ] ) => (
					<CriterionCard
						key={ key }
						criterionKey={ key }
						label={ CRITERIA_LABELS[ key ] }
						data={ data }
					/>
				) ) }
			</PanelBody>

			{/* ── Recommandations triées par priorité ───────────────── */}
			{ recommendations.length > 0 && (
				<PanelBody
					title={ `Recommandations (${ recommendations.length })` }
					initialOpen={ false }
					className="g2rd-geo__panel-recs"
				>
					<ul className="g2rd-geo__recs">
						{ recommendations.map( ( rec, i ) => (
							<li
								key={ i }
								className={ `g2rd-geo__rec g2rd-geo__rec--${ rec.status }` }
							>
								<span className="g2rd-geo__rec-dot" aria-hidden="true" />
								<span className="g2rd-geo__rec-body">
									{ rec.priority && (
										<span className={ `g2rd-geo__rec-priority g2rd-geo__rec-priority--${ rec.priority }` }>
											{ PRIORITY_LABELS[ rec.priority ] }
										</span>
									) }
									{ rec.text }
									{ rec.block && (
										<Button
											className="g2rd-geo__rec-insert"
											variant="link"
											onClick={ () => handleInsertBlock( rec.block ) }
										>
											+ Insérer le bloc
										</Button>
									) }
								</span>
							</li>
						) ) }
					</ul>
				</PanelBody>
			) }

			{/* ── Suggestions IA ────────────────────────────────────── */}
			{ aiSuggestions && ( aiSuggestions.summary || aiSuggestions.faqQuestions.length > 0 ) && (
				<PanelBody
					title="✨ Suggestions pour l'IA"
					initialOpen={ false }
					className="g2rd-geo__panel-suggestions"
				>
					{ aiSuggestions.summary && (
						<div className="g2rd-geo__suggestion">
							<p className="g2rd-geo__suggestion-label">Résumé suggéré</p>
							<p className="g2rd-geo__suggestion-text">{ aiSuggestions.summary }</p>
						</div>
					) }
					{ aiSuggestions.faqQuestions.length > 0 && (
						<div className="g2rd-geo__suggestion">
							<p className="g2rd-geo__suggestion-label">Questions FAQ adaptées</p>
							<ul className="g2rd-geo__suggestion-list">
								{ aiSuggestions.faqQuestions.map( ( q, i ) => (
									<li key={ i }>{ q }</li>
								) ) }
							</ul>
						</div>
					) }
				</PanelBody>
			) }

		</div>
	);
}
