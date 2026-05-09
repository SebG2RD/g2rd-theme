/**
 * G2RD Titre avancé — composant éditeur
 */
import { __ } from "@wordpress/i18n";
import {
	useBlockProps,
	InspectorControls,
	BlockControls,
	AlignmentToolbar,
	RichText,
	MediaUpload,
	MediaUploadCheck,
	PanelColorSettings,
} from "@wordpress/block-editor";
import {
	PanelBody,
	TextControl,
	SelectControl,
	RangeControl,
	ToggleControl,
	Button,
	ColorPalette,
} from "@wordpress/components";
import { useRef, useEffect } from "@wordpress/element";

// ─────────────────────────────────────────────
// Utilitaires
// ─────────────────────────────────────────────

function buildTextShadow( attrs ) {
	const parts = [];
	if ( attrs.shadow1Enabled ) {
		parts.push( `${ attrs.shadow1X }px ${ attrs.shadow1Y }px ${ attrs.shadow1Blur }px ${ attrs.shadow1Color }` );
	}
	if ( attrs.shadow2Enabled ) {
		parts.push( `${ attrs.shadow2X }px ${ attrs.shadow2Y }px ${ attrs.shadow2Blur }px ${ attrs.shadow2Color }` );
	}
	if ( attrs.shadow3Enabled ) {
		parts.push( `${ attrs.shadow3X }px ${ attrs.shadow3Y }px ${ attrs.shadow3Blur }px ${ attrs.shadow3Color }` );
	}
	return parts.join( ", " );
}

function buildHeadingStyle( attrs ) {
	const s = {};
	if ( attrs.textColor )      s.color        = attrs.textColor;
	if ( attrs.fontSizeValue )  s.fontSize     = attrs.fontSizeValue;
	if ( attrs.fontWeight )     s.fontWeight   = attrs.fontWeight;
	if ( attrs.lineHeight )     s.lineHeight   = attrs.lineHeight;
	if ( attrs.letterSpacing )  s.letterSpacing = attrs.letterSpacing;
	const shadow = buildTextShadow( attrs );
	if ( shadow )               s.textShadow   = shadow;
	return s;
}

function buildAnimatedStyle( attrs ) {
	const s = {};
	if ( attrs.clipMaskEnabled && attrs.clipMaskUrl ) {
		s.backgroundImage      = `url(${ attrs.clipMaskUrl })`;
		s.backgroundClip       = "text";
		s.WebkitBackgroundClip = "text";
		s.color                = "transparent";
		s.WebkitTextFillColor  = "transparent";
		s.backgroundSize       = "cover";
		s.backgroundPosition   = "center";
	} else {
		if ( attrs.animatedColor )
			s.color = attrs.animatedColor;
	}
	if ( attrs.animatedFontWeight && attrs.animatedFontWeight !== "inherit" )
		s.fontWeight = attrs.animatedFontWeight;
	return s;
}

// ─────────────────────────────────────────────
// Sous-composant : panneau d'ombre
// ─────────────────────────────────────────────

function ShadowRow( { index, attrs, set } ) {
	const eKey = `shadow${ index }Enabled`;
	const cKey = `shadow${ index }Color`;
	const xKey = `shadow${ index }X`;
	const yKey = `shadow${ index }Y`;
	const bKey = `shadow${ index }Blur`;

	return (
		<div style={ { borderTop: "1px solid #e0e0e0", paddingTop: "12px", marginTop: "12px" } }>
			<ToggleControl
				label={ `${ __( "Ombre", "g2rd" ) } ${ index }` }
				checked={ attrs[ eKey ] }
				onChange={ ( v ) => set( { [ eKey ]: v } ) }
				__nextHasNoMarginBottom
			/>
			{ attrs[ eKey ] && (
				<>
					<p style={{ fontSize: "11px", fontWeight: 600, margin: "8px 0 4px", textTransform: "uppercase" }}>
					{ __( "Couleur", "g2rd" ) }
				</p>
				<ColorPalette
					value={ attrs[ cKey ] }
					onChange={ ( v ) => set( { [ cKey ]: v || "" } ) }
					clearable={ true }
					__experimentalIsRenderedInSidebar
				/>
					<RangeControl label="X (px)" value={ attrs[ xKey ] } onChange={ ( v ) => set( { [ xKey ]: v } ) } min={ -20 } max={ 20 } __nextHasNoMarginBottom />
					<RangeControl label="Y (px)" value={ attrs[ yKey ] } onChange={ ( v ) => set( { [ yKey ]: v } ) } min={ -20 } max={ 20 } __nextHasNoMarginBottom />
					<RangeControl label={ __( "Flou (px)", "g2rd" ) } value={ attrs[ bKey ] } onChange={ ( v ) => set( { [ bKey ]: v } ) } min={ 0 } max={ 40 } __nextHasNoMarginBottom />
				</>
			) }
		</div>
	);
}

