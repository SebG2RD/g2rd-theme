import { useState, useEffect, useCallback } from '@wordpress/element';
import { Button, Spinner } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

const { licenseAdminUrl, nonce } = window.G2RDOptionsData || {};

function formatDate( iso ) {
	if ( ! iso ) return '—';
	return new Date( iso ).toLocaleDateString( 'fr-FR', {
		day: '2-digit', month: '2-digit', year: 'numeric',
	} );
}

export function TabLicenceAdmin() {
	const [ licenses,       setLicenses       ] = useState( [] );
	const [ isLoading,      setIsLoading      ] = useState( true );
	const [ isCreating,     setIsCreating     ] = useState( false );
	const [ notice,         setNotice         ] = useState( null );
	const [ maxActivations, setMaxActivations ] = useState( 1 );
	const [ customKey,      setCustomKey      ] = useState( '' );
	const [ expiresAt,      setExpiresAt      ] = useState( '' );

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
				<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 8 } }>
					<h2 className="g2rd-section__title" style={ { margin: 0 } }>
						<span className="dashicons dashicons-admin-network"></span>
						Gestion des licences
					</h2>
					<Button
						variant="secondary"
						isSmall
						onClick={ loadLicenses }
						disabled={ isLoading }
						title="Rafraîchir la liste"
					>
						<span
							className="dashicons dashicons-update"
							style={ {
								fontSize: 16,
								width: 16,
								height: 16,
								verticalAlign: 'middle',
								marginRight: 4,
								animation: isLoading ? 'g2rd-spin 1s linear infinite' : 'none',
							} }
						></span>
						Rafraîchir
					</Button>
				</div>
				<p className="g2rd-section__desc">
					Créez et attribuez les clés de licence à vos clients. Chaque clé est vérifiée
					lorsque le client l'active dans <strong>Options G2RD → Licence</strong> sur son site.
				</p>
				<p style={ { color: '#646970', marginTop: -6, marginBottom: 16, fontSize: 12 } }>
					Référence produit : nouvelles licences via FluentCart. SureCart reste limité à la compatibilité historique.
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
				<div style={ { background: '#f9f9f9', border: '1px solid #ddd', borderRadius: 6, padding: '20px 24px', marginBottom: 24 } }>
					<h3 style={ { marginTop: 0, marginBottom: 20, fontSize: 14, fontWeight: 600 } }>Créer une nouvelle licence</h3>

					<div style={ {
						display: 'grid',
						gridTemplateColumns: 'minmax(200px, 2fr) 120px minmax(150px, 1fr) auto',
						gap: 16,
						alignItems: 'end',
					} }>
						<div>
							<label style={ { display: 'block', fontWeight: 600, marginBottom: 6, fontSize: 13 } }>
								Clé personnalisée
							</label>
							<input
								type="text"
								value={ customKey }
								onChange={ ( e ) => setCustomKey( e.target.value ) }
								placeholder="G2RD-XXXXX-XXXXX-XXXXX-XXXXX"
								className="regular-text"
								style={ { width: '100%', boxSizing: 'border-box' } }
							/>
							<p style={ { margin: '4px 0 0', fontSize: 12, color: '#787c82' } }>
								Laisser vide pour générer automatiquement
							</p>
						</div>

						<div>
							<label style={ { display: 'block', fontWeight: 600, marginBottom: 6, fontSize: 13 } }>
								Activations max
							</label>
							<input
								type="number"
								min={ 1 }
								max={ 100 }
								value={ maxActivations }
								onChange={ ( e ) => setMaxActivations( Math.max( 1, parseInt( e.target.value, 10 ) || 1 ) ) }
								className="regular-text"
								style={ { width: '100%', boxSizing: 'border-box' } }
							/>
							<p style={ { margin: '4px 0 0', fontSize: 12, color: 'transparent' } }>
								&nbsp;
							</p>
						</div>

						<div>
							<label style={ { display: 'block', fontWeight: 600, marginBottom: 6, fontSize: 13 } }>
								Expiration{ ' ' }
								<span style={ { fontWeight: 400, color: '#787c82' } }>(optionnel)</span>
							</label>
							<input
								type="date"
								value={ expiresAt }
								onChange={ ( e ) => setExpiresAt( e.target.value ) }
								className="regular-text"
								style={ { width: '100%', boxSizing: 'border-box' } }
							/>
							<p style={ { margin: '4px 0 0', fontSize: 12, color: 'transparent' } }>
								&nbsp;
							</p>
						</div>

						<div style={ { paddingBottom: 22 } }>
							<Button
								variant="primary"
								onClick={ handleCreate }
								disabled={ isCreating }
								style={ { whiteSpace: 'nowrap' } }
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
								<th style={ { width: '30%' } }>Clé</th>
								<th>Statut</th>
								<th>Activations</th>
								<th>Créée le</th>
								<th>Expiration</th>
								<th style={ { width: '22%' } }>Domaines actifs</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							{ licenses.map( ( { key, status, max_activations, activations_used, expires_at, created_at, activated_domains, source } ) => (
								<tr key={ key }>
									<td>
										<div style={ { display: 'flex', alignItems: 'center', gap: 6, flexWrap: 'wrap' } }>
											<code style={ { fontSize: 12, wordBreak: 'break-all' } }>{ key }</code>
											<button
												type="button"
												className="button-link"
												onClick={ () => copyKey( key ) }
												title="Copier la clé"
												style={ { cursor: 'pointer', verticalAlign: 'middle', flexShrink: 0 } }
											>
												<span className="dashicons dashicons-clipboard" style={ { fontSize: 16, width: 16, height: 16 } }></span>
											</button>
										</div>
										{ source === 'fluentcart' && (
											<span style={ {
												background: '#7f54b3',
												borderRadius: 3,
												color: '#fff',
												fontSize: 10,
												fontWeight: 700,
												padding: '1px 6px',
												display: 'inline-block',
												marginTop: 4,
												letterSpacing: '0.03em',
											} }>
												FluentCart
											</span>
										) }
									</td>
									<td>
										<span style={ { color: status === 'active' ? '#00a32a' : '#d63638', fontWeight: 600 } }>
											{ status === 'active' ? 'Active' : 'Inactive' }
										</span>
									</td>
									<td>
										<span style={ {
											fontWeight: 600,
											color: activations_used >= max_activations ? '#d63638' : 'inherit',
										} }>
											{ activations_used }
										</span>
										{ ' / ' }{ max_activations }
									</td>
									<td style={ { fontSize: 12, color: '#787c82' } }>
										{ formatDate( created_at ) }
									</td>
									<td>
										{ expires_at
											? new Date( expires_at ).toLocaleDateString( 'fr-FR' )
											: <span style={ { color: '#787c82' } }>—</span>
										}
									</td>
									<td style={ { fontSize: 11 } }>
										{ activated_domains.length > 0
											? activated_domains.map( ( d ) => (
												<div key={ d.url } style={ { marginBottom: 4 } }>
													<a
														href={ d.url }
														target="_blank"
														rel="noreferrer"
														style={ { display: 'block', wordBreak: 'break-all' } }
													>
														{ d.url }
													</a>
													{ d.activated_at && (
														<span style={ { color: '#9ca3af', display: 'block' } }>
															{ formatDate( d.activated_at ) }
														</span>
													) }
												</div>
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

			<style>{ `
				@keyframes g2rd-spin {
					from { transform: rotate(0deg); }
					to   { transform: rotate(360deg); }
				}
			` }</style>

		</div>
	);
}
