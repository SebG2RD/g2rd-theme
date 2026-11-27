<?php
/**
 * Title: Créa Pub — Bloc contact
 * Slug: g2rd-creapub/contact
 * Categories: creapub
 * Description: Coordonnées + emplacement carte + zone formulaire. Formulaire : extension au choix (avec champ honeypot activé) ; carte : bloc g2rd/map.
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"4rem","bottom":"4rem"}}},"backgroundColor":"noir","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-noir-background-color has-background" style="padding-top:4rem;padding-bottom:4rem">
  <!-- wp:columns -->
  <div class="wp-block-columns">
    <!-- wp:column {"width":"42%"} -->
    <div class="wp-block-column" style="flex-basis:42%">
      <!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Parlons de votre <strong>projet</strong></h2><!-- /wp:heading -->
      <!-- wp:paragraph {"textColor":"gris-txt"} --><p class="has-gris-txt-color has-text-color">Réponse sous 48 h — devis gratuit et sans engagement.</p><!-- /wp:paragraph -->
      <!-- wp:paragraph --><p><strong>Adresse</strong><br>56, avenue de Rouen — 27200 Vernon</p><!-- /wp:paragraph -->
      <!-- wp:paragraph --><p><strong>Téléphone</strong><br><a href="tel:+33232213116">02 32 21 31 16</a></p><!-- /wp:paragraph -->
      <!-- wp:paragraph --><p><strong>Email</strong><br><a href="mailto:contact@crea-pub-communication.fr">contact@crea-pub-communication.fr</a></p><!-- /wp:paragraph -->
      <!-- wp:paragraph --><p><strong>Horaires</strong><br>Lun–Ven · 9h – 18h</p><!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
    <!-- wp:column -->
    <div class="wp-block-column">
      <!-- wp:group {"style":{"border":{"radius":"14px","width":"1px"},"spacing":{"padding":{"top":"1.5rem","bottom":"1.5rem","left":"1.5rem","right":"1.5rem"}}},"borderColor":"ligne","backgroundColor":"noir-3","layout":{"type":"constrained"}} -->
      <div class="wp-block-group has-border-color has-ligne-border-color has-noir-3-background-color has-background" style="border-width:1px;border-radius:14px;padding:1.5rem">
        <!-- wp:paragraph {"textColor":"gris-soft"} --><p class="has-gris-soft-color has-text-color">[ Insérer ici le formulaire — nom, email/téléphone, message + honeypot ]</p><!-- /wp:paragraph -->
      </div>
      <!-- /wp:group -->
      <!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"1rem"}}},"textColor":"gris-soft"} --><p class="has-gris-soft-color has-text-color" style="margin-top:1rem">[ Insérer ici le bloc g2rd/map — 56 avenue de Rouen, Vernon ]</p><!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
  </div>
  <!-- /wp:columns -->
</div>
<!-- /wp:group -->
