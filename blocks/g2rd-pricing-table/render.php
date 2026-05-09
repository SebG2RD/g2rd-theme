<?php
/**
 * Rendu frontend du bloc G2RD Tableau de prix.
 *
 * Variables disponibles :
 *   $attributes  array  Attributs du bloc
 *   $content     string Contenu interne (vide — bloc dynamique)
 *   $block       WP_Block Instance du bloc
 *
 * @package G2RD
 */

$columns        = $attributes['columns']       ?? [];
$design         = \sanitize_key( $attributes['design']         ?? 'cards' );
$show_title     = (bool) ( $attributes['showTitle']       ?? true );
$show_subtitle  = (bool) ( $attributes['showSubtitle']    ?? true );
$show_price     = (bool) ( $attributes['showPrice']       ?? true );
$show_desc      = (bool) ( $attributes['showDescription'] ?? true );
$show_features  = (bool) ( $attributes['showFeatures']    ?? true );
$show_cta       = (bool) ( $attributes['showCta']         ?? true );
$show_badge     = (bool) ( $attributes['showBadge']       ?? true );
$show_shadow    = (bool) ( $attributes['showBoxShadow']   ?? true );
$featured_scale = (bool) ( $attributes['featuredScale']   ?? true );
$global_accent  = \sanitize_hex_color( $attributes['globalAccentColor'] ?? '' ) ?: '';
$global_text    = \sanitize_hex_color( $attributes['globalTextColor']   ?? '' ) ?: '';
$global_bg      = \sanitize_hex_color( $attributes['globalBgColor']     ?? '' ) ?: '';
$feature_icon   = \esc_html( $attributes['featureIcon']   ?? '✓' );
$border_radius  = \absint( $attributes['borderRadius']    ?? 12 );
$gap            = \esc_attr( $attributes['gapSize']        ?? 'var(--wp--preset--spacing--m)' );
$block_id       = \esc_attr( $attributes['blockId']        ?? \wp_unique_id( 'g2rd-pt-' ) );

if ( empty( $columns ) ) {
    return;
}

$count = count( $columns );

