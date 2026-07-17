import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	useInnerBlocksProps,
	InspectorControls,
	InnerBlocks,
	MediaUpload,
	MediaUploadCheck,
	PanelColorSettings,
} from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	RangeControl,
	ToggleControl,
	Button,
	TextControl,
	__experimentalUnitControl as UnitControl,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';

// ─── Constantes ───────────────────────────────────────────────────────────────

const LAYOUT_TYPES = [
	{ value: 'flex',        label: __( 'Flex',      'g2rd' ) },
	{ value: 'grid',        label: __( 'Grille',    'g2rd' ) },
	{ value: 'constrained', label: __( 'Contraint', 'g2rd' ) },
	{ value: 'flow',        label: __( 'Flux',      'g2rd' ) },
];

// 6 valeurs : au-delà de 4 options, le natif utilise un SelectControl.
const FLEX_JUSTIFY = [
	{ value: 'flex-start',    label: __( 'Début', 'g2rd' ) },
	{ value: 'center',        label: __( 'Centre', 'g2rd' ) },
	{ value: 'flex-end',      label: __( 'Fin', 'g2rd' ) },
	{ value: 'space-between', label: __( 'Espace entre', 'g2rd' ) },
	{ value: 'space-evenly',  label: __( 'Espace égal', 'g2rd' ) },
	{ value: 'stretch',       label: __( 'Étirer', 'g2rd' ) },
];

// 4 valeurs : ToggleGroupControl.
const FLEX_ALIGN = [
	{ value: 'flex-start', label: __( 'Début', 'g2rd' ) },
	{ value: 'center',     label: __( 'Centre', 'g2rd' ) },
	{ value: 'flex-end',   label: __( 'Fin', 'g2rd' ) },
	{ value: 'stretch',    label: __( 'Étirer', 'g2rd' ) },
];

const DEVICES = [
	{ value: 'desktop', label: __( 'Bureau', 'g2rd' ) },
	{ value: 'tablet',  label: __( 'Tablette', 'g2rd' ) },
	{ value: 'mobile',  label: __( 'Mobile', 'g2rd' ) },
];

const HTML_TAGS = [
	{ value: 'div',     label: '<div>' },
	{ value: 'section', label: '<section>' },
	{ value: 'article', label: '<article>' },
	{ value: 'header',  label: '<header>' },
	{ value: 'footer',  label: '<footer>' },
	{ value: 'main',    label: '<main>' },
	{ value: 'aside',   label: '<aside>' },
	{ value: 'figure',  label: '<figure>' },
];

const ANIMATIONS = [
	{ value: 'none',      label: __( 'Aucune', 'g2rd' ) },
	{ value: 'fadeIn',    label: 'Fade In' },
	{ value: 'fadeInUp',  label: 'Fade In Up' },
	{ value: 'fadeInDown',label: 'Fade In Down' },
	{ value: 'fadeInLeft',label: 'Fade In Left' },
	{ value: 'fadeInRight',label: 'Fade In Right' },
	{ value: 'zoomIn',    label: 'Zoom In' },
];

// ─── Selecteur d'appareil ─────────────────────────────────────────────────────

/**
 * Sélecteur d'appareil pour les réglages responsives.
 *
 * Choix parmi 3 → ToggleGroupControl natif (remplace des boutons emoji maison :
 * même état `device`, même comportement, widget natif de WordPress).
 */
function DeviceSwitcher( { value, onChange } ) {
	return (
		<ToggleGroupControl
			label={ __( 'Appareil', 'g2rd' ) }
			value={ value }
			onChange={ onChange }
			isBlock
			__next40pxDefaultSize
			__nextHasNoMarginBottom
		>
			{ DEVICES.map( ( d ) => (
				<ToggleGroupControlOption key={ d.value } value={ d.value } label={ d.label } />
			) ) }
		</ToggleGroupControl>
	);
}

// ─── Contrôle responsive générique ───────────────────────────────────────────

/**
 * Retourne le nom de l'attribut pour l'appareil courant.
 * ex : getAttrKey('flexDirection', 'tablet') → 'flexDirectionTablet'
 */
function getAttrKey( base, device ) {
	if ( device === 'tablet' ) return base + 'Tablet';
	if ( device === 'mobile' ) return base + 'Mobile';
	return base;
}

