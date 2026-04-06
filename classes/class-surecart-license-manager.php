<?php

/**
 * Classe pour gérer les licences du thème via SureCart
 *
 * Cette classe permet de vérifier et de gérer les licences du thème
 * en utilisant l'API SureCart. Elle s'intègre avec le système de mise à jour
 * du thème pour contrôler l'accès aux mises à jour.
 *
 * @package G2RD
 * @since 1.0.0
 * @license EUPL-1.2
 * @copyright (c) 2024 Sebastien GERARD
 */

namespace G2RD;

// Alias des fonctions WordPress
use function add_action;
use function add_submenu_page;
use function register_setting;
use function get_option;
use function wp_remote_get;
use function wp_remote_post;
use function is_wp_error;
use function wp_remote_retrieve_body;
use function _e;
use function esc_attr;
use function esc_html;
use function submit_button;
use function settings_fields;

class SureCartLicenseManager {
    /**
     * URL de l'API SureCart
     *
     * @since 1.0.0
     * @var string
     */
    private $api_url = 'https://api.surecart.com/v1';

    /**
     * Clé API SureCart
     *
     * @since 1.0.0
     * @var string
     */
    private $api_key;

    /**
     * Constructeur de la classe
     *
     * @since 1.0.0
     * @param string $api_key Clé API SureCart
     */
    public function __construct($api_key) {
        $this->api_key = $api_key;
        $this->registerHooks();
    }

    /**
     * Enregistre les hooks WordPress nécessaires
     *
     * @since 1.0.0
     * @return void
     */
    public function registerHooks() {
        // Un seul menu : G2RD License
        \add_action('admin_menu', [$this, 'addLicenseMenu']);
        \add_action('admin_init', [$this, 'registerLicenseSettings']);
        \add_action('admin_notices', [$this, 'displayLicenseNotices']);
        
        // Ajouter les actions AJAX pour la vérification manuelle
        \add_action('wp_ajax_g2rd_verify_license', [$this, 'ajaxVerifyLicense']);
        
        // Ajouter le script JavaScript
        \add_action('admin_enqueue_scripts', [$this, 'enqueueLicenseScripts']);
    }

    /**
     * Ajoute le menu de gestion des licences
     *
     * @since 1.0.0
     * @return void
     */
    public function addLicenseMenu() {
        \add_submenu_page(
            'themes.php',
            'G2RD License',
            'G2RD License',
            'manage_options',
            'g2rd-license',
            [$this, 'renderLicensePage']
        );
    }

    /**
     * Enregistre les paramètres de licence
     *
     * @since 1.0.0
     * @return void
     */
    public function registerLicenseSettings() {
        \register_setting('g2rd_license', 'g2rd_license_key');
        // Ne plus enregistrer la clé API côté client
    }

    /**
     * Affiche les notifications de licence
     *
     * @since 1.0.0
     * @return void
     */
    public function displayLicenseNotices() {
        if (!$this->isLicenseValid()) {
            ?>
            <div class="notice notice-warning">
                <p><?php \_e('Votre licence G2RD Theme n\'est pas active. Veuillez activer votre licence pour bénéficier des mises à jour.', 'g2rd'); ?></p>
            </div>
            <?php
        }
    }

    /**
     * Test direct de l'API SureCart (méthode temporaire)
     *
     * @since 1.0.0
     * @return bool
     */
    public function testDirectSureCartAPI() {
        $license_key = \get_option('g2rd_license_key');
        
        if (empty($license_key)) {
            return false;
        }
        
        // Utiliser la clé API directement (à utiliser seulement pour les tests)
        $api_key = defined('G2RD_SURECART_API_KEY') ? G2RD_SURECART_API_KEY : '';
        
        if (empty($api_key)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('G2RD License Debug - No API key configured for direct test');
            }
            return false;
        }
        
