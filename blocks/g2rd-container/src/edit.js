import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
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
	ButtonGroup,
	TextControl,
	TabPanel,
	__experimentalUnitControl as UnitControl,
} from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';

// ─── Constantes ───────────────────────────────────────────────────────────────

const LAYOUT_TYPES = [
	{ value: 'flex',        label: __( 'Flex',      'g2rd' ) },
	{ value: 'grid',        label: __( 'Grille',    'g2rd' ) },
	{ value: 'constrained', label: __( 'Contraint', 'g2rd' ) },
	{ value: 'flow',        label: __( 'Flux',      'g2rd' ) },
];

const FLEX_JUSTIFY = [
	{ value: 'flex-start',    label: '⇤',  title: __( 'Début', 'g2rd' ) },
	{ value: 'center',        label: '↔',  title: __( 'Centre', 'g2rd' ) },
	{ value: 'flex-end',      label: '⇥',  title: __( 'Fin', 'g2rd' ) },
	{ value: 'space-between', label: '⟺',  title: __( 'Espace entre', 'g2rd' ) },
	{ value: 'space-evenly',  label: '⟸⟹', title: __( 'Espace égal', 'g2rd' ) },
	{ value: 'stretch',       label: '↹',  title: __( 'Étirer', 'g2rd' ) },
];

