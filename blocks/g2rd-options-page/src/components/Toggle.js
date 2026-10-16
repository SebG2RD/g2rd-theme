import { FormToggle } from '@wordpress/components';

/**
 * Interrupteur sans libellé visible.
 *
 * `ToggleControl` affiche toujours son `label` (il ignore
 * `hideLabelFromVision`) : dans une ligne de réglage dont le titre est déjà
 * posé à gauche, le libellé se retrouvait en double à côté de l'interrupteur
 * et écrasait la colonne de texte. `FormToggle` rend l'interrupteur seul ;
 * le nom accessible passe par `aria-label`.
 *
 * @param {Object}   props
 * @param {string}   props.label    Nom accessible de l'interrupteur.
 * @param {boolean}  props.checked  État.
 * @param {Function} props.onChange Callback(boolean).
 * @param {boolean}  props.disabled Désactivé.
 */
export function Toggle( { label, checked, onChange, disabled = false } ) {
	return (
		<FormToggle
			className="g2rd-toggle"
			checked={ !! checked }
			disabled={ disabled }
			onChange={ ( event ) => onChange( !! event.target.checked ) }
			aria-label={ label }
		/>
	);
}
