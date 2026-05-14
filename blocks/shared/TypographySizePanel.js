import { __ } from '@wordpress/i18n';
import { InspectorControls, useSettings, FontSizePicker } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';

/**
 * Panneau "Typographie" dans l'onglet Styles de la sidebar Gutenberg.
 *
 * @param {Object}   props
 * @param {string}   [props.title]    Titre du panneau (défaut : "Typographie")
 * @param {Array}    props.elements   Liste des éléments : { label, value, onChange }
 */
export function TypographySizePanel( { title, elements } ) {
	const [ fontSizes ] = useSettings( 'typography.fontSizes' );

	if ( ! elements || elements.length === 0 ) {
		return null;
	}

	return (
		<InspectorControls group="styles">
			<PanelBody title={ title || __( 'Typographie', 'g2rd' ) }>
				{ elements.map( ( { label, value, onChange } ) => (
					<div key={ label } style={ { marginBottom: '16px' } }>
						<p style={ { marginBottom: '8px', fontWeight: 500 } }>{ label }</p>
						<FontSizePicker
							fontSizes={ fontSizes }
							value={ value }
							onChange={ onChange }
							disableCustomFontSizes={ true }
							withSlider={ false }
							size="__unstable-large"
						/>
					</div>
				) ) }
			</PanelBody>
		</InspectorControls>
	);
}
