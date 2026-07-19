<?php

/**
 * Share buttons block render.
 * Outputs share links with the current content URL and l'URL and title (post or Query block context).
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

$shares        = $attributes['shares'] ?? [];
$style_variant = $attributes['styleVariant'] ?? 'rounded';
$layout        = $attributes['layout'] ?? 'horizontal';
$icon_size     = (int) ($attributes['iconSize'] ?? 24);

// Context: in a Query block we have postId, otherwise current post
$post_id = $block->context['postId'] ?? get_the_ID();
$url     = $post_id ? get_permalink($post_id) : '';
$title   = $post_id ? get_the_title($post_id) : '';

if (!$url) {
	$url = get_permalink();
}
if (!$title) {
	$title = get_the_title();
}

$url_encoded   = rawurlencode($url);
$title_encoded = rawurlencode($title);
$text_encoded  = rawurlencode($title . ' ' . $url);

$share_urls = [
	'facebook'  => 'https://www.facebook.com/sharer/sharer.php?u=' . $url_encoded,
	'twitter'  => 'https://twitter.com/intent/tweet?url=' . $url_encoded . '&text=' . $title_encoded,
	'linkedin' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $url_encoded,
	'pinterest'=> 'https://pinterest.com/pin/create/button/?url=' . $url_encoded . '&description=' . $title_encoded,
	'whatsapp' => 'https://wa.me/?text=' . $text_encoded,
	'email'    => 'mailto:?subject=' . $title_encoded . '&body=' . $url_encoded,
];

// SVG icons (même visuel que l'éditeur)
$icons = [
	'facebook'  => '<path fill="currentColor" d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>',
	'twitter'   => '<path fill="currentColor" d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>',
	'linkedin'  => '<path fill="currentColor" d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>',
	'pinterest' => '<path fill="currentColor" d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.18.271-.418.165-1.547-.72-2.518-2.989-2.518-4.816 0-3.908 2.837-7.301 7.381-7.301 3.874 0 6.876 2.758 6.876 6.454 0 3.847-2.429 6.94-5.993 6.94-1.167 0-2.266-.607-2.64-1.324l-.717 2.716c-.255.984-.96 3.68-1.429 4.928 1.076.332 2.219.513 3.411.513 6.607 0 11.985-5.365 11.985-11.987C23.97 5.39 18.592.001 11.985.001z"/>',
	'whatsapp'  => '<path fill="currentColor" d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>',
	'email'     => '<path fill="currentColor" d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>',
];

$enabled = array_filter($shares, function ($s) {
	return !empty($s['enabled']);
});

if (empty($enabled)) {
	return '';
}

$wrapper_attrs = get_block_wrapper_attributes([
	'class' => 'g2rd-share-buttons is-style-' . esc_attr($style_variant) . ' is-' . esc_attr($layout),
	'style' => '--wrb-share-icon-size:' . (int) $icon_size . 'px;',
]);
?>
<div <?php echo $wrapper_attrs; ?>>
	<ul class="g2rd-share-buttons__list" role="list">
		<?php
		foreach ($enabled as $item) {
			$network = $item['network'] ?? '';
			$label   = $item['label'] ?? $network;
			$href    = $share_urls[$network] ?? '#';
			$icon    = $icons[$network] ?? '';
			$new_tab = $network !== 'email';
			?>
			<li class="g2rd-share-buttons__item">
				<a
					href="<?php echo esc_url($href); ?>"
					class="g2rd-share-buttons__link"
					<?php echo $new_tab ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>
					aria-label="<?php echo esc_attr(sprintf(__('Partager sur %s', 'g2rd'), $label)); ?>"
				>
					<span class="g2rd-share-buttons__icon">
						<?php if ($icon) : ?>
							<svg viewBox="0 0 24 24" width="<?php echo (int) $icon_size; ?>" height="<?php echo (int) $icon_size; ?>" aria-hidden="true" focusable="false">
								<?php echo $icon; ?>
							</svg>
						<?php endif; ?>
					</span>
					<?php if ($style_variant === 'full' || $style_variant === 'rounded') : ?>
						<span class="g2rd-share-buttons__label"><?php echo esc_html($label); ?></span>
					<?php endif; ?>
				</a>
			</li>
			<?php
		}
		?>
	</ul>
</div>
