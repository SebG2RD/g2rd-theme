<?php
/**
 * Title: Accueil — Veille juridique
 * Slug: rescue18/accueil-juridique
 * Categories: rescue18
 * Description: Visuel « Le droit dans sa version officielle » et trois derniers textes suivis avec leur état.
 *
 * @package G2RD_Rescue18
 */

\defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"tagName":"section","anchor":"veille","align":"full","className":"r18-section r18-section--blanc","layout":{"type":"constrained","contentSize":"1254px"}} -->
<section id="veille" class="wp-block-group alignfull r18-section r18-section--blanc"><!-- wp:group {"className":"r18-legal-grid","layout":{"type":"default"}} -->
<div class="wp-block-group r18-legal-grid"><!-- wp:group {"className":"r18-legal-vis","layout":{"type":"default"}} -->
<div class="wp-block-group r18-legal-vis"><!-- wp:paragraph {"className":"r18-eyebrow"} -->
<p class="r18-eyebrow">Veille réglementaire</p>
<!-- /wp:paragraph -->
<!-- wp:group {"className":"r18-monitor","layout":{"type":"default"}} -->
<div class="wp-block-group r18-monitor"><!-- wp:paragraph {"className":"r18-monitor__t"} -->
<p class="r18-monitor__t">Le droit.<br>Dans sa version officielle.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"r18-monitor__s"} -->
<p class="r18-monitor__s">Source, statut, version et historique de chaque texte</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"r18-monitor__tags"} -->
<p class="r18-monitor__tags"><span>Légifrance</span><span>API PISTE</span><span>NOR · JORF · LEGI</span></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:paragraph {"className":"r18-legal-vis__note"} -->
<p class="r18-legal-vis__note">Un statut non confirmé par la source officielle reste « À vérifier ».</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"className":"r18-eyebrow"} -->
<p class="r18-eyebrow">05 / Juridique</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"className":"r18-h2"} -->
<h2 class="wp-block-heading r18-h2">Une veille juridique<br><mark style="background-color:rgba(0, 0, 0, 0)" class="has-inline-color has-r18-rouge-color">qui suit les changements.</mark></h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"r18-lead"} -->
<p class="r18-lead">Lois, décrets et arrêtés suivis par leurs identifiants officiels, avec détection des modifications, consolidations et abrogations.</p>
<!-- /wp:paragraph -->
<!-- wp:rescue18/veille-juridique {"variante":"compact","nombre":3} /-->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"r18-arrow"} -->
<div class="wp-block-button r18-arrow"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/documentations-referentiels/juridique/' ) ); ?>">Toute la veille juridique</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</section>
<!-- /wp:group -->
