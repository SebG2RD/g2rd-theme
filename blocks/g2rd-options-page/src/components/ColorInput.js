import { useCallback } from '@wordpress/element';

const PALETTE = window.G2RDOptionsData?.palette ?? [];

export function ColorInput( { label, value, onChange } ) {
	const safe = value || '#000000';

	const handleText = useCallback( ( e ) => {
		const v = e.target.value;
		if ( /^#([0-9a-fA-F]{0,6})$/.test( v ) ) {
			onChange( v );
		}
	}, [ onChange ] );

	const handlePicker = useCallback( ( e ) => {
		onChange( e.target.value );
	}, [ onChange ] );

	return (
		<div className="g2rd-color-input">
			{ label && <label className="g2rd-color-input__label">{ label }</label> }
			{ PALETTE.length > 0 && (
				<div className="g2rd-color-input__swatches">
					{ PALETTE.map( ( { slug, color, name } ) => (
						<button
							key={ slug }
							type="button"
							title={ name }
							className={ `g2rd-swatch${ value?.toLowerCase() === color?.toLowerCase() ? ' g2rd-swatch--active' : '' }` }
							style={ { background: color } }
							onClick={ () => onChange( color ) }
						/>
					) ) }
				</div>
			) }
			<div className="g2rd-color-input__row">
				<input
					type="color"
					value={ safe }
					onChange={ handlePicker }
					className="g2rd-color-input__picker"
				/>
				<input
					type="text"
					value={ value || '' }
					onChange={ handleText }
					placeholder="#000000"
					className="g2rd-color-input__text"
					maxLength={ 7 }
				/>
			</div>
		</div>
	);
}
