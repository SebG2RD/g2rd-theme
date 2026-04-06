<?php

/**
 * Progress bar block render.
 * In Query block context (postId), the value is read from post meta.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content (empty).
 * @var WP_Block $block      Block instance.
 *
 * @package G2RD
 */

if (!defined('ABSPATH')) {
	exit;
}

$value_source  = $attributes['valueSource'] ?? 'static';
$value_meta_key = $attributes['valueMetaKey'] ?? '';
$max           = (float) ($attributes['max'] ?? 100);
$static_value  = (float) ($attributes['value'] ?? 75);
$label         = (string) ($attributes['label'] ?? '');
$style_variant = $attributes['styleVariant'] ?? 'bar';
$show_percentage = !empty($attributes['showPercentage']);
$bar_color     = $attributes['barColor'] ?? '';
$track_color   = $attributes['trackColor'] ?? '';

// Value: from post meta in Query context, otherwise static value.
$value = $static_value;
if ($value_source === 'meta' && $value_meta_key !== '') {
	$post_id = $block->context['postId'] ?? null;
	if ($post_id) {
		$meta_value = get_post_meta($post_id, $value_meta_key, true);
		if ($meta_value !== '' && is_numeric($meta_value)) {
			$value = (float) $meta_value;
		}
	}
}

$value = max(0, min($max, $value));
$percent = $max > 0 ? round(($value / $max) * 100) : 0;
$percent = min(100, max(0, (int) $percent));

$wrapper_style = '';
if ($bar_color) {
	$wrapper_style .= '--wrb-progress-bar-color:' . esc_attr($bar_color) . ';';
}
if ($track_color) {
	$wrapper_style .= '--wrb-progress-track-color:' . esc_attr($track_color) . ';';
}

$wrapper_attrs = get_block_wrapper_attributes([
	'class' => 'g2rd-progress-bar is-style-' . esc_attr($style_variant),
	'style' => $wrapper_style ?: null,
]);

$circle_radius = 45;
$circle_circumference = 2 * M_PI * $circle_radius;
$circle_dash = ($percent / 100) * $circle_circumference;
?>
<div <?php echo $wrapper_attrs; ?>>
	<?php if ($label !== '') : ?>
		<div class="g2rd-progress-bar__label"><?php echo esc_html($label); ?></div>
	<?php endif; ?>

	<?php if ($style_variant === 'bar') : ?>
		<div class="g2rd-progress-bar__bar-wrap">
			<div class="g2rd-progress-bar__track" role="presentation">
				<div
					class="g2rd-progress-bar__fill"
					style="width:<?php echo (int) $percent; ?>%"
				></div>
			</div>
			<?php if ($show_percentage) : ?>
				<span class="g2rd-progress-bar__value" aria-hidden="true">
					<?php echo (int) $percent; ?> %
				</span>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ($style_variant === 'circle') : ?>
		<div class="g2rd-progress-bar__circle-wrap">
			<svg
				class="g2rd-progress-bar__circle"
				viewBox="0 0 100 100"
				aria-hidden="true"
			>
				<circle
					class="g2rd-progress-bar__circle-track"
					cx="50"
					cy="50"
					r="<?php echo (int) $circle_radius; ?>"
					fill="none"
					stroke-width="8"
				/>
				<circle
					class="g2rd-progress-bar__circle-fill"
					cx="50"
					cy="50"
					r="<?php echo (int) $circle_radius; ?>"
					fill="none"
					stroke-width="8"
					stroke-dasharray="<?php echo (float) $circle_dash; ?> <?php echo (float) $circle_circumference; ?>"
					stroke-linecap="round"
					transform="rotate(-90 50 50)"
				/>
			</svg>
			<?php if ($show_percentage) : ?>
				<span class="g2rd-progress-bar__circle-value" aria-hidden="true">
					<?php echo (int) $percent; ?> %
				</span>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>
