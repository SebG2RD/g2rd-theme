<?php

/**
 * Intégration FluentCart — onglets Support et Boutique
 *
 * Ajoute les entrées « Support » et « Boutique » dans le menu du portail client
 * FluentCart et enregistre les endpoints associés (shortcodes Fluent Support / Fluent Cart).
 *
 * @package G2RD
 * @since 1.0.0
 * @license EUPL-1.2
 * @copyright (c) 2024 Sebastien GERARD
 * @link https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 */

namespace G2RD;

/**
 * Classe FluentCartSupport
 */
class FluentCartSupport {
    /**
     * Enregistre les hooks
     *
     * @return void
     */
    public function register_hooks(): void {
        \add_filter('fluent_cart/global_customer_menu_items', [$this, 'addSupportMenuItem'], 10, 2);
        \add_filter('fluent_cart/customer_portal/custom_endpoints', [$this, 'addSupportEndpoint']);
    }

    /**
     * Ajoute les entrées « Support » et « Boutique » dans le menu du portail client.
     * Elles sont insérées juste avant « Profil » si cette entrée existe.
     *
     * @param array $items   Éléments du menu.
     * @param array $context Contexte Fluent Cart (ex. base_url).
     * @return array
     */
    public function addSupportMenuItem(array $items, array $context): array {
        $support_item = [
            'label'     => \__('Support', 'g2rd'),
            'css_class' => 'fct_route fct-menu-item-support',
            'link'      => \trailingslashit($context['base_url']) . 'support',
            'icon_svg'  => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M10 2.5C5.85786 2.5 2.5 5.85786 2.5 10C2.5 14.1421 5.85786 17.5 10 17.5C14.1421 17.5 17.5 14.1421 17.5 10C17.5 5.85786 14.1421 2.5 10 2.5ZM10 16C6.68629 16 4 13.3137 4 10C4 6.68629 6.68629 4 10 4C13.3137 4 16 6.68629 16 10C16 13.3137 13.3137 16 10 16ZM10.75 11.75V13.25H9.25V11.75H10.75ZM11.7406 8.36562L11.0688 9.05156C10.5312 9.59687 10.75 9.90625 10.75 10.625H9.25V10.25C9.25 9.42187 9.57812 8.67188 10.1094 8.13437L11.0344 7.19531C11.3125 6.925 11.5 6.55 11.5 6.125C11.5 5.29688 10.8281 4.625 10 4.625C9.17188 4.625 8.5 5.29688 8.5 6.125H7C7 4.46875 8.34375 3.125 10 3.125C11.6562 3.125 13 4.46875 13 6.125C13 6.85313 12.7031 7.51094 11.7406 8.36562Z" fill="currentColor"/></svg>',
        ];

        $boutique_item = [
            'label'     => \__('Boutique', 'g2rd'),
            'css_class' => 'fct_route fct-menu-item-boutique',
            'link'      => \trailingslashit($context['base_url']) . 'boutique',
            'icon_svg'  => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M17.5 5H15.8333C15.8333 3.15905 14.3243 1.66667 12.5 1.66667C10.6757 1.66667 9.16667 3.15905 9.16667 5H2.5L1.66667 17.5H18.3333L17.5 5ZM12.5 3.33333C13.4167 3.33333 14.1667 4.08333 14.1667 5H10.8333C10.8333 4.08333 11.5833 3.33333 12.5 3.33333ZM3.41667 15.8333L4.08333 6.66667H9.16667V8.33333H10.8333V6.66667H15.9167L16.5833 15.8333H3.41667Z" fill="currentColor"/></svg>',
        ];

        $profile_key = \array_search('profile', \array_keys($items), true);

        if (false !== $profile_key) {
            $items = \array_slice($items, 0, $profile_key, true)
                + ['support' => $support_item]
                + ['boutique' => $boutique_item]
                + \array_slice($items, $profile_key, null, true);
        } else {
            $items['support']  = $support_item;
            $items['boutique'] = $boutique_item;
        }

        return $items;
    }

    /**
     * Enregistre les endpoints « support » et « boutique » dans le portail client.
     *
     * @param array $endpoints Endpoints existants.
     * @return array
     */
    public function addSupportEndpoint(array $endpoints): array {
        $endpoints['support'] = [
            'render_callback' => [$this, 'renderSupportTab'],
        ];

        $endpoints['boutique'] = [
            'render_callback' => [$this, 'renderBoutiqueTab'],
        ];

        return $endpoints;
    }

    /**
     * Rendu de l'onglet Support dans le portail client.
     *
     * @return void
     */
    public function renderSupportTab(): void {
        ?>
        <div class="g2rd-fluentcart-support-tab">
            <h2><?php \esc_html_e('Support', 'g2rd'); ?></h2>
            <p><?php \esc_html_e('Ouvrez et suivez vos demandes directement depuis votre espace client.', 'g2rd'); ?></p>

            <?php if (\shortcode_exists('fluent_support_portal')) : ?>
                <div class="g2rd-fluentcart-support-portal">
                    <?php echo \do_shortcode('[fluent_support_portal]'); ?>
                </div>
            <?php else : ?>
                <div class="g2rd-fluentcart-support-fallback">
                    <p><?php \esc_html_e('Le portail de support n\'est pas disponible pour le moment. Vérifiez que Fluent Support et son shortcode sont bien actifs.', 'g2rd'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Rendu de l'onglet Boutique dans le portail client.
     *
     * @return void
     */
    public function renderBoutiqueTab(): void {
        ?>
        <div class="g2rd-fluentcart-boutique-tab">
            <h2><?php \esc_html_e('Boutique', 'g2rd'); ?></h2>
            <p><?php \esc_html_e('Découvrez et commandez nos produits directement depuis votre espace client.', 'g2rd'); ?></p>

            <?php if (\shortcode_exists('fluent_cart_products')) : ?>
                <div class="g2rd-fluentcart-boutique-products">
                    <?php echo \do_shortcode('[fluent_cart_products]'); ?>
                </div>
            <?php else : ?>
                <div class="g2rd-fluentcart-boutique-fallback">
                    <p><?php \esc_html_e('La boutique n\'est pas disponible pour le moment. Vérifiez que Fluent Cart et son shortcode sont bien actifs.', 'g2rd'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