const FLEX_ALIGN = [
	{ value: 'flex-start', label: '↑', title: __( 'Début', 'g2rd' ) },
	{ value: 'center',     label: '⊕', title: __( 'Centre', 'g2rd' ) },
	{ value: 'flex-end',   label: '↓', title: __( 'Fin', 'g2rd' ) },
	{ value: 'stretch',    label: '⤢', title: __( 'Étirer', 'g2rd' ) },
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

function DeviceSwitcher( { value, onChange } ) {
	return (
		<div className="g2rd-device-switcher">
			{ [ 'desktop', 'tablet', 'mobile' ].map( ( d ) => (
				<button
					key={ d }
					className={ `g2rd-device-btn ${ value === d ? 'is-active' : '' }` }
					onClick={ () => onChange( d ) }
					title={ d }
					type="button"
				>
					{ d === 'desktop' ? '🖥' : d === 'tablet' ? '📱' : '📲' }
				</button>
			) ) }
		</div>
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

	// Helper : set un attribut responsive selon l'appareil actif
	const setResponsive = ( base, value ) => {
		setAttributes( { [ getAttrKey( base, device ) ]: value } );
	};
	const getResponsive = ( base ) => attributes[ getAttrKey( base, device ) ] ?? '';

	// ── Rendu du panneau Mise en page ──────────────────────────────────────────
	const LayoutPanel = (
		<>
			{ /* Sélecteur de type de layout */ }
			<PanelBody title={ __( 'Type de layout', 'g2rd' ) } initialOpen={ true }>
				<ButtonGroup className="g2rd-layout-type-group">
					{ LAYOUT_TYPES.map( ( t ) => (
						<Button
							key={ t.value }
							variant={ layoutType === t.value ? 'primary' : 'secondary' }
							onClick={ () => setAttributes( { layoutType: t.value } ) }
						>
							{ t.label }
						</Button>
					) ) }
				</ButtonGroup>
			</PanelBody>

			{ /* Contrôles Flex */ }
			{ layoutType === 'flex' && (
				<PanelBody title={ __( 'Flex', 'g2rd' ) } initialOpen={ true }>
					<DeviceSwitcher value={ device } onChange={ setDevice } />

					<p className="g2rd-control-label">{ __( 'Direction', 'g2rd' ) }</p>
					<ButtonGroup>
						{ [ { value: 'row', label: '→' }, { value: 'column', label: '↓' } ].map( ( d ) => (
							<Button
								key={ d.value }
								variant={ getResponsive( 'flexDirection' ) === d.value ? 'primary' : 'secondary' }
								onClick={ () => setResponsive( 'flexDirection', d.value ) }
							>
								{ d.label }
							</Button>
						) ) }
					</ButtonGroup>

					<p className="g2rd-control-label">{ __( 'Justification', 'g2rd' ) }</p>
					<ButtonGroup>
						{ FLEX_JUSTIFY.map( ( j ) => (
							<Button
								key={ j.value }
								variant={ getResponsive( 'flexJustify' ) === j.value ? 'primary' : 'secondary' }
								onClick={ () => setResponsive( 'flexJustify', j.value ) }
								title={ j.title }
							>
								{ j.label }
							</Button>
						) ) }
					</ButtonGroup>

					<p className="g2rd-control-label">{ __( 'Alignement', 'g2rd' ) }</p>
					<ButtonGroup>
						{ FLEX_ALIGN.map( ( a ) => (
							<Button
								key={ a.value }
								variant={ getResponsive( 'flexAlign' ) === a.value ? 'primary' : 'secondary' }
								onClick={ () => setResponsive( 'flexAlign', a.value ) }
								title={ a.title }
							>
								{ a.label }
							</Button>
						) ) }
					</ButtonGroup>

					<ToggleControl
						label={ __( 'Passage sur plusieurs lignes (wrap)', 'g2rd' ) }
						checked={ !! attributes[ getAttrKey( 'flexWrap', device ) ] }
						onChange={ ( v ) => setResponsive( 'flexWrap', v ) }
						__nextHasNoMarginBottom={ true }
					/>
					<UnitControl
						label={ __( 'Espacement (gap)', 'g2rd' ) }
						value={ getResponsive( 'flexGap' ) || flexGap }
						onChange={ ( v ) => setResponsive( 'flexGap', v ) }
						units={ [ { value: 'px', label: 'px' }, { value: 'em', label: 'em' }, { value: 'rem', label: 'rem' } ] }
					/>
				</PanelBody>
			) }

			{ /* Contrôles Grille */ }
			{ layoutType === 'grid' && (
				<PanelBody title={ __( 'Grille', 'g2rd' ) } initialOpen={ true }>
					<DeviceSwitcher value={ device } onChange={ setDevice } />
					<RangeControl
						label={ __( 'Colonnes', 'g2rd' ) }
						value={ device === 'mobile' ? gridColumnsMobile : device === 'tablet' ? gridColumnsTablet : gridColumns }
						onChange={ ( v ) => {
							const key = device === 'mobile' ? 'gridColumnsMobile' : device === 'tablet' ? 'gridColumnsTablet' : 'gridColumns';
							setAttributes( { [ key ]: v } );
						} }
						min={ 1 } max={ 12 }
						__next40pxDefaultSize={ true }
						__nextHasNoMarginBottom={ true }
					/>
					<UnitControl
						label={ __( 'Espacement (gap)', 'g2rd' ) }
						value={ getResponsive( 'gridGap' ) || gridGap }
						onChange={ ( v ) => setResponsive( 'gridGap', v ) }
						units={ [ { value: 'px', label: 'px' }, { value: 'em', label: 'em' } ] }
					/>
				</PanelBody>
			) }

			{ /* Contraint */ }
			{ layoutType === 'constrained' && (
				<PanelBody title={ __( 'Largeur maximale', 'g2rd' ) } initialOpen={ true }>
					<UnitControl
						label={ __( 'Max-width du conteneur', 'g2rd' ) }
						value={ constrainedWidth }
						onChange={ ( v ) => setAttributes( { constrainedWidth: v } ) }
						units={ [ { value: 'px', label: 'px' }, { value: '%', label: '%' }, { value: 'vw', label: 'vw' } ] }
					/>
				</PanelBody>
			) }

			{ /* Dimensions */ }
			<PanelBody title={ __( 'Dimensions', 'g2rd' ) } initialOpen={ false }>
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
		</>
	);

	// ── Rendu du panneau Style ─────────────────────────────────────────────────
	const StylePanel = (
		<>
			{ /* Espacement */ }
			<PanelBody title={ __( 'Espacement', 'g2rd' ) } initialOpen={ true }>
				<DeviceSwitcher value={ device } onChange={ setDevice } />
				<p className="g2rd-control-label">{ __( 'Padding', 'g2rd' ) }</p>
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
				<p className="g2rd-control-label">{ __( 'Marge', 'g2rd' ) }</p>
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
			</PanelBody>

			{ /* Fond */ }
			<PanelBody title={ __( 'Fond', 'g2rd' ) } initialOpen={ false }>
				<SelectControl
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
						onChange={ ( v ) =
						__next40pxDefaultSize
						__nextHasNoMarginBottom
onChange={ ( v ) => setAttributes( { bgGradient: v } ) }
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
						<SelectControl
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
							onChange={ ( v ) =
							__next40pxDefaultSize
							__nextHasNoMarginBottom
onChange={ ( v ) => setAttributes( { bgImagePosition: v } ) }
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

			{ /* Bordure */ }
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
				<SelectControl
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
				<SelectControl
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
		</>
	);

	// ── Rendu du panneau Avancé ────────────────────────────────────────────────
	const AdvancedPanel = (
		<>
			{ /* Balise HTML */ }
			<PanelBody title={ __( 'Balise HTML', 'g2rd' ) } initialOpen={ true }>
				<SelectControl
					label={ __( 'Élément HTML', 'g2rd' ) }
					value={ htmlTag }
					options={ HTML_TAGS }
					onChange={ ( v ) => setAttributes( { htmlTag: v } ) }
					__next40pxDefaultSize={ true }
					__nextHasNoMarginBottom={ true }
				/>
			</PanelBody>

			{ /* Visibilité responsive */ }
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

			{ /* Animation d'entrée */ }
			<PanelBody title={ __( "Animation d'entrée", 'g2rd' ) } initialOpen={ false }>
				<SelectControl
					label={ __( 'Animation', 'g2rd' ) }
					value={ animation }
					options={ ANIMATIONS }
					onChange={ ( v ) => setAttributes( { animation: v } ) }
					__next40pxDefaultSize={ true }
					__nextHasNoMarginBottom={ true }
				/>
				{ animation !== 'none' && (
					<>
						<RangeControl
							label={ __( 'Délai (ms)', 'g2rd' ) }
							value={ animationDelay }
							onChange={ ( v ) => setAttributes( { animationDelay: v } ) }
							min={ 0 } max={ 2000 } step={ 50 }
							__next40pxDefaultSize={ true }
							__nextHasNoMarginBottom={ true }
						/>
						<RangeControl
							label={ __( 'Durée (ms)', 'g2rd' ) }
							value={ animationDuration }
							onChange={ ( v ) => setAttributes( { animationDuration: v } ) }
							min={ 100 } max={ 3000 } step={ 50 }
							__next40pxDefaultSize={ true }
							__nextHasNoMarginBottom={ true }
						/>
						<SelectControl
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

			{ /* Classe CSS personnalisée */ }
			<PanelBody title={ __( 'CSS personnalisé', 'g2rd' ) } initialOpen={ false }>
				<TextControl
					label={ __( 'Classe CSS', 'g2rd' ) }
					value={ customCSSClass }
					onChange={ ( v ) =
					__next40pxDefaultSize
					__nextHasNoMarginBottom
onChange={ ( v ) => setAttributes( { customCSSClass: v } ) }
					placeholder="ma-classe"
					__next40pxDefaultSize={ true }
					__nextHasNoMarginBottom={ true }
				/>
			</PanelBody>
		</>
	);

	// ── Rendu final ───────────────────────────────────────────────────────────
	return (
		<>
			<InspectorControls>
				<TabPanel
					tabs={ [
						{ name: 'layout',   title: __( 'Mise en page', 'g2rd' ) },
						{ name: 'style',    title: __( 'Style',        'g2rd' ) },
						{ name: 'advanced', title: __( 'Avancé',       'g2rd' ) },
					] }
				>
					{ ( tab ) => {
						if ( tab.name === 'layout'   ) return LayoutPanel;
						if ( tab.name === 'style'    ) return StylePanel;
						return AdvancedPanel;
					} }
				</TabPanel>
			</InspectorControls>

			<div { ...blockProps }>
				<InnerBlocks
					orientation={ flexDirection === 'column' ? 'vertical' : 'horizontal' }
					renderAppender={ InnerBlocks.ButtonBlockAppender }
				/>
			</div>
		</>
	);
}
