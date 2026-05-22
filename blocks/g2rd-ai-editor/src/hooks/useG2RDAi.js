/**
 * Hook useG2RDAi — Gestion des appels IA dans l'éditeur Gutenberg
 *
 * Encapsule : apiFetch, états loading/error/result, retry, annulation.
 * Compatible React best practices : useCallback stable, pas de closure stale.
 */

import { useState, useCallback, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * @typedef {Object} UseG2RDAiReturn
 * @property {Function}         generate  Lance une génération IA.
 * @property {boolean}          loading   Vrai pendant l'appel.
 * @property {string|null}      result    Résultat texte ou JSON stringifié.
 * @property {any}              parsed    Résultat parsé (objet/array ou string).
 * @property {string|null}      error     Message d'erreur si échec.
 * @property {Function}         reset     Remet l'état initial.
 */

/**
 * Hook principal d'accès au module IA G2RD.
 *
 * @returns {UseG2RDAiReturn}
 */
export function useG2RDAi() {
	const [ loading, setLoading ] = useState( false );
	const [ result, setResult ]   = useState( null );
	const [ parsed, setParsed ]   = useState( null );
	const [ error, setError ]     = useState( null );

	// Ref pour annuler un appel en cours si le composant est démonté.
	const abortRef = useRef( null );

	const config = window.g2rdAiConfig ?? {};

	/**
	 * Réinitialise l'état complet.
	 */
	const reset = useCallback( () => {
		setLoading( false );
		setResult( null );
		setParsed( null );
		setError( null );
	}, [] );

	/**
	 * Lance un appel IA vers l'endpoint REST.
	 *
	 * @param {Object} options
	 * @param {string} options.endpoint  Route REST relative (ex. 'block-action').
	 * @param {Object} options.payload   Corps de la requête.
	 */
	const generate = useCallback( async ( { endpoint, payload } ) => {
		if ( ! config.enabled || ! config.userCan ) {
			setError( config.i18n?.error ?? 'Module IA non disponible.' );
			return;
		}

		if ( ! config.connectorReady ) {
			setError( config.i18n?.noConnector ?? 'Connecteur IA non disponible.' );
			return;
		}

		// Annule l'appel précédent si encore en cours.
		if ( abortRef.current ) {
			abortRef.current.abort();
		}

		const controller = new AbortController();
		abortRef.current = controller;

		setLoading( true );
		setResult( null );
		setParsed( null );
		setError( null );

		try {
			const response = await apiFetch( {
				path:   ( config.restPath ?? '/g2rd/v1/ai/' ) + endpoint,
				method: 'POST',
				data:   payload,
				signal: controller.signal,
			} );

			if ( response?.result !== undefined ) {
				const raw = response.result;

				// Normalise : texte brut ou objet JSON.
				const resultStr = typeof raw === 'string'
					? raw
					: JSON.stringify( raw, null, 2 );

				setResult( resultStr );
				setParsed( raw );
			} else {
				setError( config.i18n?.error ?? 'Réponse inattendue du serveur.' );
			}
		} catch ( err ) {
			// Ignore les annulations volontaires (AbortError).
			if ( err?.name === 'AbortError' ) {
				return;
			}

			const message = err?.message
				?? err?.data?.message
				?? ( config.i18n?.error ?? 'Erreur lors de la génération.' );

			// Cas limite journalière (HTTP 429).
			if ( err?.status === 429 ) {
				setError( config.i18n?.limitReached ?? message );
			} else {
				setError( message );
			}
		} finally {
			setLoading( false );
			abortRef.current = null;
		}
	}, [ config ] ); // config est stable (window global, jamais muté).

	return { generate, loading, result, parsed, error, reset };
}