// ─── Générateur de styles inline pour la prévisualisation éditeur ─────────────

function buildEditorStyle( attrs ) {
	const { layoutType, flexDirection, flexJustify, flexAlign, flexWrap, flexGap,
		gridColumns, gridGap, constrainedWidth, paddingTop, paddingRight,
		paddingBottom, paddingLeft, marginTop, marginBottom, minHeight, width,
		sticky, stickyTop,
		bgType, bgColor, bgGradient, bgImageUrl, bgImageSize, bgImagePosition,
		bgImageRepeat, borderRadius, borderWidth, borderStyle, borderColor, overflow } = attrs;

	const s = {};

	switch ( layoutType ) {
		case 'flex':
			s.display = 'flex';
			if ( flexDirection ) s.flexDirection = flexDirection;
			if ( flexJustify   ) s.justifyContent = flexJustify;
			if ( flexAlign     ) s.alignItems     = flexAlign;
			s.flexWrap = flexWrap ? 'wrap' : 'nowrap';
			if ( flexGap ) s.gap = flexGap;
			break;
		case 'grid':
			s.display = 'grid';
			if ( gridColumns ) s.gridTemplateColumns = `repeat(${ gridColumns }, 1fr)`;
			if ( gridGap     ) s.gap = gridGap;
			break;
		case 'constrained':
			s.display     = 'block';
			s.maxWidth    = constrainedWidth || '1200px';
			s.marginLeft  = 'auto';
			s.marginRight = 'auto';
			break;
		default:
			s.display = 'block';
	}

	if ( width      ) s.width     = width;
	if ( minHeight  ) s.minHeight = minHeight;
	if ( paddingTop    ) s.paddingTop    = paddingTop;
	if ( paddingRight  ) s.paddingRight  = paddingRight;
	if ( paddingBottom ) s.paddingBottom = paddingBottom;
	if ( paddingLeft   ) s.paddingLeft   = paddingLeft;
	if ( marginTop     ) s.marginTop     = marginTop;
	if ( marginBottom  ) s.marginBottom  = marginBottom;
	if ( borderRadius  ) s.borderRadius  = borderRadius;
	if ( borderWidth && borderColor ) {
		s.border = `${ borderWidth } ${ borderStyle || 'solid' } ${ borderColor }`;
	}
	if ( overflow && overflow !== 'visible' ) s.overflow = overflow;

	// Position collante : suit le contenu au scroll (ex. sommaire latéral).
	// align-self:flex-start évite l'étirement si le parent est en flex/grille
	// (sinon l'élément remplit toute la hauteur et ne peut pas coller).
	if ( sticky ) {
		s.position  = 'sticky';
		s.top       = stickyTop || '24px';
		s.alignSelf = 'flex-start';
		s.zIndex    = 2;
	}

	switch ( bgType ) {
		case 'color':    if ( bgColor    ) s.backgroundColor = bgColor;    break;
		case 'gradient': if ( bgGradient ) s.background      = bgGradient; break;
		case 'image':
			if ( bgImageUrl ) {
				s.backgroundImage    = `url(${ bgImageUrl })`;
				s.backgroundSize     = bgImageSize     || 'cover';
				s.backgroundPosition = bgImagePosition || 'center center';
				s.backgroundRepeat   = bgImageRepeat   || 'no-repeat';
			}
			break;
	}

	return s;
}

// ─── Composant principal ──────────────────────────────────────────────────────

