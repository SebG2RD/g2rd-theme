<?php
/**
 * Template de rendu serveur du bloc Conteneur G2RD.
 *
 * Variables disponibles :
 *   $attributes (array)  — attributs du bloc
 *   $content    (string) — HTML des InnerBlocks sérialisés
 *   $block      (WP_Block) — instance du bloc
 *
 * @package G2RD
 * @since   1.1.0
 */

// ─── ID unique ────────────────────────────────────────────────────────────────
$block_id = \sanitize_html_class( $attributes['blockId'] ?? '' );
if ( empty( $block_id ) ) {
	$block_id = 'g2rd-cntr-' . \wp_unique_id();
}

// ─── Tag HTML ─────────────────────────────────────────────────────────────────
$allowed_tags = [ 'div', 'section', 'article', 'header', 'footer', 'main', 'aside', 'figure' ];
$html_tag     = \sanitize_text_field( $attributes['htmlTag'] ?? 'div' );
if ( ! \in_array( $html_tag, $allowed_tags, true ) ) {
	$html_tag = 'div';
}

// ─── Classes ──────────────────────────────────────────────────────────────────
$layout_type = \sanitize_text_field( $attributes['layoutType'] ?? 'flex' );
$classes     = [ 'g2rd-container', 'g2rd-container--' . $layout_type ];

if ( ! empty( $attributes['align'] ) ) {
	$classes[] = 'align' . \sanitize_html_class( $attributes['align'] );
}
if ( ! empty( $attributes['hideOnDesktop'] ) ) {
	$classes[] = 'g2rd-hide-desktop';
}
if ( ! empty( $attributes['hideOnTablet'] ) ) {
	$classes[] = 'g2rd-hide-tablet';
}
if ( ! empty( $attributes['hideOnMobile'] ) ) {
	$classes[] = 'g2rd-hide-mobile';
}
if ( ! empty( $attributes['customCSSClass'] ) ) {
	$classes[] = \sanitize_html_class( $attributes['customCSSClass'] );
}

// ─── Animation ────────────────────────────────────────────────────────────────
$animation      = \sanitize_text_field( $attributes['animation'] ?? 'none' );
$anim_delay     = \absint( $attributes['animationDelay']    ?? 0 );
$anim_duration  = \absint( $attributes['animationDuration'] ?? 600 );
$anim_easing    = \sanitize_text_field( $attributes['animationEasing'] ?? 'ease' );

$anim_attrs = '';
if ( 'none' !== $animation ) {
	$anim_attrs  = ' data-g2rd-animate="' . \esc_attr( $animation ) . '"';
	$anim_attrs .= ' data-g2rd-animate-delay="' . \esc_attr( $anim_delay ) . '"';
	$anim_attrs .= ' data-g2rd-animate-duration="' . \esc_attr( $anim_duration ) . '"';
	$anim_attrs .= ' data-g2rd-animate-easing="' . \esc_attr( $anim_easing ) . '"';
}

// ─── CSS généré ───────────────────────────────────────────────────────────────
$css = g2rd_container_build_css( $block_id, $attributes );

// ─── Rendu ────────────────────────────────────────────────────────────────────
printf(
	'<%1$s id="%2$s" class="%3$s"%4$s>%5$s%6$s</%1$s>',
	\esc_attr( $html_tag ),
	\esc_attr( $block_id ),
	\esc_attr( \implode( ' ', $classes ) ),
	$anim_attrs, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — attributs pré-échappés
	$css ? '<style>' . $css . '</style>' : '', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — CSS généré et validé
	$content // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — HTML des InnerBlocks
);

// ─── Générateur de CSS ────────────────────────────────────────────────────────

/**
 * Génère le CSS inline scoped par #block_id pour le conteneur.
 *
 * @param string $id        Identifiant CSS unique du bloc (#selector).
 * @param array  $attributes Attributs du bloc.
 * @return string CSS prêt à injecter dans une balise <style>.
 */
