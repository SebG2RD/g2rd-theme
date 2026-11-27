<?php
/**
 * Title: Bandeau newsletter
 * Slug: rescue18/newsletter
 * Categories: rescue18
 * Description: Inscription à la newsletter hebdomadaire (formulaire MailPoet). Sans MailPoet, un lien vers le contact remplace le formulaire.
 *
 * @package G2RD_Rescue18
 */

\defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"tagName":"section","anchor":"newsletter","align":"full","className":"r18-nl","layout":{"type":"default"}} -->
<section id="newsletter" class="wp-block-group alignfull r18-nl"><!-- wp:group {"className":"r18-nl__in","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
<div class="wp-block-group r18-nl__in"><!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"className":"r18-eyebrow"} -->
<p class="r18-eyebrow">Newsletter hebdomadaire</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">La veille du secours dans ta boîte mail.</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Articles, nouveaux GDO et GTO, RETEX, évolutions juridiques et épisodes du podcast. Une fois par semaine.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:rescue18/newsletter {"formulaire":1} /-->
</div>
<!-- /wp:group -->
</section>
<!-- /wp:group -->