export default function Edit( { attributes, setAttributes, clientId } ) {
	const {
		blockId, htmlTag, layoutType,
		flexDirection, flexDirectionTablet, flexDirectionMobile,
		flexJustify, flexJustifyTablet, flexJustifyMobile,
		flexAlign, flexAlignTablet, flexAlignMobile,
		flexWrap, flexWrapTablet, flexWrapMobile,
		flexGap, flexGapTablet, flexGapMobile,
		gridColumns, gridColumnsTablet, gridColumnsMobile,
		gridGap, gridGapTablet, gridGapMobile,
		constrainedWidth, minHeight, width,
		sticky, stickyTop,
		paddingTop, paddingRight, paddingBottom, paddingLeft,
		paddingTopTablet, paddingRightTablet, paddingBottomTablet, paddingLeftTablet,
		paddingTopMobile, paddingRightMobile, paddingBottomMobile, paddingLeftMobile,
		marginTop, marginBottom,
		bgType, bgColor, bgGradient, bgImageUrl, bgImageId,
		bgImageSize, bgImagePosition, bgImageRepeat, bgOverlay, bgOverlayColor,
		borderRadius, borderWidth, borderStyle, borderColor, overflow,
		hideOnMobile, hideOnTablet, hideOnDesktop,
		animation, animationDelay, animationDuration, animationEasing,
		customCSSClass,
	} = attributes;

	const [ device, setDevice ] = useState( 'desktop' );

	// Génère l'ID unique au premier rendu
	useEffect( () => {
		if ( ! blockId ) {
			setAttributes( { blockId: 'g2rd-cntr-' + clientId.substring( 0, 8 ) } );
		}
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps

	const blockProps = useBlockProps( {
		className: `g2rd-container g2rd-container--${ layoutType }`,
		style:     buildEditorStyle( attributes ),
	} );

	// useInnerBlocksProps applique le layout (grille/flex) DIRECTEMENT sur le
	// conteneur des blocs enfants → ils deviennent de vrais items de grille/flex
	// (sinon <InnerBlocks> les imbrique 2 niveaux plus bas et ils s'empilent).
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		orientation: layoutType === 'flex' && flexDirection === 'column' ? 'vertical' : 'horizontal',
		renderAppender: InnerBlocks.ButtonBlockAppender,
	} );

	const Tag = htmlTag || 'div';

	// Helper : set un attribut responsive selon l'appareil actif
	const setResponsive = ( base, value ) => {
		setAttributes( { [ getAttrKey( base, device ) ]: value } );
	};
	const getResponsive = ( base ) => attributes[ getAttrKey( base, device ) ] ?? '';

	// ── Onglet « Réglages » › Mise en page ─────────────────────────────────────
	const MiseEnPagePanel = (
		<PanelBody title={ __( 'Mise en page', 'g2rd' ) } initialOpen={ true }>
			<ToggleGroupControl
				label={ __( 'Type de disposition', 'g2rd' ) }
				value={ layoutType }
				onChange={ ( v ) => setAttributes( { layoutType: v } ) }
				isBlock
				__next40pxDefaultSize
				__nextHasNoMarginBottom
			>
				{ LAYOUT_TYPES.map( ( t ) => (
					<ToggleGroupControlOption key={ t.value } value={ t.value } label={ t.label } />
				) ) }
			</ToggleGroupControl>

			{ ( layoutType === 'flex' || layoutType === 'grid' ) && (
				<DeviceSwitcher value={ device } onChange={ setDevice } />
			) }

			{ layoutType === 'flex' && (
				<>
					<ToggleGroupControl
						label={ __( 'Direction', 'g2rd' ) }
						value={ getResponsive( 'flexDirection' ) }
						onChange={ ( v ) => setResponsive( 'flexDirection', v ) }
						isBlock
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					>
						<ToggleGroupControlOption value="row" label={ __( 'Ligne', 'g2rd' ) } />
						<ToggleGroupControlOption value="column" label={ __( 'Colonne', 'g2rd' ) } />
					</ToggleGroupControl>

					<SelectControl
						label={ __( 'Justification', 'g2rd' ) }
						value={ getResponsive( 'flexJustify' ) }
						options={ FLEX_JUSTIFY }
						onChange={ ( v ) => setResponsive( 'flexJustify', v ) }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>

					<ToggleGroupControl
						label={ __( 'Alignement', 'g2rd' ) }
						value={ getResponsive( 'flexAlign' ) }
						onChange={ ( v ) => setResponsive( 'flexAlign', v ) }
						isBlock
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					>
						{ FLEX_ALIGN.map( ( a ) => (
							<ToggleGroupControlOption key={ a.value } value={ a.value } label={ a.label } />
						) ) }
					</ToggleGroupControl>

					<ToggleControl
						label={ __( 'Passage sur plusieurs lignes', 'g2rd' ) }
						checked={ !! attributes[ getAttrKey( 'flexWrap', device ) ] }
						onChange={ ( v ) => setResponsive( 'flexWrap', v ) }
						__nextHasNoMarginBottom={ true }
					/>
					<UnitControl
						label={ __( 'Espacement entre les blocs', 'g2rd' ) }
						value={ getResponsive( 'flexGap' ) || flexGap }
						onChange={ ( v ) => setResponsive( 'flexGap', v ) }
						units={ [ { value: 'px', label: 'px' }, { value: 'em', label: 'em' }, { value: 'rem', label: 'rem' } ] }
					/>
				</>
			) }

			{ layoutType === 'grid' && (
				<>
					<RangeControl
						label={ __( 'Colonnes', 'g2rd' ) }
						value={ device === 'mobile' ? gridColumnsMobile : device === 'tablet' ? gridColumnsTablet : gridColumns }
						onChange={ ( v ) => {
							const key = device === 'mobile' ? 'gridColumnsMobile' : device === 'tablet' ? 'gridColumnsTablet' : 'gridColumns';
							setAttributes( { [ key ]: v } );
						} }
						min={ 1 } max={ 12 }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<UnitControl
						label={ __( 'Espacement entre les blocs', 'g2rd' ) }
						value={ getResponsive( 'gridGap' ) || gridGap }
						onChange={ ( v ) => setResponsive( 'gridGap', v ) }
						units={ [ { value: 'px', label: 'px' }, { value: 'em', label: 'em' } ] }
					/>
				</>
			) }

			{ layoutType === 'constrained' && (
				<UnitControl
					label={ __( 'Largeur maximale', 'g2rd' ) }
					value={ constrainedWidth }
					onChange={ ( v ) => setAttributes( { constrainedWidth: v } ) }
					units={ [ { value: 'px', label: 'px' }, { value: '%', label: '%' }, { value: 'vw', label: 'vw' } ] }
				/>
			) }
		</PanelBody>
	);

	// ── Onglet « Réglages » › Comportement ─────────────────────────────────────
	const ComportementPanel = (
		<PanelBody title={ __( 'Comportement', 'g2rd' ) } initialOpen={ false }>
			<ToggleControl
				label={ __( 'Rendre collant (sticky)', 'g2rd' ) }
				help={ __( 'Le bloc suit le contenu au défilement et reste visible dans sa colonne (ex. sommaire latéral).', 'g2rd' ) }
				checked={ !! sticky }
				onChange={ ( v ) => setAttributes( { sticky: v } ) }
				__nextHasNoMarginBottom={ true }
			/>
			{ sticky && (
				<UnitControl
					label={ __( 'Décalage haut', 'g2rd' ) }
					value={ stickyTop }
					onChange={ ( v ) => setAttributes( { stickyTop: v } ) }
					units={ [ { value: 'px', label: 'px' }, { value: 'rem', label: 'rem' }, { value: 'em', label: 'em' } ] }
					help={ __( 'Distance depuis le haut de la fenêtre à laquelle le bloc se fige.', 'g2rd' ) }
				/>
			) }
		</PanelBody>
	);

	// ── Onglet « Styles » › Dimensions ─────────────────────────────────────────
	// WordPress regroupe marge intérieure/extérieure, largeur et hauteur sous
	// « Dimensions » — on suit le natif plutôt que d'inventer « Espacement ».
	const DimensionsPanel = (
		<PanelBody title={ __( 'Dimensions', 'g2rd' ) } initialOpen={ true }>
			<DeviceSwitcher value={ device } onChange={ setDevice } />
			<p className="g2rd-control-label">{ __( 'Marge intérieure', 'g2rd' ) }</p>
			<div className="g2rd-spacing-grid">
				{ [ 'Top', 'Right', 'Bottom', 'Left' ].map( ( side ) => (
					<UnitControl
						key={ side }
						label={ side }
						value={ getResponsive( 'padding' + side ) || attributes[ 'padding' + side ] }
						onChange={ ( v ) => setResponsive( 'padding' + side, v ) }
						units={ [ { value: 'px', label: 'px' }, { value: 'em', label: 'em' }, { value: 'rem', label: 'rem' }, { value: '%', label: '%' } ] }
					/>
				) ) }
			</div>
			<p className="g2rd-control-label">{ __( 'Marge extérieure', 'g2rd' ) }</p>
			<UnitControl
				label={ __( 'Haut', 'g2rd' ) }
				value={ marginTop }
				onChange={ ( v ) => setAttributes( { marginTop: v } ) }
				units={ [ { value: 'px', label: 'px' }, { value: 'em', label: 'em' }, { value: 'rem', label: 'rem' } ] }
			/>
			<UnitControl
				label={ __( 'Bas', 'g2rd' ) }
				value={ marginBottom }
				onChange={ ( v ) => setAttributes( { marginBottom: v } ) }
				units={ [ { value: 'px', label: 'px' }, { value: 'em', label: 'em' }, { value: 'rem', label: 'rem' } ] }
			/>
			<UnitControl
				label={ __( 'Largeur', 'g2rd' ) }
				value={ width }
				onChange={ ( v ) => setAttributes( { width: v } ) }
				units={ [ { value: 'px', label: 'px' }, { value: '%', label: '%' }, { value: 'vw', label: 'vw' } ] }
			/>
			<UnitControl
				label={ __( 'Hauteur minimale', 'g2rd' ) }
				value={ minHeight }
				onChange={ ( v ) => setAttributes( { minHeight: v } ) }
				units={ [ { value: 'px', label: 'px' }, { value: 'vh', label: 'vh' }, { value: 'em', label: 'em' } ] }
			/>
		</PanelBody>
	);

	// ── Onglet « Styles » › Arrière-plan ───────────────────────────────────────
	const ArrierePlanPanel = (
		<>
			<PanelBody title={ __( 'Arrière-plan', 'g2rd' ) } initialOpen={ false }>
				<SelectControl __next40pxDefaultSize __nextHasNoMarginBottom
					label={ __( 'Type de fond', 'g2rd' ) }
					value={ bgType }
					options={ [
						{ value: 'none',     label: __( 'Aucun', 'g2rd' ) },
						{ value: 'color',    label: __( 'Couleur', 'g2rd' ) },
						{ value: 'gradient', label: __( 'Dégradé', 'g2rd' ) },
						{ value: 'image',    label: __( 'Image', 'g2rd' ) },
					] }
					onChange={ ( v ) => setAttributes( { bgType: v } ) }
					__next40pxDefaultSize={ true }
					__nextHasNoMarginBottom={ true }
				/>
				{ bgType === 'color' && (
					<PanelColorSettings
						title={ __( 'Couleur de fond', 'g2rd' ) }
						colorSettings={ [
							{
								value: bgColor,
								onChange: ( v ) => setAttributes( { bgColor: v } ),
								label: __( 'Fond', 'g2rd' ),
							},
						] }
					/>
				) }
				{ bgType === 'gradient' && (
					<TextControl
						label={ __( 'Valeur CSS du dégradé', 'g2rd' ) }
						value={ bgGradient }
						onChange={ ( v ) => setAttributes( { bgGradient: v } ) }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						placeholder="linear-gradient(135deg, #667eea, #764ba2)"
						__next40pxDefaultSize={ true }
						__nextHasNoMarginBottom={ true }
					/>
				) }
				{ bgType === 'image' && (
					<>
						<MediaUploadCheck>
							<MediaUpload
								onSelect={ ( media ) => setAttributes( { bgImageUrl: media.url, bgImageId: media.id } ) }
								allowedTypes={ [ 'image' ] }
								value={ bgImageId }
								render={ ( { open } ) => (
									<Button variant="secondary" onClick={ open } style={ { marginBottom: '8px' } }>
										{ bgImageUrl
											? __( 'Changer l\'image', 'g2rd' )
											: __( 'Choisir une image', 'g2rd' ) }
									</Button>
								) }
							/>
						</MediaUploadCheck>
						{ bgImageUrl && (
							<img src={ bgImageUrl } alt="" style={ { width: '100%', maxHeight: '80px', objectFit: 'cover', marginBottom: '8px' } } />
						) }
						<SelectControl __next40pxDefaultSize __nextHasNoMarginBottom
							label={ __( 'Taille', 'g2rd' ) }
							value={ bgImageSize }
							options={ [
								{ value: 'cover',   label: 'Cover' },
								{ value: 'contain', label: 'Contain' },
								{ value: 'auto',    label: 'Auto' },
							] }
							onChange={ ( v ) => setAttributes( { bgImageSize: v } ) }
							__next40pxDefaultSize={ true }
							__nextHasNoMarginBottom={ true }
						/>
						<TextControl
							label={ __( 'Position', 'g2rd' ) }
							value={ bgImagePosition }
							onChange={ ( v ) => setAttributes( { bgImagePosition: v } ) }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							placeholder="center center"
							__next40pxDefaultSize={ true }
							__nextHasNoMarginBottom={ true }
						/>
					</>
				) }
				{ bgType !== 'none' && (
					<>
						<ToggleControl
							label={ __( 'Overlay de couleur', 'g2rd' ) }
							checked={ bgOverlay }
							onChange={ ( v ) => setAttributes( { bgOverlay: v } ) }
							__nextHasNoMarginBottom={ true }
						/>
						{ bgOverlay && (
							<PanelColorSettings
								title={ __( "Couleur de l'overlay", 'g2rd' ) }
								colorSettings={ [
									{
										value: bgOverlayColor,
										onChange: ( v ) => setAttributes( { bgOverlayColor: v } ),
										label: __( 'Overlay', 'g2rd' ),
									},
								] }
							/>
						) }
					</>
				) }
			</PanelBody>

		</>
	);

	// ── Onglet « Styles » › Bordure ────────────────────────────────────────────
	const BordurePanel = (
		<PanelBody title={ __( 'Bordure', 'g2rd' ) } initialOpen={ false }>
			<UnitControl
				label={ __( 'Rayon des coins', 'g2rd' ) }
				value={ borderRadius }
				onChange={ ( v ) => setAttributes( { borderRadius: v } ) }
				units={ [ { value: 'px', label: 'px' }, { value: 'em', label: 'em' }, { value: '%', label: '%' } ] }
			/>
				<UnitControl
					label={ __( 'Épaisseur', 'g2rd' ) }
					value={ borderWidth }
					onChange={ ( v ) => setAttributes( { borderWidth: v } ) }
					units={ [ { value: 'px', label: 'px' } ] }
				/>
				<SelectControl __next40pxDefaultSize __nextHasNoMarginBottom
					label={ __( 'Style', 'g2rd' ) }
					value={ borderStyle }
					options={ [
						{ value: 'solid',  label: 'Solid' },
						{ value: 'dashed', label: 'Dashed' },
						{ value: 'dotted', label: 'Dotted' },
						{ value: 'double', label: 'Double' },
					] }
					onChange={ ( v ) => setAttributes( { borderStyle: v } ) }
					__next40pxDefaultSize={ true }
					__nextHasNoMarginBottom={ true }
				/>
				{ borderWidth && (
					<PanelColorSettings
						title={ __( 'Couleur de la bordure', 'g2rd' ) }
						colorSettings={ [
							{
								value: borderColor,
								onChange: ( v ) => setAttributes( { borderColor: v } ),
								label: __( 'Bordure', 'g2rd' ),
							},
						] }
					/>
				) }
				<SelectControl __next40pxDefaultSize __nextHasNoMarginBottom
					label={ __( 'Overflow', 'g2rd' ) }
					value={ overflow }
					options={ [
						{ value: 'visible', label: 'Visible' },
						{ value: 'hidden',  label: 'Hidden' },
						{ value: 'auto',    label: 'Auto' },
					] }
					onChange={ ( v ) => setAttributes( { overflow: v } ) }
					__next40pxDefaultSize={ true }
					__nextHasNoMarginBottom={ true }
				/>
			</PanelBody>
	);

	// ── Onglet « Réglages » › Visibilité ───────────────────────────────────────
	const VisibilitePanel = (
		<PanelBody title={ __( 'Visibilité', 'g2rd' ) } initialOpen={ false }>
				<ToggleControl
					label={ __( 'Masquer sur mobile (≤ 768 px)', 'g2rd' ) }
					checked={ hideOnMobile }
					onChange={ ( v ) => setAttributes( { hideOnMobile: v } ) }
					__nextHasNoMarginBottom={ true }
				/>
				<ToggleControl
					label={ __( 'Masquer sur tablette (≤ 1024 px)', 'g2rd' ) }
					checked={ hideOnTablet }
					onChange={ ( v ) => setAttributes( { hideOnTablet: v } ) }
					__nextHasNoMarginBottom={ true }
				/>
				<ToggleControl
					label={ __( 'Masquer sur desktop (> 1024 px)', 'g2rd' ) }
					checked={ hideOnDesktop }
					onChange={ ( v ) => setAttributes( { hideOnDesktop: v } ) }
					__nextHasNoMarginBottom={ true }
				/>
		</PanelBody>
	);

	// ── Onglet « Styles » › Animation ──────────────────────────────────────────
	const AnimationPanel = (
		<PanelBody title={ __( 'Animation', 'g2rd' ) } initialOpen={ false }>
			<SelectControl __next40pxDefaultSize __nextHasNoMarginBottom
				label={ __( 'Animation', 'g2rd' ) }
				value={ animation }
				options={ ANIMATIONS }
				onChange={ ( v ) => setAttributes( { animation: v } ) }
				__next40pxDefaultSize={ true }
				__nextHasNoMarginBottom={ true }
			/>
				{ animation !== 'none' && (
					<>
						<RangeControl __next40pxDefaultSize __nextHasNoMarginBottom
							label={ __( 'Délai (ms)', 'g2rd' ) }
							value={ animationDelay }
							onChange={ ( v ) => setAttributes( { animationDelay: v } ) }
							min={ 0 } max={ 2000 } step={ 50 }
							__next40pxDefaultSize={ true }
							__nextHasNoMarginBottom={ true }
						/>
						<RangeControl __next40pxDefaultSize __nextHasNoMarginBottom
							label={ __( 'Durée (ms)', 'g2rd' ) }
							value={ animationDuration }
							onChange={ ( v ) => setAttributes( { animationDuration: v } ) }
							min={ 100 } max={ 3000 } step={ 50 }
							__next40pxDefaultSize={ true }
							__nextHasNoMarginBottom={ true }
						/>
						<SelectControl __next40pxDefaultSize __nextHasNoMarginBottom
							label={ __( 'Accélération', 'g2rd' ) }
							value={ animationEasing }
							options={ [
								{ value: 'ease',        label: 'Ease' },
								{ value: 'ease-in',     label: 'Ease In' },
								{ value: 'ease-out',    label: 'Ease Out' },
								{ value: 'ease-in-out', label: 'Ease In Out' },
								{ value: 'linear',      label: 'Linear' },
							] }
							onChange={ ( v ) => setAttributes( { animationEasing: v } ) }
							__next40pxDefaultSize={ true }
							__nextHasNoMarginBottom={ true }
						/>
					</>
				) }
		</PanelBody>
	);

	// ── Onglet « Avancé » (natif) ──────────────────────────────────────────────
	// WordPress y place nativement le sélecteur d'élément HTML et le champ de
	// classe CSS : on s'y range au lieu d'inventer des panneaux séparés.
	//
	// customCSSClass est CONSERVÉ : c'est un attribut G2RD appliqué par render.php,
	// distinct du champ natif « Classe(s) CSS additionnelle(s) » qui écrit dans
	// `className`. Le supprimer ferait perdre l'édition de valeurs existantes.
	const AvancePanel = (
		<>
			<SelectControl
				label={ __( 'Élément HTML', 'g2rd' ) }
				value={ htmlTag }
				options={ HTML_TAGS }
				onChange={ ( v ) => setAttributes( { htmlTag: v } ) }
				__next40pxDefaultSize
				__nextHasNoMarginBottom
			/>
			<TextControl
				label={ __( 'Classe CSS G2RD', 'g2rd' ) }
				help={ __( 'Classe appliquée par le thème, en plus des classes CSS additionnelles de WordPress.', 'g2rd' ) }
				value={ customCSSClass }
				onChange={ ( v ) => setAttributes( { customCSSClass: v } ) }
				placeholder="ma-classe"
				__next40pxDefaultSize
				__nextHasNoMarginBottom
			/>
		</>
	);

	// ── Rendu final ───────────────────────────────────────────────────────────
	// Onglets natifs de WordPress (Réglages / Styles / Avancé) : plus de TabPanel
	// maison. L'ordre des panneaux suit le vocabulaire partagé de la spec.
	return (
		<>
			<InspectorControls>
				{ MiseEnPagePanel }
				{ ComportementPanel }
				{ VisibilitePanel }
			</InspectorControls>

			<InspectorControls group="styles">
				{ ArrierePlanPanel }
				{ DimensionsPanel }
				{ BordurePanel }
				{ AnimationPanel }
			</InspectorControls>

			<InspectorControls group="advanced">
				{ AvancePanel }
			</InspectorControls>

			<Tag { ...innerBlocksProps } />
		</>
	);
}
