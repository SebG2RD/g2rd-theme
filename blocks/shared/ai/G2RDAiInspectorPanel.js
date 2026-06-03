/**
 * G2RDAiInspectorPanel — Panneau IA dans les InspectorControls des blocs
 *
 * Composant partagé importé par chaque bloc Gutenberg concerné.
 * Auto-désactivé si g2rdAiConfig.blocksEnabled === false.
 * Le résultat s'affiche inline dans le panneau (pas de modal séparée).
 *
 * React best practices :
 * - BLOCK_ACTIONS et TONE_OPTIONS hoissés au niveau module
 * - useCallback sur tous les handlers
 * - useMemo pour le contexte construit depuis les attributs
 * - useG2RDAi pour la gestion d'état IA (pas de duplication)
 */

import { useState, useCallback, useMemo } from '@wordpress/element';
import { __, sprintf }                    from '@wordpress/i18n';
import { InspectorControls }              from '@wordpress/block-editor';
import {
	PanelBody,
	Button,
} from '@wordpress/components';

import { useG2RDAi } from '../../g2rd-ai-editor/src/hooks/useG2RDAi';
import G2RDAiButton  from '../../g2rd-ai-editor/src/components/G2RDAiButton';
import G2RDAiNotice  from '../../g2rd-ai-editor/src/components/G2RDAiNotice';

// ── Actions disponibles par type de bloc (hoissé — jamais recréé) ──────────

const BLOCK_ACTIONS = {
	'g2rd/hero': [
		{ id: 'hero-heading',    label: 'Générer un titre H1',        targetAttr: 'heading' },
		{ id: 'hero-subheading', label: 'Générer un sous-titre',       targetAttr: 'subheading' },
		{ id: 'hero-cta',        label: 'Générer 3 CTA',               targetAttr: null },
		{ id: 'hero-rewrite',    label: 'Réécrire le texte',           targetAttr: 'heading' },
		{ id: 'hero-seo-local',  label: 'Optimiser SEO local',         targetAttr: 'heading' },
	],
	'g2rd/faq': [
		{ id: 'faq-generate', label: 'Générer une FAQ complète', targetAttr: null },
	],
	'g2rd/cta-band': [
		{ id: 'cta-texts', label: 'Générer textes CTA', targetAttr: null },
	],
	'g2rd/pricing-table': [
		{ id: 'pricing-benefits', label: 'Générer contenu offres', targetAttr: null },
	],
	'g2rd/testimonial': [
		{ id: 'testimonial', label: 'Améliorer le témoignage', targetAttr: null },
	],
	'g2rd/card': [
		{ id: 'image-alt', label: 'Générer texte alt + légende', targetAttr: null },
	],
};

// ──────────────────────────────────────────────────────────────────────────────

/**
 * @param {Object}   props
 * @param {string}   props.blockType      Identifiant du bloc (ex. 'g2rd/hero').
 * @param {Object}   props.attributes     Attributs courants du bloc.
 * @param {Function} props.setAttributes  Setter d'attributs WordPress.
 */
export function G2RDAiInspectorPanel( { blockType, attributes, setAttributes } ) {
	const config  = window.g2rdAiConfig ?? {};
	const actions = BLOCK_ACTIONS[ blockType ] ?? [];

	if ( ! config.enabled || ! config.blocksEnabled || ! actions.length ) {
		return null;
	}

	const [ currentAction, setCurrentAction ] = useState( null );

	const { generate, loading, result, parsed, error, reset } = useG2RDAi();

	// Le contexte (activité, ville, ton) vient désormais du Profil du site, injecté
	// côté serveur. On n'envoie plus que le contexte propre au bloc courant.
	const ctx = useMemo( () => ( {
		language:         config.language ?? 'fr',
		existing_content: attributes?.heading ?? attributes?.content ?? '',
		service:          attributes?.title   ?? '',
	} ), [ attributes, config.language ] );

	const handleAction = useCallback( async ( action ) => {
		if ( ! config.connectorReady ) {
			return;
		}
		setCurrentAction( action );
		reset();
		await generate( {
			endpoint: 'block-action',
			payload:  {
				action:     action.id,
				block_type: blockType,
				context:    ctx,
			},
		} );
	}, [ generate, blockType, ctx, config, reset ] );

	const handleInsert = useCallback( () => {
		if ( ! currentAction?.targetAttr || ! parsed ) return;

		if ( typeof parsed === 'string' ) {
			setAttributes( { [ currentAction.targetAttr ]: parsed } );
		} else if ( typeof parsed === 'object' ) {
			const first = Object.values( parsed )[ 0 ];
			if ( typeof first === 'string' ) {
				setAttributes( { [ currentAction.targetAttr ]: first } );
			}
		}
		reset();
	}, [ currentAction, parsed, setAttributes, reset ] );

	const handleCopy = useCallback( () => {
		if ( ! result ) return;
		navigator.clipboard?.writeText( result ).catch( () => {
			const el = document.createElement( 'textarea' );
			el.value = result;
			document.body.appendChild( el );
			el.select();
			document.execCommand( 'copy' );
			document.body.removeChild( el );
		} );
	}, [ result ] );

	return (
		<InspectorControls>
			<PanelBody
				title={ __( 'IA G2RD', 'g2rd' ) }
				initialOpen={ false }
				className="g2rd-ai-inspector-panel"
			>
				{ ! config.connectorReady && (
					<G2RDAiNotice
						message={ __( 'Connecteur IA non configuré.', 'g2rd' ) }
						type="warning"
					/>
				) }

				<p className="g2rd-ai-panel-hint">
					{ __( 'Le contexte (activité, ville, ton) provient du Profil du site : Options G2RD → IA.', 'g2rd' ) }
				</p>

				<div
					className="g2rd-ai-actions"
					role="group"
					aria-label={ sprintf(
						/* translators: %s = type de bloc */
						__( 'Actions IA pour le bloc %s', 'g2rd' ),
						blockType
					) }
				>
					{ actions.map( ( action ) => (
						<G2RDAiButton
							key={ action.id }
							label={ __( action.label, 'g2rd' ) }
							loading={ loading && currentAction?.id === action.id }
							disabled={ ! config.connectorReady }
							variant="secondary"
							onClick={ () => handleAction( action ) }
						/>
					) ) }
				</div>

				{ /* Résultat inline */ }
				{ error && ! loading && (
					<G2RDAiNotice
						message={ error }
						type="error"
						onDismiss={ reset }
					/>
				) }

				{ result && ! loading && (
					<div className="g2rd-ai-panel-result">
						<div className="g2rd-ai-panel-result__body">
							{ typeof parsed === 'object' && parsed !== null
								? <pre className="g2rd-ai-panel-result__code">{ JSON.stringify( parsed, null, 2 ) }</pre>
								: <p className="g2rd-ai-panel-result__text">{ result }</p>
							}
						</div>
						<div className="g2rd-ai-panel-result__actions">
							{ currentAction?.targetAttr && (
								<Button
									variant="primary"
									isSmall
									onClick={ handleInsert }
								>
									{ __( 'Insérer', 'g2rd' ) }
								</Button>
							) }
							<Button
								variant="secondary"
								isSmall
								onClick={ handleCopy }
							>
								{ __( 'Copier', 'g2rd' ) }
							</Button>
							<Button
								variant="tertiary"
								isSmall
								onClick={ reset }
							>
								{ __( 'Effacer', 'g2rd' ) }
							</Button>
						</div>
					</div>
				) }

			</PanelBody>
		</InspectorControls>
	);
}
