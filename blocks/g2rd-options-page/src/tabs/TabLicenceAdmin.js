import { useState, useEffect, useCallback } from '@wordpress/element';
import { Button, Spinner, TextControl } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

const { licenseAdminUrl, nonce } = window.G2RDOptionsData || {};

export function TabLicenceAdmin() {
	const [ licenses,      setLicenses      ] = useState( [] );
	const [ isLoading,     setIsLoading     ] = useState( true );
	const [ isCreating,    setIsCreating    ] = useState( false );
	const [ notice,        setNotice        ] = useState( null );
	const [ maxActivations, setMaxActivations ] = useState( 1 );
	const [ customKey,     setCustomKey     ] = useState( '' );
	const [ expiresAt,     setExpiresAt     ] = useState( '' );

	const showNotice = useCallback( ( type, message ) => {
		setNotice( { type, message } );
		setTimeout( () => setNotice( null ), 6000 );
	}, [] );

	const loadLicenses = useCallback( async () => {
		setIsLoading( true );
		try {
			const res = await apiFetch( {
				url: licenseAdminUrl,
				headers: { 'X-WP-Nonce': nonce },
			} );
			setLicenses( res?.licenses || [] );
		} catch ( err ) {
			showNotice( 'error', err?.message || 'Erreur lors du chargement des licences.' );
		} finally {
			setIsLoading( false );
		}
	}, [ showNotice ] );

	useEffect( () => {
		loadLicenses();
	}, [ loadLicenses ] );

	const handleCreate = useCallback( async () => {
		setIsCreating( true );
		try {
			const body = { max_activations: maxActivations };
			if ( customKey.trim() ) {
				body.license_key = customKey.trim();
			}
			if ( expiresAt ) {
				body.expires_at = expiresAt;
			}

			const res = await apiFetch( {
				url: licenseAdminUrl,
				method: 'POST',
				headers: { 'X-WP-Nonce': nonce },
				data: body,
			} );

			if ( res?.success ) {
				showNotice( 'success', `Clé créée : ${ res.license_key }` );
				setCustomKey( '' );
				setExpiresAt( '' );
				setMaxActivations( 1 );
				await loadLicenses();
			} else {
				showNotice( 'error', res?.message || 'Erreur lors de la création.' );
			}
		} catch ( err ) {
			showNotice( 'error', err?.message || 'Erreur réseau.' );
		} finally {
			setIsCreating( false );
		}
	}, [ maxActivations, customKey, expiresAt, loadLicenses, showNotice ] );

	const handleDelete = useCallback( async ( key ) => {
		// eslint-disable-next-line no-alert
		if ( ! window.confirm( `Supprimer la clé ${ key } et toutes ses activations ?` ) ) {
			return;
		}
		try {
			await apiFetch( {
				url: `${ licenseAdminUrl }/${ encodeURIComponent( key ) }`,
				method: 'DELETE',
				headers: { 'X-WP-Nonce': nonce },
			} );
			showNotice( 'success', 'Clé supprimée.' );
			setLicenses( ( prev ) => prev.filter( ( l ) => l.key !== key ) );
		} catch ( err ) {
			showNotice( 'error', err?.message || 'Erreur lors de la suppression.' );
		}
	}, [ showNotice ] );

	const copyKey = useCallback( ( key ) => {
		navigator.clipboard.writeText( key );
		showNotice( 'success', 'Clé copiée dans le presse-papier.' );
	}, [ showNotice ] );

	return (
		<div className="g2rd-tab-content">

			<section className="g2rd-section">
				<h2 className="g2rd-section__title">
					<span className="dashicons dashicons-admin-network"></span>
					Gestion des licences
				</h2>
				<p className="g2rd-section__desc">
					Créez et attribuez les clés de licence à vos clients. Chaque clé est vérifiée
					lorsque le client l'active dans <strong>Options G2RD → Licence</strong> sur son site.
				</p>

				{ notice && (
					<div
						className={ `notice notice-${ notice.type } is-dismissible` }
						style={ { margin: '0 0 16px', padding: '8px 12px' } }
					>
						<p>{ notice.message }</p>
					</div>
				) }

				{ /* ── Formulaire de création ── */ }
				<div style={ { background: '#f9f9f9', border: '1px solid #ddd', borderRadius: 6, padding: '16px 20px', marginBottom: 24 } }>
					<h3 style={ { marginTop: 0, marginBottom: 16 } }>Créer une nouvelle licence</h3>

					<div style={ { display: 'flex', gap: 16, flexWrap: 'wrap', alignItems: 'flex-end' } }>
						<div style={ { flex: '2 1 220px' } }>
							<TextControl
								label="Clé personnalisée"
								help="Laisser vide pour générer automatiquement (G2RD-XXXXX-…)"
								value={ customKey }
								onChange={ setCustomKey }
								placeholder="G2RD-XXXXX-XXXXX-XXXXX-XXXXX"
							/>
						</div>

						<div style={ { flex: '0 1 120px' } }>
							<label style={ { display: 'block', fontWeight: 600, marginBottom: 4 } }>
								Activations max
							</label>
							<input
								type="number"
								min={ 1 }
								max={ 100 }
								value={ maxActivations }
								onChange={ ( e ) => setMaxActivations( Math.max( 1, parseInt( e.target.value, 10 ) || 1 ) ) }
								className="regular-text"
								style={ { width: '100%' } }
							/>
						</div>

						<div style={ { flex: '1 1 160px' } }>
							<label style={ { display: 'block', fontWeight: 600, marginBottom: 4 } }>
								Expiration (optionnel)
							</label>
							<input
								type="date"
								value={ expiresAt }
								onChange={ ( e ) => setExpiresAt( e.target.value ) }
								className="regular-text"
								style={ { width: '100%' } }
							/>
						</div>

						<div style={ { flex: '0 0 auto', paddingBottom: 2 } }>
							<Button
								variant="primary"
								onClick={ handleCreate }
								disabled={ isCreating }
							>
								{ isCreating ? (
									<Spinner />
								) : (
									<>
										<span
											className="dashicons dashicons-plus-alt"
											style={ { verticalAlign: 'middle', marginTop: -2 } }
										></span>
										{ ' ' }Créer
									</>
								) }
							</Button>
						</div>
					</div>
				</div>

				{ /* ── Tableau des clés ── */ }
				{ isLoading ? (
					<div style={ { textAlign: 'center', padding: 32 } }><Spinner /></div>
				) : licenses.length === 0 ? (
					<p style={ { color: '#787c82', fontStyle: 'italic' } }>
						Aucune clé de licence enregistrée.
					</p>
				) : (
					<table className="widefat striped">
						<thead>
							<tr>
								<th style={ { width: '35%' } }>Clé</th>
								<th>Statut</th>
								<th>Activations</th>
								<th>Expiration</th>
								<th>Domaines actifs</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							{ licenses.map( ( { key, status, max_activations, activations_used, expires_at, activated_domains } ) => (
								<tr key={ key }>
									<td>
										<code style={ { fontSize: 12, wordBreak: 'break-all' } }>{ key }</code>
										<button
											type="button"
											className="button-link"
											onClick={ () => copyKey( key ) }
											title="Copier la clé"
											style={ { marginLeft: 6, cursor: 'pointer', verticalAlign: 'middle' } }
										>
											<span className="dashicons dashicons-clipboard" style={ { fontSize: 16, width: 16, height: 16 } }></span>
										</button>
									</td>
									<td>
										<span style={ { color: status === 'active' ? '#00a32a' : '#d63638', fontWeight: 600 } }>
											{ status === 'active' ? 'Active' : 'Inactive' }
										</span>
									</td>
									<td>
										{ activations_used } / { max_activations }
									</td>
									<td>
										{ expires_at
											? new Date( expires_at ).toLocaleDateString( 'fr-FR' )
											: <span style={ { color: '#787c82' } }>—</span>
										}
									</td>
									<td style={ { fontSize: 12 } }>
										{ activated_domains.length > 0
											? activated_domains.map( ( d ) => (
												<span key={ d } style={ { display: 'block' } }>{ d }</span>
											) )
											: <em style={ { color: '#787c82' } }>Aucun</em>
										}
									</td>
									<td>
										<Button
											variant="secondary"
											isDestructive
											isSmall
											onClick={ () => handleDelete( key ) }
										>
											Supprimer
										</Button>
									</td>
								</tr>
							) ) }
						</tbody>
					</table>
				) }
			</section>

		</div>
	);
}
