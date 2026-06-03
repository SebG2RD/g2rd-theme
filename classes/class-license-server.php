<?php
/**
 * Serveur de licences G2RD — côté g2rd.fr uniquement
 *
 * Ce fichier enregistre les endpoints REST qui permettent aux sites clients
 * de valider, activer et désactiver leur licence G2RD FSE via FluentCart.
 *
 * Il ne s'active que si FluentCart est installé sur ce WordPress
 * (détecter via la présence des fonctions/classes FluentCart).
 *
 * IMPORTANT : ce fichier s'exécute uniquement sur g2rd.fr (le site vendeur).
 * Sur les sites clients, FluentCart n'est pas installé — ce fichier reste
 * inactif (aucune route n'est enregistrée, aucun impact sur les performances).
 *
 * @package    G2RD
 * @since      1.3.0
 * @license    EUPL-1.2
 * @copyright  (c) 2025 Sebastien GERARD
 */

namespace G2RD;

/**
 * Classe LicenseServer
 *
 * Expose les endpoints REST :
 *   POST /wp-json/g2rd-license/v1/activate
 *   POST /wp-json/g2rd-license/v1/deactivate
 *   POST /wp-json/g2rd-license/v1/check
 */
class LicenseServer {
    /** @var string Namespace REST */
    private const REST_NAMESPACE = 'g2rd-license/v1';

    /** @var string Clé wp_options pour le secret de webhook GitHub */
    private const OPTION_WEBHOOK_SECRET = 'g2rd_release_webhook_secret';

    /** @var int Nombre maximum de tentatives par IP toutes les 5 minutes */
    private const RATE_LIMIT_MAX = 10;

    /** @var int Durée de la fenêtre de rate limit en secondes */
    private const RATE_LIMIT_WINDOW = 300;

    /**
     * Enregistre les hooks — ne s'active que si FluentCart est présent.
     *
     * @return void
     */
    public function register_hooks(): void {
        // Le webhook de release ne dépend pas de FluentCart — toujours enregistré.
        \add_action('rest_api_init', [$this, 'register_webhook_route']);

        if (!$this->is_fluent_cart_active()) {
            return;
        }

        \add_action('rest_api_init', [$this, 'register_license_routes']);
    }

    /**
     * Détecte si ce WordPress est le serveur de licences (g2rd.fr).
     * Utilisé par ThemeOptions pour afficher l'onglet admin.
     *
     * Deux conditions requises :
     *   1. FluentCart est installé (condition technique)
     *   2. Le domaine courant est autorisé comme serveur de licences
     *      — défini via la constante G2RD_LICENSE_SERVER_HOSTS dans wp-config.php
     *        (ex. define('G2RD_LICENSE_SERVER_HOSTS', 'g2rd.fr,www.g2rd.fr'))
     *      — ou via le filtre g2rd_license_server_hosts
     *      — ou par défaut : g2rd.fr / www.g2rd.fr uniquement
     *
     * @return bool
     */
    public static function is_server_mode(): bool {
        if (!\function_exists('fluentCart') && !\class_exists('\FluentCart\App\App')) {
            return false;
        }

        $host = (string) \wp_parse_url(\home_url(), \PHP_URL_HOST);

        if (\defined('G2RD_LICENSE_SERVER_HOSTS') && !empty(\G2RD_LICENSE_SERVER_HOSTS)) {
            $allowed = array_map('trim', explode(',', \G2RD_LICENSE_SERVER_HOSTS));
        } else {
            $allowed = ['g2rd.fr', 'www.g2rd.fr'];
        }

        /** @var string[] $allowed */
        $allowed = (array) \apply_filters('g2rd_license_server_hosts', $allowed);

        return \in_array($host, $allowed, true);
    }

    /**
     * Détecte si FluentCart est actif sur ce WordPress (indépendamment du mode serveur).
     * Utilisé pour enregistrer les routes REST de validation de licences.
     *
     * @return bool
     */
    private function is_fluent_cart_active(): bool {
        return \function_exists('fluentCart') || \class_exists('\FluentCart\App\App');
    }

