const { palette } = window.G2RDOptionsData || {};

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
	const businessType = settings.businessType || '';
	const colors       = settings.colors || {};

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

		</div>
	);
}
