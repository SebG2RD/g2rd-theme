import { useState, useEffect, useCallback } from '@wordpress/element';
import {
	Button,
	Spinner,
	TextControl,
	ToggleControl,
	RangeControl,
	SelectControl,
	TextareaControl,
	Notice,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

const AI_ENDPOINT = '/g2rd/v1/ai/settings';
const PROFILE_ENDPOINT = '/g2rd/v1/ai/profile';

const PROFILE_DEFAULTS = {
	activity: '',
	city:     '',
	target:   '',
	tone:     'professionnel',
};

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
	ai_blocks_enabled:      false,
	ai_editor_enabled:      false,
	ai_logs_enabled:        true,
	ai_daily_limit:         50,
	ai_default_tone:        'professionnel',
	ai_default_length:      'moyen',
	ai_custom_instructions: '',
};

export function TabIA() {
	const [ aiSettings,    setAiSettings ]    = useState( DEFAULTS );
	const [ profile,       setProfile ]       = useState( PROFILE_DEFAULTS );
	const [ loading,       setLoading ]       = useState( true );
	const [ saving,        setSaving ]        = useState( false );
	const [ notice,        setNotice ]        = useState( null );

	// Clé API — état d'affichage
	const [ apiKeySet,     setApiKeySet ]     = useState( false );
	const [ apiKeyPreview, setApiKeyPreview ] = useState( '' );
	const [ apiKeyEditing, setApiKeyEditing ] = useState( false );
	const [ apiKeyValue,   setApiKeyValue ]   = useState( '' );

	useEffect( () => {
		// Requêtes parallèles (pas de cascade) : réglages + profil.
		Promise.all( [
			apiFetch( { path: AI_ENDPOINT } ).catch( () => null ),
			apiFetch( { path: PROFILE_ENDPOINT } ).catch( () => null ),
		] )
			.then( ( [ data, profileData ] ) => {
				if ( data && typeof data === 'object' ) {
					setAiSettings( ( prev ) => ( { ...prev, ...data } ) );
					setApiKeySet( !! data.api_key_set );
					setApiKeyPreview( data.api_key_preview || '' );
					if ( ! data.api_key_set ) {
						setApiKeyEditing( true );
					}
				} else {
					setNotice( { type: 'error', message: 'Impossible de charger les paramètres IA.' } );
				}
				if ( profileData && profileData.profile ) {
					setProfile( ( prev ) => ( { ...prev, ...profileData.profile } ) );
				}
			} )
			.finally( () => setLoading( false ) );
	}, [] );

	const set = useCallback( ( key, value ) => {
		setAiSettings( ( prev ) => ( { ...prev, [ key ]: value } ) );
	}, [] );

	const setProfileField = useCallback( ( key, value ) => {
		setProfile( ( prev ) => ( { ...prev, [ key ]: value } ) );
	}, [] );

	const handleSave = useCallback( () => {
		setSaving( true );
		setNotice( null );

		const payload = { ...aiSettings };
		delete payload.api_key_set;
		delete payload.api_key_preview;

		// N'envoyer la clé que si une nouvelle valeur a été saisie.
		if ( apiKeyEditing && apiKeyValue.trim() ) {
			payload.api_key = apiKeyValue.trim();
		}

		// Réglages + profil enregistrés en parallèle.
		Promise.all( [
			apiFetch( { path: AI_ENDPOINT, method: 'POST', data: payload } ),
			apiFetch( { path: PROFILE_ENDPOINT, method: 'POST', data: { profile } } ),
		] )
			.then( ( [ , profileRes ] ) => {
				setNotice( { type: 'success', message: 'Réglages IA et profil du site enregistrés.' } );
				// Reflète le profil RÉELLEMENT enregistré (renvoyé par le serveur) :
				// si la persistance échoue, les champs se videront immédiatement.
				if ( profileRes && profileRes.profile ) {
					setProfile( ( prev ) => ( { ...prev, ...profileRes.profile } ) );
				}
				if ( apiKeyEditing && apiKeyValue.trim() ) {
					const k = apiKeyValue.trim();
					setApiKeySet( true );
					setApiKeyPreview( '••••' + k.slice( -4 ) );
					setApiKeyEditing( false );
					setApiKeyValue( '' );
				}
			} )
			.catch( ( err ) => {
				setNotice( { type: 'error', message: err?.message || 'Erreur lors de la sauvegarde.' } );
			} )
			.finally( () => setSaving( false ) );
	}, [ aiSettings, apiKeyEditing, apiKeyValue, profile ] );

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

			{ /* ── Profil du site (capturé une fois, réutilisé partout) ── */ }
			<section className="g2rd-section">
				<h2 className="g2rd-section__title">
					<span className="dashicons dashicons-id"></span>
					Profil du site
				</h2>
				<p className="g2rd-section__desc">
					Saisi <strong>une seule fois</strong> ici, puis réutilisé automatiquement par toutes
					les générations IA (blocs, pages, articles…). Plus besoin de re-saisir l'activité ou
					la ville dans chaque bloc.
				</p>

				<div className="g2rd-settings-grid">
					<TextControl
						label="Activité"
						placeholder="Ex. Apiculture artisanale"
						value={ profile.activity }
						onChange={ ( val ) => setProfileField( 'activity', val ) }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<TextControl
						label="Ville / zone"
						placeholder="Ex. Villiers-sous-Grez"
						value={ profile.city }
						onChange={ ( val ) => setProfileField( 'city', val ) }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<TextControl
						label="Cible"
						placeholder="Ex. Particuliers gourmets"
						value={ profile.target }
						onChange={ ( val ) => setProfileField( 'target', val ) }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<SelectControl
						label="Ton par défaut"
						value={ profile.tone }
						options={ TONE_OPTIONS }
						onChange={ ( val ) => setProfileField( 'tone', val ) }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
				</div>
			</section>

			{ /* ── Clé API Anthropic ───────────────────────────────────── */ }
			<section className="g2rd-section">
				<h2 className="g2rd-section__title">
					<span className="dashicons dashicons-cloud"></span>
					Clé API Anthropic
				</h2>
				<p className="g2rd-section__desc">
					Clé secrète pour les appels IA. Obtenez-en une sur <strong>console.anthropic.com</strong>.
					Elle est stockée en base et jamais exposée publiquement.
				</p>
				{ apiKeyEditing ? (
					<TextControl
						label="Clé API"
						type="password"
						value={ apiKeyValue }
						onChange={ setApiKeyValue }
						placeholder="sk-ant-..."
						autoComplete="new-password"
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
				) : (
					<div className="g2rd-api-key-row">
						<code className="g2rd-api-key-preview">{ apiKeyPreview || '—' }</code>
						<Button
							variant="secondary"
							size="small"
							onClick={ () => { setApiKeyEditing( true ); setApiKeyValue( '' ); } }
						>
							Modifier
						</Button>
					</div>
				) }
			</section>

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
						label="IA éditoriale (modal)"
						help="Ajoute le bouton IA dans la toolbar de l'éditeur Gutenberg : génération de pages, articles, SEO, réseaux sociaux, maillage."
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

			{ /* ── Consignes personnalisées ───────────────────────────── */ }
			<section className="g2rd-section">
				<h2 className="g2rd-section__title">
					<span className="dashicons dashicons-format-quote"></span>
					Consignes personnalisées
				</h2>
				<p className="g2rd-section__desc">
					Décrivez votre entreprise, votre activité, votre ton éditorial ou toute contrainte spécifique.
					Ces informations seront automatiquement ajoutées au contexte de chaque génération IA.
				</p>
				<TextareaControl
					label="Contexte de votre site web"
					help="Exemple : « Agence web spécialisée PME à Lyon, ton chaleureux et professionnel, pas de jargon technique, clientèle artisans et commerçants. »"
					value={ aiSettings.ai_custom_instructions }
					onChange={ ( val ) => set( 'ai_custom_instructions', val ) }
					rows={ 5 }
					__nextHasNoMarginBottom
				/>
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