    /**
     * Enregistre la route webhook de release (indépendante de FluentCart).
     *
     * @return void
     */
    public function register_webhook_route(): void {
        \register_rest_route(
            self::REST_NAMESPACE,
            '/release-webhook',
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'handle_release_webhook'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'version' => [
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                        'validate_callback' => static fn( $v ) => (bool) preg_match('/^\d+\.\d+/', $v),
                    ],
                    'download_url' => [
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'esc_url_raw',
                        'validate_callback' => static fn( $v ) => filter_var($v, FILTER_VALIDATE_URL) !== false,
                    ],
                    'changelog' => [
                        'required'          => false,
                        'type'              => 'string',
                        'sanitize_callback' => 'wp_kses_post',
                    ],
                ],
            ]
        );
    }

    /**
     * Enregistre les routes REST de licences (nécessite FluentCart).
     *
     * @return void
     */
    public function register_license_routes(): void {
        \register_rest_route(
            self::REST_NAMESPACE,
            '/activate',
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'handle_activate'],
                'permission_callback' => '__return_true',
                'args'                => $this->license_args(),
            ]
        );

        \register_rest_route(
            self::REST_NAMESPACE,
            '/deactivate',
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'handle_deactivate'],
                'permission_callback' => '__return_true',
                'args'                => $this->license_args(),
            ]
        );

        \register_rest_route(
            self::REST_NAMESPACE,
            '/check',
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'handle_check'],
                'permission_callback' => '__return_true',
                'args'                => $this->license_args(),
            ]
        );

        $this->register_admin_routes();
    }

    // ── Handlers REST ─────────────────────────────────────────────────────

    /**
     * Active une licence sur un domaine.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function handle_activate( \WP_REST_Request $request ): \WP_REST_Response {
        if (!$this->check_rate_limit($request)) {
            return $this->error_response('rate_limit_exceeded', __('Trop de tentatives. Réessayez dans quelques minutes.', 'g2rd'), 429);
        }

        $license_key = $request->get_param('license_key');
        $site_url    = \trailingslashit($request->get_param('site_url'));

        // Récupérer la licence depuis FluentCart
        $license = $this->get_license($license_key);

        if (null === $license) {
            return $this->error_response('invalid_license', __('Clé de licence invalide.', 'g2rd'), 404);
        }

        // Vérifier l'état de la licence
        if ($license['status'] === 'expired') {
            return $this->error_response('license_expired', __('Cette licence a expiré.', 'g2rd'), 403);
        }

        if ($license['status'] === 'cancelled' || $license['status'] === 'revoked') {
            return $this->error_response('license_cancelled', __('Cette licence a été annulée.', 'g2rd'), 403);
        }

        // Vérifier les activations disponibles
        $activations      = $this->get_activations($license_key);
        $max_activations  = (int) ($license['max_activations'] ?? 1);
        $active_count     = count($activations);

        // Vérifier si ce domaine est déjà activé
        foreach ($activations as $activation) {
            if (\trailingslashit($activation['site_url']) === $site_url) {
                // Déjà activé sur ce domaine : retourner succès (idempotent)
                return $this->success_response([
                    'message'          => __('Licence déjà active sur ce domaine.', 'g2rd'),
                    'license'          => $this->format_license_response($license),
                    'activations_used' => $active_count,
                    'activations_left' => max(0, $max_activations - $active_count),
                ]);
            }
        }

        // Vérifier qu'il reste des activations
        if ($active_count >= $max_activations) {
            return $this->error_response(
                'max_activations_reached',
                /* translators: %d: nombre max d'activations */
                sprintf(__('Nombre maximum d\'activations atteint (%d).', 'g2rd'), $max_activations),
                403
            );
        }

        // Enregistrer l'activation
        $this->add_activation($license_key, $site_url, $request->get_header('X-Forwarded-For') ?: $request->get_header('REMOTE_ADDR') ?: '');

        $new_count = $active_count + 1;

        return $this->success_response([
            'message'          => __('Licence activée avec succès.', 'g2rd'),
            'license'          => $this->format_license_response($license),
            'activations_used' => $new_count,
            'activations_left' => max(0, $max_activations - $new_count),
        ]);
    }

    /**
     * Désactive une licence sur un domaine (libère une activation).
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function handle_deactivate( \WP_REST_Request $request ): \WP_REST_Response {
        $license_key = $request->get_param('license_key');
        $site_url    = \trailingslashit($request->get_param('site_url'));

        $license = $this->get_license($license_key);
        if (null === $license) {
            return $this->error_response('invalid_license', __('Clé de licence invalide.', 'g2rd'), 404);
        }

        $this->remove_activation($license_key, $site_url);

        $activations     = $this->get_activations($license_key);
        $max_activations = (int) ($license['max_activations'] ?? 1);
        $active_count    = count($activations);

        return $this->success_response([
            'message'          => __('Licence désactivée sur ce domaine.', 'g2rd'),
            'activations_used' => $active_count,
            'activations_left' => max(0, $max_activations - $active_count),
        ]);
    }

    /**
     * Vérifie la validité d'une licence pour un domaine donné.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function handle_check( \WP_REST_Request $request ): \WP_REST_Response {
        if (!$this->check_rate_limit($request)) {
            return $this->error_response('rate_limit_exceeded', __('Trop de tentatives.', 'g2rd'), 429);
        }

        $license_key = $request->get_param('license_key');
        $site_url    = \trailingslashit($request->get_param('site_url'));

        $license = $this->get_license($license_key);
        if (null === $license) {
            return $this->error_response('invalid_license', __('Clé de licence invalide.', 'g2rd'), 404);
        }

        // Vérifier que ce domaine est bien activé
        $activations   = $this->get_activations($license_key);
        $domain_active = false;
        foreach ($activations as $activation) {
            if (\trailingslashit($activation['site_url']) === $site_url) {
                $domain_active = true;
                break;
            }
        }

        if (!$domain_active) {
            return $this->error_response('domain_not_activated', __('Ce domaine n\'est pas activé pour cette licence.', 'g2rd'), 403);
        }

        $max_activations = (int) ($license['max_activations'] ?? 1);
        $active_count    = count($activations);

        return $this->success_response([
            'license'          => $this->format_license_response($license),
            'activations_used' => $active_count,
            'activations_left' => max(0, $max_activations - $active_count),
        ]);
    }

    /**
     * Reçoit le webhook GitHub Action lors d'une release.
     * Met à jour la version et l'URL de téléchargement du produit dans FluentCart.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function handle_release_webhook( \WP_REST_Request $request ): \WP_REST_Response {
        // Vérifier la signature HMAC-SHA256 du webhook
        $signature = $request->get_header('X-G2RD-Signature');
        $secret    = \get_option(self::OPTION_WEBHOOK_SECRET, '');

        if (empty($secret) || empty($signature)) {
            return $this->error_response('missing_signature', __('Signature manquante.', 'g2rd'), 401);
        }

        $payload       = $request->get_body();
        $expected_hmac = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($expected_hmac, $signature)) {
            return $this->error_response('invalid_signature', __('Signature invalide.', 'g2rd'), 403);
        }

        $version      = $request->get_param('version');
        $download_url = $request->get_param('download_url');
        $changelog    = $request->get_param('changelog') ?? '';

        // Stocker les informations de la dernière release
        \update_option('g2rd_latest_version',      $version, false);
        \update_option('g2rd_latest_download_url', $download_url, false);
        \update_option('g2rd_latest_changelog',    $changelog, false);
        \update_option('g2rd_latest_release_date', \current_time('mysql'), false);

        // Invalider le transient de version côté clients (si applicable)
        \delete_transient('g2rd_latest_release_info');

        // Hook pour permettre une intégration FluentCart personnalisée
        \do_action('g2rd_release_webhook_received', $version, $download_url, $changelog);

        return $this->success_response([
            'message' => sprintf(
                /* translators: %s: numéro de version */
                __('Release %s enregistrée avec succès.', 'g2rd'),
                $version
            ),
        ]);
    }

    // ── Admin — gestion des clés (g2rd.fr uniquement) ────────────────────

    /**
     * Enregistre les routes REST admin pour la gestion des clés de licence.
     * Accessibles uniquement aux utilisateurs avec la capacité manage_options.
     *
     * @return void
     */
    private function register_admin_routes(): void {
        \register_rest_route(
            'g2rd/v1',
            '/license-admin',
            [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [$this, 'rest_admin_list'],
                    'permission_callback' => static fn() => \current_user_can('manage_options'),
                ],
                [
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => [$this, 'rest_admin_create'],
                    'permission_callback' => static fn() => \current_user_can('manage_options'),
                    'args'                => [
                        'license_key'     => [
                            'required'          => false,
                            'type'              => 'string',
                            'sanitize_callback' => 'sanitize_text_field',
                        ],
                        'max_activations' => [
                            'required'          => false,
                            'type'              => 'integer',
                            'default'           => 1,
                            'minimum'           => 1,
                            'sanitize_callback' => 'absint',
                        ],
                        'expires_at'      => [
                            'required'          => false,
                            'type'              => 'string',
                            'sanitize_callback' => 'sanitize_text_field',
                        ],
                    ],
                ],
            ]
        );

        \register_rest_route(
            'g2rd/v1',
            '/license-admin/(?P<license_key>[a-zA-Z0-9_-]+)',
            [
                [
                    'methods'             => \WP_REST_Server::DELETABLE,
                    'callback'            => [$this, 'rest_admin_delete'],
                    'permission_callback' => static fn() => \current_user_can('manage_options'),
                    'args'                => [
                        'license_key' => [
                            'required'          => true,
                            'type'              => 'string',
                            'sanitize_callback' => 'sanitize_text_field',
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Liste toutes les clés de licence (REST GET /g2rd/v1/license-admin).
     * Fusionne les clés créées via l'interface admin (g2rd_license_keys)
     * et celles créées via FluentCart (wp_fc_licenses).
     *
     * @return \WP_REST_Response
     */
    public function rest_admin_list(): \WP_REST_Response {
        $stored  = (array) \get_option('g2rd_license_keys', []);
        $result  = [];

        // 1. Clés créées via l'interface admin
        foreach ($stored as $key => $data) {
            $activations = $this->get_activations((string) $key);
            $result[]    = [
                'key'               => $key,
                'status'            => $data['status'] ?? 'active',
                'max_activations'   => (int) ($data['max_activations'] ?? 1),
                'expires_at'        => $data['expires_at'] ?? null,
                'created_at'        => $data['created_at'] ?? null,
                'source'            => 'admin',
                'activations_used'  => count($activations),
                'activated_domains' => array_map(
                    static fn( $a ) => [
                        'url'          => $a['site_url'],
                        'activated_at' => $a['activated_at'] ?? null,
                    ],
                    $activations
                ),
            ];
        }

        // 2. Clés FluentCart absentes de g2rd_license_keys
        foreach ($this->get_fluent_cart_licenses() as $fc) {
            $fc_key = (string) $fc['license_key'];
            if (isset($stored[ $fc_key ])) {
                continue; // déjà incluse ci-dessus
            }
            $activations = $this->get_activations($fc_key);
            $result[]    = [
                'key'               => $fc_key,
                'status'            => $fc['status'],
                'max_activations'   => $fc['max_activations'],
                'expires_at'        => $fc['expires_at'],
                'created_at'        => $fc['created_at'],
                'source'            => 'fluentcart',
                'activations_used'  => count($activations),
                'activated_domains' => array_map(
                    static fn( $a ) => [
                        'url'          => $a['site_url'],
                        'activated_at' => $a['activated_at'] ?? null,
                    ],
                    $activations
                ),
            ];
        }

        return new \WP_REST_Response(['success' => true, 'licenses' => $result], 200);
    }

    /**
     * Retourne toutes les licences FluentCart depuis wp_fc_licenses.
     *
     * @return array<int, array<string, mixed>>
     */
    private function get_fluent_cart_licenses(): array {
        global $wpdb;

        $table = $wpdb->prefix . 'fc_licenses';

        if ($wpdb->get_var( $wpdb->prepare('SHOW TABLES LIKE %s', $table) ) !== $table) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            return [];
        }

        $rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            "SELECT license_key, status, activations_limit, expires_at, created_at, product_id FROM {$wpdb->prefix}fc_licenses ORDER BY id DESC",
            ARRAY_A
        );

        if (empty($rows)) {
            return [];
        }

        return array_map(
            static fn( $row ) => [
                'license_key'     => (string) ($row['license_key'] ?? ''),
                'status'          => $row['status'] ?? 'active',
                'max_activations' => (int) ($row['activations_limit'] ?? 1),
                'expires_at'      => $row['expires_at'] ?? null,
                'created_at'      => $row['created_at'] ?? null,
            ],
            $rows
        );
    }

    /**
     * Crée une clé de licence (REST POST /g2rd/v1/license-admin).
     * Si license_key est absent, une clé est générée automatiquement.
     *
     * @param \WP_REST_Request $request Requête REST entrante.
     * @return \WP_REST_Response
     */
    public function rest_admin_create( \WP_REST_Request $request ): \WP_REST_Response {
        $license_key     = \sanitize_text_field((string) ($request->get_param('license_key') ?? ''));
        $max_activations = \absint($request->get_param('max_activations') ?? 1);
        $expires_raw     = $request->get_param('expires_at');
        $expires_at      = !empty($expires_raw) ? \sanitize_text_field((string) $expires_raw) : null;

        if (empty($license_key)) {
            $license_key = self::generate_license_key();
        }

        $stored = (array) \get_option('g2rd_license_keys', []);

        if (isset($stored[ $license_key ])) {
            return new \WP_REST_Response(
                ['success' => false, 'message' => __('Cette clé existe déjà.', 'g2rd')],
                400
            );
        }

        $stored[ $license_key ] = [
            'status'          => 'active',
            'max_activations' => max(1, $max_activations),
            'expires_at'      => $expires_at,
            'created_at'      => \current_time('mysql'),
        ];

        \update_option('g2rd_license_keys', $stored, false);

        return new \WP_REST_Response(
            [
                'success'     => true,
                'license_key' => $license_key,
                'data'        => $stored[ $license_key ],
            ],
            201
        );
    }

    /**
     * Supprime une clé de licence (REST DELETE /g2rd/v1/license-admin/{key}).
     *
     * @param \WP_REST_Request $request Requête REST entrante.
     * @return \WP_REST_Response
     */
    public function rest_admin_delete( \WP_REST_Request $request ): \WP_REST_Response {
        $license_key = \sanitize_text_field((string) ($request->get_param('license_key') ?? ''));
        $stored      = (array) \get_option('g2rd_license_keys', []);

        if (!isset($stored[ $license_key ])) {
            return new \WP_REST_Response(
                ['success' => false, 'message' => __('Clé introuvable.', 'g2rd')],
                404
            );
        }

        unset($stored[ $license_key ]);
        \update_option('g2rd_license_keys', $stored, false);

        return new \WP_REST_Response(['success' => true], 200);
    }

    /**
     * Génère une clé de licence unique au format G2RD-XXXXX-XXXXX-XXXXX-XXXXX.
     *
     * @return string
     */
    public static function generate_license_key(): string {
        $chars    = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $len      = strlen($chars);
        $segments = [];

        for ($i = 0; $i < 4; $i++) {
            $seg = '';
            for ($j = 0; $j < 5; $j++) {
                $seg .= $chars[ random_int(0, $len - 1) ];
            }
            $segments[] = $seg;
        }

        return 'G2RD-' . implode('-', $segments);
    }

    // ── FluentCart Bridge ─────────────────────────────────────────────────

    /**
     * Récupère une licence par sa clé.
     *
     * Ordre de priorité :
     *   1. Option wp `g2rd_license_keys` (stockage natif — source principale)
     *   2. Hook FluentCart `fluent_cart/license/get_by_key` (intégration tierce)
     *   3. Requête directe via FluentCart ORM (fallback DB)
     *
     * @param string $license_key Clé de licence.
     * @return array<string, mixed>|null
     */
    private function get_license( string $license_key ): ?array {
        // 1. Clés enregistrées nativement dans wp_options
        $stored = (array) \get_option('g2rd_license_keys', []);
        if (isset($stored[ $license_key ]) && \is_array($stored[ $license_key ])) {
            return \array_merge(
                [
                    'status'          => 'active',
                    'max_activations' => 1,
                    'expires_at'      => null,
                ],
                $stored[ $license_key ]
            );
        }

        // 2. Hook FluentCart (si intégration tierce enregistrée)
        $license = \apply_filters('fluent_cart/license/get_by_key', null, $license_key); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound,WordPress.NamingConventions.ValidHookName.UseUnderscores -- hook FluentCart tiers
        if (\is_array($license) && !empty($license)) {
            return $license;
        }

        // 3. Requête directe via FluentCart ORM (fallback DB)
        if (\class_exists('\FluentCart\App\Models\Order')) {
            return $this->get_license_from_db($license_key);
        }

        return null;
    }

    /**
     * Valide une clé de licence pour un téléchargement (validation clé-seule,
     * sans exigence d'activation de domaine — le download précède l'activation).
     *
     * Réutilisée par ThemeDownload pour protéger l'endpoint de téléchargement.
     *
     * @param string $license_key Clé de licence.
     * @return bool True si la clé existe, est active et non expirée.
     */
    public function is_key_valid_for_download( string $license_key ): bool {
        if ('' === $license_key) {
            return false;
        }

        $license = $this->get_license($license_key);
        if (null === $license) {
            return false;
        }

        $status = (string) ($license['status'] ?? '');
        if (\in_array($status, ['expired', 'cancelled', 'revoked', 'invalid'], true)) {
            return false;
        }

        $expires_at = $license['expires_at'] ?? null;
        if (!empty($expires_at)) {
            $timestamp = \strtotime((string) $expires_at);
            if (false !== $timestamp && $timestamp < \time()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Récupère la liste des activations pour une clé de licence.
     * Stockées dans l'option WordPress g2rd_activations_{hash(key)}.
     *
     * @param string $license_key
     * @return array<int, array{site_url: string, activated_at: string, ip: string}>
     */
    private function get_activations( string $license_key ): array {
        $option_key = 'g2rd_act_' . substr(hash('sha256', $license_key), 0, 16);
        $stored     = \get_option($option_key, []);
        return is_array($stored) ? $stored : [];
    }

    /**
     * Ajoute une activation pour un domaine.
     *
     * @param string $license_key
     * @param string $site_url
     * @param string $ip
     * @return void
     */
    private function add_activation( string $license_key, string $site_url, string $ip ): void {
        $option_key  = 'g2rd_act_' . substr(hash('sha256', $license_key), 0, 16);
        $activations = $this->get_activations($license_key);

        $activations[] = [
            'site_url'     => $site_url,
            'activated_at' => \current_time('mysql'),
            'ip'           => sanitize_text_field($ip),
        ];

        \update_option($option_key, $activations, false);
    }

    /**
     * Supprime l'activation d'un domaine depuis le portail client (accès public contrôlé).
     * Appelée par FluentCartSupport::ajaxDeactivateDomain() après vérification de propriété.
     *
     * @param string $license_key
     * @param string $site_url
     * @return void
     */
    public function remove_activation_public( string $license_key, string $site_url ): void {
        $this->remove_activation($license_key, $site_url);
    }

    /**
     * Supprime l'activation d'un domaine (libère une activation).
     *
     * @param string $license_key
     * @param string $site_url
     * @return void
     */
    private function remove_activation( string $license_key, string $site_url ): void {
        $option_key  = 'g2rd_act_' . substr(hash('sha256', $license_key), 0, 16);
        $activations = $this->get_activations($license_key);

        $activations = array_values(
            array_filter(
                $activations,
                static fn( $a ) => \trailingslashit($a['site_url']) !== $site_url
            )
        );

        \update_option($option_key, $activations, false);
    }

    /**
     * Récupère la licence depuis la base de données FluentCart (fallback).
     * Le schéma exact dépend de la version de FluentCart installée.
     *
     * @param string $license_key
     * @return array|null
     */
    private function get_license_from_db( string $license_key ): ?array {
        global $wpdb;

        // FluentCart stocke les licences dans la table wp_fc_licenses ou comme meta de commande.
        // Adapter selon la version de FluentCart installée.
        $table = $wpdb->prefix . 'fc_licenses';

        if ($wpdb->get_var( $wpdb->prepare('SHOW TABLES LIKE %s', $table) ) !== $table) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            return null;
        }

        $row = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}fc_licenses WHERE license_key = %s LIMIT 1",
                $license_key
            ),
            ARRAY_A
        );

        if (empty($row)) {
            return null;
        }

        return [
            'id'              => (int) $row['id'],
            'license_key'     => $row['license_key'],
            'status'          => $row['status'] ?? 'active',
            'max_activations' => (int) ($row['activations_limit'] ?? 1),
            'expires_at'      => $row['expires_at'] ?? null,
            'product_id'      => $row['product_id'] ?? null,
        ];
    }

    /**
     * Formate la réponse licence (sans données sensibles).
     *
     * @param array $license
     * @return array
     */
    private function format_license_response( array $license ): array {
        return [
            'status'          => $license['status'] ?? 'active',
            'max_activations' => (int) ($license['max_activations'] ?? 1),
            'expires_at'      => $license['expires_at'] ?? null,
        ];
    }

    // ── Rate limiting ─────────────────────────────────────────────────────

    /**
     * Vérifie et incrémente le compteur de rate limit par IP.
     *
     * @param \WP_REST_Request $request
     * @return bool True si la requête est autorisée.
     */
    private function check_rate_limit( \WP_REST_Request $request ): bool {
        $ip          = $request->get_header('X-Forwarded-For') ?: (sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'] ?? '')));
        $ip          = preg_replace('/[^0-9a-fA-F.:,]/', '', $ip); // Nettoyer l'IP
        $ip          = explode(',', $ip)[0]; // Prendre la première IP si plusieurs
        $key         = 'g2rd_rl_' . md5($ip);
        $count       = (int) \get_transient($key);

        if ($count >= self::RATE_LIMIT_MAX) {
            return false;
        }

        if ($count === 0) {
            \set_transient($key, 1, self::RATE_LIMIT_WINDOW);
        } else {
            \set_transient($key, $count + 1, self::RATE_LIMIT_WINDOW);
        }

        return true;
    }

    // ── Helpers de réponse ────────────────────────────────────────────────

    /**
     * Retourne une réponse REST de succès.
     *
     * @param array $data
     * @return \WP_REST_Response
     */
    private function success_response( array $data ): \WP_REST_Response {
        return new \WP_REST_Response(
            array_merge(['success' => true], $data),
            200
        );
    }

    /**
     * Retourne une réponse REST d'erreur.
     *
     * @param string $code
     * @param string $message
     * @param int    $status
     * @return \WP_REST_Response
     */
    private function error_response( string $code, string $message, int $status = 400 ): \WP_REST_Response {
        return new \WP_REST_Response(
            [
                'success' => false,
                'code'    => $code,
                'message' => $message,
            ],
            $status
        );
    }

    /**
     * Arguments communs aux routes de licence.
     *
     * @return array
     */
    private function license_args(): array {
        return [
            'license_key' => [
                'required'          => true,
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => static fn( $v ) => !empty($v) && strlen($v) >= 10,
            ],
            'site_url' => [
                'required'          => true,
                'type'              => 'string',
                'sanitize_callback' => 'esc_url_raw',
                'validate_callback' => static fn( $v ) => filter_var($v, FILTER_VALIDATE_URL) !== false,
            ],
        ];
    }
}
