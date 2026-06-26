<?php

/**
 * Title: Card Call to Action G2RD
 * Slug: G2RD-theme/card-call-to-action-G2RD
 * Description: Présentation de l'entreprise en bandeau sombre avec double CTA et galerie
 * Categories: card, hero
 * Keywords: présentation, carte, hero
 * Viewport Width: 1400
 * Block Types: core/group
 * Post Types:
 * Inserter: true
 * Text Domain: g2rd
 */
?>
<!-- wp:group {"metadata":{"name":"G2RD Hero"},"className":"is-style-primary","style":{"spacing":{"padding":{"top":"var:preset|spacing|m","bottom":"var:preset|spacing|m"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group is-style-primary" style="padding-top:var(--wp--preset--spacing--m);padding-bottom:var(--wp--preset--spacing--m)"><!-- wp:columns {"verticalAlignment":"center","style":{"spacing":{"padding":{"top":"var:preset|spacing|s","bottom":"var:preset|spacing|s","left":"var:preset|spacing|s","right":"var:preset|spacing|s"}}}} -->
    <div class="wp-block-columns are-vertically-aligned-center" style="padding-top:var(--wp--preset--spacing--s);padding-right:var(--wp--preset--spacing--s);padding-bottom:var(--wp--preset--spacing--s);padding-left:var(--wp--preset--spacing--s)"><!-- wp:column {"verticalAlignment":"center","width":"55%"} -->
        <div class="wp-block-column is-vertically-aligned-center" style="flex-basis:55%"><!-- wp:paragraph {"style":{"typography":{"fontWeight":"600","letterSpacing":"0.06em","textTransform":"uppercase"},"elements":{"link":{"color":{"text":"var:preset|color|secondary"}}}},"textColor":"secondary","fontSize":"s"} -->
            <p class="has-secondary-color has-text-color has-link-color has-s-font-size" style="font-weight:600;letter-spacing:0.06em;text-transform:uppercase">Besoin d'un site Internet ?</p>
            <!-- /wp:paragraph -->

            <!-- wp:heading {"textColor":"white","style":{"typography":{"fontWeight":"800","letterSpacing":"-0.02em"},"spacing":{"margin":{"top":"var:preset|spacing|xs"}}}} -->
            <h2 class="wp-block-heading has-white-color has-text-color" style="margin-top:var(--wp--preset--spacing--xs);font-weight:800;letter-spacing:-0.02em">G2RD,<br>L'agence <mark style="background-color:rgba(0,0,0,0);border-bottom:0.16em solid var(--wp--preset--color--secondary)" class="has-inline-color has-secondary-color">WordPress</mark></h2>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"textColor":"white","style":{"typography":{"lineHeight":"1.7"},"spacing":{"margin":{"top":"var:preset|spacing|s"}}}} -->
            <p class="has-white-color has-text-color" style="margin-top:var(--wp--preset--spacing--s);line-height:1.7">De la conception de votre marque à la réalisation d'un site sur mesure avec WordPress, G2RD c'est une équipe de passionnés à votre écoute avec plus de 5 ans d'expérience.</p>
            <!-- /wp:paragraph -->

            <!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|m"},"blockGap":"1rem"},"layout":{"type":"flex","flexWrap":"wrap"}}} -->
            <div class="wp-block-buttons"><!-- wp:button {"style":{"typography":{"fontWeight":"700"}}} -->
                <div class="wp-block-button"><a class="wp-block-button__link wp-element-button" style="font-weight:700">Prendre rendez-vous</a></div>
                <!-- /wp:button -->

                <!-- wp:button {"className":"is-style-outline","textColor":"white","style":{"border":{"color":"color-mix(in srgb, var(--wp--preset--color--white) 40%, transparent)","width":"1px"}}} -->
                <div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-white-color has-text-color wp-element-button" style="border-color:color-mix(in srgb, var(--wp--preset--color--white) 40%, transparent);border-width:1px">Nos services</a></div>
                <!-- /wp:button -->
            </div>
            <!-- /wp:buttons -->

            <!-- wp:separator {"backgroundColor":"blue-soft","className":"is-style-wide","style":{"spacing":{"margin":{"top":"var:preset|spacing|m","bottom":"var:preset|spacing|m"}}}} -->
            <hr class="wp-block-separator has-text-color has-blue-soft-color has-alpha-channel-opacity has-blue-soft-background-color has-background is-style-wide" style="margin-top:var(--wp--preset--spacing--m);margin-bottom:var(--wp--preset--spacing--m)" />
            <!-- /wp:separator -->

            <!-- wp:columns {"verticalAlignment":"center"} -->
            <div class="wp-block-columns are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center"} -->
                <div class="wp-block-column is-vertically-aligned-center"><!-- wp:paragraph {"textColor":"white","fontSize":"s"} -->
                    <p class="has-white-color has-text-color has-s-font-size">Retrouvez-nous sur les réseaux sociaux :</p>
                    <!-- /wp:paragraph -->
                </div>
                <!-- /wp:column -->

                <!-- wp:column {"verticalAlignment":"center"} -->
                <div class="wp-block-column is-vertically-aligned-center"><!-- wp:social-links {"iconColor":"primary","iconColorValue":"var(--wp--preset--color--primary)","iconBackgroundColor":"white","iconBackgroundColorValue":"var(--wp--preset--color--white)"} -->
                    <ul class="wp-block-social-links has-icon-color has-icon-background-color"><!-- wp:social-link {"url":"https://www.facebook.com/g2rdweb","service":"facebook"} /-->

                        <!-- wp:social-link {"url":"https://www.linkedin.com/company/g2rd-developpeur-web/","service":"linkedin"} /-->

                        <!-- wp:social-link {"url":"https://www.instagram.com/g2rdagenceweb/","service":"instagram"} /-->

                        <!-- wp:social-link {"url":"https://bsky.app/profile/g2rd-agence-web.bsky.social","service":"bluesky"} /-->
                    </ul>
                    <!-- /wp:social-links -->
                </div>
                <!-- /wp:column -->
            </div>
            <!-- /wp:columns -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column {"verticalAlignment":"center","width":"45%"} -->
        <div class="wp-block-column is-vertically-aligned-center" style="flex-basis:45%"><!-- wp:image {"sizeSlug":"full","linkDestination":"none","style":{"border":{"radius":"var:custom|radius|l"},"shadow":"var:preset|shadow|magic-xl"}} -->
            <figure class="wp-block-image size-full has-custom-border" style="border-radius:var(--wp--custom--radius--l);box-shadow:var(--wp--preset--shadow--magic-xl)"><img alt="Réalisation web G2RD" /></figure>
            <!-- /wp:image -->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
</div>
<!-- /wp:group -->
