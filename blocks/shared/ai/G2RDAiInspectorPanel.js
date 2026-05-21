/**
 * G2RDAiInspectorPanel — Panneau IA dans les InspectorControls des blocs
 *
 * Composant partagé importé par chaque bloc Gutenberg concerné.
 * Auto-désactivé si g2rdAiConfig.blocksEnabled === false.
 *
 * Usage dans un bloc :
 *   import { G2RDAiInspectorPanel } from '../../shared/ai/G2RDAiInspectorPanel';
 *   // Dans le JSX, après les InspectorControls existants :
 *   <G2RDAiInspectorPanel blockType="g2rd/hero" attributes={attributes} setAttributes={setAttributes} />
 *
 * RGAA : focus visible, aria-label explicite, role="status" pour les résultats.
 *
 * React best practices :
 * - BLOCK_ACTIONS et TONE_OPTIONS hoissés au niveau module
 * - useCallback sur tous les handlers
 * - useMemo pour le contexte construit depuis les attributs
 */

import { useState, useCallback, useMemo } from '@wordpress/element';
import { __, sprintf }                    from '@wordpress/i18n';
import {
	InspectorControls,
} from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	TextControl,
	Spinner,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

import G2RDAiNotice       from '../../g2rd-ai-editor/src/components/G2RDAiNotice';
import G2RDAiPreviewModal from '../../g2rd-ai-editor/src/components/G2RDAiPreviewModal';
import G2RDAiButton       from '../../g2rd-ai-editor/src/components/G2RDAiButton';

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

// ── Options de ton (hoissé) ────────────────────────────────────────────────
const TONE_OPTIONS = [
	{ label: 'Professionnel', value: 'professionnel' },
	{ label: 'Décontracté',   value: 'decontracte' },
	{ label: 'Technique',     value: 'technique' },
	{ label: 'Humain',        value: 'humain' },
];

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

	// Ne rien rendre si le module est désactivé ou aucune action disponible.
	if ( ! config.enabled || ! config.blocksEnabled || ! actions.length ) {
		return null;
	}

	const [ activity, setActivity ]   = useState( '' );
	const [ city, setCity ]           = useState( '' );
	const [ tone, setTone ]           = useState( config.tone ?? 'professionnel' );
	const [ loading, setLoading ]     = useState( false );
	const [ error, setError ]         = useState( null );
	const [ result, setResult ]       = useState( null );
	const [ parsed, setParsed ]       = useState( null );
	const [ modalOpen, setModalOpen ] = useState( false );
	const [ currentAction, setCurrentAction ] = useState( null );

	// Contexte mémoïsé depuis les attributs du bloc + champs utilisateur.
	const ctx = useMemo( () => ( {
		activity,
		city,
		tone,
		language:         config.language ?? 'fr',
		existing_content: attributes?.heading ?? attributes?.content ?? '',
		service:          attributes?.title   ?? '',
	} ), [ activity, city, tone, attributes, config.language ] );

	/**
	 * Lance la génération pour une action donnée.
	 */
	const handleAction = useCallback( async ( action ) => {
		if ( ! config.connectorReady ) {
			setError( config.i18n?.noConnector ?? 'Connecteur IA non disponible.' );
			return;
		}

		setCurrentAction( action );
		setError( null );
		setResult( null );
		setParsed( null );
		setLoading( true );
		setModalOpen( true );

		try {
			const response = await apiFetch( {
				path:   ( config.restUrl ?? '/wp-json/g2rd/v1/ai/' ) + 'block-action',
				method: 'POST',
				data:   {
					action:     action.id,
					block_type: blockType,
					context:    ctx,
				},
			} );

			if ( response?.result !== undefined ) {
				const raw = response.result;
				const str = typeof raw === 'string' ? raw : JSON.stringify( raw, null, 2 );
				setResult( str );
				setParsed( raw );
			} else {
				setError( config.i18n?.error ?? 'Réponse inattendue.' );
			}
		} catch ( err ) {
			const msg = err?.message ?? ( config.i18n?.error ?? 'Erreur de génération.' );
			setError( err?.status === 429 ? ( config.i18n?.limitReached ?? msg ) : msg );
		} finally {
			setLoading( false );
		}
	}, [ blockType, ctx, config ] );

	/**
	 * Insère le résultat dans l'attribut cible du bloc.
	 */
	const handleInsert = useCallback( ( parsedResult ) => {
		if ( ! currentAction?.targetAttr || ! parsedResult ) {
			return;
		}

		// Pour les attributs simples (string).
		if ( typeof parsedResult === 'string' ) {
			setAttributes( { [ currentAction.targetAttr ]: parsedResult } );
			return;
		}

		// Pour les résultats JSON complexes : insérer le premier champ pertinent.
		if ( typeof parsedResult === 'object' ) {
			const firstValue = Object.values( parsedResult )[ 0 ];
			if ( typeof firstValue === 'string' ) {
				setAttributes( { [ currentAction.targetAttr ]: firstValue } );
			}
		}
	}, [ currentAction, setAttributes ] );

	const handleCloseModal = useCallback( () => {
		setModalOpen( false );
		setError( null );
	}, [] );

	return (
		<>
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

					{ /* Contexte optionnel */ }
					<TextControl
						label={ __( 'Activité', 'g2rd' ) }
						value={ activity }
						onChange={ setActivity }
						placeholder={ __( 'Ex. agence web…', 'g2rd' ) }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<TextControl
						label={ __( 'Ville', 'g2rd' ) }
						value={ city }
						onChange={ setCity }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<SelectControl
						label={ __( 'Ton', 'g2rd' ) }
						value={ tone }
						options={ TONE_OPTIONS }
						onChange={ setTone }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>

					{ /* Boutons d'action */ }
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

					{ /* Erreur hors modal (quand la modal n'est pas ouverte) */ }
					{ error && ! modalOpen && (
						<G2RDAiNotice
							message={ error }
							type="error"
							onDismiss={ () => setError( null ) }
						/>
					) }
				</PanelBody>
			</InspectorControls>

			{ /* Modal de validation — jamais d'insertion automatique */ }
			<G2RDAiPreviewModal
				isOpen={ modalOpen }
				result={ result }
				parsed={ parsed }
				actionLabel={ currentAction?.label ?? '' }
				loading={ loading }
				error={ error }
				onInsert={ handleInsert }
				onRegenerate={ () => {
					if ( currentAction ) handleAction( currentAction );
				} }
				onClose={ handleCloseModal }
			/>
		</>
	);
}
