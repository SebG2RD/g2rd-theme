import { useCallback } from '@wordpress/element';

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
