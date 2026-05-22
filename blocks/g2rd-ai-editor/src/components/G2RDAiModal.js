/**
 * G2RDAiModal — Modal principal de l'assistant IA G2RD
 *
 * Remplace G2RDAiSidebar + G2RDAiPreviewModal.
 * Contexte + onglets + résultat inline dans une seule modal large.
 *
 * @since 1.16.0
 */

import { useState, useCallback, useMemo, useRef } from '@wordpress/element';
import { __ }                                      from '@wordpress/i18n';
import {
	Modal,
	TextControl,
	TextareaControl,
	SelectControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';

import { useG2RDAi }                              from '../hooks/useG2RDAi';
import G2RDAiNotice                               from './G2RDAiNotice';
import { TONE_OPTIONS, LENGTH_OPTIONS, buildPayload } from '../utils/ai-actions';

// ── Constantes hoissées — jamais recréées ──────────────────────────────────

const PAGE_TYPE_OPTIONS = [
	{ label: 'Page de service', value: 'service' },
	{ label: 'Page locale',     value: 'local' },
];

const POST_MODE_OPTIONS = [
	{ label: "Plan d'article",  value: 'outline' },
	{ label: 'Article complet', value: 'full' },
];

const SOCIAL_NETWORK_OPTIONS = [
	{ label: 'Tous les réseaux', value: 'all' },
	{ label: 'Facebook',         value: 'facebook' },
	{ label: 'LinkedIn',         value: 'linkedin' },
	{ label: 'Google Business',  value: 'google' },
];

const TABS = [
	{ name: 'page',   title: 'Page' },
	{ name: 'post',   title: 'Article' },
	{ name: 'seo',    title: 'SEO' },
	{ name: 'social', title: 'Réseaux sociaux' },
	{ name: 'links',  title: 'Maillage' },
];

const REGEN_ICON = (
	<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" aria-hidden="true">
		<path d="M3 12a9 9 0 0 1 15-6.7L21 8" strokeLinecap="round"/>
		<path d="M21 3v5h-5" strokeLinecap="round" strokeLinejoin="round"/>
		<path d="M21 12a9 9 0 0 1-15 6.7L3 16" strokeLinecap="round"/>
		<path d="M3 21v-5h5" strokeLinecap="round" strokeLinejoin="round"/>
	</svg>
);

const COPY_ICON = (
	<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" aria-hidden="true">
		<rect x="9" y="9" width="13" height="13" rx="2" strokeLinejoin="round"/>
		<path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" strokeLinecap="round"/>
	</svg>
);

// ──────────────────────────────────────────────────────────────────────────────

/**
 * @param {Object}   props
 * @param {Function} props.onClose Ferme la modal.
 */
export default function G2RDAiModal( { onClose } ) {
	const config = window.g2rdAiConfig ?? {};

	// Contexte partagé
	const [ activity, setActivity ] = useState( '' );
	const [ city, setCity ]         = useState( '' );
	const [ service, setService ]   = useState( '' );
	const [ target, setTarget ]     = useState( '' );
	const [ tone, setTone ]         = useState( config.tone ?? 'professionnel' );
	const [ length, setLength ]     = useState( 'moyen' );
	const [ keywords, setKeywords ] = useState( '' );

	// Navigation onglets
	const [ activeTab, setActiveTab ] = useState( 'page' );

	// État par onglet
	const [ pageType, setPageType ]             = useState( 'service' );
	const [ postMode, setPostMode ]             = useState( 'outline' );
	const [ postSubject, setPostSubject ]       = useState( '' );
	const [ seoTitle, setSeoTitle ]             = useState( '' );
	const [ socialNetwork, setSocialNetwork ]   = useState( 'all' );
	const [ socialContent, setSocialContent ]   = useState( '' );

	// Mémorise le dernier appel pour la régénération.
	const lastCallRef = useRef( null );

	const { generate, loading, result, parsed, error, reset } = useG2RDAi();

	const { postContent, postTitle } = useSelect( ( select ) => {
		const editor = select( 'core/editor' );
		return {
			postContent: editor?.getEditedPostContent() ?? '',
			postTitle:   editor?.getEditedPostAttribute( 'title' ) ?? '',
		};
	}, [] );

	const sharedCtx = useMemo( () => ( {
		activity,
		city,
		service,
		target,
		tone,
		language: config.language ?? 'fr',
		length,
		keywords,
	} ), [ activity, city, service, target, tone, config.language, length, keywords ] );

	const handleGenerate = useCallback( async ( endpoint, actionData ) => {
		const payload = buildPayload( endpoint, actionData, sharedCtx );
		lastCallRef.current = { endpoint, payload };
		reset();
		await generate( { endpoint, payload } );
	}, [ generate, sharedCtx, reset ] );

	const handleRegenerate = useCallback( () => {
		if ( lastCallRef.current ) {
			reset();
			generate( lastCallRef.current );
		}
	}, [ generate, reset ] );

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

	const handleTabChange = useCallback( ( tabName ) => {
		setActiveTab( tabName );
		reset();
		lastCallRef.current = null;
	}, [ reset ] );

	// ── Bouton de génération ───────────────────────────────────────────────

	const GenBtn = ( { label, onClick, disabled = false, secondary = false } ) => (
		<button
			className={ `g2rd-aim__gen-btn${ secondary ? ' g2rd-aim__gen-btn--alt' : '' }` }
			onClick={ onClick }
			disabled={ ! config.connectorReady || loading || disabled }
			type="button"
		>
			{ loading && ! secondary
				? <span className="g2rd-aim__spin" aria-hidden="true" />
				: <span className="g2rd-aim__gen-star" aria-hidden="true">✦</span>
			}
			{ label }
		</button>
	);

	// ── Contenu de l'onglet actif ──────────────────────────────────────────

	const renderTabContent = () => {
		switch ( activeTab ) {

			case 'page':
				return (
					<div className="g2rd-aim__tab-inner">
						<SelectControl
							label={ __( 'Type de page', 'g2rd' ) }
							value={ pageType }
							options={ PAGE_TYPE_OPTIONS }
							onChange={ setPageType }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
						<GenBtn
							label={ __( 'Générer la page', 'g2rd' ) }
							onClick={ () => handleGenerate( 'generate-page', { page_type: pageType, post_id: 0 } ) }
						/>
					</div>
				);

			case 'post':
				return (
					<div className="g2rd-aim__tab-inner">
						<TextControl
							label={ __( "Sujet de l'article", 'g2rd' ) }
							value={ postSubject }
							onChange={ setPostSubject }
							placeholder={ __( 'Ex. Les avantages de la rénovation thermique', 'g2rd' ) }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
						<div className="g2rd-aim__pills-group">
							<span className="g2rd-aim__label">{ __( 'Mode', 'g2rd' ) }</span>
							<div className="g2rd-aim__pills" role="radiogroup" aria-label={ __( 'Mode de génération', 'g2rd' ) }>
								{ POST_MODE_OPTIONS.map( ( opt ) => (
									<button
										key={ opt.value }
										role="radio"
										aria-checked={ postMode === opt.value }
										className={ `g2rd-aim__pill${ postMode === opt.value ? ' g2rd-aim__pill--on' : '' }` }
										onClick={ () => setPostMode( opt.value ) }
										type="button"
									>
										{ opt.label }
									</button>
								) ) }
							</div>
						</div>
						<GenBtn
							label={ 'outline' === postMode
								? __( 'Générer le plan', 'g2rd' )
								: __( "Générer l'article", 'g2rd' )
							}
							disabled={ ! postSubject }
							onClick={ () => handleGenerate( 'generate-post', { mode: postMode, context: { service: postSubject } } ) }
						/>
					</div>
				);

			case 'seo':
				return (
					<div className="g2rd-aim__tab-inner">
						<TextControl
							label={ __( 'Titre de la page', 'g2rd' ) }
							value={ seoTitle || postTitle }
							onChange={ setSeoTitle }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
						<div className="g2rd-aim__btn-row">
							<GenBtn
								label={ __( 'Title / Description / Slug', 'g2rd' ) }
								onClick={ () => handleGenerate( 'generate-seo', { context: { service: seoTitle || postTitle, existing_content: postContent } } ) }
							/>
							<GenBtn
								label={ __( 'Suggestions H2', 'g2rd' ) }
								secondary
								onClick={ () => handleGenerate( 'optimize-content', { context: { existing_content: postContent } } ) }
							/>
						</div>
					</div>
				);

			case 'social':
				return (
					<div className="g2rd-aim__tab-inner">
						<div className="g2rd-aim__pills-group">
							<span className="g2rd-aim__label">{ __( 'Réseau cible', 'g2rd' ) }</span>
							<div className="g2rd-aim__pills" role="radiogroup" aria-label={ __( 'Réseau social cible', 'g2rd' ) }>
								{ SOCIAL_NETWORK_OPTIONS.map( ( opt ) => (
									<button
										key={ opt.value }
										role="radio"
										aria-checked={ socialNetwork === opt.value }
										className={ `g2rd-aim__pill${ socialNetwork === opt.value ? ' g2rd-aim__pill--on' : '' }` }
										onClick={ () => setSocialNetwork( opt.value ) }
										type="button"
									>
										{ opt.label }
									</button>
								) ) }
							</div>
						</div>
						<TextareaControl
							label={ __( 'Contenu source (optionnel — utilise le contenu de la page sinon)', 'g2rd' ) }
							value={ socialContent }
							onChange={ setSocialContent }
							rows={ 3 }
							__nextHasNoMarginBottom
						/>
						<GenBtn
							label={ __( 'Générer les posts', 'g2rd' ) }
							onClick={ () => handleGenerate( 'generate-social', { context: { existing_content: socialContent || postContent, service: postTitle } } ) }
						/>
					</div>
				);

			case 'links':
				return (
					<div className="g2rd-aim__tab-inner">
						<p className="g2rd-aim__hint">
							{ __( 'Suggère des liens internes pertinents depuis le contenu de la page courante.', 'g2rd' ) }
						</p>
						<GenBtn
							label={ __( 'Suggérer des liens internes', 'g2rd' ) }
							disabled={ ! postContent }
							onClick={ () => handleGenerate( 'suggest-links', { context: { existing_content: postContent } } ) }
						/>
					</div>
				);

			default:
				return null;
		}
	};

	// ── Formatage du résultat ─────────────────────────────────────────────

	const resultDisplay = useMemo( () => {
		if ( ! result ) return null;
		if ( typeof parsed === 'object' && parsed !== null ) {
			return <pre className="g2rd-aim__result-code">{ JSON.stringify( parsed, null, 2 ) }</pre>;
		}
		return <p className="g2rd-aim__result-text">{ result }</p>;
	}, [ result, parsed ] );

	// ── Rendu ─────────────────────────────────────────────────────────────

	return (
		<Modal
			title={ __( 'Assistant IA G2RD', 'g2rd' ) }
			onRequestClose={ onClose }
			className="g2rd-aim"
			size="large"
		>
			<div className="g2rd-aim__body">

				{ /* Alerte connecteur non configuré */ }
				{ ! config.connectorReady && (
					<div className="g2rd-aim__alert" role="alert">
						<span aria-hidden="true">⚠</span>
						{ __( 'Clé API Anthropic non configurée.', 'g2rd' ) }{ ' ' }
						<a href={ config.settingsUrl } target="_blank" rel="noreferrer">
							{ __( 'Ouvrir Réglages G2RD › IA', 'g2rd' ) }
						</a>
					</div>
				) }

				{ /* ── Contexte ── */ }
				<div className="g2rd-aim__section">
					<span className="g2rd-aim__section-label">{ __( 'Contexte', 'g2rd' ) }</span>
					<div className="g2rd-aim__ctx-grid">
						<TextControl
							label={ __( 'Activité', 'g2rd' ) }
							value={ activity }
							onChange={ setActivity }
							placeholder={ __( 'Ex. agence web, plombier…', 'g2rd' ) }
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
					</div>
					<TextControl
						label={ __( 'Mots-clés SEO', 'g2rd' ) }
						value={ keywords }
						onChange={ setKeywords }
						placeholder={ __( 'création site web, référencement naturel…', 'g2rd' ) }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<div className="g2rd-aim__ctx-row">
						<div className="g2rd-aim__pills-group">
							<span className="g2rd-aim__label">{ __( 'Ton', 'g2rd' ) }</span>
							<div className="g2rd-aim__pills" role="radiogroup" aria-label={ __( 'Ton de rédaction', 'g2rd' ) }>
								{ TONE_OPTIONS.map( ( opt ) => (
									<button
										key={ opt.value }
										role="radio"
										aria-checked={ tone === opt.value }
										className={ `g2rd-aim__pill${ tone === opt.value ? ' g2rd-aim__pill--on' : '' }` }
										onClick={ () => setTone( opt.value ) }
										type="button"
									>
										{ opt.label }
									</button>
								) ) }
							</div>
						</div>
						<div className="g2rd-aim__pills-group">
							<span className="g2rd-aim__label">{ __( 'Longueur', 'g2rd' ) }</span>
							<div className="g2rd-aim__pills" role="radiogroup" aria-label={ __( 'Longueur du contenu', 'g2rd' ) }>
								{ LENGTH_OPTIONS.map( ( opt ) => (
									<button
										key={ opt.value }
										role="radio"
										aria-checked={ length === opt.value }
										className={ `g2rd-aim__pill${ length === opt.value ? ' g2rd-aim__pill--on' : '' }` }
										onClick={ () => setLength( opt.value ) }
										type="button"
									>
										{ opt.label }
									</button>
								) ) }
							</div>
						</div>
					</div>
				</div>

				{ /* ── Onglets + contenu ── */ }
				<div className="g2rd-aim__section g2rd-aim__section--tabs">
					<div className="g2rd-aim__tabs" role="tablist">
						{ TABS.map( ( tab ) => (
							<button
								key={ tab.name }
								role="tab"
								id={ `g2rd-aim-tab-${ tab.name }` }
								aria-selected={ activeTab === tab.name }
								aria-controls={ `g2rd-aim-panel-${ tab.name }` }
								className={ `g2rd-aim__tab${ activeTab === tab.name ? ' g2rd-aim__tab--on' : '' }` }
								onClick={ () => handleTabChange( tab.name ) }
								type="button"
							>
								{ tab.title }
							</button>
						) ) }
					</div>
					<div
						className="g2rd-aim__tab-panel"
						role="tabpanel"
						id={ `g2rd-aim-panel-${ activeTab }` }
						aria-labelledby={ `g2rd-aim-tab-${ activeTab }` }
					>
						{ renderTabContent() }
					</div>
				</div>

				{ /* ── Résultat inline ── */ }
				{ ( loading || result || error ) && (
					<div className="g2rd-aim__result-wrap" aria-live="polite" aria-atomic="false">
						{ loading && (
							<div className="g2rd-aim__loading" role="status">
								<span className="g2rd-aim__dot" aria-hidden="true" />
								<span className="g2rd-aim__dot g2rd-aim__dot--2" aria-hidden="true" />
								<span className="g2rd-aim__dot g2rd-aim__dot--3" aria-hidden="true" />
								<span>{ __( 'Génération en cours…', 'g2rd' ) }</span>
							</div>
						) }
						{ error && ! loading && (
							<G2RDAiNotice message={ error } type="error" onDismiss={ reset } />
						) }
						{ result && ! loading && (
							<div className="g2rd-aim__result-card">
								<div className="g2rd-aim__result-bar">
									<span className="g2rd-aim__result-label">{ __( 'Proposition', 'g2rd' ) }</span>
									<div className="g2rd-aim__result-actions">
										<button
											className="g2rd-aim__action-btn"
											onClick={ handleRegenerate }
											type="button"
											aria-label={ __( 'Régénérer', 'g2rd' ) }
										>
											{ REGEN_ICON }
											{ __( 'Régénérer', 'g2rd' ) }
										</button>
										<button
											className="g2rd-aim__action-btn g2rd-aim__action-btn--primary"
											onClick={ handleCopy }
											type="button"
											aria-label={ __( 'Copier dans le presse-papier', 'g2rd' ) }
										>
											{ COPY_ICON }
											{ __( 'Copier', 'g2rd' ) }
										</button>
									</div>
								</div>
								<div className="g2rd-aim__result-body">
									{ resultDisplay }
								</div>
							</div>
						) }
					</div>
				) }

			</div>
		</Modal>
	);
}
