<?php
/**
 * Suivi des clics de contact (téléphone et e-mail)
 *
 * Reprise native de l'extension « G2RD — Suivi des clics de contact » 1.2.0.
 * Compte les clics sur les liens tel: et mailto: (y compris les adresses
 * obfusquées par Cloudflare), émet un événement Google Analytics / GTM et
 * tient un compteur local par mois et par type, affiché dans le tableau de bord.
 *
 * Fonctionnalité optionnelle (clé « contact_tracking ») réservée aux sites
 * dont la licence G2RD est active. Les compteurs sont stockés dans la même
 * option que l'extension : un site qui passe de l'extension au thème conserve
 * son historique.
 *
 * @package    G2RD
 * @since      1.42.0
 * @license    EUPL-1.2
 * @copyright  (c) 2026 Sebastien GERARD
 */

namespace G2RD;

/**
 * Suivi des clics sur les liens téléphone et e-mail.
 */
class ContactTracking {

    /** Clé de la fonctionnalité dans la page d'options. */
    public const FEATURE = 'contact_tracking';

    /** Option stockant les compteurs. Jamais en autoload. */
    public const OPTION = 'g2rd_contact_clicks';

    /** Nombre de mois d'historique conservés par type. */
    public const MONTHS = 12;

    /**
     * Types de contact suivis. Source unique de vérité : enum du schéma REST,
     * liste blanche serveur et squelette des compteurs.
     */
    public const TYPES = [ 'tel', 'mail' ];

    /** Clics acceptés par IP et par minute. Très au-dessus d'un usage humain. */
    public const RATE_LIMIT = 20;

    /** Espace de noms REST partagé avec les autres endpoints du thème. */
    public const REST_NAMESPACE = 'g2rd/v1';

    /** Route REST recevant les clics. */
    public const REST_ROUTE = '/contact-click';

    /** Identifiant du widget du tableau de bord (sert aussi d'ancre). */
    public const WIDGET_ID = 'g2rd_contact_clicks';

    /** Handle du script front. */
    public const SCRIPT_HANDLE = 'g2rd-contact-tracking';

    /**
     * Constante définie par l'extension d'origine : sa présence indique que
     * l'extension est encore active sur le site.
     */
    public const PLUGIN_CONSTANT = 'G2RD_CONTACT_SLUG';

    /** Format attendu d'une clé de mois. */
    private const MONTH_PATTERN = '/^\d{4}-\d{2}$/';

    /** @var bool Fonctionnalité activée dans les options. */
    private bool $enabled;

    /** @var bool Licence valide. */
    private bool $licensed;

    /** @var bool Vrai lorsque le suivi est réellement en service sur cette requête. */
    private static bool $running = false;

    /**
     * @param LicenseManager $license_manager Instance du gestionnaire de licences.
     */
    public function __construct( LicenseManager $license_manager ) {
        $this->enabled  = ThemeOptions::isFeatureEnabled( self::FEATURE );
        $this->licensed = $license_manager->isLicenseValid();
    }

    /**
     * Enregistre les hooks, uniquement si la fonctionnalité peut tourner.
     *
     * @return void
     */
    public function register_hooks(): void {
        if ( ! $this->enabled || ! $this->licensed ) {
            return;
        }

        // L'extension d'origine encore active compterait chaque clic deux fois :
        // le thème s'efface et invite simplement à la désactiver.
        if ( self::pluginIsActive() ) {
            \add_action( 'admin_notices', [ $this, 'renderPluginNotice' ] );
            return;
        }

        self::$running = true;

        \add_action( 'wp_enqueue_scripts', [ $this, 'enqueueScript' ] );
        \add_action( 'rest_api_init', [ $this, 'registerRoute' ] );
        \add_action( 'wp_dashboard_setup', [ $this, 'registerDashboardWidget' ] );
    }

    /**
     * Indique si le suivi est en service (fonctionnalité, licence, pas de conflit).
     *
     * @return bool
     */
    public static function isRunning(): bool {
        return self::$running;
    }

