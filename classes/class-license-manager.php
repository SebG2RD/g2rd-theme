<?php
/**
 * Gestionnaire de licences G2RD — côté client
 *
 * Gère l'activation, la désactivation et la validation périodique de la licence
 * du thème G2RD FSE via les endpoints REST de g2rd.fr (LicenseServer).
 *
 * Flux :
 *  1. L'utilisateur colle sa clé de licence dans l'admin WP.
 *  2. Le thème appelle g2rd.fr/wp-json/g2rd-license/v1/activate avec la clé + domain.
 *  3. g2rd.fr vérifie la clé dans FluentCart et enregistre l'activation.
 *  4. Statut stocké en option WP + transient 24h pour éviter les appels répétés.
 *  5. Un cron journalier revalide la licence (gestion des révocations/expirations).
 *  6. Si licence active → blocs G2RD disponibles (via BlockEditorAutoload).
 *
 * @package    G2RD
 * @since      1.3.0
 * @license    EUPL-1.2
 * @copyright  (c) 2025 Sebastien GERARD
 */

namespace G2RD;

/**
 * Classe LicenseManager
 */
class LicenseManager {
    // ── Options WordPress (autoload:false) ────────────────────────────────

    /** @var string Clé de licence (stockée en clair, jamais exposée côté front) */
    private const OPT_LICENSE_KEY    = 'g2rd_license_key';

    /** @var string Statut : 'active' | 'inactive' | 'expired' | 'invalid' */
    private const OPT_LICENSE_STATUS = 'g2rd_license_status';

    /** @var string Données JSON : expires_at, max_activations, activations_left */
    private const OPT_LICENSE_DATA   = 'g2rd_license_data';

    /** @var string Domain activé (pour détection de migration de site) */
    private const OPT_LICENSE_DOMAIN = 'g2rd_license_domain';

    // ── Cache transient ───────────────────────────────────────────────────

    /** @var string Clé transient pour la validité en cache */
    private const TRANSIENT_VALID = 'g2rd_license_valid';

    /** @var int TTL du transient de validité (24h) */
    private const TRANSIENT_TTL = 86400;

    // ── Endpoints API (LicenseServer sur g2rd.fr) ─────────────────────────

    /** @var string URL de base de l'API de licences */
    private const API_BASE = 'https://g2rd.fr/wp-json/g2rd-license/v1';

    /** @var int Timeout des appels REST en secondes */
    private const API_TIMEOUT = 15;

    // ── Formulaires admin ─────────────────────────────────────────────────

    private const NONCE_ACTIVATE   = 'g2rd_license_activate';
    private const NONCE_DEACTIVATE = 'g2rd_license_deactivate';
    private const NONCE_FIELD      = 'g2rd_license_nonce';

    // ── Hooks ─────────────────────────────────────────────────────────────

    /**
     * Enregistre les hooks WordPress.
     *
     * @return void
     */
    public function register_hooks(): void {
        \add_action('admin_post_g2rd_license_activate',   [$this, 'handle_activate']);
        \add_action('admin_post_g2rd_license_deactivate', [$this, 'handle_deactivate']);
        \add_action('g2rd_options_before_form',           [$this, 'render_section']);

        // Cron journalier pour revalider la licence (détecte expirations / révocations)
        \add_action('g2rd_license_daily_check', [$this, 'periodic_validate']);

        if (!\wp_next_scheduled('g2rd_license_daily_check')) {
            \wp_schedule_event(\time(), 'daily', 'g2rd_license_daily_check');
        }

        // Si le domain a changé (migration de site), invalider le transient
        \add_action('init', [$this, 'detect_domain_change'], 1);
    }

    // ── API publique (statique) ───────────────────────────────────────────

    /**
     * Indique si la licence est active.
     * Méthode rapide : lit le transient ou l'option (pas d'appel API).
     * Utilisée par BlockEditorAutoload et GitHubUpdater.
     *
     * @return bool
     */
    public static function is_active(): bool {
        $cached = \get_transient(self::TRANSIENT_VALID);
        if ($cached !== false) {
            return (bool) $cached;
        }

        return \get_option(self::OPT_LICENSE_STATUS, 'inactive') === 'active';
    }

