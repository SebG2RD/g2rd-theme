<?php
/**
 * Rendu frontend — Bloc FAQ GEO
 *
 * Génère :
 *  1. L'accordéon HTML accessible (<details>/<summary>)
 *  2. Le schema.org FAQPage en JSON-LD
 *
 * @package G2RD
 * @since   1.3.4
 *
 * @var array  $attributes Attributs du bloc (items, etc.)
 * @var string $content    Contenu HTML interne (vide pour les blocs dynamiques)
 */

$items = isset( $attributes['items'] ) && is_array( $attributes['items'] )
	? $attributes['items']
	: [];

// Filtrer les items vides
$items = array_filter(
	$items,
	static function ( $item ) {
		return ! empty( $item['question'] ) && ! empty( $item['answer'] );
	}
);

if ( empty( $items ) ) {
	return;
}

// Réindexer après le filtre
$items = array_values( $items );

$question_font_size = ! empty( $attributes['questionFontSize'] ) ? esc_attr( $attributes['questionFontSize'] ) : '';
$answer_font_size   = ! empty( $attributes['answerFontSize'] ) ? esc_attr( $attributes['answerFontSize'] ) : '';
$q_style            = $question_font_size ? ' style="font-size:' . $question_font_size . ';"' : '';
$a_style            = $answer_font_size ? ' style="font-size:' . $answer_font_size . ';"' : '';

$wrapper_attributes = get_block_wrapper_attributes( [
	'class' => 'wp-block-g2rd-geo-faq',
] );
?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

	<?php /* Titre accessible */ ?>
	<div class="geo-faq__header" aria-hidden="true">
		<span class="geo-faq__header-icon">❓</span>
		<span class="geo-faq__header-title">Questions fréquentes</span>
	</div>

	<?php /* Accordéon FAQ */ ?>
	<div
		class="geo-faq__items"
		itemscope
		itemtype="https://schema.org/FAQPage"
	>
		<?php foreach ( $items as $i => $item ) :
			$question = sanitize_text_field( $item['question'] ?? '' );
			$answer   = wp_kses_post( $item['answer'] ?? '' );

			if ( empty( $question ) || empty( $answer ) ) {
				continue;
			}
		?>
		<div
			class="geo-faq__item"
			itemscope
			itemprop="mainEntity"
			itemtype="https://schema.org/Question"
		>
			<details class="geo-faq__details"<?php echo $i === 0 ? ' open' : ''; ?>>
				<summary
					class="geo-faq__question"
					itemprop="name"
					<?php echo $q_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- valeur préalablement échappée via esc_attr() ?>
				>
					<span class="geo-faq__question-text"><?php echo esc_html( $question ); ?></span>
					<span class="geo-faq__chevron" aria-hidden="true">▾</span>
				</summary>

				<div
					class="geo-faq__answer"
					itemscope
					itemprop="acceptedAnswer"
					itemtype="https://schema.org/Answer"
				>
					<div class="geo-faq__answer-text" itemprop="text"<?php echo $a_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- valeur préalablement échappée via esc_attr() ?>>
						<?php echo wp_kses_post( nl2br( $answer ) ); ?>
					</div>
				</div>
			</details>
		</div>
		<?php endforeach; ?>
	</div>

	<?php
	/* JSON-LD FAQPage schema.org — items collectés pour fusion en wp_footer */
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