// ─────────────────────────────────────────────
// Composant principal Edit
// ─────────────────────────────────────────────

export default function Edit( { attributes, setAttributes } ) {
	const {
		textBefore, textAfter, animatedWords, headingTag,
		alignment, animationEffect, animationSpeed,
		textColor, animatedColor, animatedFontWeight,
		fontSizeValue, fontWeight, lineHeight, letterSpacing,
		highlightEnabled, highlightBgColor, highlightTextColor, highlightPadding, highlightBorderRadius,
		decoratorType, decoratorColor, decoratorSize,
		numberValue, numberBgColor, numberTextColor, numberSize,
		clipMaskEnabled, clipMaskUrl, clipMaskId,
	} = attributes;

	const TagName      = headingTag || "h2";
	const headingStyle = buildHeadingStyle( attributes );
	const animStyle    = buildAnimatedStyle( attributes );

	// ── Ref pour piloter l'animation dans l'éditeur
	const wrapRef = useRef( null );

	useEffect( () => {
		if ( ! wrapRef.current ) return;

		const ANIM_MS = 400;
		const words   = Array.from( wrapRef.current.querySelectorAll( ".g2rd-adv-heading__word" ) );
		const wrap    = wrapRef.current.querySelector( ".g2rd-adv-heading__animated-wrap" );

		if ( ! wrap || words.length <= 1 ) {
			if ( words[ 0 ] ) {
				words[ 0 ].style.opacity  = "1";
				words[ 0 ].style.position = "relative";
			}
			return;
		}

		if ( animationEffect === "rotation" ) wrap.style.perspective = "400px";
		wrap.style.display       = "inline-block";
		wrap.style.position      = "relative";
		wrap.style.overflow      = "hidden";
		wrap.style.verticalAlign = "middle";

		words.forEach( ( w ) => {
			w.style.position   = "absolute";
			w.style.top        = "0";
			w.style.left       = "0";
			w.style.visibility = "hidden";
			w.style.whiteSpace = "nowrap";
		} );

		wrap.offsetWidth; // force reflow

		let maxW = 0, maxH = 0;
		words.forEach( ( w ) => {
			const r = w.getBoundingClientRect();
			if ( r.width  > maxW ) maxW = r.width;
			if ( r.height > maxH ) maxH = r.height;
		} );

		wrap.style.width  = maxW + "px";
		wrap.style.height = maxH + "px";

		const setState = ( el, state ) => {
			el.style.transition = state === "visible"
				? `opacity ${ ANIM_MS }ms ease, transform ${ ANIM_MS }ms cubic-bezier(0.23,1,0.32,1), clip-path ${ ANIM_MS }ms ease`
				: "none";
			el.style.opacity   = "";
			el.style.transform = "";
			el.style.clipPath  = "";

			if ( state === "visible" ) {
				el.style.opacity   = "1";
				el.style.transform = "none";
				el.style.clipPath  = "none";
				return;
			}
			const isBefore = state === "before";
			if ( animationEffect === "sliding" ) {
				el.style.opacity   = "0";
				el.style.transform = `translateY(${ isBefore ? "100%" : "-100%" })`;
			} else if ( animationEffect === "pushing" ) {
				el.style.opacity   = "0";
				el.style.transform = `translateX(${ isBefore ? "100%" : "-100%" })`;
			} else if ( animationEffect === "rotation" ) {
				el.style.opacity   = "0";
				el.style.transform = `rotateX(${ isBefore ? "90deg" : "-90deg" })`;
			} else if ( animationEffect === "zoom" ) {
				el.style.opacity   = "0";
				el.style.transform = `scale(${ isBefore ? "0.4" : "1.6" })`;
			} else if ( animationEffect === "loader" ) {
				el.style.opacity  = "1";
				el.style.clipPath = isBefore ? "inset(0 100% 0 0)" : "inset(0 0 0 100%)";
			} else {
				el.style.opacity = "0";
			}
		};

		words.forEach( ( w, i ) => {
			w.style.visibility = "";
			setState( w, i === 0 ? "visible" : "before" );
		} );

		let current = 0;
		const id = setInterval( () => {
			const leaving  = words[ current ];
			current        = ( current + 1 ) % words.length;
			const entering = words[ current ];

			setState( leaving, "after" );
			entering.style.transition = "none";
			setState( entering, "before" );

			requestAnimationFrame( () => {
				requestAnimationFrame( () => setState( entering, "visible" ) );
			} );
		}, animationSpeed );

		return () => clearInterval( id );
	}, [ animationEffect, animationSpeed, animatedWords.join( "," ) ] );

	const highlightStyle = highlightEnabled ? {
		background:    highlightBgColor,
		color:         highlightTextColor,
		padding:       `2px ${ highlightPadding }px`,
		borderRadius:  `${ highlightBorderRadius }px`,
		display:       "inline",
	} : {};

	const decoratorCss = decoratorType !== "none" ? {
		"--g2rd-dec-color": decoratorColor || "var(--wp--preset--color--primary, #2f425d)",
		"--g2rd-dec-size":  `${ decoratorSize }px`,
	} : {};

	const blockProps = useBlockProps( {
		className: "g2rd-adv-heading-wrap",
		style:     { textAlign: alignment },
		ref:       wrapRef,
	} );

	// ── Gestion des mots animés ──
	const updateWord  = ( i, v ) => { const w = [ ...animatedWords ]; w[ i ] = v; setAttributes( { animatedWords: w } ); };
	const addWord     = () => setAttributes( { animatedWords: [ ...animatedWords, __( "nouveau mot", "g2rd" ) ] } );
	const removeWord  = ( i ) => animatedWords.length > 1 && setAttributes( { animatedWords: animatedWords.filter( ( _, j ) => j !== i ) } );

	return (
		<>
			{ /* ──────────── Contrôles inspecteur ──────────── */ }
			<InspectorControls>

				{ /* Contenu */ }
				<PanelBody title={ __( "Contenu", "g2rd" ) } initialOpen={ true }>
					<SelectControl
						label={ __( "Balise HTML", "g2rd" ) }
						value={ headingTag }
						options={ [ "h1","h2","h3","h4","h5","h6","p" ].map( t => ( { label: t.toUpperCase(), value: t } ) ) }
						onChange={ ( v ) => setAttributes( { headingTag: v } ) }
					/>
					<p style={{ fontSize: "12px", color: "#757575", marginBottom: "8px" }}>
						{ __( "Éditez le texte avant/après directement sur le bloc.", "g2rd" ) }
					</p>
					<div style={ { margin: "12px 0 8px" } }>
						<p style={ { margin: "0 0 6px", fontSize: "11px", fontWeight: 600, textTransform: "uppercase", color: "#1e1e1e" } }>
							{ __( "Mots animés", "g2rd" ) }
						</p>
						{ animatedWords.map( ( word, i ) => (
							<div key={ i } style={ { display: "flex", gap: "4px", marginBottom: "4px", alignItems: "center" } }>
								<input
									type="text"
									value={ word }
									onChange={ ( e ) => updateWord( i, e.target.value ) }
									style={ { flex: 1, padding: "4px 8px", border: "1px solid #ddd", borderRadius: "4px", fontSize: "13px" } }
								/>
								<Button
									isDestructive
									size="small"
									onClick={ () => removeWord( i ) }
									disabled={ animatedWords.length <= 1 }
									icon="trash"
									label={ __( "Supprimer", "g2rd" ) }
								/>
							</div>
						) ) }
						<Button variant="secondary" size="small" onClick={ addWord } icon="plus" style={ { marginTop: "4px" } }>
							{ __( "Ajouter un mot", "g2rd" ) }
						</Button>
					</div>
				</PanelBody>

				{ /* Animation */ }
				<PanelBody title={ __( "Animation", "g2rd" ) } initialOpen={ false }>
					<SelectControl
						label={ __( "Effet d'animation", "g2rd" ) }
						value={ animationEffect }
						options={ [
							{ label: __( "Glissement vertical (Sliding)",  "g2rd" ), value: "sliding"  },
							{ label: __( "Poussée horizontale (Pushing)",   "g2rd" ), value: "pushing"  },
							{ label: __( "Révélation (Loader)",            "g2rd" ), value: "loader"   },
							{ label: __( "Rotation 3D (Rotation)",         "g2rd" ), value: "rotation" },
							{ label: __( "Zoom (Zoom)",                    "g2rd" ), value: "zoom"     },
						] }
						onChange={ ( v ) => setAttributes( { animationEffect: v } ) }
					/>
					<RangeControl
						label={ __( "Durée par mot (ms)", "g2rd" ) }
						value={ animationSpeed }
						onChange={ ( v ) => setAttributes( { animationSpeed: v } ) }
						min={ 500 } max={ 8000 } step={ 100 }
						__nextHasNoMarginBottom
					/>
				</PanelBody>

				{ /* Typographie & Couleurs */ }
				<PanelColorSettings
					title={ __( "Couleurs", "g2rd" ) }
					initialOpen={ false }
					colorSettings={ [
						{
							value: textColor,
							onChange: ( v ) => setAttributes( { textColor: v || "" } ),
							label: __( "Texte statique", "g2rd" ),
						},
						{
							value: animatedColor,
							onChange: ( v ) => setAttributes( { animatedColor: v || "" } ),
							label: __( "Mots animés", "g2rd" ),
						},
					] }
				/>
				<PanelBody title={ __( "Typographie", "g2rd" ) } initialOpen={ false }>
					<SelectControl
						label={ __( "Graisse — mots animés", "g2rd" ) }
						value={ animatedFontWeight }
						options={ [
							{ label: __( "Hériter",    "g2rd" ), value: "inherit" },
							{ label: "300 — Light",              value: "300" },
							{ label: "400 — Normal",             value: "400" },
							{ label: "600 — Semi-bold",          value: "600" },
							{ label: "700 — Bold",               value: "700" },
							{ label: "800 — Extra-bold",         value: "800" },
							{ label: "900 — Black",              value: "900" },
						] }
						onChange={ ( v ) => setAttributes( { animatedFontWeight: v } ) }
					/>
					<TextControl
						label={ __( "Taille de police (ex : 3rem, 48px)", "g2rd" ) }
						value={ fontSizeValue }
						onChange={ ( v ) => setAttributes( { fontSizeValue: v } ) }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<SelectControl
						label={ __( "Graisse globale", "g2rd" ) }
						value={ fontWeight }
						options={ [ "300","400","600","700","800","900" ].map( w => ( { label: w, value: w } ) ) }
						onChange={ ( v ) => setAttributes( { fontWeight: v } ) }
					/>
					<TextControl
						label={ __( "Hauteur de ligne (ex : 1.3)", "g2rd" ) }
						value={ lineHeight }
						onChange={ ( v ) => setAttributes( { lineHeight: v } ) }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<TextControl
						label={ __( "Espacement lettres (ex : -0.02em)", "g2rd" ) }
						value={ letterSpacing }
						onChange={ ( v ) => setAttributes( { letterSpacing: v } ) }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
				</PanelBody>

				{ /* Mise en évidence */ }
				<PanelBody title={ __( "Mise en évidence", "g2rd" ) } initialOpen={ false }>
					<ToggleControl
						label={ __( "Mettre en évidence les mots animés", "g2rd" ) }
						checked={ highlightEnabled }
						onChange={ ( v ) => setAttributes( { highlightEnabled: v } ) }
						__nextHasNoMarginBottom
					/>
					{ highlightEnabled && (
						<>
							<p style={{ fontSize: "11px", fontWeight: 600, margin: "8px 0 4px", textTransform: "uppercase" }}>{ __( "Fond", "g2rd" ) }</p>
							<ColorPalette value={ highlightBgColor } onChange={ ( v ) => setAttributes( { highlightBgColor: v || "" } ) } clearable __experimentalIsRenderedInSidebar />
							<p style={{ fontSize: "11px", fontWeight: 600, margin: "8px 0 4px", textTransform: "uppercase" }}>{ __( "Texte", "g2rd" ) }</p>
							<ColorPalette value={ highlightTextColor } onChange={ ( v ) => setAttributes( { highlightTextColor: v || "" } ) } clearable __experimentalIsRenderedInSidebar />
							<RangeControl label={ __( "Padding horizontal (px)", "g2rd" ) } value={ highlightPadding }      onChange={ ( v ) => setAttributes( { highlightPadding: v } ) }      min={ 0 } max={ 40 } __nextHasNoMarginBottom />
							<RangeControl label={ __( "Arrondi (px)", "g2rd" ) }            value={ highlightBorderRadius } onChange={ ( v ) => setAttributes( { highlightBorderRadius: v } ) } min={ 0 } max={ 50 } __nextHasNoMarginBottom />
						</>
					) }
				</PanelBody>

				{ /* Élément décoratif */ }
				<PanelBody title={ __( "Élément décoratif", "g2rd" ) } initialOpen={ false }>
					<SelectControl
						label={ __( "Type de décoration", "g2rd" ) }
						value={ decoratorType }
						options={ [
							{ label: __( "Aucun",                  "g2rd" ), value: "none"   },
							{ label: __( "Soulignement zigzag",    "g2rd" ), value: "zigzag" },
							{ label: __( "Soulignement / bordure", "g2rd" ), value: "border" },
							{ label: __( "Cercle autour du texte", "g2rd" ), value: "circle" },
							{ label: __( "Badge numéroté",         "g2rd" ), value: "number" },
						] }
						onChange={ ( v ) => setAttributes( { decoratorType: v } ) }
					/>
					{ decoratorType !== "none" && (
						<>
							<p style={{ fontSize: "11px", fontWeight: 600, margin: "8px 0 4px", textTransform: "uppercase" }}>{ __( "Couleur de la décoration", "g2rd" ) }</p>
							<ColorPalette value={ decoratorColor } onChange={ ( v ) => setAttributes( { decoratorColor: v || "" } ) } clearable __experimentalIsRenderedInSidebar />
						</>
					) }
					{ ( decoratorType === "zigzag" || decoratorType === "border" || decoratorType === "circle" ) && (
						<RangeControl label={ __( "Épaisseur (px)", "g2rd" ) } value={ decoratorSize } onChange={ ( v ) => setAttributes( { decoratorSize: v } ) } min={ 1 } max={ 10 } __nextHasNoMarginBottom />
					) }
					{ decoratorType === "number" && (
						<>
							<RangeControl label={ __( "Numéro affiché", "g2rd" ) } value={ numberValue } onChange={ ( v ) => setAttributes( { numberValue: v } ) } min={ 0 } max={ 99 } __nextHasNoMarginBottom />
							<RangeControl label={ __( "Taille du badge (px)", "g2rd" ) } value={ numberSize } onChange={ ( v ) => setAttributes( { numberSize: v } ) } min={ 24 } max={ 120 } __nextHasNoMarginBottom />
							<p style={{ fontSize: "11px", fontWeight: 600, margin: "8px 0 4px", textTransform: "uppercase" }}>{ __( "Fond du badge", "g2rd" ) }</p>
							<ColorPalette value={ numberBgColor } onChange={ ( v ) => setAttributes( { numberBgColor: v || "" } ) } clearable __experimentalIsRenderedInSidebar />
							<p style={{ fontSize: "11px", fontWeight: 600, margin: "8px 0 4px", textTransform: "uppercase" }}>{ __( "Couleur du numéro", "g2rd" ) }</p>
							<ColorPalette value={ numberTextColor } onChange={ ( v ) => setAttributes( { numberTextColor: v || "" } ) } clearable __experimentalIsRenderedInSidebar />
						</>
					) }
				</PanelBody>

				{ /* Ombres 3D */ }
				<PanelBody title={ __( "Ombres de texte (3D)", "g2rd" ) } initialOpen={ false }>
					<ShadowRow index={ 1 } attrs={ attributes } set={ setAttributes } />
					<ShadowRow index={ 2 } attrs={ attributes } set={ setAttributes } />
					<ShadowRow index={ 3 } attrs={ attributes } set={ setAttributes } />
				</PanelBody>

				{ /* Masque image */ }
				<PanelBody title={ __( "Masque image (clip-path)", "g2rd" ) } initialOpen={ false }>
					<ToggleControl
						label={ __( "Appliquer un masque image sur les mots animés", "g2rd" ) }
						checked={ clipMaskEnabled }
						onChange={ ( v ) => setAttributes( { clipMaskEnabled: v } ) }
						__nextHasNoMarginBottom
					/>
					{ clipMaskEnabled && (
						<MediaUploadCheck>
							<MediaUpload
								onSelect={ ( media ) => setAttributes( { clipMaskUrl: media.url, clipMaskId: media.id } ) }
								allowedTypes={ [ "image" ] }
								value={ clipMaskId }
								render={ ( { open } ) => (
									<div style={ { marginTop: "8px" } }>
										{ clipMaskUrl && (
											<img src={ clipMaskUrl } alt="" style={ { width: "100%", height: "56px", objectFit: "cover", borderRadius: "4px", marginBottom: "8px" } } />
										) }
										<Button variant="secondary" size="small" onClick={ open }>
											{ clipMaskUrl ? __( "Changer l'image", "g2rd" ) : __( "Choisir une image", "g2rd" ) }
										</Button>
										{ clipMaskUrl && (
											<Button isDestructive size="small" style={ { marginLeft: "8px" } } onClick={ () => setAttributes( { clipMaskUrl: "", clipMaskId: 0 } ) }>
												{ __( "Supprimer", "g2rd" ) }
											</Button>
										) }
									</div>
								) }
							/>
						</MediaUploadCheck>
					) }
				</PanelBody>

			</InspectorControls>

			{ /* ──────────── Toolbar ──────────── */ }
			<BlockControls>
				<AlignmentToolbar
					value={ alignment }
					onChange={ ( v ) => setAttributes( { alignment: v || "left" } ) }
				/>
			</BlockControls>

			{ /* ──────────── Rendu éditeur ──────────── */ }
			<div { ...blockProps }>
				{ decoratorType === "number" && (
					<span
						className="g2rd-adv-heading__number-badge"
						style={ {
							background:  numberBgColor  || "var(--wp--preset--color--primary, #2f425d)",
							color:       numberTextColor || "#ffffff",
							width:       numberSize + "px",
							height:      numberSize + "px",
							fontSize:    Math.round( numberSize * 0.45 ) + "px",
						} }
						aria-hidden="true"
					>
						{ numberValue }
					</span>
				) }

				<TagName className="g2rd-adv-heading" style={ headingStyle }>
					<RichText
						tagName="span"
						className="g2rd-adv-heading__static-before"
						value={ textBefore }
						onChange={ ( v ) => setAttributes( { textBefore: v } ) }
						placeholder={ __( "Texte avant…", "g2rd" ) }
						style={ textBefore ? {} : { opacity: 0.4, minWidth: "60px", display: "inline-block" } }
					/>&nbsp;

					<span
						className={ [
							"g2rd-adv-heading__animated-outer",
							highlightEnabled               ? "has-highlight"                  : "",
							decoratorType !== "none"       ? `has-decorator--${ decoratorType }` : "",
						].filter( Boolean ).join( " " ) }
						style={ {
							...( highlightEnabled ? {
								background:   highlightBgColor,
								color:        highlightTextColor,
								padding:      `2px ${ highlightPadding }px`,
								borderRadius: `${ highlightBorderRadius }px`,
							} : {} ),
							...decoratorCss,
						} }
					>
						<span className="g2rd-adv-heading__animated-wrap" style={ animStyle }>
							{ animatedWords.map( ( word, i ) => (
								<span
									key={ i }
									className={ `g2rd-adv-heading__word${ i === 0 ? " is-visible" : "" }` }
									aria-hidden={ i !== 0 ? "true" : undefined }
								>
									{ word }
								</span>
							) ) }
						</span>
					</span>

					&nbsp;<RichText
						tagName="span"
						className="g2rd-adv-heading__static-after"
						value={ textAfter }
						onChange={ ( v ) => setAttributes( { textAfter: v } ) }
						placeholder={ __( "Texte après…", "g2rd" ) }
						style={ textAfter ? {} : { opacity: 0.4, minWidth: "60px", display: "inline-block" } }
					/>
				</TagName>
			</div>
		</>
	);
}