    /**
     * Retourne les données de la licence (pour l'affichage admin).
     *
     * @return array{status: string, expires_at: string|null, max_activations: int, activations_left: int}
     */
    public function get_license_data(): array {
        $json = \get_option(self::OPT_LICENSE_DATA, '');
        if (empty($json)) {
            return [];
        }

        return json_decode($json, true) ?: [];
    }

    /**
     * Alias pour GitHubUpdater (compatibilité).
     *
     * @return bool
     */
    public function isLicenseValid(): bool {
        return self::is_active();
    }

    // ── Activation / Désactivation / Validation ───────────────────────────

    /**
     * Active la licence sur ce domaine via l'API LicenseServer.
     *
     * @param string $license_key Clé de licence saisie par l'utilisateur
     * @return array{success: bool, message: string}
     */
    public function activate( string $license_key ): array {
        if (empty($license_key)) {
            return $this->make_error(__('La clé de licence ne peut pas être vide.', 'g2rd'));
        }

        $response = \wp_remote_post(
            self::API_BASE . '/activate',
            [
                'timeout'     => self::API_TIMEOUT,
                'redirection' => 3,
                'headers'     => [
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ],
                'body'        => \wp_json_encode([
                    'license_key' => $license_key,
                    'site_url'    => \home_url(),
                ]),
            ]
        );

        if (\is_wp_error($response)) {
            return $this->make_error(
                sprintf(
                    /* translators: %s: message d'erreur réseau */
                    __('Impossible de contacter le serveur de licences : %s', 'g2rd'),
                    $response->get_error_message()
                )
            );
        }

        $code = (int) \wp_remote_retrieve_response_code($response);
        $body = json_decode(\wp_remote_retrieve_body($response), true);

        if ($code !== 200 || empty($body['success'])) {
            return $this->make_error($body['message'] ?? __('Activation échouée. Vérifiez votre clé de licence.', 'g2rd'));
        }

        // Persister la licence
        $this->store_license($license_key, 'active', $body);

        return [
            'success' => true,
            'message' => __('Licence activée. Les blocs G2RD sont maintenant disponibles.', 'g2rd'),
        ];
    }

    /**
     * Désactive la licence sur ce domaine (libère une activation).
     *
     * @return array{success: bool, message: string}
     */
    public function deactivate(): array {
        $license_key = \get_option(self::OPT_LICENSE_KEY, '');

        if (!empty($license_key)) {
            // Notifier le serveur de licences (best-effort — on désactive localement même en cas d'erreur)
            \wp_remote_post(
                self::API_BASE . '/deactivate',
                [
                    'timeout' => self::API_TIMEOUT,
                    'headers' => ['Content-Type' => 'application/json'],
                    'body'    => \wp_json_encode([
                        'license_key' => $license_key,
                        'site_url'    => \home_url(),
                    ]),
                ]
            );
        }

        $this->clear_license();

        return [
            'success' => true,
            'message' => __('Licence désactivée. Les blocs G2RD ne sont plus disponibles.', 'g2rd'),
        ];
    }

    /**
     * Revalide la licence auprès du serveur (appelée par le cron journalier).
     * En cas d'erreur réseau, le statut actuel est conservé (pas d'invalidation abusive).
     *
     * @return void
     */
    public function periodic_validate(): void {
        $license_key = \get_option(self::OPT_LICENSE_KEY, '');

        if (empty($license_key)) {
            \delete_transient(self::TRANSIENT_VALID);
            return;
        }

        $response = \wp_remote_post(
            self::API_BASE . '/check',
            [
                'timeout' => self::API_TIMEOUT,
                'headers' => ['Content-Type' => 'application/json'],
                'body'    => \wp_json_encode([
                    'license_key' => $license_key,
                    'site_url'    => \home_url(),
                ]),
            ]
        );

        if (\is_wp_error($response)) {
            // Erreur réseau : conserver le statut actuel, ne pas invalider
            // (évite les faux positifs sur hébergements avec restrictions sortantes)
            \set_transient(self::TRANSIENT_VALID, self::is_active(), self::TRANSIENT_TTL);
            return;
        }

        $code     = (int) \wp_remote_retrieve_response_code($response);
        $body     = json_decode(\wp_remote_retrieve_body($response), true);
        $is_valid = ($code === 200 && !empty($body['success']));
        $status   = $is_valid ? 'active' : ($body['license']['status'] ?? 'invalid');

        \update_option(self::OPT_LICENSE_STATUS, $status, false);
        \set_transient(self::TRANSIENT_VALID, $is_valid, self::TRANSIENT_TTL);

        if (!empty($body['license'])) {
            \update_option(self::OPT_LICENSE_DATA, \wp_json_encode($body['license']), false);
        }
    }