    /**
     * Indique si l'extension d'origine est encore active.
     *
     * Les extensions sont chargées avant le thème : la constante est donc
     * déjà définie au moment de l'amorçage du thème.
     *
     * @return bool
     */
    public static function pluginIsActive(): bool {
        return \defined( self::PLUGIN_CONSTANT );
    }

    // ── Compteurs (fonctions pures, testées unitairement) ─────────────────

    /**
     * Ramène une valeur quelconque à une structure de compteurs garantie.
     *
     * L'option peut être corrompue par un import, WP-CLI ou une autre
     * extension : tout contenu inattendu est ramené à zéro plutôt que de
     * provoquer une erreur dans le tableau de bord.
     *
     * @param mixed $raw Valeur brute de l'option.
     * @return array<string, array{total: int, months: array<string, int>}>
     */
    public static function normalize( $raw ): array {
        $raw   = \is_array( $raw ) ? $raw : [];
        $stats = [];

        foreach ( self::TYPES as $type ) {
            $entry  = $raw[ $type ] ?? null;
            $total  = 0;
            $months = [];

            if ( \is_array( $entry ) ) {
                $total = \is_numeric( $entry['total'] ?? null ) ? (int) $entry['total'] : 0;

                if ( isset( $entry['months'] ) && \is_array( $entry['months'] ) ) {
                    foreach ( $entry['months'] as $month => $count ) {
                        // Seules les clés « AAAA-MM » sont conservées : une clé
                        // arbitraire ferait grossir l'option sans jamais expirer.
                        if ( \is_string( $month ) && \preg_match( self::MONTH_PATTERN, $month ) && \is_numeric( $count ) ) {
                            $months[ $month ] = (int) $count;
                        }
                    }
                }
            }

            $stats[ $type ] = [
                'total'  => $total,
                'months' => $months,
            ];
        }

        return $stats;
    }

    /**
     * Ajoute un clic au type et au mois donnés.
     *
     * Un type inconnu ou un mois mal formé laisse les compteurs inchangés.
     *
     * @param array<string, mixed> $stats Compteurs actuels.
     * @param string               $type  Type de contact (tel|mail).
     * @param string               $month Mois au format AAAA-MM.
     * @return array<string, array{total: int, months: array<string, int>}>
     */
    public static function increment( array $stats, string $type, string $month ): array {
        $stats = self::normalize( $stats );

        if ( ! \in_array( $type, self::TYPES, true ) || ! \preg_match( self::MONTH_PATTERN, $month ) ) {
            return $stats;
        }

        // Les deux types sont comptés séparément, jamais additionnés.
        $stats[ $type ]['total']           += 1;
        $stats[ $type ]['months'][ $month ] = ( $stats[ $type ]['months'][ $month ] ?? 0 ) + 1;

        // Borne l'historique pour que l'option ne grossisse pas indéfiniment.
        if ( \count( $stats[ $type ]['months'] ) > self::MONTHS ) {
            \krsort( $stats[ $type ]['months'] );
            $stats[ $type ]['months'] = \array_slice( $stats[ $type ]['months'], 0, self::MONTHS, true );
        }

        return $stats;
    }

    /**
     * Somme des clics de tous les types pour un mois donné.
     *
     * @param array<string, mixed> $stats Compteurs.
     * @param string               $month Mois au format AAAA-MM.
     * @return int
     */
    public static function sumForMonth( array $stats, string $month ): int {
        $sum = 0;
        foreach ( self::normalize( $stats ) as $entry ) {
            $sum += $entry['months'][ $month ] ?? 0;
        }
        return $sum;
    }

    /**
     * Compteurs enregistrés, normalisés.
     *
     * @return array<string, array{total: int, months: array<string, int>}>
     */
    public static function getStats(): array {
        return self::normalize( \get_option( self::OPTION, [] ) );
    }

