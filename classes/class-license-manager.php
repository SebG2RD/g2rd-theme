<?php

/**
 * Gestionnaire de licence — FluentCart
 *
 * Pour l'instant : vérification de la présence d'une clé API uniquement.
 * Aucune validation distante, aucun blocage. L'intégration complète
 * avec l'API FluentCart sera finalisée ultérieurement.
 *
 * @package G2RD
 * @since 1.2.0
 * @license EUPL-1.2
 * @copyright (c) 2024 Sebastien GERARD
 * @link https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 */

namespace G2RD;

/**
 * Classe LicenseManager
 */
class LicenseManager {
    /** @var string Clé wp_options pour la clé API FluentCart */
    private const OPTION_API_KEY = 'g2rd_fluent_cart_api_key';

    /** @var string Action nonce pour la sauvegarde */
    private const NONCE_ACTION = 'g2rd_save_license_key';

    /** @var string Nom du champ nonce */
    private const NONCE_FIELD = 'g2rd_license_nonce';

    /**
     * Enregistre les hooks
     *
     * @return void
     */
    public function register_hooks(): void {
        \add_action('admin_post_g2rd_save_license_key', [$this, 'saveApiKey']);
        \add_action('g2rd_options_before_form',         [$this, 'renderSection']);
    }

    /**
     * Indique si une clé API est configurée.
     *
     * @return bool
     */
    public function isConfigured(): bool {
        return ! empty(\get_option(self::OPTION_API_KEY, ''));
    }

    /**
     * Retourne la clé API stockée.
     *
     * @return string
     */
    public function getApiKey(): string {
        return (string) \get_option(self::OPTION_API_KEY, '');
    }

    /**
     * Alias de isConfigured() pour la compatibilité avec GitHubUpdater.
     * À remplacer par une validation API complète lors de l'intégration FluentCart.
     *
     * @return bool
     */
    public function isLicenseValid(): bool {
        return $this->isConfigured();
    }

    /**
     * Sauvegarde la clé API soumise via le mini-formulaire.
     *
     * @return void
     */
    public function saveApiKey(): void {
        if (! \current_user_can('manage_options')) {
            \wp_die(\esc_html__('Accès refusé.', 'g2rd'), 403);
        }

        \check_admin_referer(self::NONCE_ACTION, self::NONCE_FIELD);

        $api_key = \sanitize_text_field(\wp_unslash($_POST[self::OPTION_API_KEY] ?? ''));
        \update_option(self::OPTION_API_KEY, $api_key);

        \wp_safe_redirect(
            \add_query_arg(
                ['page' => 'g2rd-options', 'license_saved' => '1'],
                \admin_url('themes.php')
            )
        );
        exit;
    }

    /**
     * Affiche la section licence dans la page d'options du thème.
     * Injectée via le hook g2rd_options_before_form — toujours en première position.
     *
     * @return void
     */
    public function renderSection(): void {
        $is_configured = $this->isConfigured();
        $api_key       = $this->getApiKey();
        $masked        = $is_configured
            ? \esc_html(\substr($api_key, 0, 8)) . \str_repeat('•', 8)
            : '';
?>
        <div class="g2rd-section g2rd-section--license">

            <h2 class="g2rd-section-title">
                <span class="dashicons dashicons-admin-network"></span>
                <?php \esc_html_e('Licence FluentCart', 'g2rd'); ?>
            </h2>

            <p class="g2rd-section-desc">
                <?php \esc_html_e('Renseignez votre clé API FluentCart pour activer les mises à jour automatiques du thème.', 'g2rd'); ?>
            </p>

            <?php if (isset($_GET['license_saved'])) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- indicateur de redirection POST→GET, pas de traitement de données ?>
                <div class="notice notice-success is-dismissible inline" style="margin:0 0 16px;">
                    <p><?php \esc_html_e('Clé API enregistrée.', 'g2rd'); ?></p>
                </div>
            <?php endif; ?>

            <div class="g2rd-card <?php echo $is_configured ? 'is-active' : 'is-inactive'; ?>" style="width:95%;">
                <div class="g2rd-card-body">

                    <!-- Statut -->
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
                        <?php if ($is_configured) : ?>
                            <span class="dashicons dashicons-yes-alt" style="color:#00a32a;font-size:22px;width:22px;height:22px;"></span>
                            <strong><?php \esc_html_e('Clé API configurée', 'g2rd'); ?></strong>
                            <code style="background:#f0f0f0;padding:2px 8px;border-radius:3px;font-size:12px;">
                                <?php echo \esc_html($masked); ?>
                            </code>
                        <?php else : ?>
                            <span class="dashicons dashicons-warning" style="color:#d63638;font-size:22px;width:22px;height:22px;"></span>
                            <strong style="color:#d63638;"><?php \esc_html_e('Clé API non configurée', 'g2rd'); ?></strong>
                            <em style="color:#787c82;font-size:13px;">
                                <?php \esc_html_e('(non bloquant — mises à jour automatiques désactivées)', 'g2rd'); ?>
                            </em>
                        <?php endif; ?>
                    </div>

                    <!-- Formulaire indépendant (hors formulaire principal) -->
                    <form method="post" action="<?php echo \esc_url(\admin_url('admin-post.php')); ?>">
                        <?php \wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD); ?>
                        <input type="hidden" name="action" value="g2rd_save_license_key">

                        <label for="<?php echo \esc_attr(self::OPTION_API_KEY); ?>"
                            style="display:block;margin-bottom:6px;font-weight:600;">
                            <?php \esc_html_e('Clé API FluentCart', 'g2rd'); ?>
                        </label>

                        <div style="display:flex;gap:8px;align-items:center;">
                            <input
                                type="password"
                                id="<?php echo \esc_attr(self::OPTION_API_KEY); ?>"
                                name="<?php echo \esc_attr(self::OPTION_API_KEY); ?>"
                                value="<?php echo \esc_attr($api_key); ?>"
                                class="regular-text"
                                placeholder="<?php \esc_attr_e('Coller votre clé API ici…', 'g2rd'); ?>"
                                autocomplete="off">
                            <?php \submit_button(\__('Enregistrer', 'g2rd'), 'primary', 'submit', false); ?>
                        </div>

                    </form>

                </div>
            </div>

        </div>
<?php
    }
}
