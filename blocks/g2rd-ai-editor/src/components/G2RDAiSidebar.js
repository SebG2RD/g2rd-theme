/**
 * G2RDAiSidebar — Sidebar éditoriale IA G2RD
 *
 * Panneau latéral Gutenberg avec 5 onglets :
 * Page | Article | SEO | Réseaux sociaux | Maillage interne
 *
 * React best practices :
 * - Options arrays hoissés au niveau module (jamais recréés)
 * - useCallback pour les handlers stables
 * - useMemo pour les dérivations de contexte
 * - Pas d'insertion automatique (toujours via modal)
 */

import { useState, useCallback, useMemo } from '@wordpress/element';
import { __, sprintf }                    from '@wordpress/i18n';
import {
	TabPanel,
	PanelBody,
	TextControl,
	TextareaControl,
	SelectControl,
	Button,
	__experimentalText as Text,
} from '@wordpress/components';
import { useSelect }  from '@wordpress/data';

import { useG2RDAi }      from '../hooks/useG2RDAi';
import G2RDAiButton       from './G2RDAiButton';
import G2RDAiNotice       from './G2RDAiNotice';
import G2RDAiPreviewModal from './G2RDAiPreviewModal';
import { TONE_OPTIONS, LENGTH_OPTIONS, buildPayload } from '../utils/ai-actions';

// ── Constantes hoissées — jamais recréées ──────────────────────────────
const PAGE_TYPE_OPTIONS = [
	{ label: 'Page de service',  value: 'service' },
	{ label: 'Page locale',      value: 'local' },
];

const POST_MODE_OPTIONS = [
	{ label: 'Plan d\'article',    value: 'outline' },
	{ label: 'Article complet',    value: 'full' },
];

const SOCIAL_NETWORK_OPTIONS = [
	{ label: 'Tous les réseaux', value: 'all' },
	{ label: 'Facebook',          value: 'facebook' },
	{ label: 'LinkedIn',          value: 'linkedin' },
	{ label: 'Google Business',   value: 'google' },
];

const TABS = [
	{ name: 'page',   title: 'Page',           className: 'g2rd-ai-tab' },
	{ name: 'post',   title: 'Article',         className: 'g2rd-ai-tab' },
	{ name: 'seo',    title: 'SEO',             className: 'g2rd-ai-tab' },
	{ name: 'social', title: 'Réseaux sociaux', className: 'g2rd-ai-tab' },
	{ name: 'links',  title: 'Maillage',        className: 'g2rd-ai-tab' },
];

// ──────────────────────────────────────────────────────────────────────────────