    /**
     * Total des clics du mois en cours, tous types confondus.
     *
     * @return int
     */
    public static function monthlyTotal(): int {
        return self::sumForMonth( self::getStats(), self::currentMonth() );
    }

    /**
     * Mois courant dans le fuseau du site : un clic le 31 à 23 h 30, heure
     * française, compte dans le mois en cours et pas dans le suivant en UTC.
     *
     * @return string
     */
    private static function currentMonth(): string {
        return (string) \current_time( 'Y-m' );
    }

    // ── Front ─────────────────────────────────────────────────────────────

    /**
     * Charge le script de suivi sur le front.
     *
     * Écoute déléguée sur document : aucun bloc à initialiser, le pattern
     * MutationObserver des blocs interactifs est donc inutile ici.
     *
     * @return void
     */
    public function enqueueScript(): void {
        if ( \is_admin() || ScriptsManager::is_speed_test() ) {
            return;
        }

        $path    = \get_template_directory() . '/assets/js/contact-tracking.js';
        $version = \file_exists( $path ) ? (string) \filemtime( $path ) : (string) \wp_get_theme()->get( 'Version' );

        \wp_enqueue_script(
            self::SCRIPT_HANDLE,
            \get_template_directory_uri() . '/assets/js/contact-tracking.js',
            [],
            $version,
            [
                'in_footer' => true,
                'strategy'  => 'defer',
            ]
        );

        // Script « before » : ne remet pas en cause la stratégie defer.
        \wp_add_inline_script(
            self::SCRIPT_HANDLE,
            'window.g2rdContactTracking=' . \wp_json_encode(
                [ 'endpoint' => \esc_url_raw( \rest_url( self::REST_NAMESPACE . self::REST_ROUTE ) ) ]
            ) . ';',
            'before'
        );
    }

    // ── REST ──────────────────────────────────────────────────────────────

