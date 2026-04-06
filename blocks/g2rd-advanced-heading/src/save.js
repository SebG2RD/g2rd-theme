/**
 * G2RD Titre avancé — fonction de sauvegarde
 */
import { useBlockProps } from "@wordpress/block-editor";

function buildTextShadow( attrs ) {
	const parts = [];
	if ( attrs.shadow1Enabled ) parts.push( `${ attrs.shadow1X }px ${ attrs.shadow1Y }px ${ attrs.shadow1Blur }px ${ attrs.shadow1Color }` );
	if ( attrs.shadow2Enabled ) parts.push( `${ attrs.shadow2X }px ${ attrs.shadow2Y }px ${ attrs.shadow2Blur }px ${ attrs.shadow2Color }` );
	if ( attrs.shadow3Enabled ) parts.push( `${ attrs.shadow3X }px ${ attrs.shadow3Y }px ${ attrs.shadow3Blur }px ${ attrs.shadow3Color }` );
	return parts.join( ", " );
}

export default function save( { attributes } ) {
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

	// ── Style du titre
	const headingStyle = {};
	if ( textColor )     headingStyle.color         = textColor;
	if ( fontSizeValue ) headingStyle.fontSize       = fontSizeValue;
	if ( fontWeight )    headingStyle.fontWeight     = fontWeight;
	if ( lineHeight )    headingStyle.lineHeight     = lineHeight;
	if ( letterSpacing ) headingStyle.letterSpacing  = letterSpacing;
	const shadow = buildTextShadow( attributes );
	if ( shadow )        headingStyle.textShadow     = shadow;

	// ── Style des mots animés
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

	// ── Classes et styles de l'enveloppe animée
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

	// ── Badge numéroté
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
					<span className="g2rd-adv-heading__static-before">{ textBefore }&nbsp;</span>
				) }

				<span className={ outerClasses } style={ outerStyle }>
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

				{ textAfter && (
					<span className="g2rd-adv-heading__static-after">&nbsp;{ textAfter }</span>
				) }
			</TagName>
		</div>
	);
}
