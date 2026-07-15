import { __ } from '@wordpress/i18n';

const PALETTE = window.G2RDOptionsData?.palette ?? [];

/**
 * Sélecteur de couleur basé sur les SLUGS de la palette theme.json.
 *
 * Contrairement à ColorInput (qui stocke une valeur hex), ce composant stocke
 * le slug de palette choisi (ex. "petrol"). La sortie CSS utilise
 * var(--wp--preset--color--{slug}) → la couleur suit la variation de style active.
 * Une pastille « Défaut » (valeur '') laisse la couleur par défaut du thème.
 *
 * @param {Object}   props
 * @param {string}   props.label    Libellé du champ.
 * @param {string}   props.value    Slug actuellement sélectionné ('' = défaut).
 * @param {Function} props.onChange Callback(slug).
 */
export function ColorSlugPicker( { label, value, onChange } ) {
	return (
		<div className="g2rd-color-input">
			{ label && <label className="g2rd-color-input__label">{ label }</label> }
			<div className="g2rd-color-input__swatches">
				<button
					type="button"
					title={ __( 'Couleur par défaut du thème', 'g2rd' ) }
					className={ `g2rd-swatch g2rd-swatch--default${ ! value ? ' g2rd-swatch--active' : '' }` }
					onClick={ () => onChange( '' ) }
				>
					<span className="dashicons dashicons-no-alt"></span>
				</button>
				{ PALETTE.map( ( { slug, color, name } ) => (
					<button
						key={ slug }
						type="button"
						title={ name }
						className={ `g2rd-swatch${ value === slug ? ' g2rd-swatch--active' : '' }` }
						style={ { background: color } }
						onClick={ () => onChange( slug ) }
					/>
				) ) }
			</div>
		</div>
	);
}
