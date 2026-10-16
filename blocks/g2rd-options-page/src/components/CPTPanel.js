import { useState } from '@wordpress/element';
import { ToggleControl, TextControl, Button } from '@wordpress/components';
import { Icon } from './Icon';
import { Toggle } from './Toggle';

const CPT_ICONS = {
	portfolio:         'image',
	prestations:       'briefcase',
	'qui-sommes-nous': 'users',
};

export function CPTPanel( { cptKey, label, settings, defaults, onChange } ) {
	const [ expanded, setExpanded ] = useState( false );

	const get = ( key ) => settings?.[ key ] ?? defaults?.[ key ] ?? '';
	const set = ( key, val ) => onChange( key, val );
	const enabled = !! get( 'enabled' );

	return (
		<div className={ `g2rd-cpt-panel${ enabled ? '' : ' is-off' }${ expanded ? ' is-open' : '' }` }>
			<div className="g2rd-setting-row g2rd-cpt-panel__header">
				<span className="g2rd-setting-row__icon">
					<Icon name={ CPT_ICONS[ cptKey ] || 'folder' } size={ 16 } />
				</span>
				<div className="g2rd-setting-row__main">
					<div className="g2rd-setting-row__title">{ label }</div>
					<p className="g2rd-setting-row__desc">
						{ enabled ? `Slug : /${ get( 'slug' ) || cptKey }/` : 'Désactivé' }
					</p>
				</div>
				<div className="g2rd-setting-row__control g2rd-row g2rd-row--loose">
					<Button
						variant="tertiary"
						size="small"
						onClick={ () => setExpanded( ( v ) => ! v ) }
						aria-expanded={ expanded }
						className="g2rd-cpt-panel__toggle"
					>
						{ expanded ? 'Fermer' : 'Configurer' }
					</Button>
					<Toggle
						label={ `Activer ${ label }` }
						checked={ enabled }
						onChange={ ( val ) => set( 'enabled', val ) }
					/>
				</div>
			</div>

			{ expanded && (
				<div className="g2rd-cpt-panel__fields">
					<div className="g2rd-cpt-panel__row">
						<TextControl
							label="Nom singulier"
							value={ get( 'singular' ) }
							onChange={ ( v ) => set( 'singular', v ) }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
						<TextControl
							label="Nom pluriel"
							value={ get( 'plural' ) }
							onChange={ ( v ) => set( 'plural', v ) }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
					</div>
					<div className="g2rd-cpt-panel__row">
						<TextControl
							label="Libellé « Tous les éléments »"
							value={ get( 'all_items' ) }
							onChange={ ( v ) => set( 'all_items', v ) }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
						<TextControl
							label="Slug URL"
							help="Un changement de slug nécessite une mise à jour des permaliens."
							value={ get( 'slug' ) }
							onChange={ ( v ) => set( 'slug', v ) }
							__next40pxDefaultSize
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
								label="Nom de la taxonomie (singulier)"
								value={ get( 'tax_singular' ) }
								onChange={ ( v ) => set( 'tax_singular', v ) }
								__next40pxDefaultSize
								__nextHasNoMarginBottom
							/>
							<TextControl
								label="Nom de la taxonomie (pluriel)"
								value={ get( 'tax_plural' ) }
								onChange={ ( v ) => set( 'tax_plural', v ) }
								__next40pxDefaultSize
								__nextHasNoMarginBottom
							/>
						</div>
					) }
				</div>
			) }
		</div>
	);
}
