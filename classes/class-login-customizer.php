<?php
/**
 * Personnalisation de la page de connexion WordPress
 *
 * Lit les options stockées dans `g2rd_login_settings` et applique dynamiquement
 * les couleurs, logo, image de fond, mise en page et bouton CTA.
 * Quand le mode personnalisé est désactivé, WordPress affiche sa page par défaut.
 *
 * @package    G2RD
 * @since      1.3.5
 * @license    EUPL-1.2
 * @copyright  (c) 2025 Sebastien GERARD
 */

namespace G2RD;

/**
 * Classe LoginCustomizer
 */
class LoginCustomizer {

    /** Clé wp_options. */
    public const OPTION_KEY = 'g2rd_login_settings';

    /** Logo par défaut du thème. */
    private const DEFAULT_LOGO_PATH = '/assets/img/Nouveau-logo-G2RD-Agence-Web-blanc-Horizontale@3x.png';

    /** Image de fond par défaut. */
    private const DEFAULT_BG_PATH = '/assets/img/g2rd_image_admin.jpg';

    /** Paramètres par défaut. */
    private const DEFAULTS = [
        'enabled'         => true,
        'layout'          => 'two-columns',
        'logoUrl'         => '',
        'logoLink'        => '',
        'panelColor'      => '#2f425d',
        'buttonColor'     => '#d4a373',
        'buttonTextColor' => '#ffffff',
        'linksColor'      => '#cccccc',
        'bgType'          => 'image',
        'bgColor'         => '#1a2a3a',
        'bgImageUrl'      => '',
        'ctaShow'         => true,
        'ctaText'         => 'Visiter notre site',
        'ctaUrl'          => 'https://g2rd.fr',
        'ctaColor'        => '#d4a373',
        'welcomeText'     => '',
        'borderRadius'    => 8,
    ];

    // ── API statique (utilisée par ThemeOptions) ───────────────────────────

    /**
     * Retourne les paramètres actuels fusionnés avec les valeurs par défaut.
     *
     * @return array<string, mixed>
     */
    public static function get_settings(): array {
        $saved = \get_option( self::OPTION_KEY, [] );
        return \array_merge( self::DEFAULTS, \is_array( $saved ) ? $saved : [] );
    }

    /**
     * Sanitise et enregistre les paramètres de connexion.
     *
     * @param array<string, mixed> $raw Données brutes transmises par le REST.
     * @return void
     */
    public static function save_settings( array $raw ): void {
        $allowed_layouts = [ 'one-column', 'two-columns' ];
        $allowed_bg      = [ 'color', 'image' ];

        \update_option(
            self::OPTION_KEY,
            [
                'enabled'         => ! empty( $raw['enabled'] ),
                'layout'          => \in_array( $raw['layout'] ?? '', $allowed_layouts, true )
                    ? $raw['layout']
                    : 'two-columns',
                'logoUrl'         => \esc_url_raw( (string) ( $raw['logoUrl']  ?? '' ) ),
                'logoLink'        => \esc_url_raw( (string) ( $raw['logoLink'] ?? '' ) ),
                'panelColor'      => self::sanitize_color( (string) ( $raw['panelColor']      ?? '' ), '#2f425d' ),
                'buttonColor'     => self::sanitize_color( (string) ( $raw['buttonColor']     ?? '' ), '#d4a373' ),
                'buttonTextColor' => self::sanitize_color( (string) ( $raw['buttonTextColor'] ?? '' ), '#ffffff' ),
                'linksColor'      => self::sanitize_color( (string) ( $raw['linksColor']      ?? '' ), '#cccccc' ),
                'bgType'          => \in_array( $raw['bgType'] ?? '', $allowed_bg, true )
                    ? $raw['bgType']
                    : 'image',
                'bgColor'         => self::sanitize_color( (string) ( $raw['bgColor'] ?? '' ), '#1a2a3a' ),
                'bgImageUrl'      => \esc_url_raw( (string) ( $raw['bgImageUrl'] ?? '' ) ),
                'ctaShow'         => ! empty( $raw['ctaShow'] ),
                'ctaText'         => \sanitize_text_field( (string) ( $raw['ctaText']  ?? '' ) ),
                'ctaUrl'          => \esc_url_raw( (string) ( $raw['ctaUrl']   ?? '' ) ),
                'ctaColor'        => self::sanitize_color( (string) ( $raw['ctaColor'] ?? '' ), '#d4a373' ),
                'welcomeText'     => \sanitize_text_field( (string) ( $raw['welcomeText'] ?? '' ) ),
                'borderRadius'    => \min( 32, \max( 0, (int) ( $raw['borderRadius'] ?? 8 ) ) ),
            ]
        );
    }