// ── Styles CSS dynamiques ─────────────────────────────────────────────────────
$css = "
#block-{$block_id} .g2rd-pt__grid {
    display: grid;
    grid-template-columns: repeat({$count}, 1fr);
    gap: {$gap};
    align-items: " . ( $featured_scale ? 'end' : 'stretch' ) . ";
}
@media (max-width: 960px) {
    #block-{$block_id} .g2rd-pt__grid {
        grid-template-columns: repeat(" . min( $count, 2 ) . ", 1fr);
    }
}
@media (max-width: 600px) {
    #block-{$block_id} .g2rd-pt__grid {
        grid-template-columns: 1fr;
    }
}
";
// Pas de wp_add_inline_style ici (pas de handle) — style inline dans le bloc
?>
<div <?php echo \get_block_wrapper_attributes( [
    'id'    => 'block-' . $block_id,
    'class' => 'g2rd-pricing-table g2rd-pricing-table--' . $design,
] ); ?>>
    <style><?php echo $css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></style>

    <div class="g2rd-pt__grid">
        <?php foreach ( $columns as $col ) :
            $title       = \wp_kses_post( $col['title']       ?? '' );
            $subtitle    = \wp_kses_post( $col['subtitle']    ?? '' );
            $price       = \esc_html(   $col['price']         ?? '' );
            $period      = \esc_html(   $col['pricePeriod']   ?? '' );
            $prefix      = \esc_html(   $col['pricePrefix']   ?? '' );
            $description = \wp_kses_post( $col['description'] ?? '' );
            $features    = array_map( 'wp_kses_post', (array) ( $col['features'] ?? [] ) );
            $cta_text    = \wp_kses_post( $col['ctaText']       ?? '' );
            $cta_url     = \esc_url(     $col['ctaUrl']        ?? '#' );
            $cta_target  = ! empty( $col['ctaTarget'] ) ? ' target="_blank" rel="noopener noreferrer"' : '';
            $badge       = \wp_kses_post( $col['badge']        ?? '' );
            $is_featured = ! empty( $col['isFeatured'] );
            $accent      = \sanitize_hex_color( $col['accentColor'] ?? '' ) ?: $global_accent ?: 'var(--wp--preset--color--primary, #2F425D)';
            $order       = array_filter( (array) ( $col['elementsOrder'] ?? ['badge','title','subtitle','price','description','features','cta'] ) );

            // ── Styles inline de la carte ─────────────────────────────────────
            $radius_px = $border_radius . 'px';
            $shadow_val = $show_shadow
                ? ( $is_featured
                    ? '0 8px 40px rgba(0,0,0,0.18)'
                    : '0 4px 24px rgba(0,0,0,0.10)' )
                : 'none';

            $card_bg = match ( $design ) {
                'gradient' => "linear-gradient(135deg, {$accent}22 0%, {$accent}08 100%)",
                'glass'    => 'rgba(255,255,255,0.18)',
                default    => $global_bg ?: '#ffffff',
            };
            $card_border = match ( $design ) {
                'bordered' => "2px solid {$accent}",
                'glass'    => '1px solid rgba(255,255,255,0.3)',
                'minimal'  => '0 0 0 1px #e5e7eb',
                default    => $is_featured ? "2px solid {$accent}" : '1px solid #e5e7eb',
            };
            $backdrop = $design === 'glass' ? 'backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);' : '';
            $transform = ( $is_featured && $featured_scale ) ? 'transform:scale(1.04);' : '';
            $card_style = "background:{$card_bg};border:{$card_border};border-radius:{$radius_px};box-shadow:{$shadow_val};padding:28px 24px;{$backdrop}{$transform}color:" . ( $global_text ?: 'inherit' ) . ';position:relative;overflow:hidden;';

            // ── Éléments rendus ───────────────────────────────────────────────
            $elements = [];

            if ( $show_badge && $badge ) {
                $elements['badge'] = '<div class="g2rd-pt__badge" style="background:' . \esc_attr( $accent ) . ';color:#fff;display:inline-block;border-radius:999px;padding:3px 14px;font-size:12px;font-weight:700;margin-bottom:12px;letter-spacing:0.05em;">'
                    . $badge . '</div>';
            } else {
                $elements['badge'] = '';
            }

            if ( $show_title && $title ) {
                $elements['title'] = '<div class="g2rd-pt__title" style="font-weight:700;font-size:22px;margin-bottom:4px;">' . $title . '</div>';
            } else {
                $elements['title'] = '';
            }

            if ( $show_subtitle && $subtitle ) {
                $elements['subtitle'] = '<div class="g2rd-pt__subtitle" style="font-size:14px;opacity:0.7;margin-bottom:16px;">' . $subtitle . '</div>';
            } else {
                $elements['subtitle'] = '';
            }

            if ( $show_price && $price ) {
                $border_top_price = $design === 'minimal'
                    ? 'border-top:2px solid ' . \esc_attr( $accent ) . ';padding-top:16px;'
                    : '';
                $elements['price'] = '<div class="g2rd-pt__price" style="margin:16px 0;' . $border_top_price . '">'
                    . ( $prefix ? '<span class="g2rd-pt__price-prefix" style="font-size:13px;opacity:0.7;display:block;margin-bottom:4px;">' . $prefix . '</span>' : '' )
                    . '<span class="g2rd-pt__price-amount" style="font-size:42px;font-weight:800;color:' . \esc_attr( $accent ) . ';">' . $price . '</span>'
                    . ( $period ? '<span class="g2rd-pt__price-period" style="font-size:14px;opacity:0.6;margin-left:4px;">' . $period . '</span>' : '' )
                    . '</div>';
            } else {
                $elements['price'] = '';
            }

            if ( $show_desc && $description ) {
                $elements['description'] = '<div class="g2rd-pt__description" style="font-size:14px;opacity:0.8;margin-bottom:16px;line-height:1.6;">' . $description . '</div>';
            } else {
                $elements['description'] = '';
            }

            if ( $show_features && ! empty( $features ) ) {
                $items_html = '';
                foreach ( $features as $feat ) {
                    if ( empty( $feat ) ) continue;
                    $items_html .= '<li style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">'
                        . '<span style="color:' . \esc_attr( $accent ) . ';font-weight:700;flex-shrink:0;">' . $feature_icon . '</span>'
                        . '<span>' . $feat . '</span>'
                        . '</li>';
                }
                $elements['features'] = '<ul class="g2rd-pt__features" style="list-style:none;padding:0;margin:0 0 20px;font-size:14px;">' . $items_html . '</ul>';
            } else {
                $elements['features'] = '';
            }

            if ( $show_cta && $cta_text ) {
                $elements['cta'] = '<a href="' . $cta_url . '"' . $cta_target . ' class="g2rd-pt__cta wp-element-button" style="display:block;text-align:center;padding:12px 24px;border-radius:8px;font-weight:700;font-size:15px;background:' . \esc_attr( $accent ) . ';color:#fff;text-decoration:none;">'
                    . $cta_text . '</a>';
            } else {
                $elements['cta'] = '';
            }
        ?>
        <div class="g2rd-pt__col<?php echo $is_featured ? ' g2rd-pt__col--featured' : ''; ?>" style="<?php echo \esc_attr( $card_style ); ?>">
            <?php foreach ( $order as $key ) :
                echo $elements[ $key ] ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
