<?php
/**
 * Rendu frontend — Bloc FAQ G2RD (unifié SEO/GEO)
 *
 * Deux modes :
 *  - Standard : accordéon button/ARIA + animation CSS + JS (view.js)
 *  - GEO      : <details>/<summary> + microdata schema.org + JSON-LD FAQPage
 *
 * @package G2RD
 * @since   1.4.0
 *
 * @var array  $attributes Attributs du bloc
 * @var string $content    Contenu HTML interne
 */

$items = isset( $attributes['items'] ) && is_array( $attributes['items'] )
	? $attributes['items']
	: [];

$items = array_filter(
	$items,
	static function ( $item ) {
		return ! empty( $item['question'] ) && ! empty( $item['answer'] );
	}
);

if ( empty( $items ) ) {
	return;
}

$items = array_values( $items );

$optimize_geo   = ! empty( $attributes['optimizeForGEO'] );
$open_first     = ! empty( $attributes['openFirst'] );
$allow_multiple = ! empty( $attributes['allowMultiple'] );
$icon_type      = sanitize_text_field( $attributes['iconType'] ?? 'plus-minus' );
$border_radius  = absint( $attributes['borderRadius'] ?? 8 );
$show_header    = ! empty( $attributes['showHeader'] );
$header_text    = isset( $attributes['headerText'] ) ? sanitize_text_field( $attributes['headerText'] ) : __( 'Questions fréquentes', 'g2rd' );
$header_icon    = isset( $attributes['headerIcon'] )  ? sanitize_text_field( $attributes['headerIcon'] )  : '❓';

$question_color  = isset( $attributes['questionColor'] )  ? sanitize_hex_color( $attributes['questionColor'] )  : '';
$answer_color    = isset( $attributes['answerColor'] )    ? sanitize_hex_color( $attributes['answerColor'] )    : '';
$icon_color      = isset( $attributes['iconColor'] )      ? sanitize_hex_color( $attributes['iconColor'] )      : '';
$bg_color        = isset( $attributes['backgroundColor'] ) ? sanitize_hex_color( $attributes['backgroundColor'] ) : '';
$border_color    = isset( $attributes['borderColor'] )    ? sanitize_hex_color( $attributes['borderColor'] )    : '';
$separator_color = isset( $attributes['separatorColor'] ) ? sanitize_hex_color( $attributes['separatorColor'] ) : '';

$question_font_size = ! empty( $attributes['questionFontSize'] ) ? sanitize_text_field( $attributes['questionFontSize'] ) : '';
$answer_font_size   = ! empty( $attributes['answerFontSize'] )   ? sanitize_text_field( $attributes['answerFontSize'] )   : '';

$question_font_style = $question_font_size ? ' style="font-size:' . esc_attr( $question_font_size ) . ';"' : '';
$answer_font_style   = $answer_font_size   ? ' style="font-size:' . esc_attr( $answer_font_size )   . ';"' : '';

$css_parts = [];
if ( $question_color )  { $css_parts[] = "--g2rd-faq-question-color:{$question_color}"; }
if ( $answer_color )    { $css_parts[] = "--g2rd-faq-answer-color:{$answer_color}"; }
if ( $icon_color )      { $css_parts[] = "--g2rd-faq-icon-color:{$icon_color}"; }
if ( $bg_color )        { $css_parts[] = "--g2rd-faq-bg:{$bg_color}"; }
if ( $border_color )    { $css_parts[] = "--g2rd-faq-border:{$border_color}"; }
if ( $separator_color ) { $css_parts[] = "--g2rd-faq-separator:{$separator_color}"; }
$css_parts[] = "--g2rd-faq-radius:{$border_radius}px";

$list_style = esc_attr( implode( ';', $css_parts ) );

$block_class = 'g2rd-faq' . ( $optimize_geo ? ' g2rd-faq--geo' : '' );
$wrapper_attributes = get_block_wrapper_attributes( [
	'class'               => $block_class,
	'data-open-first'     => $open_first     ? 'true' : 'false',
	'data-allow-multiple' => $allow_multiple ? 'true' : 'false',
	'data-icon-type'      => $icon_type,
] );