    /**
     * Déclare l'endpoint recevant les clics.
     *
     * @return void
     */
    public function registerRoute(): void {
        \register_rest_route(
            self::REST_NAMESPACE,
            self::REST_ROUTE,
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'handleClick' ],

                /*
                 * Endpoint volontairement public : les visiteurs ne sont pas
                 * connectés. Pas de nonce non plus : les pages sont souvent
                 * servies depuis un cache (LiteSpeed, WP Rocket…) et un nonce
                 * périmé rejetterait les clics légitimes. Protection : enum
                 * stricte, limitation du débit par IP, et l'endpoint ne fait
                 * qu'incrémenter un compteur sans rien exposer.
                 */
                'permission_callback' => '__return_true',
                'args'                => [
                    'type' => [
                        'required' => true,
                        'type'     => 'string',
                        'enum'     => self::TYPES,
                    ],
                ],
            ]
        );
    }

    /**
     * Enregistre un clic.
     *
     * @param \WP_REST_Request $request Requête entrante.
     * @return \WP_REST_Response
     */
    public function handleClick( \WP_REST_Request $request ): \WP_REST_Response {
        $type = $request->get_param( 'type' );

        // Défense en profondeur : l'enum du schéma filtre déjà, on revérifie.
        if ( ! \is_string( $type ) || ! \in_array( $type, self::TYPES, true ) ) {
            return new \WP_REST_Response( [ 'ok' => false ], 400 );
        }

        // Au-delà du plafond, sortie AVANT toute écriture : un flood coûte une
        // lecture, jamais une écriture supplémentaire.
        $key    = 'g2rd_contact_rl_' . \md5( self::clientIp() );
        $stored = \get_transient( $key );
        $hits   = \is_numeric( $stored ) ? (int) $stored : 0;

        if ( $hits >= self::RATE_LIMIT ) {
            return new \WP_REST_Response( [ 'ok' => false ], 429 );
        }
        \set_transient( $key, $hits + 1, MINUTE_IN_SECONDS );

        $stats = self::increment( self::getStats(), $type, self::currentMonth() );

        // autoload à false : compteurs utiles dans l'admin uniquement.
        \update_option( self::OPTION, $stats, false );

        // 204 : corps vide, sendBeacon() ignore de toute façon la réponse.
        return new \WP_REST_Response( null, 204 );
    }

    /**
     * Adresse IP du visiteur, pour la limitation du débit uniquement.
     *
     * REMOTE_ADDR est la seule valeur non falsifiable par le client. Derrière
     * un CDN, brancher le filtre « g2rd_contact_client_ip » sur l'en-tête de
     * confiance du CDN (ex. HTTP_CF_CONNECTING_IP). L'IP n'est jamais
     * stockée : seule son empreinte sert de clé à un transient d'une minute.
     *
     * @return string
     */
    private static function clientIp(): string {
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) && \is_string( $_SERVER['REMOTE_ADDR'] )
            ? \sanitize_text_field( \wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
            : '';

        // Un filtre tiers peut retourner n'importe quoi : seule une chaîne est
        // acceptée, sinon la limitation de débit retombe sur l'IP locale.
        $filtered = \apply_filters( 'g2rd_contact_client_ip', $ip );

        return \is_string( $filtered ) ? $filtered : $ip;
    }

    // ── Administration ────────────────────────────────────────────────────

    /**
     * Ajoute le widget au tableau de bord.
     *
     * @return void
     */
    public function registerDashboardWidget(): void {
        if ( ! \current_user_can( 'manage_options' ) ) {
            return;
        }

        \wp_add_dashboard_widget(
            self::WIDGET_ID,
            \__( 'Clics de contact', 'g2rd' ),
            [ $this, 'renderDashboardWidget' ]
        );
    }

    /**
     * Affiche le widget : mois en cours et cumul, par type de contact.
     *
     * @return void
     */
    public function renderDashboardWidget(): void {
        $stats  = self::getStats();
        $month  = self::currentMonth();
        $labels = [
            'tel'  => \__( 'Téléphone', 'g2rd' ),
            'mail' => \__( 'E-mail', 'g2rd' ),
        ];

        echo '<table class="g2rd-contact-stats">';
        echo '<thead><tr>';
        echo '<th scope="col">' . \esc_html__( 'Contact', 'g2rd' ) . '</th>';
        echo '<th scope="col" class="is-num">' . \esc_html__( 'Ce mois-ci', 'g2rd' ) . '</th>';
        echo '<th scope="col" class="is-num">' . \esc_html__( 'Depuis le début', 'g2rd' ) . '</th>';
        echo '</tr></thead><tbody>';

        $recorded = 0;
        foreach ( $labels as $type => $label ) {
            $total     = $stats[ $type ]['total'];
            $current   = $stats[ $type ]['months'][ $month ] ?? 0;
            $recorded += $total;

            echo '<tr>';
            echo '<th scope="row">' . \esc_html( $label ) . '</th>';
            echo '<td class="is-num"><strong>' . \esc_html( (string) $current ) . '</strong></td>';
            echo '<td class="is-num">' . \esc_html( (string) $total ) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';

        if ( 0 === $recorded ) {
            echo '<p class="g2rd-contact-stats__empty">'
                . \esc_html__( 'Aucun clic enregistré pour le moment.', 'g2rd' )
                . '</p>';
        }
    }

    /**
     * Notice affichée tant que l'extension d'origine reste active.
     *
     * @return void
     */
    public function renderPluginNotice(): void {
        if ( ! \current_user_can( 'activate_plugins' ) ) {
            return;
        }

        echo '<div class="notice notice-warning"><p>';
        echo \esc_html__(
            'Le suivi des clics de contact est désormais intégré au thème G2RD. Désactivez l’extension « G2RD — Suivi des clics de contact » : le thème prendra le relais et conservera les compteurs existants.',
            'g2rd'
        );
        echo ' <a href="' . \esc_url( \admin_url( 'plugins.php' ) ) . '">' . \esc_html__( 'Gérer les extensions', 'g2rd' ) . '</a>';
        echo '</p></div>';
    }
}
