import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	InspectorControls,
	InnerBlocks,
} from '@wordpress/block-editor';
import {
	PanelBody,
	PanelRow,
	SelectControl,
	TextControl,
	TextareaControl,
	ToggleControl,
	RangeControl,
	Button,
	Notice,
	Spinner,
} from '@wordpress/components';
import { useState, useEffect, useRef } from '@wordpress/element';

export default function Edit( { attributes, setAttributes, clientId } ) {
	const {
		connectorType,
		apiUrl,
		apiMethod,
		apiHeaders,
		apiBody,
		responseField,
		singleItem,
		enableAjax,
		ajaxTrigger,
		formSelector,
		customInteractionId,
		intervalTime,
		intervalCount,
		customLoaderSelector,
		paginationSelector,
		enableChat,
		enableStreaming,
		chatAddField,
		chatUserFormat,
		chatAssistantFormat,
		chatResponseField,
		resultMappings,
		resultsContainerClass,
		templateContainerClass,
		blockId,
	} = attributes;

	const [ testResult, setTestResult ]         = useState( null );
	const [ isTesting, setIsTesting ]           = useState( false );
	const [ testError, setTestError ]           = useState( null );
	const abortRef                              = useRef( null );
	const [ newHeaderKey, setNewHeaderKey ]     = useState( '' );
	const [ newHeaderValue, setNewHeaderValue ] = useState( '' );
	const [ newMapSelector, setNewMapSelector ] = useState( '' );
	const [ newMapKey, setNewMapKey ]           = useState( '' );

	// Générer un ID unique au premier rendu.
	useEffect( () => {
		if ( ! blockId ) {
			setAttributes( { blockId: 'g2rd-api-' + clientId.substring( 0, 8 ) } );
		}
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps

	// Annuler le fetch de test si le bloc est démonté (navigation entre blocs, suppression).
	useEffect( () => {
		return () => {
			if ( abortRef.current ) {
				abortRef.current.abort();
			}
		};
	}, [] );

	const blockProps = useBlockProps( { className: 'g2rd-block-api-editor' } );

	// --- Test de l'API depuis l'éditeur ---
	const handleTestApi = async () => {
		if ( ! apiUrl ) return;

		// Annuler tout test précédent encore en cours avant d'en lancer un nouveau.
		if ( abortRef.current ) {
			abortRef.current.abort();
		}
		abortRef.current = new AbortController();

		setIsTesting( true );
		setTestError( null );
		setTestResult( null );

		try {
			const headers = { 'Content-Type': 'application/json' };
			if ( apiHeaders && apiHeaders.length > 0 ) {
				apiHeaders.forEach( ( { key, value } ) => {
					if ( key ) headers[ key ] = value;
				} );
			}

			const fetchOptions = {
				method:  apiMethod,
				headers,
				signal:  abortRef.current.signal,
			};

			if ( apiBody && [ 'POST', 'PUT', 'PATCH' ].includes( apiMethod ) ) {
				fetchOptions.body = apiBody;
			}

			const response = await fetch( apiUrl, fetchOptions );

			if ( ! response.ok ) {
				throw new Error( `HTTP ${ response.status } — ${ response.statusText }` );
			}

			const data = await response.json();

			// Extraire un sous-champ si configuré.
			let result = data;
			if ( responseField ) {
				const fields = responseField.replace( /\[(\w+)\]/g, '.$1' ).split( '.' );
				for ( const field of fields ) {
					if ( result && result[ field ] !== undefined ) {
						result = result[ field ];
					}
				}
			}

			setTestResult( JSON.stringify( result, null, 2 ).substring( 0, 3000 ) );
		} catch ( err ) {
			// Ignorer l'erreur d'annulation (démontage ou re-clic).
			if ( err.name === 'AbortError' ) return;
			setTestError( err.message );
		} finally {
			setIsTesting( false );
		}
	};

	// --- Gestion des en-têtes ---
	const addHeader = () => {
		if ( ! newHeaderKey ) return;
		setAttributes( {
			apiHeaders: [ ...apiHeaders, { key: newHeaderKey, value: newHeaderValue } ],
		} );
		setNewHeaderKey( '' );
		setNewHeaderValue( '' );
	};

	const removeHeader = ( index ) => {
		const updated = [ ...apiHeaders ];
		updated.splice( index, 1 );
		setAttributes( { apiHeaders: updated } );
	};

	// --- Gestion des mappages de champs ---
	const addMapping = () => {
		if ( ! newMapSelector || ! newMapKey ) return;
		setAttributes( {
			resultMappings: [
				...resultMappings,
				{ selector: newMapSelector, key: newMapKey },
			],
		} );
		setNewMapSelector( '' );
		setNewMapKey( '' );
	};

	const removeMapping = ( index ) => {
		const updated = [ ...resultMappings ];
		updated.splice( index, 1 );
		setAttributes( { resultMappings: updated } );
	};

	return (
		<>
			<InspectorControls>

				{ /* --- Connecteur API --- */ }
				<PanelBody title={ __( 'Connecteur API', 'g2rd' ) } initialOpen={ true }>
					<SelectControl
						label={ __( 'Type de connecteur', 'g2rd' ) }
						value={ connectorType }
						options={ [
							{ label: __( 'Côté client (JS fetch)', 'g2rd' ), value: 'client' },
							{ label: __( 'Côté serveur (proxy WP)', 'g2rd' ), value: 'server' },
						] }
						onChange={ ( value ) => setAttributes( { connectorType: value } ) }
						help={
							connectorType === 'client'
								? __( 'Exécuté dans le navigateur. Les clés API sont visibles.', 'g2rd' )
								: __( 'Exécuté sur le serveur. Les clés API restent privées.', 'g2rd' )
						}
					/>
					<SelectControl
						label={ __( 'Méthode HTTP', 'g2rd' ) }
						value={ apiMethod }
						options={ [
							{ label: 'GET',    value: 'GET' },
							{ label: 'POST',   value: 'POST' },
							{ label: 'PUT',    value: 'PUT' },
							{ label: 'PATCH',  value: 'PATCH' },
							{ label: 'DELETE', value: 'DELETE' },
						] }
						onChange={ ( value ) => setAttributes( { apiMethod: value } ) }
					/>
					<TextControl
						label={ __( "URL de l'API", 'g2rd' ) }
						value={ apiUrl }
						onChange={ ( value ) =
						__next40pxDefaultSize
						__nextHasNoMarginBottom
onChange={ ( value ) => setAttributes( { apiUrl: value } ) }
						placeholder="https://api.example.com/endpoint"
						help={ __(
							'Espaces réservés supportés : {{USER_META:field}}, {{FORM:field|default}}, {{INCREMENT:1}}, {{RANDOM:0-100}}',
							'g2rd'
						) }
					/>
					{ [ 'POST', 'PUT', 'PATCH' ].includes( apiMethod ) && (
						<TextareaControl
							label={ __( 'Corps de la requête (JSON)', 'g2rd' ) }
							value={ apiBody }
							onChange={ ( value ) =
							__nextHasNoMarginBottom
onChange={ ( value ) => setAttributes( { apiBody: value } ) }
							placeholder='{"key": "value"}'
							rows={ 4 }
						/>
					) }
				</PanelBody>

				{ /* --- En-têtes HTTP --- */ }
				<PanelBody title={ __( 'En-têtes HTTP', 'g2rd' ) } initialOpen={ false }>
					{ apiHeaders.map( ( header, index ) => (
						<PanelRow key={ `header-${ header.key || '' }-${ index }` } className="g2rd-api-header-row">
							<code>{ header.key }: { header.value }</code>
							<Button
								size="small"
								isDestructive
								onClick={ () => removeHeader( index ) }
								icon="trash"
								label={ __( 'Supprimer', 'g2rd' ) }
							/>
						</PanelRow>
					) ) }
					<TextControl
						label={ __( 'Clé', 'g2rd' ) }
						value={ newHeaderKey }
						onChange={ setNewHeaderKey }
						placeholder="Authorization"
						__next40pxDefaultSize
						__nextHasNoMarginBottom
placeholder="Authorization"/>
					<TextControl
						label={ __( 'Valeur', 'g2rd' ) }
						value={ newHeaderValue }
						onChange={ setNewHeaderValue }
						placeholder="Bearer mon-token…"
						__next40pxDefaultSize
						__nextHasNoMarginBottom
placeholder="Bearer mon-token…"/>
					<Button variant="primary" size="small" onClick={ addHeader }>
						{ __( "Ajouter l'en-tête", 'g2rd' ) }
					</Button>
				</PanelBody>

				{ /* --- Traitement de la réponse --- */ }
				<PanelBody title={ __( 'Traitement de la réponse', 'g2rd' ) } initialOpen={ false }>
					<TextControl
						label={ __( 'Champ de réponse', 'g2rd' ) }
						value={ responseField }
						onChange={ ( value ) =
						__next40pxDefaultSize
						__nextHasNoMarginBottom
onChange={ ( value ) => setAttributes( { responseField: value } ) }
						placeholder="data[results]"
						help={ __(
							'Laissez vide pour utiliser toute la réponse. Exemple : field[subfield][0]',
							'g2rd'
						) }
					/>
					<ToggleControl
						label={ __( "Élément unique (l'API renvoie un objet, pas un tableau)", 'g2rd' ) }
						checked={ singleItem }
						onChange={ ( value ) => setAttributes( { singleItem: value } ) }
					/>
				</PanelBody>

				{ /* --- Chargement Ajax --- */ }
				<PanelBody title={ __( 'Chargement Ajax', 'g2rd' ) } initialOpen={ false }>
					<ToggleControl
						label={ __( 'Activer le chargement Ajax', 'g2rd' ) }
						checked={ enableAjax }
						onChange={ ( value ) => setAttributes( { enableAjax: value } ) }
					/>
					{ enableAjax && (
						<>
							<SelectControl
								label={ __( 'Déclencheur', 'g2rd' ) }
								value={ ajaxTrigger }
								options={ [
									{ label: __( 'Soumission de formulaire', 'g2rd' ),  value: 'form' },
									{ label: __( 'Interaction personnalisée', 'g2rd' ), value: 'interaction' },
									{ label: __( 'Intervalle (boucle)', 'g2rd' ),       value: 'interval' },
								] }
								onChange={ ( value ) => setAttributes( { ajaxTrigger: value } ) }
							/>
							{ ajaxTrigger === 'form' && (
								<TextControl
									label={ __( 'Sélecteur du formulaire', 'g2rd' ) }
									value={ formSelector }
									onChange={ ( value ) =
									__next40pxDefaultSize
									__nextHasNoMarginBottom
onChange={ ( value ) => setAttributes( { formSelector: value } ) }
									placeholder=".mon-formulaire"
								/>
							) }
							{ ajaxTrigger === 'interaction' && (
								<>
									<TextControl
										label={ __( "ID d'interaction", 'g2rd' ) }
										value={ customInteractionId }
										onChange={ ( value ) =
										__next40pxDefaultSize
										__nextHasNoMarginBottom
onChange={ ( value ) => setAttributes( { customInteractionId: value } ) }
										placeholder="mon-api-trigger"
										help={ __(
											"Utilisez cet ID dans la couche d'interaction avec l'action « Appel API dynamique ».",
											'g2rd'
										) }
									/>
									{ blockId && (
										<Notice isDismissible={ false } status="info">
											<strong>{ __( 'ID de ce bloc :', 'g2rd' ) }</strong>{ ' ' }
											<code>{ blockId }</code>
										</Notice>
									) }
								</>
							) }
							{ ajaxTrigger === 'interval' && (
								<>
									<RangeControl
										label={ __( 'Intervalle (ms)', 'g2rd' ) }
										value={ intervalTime }
										onChange={ ( value ) => setAttributes( { intervalTime: value } ) }
										min={ 1000 }
										max={ 60000 }
										step={ 500 }
									/>
									<RangeControl
										label={ __( "Nombre d'appels (0 = infini)", 'g2rd' ) }
										value={ intervalCount }
										onChange={ ( value ) => setAttributes( { intervalCount: value } ) }
										min={ 0 }
										max={ 100 }
									/>
								</>
							) }
						</>
					) }
				</PanelBody>

				{ /* --- Affichage des résultats (côté client uniquement) --- */ }
				{ connectorType === 'client' && (
					<PanelBody title={ __( 'Affichage des résultats', 'g2rd' ) } initialOpen={ false }>
						<TextControl
							label={ __( 'Classe du conteneur de template', 'g2rd' ) }
							value={ templateContainerClass }
							onChange={ ( value ) =
							__next40pxDefaultSize
							__nextHasNoMarginBottom
onChange={ ( value ) => setAttributes( { templateContainerClass: value } ) }
							placeholder="api-template"
							help={ __(
								'Ce bloc sera masqué et cloné pour chaque résultat.',
								'g2rd'
							) }
						/>
						<TextControl
							label={ __( 'Classe du conteneur de résultats', 'g2rd' ) }
							value={ resultsContainerClass }
							onChange={ ( value ) =
							__next40pxDefaultSize
							__nextHasNoMarginBottom
onChange={ ( value ) => setAttributes( { resultsContainerClass: value } ) }
							placeholder="api-results"
							help={ __( 'Les résultats seront injectés ici.', 'g2rd' ) }
						/>
						<hr />
						<strong>{ __( 'Mappages de champs API → DOM', 'g2rd' ) }</strong>
						<p className="g2rd-api-panel-hint">
							{ __( 'Associez un sélecteur CSS à une clé de la réponse API.', 'g2rd' ) }
						</p>
						{ resultMappings.map( ( mapping, index ) => (
							<PanelRow key={ `mapping-${ mapping.selector || '' }-${ index }` } className="g2rd-api-mapping-row">
								<code>{ mapping.selector } → { mapping.key }</code>
								<Button
									size="small"
									isDestructive
									onClick={ () => removeMapping( index ) }
									icon="trash"
									label={ __( 'Supprimer', 'g2rd' ) }
								/>
							</PanelRow>
						) ) }
						<TextControl
							label={ __( 'Sélecteur CSS', 'g2rd' ) }
							value={ newMapSelector }
							onChange={ setNewMapSelector }
							placeholder=".titre-element"
							__next40pxDefaultSize
							__nextHasNoMarginBottom
placeholder=".titre-element"/>
						<TextControl
							label={ __( "Clé de l'API", 'g2rd' ) }
							value={ newMapKey }
							onChange={ setNewMapKey }
							placeholder="title ou urls[small]"
							__next40pxDefaultSize
							__nextHasNoMarginBottom
placeholder="title ou urls[small]"/>
						<Button variant="primary" size="small" onClick={ addMapping }>
							{ __( 'Ajouter le mapping', 'g2rd' ) }
						</Button>
					</PanelBody>
				) }

				{ /* --- Préchargeur & Pagination --- */ }
				<PanelBody title={ __( 'Préchargeur & Pagination', 'g2rd' ) } initialOpen={ false }>
					<TextControl
						label={ __( 'Sélecteur du préchargeur', 'g2rd' ) }
						value={ customLoaderSelector }
						onChange={ ( value ) =
						__next40pxDefaultSize
						__nextHasNoMarginBottom
onChange={ ( value ) => setAttributes( { customLoaderSelector: value } ) }
						placeholder=".mon-loader"
						help={ __( 'Reçoit la classe .active pendant le chargement.', 'g2rd' ) }
					/>
					<TextControl
						label={ __( 'Sélecteur de pagination', 'g2rd' ) }
						value={ paginationSelector }
						onChange={ ( value ) =
						__next40pxDefaultSize
						__nextHasNoMarginBottom
onChange={ ( value ) => setAttributes( { paginationSelector: value } ) }
						placeholder=".ma-pagination"
						help={ __( "Utilisez le paramètre ?page= dans l'URL API.", 'g2rd' ) }
					/>
				</PanelBody>

				{ /* --- Mode Chat & Streaming (côté client uniquement) --- */ }
				{ connectorType === 'client' && (
					<PanelBody title={ __( 'Mode Chat & Streaming', 'g2rd' ) } initialOpen={ false }>
						<ToggleControl
							label={ __( 'Activer le mode Chat', 'g2rd' ) }
							checked={ enableChat }
							onChange={ ( value ) => setAttributes( { enableChat: value } ) }
							help={ __( "Maintient l'historique des messages entre les appels.", 'g2rd' ) }
						/>
						<ToggleControl
							label={ __( 'Activer le streaming (SSE)', 'g2rd' ) }
							checked={ enableStreaming }
							onChange={ ( value ) => setAttributes( { enableStreaming: value } ) }
							help={ __( 'Affiche les résultats en temps réel par blocs (OpenAI, etc.).', 'g2rd' ) }
						/>
						{ ( enableChat || enableStreaming ) && (
							<>
								<TextControl
									label={ __( 'Champ des messages dans le corps', 'g2rd' ) }
									value={ chatAddField }
									onChange={ ( value ) =
									__next40pxDefaultSize
									__nextHasNoMarginBottom
onChange={ ( value ) => setAttributes( { chatAddField: value } ) }
									placeholder="messages"
								/>
								<TextareaControl
									label={ __( 'Format message utilisateur (JSON)', 'g2rd' ) }
									value={ chatUserFormat }
									onChange={ ( value ) =
									__nextHasNoMarginBottom
onChange={ ( value ) => setAttributes( { chatUserFormat: value } ) }
									placeholder={ '{"role":"user","content":"{{VALUE:.search-input}}"}' }
									rows={ 3 }
								/>
								<TextareaControl
									label={ __( 'Format réponse assistant (JSON)', 'g2rd' ) }
									value={ chatAssistantFormat }
									onChange={ ( value ) =
									__nextHasNoMarginBottom
onChange={ ( value ) => setAttributes( { chatAssistantFormat: value } ) }
									placeholder={ '{"role":"assistant","content":"{{RESPONSE}}"}' }
									rows={ 3 }
								/>
								<TextControl
									label={ __( 'Champ du texte de réponse', 'g2rd' ) }
									value={ chatResponseField }
									onChange={ ( value ) =
									__next40pxDefaultSize
									__nextHasNoMarginBottom
onChange={ ( value ) => setAttributes( { chatResponseField: value } ) }
									placeholder="choices[0][message][content]"
									help={ __( 'Chemin vers le texte dans la réponse JSON.', 'g2rd' ) }
								/>
							</>
						) }
					</PanelBody>
				) }

				{ /* --- Tester l'API --- */ }
				<PanelBody title={ __( "Tester l'API", 'g2rd' ) } initialOpen={ false }>
					<p className="g2rd-api-panel-hint">
						{ __( 'Testez sans espaces réservés dynamiques.', 'g2rd' ) }
					</p>
					<Button
						variant="primary"
						onClick={ handleTestApi }
						disabled={ ! apiUrl || isTesting }
					>
						{ isTesting
							? <>
								<Spinner />
								{ __( 'Récupération…', 'g2rd' ) }
							</>
							: __( "Tester l'API", 'g2rd' )
						}
					</Button>
					{ testError && (
						<Notice status="error" isDismissible={ false } className="g2rd-api-test-notice">
							{ testError }
						</Notice>
					) }
					{ testResult && (
						<div className="g2rd-api-test-result">
							<p className="g2rd-api-test-label">
								{ __( 'Réponse :', 'g2rd' ) }
							</p>
							<pre className="g2rd-api-test-pre">{ testResult }</pre>
						</div>
					) }
				</PanelBody>

			</InspectorControls>

			{ /* --- Zone d'édition dans le canvas --- */ }
			<div { ...blockProps }>
				<div className="g2rd-block-api-header">
					<span className="g2rd-block-api-badge">
						{ connectorType === 'server'
							? __( 'API Serveur', 'g2rd' )
							: __( 'API Client', 'g2rd' )
						}
					</span>
					{ apiUrl && (
						<span className="g2rd-block-api-url">{ apiUrl }</span>
					) }
				</div>
				<div className="g2rd-block-api-template-wrap">
					<p className="g2rd-block-api-template-label">
						{ __( "Template d'élément répété", 'g2rd' ) }
					</p>
					<InnerBlocks
						template={ [
							[ 'core/paragraph', { placeholder: __( "Texte ou titre de l'élément\u2026", 'g2rd' ) } ],
						] }
					/>
				</div>
			</div>
		</>
	);
}
