import { useBlockProps } from "@wordpress/block-editor";

function buildTextShadow( attrs ) {
	const parts = [];
	if ( attrs.shadow1Enabled ) parts.push( `${ attrs.shadow1X }px ${ attrs.shadow1Y }px ${ attrs.shadow1Blur }px ${ attrs.shadow1Color }` );
	if ( attrs.shadow2Enabled ) parts.push( `${ attrs.shadow2X }px ${ attrs.shadow2Y }px ${ attrs.shadow2Blur }px ${ attrs.shadow2Color }` );
	if ( attrs.shadow3Enabled ) parts.push( `${ attrs.shadow3X }px ${ attrs.shadow3Y }px ${ attrs.shadow3Blur }px ${ attrs.shadow3Color }` );
	return parts.join( ", " );
}

export default [
	// ── Avant renommage fontSizeValue → fontSize ────────────────────────────
	{
		attributes: {
			textBefore:           { type: "string",  default: "Nous sommes" },
			textAfter:            { type: "string",  default: "" },
			animatedWords:        { type: "array",   default: [ "créatifs", "innovants", "passionnés" ] },
			headingTag:           { type: "string",  default: "h2" },
			alignment:            { type: "string",  default: "left" },
			animationEffect:      { type: "string",  default: "sliding" },
			animationSpeed:       { type: "number",  default: 2500 },
			textColor:            { type: "string",  default: "" },
			animatedColor:        { type: "string",  default: "" },
			animatedFontWeight:   { type: "string",  default: "inherit" },
			fontSizeValue:        { type: "string",  default: "" },
			fontWeight:           { type: "string",  default: "700" },
			lineHeight:           { type: "string",  default: "" },
			letterSpacing:        { type: "string",  default: "" },
			highlightEnabled:     { type: "boolean", default: false },
			highlightBgColor:     { type: "string",  default: "#f59e0b" },
			highlightTextColor:   { type: "string",  default: "#ffffff" },
			highlightPadding:     { type: "number",  default: 8 },
			highlightBorderRadius:{ type: "number",  default: 4 },
			decoratorType:        { type: "string",  default: "none" },
			decoratorColor:       { type: "string",  default: "" },
			decoratorSize:        { type: "number",  default: 3 },
			numberValue:          { type: "number",  default: 1 },
			numberBgColor:        { type: "string",  default: "" },
			numberTextColor:      { type: "string",  default: "#ffffff" },
			numberSize:           { type: "number",  default: 56 },
			shadow1Enabled:       { type: "boolean", default: false },
			shadow1Color:         { type: "string",  default: "rgba(0,0,0,0.3)" },
			shadow1X:             { type: "number",  default: 2 },
			shadow1Y:             { type: "number",  default: 2 },
			shadow1Blur:          { type: "number",  default: 4 },
			shadow2Enabled:       { type: "boolean", default: false },
			shadow2Color:         { type: "string",  default: "rgba(0,0,0,0.2)" },
			shadow2X:             { type: "number",  default: 4 },
			shadow2Y:             { type: "number",  default: 4 },
			shadow2Blur:          { type: "number",  default: 8 },
			shadow3Enabled:       { type: "boolean", default: false },
			shadow3Color:         { type: "string",  default: "rgba(0,0,0,0.1)" },
			shadow3X:             { type: "number",  default: 6 },
			shadow3Y:             { type: "number",  default: 6 },
			shadow3Blur:          { type: "number",  default: 12 },
			clipMaskEnabled:      { type: "boolean", default: false },
			clipMaskUrl:          { type: "string",  default: "" },
			clipMaskId:           { type: "number",  default: 0 },
		},

		migrate( attributes ) {
			const { fontSizeValue, ...rest } = attributes;
			return { ...rest, fontSize: fontSizeValue || "" };
		},

		save( { attributes } ) {
			const {
				textBefore, textAfter, animatedWords, headingTag,
				alignment, animationEffect, animationSpeed,
				textColor, animatedColor, animatedFontWeight,
				fontSizeValue, fontWeight, lineHeight, letterSpacing,
				highlightEnabled, highlightBgColor, highlightTextColor, highlightPadding, highlightBorderRadius,
				decoratorType, decoratorColor, decoratorSize,
				numberValue, numberBgColor, numberTextColor, numberSize,
				clipMaskEnabled, clipMaskUrl,
			} = attributes;

			const TagName = headingTag || "h2";

			const headingStyle = {};
			if ( textColor )     headingStyle.color         = textColor;
			if ( fontSizeValue ) headingStyle.fontSize       = fontSizeValue;
			if ( fontWeight )    headingStyle.fontWeight     = fontWeight;
			if ( lineHeight )    headingStyle.lineHeight     = lineHeight;
			if ( letterSpacing ) headingStyle.letterSpacing  = letterSpacing;
			const shadow = buildTextShadow( attributes );
			if ( shadow )        headingStyle.textShadow     = shadow;

			const animStyle = {};
			if ( clipMaskEnabled && clipMaskUrl ) {
				animStyle.backgroundImage      = `url(${ clipMaskUrl })`;
				animStyle.backgroundClip       = "text";
				animStyle.WebkitBackgroundClip = "text";
				animStyle.color                = "transparent";
				animStyle.WebkitTextFillColor  = "transparent";
				animStyle.backgroundSize       = "cover";
				animStyle.backgroundPosition   = "center";
			} else if ( animatedColor ) {
				animStyle.color = animatedColor;
			}
			if ( animatedFontWeight && animatedFontWeight !== "inherit" )
				animStyle.fontWeight = animatedFontWeight;

			const outerClasses = [
				"g2rd-adv-heading__animated-outer",
				highlightEnabled         ? "has-highlight"                     : "",
				decoratorType !== "none" ? `has-decorator--${ decoratorType }` : "",
			].filter( Boolean ).join( " " );

			const outerStyle = {};
			if ( highlightEnabled ) {
				outerStyle.background   = highlightBgColor;
				outerStyle.color        = highlightTextColor;
				outerStyle.padding      = `2px ${ highlightPadding }px`;
				outerStyle.borderRadius = `${ highlightBorderRadius }px`;
			}
			if ( decoratorType !== "none" ) {
				outerStyle[ "--g2rd-dec-color" ] = decoratorColor || "var(--wp--preset--color--primary, #2f425d)";
				outerStyle[ "--g2rd-dec-size" ]  = `${ decoratorSize }px`;
			}

			const badgeStyle = {
				background: numberBgColor  || "var(--wp--preset--color--primary, #2f425d)",
				color:      numberTextColor || "#ffffff",
				width:      numberSize + "px",
				height:     numberSize + "px",
				fontSize:   Math.round( numberSize * 0.45 ) + "px",
			};

			const blockProps = useBlockProps.save( {
				className:     "g2rd-adv-heading-wrap",
				style:         { textAlign: alignment },
				"data-effect": animationEffect,
				"data-speed":  animationSpeed,
			} );

			return (
				<div { ...blockProps }>
					{ decoratorType === "number" && (
						<span className="g2rd-adv-heading__number-badge" style={ badgeStyle } aria-hidden="true">
							{ numberValue }
						</span>
					) }
					<TagName className="g2rd-adv-heading" style={ headingStyle }>
						{ textBefore && (
							<span className="g2rd-adv-heading__static-before" dangerouslySetInnerHTML={ { __html: textBefore + ' ' } } />
						) }
						<span className={ outerClasses } style={ outerStyle }>
							<span className="g2rd-adv-heading__animated-wrap" style={ animStyle }>
								{ animatedWords.map( ( word, i ) => (
									<span key={ i } className={ `g2rd-adv-heading__word${ i === 0 ? " is-visible" : "" }` } aria-hidden={ i !== 0 ? "true" : undefined }>
										{ word }
									</span>
								) ) }
							</span>
						</span>
						{ textAfter && (
							<span className="g2rd-adv-heading__static-after" dangerouslySetInnerHTML={ { __html: ' ' + textAfter } } />
						) }
					</TagName>
				</div>
			);
		},
	},
];
