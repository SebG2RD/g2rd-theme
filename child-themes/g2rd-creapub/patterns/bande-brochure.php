<?php
/**
 * Title: Créa Pub — Bandeau brochure
 * Slug: g2rd-creapub/bande-brochure
 * Categories: creapub
 * Description: Lien vers la brochure PDF, avec son icône rose, comme sur le site actuel.
 *
 * @package G2RD_CreaPub
 */

?>
<!-- wp:group {"align":"full","className":"creapub-brochure-band gsap-fade-in","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull creapub-brochure-band gsap-fade-in"><!-- wp:paragraph -->
<p><a href="<?php echo \esc_url( \G2RD_CreaPub\media_url( 'brochure' ) ); ?>" target="_blank" rel="noreferrer noopener">Consultez notre <strong>brochure</strong></a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
