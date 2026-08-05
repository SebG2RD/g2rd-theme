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
				<div className="g2rd-section__head">
					<h2 className="g2rd-section__title">
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
							className={ `dashicons dashicons-update g2rd-ico${ isLoading ? ' g2rd-ico--spin' : '' }` }
						></span>
						Rafraîchir
					</Button>
				</div>
				<p className="g2rd-section__desc">
					Créez et attribuez les clés de licence à vos clients. Chaque clé est vérifiée
					lorsque le client l'active dans <strong>Options G2RD → Licence</strong> sur son site.
				</p>
				<p className="g2rd-muted g2rd-section__footnote">
					Référence produit : licences gérées via FluentCart.
				</p>

				{ notice && (
					<div
						className={ `notice notice-${ notice.type } is-dismissible g2rd-notice-inline` }
					>
						<p>{ notice.message }</p>
					</div>
				) }

				{ /* ── Formulaire de création ── */ }
				<div className="g2rd-panel">
					<h3 className="g2rd-panel__title">Créer une nouvelle licence</h3>

					<div className="g2rd-form-grid g2rd-form-grid--licences">
						<div>
							<label className="g2rd-field__label" htmlFor="g2rd-licence-key">
								Clé personnalisée
							</label>
							<input
								id="g2rd-licence-key"
								type="text"
								value={ customKey }
								onChange={ ( e ) => setCustomKey( e.target.value ) }
								placeholder="G2RD-XXXXX-XXXXX-XXXXX-XXXXX"
								className="regular-text g2rd-field__input"
							/>
							<p className="g2rd-field__hint">
								Laisser vide pour générer automatiquement
							</p>
						</div>

						<div>
							<label className="g2rd-field__label" htmlFor="g2rd-licence-max">
								Activations max
							</label>
							<input
								id="g2rd-licence-max"
								type="number"
								min={ 1 }
								max={ 100 }
								value={ maxActivations }
								onChange={ ( e ) => setMaxActivations( Math.max( 1, parseInt( e.target.value, 10 ) || 1 ) ) }
								className="regular-text g2rd-field__input"
							/>
							<p className="g2rd-field__hint g2rd-field__hint--ghost" aria-hidden="true">
								&nbsp;
							</p>
						</div>

						<div>
							<label className="g2rd-field__label" htmlFor="g2rd-licence-expiry">
								Expiration{ ' ' }
								<span className="g2rd-field__optional">(optionnel)</span>
							</label>
							<input
								id="g2rd-licence-expiry"
								type="date"
								value={ expiresAt }
								onChange={ ( e ) => setExpiresAt( e.target.value ) }
								className="regular-text g2rd-field__input"
							/>
							<p className="g2rd-field__hint g2rd-field__hint--ghost" aria-hidden="true">
								&nbsp;
							</p>
						</div>

						<div className="g2rd-field__action g2rd-field__action--offset">
							<Button
								variant="primary"
								onClick={ handleCreate }
								disabled={ isCreating }
								className="g2rd-nowrap"
							>
								{ isCreating ? (
									<Spinner />
								) : (
									<>
										<span
											className="dashicons dashicons-plus-alt g2rd-ico g2rd-ico--btn"
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
					<div className="g2rd-loading"><Spinner /></div>
				) : licenses.length === 0 ? (
					<p className="g2rd-muted g2rd-muted--italic">
						Aucune clé de licence enregistrée.
					</p>
				) : (
					<table className="widefat striped g2rd-table">
						<thead>
							<tr>
								<th className="col-key">Clé</th>
								<th>Statut</th>
								<th>Activations</th>
								<th>Créée le</th>
								<th>Expiration</th>
								<th className="col-domains">Domaines actifs</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							{ licenses.map( ( { key, status, max_activations, activations_used, expires_at, created_at, activated_domains, source } ) => (
								<tr key={ key }>
									<td>
										<div className="g2rd-row g2rd-row--tight g2rd-row--wrap">
											<code className="g2rd-key">{ key }</code>
											<button
												type="button"
												className="button-link g2rd-copy-btn"
												onClick={ () => copyKey( key ) }
												title="Copier la clé"
											>
												<span className="dashicons dashicons-clipboard g2rd-ico g2rd-ico--bare"></span>
											</button>
										</div>
										{ source === 'fluentcart' && (
											<span className="g2rd-tag g2rd-tag--vendor">
												FluentCart
											</span>
										) }
									</td>
									<td>
										<span className={ status === 'active' ? 'g2rd-state--ok' : 'g2rd-state--danger' }>
											{ status === 'active' ? 'Active' : 'Inactive' }
										</span>
									</td>
									<td>
										<span className={ activations_used >= max_activations ? 'g2rd-state--danger' : 'g2rd-strong' }>
											{ activations_used }
										</span>
										{ ' / ' }{ max_activations }
									</td>
									<td className="is-date">
										{ formatDate( created_at ) }
									</td>
									<td>
										{ expires_at
											? new Date( expires_at ).toLocaleDateString( 'fr-FR' )
											: <span className="g2rd-dash">—</span>
										}
									</td>
									<td className="g2rd-domains">
										{ activated_domains.length > 0
											? activated_domains.map( ( d ) => (
												<div key={ d.url } className="g2rd-domains__item">
													<a
														href={ d.url }
														target="_blank"
														rel="noreferrer"
														className="g2rd-domains__url"
													>
														{ d.url }
													</a>
													{ d.activated_at && (
														<span className="g2rd-domains__date">
															{ formatDate( d.activated_at ) }
														</span>
													) }
												</div>
											) )
											: <em className="g2rd-dash">Aucun</em>
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
