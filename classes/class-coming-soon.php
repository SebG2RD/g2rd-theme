<?php

/**
 * Mode « Bientôt disponible »
 *
 * Redirige les visiteurs non connectés vers une page de maintenance
 * lorsque le mode est activé depuis les options du thème.
 *
 * @package G2RD
 * @since 1.0.0
 * @license EUPL-1.2
 * @copyright (c) 2024 Sebastien GERARD
 * @link https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 */

namespace G2RD;

/**
 * Classe ComingSoon
 */
class ComingSoon
{
    /**
     * Enregistre les hooks
     *
     * @return void
     */
    public function register_hooks(): void
    {
        \add_action('template_redirect', [$this, 'maybeRedirect']);
    }

    /**
     * Redirige vers la page « Bientôt disponible » si le mode est actif.
     * Ignore les utilisateurs connectés, les flux, REST et les requêtes JSON.
     *
     * @return void
     */
    public function maybeRedirect(): void
    {
        if ( \is_user_logged_in() ) {
            return;
        }

        if ( \is_feed() || \is_robots() || \wp_is_json_request() ) {
            return;
        }

        if ( \defined('REST_REQUEST') && REST_REQUEST ) {
            return;
        }

        $options = (array) \get_option('g2rd_coming_soon', []);
        if ( empty( $options['enabled'] ) ) {
            return;
        }

        $page_id = \absint( $options['page_id'] ?? 0 );
        if ( ! $page_id || ! \get_post( $page_id ) ) {
            return;
        }

        if ( \is_page( $page_id ) ) {
            return;
        }

        \wp_safe_redirect( \get_permalink( $page_id ), 302 );
        exit;
    }
}
