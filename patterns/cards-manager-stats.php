<?php
/**
 * Title: Cartes statistiques (WP Manager)
 * Slug: g2rd-theme/cards-manager-stats
 * Description: Trio de cartes façon dashboard wp-manager — claire, sombre et action (dégradé). 100% tokens FSE + variations de styles.
 * Categories: featured, columns
 * Keywords: cartes, stats, dashboard, wp-manager, kpi
 * Viewport Width: 1400
 * Block Types: core/columns
 * Inserter: true
 * Text Domain: g2rd
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|l","bottom":"var:preset|spacing|l","right":"var:preset|spacing|m","left":"var:preset|spacing|m"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--l);padding-bottom:var(--wp--preset--spacing--l);padding-right:var(--wp--preset--spacing--m);padding-left:var(--wp--preset--spacing--m)">

	<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|m","left":"var:preset|spacing|m"}}}} -->
	<div class="wp-block-columns">

		<!-- Carte claire -->
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"is-style-card","layout":{"type":"constrained"}} -->
			<div class="wp-block-group is-style-card">
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"2rem","lineHeight":"1"}}} --><p style="font-size:2rem;line-height:1">🧩</p><!-- /wp:paragraph -->
				<!-- wp:paragraph {"textColor":"muted","style":{"typography":{"fontWeight":"600","letterSpacing":"0.1em","textTransform":"uppercase"}},"fontSize":"s"} --><p class="has-muted-color has-text-color has-s-font-size" style="font-weight:600;letter-spacing:0.1em;text-transform:uppercase">Extensions totales</p><!-- /wp:paragraph -->
				<!-- wp:heading {"textColor":"primary","style":{"typography":{"fontSize":"3rem","fontWeight":"800","lineHeight":"1"}}} --><h2 class="wp-block-heading has-primary-color has-text-color" style="font-size:3rem;font-weight:800;line-height:1">24</h2><!-- /wp:heading -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- Carte action (dégradé) -->
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"is-style-card-action","layout":{"type":"constrained"}} -->
			<div class="wp-block-group is-style-card-action">
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"2rem","lineHeight":"1"}}} --><p style="font-size:2rem;line-height:1">🚀</p><!-- /wp:paragraph -->
				<!-- wp:paragraph {"textColor":"white","style":{"typography":{"fontWeight":"600","letterSpacing":"0.1em","textTransform":"uppercase"}},"fontSize":"s"} --><p class="has-white-color has-text-color has-s-font-size" style="font-weight:600;letter-spacing:0.1em;text-transform:uppercase">Mises à jour prêtes</p><!-- /wp:paragraph -->
				<!-- wp:heading {"textColor":"white","style":{"typography":{"fontSize":"3rem","fontWeight":"800","lineHeight":"1"}}} --><h2 class="wp-block-heading has-white-color has-text-color" style="font-size:3rem;font-weight:800;line-height:1">06</h2><!-- /wp:heading -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- Carte sombre -->
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"is-style-card-dark","layout":{"type":"constrained"}} -->
			<div class="wp-block-group is-style-card-dark">
				<!-- wp:paragraph {"textColor":"secondary","style":{"typography":{"fontSize":"2rem","lineHeight":"1"}}} --><p class="has-secondary-color has-text-color" style="font-size:2rem;line-height:1">🛡️</p><!-- /wp:paragraph -->
				<!-- wp:paragraph {"textColor":"secondary","style":{"typography":{"fontWeight":"600","letterSpacing":"0.1em","textTransform":"uppercase"}},"fontSize":"s"} --><p class="has-secondary-color has-text-color has-s-font-size" style="font-weight:600;letter-spacing:0.1em;text-transform:uppercase">Score de sécurité</p><!-- /wp:paragraph -->
				<!-- wp:heading {"textColor":"white","style":{"typography":{"fontSize":"3rem","fontWeight":"800","lineHeight":"1"}}} --><h2 class="wp-block-heading has-white-color has-text-color" style="font-size:3rem;font-weight:800;line-height:1">100%</h2><!-- /wp:heading -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->

</div>
<!-- /wp:group -->
