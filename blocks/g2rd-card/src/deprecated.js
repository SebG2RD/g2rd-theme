import { useBlockProps, RichText } from "@wordpress/block-editor";

// v1.13.3 : mêmes attributs que la version actuelle
const attrsV3 = {
	mediaType:           { type: "string",  default: "icon" },
	icon:                { type: "string",  default: "dashicons-star-filled" },
	iconSize:            { type: "number",  default: 48 },
	iconColor:           { type: "string",  default: "" },
	iconBgColor:         { type: "string",  default: "" },
	iconBorderRadius:    { type: "number",  default: 50 },
	imageUrl:            { type: "string",  default: "" },
	imageId:             { type: "number",  default: 0 },
	imageAlt:            { type: "string",  default: "" },
	imageWidth:          { type: "number",  default: 80 },
	iconPosition:        { type: "string",  default: "top" },
	alignment:           { type: "string",  default: "center" },
	heading:             { type: "string",  default: "" },
	headingTag:          { type: "string",  default: "h3" },
	subHeading:          { type: "string",  default: "" },
	description:         { type: "string",  default: "" },
	showSeparator:       { type: "boolean", default: false },
	separatorColor:      { type: "string",  default: "" },
	separatorWidth:      { type: "number",  default: 40 },
	separatorHeight:     { type: "number",  default: 3 },
	ctaText:             { type: "string",  default: "" },
	ctaUrl:              { type: "string",  default: "" },
	ctaTarget:           { type: "boolean", default: false },
	ctaAriaLabel:        { type: "string",  default: "" },
	ctaStyle:            { type: "string",  default: "button" },
	ctaBgColor:          { type: "string",  default: "" },
	ctaTextColor:        { type: "string",  default: "" },
	ctaBorderRadius:     { type: "number",  default: 4 },
	headingColor:        { type: "string",  default: "" },
	subHeadingColor:     { type: "string",  default: "" },
	descriptionColor:    { type: "string",  default: "" },
	backgroundColor:     { type: "string",  default: "" },
	borderRadius:        { type: "number",  default: 8 },
	paddingTop:          { type: "string",  default: "24px" },
	paddingRight:        { type: "string",  default: "24px" },
	paddingBottom:       { type: "string",  default: "24px" },
	paddingLeft:         { type: "string",  default: "24px" },
	headingFontSize:     { type: "string",  default: "" },
	subheadingFontSize:  { type: "string",  default: "" },
	descriptionFontSize: { type: "string",  default: "" },
	linkMode:            { type: "string",  default: "cta" },
	showSubHeading:      { type: "boolean", default: true },
	showDescription:     { type: "boolean", default: true },
	mediaGap:            { type: "string",  default: "16px" },
	contentGap:          { type: "string",  default: "8px" },
	alignItems:          { type: "string",  default: "" },
};

// v1.13.1 : sans mediaGap, contentGap, alignItems
const { mediaGap: _mg, contentGap: _cg, alignItems: _ai, ...attrsV2 } = attrsV3;

// pré-typographie : sans les fontSizes, linkMode, showSubHeading, showDescription
const {
	headingFontSize: _hfs,
	subheadingFontSize: _sfs,
	descriptionFontSize: _dfs,
	linkMode: _lm,
	showSubHeading: _ssh,
	showDescription: _sd,
	...attrsV1
} = attrsV2;

