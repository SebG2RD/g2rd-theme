import { useCallback } from '@wordpress/element';
import { Button } from '@wordpress/components';

export function MediaPicker( { label, value, onChange, placeholder = '' } ) {
	const open = useCallback( () => {
		if ( ! window.wp?.media ) return;

		const frame = window.wp.media( {
			title: 'Sélectionner une image',
			multiple: false,
			library: { type: 'image' },
			button: { text: 'Utiliser cette image' },
		} );

		frame.on( 'select', () => {
			const attachment = frame.state().get( 'selection' ).first().toJSON();
			onChange( attachment.url );
		} );

		frame.open();
	}, [ onChange ] );

	const remove = useCallback( () => onChange( '' ), [ onChange ] );

	return (
		<div className="g2rd-media-picker">
			{ label && <label className="g2rd-media-picker__label">{ label }</label> }
			{ value && (
				<div className="g2rd-media-picker__preview">
					<img src={ value } alt="" />
				</div>
			) }
			<div className="g2rd-media-picker__actions">
				<Button variant="secondary" onClick={ open }>
					{ value ? "Changer l'image" : 'Sélectionner une image' }
				</Button>
				{ value && (
					<Button isDestructive variant="tertiary" onClick={ remove }>
						Supprimer
					</Button>
				) }
			</div>
			{ placeholder && ! value && (
				<p className="g2rd-media-picker__hint">{ placeholder }</p>
			) }
		</div>
	);
}
