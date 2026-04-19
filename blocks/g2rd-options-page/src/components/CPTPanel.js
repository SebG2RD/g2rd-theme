import { useState } from '@wordpress/element';
import { ToggleControl, TextControl, Button } from '@wordpress/components';

export function CPTPanel( { cptKey, label, icon, settings, defaults, onChange } ) {
	const [ expanded, setExpanded ] = useState( false );

	const get = ( key ) => settings?.[ key ] ?? defaults?.[ key ] ?? '';
	const set = ( key, val ) => onChange( key, val );

	return (
		<div className={ `g2rd-cpt-panel${ get( 'enabled' ) ? '' : ' g2rd-cpt-panel--disabled' }` }>
			<div className="g2rd-cpt-panel__header">
				<span className={ `dashicons dashicons-${ icon }` }></span>
				<strong>{ label }</strong>
				<ToggleControl
					checked={ !! get( 'enabled' ) }
					onChange={ ( val ) => set( 'enabled', val ) }
					__nextHasNoMarginBottom
				/>
				<Button
					variant="tertiary"
					size="small"
					onClick={ () => setExpanded( ( v ) => ! v ) }
					aria-expanded={ expanded }
					className="g2rd-cpt-panel__toggle"
				>
					{ expanded ? 'Masquer' : 'Configurer' }
				</Button>
			</div>

			{ expanded && (
				<div className="g2rd-cpt-panel__fields">
					<div className="g2rd-cpt-panel__row">
						<TextControl
							label="Nom singulier"
							value={ get( 'singular' ) }
							onChange={ ( v ) => set( 'singular', v ) }
							__nextHasNoMarginBottom
						/>
						<TextControl
							label="Nom pluriel"
							value={ get( 'plural' ) }
							onChange={ ( v ) => set( 'plural', v ) }
							__nextHasNoMarginBottom
						/>
					</div>
					<div className="g2rd-cpt-panel__row">
						<TextControl
							label="Label «Tous les éléments»"
							value={ get( 'all_items' ) }
							onChange={ ( v ) => set( 'all_items', v ) }
							__nextHasNoMarginBottom
						/>
						<TextControl
							label="Slug URL"
							value={ get( 'slug' ) }
							onChange={ ( v ) => set( 'slug', v ) }
							__nextHasNoMarginBottom
						/>
					</div>
					<div className="g2rd-cpt-panel__toggles">
						<ToggleControl
							label="Archive activée"
							checked={ !! get( 'has_archive' ) }
							onChange={ ( v ) => set( 'has_archive', v ) }
							__nextHasNoMarginBottom
						/>
						<ToggleControl
							label="Exposé dans l'API REST"
							checked={ !! get( 'show_in_rest' ) }
							onChange={ ( v ) => set( 'show_in_rest', v ) }
							__nextHasNoMarginBottom
						/>
						<ToggleControl
							label="Taxonomie activée"
							checked={ !! get( 'tax_enabled' ) }
							onChange={ ( v ) => set( 'tax_enabled', v ) }
							__nextHasNoMarginBottom
						/>
					</div>
					{ get( 'tax_enabled' ) && (
						<div className="g2rd-cpt-panel__row">
							<TextControl
								label="Nom taxonomie (singulier)"
								value={ get( 'tax_singular' ) }
								onChange={ ( v ) => set( 'tax_singular', v ) }
								__nextHasNoMarginBottom
							/>
							<TextControl
								label="Nom taxonomie (pluriel)"
								value={ get( 'tax_plural' ) }
								onChange={ ( v ) => set( 'tax_plural', v ) }
								__nextHasNoMarginBottom
							/>
						</div>
					) }
				</div>
			) }
		</div>
	);
}
