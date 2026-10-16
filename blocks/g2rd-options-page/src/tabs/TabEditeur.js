import { useCallback } from '@wordpress/element';
import { FeatureCard } from '../components/FeatureCard';
import { ColorSlugPicker } from '../components/ColorSlugPicker';
import { Toggle } from '../components/Toggle';

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

	const blockEntries  = Object.entries( blockDefs || {} );
	const enabledCount  = blockEntries.filter( ( [ name ] ) => ! disabledBlocks.includes( name ) ).length;

	return (
		<div className="g2rd-tab-content">

			<section className="g2rd-section">
				<div className="g2rd-section__head">
					<div>
						<h3 className="g2rd-section__title">Fonctionnalités</h3>
						<p className="g2rd-section__desc">Modules optionnels du thème, chargés uniquement lorsqu'ils sont activés.</p>
					</div>
				</div>
				<div className="g2rd-setting-list">
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
				<h3 className="g2rd-section__title">Boutons flottants</h3>
				<p className="g2rd-section__desc">
					Couleur des boutons « accessibilité » et « retour en haut », choisie dans la
					palette du thème. « Défaut » reprend la couleur primaire du style actif.
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
				<div className="g2rd-section__head">
					<div>
						<h3 className="g2rd-section__title">
							Blocs Gutenberg
							{ ! licensed && <span className="g2rd-chip g2rd-chip--muted">Licence requise</span> }
						</h3>
						<p className="g2rd-section__desc">
							{ licensed
								? 'Masquez les blocs inutilisés pour simplifier l\'éditeur de vos clients.'
								: 'La gestion des blocs est disponible avec une licence G2RD active.'
							}
						</p>
					</div>
					<span className="g2rd-muted g2rd-nowrap">
						{ enabledCount } / { blockEntries.length } actifs
					</span>
				</div>
				<div className="g2rd-blocks-grid">
					{ blockEntries.map( ( [ name, { title, icon } ] ) => {
						const isDisabled = disabledBlocks.includes( name );
						return (
							<div
								key={ name }
								className={ `g2rd-block-item${ isDisabled ? ' is-off' : '' }${ ! licensed ? ' is-locked' : '' }` }
							>
								<span className={ `dashicons dashicons-${ icon }` } aria-hidden="true"></span>
								<span className="g2rd-block-item__title">{ title }</span>
								<Toggle
									label={ `Activer le bloc ${ title }` }
									checked={ ! isDisabled }
									onChange={ ( val ) => toggleBlock( name, ! val ) }
									disabled={ ! licensed }
								/>
							</div>
						);
					} ) }
				</div>
			</section>

		</div>
	);
}