    /**
     * Valide une couleur hexadécimale et retourne le fallback si invalide.
     *
     * @param  string $color    Valeur soumise.
     * @param  string $fallback Valeur par défaut.
     * @return string
     */
    private static function sanitize_color( string $color, string $fallback ): string {
        if ( \preg_match( '/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color ) ) {
            return $color;
        }
        return $fallback;
    }

    // ── Hooks WordPress ────────────────────────────────────────────────────

    /**
     * Enregistre les hooks WordPress (uniquement si le mode personnalisé est actif).
     *
     * @return void
     */
    public function register_hooks(): void {
        if ( ! self::get_settings()['enabled'] ) {
            return;
        }
        \add_action( 'login_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        \add_filter( 'login_headerurl',       [ $this, 'filter_logo_url' ] );
        \add_filter( 'login_headertext',      [ $this, 'filter_logo_text' ] );
        \add_action( 'login_header',          [ $this, 'render_header' ], 0 );
        \add_action( 'login_footer',          [ $this, 'render_footer' ] );
    }

    /**
     * Charge le CSS de base + injecte les variables CSS dynamiques.
     *
     * @return void
     */
    public function enqueue_assets(): void {
        $s        = self::get_settings();
        $dir_path = \get_template_directory();
        $dir_uri  = \get_template_directory_uri();
        $css_path = $dir_path . '/assets/css/login.css';

        if ( ! \file_exists( $css_path ) ) {
            return;
        }

        \wp_enqueue_style(
            'g2rd-login',
            $dir_uri . '/assets/css/login.css',
            [],
            (string) \filemtime( $css_path )
        );

        // Résolution du logo et de l'image de fond
        $logo_url = $s['logoUrl'] ? \esc_url( $s['logoUrl'] ) : \esc_url( $dir_uri . self::DEFAULT_LOGO_PATH );
        $bg_url   = ( $s['bgType'] === 'image' )
            ? ( $s['bgImageUrl'] ? \esc_url( $s['bgImageUrl'] ) : \esc_url( $dir_uri . self::DEFAULT_BG_PATH ) )
            : '';

        $panel_color  = \esc_attr( $s['panelColor'] );
        $btn_color    = \esc_attr( $s['buttonColor'] );
        $btn_txt      = \esc_attr( $s['buttonTextColor'] );
        $links_color  = \esc_attr( $s['linksColor'] );
        $bg_color     = \esc_attr( $s['bgColor'] );
        $radius       = (int) $s['borderRadius'];
        $is_one_col   = $s['layout'] === 'one-column';
        $login_width  = $is_one_col ? '100%' : '50%';

        $inline_css = "
body.login {
    --g2rd-panel-color: {$panel_color};
    --g2rd-btn-color: {$btn_color};
    --g2rd-btn-text: {$btn_txt};
    --g2rd-links-color: {$links_color};
    --g2rd-radius: {$radius}px;
}
.login h1 a {
    background-image: url({$logo_url}) !important;
    background-size: contain !important;
    width: 250px !important;
    height: 70px !important;
    margin-bottom: 30px !important;
}
#login {
    background: var(--g2rd-panel-color) !important;
    width: {$login_width} !important;
}
.wp-core-ui .button-primary {
    background-color: var(--g2rd-btn-color) !important;
    border-color: var(--g2rd-btn-color) !important;
    color: var(--g2rd-btn-text) !important;
    border-radius: var(--g2rd-radius) !important;
    text-shadow: none !important;
    box-shadow: none !important;
}
.login #nav a, .login #backtoblog a {
    color: var(--g2rd-links-color) !important;
}
.login #nav a:hover, .login #backtoblog a:hover {
    color: var(--g2rd-btn-color) !important;
}
.login form .input {
    border-radius: var(--g2rd-radius) !important;
}
.g2rd-button {
    background: var(--g2rd-btn-color) !important;
    color: var(--g2rd-btn-text) !important;
    border-radius: var(--g2rd-radius) !important;
}";

        if ( $is_one_col ) {
            $inline_css .= "
.login-container { flex-direction: column !important; }
.login-image { display: none !important; }
body.login { background: {$bg_color} !important; }";
        } else {
            $bg_image_css = $bg_url
                ? ".login-image { background-image: url({$bg_url}) !important; }"
                : ".login-image { background-color: {$bg_color} !important; }";
            $inline_css .= "\n" . $bg_image_css;
        }

        \wp_add_inline_style( 'g2rd-login', $inline_css );

        // Bouton CTA
        if ( $s['ctaShow'] && ! empty( $s['ctaText'] ) && ! empty( $s['ctaUrl'] ) ) {
            $cta_url  = \esc_js( $s['ctaUrl'] );
            $cta_text = \esc_js( $s['ctaText'] );
            \wp_register_script( 'g2rd-login-js', false, [], false, true ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters
            \wp_enqueue_script( 'g2rd-login-js' );
            \wp_add_inline_script(
                'g2rd-login-js',
                "document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('loginform');
    if (form) {
        var btn = document.createElement('a');
        btn.href = '{$cta_url}';
        btn.target = '_blank';
        btn.rel = 'noopener noreferrer';
        btn.className = 'g2rd-button';
        btn.textContent = '{$cta_text}';
        form.insertAdjacentElement('afterend', btn);
    }
});"
            );
        }
    }

    /**
     * Filtre l'URL du logo (lien sur le logo).
     *
     * @return string
     */
    public function filter_logo_url(): string {
        $s    = self::get_settings();
        $link = $s['logoLink'] ? $s['logoLink'] : \home_url( '/' );
        return \esc_url( $link );
    }

    /**
     * Filtre le texte alternatif du logo.
     *
     * @return string
     */
    public function filter_logo_text(): string {
        return \get_bloginfo( 'name' );
    }

    /**
     * Ouvre le conteneur principal de la page de connexion.
     *
     * @return void
     */
    public function render_header(): void {
        $s      = self::get_settings();
        $is_one = $s['layout'] === 'one-column';

        echo '<div class="login-container">';
        if ( ! $is_one ) {
            echo '<div class="login-image"></div>';
        }
        if ( ! empty( $s['welcomeText'] ) ) {
            echo '<div class="g2rd-login-welcome">' . \esc_html( $s['welcomeText'] ) . '</div>';
        }
    }

    /**
     * Ferme le conteneur principal.
     *
     * @return void
     */
    public function render_footer(): void {
        echo '</div>';
    }
}
