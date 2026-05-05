import { useCallback, useEffect, useReducer, useRef } from '@wordpress/element';
import { TextControl, Button, Spinner, __experimentalConfirmDialog as ConfirmDialog } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

const { licenseRestUrl, nonce, licenseData: initialData } = window.G2RDOptionsData || {};

const STATUS_CONFIG = {
	active:   { label: 'Active',   color: '#00a32a', icon: 'dashicons-yes-alt' },
	expired:  { label: 'Expirée',  color: '#d63638', icon: 'dashicons-clock' },
	invalid:  { label: 'Invalide', color: '#d63638', icon: 'dashicons-dismiss' },
	inactive: { label: 'Inactive', color: '#787c82', icon: 'dashicons-warning' },
};

const initialState = {
	status:      initialData?.status     || 'inactive',
	maskedKey:   initialData?.masked_key || '',
	licenseInfo: initialData?.data       || {},
	domain:      initialData?.domain     || '',
	licenseKey:  '',
	isLoading:   false,
	notice:      null,
	confirmOpen: false,
};

function reducer( state, action ) {
	switch ( action.type ) {
		case 'KEY_CHANGE':
			return { ...state, licenseKey: action.payload };
		case 'LOADING':
			return { ...state, isLoading: true };
		case 'ACTIVATE_SUCCESS':
			return {
				...state,
				status:      action.payload.status     || 'inactive',
				maskedKey:   action.payload.masked_key || '',
				licenseInfo: action.payload.data       || {},
				domain:      action.payload.domain     || '',
				licenseKey:  '',
				isLoading:   false,
			};
		case 'DEACTIVATE_SUCCESS':
			return {
				...state,
				status:      'inactive',
				maskedKey:   '',
				licenseInfo: {},
				domain:      '',
				isLoading:   false,
			};
		case 'REQUEST_DONE':
			return { ...state, isLoading: false };
		case 'CONFIRM_OPEN':
			return { ...state, confirmOpen: true };
		case 'CONFIRM_CLOSE':
			return { ...state, confirmOpen: false };
		case 'NOTICE_SHOW':
			return { ...state, notice: action.payload };
		case 'NOTICE_CLEAR':
			return { ...state, notice: null };
		default:
			return state;
	}
}