    /**
     * Détecte un changement de domain (migration de site) et invalide le transient.
     * Le prochain cron forcera une revalidation avec le nouveau domain.
     *
     * @return void
     */
    public function detect_domain_change(): void {
        $stored_domain = \get_option(self::OPT_LICENSE_DOMAIN, '');
        $current       = \home_url();

        if (!empty($stored_domain) && $stored_domain !== $current) {
            // Domain changé — invalider le cache, le cron revalidera
            \delete_transient(self::TRANSIENT_VALID);
            \update_option(self::OPT_LICENSE_STATUS, 'inactive', false);
        }
    }

    // ── Handlers de formulaires ───────────────────────────────────────────

    /**
     * Traite la soumission du formulaire d'activation.
     *
     * @return void
     */
    public function handle_activate(): void {
        if (!\current_user_can('manage_options')) {
            \wp_die(\esc_html__('Accès refusé.', 'g2rd'), 403);
        }

        \check_admin_referer(self::NONCE_ACTIVATE, self::NONCE_FIELD);

        $license_key = \sanitize_text_field(\wp_unslash($_POST['g2rd_license_key'] ?? ''));
        $result      = $this->activate($license_key);

        \wp_safe_redirect(
            \add_query_arg(
                [
                    'page'    => 'g2rd-options',
                    'license' => $result['success'] ? 'activated' : 'error',
                ],
                \admin_url('themes.php')
            )
        );
        exit;
    }

    /**
     * Traite la soumission du formulaire de désactivation.
     *
     * @return void
     */
    public function handle_deactivate(): void {
        if (!\current_user_can('manage_options')) {
            \wp_die(\esc_html__('Accès refusé.', 'g2rd'), 403);
        }

        \check_admin_referer(self::NONCE_DEACTIVATE, self::NONCE_FIELD);

        $this->deactivate();

        \wp_safe_redirect(
            \add_query_arg(
                [
                    'page'    => 'g2rd-options',
                    'license' => 'deactivated',
                ],
                \admin_url('themes.php')
            )
        );
        exit;
    }

    // ── Interface admin ───────────────────────────────────────────────────