export default function G2RDAiSidebar() {
	const config   = window.g2rdAiConfig ?? {};

	// Contexte partagé entre tous les onglets.
	const [ activity, setActivity ]     = useState( '' );
	const [ city, setCity ]             = useState( '' );
	const [ service, setService ]       = useState( '' );
	const [ target, setTarget ]         = useState( '' );
	const [ tone, setTone ]             = useState( config.tone ?? 'professionnel' );
	const [ language, setLanguage ]     = useState( config.language ?? 'fr' );
	const [ length, setLength ]         = useState( 'moyen' );
	const [ keywords, setKeywords ]     = useState( '' );

	// Onglet Page.
	const [ pageType, setPageType ] = useState( 'service' );

	// Onglet Article.
	const [ postMode, setPostMode ]     = useState( 'outline' );
	const [ postSubject, setPostSubject ] = useState( '' );

	// Onglet SEO.
	const [ seoTitle, setSeoTitle ]     = useState( '' );

	// Onglet Réseaux sociaux.
	const [ socialNetwork, setSocialNetwork ] = useState( 'all' );
	const [ socialContent, setSocialContent ] = useState( '' );

	// Onglet Maillage.
	const [ linksContent, setLinksContent ] = useState( '' );

	// Modal.
	const [ modalOpen, setModalOpen ]       = useState( false );
	const [ modalAction, setModalAction ]   = useState( '' );

	const { generate, loading, result, parsed, error, reset } = useG2RDAi();

	// Contenu du post courant depuis le store Gutenberg.
	const { postContent, postTitle } = useSelect( ( select ) => {
		const editor = select( 'core/editor' );
		return {
			postContent: editor?.getEditedPostContent() ?? '',
			postTitle:   editor?.getEditedPostAttribute( 'title' ) ?? '',
		};
	}, [] );

	// Contexte partagé mémoïsé — évite les objets recréés à chaque render.
	const sharedCtx = useMemo( () => ( {
		activity,
		city,
		service,
		target,
		tone,
		language,
		length,
		keywords,
	} ), [ activity, city, service, target, tone, language, length, keywords ] );

	/** Lance une génération et ouvre la modal. */
	const handleGenerate = useCallback( async ( endpoint, actionData, label ) => {
		setModalAction( label );
		setModalOpen( true );
		await generate( {
			endpoint,
			payload: buildPayload( endpoint, actionData, sharedCtx ),
		} );
	}, [ generate, sharedCtx ] );

	const handleCloseModal = useCallback( () => {
		setModalOpen( false );
		reset();
	}, [ reset ] );

	/** Panneau de contexte commun à tous les onglets */
	const ContextPanel = (
		<PanelBody
			title={ __( 'Contexte', 'g2rd' ) }
			initialOpen
			className="g2rd-ai-context-panel"
		>
			<TextControl
				label={ __( 'Activité', 'g2rd' ) }
				value={ activity }
				onChange={ setActivity }
				placeholder={ __( 'Ex. agence web, plombier, consultant…', 'g2rd' ) }
				__next40pxDefaultSize
				__nextHasNoMarginBottom
			/>
			<TextControl
				label={ __( 'Ville', 'g2rd' ) }
				value={ city }
				onChange={ setCity }
				placeholder={ __( 'Ex. Paris, Lyon…', 'g2rd' ) }
				__next40pxDefaultSize
				__nextHasNoMarginBottom
			/>
			<TextControl
				label={ __( 'Service / Offre', 'g2rd' ) }
				value={ service }
				onChange={ setService }
				__next40pxDefaultSize
				__nextHasNoMarginBottom
			/>
			<TextControl
				label={ __( 'Cible client', 'g2rd' ) }
				value={ target }
				onChange={ setTarget }
				placeholder={ __( 'Ex. PME, particuliers…', 'g2rd' ) }
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
			<SelectControl
				label={ __( 'Longueur', 'g2rd' ) }
				value={ length }
				options={ LENGTH_OPTIONS }
				onChange={ setLength }
				__next40pxDefaultSize
				__nextHasNoMarginBottom
			/>
			<TextControl
				label={ __( 'Mots-clés SEO', 'g2rd' ) }
				value={ keywords }
				onChange={ setKeywords }
				placeholder={ __( 'création site web, référencement…', 'g2rd' ) }
				__next40pxDefaultSize
				__nextHasNoMarginBottom
			/>
		</PanelBody>
	);

	return (
		<div className="g2rd-ai-sidebar">
			<div className="g2rd-ai-sidebar__header">
				<Text variant="title.small" as="h2" className="g2rd-ai-sidebar__title">
					{ __( 'Assistant IA G2RD', 'g2rd' ) }
				</Text>
				{ ! config.connectorReady && (
					<G2RDAiNotice
						message={ __( 'Connecteur IA non configuré. Rendez-vous dans Réglages > IA.', 'g2rd' ) }
						type="warning"
					/>
				) }
			</div>

			{ ContextPanel }

			<TabPanel
				className="g2rd-ai-tabs"
				tabs={ TABS }
				orientation="horizontal"
			>
				{ ( tab ) => {
					switch ( tab.name ) {

						// ── Onglet PAGE ──────────────────────────────────────
						case 'page':
							return (
								<PanelBody title={ __( 'Générer une page', 'g2rd' ) } initialOpen>
									<SelectControl
										label={ __( 'Type de page', 'g2rd' ) }
										value={ pageType }
										options={ PAGE_TYPE_OPTIONS }
										onChange={ setPageType }
										__next40pxDefaultSize
										__nextHasNoMarginBottom
									/>
									<G2RDAiButton
										label={ __( 'Générer la page', 'g2rd' ) }
										loading={ loading && modalAction === 'page' }
										disabled={ ! config.connectorReady }
										variant="primary"
										onClick={ () => handleGenerate(
											'generate-page',
											{ page_type: pageType, post_id: 0 },
											__( 'Génération de page', 'g2rd' )
										) }
									/>
								</PanelBody>
							);

						// ── Onglet ARTICLE ───────────────────────────────────
						case 'post':
							return (
								<PanelBody title={ __( 'Générer un article', 'g2rd' ) } initialOpen>
									<TextControl
										label={ __( 'Sujet de l\'article', 'g2rd' ) }
										value={ postSubject }
										onChange={ setPostSubject }
										__next40pxDefaultSize
										__nextHasNoMarginBottom
									/>
									<SelectControl
										label={ __( 'Mode', 'g2rd' ) }
										value={ postMode }
										options={ POST_MODE_OPTIONS }
										onChange={ setPostMode }
										__next40pxDefaultSize
										__nextHasNoMarginBottom
									/>
									<G2RDAiButton
										label={ 'outline' === postMode
											? __( 'Générer un plan', 'g2rd' )
											: __( 'Générer l\'article complet', 'g2rd' )
										}
										loading={ loading && modalAction === 'post' }
										disabled={ ! postSubject || ! config.connectorReady }
										variant="primary"
										onClick={ () => handleGenerate(
											'generate-post',
											{ mode: postMode, context: { service: postSubject } },
											__( 'Génération d\'article', 'g2rd' )
										) }
									/>
								</PanelBody>
							);

						// ── Onglet SEO ───────────────────────────────────────
						case 'seo':
							return (
								<PanelBody title={ __( 'Optimisation SEO', 'g2rd' ) } initialOpen>
									<TextControl
										label={ __( 'Titre de la page', 'g2rd' ) }
										value={ seoTitle || postTitle }
										onChange={ setSeoTitle }
										__next40pxDefaultSize
										__nextHasNoMarginBottom
									/>
									<G2RDAiButton
										label={ __( 'Générer title/desc/slug', 'g2rd' ) }
										loading={ loading && modalAction === 'seo' }
										disabled={ ! config.connectorReady }
										variant="primary"
										onClick={ () => handleGenerate(
											'generate-seo',
											{ context: { service: seoTitle || postTitle, existing_content: postContent } },
											__( 'Génération SEO', 'g2rd' )
										) }
									/>
									<G2RDAiButton
										label={ __( 'Générer suggestions H2', 'g2rd' ) }
										loading={ loading }
										disabled={ ! config.connectorReady }
										variant="secondary"
										onClick={ () => handleGenerate(
											'optimize-content',
											{ context: { existing_content: postContent } },
											__( 'Optimisation contenu', 'g2rd' )
										) }
									/>
								</PanelBody>
							);

						// ── Onglet RÉSEAUX SOCIAUX ───────────────────────────
						case 'social':
							return (
								<PanelBody title={ __( 'Réseaux sociaux', 'g2rd' ) } initialOpen>
									<SelectControl
										label={ __( 'Réseau cible', 'g2rd' ) }
										value={ socialNetwork }
										options={ SOCIAL_NETWORK_OPTIONS }
										onChange={ setSocialNetwork }
										__next40pxDefaultSize
										__nextHasNoMarginBottom
									/>
									<TextareaControl
										label={ __( 'Contenu source (ou laisser vide pour utiliser l\'article)', 'g2rd' ) }
										value={ socialContent }
										onChange={ setSocialContent }
										rows={ 4 }
										__nextHasNoMarginBottom
									/>
									<G2RDAiButton
										label={ __( 'Générer les posts', 'g2rd' ) }
										loading={ loading && modalAction === 'social' }
										disabled={ ! config.connectorReady }
										variant="primary"
										onClick={ () => handleGenerate(
											'generate-social',
											{ context: {
												existing_content: socialContent || postContent,
												service: postTitle,
											} },
											__( 'Génération réseaux sociaux', 'g2rd' )
										) }
									/>
								</PanelBody>
							);

						// ── Onglet MAILLAGE ──────────────────────────────────
						case 'links':
							return (
								<PanelBody title={ __( 'Maillage interne', 'g2rd' ) } initialOpen>
									<Text variant="muted" as="p">
										{ __( 'Suggère des liens internes depuis le contenu de la page courante.', 'g2rd' ) }
									</Text>
									<G2RDAiButton
										label={ __( 'Suggérer des liens internes', 'g2rd' ) }
										loading={ loading && modalAction === 'links' }
										disabled={ ! config.connectorReady || ! postContent }
										variant="primary"
										onClick={ () => handleGenerate(
											'suggest-links',
											{ context: { existing_content: postContent } },
											__( 'Suggestions de maillage', 'g2rd' )
										) }
									/>
								</PanelBody>
							);

						default:
							return null;
					}
				} }
			</TabPanel>

			{ /* Modal de prévisualisation — partagée entre tous les onglets */ }
			<G2RDAiPreviewModal
				isOpen={ modalOpen }
				result={ result }
				parsed={ parsed }
				actionLabel={ modalAction }
				loading={ loading }
				error={ error }
				onInsert={ () => {
					// Dans la sidebar, l'insertion n'est pas directe dans les attributs du bloc.
					// L'utilisateur copie puis colle où il le souhaite.
					// Pour les pages/articles, on pourrait utiliser wp.data dispatch.
					handleCloseModal();
				} }
				onRegenerate={ () => {
					// Relance la dernière génération — le payload est dans le state du handler.
					// Pour simplifier, on ferme et l'utilisateur reclique.
					setModalOpen( false );
					reset();
				} }
				onClose={ handleCloseModal }
			/>
		</div>
	);
}