export function TabLicence() {
	const [ state, dispatch ] = useReducer( reducer, initialState );
	const { status, maskedKey, licenseInfo, domain, licenseKey, isLoading, notice, confirmOpen } = state;

	const timerRef = useRef( null );

	useEffect( () => () => clearTimeout( timerRef.current ), [] );

	const isActive = status === 'active';
	const badge    = STATUS_CONFIG[ status ] || STATUS_CONFIG.inactive;

	const showNotice = useCallback( ( type, message ) => {
		dispatch( { type: 'NOTICE_SHOW', payload: { type, message } } );
		clearTimeout( timerRef.current );
		timerRef.current = setTimeout( () => dispatch( { type: 'NOTICE_CLEAR' } ), 5000 );
	}, [] );

	const handleActivate = useCallback( async () => {
		if ( ! licenseKey.trim() ) {
			showNotice( 'error', 'Veuillez saisir une clé de licence.' );
			return;
		}
		dispatch( { type: 'LOADING' } );
		try {
			const res = await apiFetch( {
				url: licenseRestUrl + '/activate',
				method: 'POST',
				headers: { 'X-WP-Nonce': nonce },
				data: { license_key: licenseKey.trim() },
			} );
			if ( res?.success ) {
				dispatch( { type: 'ACTIVATE_SUCCESS', payload: res.license || {} } );
				showNotice( 'success', res.message );
			} else {
				dispatch( { type: 'REQUEST_DONE' } );
				showNotice( 'error', res?.message || 'Activation échouée.' );
			}
		} catch ( err ) {
			dispatch( { type: 'REQUEST_DONE' } );
			showNotice( 'error', err?.message || 'Erreur réseau.' );
		}
	}, [ licenseKey, showNotice ] );

	const handleDeactivate = useCallback( async () => {
		dispatch( { type: 'LOADING' } );
		dispatch( { type: 'CONFIRM_CLOSE' } );
		try {
			const res = await apiFetch( {
				url: licenseRestUrl + '/deactivate',
				method: 'POST',
				headers: { 'X-WP-Nonce': nonce },
			} );
			if ( res?.success ) {
				dispatch( { type: 'DEACTIVATE_SUCCESS' } );
				showNotice( 'info', res.message );
			} else {
				dispatch( { type: 'REQUEST_DONE' } );
			}
		} catch ( err ) {
			dispatch( { type: 'REQUEST_DONE' } );
			showNotice( 'error', err?.message || 'Erreur réseau.' );
		}
	}, [ showNotice ] );

	return (
		<div className="g2rd-tab-content">

			<ConfirmDialog
				isOpen={ confirmOpen }
				onConfirm={ handleDeactivate }
				onCancel={ () => dispatch( { type: 'CONFIRM_CLOSE' } ) }
			>
				Désactiver la licence sur ce domaine ? Cela libérera une activation utilisable sur un autre site.
			</ConfirmDialog>

			<section className="g2rd-section">
				<h2 className="g2rd-section__title">
					<span className="dashicons dashicons-admin-network"></span>
					Licence G2RD FSE
				</h2>
				<p className="g2rd-section__desc">
					Activez votre licence pour débloquer les blocs Gutenberg personnalisés G2RD.{ ' ' }
					<a href="https://g2rd.fr/boutique" target="_blank" rel="noopener noreferrer">
						Obtenir une licence →
					</a>
				</p>
				<p style={ { color: '#646970', marginTop: -6, marginBottom: 16, fontSize: 12 } }>
					Les nouvelles licences sont gérées via FluentCart. SureCart reste supporté uniquement pour la compatibilité historique.
				</p>

				{ notice && (
					<div
						className={ `notice notice-${ notice.type } is-dismissible` }
						style={ { margin: '0 0 16px', padding: '8px 12px' } }
					>
						<p>{ notice.message }</p>
					</div>
				) }

				<div className={ `g2rd-license-card ${ isActive ? 'is-active' : 'is-inactive' }` }>

					<div className="g2rd-license-status">
						<span
							className={ `dashicons ${ badge.icon }` }
							style={ { color: badge.color, fontSize: 22, width: 22, height: 22 } }
						></span>
						<strong style={ { color: badge.color } }>{ badge.label }</strong>
						{ isActive && maskedKey && (
							<code className="g2rd-license-key">{ maskedKey }</code>
						) }
					</div>

					{ isActive && (
						<table className="widefat g2rd-license-details" style={ { marginBottom: 16 } }>
							<tbody>
								{ licenseInfo.expires_at && (
									<tr>
										<td style={ { fontWeight: 600, width: 180 } }>Expiration</td>
										<td>{ new Date( licenseInfo.expires_at ).toLocaleDateString( 'fr-FR' ) }</td>
									</tr>
								) }
								{ licenseInfo.activations_left !== undefined && (
									<tr>
										<td style={ { fontWeight: 600 } }>Activations restantes</td>
										<td>{ licenseInfo.activations_left }</td>
									</tr>
								) }
								{ domain && (
									<tr>
										<td style={ { fontWeight: 600 } }>Domaine activé</td>
										<td>{ domain }</td>
									</tr>
								) }
							</tbody>
						</table>
					) }

					{ isActive ? (
						<div>
							<Button
								variant="secondary"
								isDestructive
								onClick={ () => dispatch( { type: 'CONFIRM_OPEN' } ) }
								disabled={ isLoading }
							>
								{ isLoading ? (
									<Spinner />
								) : (
									<>
										<span
											className="dashicons dashicons-no"
											style={ { verticalAlign: 'middle', marginTop: -2 } }
										></span>
										{ ' ' }Désactiver la licence
									</>
								) }
							</Button>
							<span style={ { color: '#787c82', fontSize: 12, marginLeft: 8 } }>
								Cela libérera une activation utilisable sur un autre site.
							</span>
						</div>
					) : (
						<div className="g2rd-license-form">
							<TextControl
								label="Clé de licence"
								value={ licenseKey }
								onChange={ ( val ) => dispatch( { type: 'KEY_CHANGE', payload: val } ) }
								placeholder="XXXX-XXXX-XXXX-XXXX-XXXX"
								autoComplete="off"
								onKeyDown={ ( e ) => e.key === 'Enter' && handleActivate() }
							/>
							<Button
								variant="primary"
								onClick={ handleActivate }
								disabled={ isLoading || ! licenseKey.trim() }
							>
								{ isLoading ? <Spinner /> : 'Activer la licence' }
							</Button>
							<p style={ { color: '#787c82', fontSize: 12, marginTop: 8 } }>
								Vous trouverez votre clé dans votre espace client G2RD (FluentCart) sur g2rd.fr.
							</p>
						</div>
					) }

				</div>
			</section>

		</div>
	);
}
