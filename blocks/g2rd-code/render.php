<?php

/**
 * Render template pour le bloc code G2RD
 * Rendu serveur avec highlight.php (0 JS en frontend)
 *
 * @var array    $attributes Attributs du bloc.
 * @var string   $content    Contenu (vide pour bloc dynamique).
 * @var WP_Block $block      Instance du bloc.
 *
 * @package G2RD
 */

defined('ABSPATH') || exit;

// Helpers et liste des langues : tout reste dans le dossier du bloc (languages.json).
require_once __DIR__ . '/prettycode-helpers.php';

$source     = $attributes['source'] ?? __('No code to display', 'g2rd');
$file       = $attributes['file'] ?? '';
$language   = $attributes['language'] ?? 'html';
$theme      = $attributes['theme'] ?? 'monokai';
$font_size  = max(10, min(24, (int) ($attributes['fontSize'] ?? 14)));
$start_line = (int) ($attributes['startLine'] ?? 1);
$show_lines = (bool) ($attributes['showLines'] ?? true);
$wrap_lines = (bool) ($attributes['wrapLines'] ?? true);
$align         = $attributes['align'] ?? '';
$custom_class  = $attributes['className'] ?? '';
$border        = $attributes['border'] ?? [];
$border_radius = (int) ($attributes['borderRadius'] ?? 0);
$shadow_val    = $attributes['shadow'] ?? '';

$hljs_lang  = g2rd_prettycode_lang_to_hljs($language);
$align_class = $align ? 'align' . $align : '';

// Coloration via scrivo/highlight.php (install : composer install à la racine du thème).
$highlighted = '';
if (g2rd_code_block_ensure_highlight_loaded()) {
	try {
		$hl     = new \Highlight\Highlighter();
		$result = $hl->highlight($hljs_lang, $source);
		$highlighted = $result->value;
	} catch (\Throwable $e) {
		$highlighted = esc_html($source);
	}
} else {
	$highlighted = esc_html($source);
}

// Numéros de ligne
$line_numbers_html = '';
if ($show_lines) {
	$lines = substr_count($highlighted, "\n") + 1;
	$numbers = [];
	for ($i = $start_line; $i < $start_line + $lines; $i++) {
		$numbers[] = '<span>' . $i . '</span>';
	}
	$line_numbers_html = '<span class="g2rd-code__lines" aria-hidden="true">' . implode("\n", $numbers) . '</span>';
}

// Enqueue le CSS du thème depuis vendor (chemins relatifs à la racine du thème)
$theme_file = 'vendor/scrivo/highlight.php/styles/' . $theme . '.css';
$g2rd_theme_dir = \get_template_directory();
$g2rd_theme_uri = \get_template_directory_uri();
$g2rd_theme_ver = (string) \wp_get_theme()->get('Version');
if (file_exists($g2rd_theme_dir . '/' . $theme_file)) {
	wp_enqueue_style(
		'g2rd-hljs-theme-' . $theme,
		$g2rd_theme_uri . '/' . $theme_file,
		[],
		$g2rd_theme_ver
	);
}

// Label langue
$languages  = g2rd_prettycode_get_languages();
$key        = array_search($language, array_column($languages, 'value'), true);
$lang_label = ($key !== false) ? $languages[$key]['label'] : $language;

$wrap_class = $wrap_lines ? ' g2rd-code--wrap' : '';

// Build border styles
$extra_styles = ["--wrb-code-font-size: {$font_size}px"];

if (!empty($border)) {
	if (isset($border['top']) || isset($border['right']) || isset($border['bottom']) || isset($border['left'])) {
		foreach (['top', 'right', 'bottom', 'left'] as $side) {
			if (isset($border[$side])) {
				$s = $border[$side];
				if (!empty($s['color'])) {
					$extra_styles[] = "border-{$side}-color:{$s['color']}";
				}
				if (!empty($s['width'])) {
					$extra_styles[] = "border-{$side}-width:{$s['width']}";
				}
				$st = $s['style'] ?? ((!empty($s['color']) || !empty($s['width'])) ? 'solid' : '');
				if (!empty($st)) {
					$extra_styles[] = "border-{$side}-style:{$st}";
				}
			}
		}
	} else {
		if (!empty($border['color'])) {
			$extra_styles[] = "border-color:{$border['color']}";
		}
		if (!empty($border['width'])) {
			$extra_styles[] = "border-width:{$border['width']}";
		}
		$st = $border['style'] ?? ((!empty($border['color']) || !empty($border['width'])) ? 'solid' : '');
		if (!empty($st)) {
			$extra_styles[] = "border-style:{$st}";
		}
	}
}

if ($border_radius > 0) {
	$extra_styles[] = "border-radius:{$border_radius}px";
	$extra_styles[] = "overflow:hidden";
}

if (!empty($shadow_val)) {
	$extra_styles[] = "box-shadow:{$shadow_val}";
}

$wrapper_attrs = get_block_wrapper_attributes([
	'class' => "g2rd-code hljs $align_class $custom_class $wrap_class",
	'style' => implode(';', $extra_styles),
]);
?>
<div <?php echo $wrapper_attrs; ?>>
	<header class="g2rd-code__header">
		<span class="g2rd-code__lang is-lang-<?php echo esc_attr($language); ?>">
			<?php echo esc_html($lang_label); ?>
		</span>
		<?php if (!empty($file)) : ?>
			<span class="g2rd-code__file"><?php echo esc_html($file); ?></span>
		<?php endif; ?>
	</header>
	<pre class="g2rd-code__pre"><?php echo $line_numbers_html; ?><code class="hljs language-<?php echo esc_attr($hljs_lang); ?>"><?php echo $highlighted; ?></code></pre>
</div>