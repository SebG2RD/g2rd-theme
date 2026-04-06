import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';

export default function Save( { attributes } ) {
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

	// La configuration est sérialisée dans data-config pour être lue par view.js.
	//
	// SÉCURITÉ : en mode serveur (proxy WP), les credentials sensibles
	// (apiHeaders, apiBody) ne sont PAS inclus ici — ils transitent uniquement
	// via wp_localize_script (class-api-connector.php) et restent invisibles
	// dans le source HTML public.
	// En mode client, les headers/body sont exposés par conception (API publiques).
	const isServer = connectorType === 'server';

	const config = {
		connectorType,
		apiUrl,
		apiMethod,
		// Credentials omis du HTML en mode serveur.
		apiHeaders: isServer ? [] : apiHeaders,
		apiBody:    isServer ? '' : apiBody,
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
		// Formats de chat omis du HTML en mode serveur.
		chatUserFormat:      isServer ? '' : chatUserFormat,
		chatAssistantFormat: isServer ? '' : chatAssistantFormat,
		chatResponseField,
		resultMappings,
		resultsContainerClass,
		templateContainerClass,
	};

	const blockProps = useBlockProps.save( {
		className: 'g2rd-block-api',
		'data-block-id': blockId,
		'data-config': JSON.stringify( config ),
	} );

	return (
		<div { ...blockProps }>
			{ /*
			  * Template masqué : contient le HTML de l'InnerBlock.
			  * Le script view.js clone ce nœud pour chaque élément retourné par l'API.
			  */ }
			<div
				className={ 'g2rd-api-template' + ( templateContainerClass ? ' ' + templateContainerClass : '' ) }
				aria-hidden="true"
			>
				<InnerBlocks.Content />
			</div>

			{ /* Conteneur vide où les résultats clonés seront injectés. */ }
			<div
				className={ 'g2rd-api-results' + ( resultsContainerClass ? ' ' + resultsContainerClass : '' ) }
				role="region"
				aria-live="polite"
			/>
		</div>
	);
}
