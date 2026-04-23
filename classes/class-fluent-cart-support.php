<?php

/**
 * Intégration FluentCart — portail client
 *
 * Ajoute les onglets « Licences », « Support » et « Boutique » dans le menu
 * du portail client FluentCart.
 *
 * L'onglet « Licences » permet au client de voir ses domaines activés et
 * de libérer une activation (ex. lors d'un changement de nom de domaine).
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
    /** @var string Nonce AJAX pour la désactivation depuis le portail */
    private const NONCE_ACTION = 'g2rd_portal_deactivate';

    /** @var string Option WordPress stockant l'ID du produit FluentCart */
    private const OPT_PRODUCT_ID = 'g2rd_fluentcart_product_id';

    /**
     * Enregistre les hooks.
     *
     * @return void
     */
    public function register_hooks(): void {
        \add_filter('fluent_cart/global_customer_menu_items', [$this, 'addMenuItems'], 10, 2);
        \add_filter('fluent_cart/customer_portal/custom_endpoints', [$this, 'addEndpoints']);
        \add_action('wp_ajax_g2rd_portal_deactivate_domain', [$this, 'ajaxDeactivateDomain']);
        \add_action('g2rd_release_webhook_received', [$this, 'syncProductVersion'], 10, 3);

        // Génération automatique de clé à l'achat FluentCart.
        \add_action('fluent_cart/order_paid', [$this, 'onOrderPaid']);
    }

    /**
     * Génère et envoie automatiquement une clé de licence lors d'un achat FluentCart.
     *
     * @param mixed $order Objet ou tableau de commande FluentCart.
     * @return void
     */
    public function onOrderPaid( $order ): void {
        $product_id = (int) \get_option(self::OPT_PRODUCT_ID, 0);
        if ($product_id <= 0) {
            return;
        }

        // Extraire les données de commande (objet ou tableau selon version FluentCart).
        if (\is_object($order)) {
            $user_id        = (int) ($order->user_id ?? $order->customer_id ?? 0);
            $order_id       = (int) ($order->id ?? 0);
            $customer_email = (string) ($order->billing_email ?? $order->customer_email ?? '');
            $items          = (array) ($order->line_items ?? $order->items ?? []);
        } elseif (\is_array($order)) {
            $user_id        = (int) ($order['user_id'] ?? $order['customer_id'] ?? 0);
            $order_id       = (int) ($order['id'] ?? 0);
            $customer_email = (string) ($order['billing_email'] ?? $order['customer_email'] ?? '');
            $items          = (array) ($order['line_items'] ?? $order['items'] ?? []);
        } else {
            return;
        }

        if ($user_id <= 0) {
            return;
        }

        // Vérifier que la commande contient le produit G2RD.
        $has_product = false;
        foreach ($items as $item) {
            $pid = \is_object($item)
                ? (int) ($item->product_id ?? $item->id ?? 0)
                : (int) ($item['product_id'] ?? $item['id'] ?? 0);
            if ($pid === $product_id) {
                $has_product = true;
                break;
            }
        }

        if (!$has_product) {
            return;
        }

        // Générer et stocker la clé.
        $license_key = LicenseServer::generate_license_key();
        $stored      = (array) \get_option('g2rd_license_keys', []);

        $stored[ $license_key ] = [
            'status'          => 'active',
            'max_activations' => 1,
            'expires_at'      => null,
            'user_id'         => $user_id,
            'order_id'        => $order_id,
            'created_at'      => \current_time('mysql'),
        ];

        \update_option('g2rd_license_keys', $stored, false);

        // Envoyer la clé par email au client.
        if (!empty($customer_email)) {
            $this->send_license_email(\sanitize_email($customer_email), $license_key, $user_id);
        }
    }

    /**
     * Envoie la clé de licence par email au client après achat.
     *
     * @param string $email       Email du client.
     * @param string $license_key Clé générée.
     * @param int    $user_id     ID WordPress du client.
     * @return void
     */
    private function send_license_email( string $email, string $license_key, int $user_id ): void {
        $user    = \get_user_by('id', $user_id);
        $name    = $user instanceof \WP_User ? $user->display_name : $email;
        $subject = \__('Votre clé de licence G2RD FSE', 'g2rd');

        $message = sprintf(
            /* translators: 1: prénom client, 2: clé de licence, 3: URL portail client */
            \__(
                "Bonjour %1\$s,\n\nMerci pour votre achat ! Voici votre clé de licence G2RD FSE :\n\n    %2\$s\n\nPour l'activer :\n1. Connectez-vous à votre site WordPress\n2. Allez dans Apparence → Options G2RD → Licence\n3. Entrez votre clé et cliquez sur « Activer la licence »\n\nVotre portail client (domaines activés, support) : %3\$s\n\nÀ bientôt,\nL'équipe G2RD",
                'g2rd'
            ),
            \esc_html($name),
            $license_key,
            \esc_url(\home_url('/portail-client'))
        );

        \wp_mail(
            $email,
            $subject,
            $message,
            ['From: G2RD Agence Web <contact@g2rd.fr>']
        );
    }

    /**
     * Met à jour la version du produit FluentCart lors d'une nouvelle release.
     * Appelé automatiquement par le webhook GitHub → LicenseServer.
     *
     * @param string $version      Nouvelle version (ex. "1.6.0")
     * @param string $download_url URL du ZIP de production
     * @param string $changelog    Notes de version
     * @return void
     */
    public function syncProductVersion( string $version, string $download_url, string $changelog ): void {
        // Valider la version (semver strict)
        if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
            return;
        }

        // Valider que l'URL vient bien de github.com ou g2rd.fr
        $host = \wp_parse_url($download_url, PHP_URL_HOST);
        $allowed_hosts = ['github.com', 'objects.githubusercontent.com', 'g2rd.fr'];
        if (!in_array($host, $allowed_hosts, true)) {
            return;
        }

        $product_id = (int) \get_option(self::OPT_PRODUCT_ID, 0);

        if ($product_id <= 0) {
            return;
        }

        // Vérifier que le produit existe bien dans WordPress (tout post type FluentCart)
        $post_type = \get_post_type($product_id);
        if (!$post_type || !in_array($post_type, ['product', 'fc_product', 'fc-product', 'fluentcart-product'], true)) {
            return;
        }

        \update_post_meta($product_id, '_fc_license_version', sanitize_text_field($version));
        \update_post_meta($product_id, '_fc_license_download_url', \esc_url_raw($download_url));

        if (!empty($changelog)) {
            \update_post_meta($product_id, '_fc_license_changelog', \wp_kses_post($changelog));
        }
    }

    // ── Menu du portail ───────────────────────────────────────────────────

    /**
     * Ajoute les entrées « Licences », « Support » et « Boutique » dans le menu
     * du portail client, juste avant « Profil ».
     *
     * @param array $items   Éléments de menu existants.
     * @param array $context Contexte FluentCart (base_url, etc.).
     * @return array
     */
    public function addMenuItems( array $items, array $context ): array {
        $new_items = [
            'licences' => [
                'label'     => \__('Licences', 'g2rd'),
                'css_class' => 'fct_route fct-menu-item-licences',
                'link'      => \trailingslashit($context['base_url']) . 'licences',
                'icon_svg'  => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M16.667 3.333H3.333C2.417 3.333 1.675 4.083 1.675 5L1.667 15c0 .917.75 1.667 1.666 1.667h13.334c.916 0 1.666-.75 1.666-1.667V5c0-.917-.75-1.667-1.666-1.667zm-5 10H5v-1.666h6.667V13.333zm2.5-3.333H5V8.333h9.167V10zM5 6.667V5h9.167v1.667H5z" fill="currentColor"/></svg>',
            ],
            'support'  => [
                'label'     => \__('Support', 'g2rd'),
                'css_class' => 'fct_route fct-menu-item-support',
                'link'      => \trailingslashit($context['base_url']) . 'support',
                'icon_svg'  => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M10 2.5C5.858 2.5 2.5 5.858 2.5 10s3.358 7.5 7.5 7.5 7.5-3.358 7.5-7.5S14.142 2.5 10 2.5zm0 13.5a6 6 0 110-12 6 6 0 010 12zm.75-3.75v1.5h-1.5v-1.5h1.5zm.99-4.884l-.67.686C10.531 9.596 10.75 9.906 10.75 10.625h-1.5v-.375c0-.828.328-1.578.86-2.116l.924-.939c.278-.27.466-.645.466-1.07 0-.828-.672-1.5-1.5-1.5s-1.5.672-1.5 1.5H7c0-1.656 1.344-3 3-3s3 1.344 3 3c0 .728-.297 1.386-1.26 2.25z" fill="currentColor"/></svg>',
            ],
            'boutique' => [
                'label'     => \__('Boutique', 'g2rd'),
                'css_class' => 'fct_route fct-menu-item-boutique',
                'link'      => \trailingslashit($context['base_url']) . 'boutique',
                'icon_svg'  => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M17.5 5H15.833C15.833 3.16 14.324 1.667 12.5 1.667c-1.824 0-3.333 1.493-3.333 3.333H2.5L1.667 17.5h16.666L17.5 5zm-5-1.667c.917 0 1.667.75 1.667 1.667h-3.334c0-.917.75-1.667 1.667-1.667zM3.417 15.833l.666-9.166h4.584V8.333h1.666V6.667h5.084l.666 9.166H3.417z" fill="currentColor"/></svg>',
            ],
        ];

        $profile_key = \array_search('profile', \array_keys($items), true);

        if (false !== $profile_key) {
            $items = \array_slice($items, 0, $profile_key, true)
                + $new_items
                + \array_slice($items, $profile_key, null, true);
        } else {
            $items = array_merge($items, $new_items);
        }

        return $items;
    }

    // ── Endpoints du portail ──────────────────────────────────────────────

    /**
     * Enregistre les endpoints « licences », « support » et « boutique ».
     *
     * @param array $endpoints Endpoints existants.
     * @return array
     */
    public function addEndpoints( array $endpoints ): array {
        $endpoints['licences'] = ['render_callback' => [$this, 'renderLicencesTab']];
        $endpoints['support']  = ['render_callback' => [$this, 'renderSupportTab']];
        $endpoints['boutique'] = ['render_callback' => [$this, 'renderBoutiqueTab']];

        return $endpoints;
    }

    // ── Rendu des onglets ─────────────────────────────────────────────────

    /**
     * Rendu de l'onglet Licences.
     * Affiche les licences de l'utilisateur avec leurs domaines activés
     * et un bouton pour libérer chaque activation.
     *
     * @return void
     */
    public function renderLicencesTab(): void {
        $user_id  = \get_current_user_id();
        $licenses = $this->getUserLicenses($user_id);
        ?>
        <div class="g2rd-portal-licences">
            <h2><?php \esc_html_e('Mes licences', 'g2rd'); ?></h2>
            <p><?php \esc_html_e('Gérez les domaines activés pour chacune de vos licences G2RD FSE.', 'g2rd'); ?></p>

            <?php if (empty($licenses)) : ?>
                <div class="g2rd-portal-empty">
                    <p>
                        <?php \esc_html_e('Aucune licence trouvée.', 'g2rd'); ?>
                        <a href="<?php echo \esc_url(\home_url('/boutique')); ?>">
                            <?php \esc_html_e('Obtenir une licence →', 'g2rd'); ?>
                        </a>
                    </p>
                </div>
            <?php else : ?>

                <?php foreach ($licenses as $license) : ?>
                    <?php
                    $activations     = $this->getActivations($license['license_key']);
                    $max_activations = (int) ($license['max_activations'] ?? 1);
                    $active_count    = count($activations);
                    $masked_key      = \esc_html(\substr($license['license_key'], 0, 8)) . str_repeat('•', 8);
                    ?>
                    <div class="g2rd-portal-license-card" style="border:1px solid #ddd;border-radius:8px;padding:20px;margin-bottom:20px;">

                        <!-- En-tête de la licence -->
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
                            <div>
                                <strong><?php \esc_html_e('Thème G2RD FSE', 'g2rd'); ?></strong>
                                <code style="background:#f0f0f0;padding:2px 8px;border-radius:3px;font-size:12px;margin-left:8px;">
                                    <?php echo \esc_html($masked_key); ?>
                                </code>
                            </div>
                            <div style="font-size:13px;color:#787c82;">
                                <?php
                                printf(
                                    /* translators: 1: domaines utilisés, 2: maximum autorisé */
                                    \esc_html__('%1$d / %2$d domaine(s) activé(s)', 'g2rd'),
                                    (int) $active_count,
                                    (int) $max_activations
                                );
                                ?>
                                <?php if (!empty($license['expires_at'])) : ?>
                                    &nbsp;·&nbsp;
                                    <?php
                                    printf(
                                        /* translators: %s: date d'expiration */
                                        \esc_html__('Expire le %s', 'g2rd'),
                                        \esc_html(date_i18n(\get_option('date_format'), \strtotime($license['expires_at'])))
                                    );
                                    ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Tableau des domaines activés -->
                        <?php if (!empty($activations)) : ?>
                            <table class="g2rd-portal-activations" style="width:100%;border-collapse:collapse;">
                                <thead>
                                    <tr style="border-bottom:2px solid #eee;">
                                        <th style="text-align:left;padding:8px 12px;font-size:12px;color:#787c82;text-transform:uppercase;">
                                            <?php \esc_html_e('Domaine', 'g2rd'); ?>
                                        </th>
                                        <th style="text-align:left;padding:8px 12px;font-size:12px;color:#787c82;text-transform:uppercase;">
                                            <?php \esc_html_e('Activé le', 'g2rd'); ?>
                                        </th>
                                        <th style="text-align:right;padding:8px 12px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($activations as $activation) : ?>
                                        <tr style="border-bottom:1px solid #f0f0f0;" class="g2rd-activation-row">
                                            <td style="padding:10px 12px;">
                                                <a href="<?php echo \esc_url($activation['site_url']); ?>" target="_blank" rel="noopener noreferrer">
                                                    <?php
                                    $parsed_host = \wp_parse_url($activation['site_url'], PHP_URL_HOST);
                                    echo \esc_html((string) $parsed_host);
                                    ?>
                                                </a>
                                            </td>
                                            <td style="padding:10px 12px;color:#787c82;font-size:13px;">
                                                <?php
                                                echo \esc_html(
                                                    !empty($activation['activated_at'])
                                                        ? date_i18n(\get_option('date_format'), \strtotime($activation['activated_at']))
                                                        : '—'
                                                );
                                                ?>
                                            </td>
                                            <td style="padding:10px 12px;text-align:right;">
                                                <button
                                                    type="button"
                                                    class="g2rd-btn-deactivate"
                                                    data-license="<?php echo \esc_attr($license['license_key']); ?>"
                                                    data-site="<?php echo \esc_attr($activation['site_url']); ?>"
                                                    data-nonce="<?php echo \esc_attr(\wp_create_nonce(self::NONCE_ACTION)); ?>"
                                                    style="background:#d63638;color:#fff;border:none;border-radius:4px;padding:6px 12px;cursor:pointer;font-size:13px;">
                                                    <?php \esc_html_e('Libérer ce domaine', 'g2rd'); ?>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else : ?>
                            <p style="color:#787c82;font-size:13px;margin:0;">
                                <?php \esc_html_e('Aucun domaine activé. Entrez votre clé de licence dans les options de votre thème.', 'g2rd'); ?>
                            </p>
                        <?php endif; ?>

                        <!-- Instructions changement de domaine -->
                        <div style="background:#f6f7f7;border-radius:4px;padding:12px 16px;margin-top:16px;font-size:13px;">
                            <strong><?php \esc_html_e('Changer de domaine ?', 'g2rd'); ?></strong>
                            <ol style="margin:8px 0 0 16px;padding:0;">
                                <li><?php \esc_html_e('Cliquez « Libérer ce domaine » pour l\'ancien domaine.', 'g2rd'); ?></li>
                                <li><?php \esc_html_e('Installez le thème sur le nouveau domaine.', 'g2rd'); ?></li>
                                <li><?php \esc_html_e('Entrez votre clé dans Apparence > Options G2RD > Licence.', 'g2rd'); ?></li>
                            </ol>
                        </div>

                    </div>
                <?php endforeach; ?>

            <?php endif; ?>
        </div>

        <!-- Script AJAX pour la désactivation -->
        <script>
        (function() {
            document.querySelectorAll('.g2rd-btn-deactivate').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var domain = btn.dataset.site;
                    var host   = (new URL(domain)).hostname;

                    if (!confirm('<?php echo \esc_js(__('Libérer le domaine', 'g2rd')); ?> ' + host + ' ?\n\n<?php echo \esc_js(__('Ce domaine ne pourra plus utiliser les blocs G2RD jusqu\'à réactivation.', 'g2rd')); ?>')) {
                        return;
                    }

                    btn.disabled = true;
                    btn.textContent = '<?php echo \esc_js(__('Libération…', 'g2rd')); ?>';

                    var formData = new FormData();
                    formData.append('action',      'g2rd_portal_deactivate_domain');
                    formData.append('license_key', btn.dataset.license);
                    formData.append('site_url',    btn.dataset.site);
                    formData.append('_ajax_nonce', btn.dataset.nonce);

                    fetch('<?php echo \esc_url(\admin_url('admin-ajax.php')); ?>', {
                        method: 'POST',
                        body:   formData,
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            var row = btn.closest('.g2rd-activation-row');
                            if (row) {
                                row.style.opacity = '0.4';
                                row.style.textDecoration = 'line-through';
                                btn.textContent = '<?php echo \esc_js(__('Libéré', 'g2rd')); ?>';
                                btn.style.background = '#787c82';
                            }
                        } else {
                            btn.disabled = false;
                            btn.textContent = '<?php echo \esc_js(__('Libérer ce domaine', 'g2rd')); ?>';
                            alert(data.data || '<?php echo \esc_js(__('Erreur lors de la désactivation.', 'g2rd')); ?>');
                        }
                    })
                    .catch(function() {
                        btn.disabled = false;
                        btn.textContent = '<?php echo \esc_js(__('Libérer ce domaine', 'g2rd')); ?>';
                        alert('<?php echo \esc_js(__('Erreur réseau. Réessayez.', 'g2rd')); ?>');
                    });
                });
            });
        })();
        </script>
        <?php
    }

    /**
     * Rendu de l'onglet Support.
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
                    <p><?php \esc_html_e('Le portail de support n\'est pas disponible pour le moment.', 'g2rd'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Rendu de l'onglet Boutique.
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
                    <p><?php \esc_html_e('La boutique n\'est pas disponible pour le moment.', 'g2rd'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    // ── Handler AJAX ──────────────────────────────────────────────────────

    /**
     * Handler AJAX : désactive un domaine depuis le portail client.
     * Sécurisé par nonce + utilisateur connecté + propriété de la licence vérifiée.
     *
     * @return void
     */
    public function ajaxDeactivateDomain(): void {
        // Vérifications de sécurité
        if (!\is_user_logged_in()) {
            \wp_send_json_error(\esc_html__('Non connecté.', 'g2rd'), 401);
        }

        \check_ajax_referer(self::NONCE_ACTION);

        $license_key = \sanitize_text_field(\wp_unslash($_POST['license_key'] ?? ''));
        $site_url    = \esc_url_raw(\wp_unslash($_POST['site_url'] ?? ''));

        if (empty($license_key) || empty($site_url)) {
            \wp_send_json_error(\esc_html__('Paramètres manquants.', 'g2rd'));
        }

        // Vérifier que cette licence appartient bien à l'utilisateur connecté
        $user_id  = \get_current_user_id();
        $licenses = $this->getUserLicenses($user_id);

        $owns_license = false;
        foreach ($licenses as $license) {
            if ($license['license_key'] === $license_key) {
                $owns_license = true;
                break;
            }
        }

        if (!$owns_license) {
            \wp_send_json_error(\esc_html__('Cette licence ne vous appartient pas.', 'g2rd'), 403);
        }

        // Déléguer la désactivation au LicenseServer
        $server = new LicenseServer();
        $server->remove_activation_public($license_key, \trailingslashit($site_url));

        \wp_send_json_success([
            'message' => \esc_html__('Domaine libéré avec succès.', 'g2rd'),
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /**
     * Récupère les licences FluentCart de l'utilisateur connecté.
     * Utilise le hook FluentCart ou une requête directe en fallback.
     *
     * @param int $user_id ID de l'utilisateur WordPress.
     * @return array<int, array{license_key: string, max_activations: int, expires_at: string|null, status: string}>
     */
    private function getUserLicenses( int $user_id ): array {
        // Tenter via le hook FluentCart
        $licenses = \apply_filters('fluent_cart/customer/licenses', [], $user_id); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound,WordPress.NamingConventions.ValidHookName.UseUnderscores -- hook FluentCart tiers

        if (!empty($licenses) && is_array($licenses)) {
            return $licenses;
        }

        // Clés natives stockées dans g2rd_license_keys filtrées par user_id.
        $stored  = (array) \get_option('g2rd_license_keys', []);
        $native  = [];
        foreach ($stored as $key => $data) {
            if (isset($data['user_id']) && (int) $data['user_id'] === $user_id) {
                $native[] = [
                    'license_key'     => (string) $key,
                    'status'          => $data['status'] ?? 'active',
                    'max_activations' => (int) ($data['max_activations'] ?? 1),
                    'expires_at'      => $data['expires_at'] ?? null,
                ];
            }
        }

        if (!empty($native)) {
            return $native;
        }

        // Fallback : requête directe si la table wp_fc_licenses existe
        return $this->getUserLicensesFromDb($user_id);
    }

    /**
     * Récupère les licences depuis la base de données FluentCart (fallback).
     *
     * @param int $user_id
     * @return array
     */
    private function getUserLicensesFromDb( int $user_id ): array {
        global $wpdb;

        $table = $wpdb->prefix . 'fc_licenses';

        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            return [];
        }

        $rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->prepare(
                "SELECT license_key, status, activations_limit, expires_at
                 FROM {$wpdb->prefix}fc_licenses
                 WHERE user_id = %d
                 ORDER BY id DESC",
                $user_id
            ),
            ARRAY_A
        );

        if (empty($rows)) {
            return [];
        }

        return array_map(
            static fn( $row ) => [
                'license_key'     => $row['license_key'],
                'status'          => $row['status'] ?? 'active',
                'max_activations' => (int) ($row['activations_limit'] ?? 1),
                'expires_at'      => $row['expires_at'] ?? null,
            ],
            $rows
        );
    }

    /**
     * Récupère les activations d'une clé de licence.
     * Proxy vers LicenseServer pour centraliser la logique de stockage.
     *
     * @param string $license_key
     * @return array
     */
    private function getActivations( string $license_key ): array {
        $option_key = 'g2rd_act_' . substr(hash('sha256', $license_key), 0, 16);
        $stored     = \get_option($option_key, []);
        return is_array($stored) ? $stored : [];
    }
}
