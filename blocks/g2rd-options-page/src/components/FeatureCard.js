import { Icon } from './Icon';
import { Toggle } from './Toggle';

/**
 * Ligne de réglage d'une fonctionnalité optionnelle du thème.
 *
 * Libellé et description à gauche, interrupteur à droite, filet entre les
 * lignes : la lecture suit une seule colonne et l'état se voit d'un coup
 * d'œil, sans carte ni grille. S'empile dans `.g2rd-setting-list`.
 */
export function FeatureCard( { featureKey, definition, value, onChange, locked } ) {
	const { label, description } = definition;
	const isOn = !! value && ! locked;

	const classes = [
		'g2rd-setting-row',
		isOn ? 'is-on' : '',
		locked ? 'is-locked' : '',
	].filter( Boolean ).join( ' ' );

	return (
		<div className={ classes }>
			<span className="g2rd-setting-row__icon">
				<Icon name={ getIcon( featureKey ) } size={ 16 } />
			</span>
			<div className="g2rd-setting-row__main">
				<div className="g2rd-setting-row__title">
					{ label }
					{ locked && <span className="g2rd-chip g2rd-chip--muted">Licence requise</span> }
				</div>
				<p className="g2rd-setting-row__desc">{ description }</p>
			</div>
			<div className="g2rd-setting-row__control">
				<Toggle
					label={ label }
					checked={ !! value }
					onChange={ onChange }
					disabled={ locked }
				/>
			</div>
		</div>
	);
}

function getIcon( key ) {
	const icons = {
		gsap_animations:          'play',
		particles_effect:         'star',
		glass_effect:             'layers',
		clickable_articles:       'link',
		accessibility:            'accessibility',
		back_to_top:              'arrowUp',
		dark_mode:                'moon',
		enable_ai:                'sparkles',
		patterns_require_license: 'lock',
		pin_scroll:               'film',
	};
	return icons[ key ] || 'dot';
}
