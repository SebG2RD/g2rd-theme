import { useCallback } from '@wordpress/element';
import { ToggleControl } from '@wordpress/components';
import { FeatureCard } from '../components/FeatureCard';
import { ColorSlugPicker } from '../components/ColorSlugPicker';

const { features: featureDefs, blocks: blockDefs, licensed } = window.G2RDOptionsData || {};

// Features nécessitant une licence active
const LICENSE_REQUIRED_FEATURES = [ 'pin_scroll' ];
const EMPTY_FEATURES = {};
const EMPTY_BLOCKS   = [];
const EMPTY_COLORS   = {};

export function TabEditeur( { settings, update } ) {
	const features       = settings.features       || EMPTY_FEATURES;
	const disabledBlocks = settings.disabledBlocks || EMPTY_BLOCKS;
	const colors         = settings.colors         || EMPTY_COLORS;

	const toggleBlock = useCallback( ( blockName, disabled ) => {
		const next = disabled
			? [ ...new Set( [ ...disabledBlocks, blockName ] ) ]
			: disabledBlocks.filter( ( b ) => b !== blockName );
		update( [ 'disabledBlocks' ], next );
	}, [ disabledBlocks, update ] );

	return (
		<div className="g2rd-tab-content">

			<section className="g2rd-section">
				<h2 className="g2rd-section__title">
					<span className="dashicons dashicons-admin-plugins"></span>
					Fonctionnalités
				</h2>
				<p className="g2rd-section__desc">
					Activez ou désactivez les modules optionnels du thème.
				</p>
				<div className="g2rd-features-grid">
					{ Object.entries( featureDefs || {} ).map( ( [ key, def ] ) => (
						<FeatureCard
							key={ key }
							featureKey={ key }
							definition={ def }
							value={ features[ key ] }
							onChange={ ( val ) => update( [ 'features', key ], val ) }
							locked={ ! licensed && LICENSE_REQUIRED_FEATURES.includes( key ) }
						/>
					) ) }
				</div>
			</section>

			<section className="g2rd-section">
				<h2 className="g2rd-section__title">
					<span className="dashicons dashicons-art"></span>
					Couleur des boutons flottants
				</h2>
				<p className="g2rd-section__desc">
					Couleur des boutons « accessibilité » et « retour en haut », choisie
					dans la palette du thème. « Défaut » utilise la couleur primaire du thème.
				</p>
				<div className="g2rd-color-grid">
					<ColorSlugPicker
						label="Bouton d'accessibilité"
						value={ colors.a11y_btn || '' }
						onChange={ ( slug ) => update( [ 'colors', 'a11y_btn' ], slug ) }
					/>
					<ColorSlugPicker
						label="Bouton retour en haut"
						value={ colors.top_btn || '' }
						onChange={ ( slug ) => update( [ 'colors', 'top_btn' ], slug ) }
					/>
				</div>
			</section>

			<section className="g2rd-section">
				<h2 className="g2rd-section__title">
					<span className="dashicons dashicons-block-default"></span>
					Blocs Gutenberg
					{ ! licensed && (
						<span className="g2rd-badge g2rd-badge--lock">Licence requise</span>
					) }
				</h2>
				<p className="g2rd-section__desc">
					{ licensed
						? 'Masquez les blocs inutilisés pour simplifier l\'interface de vos clients.'
						: 'La gestion des blocs est disponible avec une licence G2RD active.'
					}
				</p>
				<div className="g2rd-blocks-grid">
					{ Object.entries( blockDefs || {} ).map( ( [ name, { title, icon } ] ) => {
						const isDisabled = disabledBlocks.includes( name );
						return (
							<div
								key={ name }
								className={ `g2rd-block-item${ isDisabled ? ' g2rd-block-item--off' : '' }${ ! licensed ? ' g2rd-block-item--locked' : '' }` }
							>
								<span className={ `dashicons dashicons-${ icon }` }></span>
								<span className="g2rd-block-item__title">{ title }</span>
								<ToggleControl
									checked={ ! isDisabled }
									onChange={ ( val ) => toggleBlock( name, ! val ) }
									disabled={ ! licensed }
									__nextHasNoMarginBottom
								/>
							</div>
						);
					} ) }
				</div>
			</section>

		</div>
	);
}
