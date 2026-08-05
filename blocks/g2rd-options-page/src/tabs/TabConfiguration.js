import { useState } from '@wordpress/element';

const { palette, googleReviewsClearUrl, nonce } = window.G2RDOptionsData || {};

const BUSINESS_TYPES = [
	{ label: 'Site vitrine',     value: 'vitrine',   icon: 'admin-home',    desc: 'Présentation de votre activité' },
	{ label: 'Génération leads', value: 'leads',     icon: 'email-alt',     desc: 'Capture et conversion de prospects' },
	{ label: 'E-commerce',       value: 'ecommerce', icon: 'cart',          desc: 'Boutique en ligne et vente' },
	{ label: 'Non défini',       value: '',          icon: 'minus',         desc: 'Aucune recommandation spécifique' },
];

const COLOR_SLOTS = [
	{ key: 'admin_bg',       label: 'Fond barre admin' },
	{ key: 'admin_text',     label: 'Texte barre admin' },
	{ key: 'btn_bg',         label: 'Fond bouton (normal)' },
	{ key: 'btn_text',       label: 'Texte bouton (normal)' },
	{ key: 'btn_bg_hover',   label: 'Fond bouton (hover)' },
	{ key: 'btn_text_hover', label: 'Texte bouton (hover)' },
];

export function TabConfiguration( { settings, update } ) {
	const businessType    = settings.businessType || '';
	const colors          = settings.colors || {};
	const apiKeySet       = !! settings.googleMapsApiKeySet;

	const [ apiKeyInput,    setApiKeyInput    ] = useState( '' );
	const [ showKey,        setShowKey        ] = useState( false );
	const [ clearingCache,  setClearingCache  ] = useState( false );
	const [ clearMsg,       setClearMsg       ] = useState( '' );
	const [ placeId,        setPlaceId        ] = useState( '' );

	return (
		<div className="g2rd-tab-content">

			<section className="g2rd-section">
				<h2 className="g2rd-section__title">
					<span className="dashicons dashicons-admin-home"></span>
					Type de site
				</h2>
				<p className="g2rd-section__desc">
					Oriente les recommandations GEO et les conseils contextuels dans l'éditeur.
				</p>
				<div className="g2rd-business-grid">
					{ BUSINESS_TYPES.map( ( { label, value, icon, desc } ) => (
						<button
							key={ value }
							type="button"
							className={ `g2rd-business-card${ businessType === value ? ' g2rd-business-card--active' : '' }` }
							onClick={ () => update( [ 'businessType' ], value ) }
						>
							<span className={ `dashicons dashicons-${ icon }` }></span>
							<strong>{ label }</strong>
							<span className="g2rd-business-card__desc">{ desc }</span>
						</button>
					) ) }
				</div>
			</section>

			<section className="g2rd-section">
				<h2 className="g2rd-section__title">
					<span className="dashicons dashicons-art"></span>
					Couleurs de l'administration
				</h2>
				<p className="g2rd-section__desc">
					Personnalise la barre admin WordPress avec les couleurs du thème.
				</p>
				<div className="g2rd-colors-grid">
					{ COLOR_SLOTS.map( ( { key, label } ) => (
						<div key={ key } className="g2rd-color-slot">
							<label className="g2rd-color-slot__label">{ label }</label>
							<div className="g2rd-color-slot__swatches">
								{ ( palette || [] ).map( ( { slug, color, name } ) => (
									<button
										key={ slug }
										type="button"
										title={ name }
										className={ `g2rd-swatch${ colors[ key ] === slug ? ' g2rd-swatch--active' : '' }` }
										style={ { background: color } }
										onClick={ () => update( [ 'colors', key ], slug ) }
									/>
								) ) }
							</div>
						</div>
					) ) }
				</div>
			</section>

			<section className="g2rd-section">
				<h2 className="g2rd-section__title">
					<span className="dashicons dashicons-google"></span>
					Intégrations — Google Maps
				</h2>
				<p className="g2rd-section__desc">
					Clé API Places (Google Maps Platform) — utilisée par le bloc Testimonial pour afficher les avis Google Business.
				</p>

				<div className="g2rd-row g2rd-key-state">
					{ apiKeySet && ! apiKeyInput && (
						<span className="g2rd-tag g2rd-tag--success">
							✓ Clé configurée
						</span>
					) }
					{ ! apiKeySet && ! apiKeyInput && (
						<span className="g2rd-tag g2rd-tag--warning">
							Non configurée
						</span>
					) }
				</div>

				<div className="g2rd-row g2rd-row--stretch">
					<input
						type={ showKey ? 'text' : 'password' }
						aria-label="Clé API Google Maps (Places)"
						value={ apiKeyInput }
						onChange={ ( e ) => {
							setApiKeyInput( e.target.value );
							update( [ 'googleMapsApiKey' ], e.target.value );
						} }
						placeholder={ apiKeySet ? '••••••••••••••••••••••••••' : 'Collez la clé fournie par Google Cloud…' }
						className="g2rd-inline-input"
					/>
					<button
						type="button"
						onClick={ () => setShowKey( ( v ) => ! v ) }
						className="g2rd-inline-btn"
						title={ showKey ? 'Masquer' : 'Afficher' }
					>
						<span className={ `dashicons dashicons-${ showKey ? 'hidden' : 'visibility' }` }></span>
					</button>
				</div>
				<p className="g2rd-muted g2rd-hint-spaced">
					Créez votre clé sur{ ' ' }
					<a href="https://console.cloud.google.com/apis/library/places-backend.googleapis.com" target="_blank" rel="noreferrer">Google Cloud Console</a>
					{ ' ' }→ activez « Places API » → restreignez la clé à l'IP de votre serveur.
				</p>

				<div className="g2rd-subsection">
					<p className="g2rd-subsection__title">Vider le cache des avis</p>
					<p className="g2rd-muted g2rd-subsection__desc">
						Les avis sont mis en cache 12h. Entrez le Place ID pour forcer le rafraîchissement.
					</p>
					<div className="g2rd-row">
						<input
							type="text"
							aria-label="Place ID Google (ChIJ…)"
							value={ placeId }
							onChange={ ( e ) => setPlaceId( e.target.value ) }
							placeholder="Place ID (ChIJ…)"
							className="g2rd-inline-input g2rd-inline-input--text"
						/>
						<button
							type="button"
							disabled={ clearingCache }
							onClick={ () => {
								const id = placeId.trim();
									if ( ! id ) return;
								setClearingCache( true );
								setClearMsg( '' );
								fetch( ( googleReviewsClearUrl || '' ) + '?place_id=' + encodeURIComponent( id ), {
									method: 'DELETE',
									headers: { 'X-WP-Nonce': nonce || '' },
								} )
									.then( () => setClearMsg( '✓ Cache vidé.' ) )
									.catch( () => setClearMsg( '✗ Erreur réseau.' ) )
									.finally( () => setClearingCache( false ) );
							} }
							className="g2rd-inline-btn g2rd-inline-btn--primary"
						>
							{ clearingCache ? '…' : 'Vider le cache' }
						</button>
					</div>
					{ clearMsg && (
						<p className={ `g2rd-result ${ clearMsg.startsWith( '✓' ) ? 'g2rd-result--ok' : 'g2rd-result--ko' }` }>
							{ clearMsg }
						</p>
					) }
				</div>
			</section>

		</div>
	);
}