    /**
     * Affiche la section licence dans la page d'options du thème.
     *
     * @return void
     */
    public function render_section(): void {
        $status       = \get_option(self::OPT_LICENSE_STATUS, 'inactive');
        $license_key  = \get_option(self::OPT_LICENSE_KEY, '');
        $license_data = $this->get_license_data();
        $is_active    = $status === 'active';
        $domain       = \get_option(self::OPT_LICENSE_DOMAIN, '');

        $masked_key = $is_active && !empty($license_key)
            ? \esc_html(\substr($license_key, 0, 8)) . str_repeat('•', 8)
            : '';

        $status_labels = [
            'active'   => ['label' => __('Active', 'g2rd'),   'color' => '#00a32a', 'icon' => 'dashicons-yes-alt'],
            'expired'  => ['label' => __('Expirée', 'g2rd'),  'color' => '#d63638', 'icon' => 'dashicons-clock'],
            'invalid'  => ['label' => __('Invalide', 'g2rd'), 'color' => '#d63638', 'icon' => 'dashicons-dismiss'],
            'inactive' => ['label' => __('Inactive', 'g2rd'), 'color' => '#787c82', 'icon' => 'dashicons-warning'],
        ];
        $badge = $status_labels[$status] ?? $status_labels['inactive'];
?>
        <div class="g2rd-section g2rd-section--license">

            <h2 class="g2rd-section-title">
                <span class="dashicons dashicons-admin-network"></span>
                <?php \esc_html_e('Licence G2RD FSE', 'g2rd'); ?>
            </h2>

            <p class="g2rd-section-desc">
                <?php \esc_html_e('Activez votre licence pour débloquer les blocs Gutenberg personnalisés G2RD.', 'g2rd'); ?>
                <a href="https://g2rd.fr/boutique" target="_blank" rel="noopener noreferrer">
                    <?php \esc_html_e('Obtenir une licence →', 'g2rd'); ?>
                </a>
            </p>

            <?php $this->render_notice(); ?>

            <div class="g2rd-card <?php echo $is_active ? 'is-active' : 'is-inactive'; ?>" style="width:95%;">
                <div class="g2rd-card-body">

                    <!-- Badge de statut -->
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
                        <span class="dashicons <?php echo \esc_attr($badge['icon']); ?>"
                                style="color:<?php echo \esc_attr($badge['color']); ?>;font-size:22px;width:22px;height:22px;"></span>
                        <strong style="color:<?php echo \esc_attr($badge['color']); ?>;">
                            <?php echo \esc_html($badge['label']); ?>
                        </strong>

                        <?php if ($is_active && !empty($masked_key)) : ?>
                            <code style="background:#f0f0f0;padding:2px 8px;border-radius:3px;font-size:12px;">
                                <?php echo \esc_html($masked_key); ?>
                            </code>
                        <?php endif; ?>
                    </div>

                    <?php if ($is_active) : ?>

                        <!-- Détails de la licence active -->
                        <table class="widefat" style="margin-bottom:16px;">
                            <tbody>
                                <?php if (!empty($license_data['expires_at'])) : ?>
                                <tr>
                                    <td style="font-weight:600;width:180px;"><?php \esc_html_e('Expiration', 'g2rd'); ?></td>
                                    <td><?php echo \esc_html(date_i18n(\get_option('date_format'), strtotime($license_data['expires_at']))); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (isset($license_data['activations_left'])) : ?>
                                <tr>
                                    <td style="font-weight:600;"><?php \esc_html_e('Activations restantes', 'g2rd'); ?></td>
                                    <td><?php echo \esc_html((string) $license_data['activations_left']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (!empty($domain)) : ?>
                                <tr>
                                    <td style="font-weight:600;"><?php \esc_html_e('Domaine activé', 'g2rd'); ?></td>
                                    <td><?php echo \esc_html($domain); ?></td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>

                        <!-- Formulaire de désactivation -->
                        <form method="post" action="<?php echo \esc_url(\admin_url('admin-post.php')); ?>">
                            <?php \wp_nonce_field(self::NONCE_DEACTIVATE, self::NONCE_FIELD); ?>
                            <input type="hidden" name="action" value="g2rd_license_deactivate">
                            <button type="submit" class="button button-secondary"
                                    onclick="return confirm('<?php \esc_attr_e('Désactiver la licence sur ce domaine ?', 'g2rd'); ?>');">
                                <span class="dashicons dashicons-no" style="vertical-align:middle;margin-top:-2px;"></span>
                                <?php \esc_html_e('Désactiver la licence', 'g2rd'); ?>
                            </button>
                            <span style="color:#787c82;font-size:12px;margin-left:8px;">
                                <?php \esc_html_e('Cela libérera une activation que vous pourrez utiliser sur un autre site.', 'g2rd'); ?>
                            </span>
                        </form>

                    <?php else : ?>

                        <!-- Formulaire d'activation -->
                        <form method="post" action="<?php echo \esc_url(\admin_url('admin-post.php')); ?>">
                            <?php \wp_nonce_field(self::NONCE_ACTIVATE, self::NONCE_FIELD); ?>
                            <input type="hidden" name="action" value="g2rd_license_activate">

                            <label for="g2rd_license_key" style="display:block;margin-bottom:6px;font-weight:600;">
                                <?php \esc_html_e('Clé de licence', 'g2rd'); ?>
                            </label>

                            <div style="display:flex;gap:8px;align-items:center;">
                                <input
                                    type="text"
                                    id="g2rd_license_key"
                                    name="g2rd_license_key"
                                    class="regular-text"
                                    placeholder="XXXX-XXXX-XXXX-XXXX-XXXX"
                                    autocomplete="off"
                                    spellcheck="false">
                                <?php \submit_button(\__('Activer la licence', 'g2rd'), 'primary', 'submit', false); ?>
                            </div>

                            <p style="color:#787c82;font-size:12px;margin-top:6px;">
                                <?php \esc_html_e('Vous trouverez votre clé dans votre espace client sur g2rd.fr.', 'g2rd'); ?>
                            </p>
                        </form>

                    <?php endif; ?>

                </div>
            </div>

        </div>
<?php
    }

    // ── Persistance ───────────────────────────────────────────────────────

    /**
     * Stocke les données de la licence activée.
     *
     * @param string $license_key
     * @param string $status
     * @param array  $response_body Corps de la réponse de l'API
     * @return void
     */
    private function store_license( string $license_key, string $status, array $response_body ): void {
        $license_data = $response_body['license'] ?? [];
        // Compléter avec les champs activations_left de la réponse
        if (isset($response_body['activations_left'])) {
            $license_data['activations_left'] = (int) $response_body['activations_left'];
        }
        if (isset($response_body['activations_used'])) {
            $license_data['activations_used'] = (int) $response_body['activations_used'];
        }

        \update_option(self::OPT_LICENSE_KEY,    $license_key, false);
        \update_option(self::OPT_LICENSE_STATUS, $status, false);
        \update_option(self::OPT_LICENSE_DATA,   \wp_json_encode($license_data), false);
        \update_option(self::OPT_LICENSE_DOMAIN, \home_url(), false);
        \set_transient(self::TRANSIENT_VALID, $status === 'active', self::TRANSIENT_TTL);
    }

    /**
     * Supprime toutes les données de licence stockées localement.
     *
     * @return void
     */
    private function clear_license(): void {
        \delete_option(self::OPT_LICENSE_KEY);
        \delete_option(self::OPT_LICENSE_STATUS);
        \delete_option(self::OPT_LICENSE_DATA);
        \delete_option(self::OPT_LICENSE_DOMAIN);
        \delete_transient(self::TRANSIENT_VALID);
    }

    // ── Notices admin ─────────────────────────────────────────────────────

    /**
     * Affiche un message de feedback suite à une action sur la licence.
     *
     * @return void
     */
    private function render_notice(): void {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- indicateur de redirection POST→GET
        $action = isset($_GET['license']) ? \sanitize_key(\wp_unslash($_GET['license'])) : '';
        if (empty($action)) {
            return;
        }

        $notices = [
            'activated'   => ['type' => 'success', 'msg' => __('Licence activée avec succès. Rechargez la page pour voir les blocs G2RD.', 'g2rd')],
            'deactivated' => ['type' => 'info',    'msg' => __('Licence désactivée sur ce domaine.', 'g2rd')],
            'error'       => ['type' => 'error',   'msg' => __('Échec de l\'activation. Vérifiez votre clé et réessayez.', 'g2rd')],
        ];

        if (!isset($notices[$action])) {
            return;
        }

        $notice = $notices[$action];
        ?>
        <div class="notice notice-<?php echo \esc_attr($notice['type']); ?> is-dismissible inline" style="margin:0 0 16px;">
            <p><?php echo \esc_html($notice['msg']); ?></p>
        </div>
        <?php
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /**
     * Retourne un tableau d'erreur formaté.
     *
     * @param string $message
     * @return array{success: bool, message: string}
     */
    private function make_error( string $message ): array {
        return ['success' => false, 'message' => $message];
    }
}
