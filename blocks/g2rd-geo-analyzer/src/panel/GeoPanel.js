/**
 * Composant GeoPanel
 *
 * Panneau principal du plugin sidebar GEO Analyzer.
 * Analyse le contenu Gutenberg en temps réel et affiche le score GEO.
 */

import { useMemo, useCallback, useState } from '@wordpress/element';
import { useSelect }                      from '@wordpress/data';
import { PanelBody, Button, Spinner }     from '@wordpress/components';
import { store as blockEditorStore }      from '@wordpress/block-editor';
import { store as editorStore }           from '@wordpress/editor';

import ScoreGauge                         from './ScoreGauge';
import CriterionCard                      from './CriterionCard';
import { analyzeContent, getGlobalColor, CRITERIA_LABELS } from '../utils/analyzer';

/** Nombre minimum de mots pour déclencher l'analyse (évite le bruit) */
const MIN_WORDS = 20;

export default function GeoPanel() {
	const [ manualRefresh, setManualRefresh ] = useState( 0 );

	// Récupère les blocs et le titre depuis le store WP
	const { blocks, title } = useSelect( ( select ) => ( {
		blocks: select( blockEditorStore ).getBlocks(),
		title:  select( editorStore ).getEditedPostAttribute( 'title' ) ?? '',
	} ), [ manualRefresh ] );

	// Analyse mémoïsée (recalcul uniquement si blocks/title changent)
	const analysis = useMemo( () => {
		// Heuristique rapide : si peu de blocs, retourner un état vide
		const textBlocks = blocks.filter( ( b ) =>
			[ 'core/paragraph', 'core/heading', 'core/list' ].includes( b.name )
		);
		if ( textBlocks.length === 0 ) {
			return null;
		}
		return analyzeContent( blocks, title );
	}, [ blocks, title ] );

	const handleRefresh = useCallback( () => {
		setManualRefresh( ( n ) => n + 1 );
	}, [] );

	// État vide (contenu insuffisant)
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

	const { score, criteria } = analysis;
	const color               = getGlobalColor( score );

	// Collecte toutes les recommandations (détails warning/error)
	const recommendations = Object.values( criteria )
		.flatMap( ( c ) => c.details )
		.filter( ( d ) => d.status !== 'ok' );

	return (
		<div className="g2rd-geo">

			{/* ── Score global ──────────────────────────────────── */}
			<div className="g2rd-geo__header">
				<ScoreGauge score={ score } />

				{/* Barre globale */}
				<div className="g2rd-geo__global-bar-track">
					<div
						className="g2rd-geo__global-bar-fill"
						style={ {
							width:      `${ score }%`,
							background: `linear-gradient(90deg, ${ color }cc, ${ color })`,
						} }
					/>
				</div>

				{/* Bouton rafraîchir */}
				<Button
					className="g2rd-geo__refresh-btn"
					variant="tertiary"
					onClick={ handleRefresh }
					size="small"
				>
					↻ Rafraîchir
				</Button>
			</div>

			{/* ── Critères ──────────────────────────────────────── */}
			<PanelBody
				title={ `Critères (${ Object.values( criteria ).filter( c => c.score / c.max >= 0.8 ).length }/${ Object.keys( criteria ).length } validés)` }
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

			{/* ── Recommandations ───────────────────────────────── */}
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
								{ rec.text }
							</li>
						) ) }
					</ul>
				</PanelBody>
			) }

			{/* ── Blocs suggérés ────────────────────────────────── */}
			{ ( ! Object.values( criteria ).some( c => c.score > 0 && c.max >= 10 && c.score / c.max >= 0.8 ) ) && (
				<PanelBody title="Blocs GEO recommandés" initialOpen={ false }>
					<p className="g2rd-geo__blocks-hint">
						Ces blocs améliorent automatiquement votre score GEO :
					</p>
					<ul className="g2rd-geo__blocks-list">
						<li>
							<strong>Résumé GEO</strong> — TL;DR pour les IA
						</li>
						<li>
							<strong>FAQ GEO</strong> — Questions/réponses avec schema.org
						</li>
					</ul>
				</PanelBody>
			) }

		</div>
	);
}
