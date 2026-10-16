import { ToggleControl } from '@wordpress/components';

/**
 * Carte d'une fonctionnalité optionnelle du thème.
 *
 * L'interrupteur est aligné à droite, sur la ligne du titre : l'état se lit
 * d'un coup d'œil sur une grille de cartes, sans descendre sous chaque
 * description. La carte porte la classe `--on` quand la fonctionnalité est
 * active pour teinter l'icône et la bordure.
 */
export function FeatureCard( { featureKey, definition, value, onChange, locked } ) {
	const { label, description } = definition;
	const isOn = !! value && ! locked;

	const classes = [
		'g2rd-feature-card',
		isOn ? 'g2rd-feature-card--on' : '',
		locked ? 'g2rd-feature-card--locked' : '',
	].filter( Boolean ).join( ' ' );

	return (
		<div className={ classes }>
			<div className="g2rd-feature-card__icon" aria-hidden="true">
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
			</div>
			<div className="g2rd-feature-card__control">
				<ToggleControl
					label={ label }
					hideLabelFromVision
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
		accessibility:            'universal-access-alt',
		back_to_top:              'arrow-up-alt2',
		dark_mode:                'visibility',
		enable_ai:                'rest-api',
		patterns_require_license: 'lock',
		pin_scroll:               'video-alt2',
	};
	return icons[ key ] || 'admin-generic';
}