export default [
	// ── v1.13.3 → v1.13.4 ──────────────────────────────────────────────────
	// alignItems était une propriété CSS inline (align-items), pas une variable CSS
	{
		attributes: attrsV3,
		save( { attributes } ) {
			const {
				mediaType, icon, iconSize, iconColor, iconBgColor, iconBorderRadius,
				imageUrl, imageAlt, imageWidth, iconPosition, alignment,
				heading, headingTag, subHeading, description,
				showSeparator, separatorColor, separatorWidth, separatorHeight,
				ctaText, ctaUrl, ctaTarget, ctaAriaLabel, ctaStyle,
				ctaBgColor, ctaTextColor, ctaBorderRadius,
				headingColor, subHeadingColor, descriptionColor,
				backgroundColor, borderRadius,
				paddingTop, paddingRight, paddingBottom, paddingLeft,
				headingFontSize, subheadingFontSize, descriptionFontSize,
				linkMode, showSubHeading, showDescription,
				mediaGap, contentGap, alignItems,
			} = attributes;

			const blockProps = useBlockProps.save( {
				className: `g2rd-card g2rd-card--icon-${ iconPosition } g2rd-card--align-${ alignment }`,
				style: {
					backgroundColor: backgroundColor || undefined,
					borderRadius: borderRadius ? `${ borderRadius }px` : undefined,
					padding: `${ paddingTop } ${ paddingRight } ${ paddingBottom } ${ paddingLeft }`,
					textAlign: alignment,
					gap: mediaGap || undefined,
					alignItems: alignItems || undefined,
				},
			} );

			const renderMedia = () => {
				if ( mediaType === "icon" && icon ) {
					return (
						<div className="g2rd-card__icon-wrap" style={ { width: `${ iconSize }px`, height: `${ iconSize }px`, minWidth: `${ iconSize }px`, backgroundColor: iconBgColor || undefined, borderRadius: `${ iconBorderRadius }%`, display: "flex", alignItems: "center", justifyContent: "center" } }>
							<span className={ `dashicons ${ icon }` } aria-hidden="true" style={ { fontSize: `${ Math.round( iconSize * 0.6 ) }px`, color: iconColor || "var(--wp--preset--color--primary,#2f425d)", width: `${ Math.round( iconSize * 0.6 ) }px`, height: `${ Math.round( iconSize * 0.6 ) }px` } }></span>
						</div>
					);
				}
				if ( mediaType === "image" && imageUrl ) {
					return (
						<div className="g2rd-card__image-wrap">
							<img src={ imageUrl } alt={ imageAlt } style={ { width: `${ imageWidth }px`, height: "auto" } } />
						</div>
					);
				}
				return null;
			};

			const media = renderMedia();

			return (
				<div { ...blockProps }>
					{ linkMode === "card" && ctaUrl && (
						<a className="g2rd-card__overlay-link" href={ ctaUrl } target={ ctaTarget ? "_blank" : undefined } rel={ ctaTarget ? "noopener noreferrer" : undefined } aria-label={ ctaAriaLabel || undefined }></a>
					) }
					{ media && <div className="g2rd-card__media">{ media }</div> }
					<div className="g2rd-card__content" style={ { gap: contentGap || undefined } }>
						{ heading && ( <RichText.Content tagName={ headingTag } className="g2rd-card__heading" value={ heading } style={ { color: headingColor || undefined, fontSize: headingFontSize || undefined, margin: 0 } } /> ) }
						{ showSubHeading && subHeading && ( <RichText.Content tagName="p" className="g2rd-card__subheading" value={ subHeading } style={ { color: subHeadingColor || undefined, fontSize: subheadingFontSize || undefined } } /> ) }
						{ showSeparator && ( <div className="g2rd-card__separator" style={ { width: `${ separatorWidth }px`, height: `${ separatorHeight }px`, backgroundColor: separatorColor || "var(--wp--preset--color--primary,#2f425d)" } }></div> ) }
						{ showDescription && description && ( <RichText.Content tagName="p" className="g2rd-card__description" value={ description } style={ { color: descriptionColor || undefined, fontSize: descriptionFontSize || undefined } } /> ) }
						{ linkMode === "cta" && ctaText && (
							<div className="g2rd-card__cta">
								<RichText.Content tagName="a" href={ ctaUrl || "#" } className={ `g2rd-card__cta-link g2rd-card__cta-link--${ ctaStyle }` } target={ ctaTarget ? "_blank" : undefined } rel={ ctaTarget ? "noopener noreferrer" : undefined } aria-label={ ctaAriaLabel || undefined } value={ ctaText } style={ ctaStyle === "button" ? { backgroundColor: ctaBgColor || "var(--wp--preset--color--primary,#2f425d)", color: ctaTextColor || "var(--wp--preset--color--white,#fff)", borderRadius: `${ ctaBorderRadius }px` } : { color: ctaBgColor || "var(--wp--preset--color--primary,#2f425d)" } } />
							</div>
						) }
					</div>
				</div>
			);
		},
	},

	// ── v1.13.1 → v1.13.3 ──────────────────────────────────────────────────
	// Pas de mediaGap, contentGap, alignItems — .g2rd-card__content sans style
	{
		attributes: attrsV2,
		save( { attributes } ) {
			const {
				mediaType, icon, iconSize, iconColor, iconBgColor, iconBorderRadius,
				imageUrl, imageAlt, imageWidth, iconPosition, alignment,
				heading, headingTag, subHeading, description,
				showSeparator, separatorColor, separatorWidth, separatorHeight,
				ctaText, ctaUrl, ctaTarget, ctaAriaLabel, ctaStyle,
				ctaBgColor, ctaTextColor, ctaBorderRadius,
				headingColor, subHeadingColor, descriptionColor,
				backgroundColor, borderRadius,
				paddingTop, paddingRight, paddingBottom, paddingLeft,
				headingFontSize, subheadingFontSize, descriptionFontSize,
				linkMode, showSubHeading, showDescription,
			} = attributes;

			const blockProps = useBlockProps.save( {
				className: `g2rd-card g2rd-card--icon-${ iconPosition } g2rd-card--align-${ alignment }`,
				style: {
					backgroundColor: backgroundColor || undefined,
					borderRadius: borderRadius ? `${ borderRadius }px` : undefined,
					padding: `${ paddingTop } ${ paddingRight } ${ paddingBottom } ${ paddingLeft }`,
					textAlign: alignment,
				},
			} );

			const renderMedia = () => {
				if ( mediaType === "icon" && icon ) {
					return (
						<div className="g2rd-card__icon-wrap" style={ { width: `${ iconSize }px`, height: `${ iconSize }px`, minWidth: `${ iconSize }px`, backgroundColor: iconBgColor || undefined, borderRadius: `${ iconBorderRadius }%`, display: "flex", alignItems: "center", justifyContent: "center" } }>
							<span className={ `dashicons ${ icon }` } aria-hidden="true" style={ { fontSize: `${ Math.round( iconSize * 0.6 ) }px`, color: iconColor || "var(--wp--preset--color--primary,#2f425d)", width: `${ Math.round( iconSize * 0.6 ) }px`, height: `${ Math.round( iconSize * 0.6 ) }px` } }></span>
						</div>
					);
				}
				if ( mediaType === "image" && imageUrl ) {
					return (
						<div className="g2rd-card__image-wrap">
							<img src={ imageUrl } alt={ imageAlt } style={ { width: `${ imageWidth }px`, height: "auto" } } />
						</div>
					);
				}
				return null;
			};

			const media = renderMedia();

			return (
				<div { ...blockProps }>
					{ linkMode === "card" && ctaUrl && (
						<a className="g2rd-card__overlay-link" href={ ctaUrl } target={ ctaTarget ? "_blank" : undefined } rel={ ctaTarget ? "noopener noreferrer" : undefined } aria-label={ ctaAriaLabel || undefined }></a>
					) }
					{ media && <div className="g2rd-card__media">{ media }</div> }
					<div className="g2rd-card__content">
						{ heading && ( <RichText.Content tagName={ headingTag } className="g2rd-card__heading" value={ heading } style={ { color: headingColor || undefined, fontSize: headingFontSize || undefined, margin: 0 } } /> ) }
						{ showSubHeading && subHeading && ( <RichText.Content tagName="p" className="g2rd-card__subheading" value={ subHeading } style={ { color: subHeadingColor || undefined, fontSize: subheadingFontSize || undefined } } /> ) }
						{ showSeparator && ( <div className="g2rd-card__separator" style={ { width: `${ separatorWidth }px`, height: `${ separatorHeight }px`, backgroundColor: separatorColor || "var(--wp--preset--color--primary,#2f425d)" } }></div> ) }
						{ showDescription && description && ( <RichText.Content tagName="p" className="g2rd-card__description" value={ description } style={ { color: descriptionColor || undefined, fontSize: descriptionFontSize || undefined } } /> ) }
						{ linkMode === "cta" && ctaText && (
							<div className="g2rd-card__cta">
								<RichText.Content tagName="a" href={ ctaUrl || "#" } className={ `g2rd-card__cta-link g2rd-card__cta-link--${ ctaStyle }` } target={ ctaTarget ? "_blank" : undefined } rel={ ctaTarget ? "noopener noreferrer" : undefined } aria-label={ ctaAriaLabel || undefined } value={ ctaText } style={ ctaStyle === "button" ? { backgroundColor: ctaBgColor || "var(--wp--preset--color--primary,#2f425d)", color: ctaTextColor || "var(--wp--preset--color--white,#fff)", borderRadius: `${ ctaBorderRadius }px` } : { color: ctaBgColor || "var(--wp--preset--color--primary,#2f425d)" } } />
							</div>
						) }
					</div>
				</div>
			);
		},
	},

	// ── pré-typographie ─────────────────────────────────────────────────────
	// Pas de fontSize, linkMode, showSubHeading, showDescription
	// CTA conditionnel sur ctaText seul — subHeading/description toujours visibles
	{
		attributes: attrsV1,
		save( { attributes } ) {
			const {
				mediaType, icon, iconSize, iconColor, iconBgColor, iconBorderRadius,
				imageUrl, imageAlt, imageWidth, iconPosition, alignment,
				heading, headingTag, subHeading, description,
				showSeparator, separatorColor, separatorWidth, separatorHeight,
				ctaText, ctaUrl, ctaTarget, ctaAriaLabel, ctaStyle,
				ctaBgColor, ctaTextColor, ctaBorderRadius,
				headingColor, subHeadingColor, descriptionColor,
				backgroundColor, borderRadius,
				paddingTop, paddingRight, paddingBottom, paddingLeft,
			} = attributes;

			const blockProps = useBlockProps.save( {
				className: `g2rd-card g2rd-card--icon-${ iconPosition } g2rd-card--align-${ alignment }`,
				style: {
					backgroundColor: backgroundColor || undefined,
					borderRadius: borderRadius ? `${ borderRadius }px` : undefined,
					padding: `${ paddingTop } ${ paddingRight } ${ paddingBottom } ${ paddingLeft }`,
					textAlign: alignment,
				},
			} );

			const renderMedia = () => {
				if ( mediaType === "icon" && icon ) {
					return (
						<div className="g2rd-card__icon-wrap" style={ { width: `${ iconSize }px`, height: `${ iconSize }px`, minWidth: `${ iconSize }px`, backgroundColor: iconBgColor || undefined, borderRadius: `${ iconBorderRadius }%`, display: "flex", alignItems: "center", justifyContent: "center" } }>
							<span className={ `dashicons ${ icon }` } aria-hidden="true" style={ { fontSize: `${ Math.round( iconSize * 0.6 ) }px`, color: iconColor || "var(--wp--preset--color--primary,#2f425d)", width: `${ Math.round( iconSize * 0.6 ) }px`, height: `${ Math.round( iconSize * 0.6 ) }px` } }></span>
						</div>
					);
				}
				if ( mediaType === "image" && imageUrl ) {
					return (
						<div className="g2rd-card__image-wrap">
							<img src={ imageUrl } alt={ imageAlt } style={ { width: `${ imageWidth }px`, height: "auto" } } />
						</div>
					);
				}
				return null;
			};

			const media = renderMedia();

			return (
				<div { ...blockProps }>
					{ media && <div className="g2rd-card__media">{ media }</div> }
					<div className="g2rd-card__content">
						{ heading && ( <RichText.Content tagName={ headingTag } className="g2rd-card__heading" value={ heading } style={ { color: headingColor || undefined, margin: 0 } } /> ) }
						{ subHeading && ( <RichText.Content tagName="p" className="g2rd-card__subheading" value={ subHeading } style={ { color: subHeadingColor || undefined } } /> ) }
						{ showSeparator && ( <div className="g2rd-card__separator" style={ { width: `${ separatorWidth }px`, height: `${ separatorHeight }px`, backgroundColor: separatorColor || "var(--wp--preset--color--primary,#2f425d)" } }></div> ) }
						{ description && ( <RichText.Content tagName="p" className="g2rd-card__description" value={ description } style={ { color: descriptionColor || undefined } } /> ) }
						{ ctaText && (
							<div className="g2rd-card__cta">
								<RichText.Content tagName="a" href={ ctaUrl || "#" } className={ `g2rd-card__cta-link g2rd-card__cta-link--${ ctaStyle }` } target={ ctaTarget ? "_blank" : undefined } rel={ ctaTarget ? "noopener noreferrer" : undefined } aria-label={ ctaAriaLabel || undefined } value={ ctaText } style={ ctaStyle === "button" ? { backgroundColor: ctaBgColor || "var(--wp--preset--color--primary,#2f425d)", color: ctaTextColor || "var(--wp--preset--color--white,#fff)", borderRadius: `${ ctaBorderRadius }px` } : { color: ctaBgColor || "var(--wp--preset--color--primary,#2f425d)" } } />
							</div>
						) }
					</div>
				</div>
			);
		},
	},

	// ── version initiale (RichText simple) ──────────────────────────────────
	{
		attributes: {
			content: { type: "string", default: "" },
		},
		save( { attributes } ) {
			const { content } = attributes;
			const blockProps = useBlockProps.save();
			return (
				<div { ...blockProps }>
					<RichText.Content tagName="p" value={ content } />
				</div>
			);
		},
	},
];
