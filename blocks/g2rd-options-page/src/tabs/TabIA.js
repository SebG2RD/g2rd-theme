import { useState, useEffect, useCallback } from '@wordpress/element';
import {
	Button,
	Spinner,
	ToggleControl,
	RangeControl,
	SelectControl,
	Notice,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

const { restBase } = window.G2RDOptionsData || {};
const AI_ENDPOINT  = `${ restBase }g2rd/v1/ai/settings`;

const TONE_OPTIONS = [
	{ label: 'Professionnel', value: 'professionnel' },
	{ label: 'Décontracté',   value: 'decontracte' },
	{ label: 'Technique',     value: 'technique' },
	{ label: 'Humain',        value: 'humain' },
];

const LENGTH_OPTIONS = [
	{ label: 'Court',  value: 'court' },
	{ label: 'Moyen',  value: 'moyen' },
	{ label: 'Long',   value: 'long' },
];

const DEFAULTS = {
	ai_blocks_enabled: false,
	ai_editor_enabled: false,
	ai_logs_enabled:   true,
	ai_daily_limit:    50,
	ai_default_tone:   'professionnel',
	ai_default_length: 'moyen',
};

export function TabIA() {
	const [ aiSettings, setAiSettings ] = useState( DEFAULTS );
	const [ loading,    setLoading ]    = useState( true );
	const [ saving,     setSaving ]     = useState( false );
	const [ notice,     setNotice ]     = useState( null );

	// Chargement des paramètres IA
	useEffect( () => {
		apiFetch( { path: '/g2rd/v1/ai/settings' } )
			.then( ( data ) => {
				if ( data && typeof data === 'object' ) {
					setAiSettings( ( prev ) => ( { ...prev, ...data } ) );
				}
			} )
			.catch( () => {
				setNotice( { type: 'error', message: 'Impossible de charger les paramètres IA.' } );
			} )
			.finally( () => setLoading( false ) );
	}, [] );

	const set = useCallback( ( key, value ) => {
		setAiSettings( ( prev ) => ( { ...prev, [ key ]: value } ) );
	}, [] );

	const handleSave = useCallback( () => {
		setSaving( true );
		setNotice( null );

		apiFetch( {
			path:   '/g2rd/v1/ai/settings',
			method: 'POST',
			data:   aiSettings,
		} )
			.then( () => {
				setNotice( { type: 'success', message: 'Paramètres IA enregistrés.' } );
			} )
			.catch( ( err ) => {
				setNotice( { type: 'error', message: err?.message || 'Erreur lors de la sauvegarde.' } );
			} )
			.finally( () => setSaving( false ) );
	}, [ aiSettings ] );

	if ( loading ) {
		return (
			<div className="g2rd-tab-content" style={ { padding: '2rem', textAlign: 'center' } }>
				<Spinner />
			</div>
		);
	}

	return (
		<div className="g2rd-tab-content">

			{ notice && (
				<Notice
					status={ notice.type }
					onRemove={ () => setNotice( null ) }
					isDismissible
				>
					{ notice.message }
				</Notice>
			) }

			{ /* ── Activation ──────────────────────────────────────────── */ }
			<section className="g2rd-section">
				<h2 className="g2rd-section__title">
					<span className="dashicons dashicons-superhero-alt"></span>
					Activation du module IA
				</h2>
				<p className="g2rd-section__desc">
					Le module IA doit d'abord être activé dans <strong>Éditeur → Fonctionnalités</strong> (interrupteur « Module IA G2RD »).
					Ces réglages configurent son comportement une fois activé.
				</p>

				<div className="g2rd-settings-grid">
					<ToggleControl
						label="IA dans les blocs Gutenberg"
						help="Ajoute un panneau IA dans les InspectorControls des blocs Hero, FAQ, CTA, Tarifs, Témoignage, Card."
						checked={ !! aiSettings.ai_blocks_enabled }
						onChange={ ( val ) => set( 'ai_blocks_enabled', val ) }
						__nextHasNoMarginBottom
					/>
					<ToggleControl
						label="IA éditoriale (sidebar)"
						help="Ajoute la sidebar IA dans l'éditeur Gutenberg : génération de pages, articles, SEO, réseaux sociaux, maillage."
						checked={ !! aiSettings.ai_editor_enabled }
						onChange={ ( val ) => set( 'ai_editor_enabled', val ) }
						__nextHasNoMarginBottom
					/>
					<ToggleControl
						label="Journal des appels IA"
						help="Enregistre chaque appel IA dans les logs MCP (audit trail)."
						checked={ !! aiSettings.ai_logs_enabled }
						onChange={ ( val ) => set( 'ai_logs_enabled', val ) }
						__nextHasNoMarginBottom
					/>
				</div>
			</section>

			{ /* ── Limites ──────────────────────────────────────────────── */ }
			<section className="g2rd-section">
				<h2 className="g2rd-section__title">
					<span className="dashicons dashicons-performance"></span>
					Limites et quotas
				</h2>

				<RangeControl
					label="Quota journalier par utilisateur"
					help="Nombre maximal d'appels IA par utilisateur et par jour (remise à zéro à minuit UTC)."
					value={ aiSettings.ai_daily_limit }
					onChange={ ( val ) => set( 'ai_daily_limit', val ) }
					min={ 1 }
					max={ 500 }
					step={ 1 }
					__nextHasNoMarginBottom
				/>
			</section>

			{ /* ── Valeurs par défaut ───────────────────────────────────── */ }
			<section className="g2rd-section">
				<h2 className="g2rd-section__title">
					<span className="dashicons dashicons-edit-large"></span>
					Valeurs par défaut
				</h2>
				<p className="g2rd-section__desc">
					Ces valeurs sont pré-remplies dans les interfaces IA mais restent modifiables par chaque utilisateur.
				</p>

				<div className="g2rd-settings-grid">
					<SelectControl
						label="Ton par défaut"
						value={ aiSettings.ai_default_tone }
						options={ TONE_OPTIONS }
						onChange={ ( val ) => set( 'ai_default_tone', val ) }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<SelectControl
						label="Longueur par défaut"
						value={ aiSettings.ai_default_length }
						options={ LENGTH_OPTIONS }
						onChange={ ( val ) => set( 'ai_default_length', val ) }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
				</div>
			</section>

			{ /* ── Connecteur ───────────────────────────────────────────── */ }
			<section className="g2rd-section">
				<h2 className="g2rd-section__title">
					<span className="dashicons dashicons-cloud"></span>
					Connecteur IA WordPress
				</h2>
				<p className="g2rd-section__desc">
					Le module IA utilise les <strong>WordPress AI Connectors</strong> (WordPress 7.0+).
					Aucune clé API n'est stockée dans le thème : configurez votre fournisseur IA
					directement dans <strong>Réglages → IA</strong> de WordPress.
				</p>
				<p className="g2rd-section__desc">
					Si les Connectors ne sont pas disponibles (WordPress &lt; 7.0), les boutons IA
					sont automatiquement désactivés avec un message explicatif.
				</p>
			</section>

			{ /* ── Actions ──────────────────────────────────────────────── */ }
			<div className="g2rd-actions">
				<Button
					variant="primary"
					onClick={ handleSave }
					isBusy={ saving }
					disabled={ saving }
				>
					{ saving ? 'Enregistrement…' : 'Enregistrer les réglages IA' }
				</Button>
			</div>

		</div>
	);
}