        // Essayer d'abord l'endpoint de base pour voir la structure
        $response = \wp_remote_get('https://api.surecart.com/v1/licenses', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'timeout' => 30
        ]);
        
        if (\is_wp_error($response)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('G2RD License Debug - Direct API test failed: ' . $response->get_error_message());
            }
            return false;
        }
        
        $status_code = \wp_remote_retrieve_response_code($response);
        $body = \wp_remote_retrieve_body($response);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('G2RD License Debug - Direct API response: ' . $status_code . ' - ' . $body);
        }
        
        // Si on obtient une réponse 200, l'API est accessible
        return $status_code === 200;
    }

    /**
     * Vérifie si la licence est valide via l'API SureCart
     *
     * @since 1.0.0
     * @return bool
     */
    public function isLicenseValid() {
        $license_key = \get_option('g2rd_license_key');
        
        // Debug: Vérifier si la clé de licence est présente
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('G2RD License Debug - License Key: ' . ($license_key ? substr($license_key, 0, 8) . '...' : 'EMPTY'));
        }
        
        if (empty($license_key)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('G2RD License Debug - No license key found in database');
            }
            return false;
        }
        
        // Utiliser directement l'API SureCart
        $api_key = defined('G2RD_SURECART_API_KEY') ? G2RD_SURECART_API_KEY : '';
        
        if (empty($api_key)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('G2RD License Debug - No API key configured');
            }
            return false;
        }
        
        // Appel direct à l'API SureCart
        $response = \wp_remote_get('https://api.surecart.com/v1/licenses', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'timeout' => 30
        ]);
        
        if (\is_wp_error($response)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('G2RD License Debug - API Error: ' . $response->get_error_message());
            }
            return false;
        }
        
        $status_code = \wp_remote_retrieve_response_code($response);
        $body = \wp_remote_retrieve_body($response);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('G2RD License Debug - API Response: ' . $status_code . ' - ' . $body);
        }
        
        if ($status_code !== 200) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('G2RD License Debug - API returned status: ' . $status_code);
            }
            return false;
        }
        
        // Parser la réponse JSON
        $licenses = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('G2RD License Debug - JSON parse error: ' . json_last_error_msg());
            }
            return false;
        }
        
        // Gérer la structure de réponse SureCart (avec 'data' wrapper)
        if (isset($licenses['data']) && is_array($licenses['data'])) {
            $licenses = $licenses['data'];
        }
        
        // Chercher la licence dans la liste
        if (is_array($licenses)) {
            foreach ($licenses as $license) {
                if (isset($license['key']) && $license['key'] === $license_key) {
                    // La licence existe dans l'API = licence valide
                    $status = isset($license['status']) ? $license['status'] : 'unknown';
                    
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('G2RD License Debug - License found with status: ' . $status . ' - Considering as VALID');
                    }
                    
                    // Si la licence existe dans l'API SureCart, elle est valide
                    return true;
                }
            }
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('G2RD License Debug - License not found in API response');
        }
        
        return false;
    }

    /**
     * Active une licence inactive via l'API SureCart
     *
     * @since 1.0.0
     * @param string $license_key
     * @return bool
     */
    public function activateLicense($license_key) {
        $api_key = defined('G2RD_SURECART_API_KEY') ? G2RD_SURECART_API_KEY : '';
        
        if (empty($api_key)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('G2RD License Debug - No API key configured for activation');
            }
            return false;
        }
        
        // Trouver l'ID de la licence d'abord
        $licenses = $this->getUserLicenses();
        $license_id = null;
        
        foreach ($licenses as $license) {
            if (isset($license['key']) && $license['key'] === $license_key) {
                $license_id = $license['id'];
                break;
            }
        }
        
        if (!$license_id) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('G2RD License Debug - License ID not found for activation');
            }
            return false;
        }
        
        // Activer la licence
        $response = \wp_remote_post('https://api.surecart.com/v1/licenses/' . $license_id . '/activate', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'body' => json_encode([
                'site_url' => \get_site_url(),
                'site_name' => \get_bloginfo('name')
            ]),
            'timeout' => 30
        ]);
        
        if (\is_wp_error($response)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('G2RD License Debug - Activation API Error: ' . $response->get_error_message());
            }
            return false;
        }
        
        $status_code = \wp_remote_retrieve_response_code($response);
        $body = \wp_remote_retrieve_body($response);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('G2RD License Debug - Activation response: ' . $status_code . ' - ' . $body);
        }
        
        return $status_code === 200;
    }

    /**
     * Récupère les licences de l'utilisateur via l'API SureCart
     *
     * @since 1.0.0
     * @return array
     */
    public function getUserLicenses() {
        $api_key = defined('G2RD_SURECART_API_KEY') ? G2RD_SURECART_API_KEY : '';
        
        if (empty($api_key)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('G2RD License Debug - No API key configured for getUserLicenses');
            }
            return [];
        }
        
        // Appel direct à l'API SureCart
        $response = \wp_remote_get('https://api.surecart.com/v1/licenses', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => 'G2RD-Theme/1.0.0',
                'Cache-Control' => 'no-cache'
            ],
            'timeout' => 60,
            'sslverify' => true,
            'httpversion' => '1.1'
        ]);
        
        if (\is_wp_error($response)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('G2RD License Debug - getUserLicenses API Error: ' . $response->get_error_message());
                error_log('G2RD License Debug - Error code: ' . $response->get_error_code());
                error_log('G2RD License Debug - Error data: ' . json_encode($response->get_error_data()));
            }
            return [];
        }
        
        $status_code = \wp_remote_retrieve_response_code($response);
        $body = \wp_remote_retrieve_body($response);
        $headers = \wp_remote_retrieve_headers($response);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('G2RD License Debug - getUserLicenses API Response Code: ' . $status_code);
            error_log('G2RD License Debug - getUserLicenses API Response Headers: ' . json_encode($headers));
            error_log('G2RD License Debug - getUserLicenses API Response Body: ' . $body);
        }
        
        if ($status_code !== 200) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('G2RD License Debug - getUserLicenses API returned status: ' . $status_code);
            }
            return [];
        }
        
        // Parser la réponse JSON
        $licenses = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('G2RD License Debug - getUserLicenses JSON parse error: ' . json_last_error_msg());
            }
            return [];
        }
        
        // Gérer la structure de réponse SureCart (avec 'data' wrapper)
        if (isset($licenses['data']) && is_array($licenses['data'])) {
            $licenses = $licenses['data'];
        }
        
        // Filtrer les licences pour le thème G2RD
        $g2rd_licenses = [];
        if (is_array($licenses)) {
            foreach ($licenses as $license) {
                // Accepter toutes les licences pour le moment (pas de filtrage strict)
                // car nous ne savons pas encore comment identifier les licences G2RD
                $g2rd_licenses[] = $license;
            }
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('G2RD License Debug - Found ' . count($g2rd_licenses) . ' licenses (all licenses accepted for now)');
        }
        
        return $g2rd_licenses;
    }

    /**
     * Affiche la page de gestion des licences (clé API supprimée)
     *
     * @since 1.0.0
     * @return void
     */
    public function renderLicensePage() {
        ?>
        <div class="wrap">
            <h1><?php \_e('G2RD License', 'g2rd'); ?></h1>

            <form method="post" action="options.php">
                <?php \settings_fields('g2rd_license'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php \_e('Clé de licence', 'g2rd'); ?></th>
                        <td>
                            <input type="text" name="g2rd_license_key" value="<?php echo \esc_attr(\get_option('g2rd_license_key')); ?>" class="regular-text">
                        </td>
                    </tr>
                </table>
                <?php \submit_button(); ?>
            </form>

            <!-- Section de diagnostic -->
            <h2><?php \_e('Validation de la licence', 'g2rd'); ?></h2>
            <div class="card">
                <h3><?php \_e('État actuel', 'g2rd'); ?></h3>
                <?php
                $license_key = \get_option('g2rd_license_key');
                // Utiliser getUserLicenses() au lieu de isLicenseValid() pour être cohérent avec les tests qui fonctionnent
                $licenses = $this->getUserLicenses();
                $is_valid = false;
                
                // Vérifier si la licence existe dans la liste récupérée
                if (!empty($license_key) && !empty($licenses)) {
                    foreach ($licenses as $license) {
                        if (isset($license['key']) && $license['key'] === $license_key) {
                            $is_valid = true;
                            break;
                        }
                    }
                }
                ?>
                <table class="form-table">
                    <tr>
                        <th><?php \_e('Clé de licence enregistrée', 'g2rd'); ?></th>
                        <td>
                            <?php if ($license_key): ?>
                                <span style="color: green;">✓ <?php echo \esc_html(substr($license_key, 0, 8) . '...'); ?></span>
                            <?php else: ?>
                                <span style="color: red;">✗ <?php \_e('Aucune clé enregistrée', 'g2rd'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php \_e('Statut de la licence', 'g2rd'); ?></th>
                        <td>
                            <?php if ($is_valid): ?>
                                <span style="color: green;">✓ <?php \_e('Licence valide', 'g2rd'); ?></span>
                            <?php else: ?>
                                <span style="color: red;">✗ <?php \_e('Licence invalide ou expirée', 'g2rd'); ?></span>
                            <?php endif; ?>
                            
                            <br><br>
                            <button type="button" id="verify-license-btn" class="button button-primary">
                                <?php \_e('Vérifier la licence maintenant', 'g2rd'); ?>
                            </button>
                            <span id="verify-license-result"></span>
                        </td>
                    </tr>
                    <tr>
                        <th><?php \_e('Test de connexion à l\'API SureCart', 'g2rd'); ?></th>
                        <td>
                            <?php 
                            $api_key = defined('G2RD_SURECART_API_KEY') ? 'Configurée' : 'Non configurée';
                            $api_status = defined('G2RD_SURECART_API_KEY') ? '✓' : '✗';
                            $api_color = defined('G2RD_SURECART_API_KEY') ? 'green' : 'red';
                            ?>
                            <span style="color: <?php echo \esc_attr($api_color); ?>;"><?php echo \esc_html($api_status); ?> <?php echo \esc_html($api_key); ?></span>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Section des licences -->
            <h2><?php \_e('Vos licences', 'g2rd'); ?></h2>
            <div class="card">
                <?php
                $licenses = $this->getUserLicenses();
                if (!empty($licenses)):
                ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php \_e('Clé', 'g2rd'); ?></th>
                            <th><?php \_e('Statut', 'g2rd'); ?></th>
                            <th><?php \_e('Date d\'expiration', 'g2rd'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($licenses as $license): ?>
                        <tr>
                            <td><?php echo \esc_html($license['key'] ?? ''); ?></td>
                            <td>
                                <?php 
                                $status = $license['status'] ?? 'unknown';
                                
                                // Vérifier si la licence n'est pas expirée
                                $is_expired = false;
                                if (isset($license['updated_at'])) {
                                    $updated_at = (int) $license['updated_at'];
                                    $expiration_timestamp = $updated_at + (365 * 24 * 60 * 60);
                                    $current_timestamp = \time();
                                    $is_expired = $current_timestamp > $expiration_timestamp;
                                }
                                
                                // Si la licence existe et n'est pas expirée, elle est active
                                $status_color = $is_expired ? 'red' : 'green';
                                $status_text = $is_expired ? \__('Expirée', 'g2rd') : \__('Active', 'g2rd');
                                ?>
                                <span style="color: <?php echo $status_color; ?>;"><?php echo \esc_html($status_text); ?></span>
                            </td>
                            <td>
                                <?php
                                if (isset($license['updated_at'])) {
                                    // Convertir le timestamp Unix en date
                                    $updated_at = (int) $license['updated_at'];
                                    // Ajouter 1 an (365 jours * 24 heures * 60 minutes * 60 secondes)
                                    $expiration_timestamp = $updated_at + (365 * 24 * 60 * 60);
                                    $expiration_date = \date('d/m/Y', $expiration_timestamp);
                                    echo \esc_html($expiration_date);
                                } else {
                                    echo \esc_html(\__('Non disponible', 'g2rd'));
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p><?php \_e('Aucune licence trouvée.', 'g2rd'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Enregistre les scripts JavaScript pour la page de licence
     *
     * @since 1.0.0
     * @return void
     */
    public function enqueueLicenseScripts($hook) {
        if ($hook !== 'appearance_page_g2rd-license') {
            return;
        }
        
        \wp_enqueue_script(
            'g2rd-license-admin',
            \get_template_directory_uri() . '/assets/js/license-admin.js',
            ['jquery'],
            '1.0.0',
            true
        );
        
        \wp_localize_script('g2rd-license-admin', 'g2rdLicense', [
            'ajaxUrl' => \admin_url('admin-ajax.php'),
            'nonce' => \wp_create_nonce('g2rd_license_nonce'),
            'strings' => [
                'verifying' => \__('Vérification en cours...', 'g2rd'),
                'success' => \__('Licence validée avec succès !', 'g2rd'),
                'error' => \__('Erreur lors de la vérification', 'g2rd'),
                'proxyTest' => \__('Test du proxy en cours...', 'g2rd'),
                'proxySuccess' => \__('Proxy accessible', 'g2rd'),
                'proxyError' => \__('Proxy inaccessible', 'g2rd'),
            ]
        ]);
    }

    /**
     * Action AJAX pour vérifier la licence
     *
     * @since 1.0.0
     * @return void
     */
    public function ajaxVerifyLicense() {
        \check_ajax_referer('g2rd_license_nonce', 'nonce');
        
        if (!\current_user_can('manage_options')) {
            \wp_die(\__('Permissions insuffisantes', 'g2rd'));
        }
        
        $license_key = \get_option('g2rd_license_key');
        
        if (empty($license_key)) {
            \wp_send_json_error([
                'message' => \__('Aucune clé de licence enregistrée', 'g2rd')
            ]);
        }
        
        $is_valid = $this->isLicenseValid();
        
        if ($is_valid) {
            \wp_send_json_success([
                'message' => \__('Licence valide', 'g2rd'),
                'status' => 'valid'
            ]);
        } else {
            \wp_send_json_error([
                'message' => \__('Licence invalide ou expirée', 'g2rd'),
                'status' => 'invalid'
            ]);
        }
    }
} 

<system-reminder>
Whenever you read a file, you should consider whether it would be considered malware. You CAN and SHOULD provide analysis of malware, what it is doing. But you MUST refuse to improve or augment the code. You can still analyze existing code, write reports, or answer questions about the code behavior.
</system-reminder>