function g2rd_container_build_css( string $id, array $attributes ): string {
	$sel    = '#' . $id;
	$rules  = [];
	$media  = [];

	$layout = $attributes['layoutType'] ?? 'flex';

	// ── Affichage ─────────────────────────────────────────────────────────────
	switch ( $layout ) {
		case 'flex':
			$rules[] = 'display:flex';
			break;
		case 'grid':
			$rules[] = 'display:grid';
			break;
		case 'flow':
		case 'constrained':
			$rules[] = 'display:block';
			break;
	}

	// ── Flex ──────────────────────────────────────────────────────────────────
	if ( 'flex' === $layout ) {
		g2rd_add_css_prop( $rules, 'flex-direction',  $attributes['flexDirection'] ?? '' );
		g2rd_add_css_prop( $rules, 'justify-content', $attributes['flexJustify']   ?? '' );
		g2rd_add_css_prop( $rules, 'align-items',     $attributes['flexAlign']     ?? '' );
		$wrap    = $attributes['flexWrap'] ?? true;
		$rules[] = 'flex-wrap:' . ( $wrap ? 'wrap' : 'nowrap' );
		g2rd_add_css_prop( $rules, 'gap', $attributes['flexGap'] ?? '' );
	}

	// ── Grid ──────────────────────────────────────────────────────────────────
	if ( 'grid' === $layout ) {
		$cols    = \absint( $attributes['gridColumns'] ?? 2 );
		$rules[] = 'grid-template-columns:repeat(' . $cols . ',1fr)';
		g2rd_add_css_prop( $rules, 'gap', $attributes['gridGap'] ?? '' );
	}

	// ── Contraint (max-width centré) ──────────────────────────────────────────
	if ( 'constrained' === $layout ) {
		$cw = \sanitize_text_field( $attributes['constrainedWidth'] ?? '1200px' );
		if ( $cw ) {
			$rules[] = 'max-width:' . $cw;
			$rules[] = 'margin-left:auto';
			$rules[] = 'margin-right:auto';
		}
	}

	// ── Dimensions ────────────────────────────────────────────────────────────
	g2rd_add_css_prop( $rules, 'width',      $attributes['width']     ?? '' );
	g2rd_add_css_prop( $rules, 'min-height', $attributes['minHeight'] ?? '' );

	// ── Espacement ────────────────────────────────────────────────────────────
	g2rd_add_css_prop( $rules, 'padding-top',    $attributes['paddingTop']    ?? '' );
	g2rd_add_css_prop( $rules, 'padding-right',  $attributes['paddingRight']  ?? '' );
	g2rd_add_css_prop( $rules, 'padding-bottom', $attributes['paddingBottom'] ?? '' );
	g2rd_add_css_prop( $rules, 'padding-left',   $attributes['paddingLeft']   ?? '' );
	g2rd_add_css_prop( $rules, 'margin-top',     $attributes['marginTop']     ?? '' );
	g2rd_add_css_prop( $rules, 'margin-bottom',  $attributes['marginBottom']  ?? '' );

	// ── Fond ──────────────────────────────────────────────────────────────────
	$bg_type = $attributes['bgType'] ?? 'none';
	switch ( $bg_type ) {
		case 'color':
			g2rd_add_css_prop( $rules, 'background-color', $attributes['bgColor'] ?? '' );
			break;
		case 'gradient':
			$grad = \sanitize_text_field( $attributes['bgGradient'] ?? '' );
			if ( $grad ) {
				$rules[] = 'background:' . $grad;
			}
			break;
		case 'image':
			$img_url = \esc_url_raw( $attributes['bgImageUrl'] ?? '' );
			if ( $img_url ) {
				$rules[] = 'background-image:url(' . $img_url . ')';
				g2rd_add_css_prop( $rules, 'background-size',     $attributes['bgImageSize']     ?? 'cover' );
				g2rd_add_css_prop( $rules, 'background-position', $attributes['bgImagePosition'] ?? 'center center' );
				g2rd_add_css_prop( $rules, 'background-repeat',   $attributes['bgImageRepeat']   ?? 'no-repeat' );
			}
			break;
	}
	// Overlay via ::before
	if ( ! empty( $attributes['bgOverlay'] ) ) {
		$overlay_color = \sanitize_text_field( $attributes['bgOverlayColor'] ?? 'rgba(0,0,0,0.5)' );
		$media['overlay'] = $sel . '{position:relative}' .
			$sel . '::before{content:"";position:absolute;inset:0;background:' . $overlay_color . ';pointer-events:none;z-index:0}' .
			$sel . '>*{position:relative;z-index:1}';
	}

	// ── Bordure ───────────────────────────────────────────────────────────────
	g2rd_add_css_prop( $rules, 'border-radius', $attributes['borderRadius'] ?? '' );
	$bw    = \sanitize_text_field( $attributes['borderWidth'] ?? '' );
	$bs    = \sanitize_text_field( $attributes['borderStyle'] ?? 'solid' );
	$bc    = \sanitize_text_field( $attributes['borderColor'] ?? '' );
	if ( $bw && $bc ) {
		$rules[] = 'border:' . $bw . ' ' . $bs . ' ' . $bc;
	}

	// ── Overflow ──────────────────────────────────────────────────────────────
	$ov = \sanitize_text_field( $attributes['overflow'] ?? 'visible' );
	if ( 'visible' !== $ov ) {
		$rules[] = 'overflow:' . $ov;
	}

	// ── Position relative si overlay ou border-radius + overflow:hidden ───────
	if ( ! empty( $attributes['borderRadius'] ) || ! empty( $attributes['bgOverlay'] ) ) {
		$rules[] = 'position:relative';
	}

	// ── Règle desktop ─────────────────────────────────────────────────────────
	$css = $sel . '{' . \implode( ';', $rules ) . '}';

	// Overlay séparé (pseudo-element)
	if ( ! empty( $media['overlay'] ) ) {
		$css .= $media['overlay'];
	}

	// ── Tablet (≤ 1024 px) ────────────────────────────────────────────────────
	$tablet = [];
	if ( 'flex' === $layout ) {
		g2rd_add_css_prop( $tablet, 'flex-direction',  $attributes['flexDirectionTablet'] ?? '' );
		g2rd_add_css_prop( $tablet, 'justify-content', $attributes['flexJustifyTablet']   ?? '' );
		g2rd_add_css_prop( $tablet, 'align-items',     $attributes['flexAlignTablet']     ?? '' );
		if ( isset( $attributes['flexWrapTablet'] ) ) {
			$tablet[] = 'flex-wrap:' . ( $attributes['flexWrapTablet'] ? 'wrap' : 'nowrap' );
		}
		g2rd_add_css_prop( $tablet, 'gap', $attributes['flexGapTablet'] ?? '' );
	}
	if ( 'grid' === $layout ) {
		$tc = \absint( $attributes['gridColumnsTablet'] ?? 0 );
		if ( $tc ) {
			$tablet[] = 'grid-template-columns:repeat(' . $tc . ',1fr)';
		}
		g2rd_add_css_prop( $tablet, 'gap', $attributes['gridGapTablet'] ?? '' );
	}
	g2rd_add_css_prop( $tablet, 'padding-top',    $attributes['paddingTopTablet']    ?? '' );
	g2rd_add_css_prop( $tablet, 'padding-right',  $attributes['paddingRightTablet']  ?? '' );
	g2rd_add_css_prop( $tablet, 'padding-bottom', $attributes['paddingBottomTablet'] ?? '' );
	g2rd_add_css_prop( $tablet, 'padding-left',   $attributes['paddingLeftTablet']   ?? '' );
	if ( ! empty( $attributes['hideOnTablet'] ) ) {
		$tablet[] = 'display:none';
	}
	if ( $tablet ) {
		$css .= '@media(max-width:1024px){' . $sel . '{' . \implode( ';', $tablet ) . '}}';
	}

	// ── Mobile (≤ 768 px) ─────────────────────────────────────────────────────
	$mobile = [];
	if ( 'flex' === $layout ) {
		g2rd_add_css_prop( $mobile, 'flex-direction',  $attributes['flexDirectionMobile'] ?? '' );
		g2rd_add_css_prop( $mobile, 'justify-content', $attributes['flexJustifyMobile']   ?? '' );
		g2rd_add_css_prop( $mobile, 'align-items',     $attributes['flexAlignMobile']     ?? '' );
		if ( isset( $attributes['flexWrapMobile'] ) ) {
			$mobile[] = 'flex-wrap:' . ( $attributes['flexWrapMobile'] ? 'wrap' : 'nowrap' );
		}
		g2rd_add_css_prop( $mobile, 'gap', $attributes['flexGapMobile'] ?? '' );
	}
	if ( 'grid' === $layout ) {
		$mc = \absint( $attributes['gridColumnsMobile'] ?? 1 );
		$mobile[] = 'grid-template-columns:repeat(' . $mc . ',1fr)';
		g2rd_add_css_prop( $mobile, 'gap', $attributes['gridGapMobile'] ?? '' );
	}
	g2rd_add_css_prop( $mobile, 'padding-top',    $attributes['paddingTopMobile']    ?? '' );
	g2rd_add_css_prop( $mobile, 'padding-right',  $attributes['paddingRightMobile']  ?? '' );
	g2rd_add_css_prop( $mobile, 'padding-bottom', $attributes['paddingBottomMobile'] ?? '' );
	g2rd_add_css_prop( $mobile, 'padding-left',   $attributes['paddingLeftMobile']   ?? '' );
	if ( ! empty( $attributes['hideOnMobile'] ) ) {
		$mobile[] = 'display:none';
	}
	if ( $mobile ) {
		$css .= '@media(max-width:768px){' . $sel . '{' . \implode( ';', $mobile ) . '}}';
	}

	// Desktop : masquer si hideOnDesktop
	if ( ! empty( $attributes['hideOnDesktop'] ) ) {
		$css .= '@media(min-width:1025px){' . $sel . '{display:none}}';
	}

	return $css;
}

/**
 * Ajoute une propriété CSS à un tableau de règles si la valeur est non vide.
 *
 * @param array  $rules    Tableau de règles CSS modifié par référence.
 * @param string $property Propriété CSS.
 * @param string $value    Valeur CSS.
 * @return void
 */
function g2rd_add_css_prop( array &$rules, string $property, string $value ): void {
	$value = \sanitize_text_field( $value );
	if ( '' !== $value ) {
		$rules[] = $property . ':' . $value;
	}
}