if ( $optimize_geo ) :
?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

	<?php if ( $show_header ) : ?>
	<div class="g2rd-faq__header">
		<?php if ( $header_icon ) : ?>
		<span class="g2rd-faq__header-icon" aria-hidden="true"><?php echo esc_html( $header_icon ); ?></span>
		<?php endif; ?>
		<span class="g2rd-faq__header-title"><?php echo esc_html( $header_text ); ?></span>
		<span class="g2rd-faq__badge">schema.org</span>
	</div>
	<?php endif; ?>

	<div
		class="g2rd-faq__list"
		style="<?php echo $list_style; ?>"
		itemscope
		itemtype="https://schema.org/FAQPage"
	>
		<?php foreach ( $items as $i => $item ) :
			$question = sanitize_text_field( $item['question'] ?? '' );
			$answer   = nl2br( wp_kses_post( $item['answer'] ?? '' ) );
			if ( empty( $question ) || empty( $item['answer'] ) ) {
				continue;
			}
		?>
		<div
			class="g2rd-faq__item"
			itemscope
			itemprop="mainEntity"
			itemtype="https://schema.org/Question"
		>
			<details class="g2rd-faq__details"<?php echo ( $open_first && 0 === $i ) ? ' open' : ''; ?>>
				<summary class="g2rd-faq__question" itemprop="name">
					<span class="g2rd-faq__question-text"<?php echo $question_font_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( $question ); ?></span>
					<span class="g2rd-faq__icon" aria-hidden="true">▾</span>
				</summary>
				<div
					class="g2rd-faq__answer"
					itemscope
					itemprop="acceptedAnswer"
					itemtype="https://schema.org/Answer"
				>
					<div class="g2rd-faq__answer-inner" itemprop="text"<?php echo $answer_font_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
						<p><?php echo wp_kses_post( $answer ); ?></p>
					</div>
				</div>
			</details>
		</div>
		<?php endforeach; ?>
	</div>

	<?php
	$schema_items = [];
	foreach ( $items as $item ) {
		$question = sanitize_text_field( $item['question'] ?? '' );
		$answer   = wp_strip_all_tags( $item['answer'] ?? '' );
		if ( empty( $question ) || empty( $answer ) ) {
			continue;
		}
		$schema_items[] = [
			'@type'          => 'Question',
			'name'           => $question,
			'acceptedAnswer' => [
				'@type' => 'Answer',
				'text'  => $answer,
			],
		];
	}

	// Accumulate items for a single merged FAQPage JSON-LD emitted in wp_footer.
	// This prevents duplicate FAQPage schemas when multiple FAQ blocks coexist on the same page.
	if ( ! empty( $schema_items ) ) {
		if ( ! isset( $GLOBALS['g2rd_faq_schema_items'] ) ) {
			$GLOBALS['g2rd_faq_schema_items'] = [];
			\add_action(
				'wp_footer',
				static function () {
					if ( empty( $GLOBALS['g2rd_faq_schema_items'] ) ) {
						return;
					}
					$schema = [
						'@context'   => 'https://schema.org',
						'@type'      => 'FAQPage',
						'mainEntity' => $GLOBALS['g2rd_faq_schema_items'],
					];
					?>
					<script type="application/ld+json">
					<?php echo \wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</script>
					<?php
				},
				5
			);
		}
		\array_push( $GLOBALS['g2rd_faq_schema_items'], ...$schema_items );
	}
	?>

</div>
<?php else : ?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

	<?php if ( $show_header ) : ?>
	<div class="g2rd-faq__header">
		<?php if ( $header_icon ) : ?>
		<span class="g2rd-faq__header-icon" aria-hidden="true"><?php echo esc_html( $header_icon ); ?></span>
		<?php endif; ?>
		<span class="g2rd-faq__header-title"><?php echo esc_html( $header_text ); ?></span>
	</div>
	<?php endif; ?>

	<div class="g2rd-faq__list" style="<?php echo $list_style; ?>">
		<?php foreach ( $items as $i => $item ) :
			$question = sanitize_text_field( $item['question'] ?? '' );
			$answer   = nl2br( wp_kses_post( $item['answer'] ?? '' ) );
			if ( empty( $question ) || empty( $answer ) ) {
				continue;
			}
			$is_open = $open_first && 0 === $i;
		?>
		<div class="g2rd-faq__item<?php echo $is_open ? ' is-open' : ''; ?>">
			<button
				class="g2rd-faq__question"
				type="button"
				id="<?php echo esc_attr( 'g2rd-faq-btn-' . $i ); ?>"
				aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>"
				aria-controls="<?php echo esc_attr( 'g2rd-faq-answer-' . $i ); ?>"
			>
				<span class="g2rd-faq__question-text"<?php echo $question_font_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( $question ); ?></span>
				<span class="g2rd-faq__icon" aria-hidden="true"></span>
			</button>
			<div
				class="g2rd-faq__answer"
				id="<?php echo esc_attr( 'g2rd-faq-answer-' . $i ); ?>"
				role="region"
				aria-labelledby="<?php echo esc_attr( 'g2rd-faq-btn-' . $i ); ?>"
			>
				<div class="g2rd-faq__answer-inner"<?php echo $answer_font_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
					<p><?php echo wp_kses_post( $answer ); ?></p>
				</div>
			</div>
		</div>
		<?php endforeach; ?>
	</div>

</div>
<?php endif; ?>
