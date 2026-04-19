import { useState, useCallback, useRef, useEffect, useMemo } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const { restUrl, nonce, settings: initialSettings } = window.G2RDOptionsData || {};

export function useSettings() {
	const [ settings, setSettings ] = useState( initialSettings || {} );
	const [ saved, setSaved ]       = useState( initialSettings || {} );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ notice, setNotice ]     = useState( null );
	const timerRef = useRef( null );

	useEffect( () => {
		return () => {
			if ( timerRef.current ) {
				clearTimeout( timerRef.current );
			}
		};
	}, [] );

	const isDirty = useMemo(
		() => JSON.stringify( settings ) !== JSON.stringify( saved ),
		[ settings, saved ]
	);

	const update = useCallback( ( path, value ) => {
		setSettings( ( prev ) => {
			const next = { ...prev };
			if ( path.length === 1 ) {
				next[ path[ 0 ] ] = value;
			} else if ( path.length === 2 ) {
				next[ path[ 0 ] ] = { ...( next[ path[ 0 ] ] || {} ), [ path[ 1 ] ]: value };
			} else if ( path.length === 3 ) {
				next[ path[ 0 ] ] = {
					...( next[ path[ 0 ] ] || {} ),
					[ path[ 1 ] ]: {
						...( ( next[ path[ 0 ] ] || {} )[ path[ 1 ] ] || {} ),
						[ path[ 2 ] ]: value,
					},
				};
			}
			return next;
		} );
	}, [] );

	const showNotice = useCallback( ( type, message ) => {
		setNotice( { type, message } );
		if ( timerRef.current ) {
			clearTimeout( timerRef.current );
		}
		timerRef.current = setTimeout( () => setNotice( null ), 4000 );
	}, [] );

	const save = useCallback( async () => {
		setIsSaving( true );
		try {
			const response = await apiFetch( {
				url: restUrl,
				method: 'POST',
				headers: { 'X-WP-Nonce': nonce },
				data: { settings },
			} );
			if ( response?.success ) {
				setSaved( response.settings || settings );
				setSettings( response.settings || settings );
				showNotice( 'success', 'Options enregistrées avec succès.' );
			} else {
				showNotice( 'error', 'Erreur lors de la sauvegarde.' );
			}
		} catch ( err ) {
			showNotice( 'error', err?.message || 'Erreur lors de la sauvegarde.' );
		} finally {
			setIsSaving( false );
		}
	}, [ settings, showNotice ] );

	const reset = useCallback( () => {
		setSettings( saved );
	}, [ saved ] );

	const clearNotice = useCallback( () => setNotice( null ), [] );

	return { settings, update, isDirty, isSaving, save, reset, notice, clearNotice };
}
