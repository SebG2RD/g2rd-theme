<?php
/**
 * Gestionnaire de licences G2RD — côté client
 *
 * Gère l'activation, la désactivation et la validation périodique de la licence
 * du thème G2RD FSE via les endpoints REST de g2rd.fr (LicenseServer).
 *
 * Flux :
 *  1. L'utilisateur colle sa clé de licence dans l'onglet Licence de la page d'options React.
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

    // ── REST namespace ────────────────────────────────────────────────────

    /** @var string Namespace REST des routes de licence */
    private const REST_NAMESPACE = 'g2rd/v1';

    // ── Hooks ─────────────────────────────────────────────────────────────

    /**
     * Enregistre les hooks WordPress.
     *
     * @return void
     */
    public function register_hooks(): void {
        \add_action('rest_api_init', [$this, 'register_rest_routes']);

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
     * Retourne les données de licence brutes (pour les helpers internes).
     *
     * @return array<string, mixed>
     */
    public function get_license_data(): array {
        $json = \get_option(self::OPT_LICENSE_DATA, '');
        if (empty($json)) {
            return [];
        }

        return json_decode($json, true) ?: [];
    }

    /**
     * Retourne les données de licence formatées pour l'affichage admin React.
     * Utilisé par ThemeOptions::get_initial_data() et les callbacks REST.
     *
     * @return array{status: string, masked_key: string, data: array<string, mixed>, domain: string}
     */
    public static function get_display_data(): array {
        $status      = (string) \get_option(self::OPT_LICENSE_STATUS, 'inactive');
        $license_key = (string) \get_option(self::OPT_LICENSE_KEY, '');
        $domain      = (string) \get_option(self::OPT_LICENSE_DOMAIN, '');
        $json        = \get_option(self::OPT_LICENSE_DATA, '');
        $data        = !empty($json) ? (json_decode($json, true) ?: []) : [];

        $masked_key = !empty($license_key)
            ? \substr($license_key, 0, 8) . str_repeat('•', 8)
            : '';

        return [
            'status'     => $status,
            'masked_key' => $masked_key,
            'data'       => $data,
            'domain'     => $domain,
        ];
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
     * @param string $license_key Clé de licence saisie par l'utilisateur.
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
            \delete_transient(self::TRANSIENT_VALID);
            \update_option(self::OPT_LICENSE_STATUS, 'inactive', false);
        }
    }

    // ── Routes REST ───────────────────────────────────────────────────────

    /**
     * Enregistre les routes REST pour la gestion de la licence depuis l'app React.
     *
     * @return void
     */
    public function register_rest_routes(): void {
        \register_rest_route(
            self::REST_NAMESPACE,
            '/license',
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'rest_get_license'],
                'permission_callback' => static fn() => \current_user_can('manage_options'),
            ]
        );

        \register_rest_route(
            self::REST_NAMESPACE,
            '/license/activate',
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'rest_activate'],
                'permission_callback' => static fn() => \current_user_can('manage_options'),
                'args'                => [
                    'license_key' => [
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ]
        );

        \register_rest_route(
            self::REST_NAMESPACE,
            '/license/deactivate',
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'rest_deactivate'],
                'permission_callback' => static fn() => \current_user_can('manage_options'),
            ]
        );
    }

    /**
     * Retourne le statut et les données de la licence (REST GET /license).
     *
     * @return \WP_REST_Response
     */
    public function rest_get_license(): \WP_REST_Response {
        return new \WP_REST_Response(self::get_display_data(), 200);
    }

    /**
     * Active la licence via l'API (REST POST /license/activate).
     *
     * @param \WP_REST_Request $request Requête REST entrante.
     * @return \WP_REST_Response
     */
    public function rest_activate( \WP_REST_Request $request ): \WP_REST_Response {
        $license_key = (string) $request->get_param('license_key');
        $result      = $this->activate($license_key);

        return new \WP_REST_Response(
            [
                'success' => $result['success'],
                'message' => $result['message'],
                'license' => $result['success'] ? self::get_display_data() : null,
            ],
            $result['success'] ? 200 : 400
        );
    }

    /**
     * Désactive la licence (REST POST /license/deactivate).
     *
     * @return \WP_REST_Response
     */
    public function rest_deactivate(): \WP_REST_Response {
        $result = $this->deactivate();

        return new \WP_REST_Response(
            [
                'success' => $result['success'],
                'message' => $result['message'],
            ],
            200
        );
    }

    // ── Persistance ───────────────────────────────────────────────────────

    /**
     * Stocke les données de la licence activée.
     *
     * @param string               $license_key   Clé de licence.
     * @param string               $status        Statut de la licence.
     * @param array<string, mixed> $response_body Corps de la réponse de l'API.
     * @return void
     */
    private function store_license( string $license_key, string $status, array $response_body ): void {
        $license_data = $response_body['license'] ?? [];
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

    // ── Helpers ───────────────────────────────────────────────────────────

    /**
     * Retourne un tableau d'erreur formaté.
     *
     * @param string $message Message d'erreur.
     * @return array{success: bool, message: string}
     */
    private function make_error( string $message ): array {
        return ['success' => false, 'message' => $message];
    }
}
