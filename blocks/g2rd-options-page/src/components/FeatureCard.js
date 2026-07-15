import { ToggleControl } from '@wordpress/components';

export function FeatureCard( { featureKey, definition, value, onChange, locked } ) {
	const { label, description } = definition;

	return (
		<div className={ `g2rd-feature-card${ locked ? ' g2rd-feature-card--locked' : '' }` }>
			<div className="g2rd-feature-card__icon">
				<span className={ `dashicons dashicons-${ getIcon( featureKey ) }` }></span>
			</div>
			<div className="g2rd-feature-card__body">
				<div className="g2rd-feature-card__header">
					<strong className="g2rd-feature-card__label">{ label }</strong>
					{ locked && (
						<span className="g2rd-feature-card__badge">Licence requise</span>
					) }
				</div>
				<p className="g2rd-feature-card__desc">{ description }</p>
				<ToggleControl
					checked={ !! value }
					onChange={ onChange }
					disabled={ locked }
					__nextHasNoMarginBottom
				/>
			</div>
		</div>
	);
}

function getIcon( key ) {
	const icons = {
		gsap_animations:          'video-alt3',
		particles_effect:         'star-filled',
		glass_effect:             'admin-appearance',
		clickable_articles:       'admin-links',
		accessibility:            'universal',
		back_to_top:              'arrow-up-alt2',
		dark_mode:                'visibility',
		enable_ai:                'rest-api',
		patterns_require_license: 'lock',
		pin_scroll:               'video-alt2',
	};
	return icons[ key ] || 'admin-generic';
}
