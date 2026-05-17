import { __, sprintf } from '@wordpress/i18n';
import {
	useBlockProps,
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
} from '@wordpress/block-editor';
import {
	PanelBody,
	Button,
	TextControl,
	RangeControl,
	ToggleControl,
	SelectControl,
	ColorPicker,
} from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import './editor.css';

export default function Edit( { attributes, setAttributes, clientId } ) {
	const {
		blockId,
		images,
		scrollDistance,
		renderMode,
		imageWidth,
		imageHeight,
		minHeight,
		backgroundColor,
		showOverlayText,
		overlayText,
		overlayTextColor,
		overlayTextSize,
		overlayTextWeight,
		overlayPosition,
		overlayFade,
	} = attributes;

	useEffect( () => {
		if ( ! blockId ) {
			setAttributes( { blockId: `ps-${ clientId.slice( 0, 8 ) }` } );
		}
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps

	const blockProps = useBlockProps( {
		className: 'g2rd-pin-scroll-editor',
		style: {
			backgroundColor,
			minHeight,
			position: 'relative',
			overflow: 'hidden',
		},
	} );

	const onSelectImages = ( media ) => {
		setAttributes( {
			images: media.map( ( img ) => ( {
				id:  img.id,
				url: img.url,
				alt: img.alt || '',
			} ) ),
		} );
	};

	const firstImage = images.length > 0 ? images[ 0 ] : null;

	return (
		<>
			<InspectorControls>

				<PanelBody title={ __( 'Séquence d\'images', 'g2rd' ) } initialOpen={ true }>
					<MediaUploadCheck>
						<MediaUpload
							onSelect={ onSelectImages }
							allowedTypes={ [ 'image' ] }
							multiple
							gallery
							value={ images.map( ( img ) => img.id ) }
							render={ ( { open } ) => (
								<Button
									onClick={ open }
									variant={ images.length === 0 ? 'primary' : 'secondary' }
									style={ { width: '100%', justifyContent: 'center', marginBottom: '8px' } }
								>
									{ images.length === 0
										? __( 'Choisir les images de la séquence', 'g2rd' )
										: sprintf( __( 'Modifier (%d images)', 'g2rd' ), images.length )
									}
								</Button>
							) }
						/>
					</MediaUploadCheck>
					{ images.length > 0 && (
						<p style={ { fontSize: '12px', color: '#757575', margin: '0 0 8px' } }>
							{ sprintf(
								__( '%d images chargées. La première s\'affiche immédiatement, les autres en préchargement.', 'g2rd' ),
								images.length
							) }
						</p>
					) }
					{ images.length > 0 && (
						<Button
							isDestructive
							variant="tertiary"
							size="small"
							onClick={ () => setAttributes( { images: [] } ) }
						>
							{ __( 'Supprimer toutes les images', 'g2rd' ) }
						</Button>
					) }
				</PanelBody>

				<PanelBody title={ __( 'Animation', 'g2rd' ) } initialOpen={ false }>
					<RangeControl __next40pxDefaultSize __nextHasNoMarginBottom
						label={ __( 'Distance de défilement (px)', 'g2rd' ) }
						value={ scrollDistance }
						onChange={ ( v ) => setAttributes( { scrollDistance: v } ) }
						min={ 500 }
						max={ 10000 }
						step={ 100 }
						help={ __( 'Distance de scroll pendant laquelle le bloc reste épinglé et la séquence s\'anime.', 'g2rd' ) }
						__nextHasNoMarginBottom
					/>
				</PanelBody>

				<PanelBody title={ __( 'Rendu', 'g2rd' ) } initialOpen={ false }>
					<SelectControl __next40pxDefaultSize __nextHasNoMarginBottom
						label={ __( 'Mode de rendu', 'g2rd' ) }
						value={ renderMode }
						options={ [
							{ label: __( 'Canvas (recommandé — plus fluide)', 'g2rd' ), value: 'canvas' },
							{ label: __( 'Image (plus flexible)', 'g2rd' ), value: 'image' },
						] }
						onChange={ ( v ) => setAttributes( { renderMode: v } ) }
						help={ renderMode === 'canvas'
							? __( 'Nécessite de définir les dimensions exactes de l\'image source.', 'g2rd' )
							: __( 'Adapte automatiquement l\'image au conteneur.', 'g2rd' )
						}
						__nextHasNoMarginBottom
					/>
					{ renderMode === 'canvas' && (
						<>
							<TextControl
								label={ __( 'Largeur image source (px)', 'g2rd' ) }
								type="number"
								value={ imageWidth }
								onChange={ ( v ) => setAttributes( { imageWidth: parseInt( v, 10 ) || 1920 } ) }
								style={ { marginTop: '8px' } }
								__next40pxDefaultSize
								__nextHasNoMarginBottom
							/>
							<TextControl
								label={ __( 'Hauteur image source (px)', 'g2rd' ) }
								type="number"
								value={ imageHeight }
								onChange={ ( v ) => setAttributes( { imageHeight: parseInt( v, 10 ) || 1080 } ) }
								style={ { marginTop: '8px' } }
								__next40pxDefaultSize
								__nextHasNoMarginBottom
							/>
						</>
					) }
					<TextControl
						label={ __( 'Hauteur minimale', 'g2rd' ) }
						value={ minHeight }
						onChange={ ( v ) => setAttributes( { minHeight: v } ) }
						placeholder="100vh"
						style={ { marginTop: '8px' } }
						help={ __( 'Ex : 100vh, 600px. Zone visible pendant le pin.', 'g2rd' ) }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<div style={ { marginTop: '12px' } }>
						<label style={ { display: 'block', marginBottom: '6px', fontSize: '11px', fontWeight: '500', color: '#1e1e1e' } }>
							{ __( 'Couleur de fond', 'g2rd' ) }
						</label>
						<ColorPicker
							color={ backgroundColor }
							onChange={ ( v ) => setAttributes( { backgroundColor: v } ) }
							enableAlpha={ false }
						/>
					</div>
				</PanelBody>

				<PanelBody title={ __( 'Texte superposé', 'g2rd' ) } initialOpen={ false }>
					<ToggleControl
						label={ __( 'Afficher un texte pendant la séquence', 'g2rd' ) }
						checked={ showOverlayText }
						onChange={ ( v ) => setAttributes( { showOverlayText: v } ) }
						__nextHasNoMarginBottom
					/>
					{ showOverlayText && (
						<>
							<TextControl
								label={ __( 'Texte', 'g2rd' ) }
								value={ overlayText }
								onChange={ ( v ) => setAttributes( { overlayText: v } ) }
								placeholder={ __( 'Conçu pour l\'excellence.', 'g2rd' ) }
								style={ { marginTop: '8px' } }
								__next40pxDefaultSize
								__nextHasNoMarginBottom
							/>
							<RangeControl __next40pxDefaultSize __nextHasNoMarginBottom
								label={ __( 'Apparition (% du scroll)', 'g2rd' ) }
								value={ overlayPosition }
								onChange={ ( v ) => setAttributes( { overlayPosition: v } ) }
								min={ 0 }
								max={ 80 }
								step={ 5 }
								style={ { marginTop: '8px' } }
								help={ __( 'Ex : 30 = le texte apparaît après 30% du scroll épinglé.', 'g2rd' ) }
								__nextHasNoMarginBottom
							/>
							<ToggleControl
								label={ __( 'Effet fondu enchaîné (intro + outro)', 'g2rd' ) }
								checked={ overlayFade }
								onChange={ ( v ) => setAttributes( { overlayFade: v } ) }
								style={ { marginTop: '8px' } }
								__nextHasNoMarginBottom
							/>
							<RangeControl __next40pxDefaultSize __nextHasNoMarginBottom
								label={ __( 'Taille du texte (px)', 'g2rd' ) }
								value={ overlayTextSize }
								onChange={ ( v ) => setAttributes( { overlayTextSize: v } ) }
								min={ 12 }
								max={ 120 }
								step={ 2 }
								style={ { marginTop: '8px' } }
								__nextHasNoMarginBottom
							/>
							<SelectControl __next40pxDefaultSize __nextHasNoMarginBottom
								label={ __( 'Graisse du texte', 'g2rd' ) }
								value={ overlayTextWeight }
								options={ [
									{ label: __( 'Normal (400)', 'g2rd' ), value: '400' },
									{ label: __( 'Medium (500)', 'g2rd' ), value: '500' },
									{ label: __( 'Semi-bold (600)', 'g2rd' ), value: '600' },
									{ label: __( 'Gras (700)', 'g2rd' ), value: '700' },
									{ label: __( 'Extra gras (800)', 'g2rd' ), value: '800' },
								] }
								onChange={ ( v ) => setAttributes( { overlayTextWeight: v } ) }
								style={ { marginTop: '8px' } }
								__nextHasNoMarginBottom
							/>
							<div style={ { marginTop: '12px' } }>
								<label style={ { display: 'block', marginBottom: '6px', fontSize: '11px', fontWeight: '500', color: '#1e1e1e' } }>
									{ __( 'Couleur du texte', 'g2rd' ) }
								</label>
								<ColorPicker
									color={ overlayTextColor }
									onChange={ ( v ) => setAttributes( { overlayTextColor: v } ) }
									enableAlpha={ false }
								/>
							</div>
						</>
					) }
				</PanelBody>

			</InspectorControls>

			<div { ...blockProps }>
				{ firstImage ? (
					<>
						<img
							src={ firstImage.url }
							alt={ firstImage.alt }
							className="g2rd-pin-scroll-editor__preview"
						/>
						<div className="g2rd-pin-scroll-editor__badge">
							<span className="dashicons dashicons-video-alt2"></span>
							{ sprintf(
								__( 'Pin Scroll — %d images · %d px scroll', 'g2rd' ),
								images.length,
								scrollDistance
							) }
						</div>
						{ showOverlayText && overlayText && (
							<div
								className="g2rd-pin-scroll-editor__overlay-preview"
								style={ {
									color:      overlayTextColor,
									fontSize:   overlayTextSize + 'px',
									fontWeight: overlayTextWeight,
									bottom:     ( 100 - overlayPosition ) + '%',
								} }
							>
								{ overlayText }
							</div>
						) }
					</>
				) : (
					<div className="g2rd-pin-scroll-editor__placeholder">
						<MediaUploadCheck>
							<MediaUpload
								onSelect={ onSelectImages }
								allowedTypes={ [ 'image' ] }
								multiple
								gallery
								render={ ( { open } ) => (
									<button
										type="button"
										onClick={ open }
										className="g2rd-pin-scroll-editor__placeholder-btn"
									>
										<span
											className="dashicons dashicons-video-alt2"
											style={ { fontSize: '48px', width: '48px', height: '48px', color: '#888' } }
										></span>
										<strong>{ __( 'G2RD Pin Scroll', 'g2rd' ) }</strong>
										<span>{ __( 'Cliquer pour importer la séquence d\'images', 'g2rd' ) }</span>
										<small>{ __( 'Sélectionner les images dans l\'ordre : première en premier.', 'g2rd' ) }</small>
									</button>
								) }
							/>
						</MediaUploadCheck>
					</div>
				) }
			</div>
		</>
	);
}
