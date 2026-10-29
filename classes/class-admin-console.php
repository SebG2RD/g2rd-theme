<?php
/**
 * Console d'administration G2RD — point d'entrée unique et charte commune.
 *
 * Le thème crée un menu de premier niveau « G2RD » (ThemeOptions::registerAdminPage)
 * sous lequel toutes les interfaces G2RD se rangent : le thème, le connecteur, la
 * facturation et les extensions à venir. Cette classe fournit ce que ces extensions
 * partagent :
 *
 * - le slug du menu parent (MENU_SLUG) et l'icône ;
 * - la feuille de style de la console et ses polices, chargées automatiquement sur
 *   toute page rattachée au menu « G2RD » (hooks `toplevel_page_g2rd` et `g2rd_page_*`) ;
 * - des aides de rendu PHP (open() / close()) qui dessinent le bandeau, le titre et
 *   les tuiles avec la même charte que la console React, pour les pages d'extension
 *   écrites en PHP.
 *
 * Exemple côté extension :
 *
 *     add_action( 'g2rd_console_menu', function ( string $parent ) {
 *         add_submenu_page( $parent, 'Facturation', 'Facturation', 'manage_options',
 *             'g2rd-facturation', 'g2rd_facturation_render' );
 *     } );
 *
 *     function g2rd_facturation_render(): void {
 *         \G2RD\AdminConsole::open( [
 *             'eyebrow'     => 'Facturation',
 *             'title'       => 'Documents',
 *             'description' => 'Devis, factures et acomptes synchronisés avec Abby.',
 *             'tiles'       => [ [ 'label' => 'À finaliser', 'value' => 4, 'hint' => 'brouillons' ] ],
 *         ] );
 *         echo '<section class="g2rd-section">…</section>';
 *         \G2RD\AdminConsole::close();
 *     }
 *
 * @package G2RD
 * @since 1.41.0
 * @license EUPL-1.2
 */

namespace G2RD;

/**
 * Menu parent, assets partagés et aides de rendu de la console G2RD.
 */
class AdminConsole {

    /** Slug du menu de premier niveau « G2RD » (et de sa page d'accueil, le thème). */
    public const MENU_SLUG = 'g2rd';

    /** Position dans le menu : juste sous « Apparence » (60) et avant « Extensions » (65). */
    public const MENU_POSITION = 61;

    /** Handle de la feuille de style partagée. */
    public const STYLE_HANDLE = 'g2rd-console';

    /**
     * Version du contrat d'interface exposé aux extensions
     * (`window.G2RDOptions.console.version`). N'augmente que si une classe du
     * contrat change de sens — cf. docs/plugin-wordpress.md.
     */
    public const CONTRACT_VERSION = 2;

    /**
     * Enregistre les hooks.
     *
     * @return void
     */
    public function register_hooks(): void {
        \add_action( 'admin_enqueue_scripts', [ $this, 'enqueueConsoleAssets' ] );
        \add_action( 'admin_notices', [ $this, 'renderDashboardHub' ], 1 );
    }

    /**
     * Snippet qui pose le contrat d'interface dans `window.G2RDOptions.console`.
     * Imprimé dans <head> des pages de la console, donc lisible par les scripts
     * d'extension quel que soit leur ordre de chargement.
     *
     * @return string
     */
    public static function contractScript(): string {
        return 'window.G2RDOptions=window.G2RDOptions||{};window.G2RDOptions.console={version:' . (int) self::CONTRACT_VERSION . '};';
    }

    /**
     * Nombre d'opérations MCP en attente d'approbation (0 si la table n'existe pas).
     *
     * @return int
     */
    public static function pendingMcpOperations(): int {
        global $wpdb;
        $table = $wpdb->prefix . 'g2rd_mcp_confirmation_queue';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- vérification d'existence d'une table du thème.
        $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
        if ( $exists !== $table ) {
            return 0;
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- nom de table préfixé, non saisi par l'utilisateur.
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'pending'" );
    }

    /**
     * Hub de raccourcis G2RD sur l'accueil du tableau de bord : version, licence,
     * type de site, file MCP, et les entrées du menu « G2RD » (thème + extensions).
     *
     * @return void
     */
    public function renderDashboardHub(): void {
        $screen = \get_current_screen();
        if ( ! $screen || 'dashboard' !== $screen->id || ! \current_user_can( 'manage_options' ) ) {
            return;
        }

        global $submenu;

        $version  = (string) \wp_get_theme( \get_template() )->get( 'Version' );
        $licensed = \class_exists( __NAMESPACE__ . '\\LicenseManager' ) && LicenseManager::is_active();
        $pending  = self::pendingMcpOperations();
        $type     = (string) \get_option( 'g2rd_business_type', '' );
        $types    = \class_exists( __NAMESPACE__ . '\\BusinessMode' ) ? BusinessMode::TYPES : [];
        $console  = \admin_url( 'admin.php?page=' . self::MENU_SLUG );

        $facts = [
            [
                'label' => $licensed ? \__( 'Licence active', 'g2rd' ) : \__( 'Licence absente', 'g2rd' ),
                'tone'  => $licensed ? 'ok' : 'warn',
                'href'  => $console . '#licence',
            ],
            [
                'label' => '' !== $type && isset( $types[ $type ] )
                    ? \sprintf( /* translators: %s : type de site */ \__( 'Type de site : %s', 'g2rd' ), $types[ $type ] )
                    : \__( 'Type de site à définir', 'g2rd' ),
                'tone'  => '' !== $type ? 'ok' : 'warn',
                'href'  => $console . '#configuration',
            ],
            [
                'label' => $pending > 0
                    ? \sprintf( /* translators: %d : nombre d'opérations */ \_n( '%d opération MCP à approuver', '%d opérations MCP à approuver', $pending, 'g2rd' ), $pending )
                    : \__( 'File MCP vide', 'g2rd' ),
                'tone'  => $pending > 0 ? 'warn' : 'ok',
                'href'  => $console . '#mcp-queue',
            ],
        ];

        // Suivi des clics de contact : synthèse du mois, lien vers le widget détaillé.
        if ( \class_exists( __NAMESPACE__ . '\\ContactTracking' ) && ContactTracking::isRunning() ) {
            $clicks  = ContactTracking::monthlyTotal();
            $facts[] = [
                'label' => \sprintf( /* translators: %d : nombre de clics */ \_n( '%d clic de contact ce mois-ci', '%d clics de contact ce mois-ci', $clicks, 'g2rd' ), $clicks ),
                'tone'  => 'ok',
                'href'  => \admin_url( 'index.php#' . ContactTracking::WIDGET_ID ),
            ];
        }

        // Entrées du menu « G2RD » : le thème, l'assistant, puis les extensions.
        $links = [];
        foreach ( (array) ( $submenu[ self::MENU_SLUG ] ?? [] ) as $item ) {
            $slug = (string) ( $item[2] ?? '' );
            if ( '' === $slug || ! \current_user_can( (string) ( $item[1] ?? 'manage_options' ) ) ) {
                continue;
            }
            $links[] = [
                'label' => \wp_strip_all_tags( (string) ( $item[0] ?? $slug ) ),
                'href'  => \str_contains( $slug, '.php' ) ? \admin_url( $slug ) : \admin_url( 'admin.php?page=' . $slug ),
            ];
        }
        if ( ! $links ) {
            $links[] = [ 'label' => \__( 'Thème', 'g2rd' ), 'href' => $console ];
        }

        echo '<div class="g2rd-hub" role="region" aria-label="' . \esc_attr__( 'Console G2RD', 'g2rd' ) . '">';
        echo '<div class="g2rd-hub__brand">';
        echo '<span class="g2rd-hub__mark" aria-hidden="true">G</span>';
        echo '<div><strong class="g2rd-hub__title">' . \esc_html__( 'Console G2RD', 'g2rd' ) . '</strong>';
        echo '<span class="g2rd-hub__version">v' . \esc_html( $version ) . '</span></div>';
        echo '</div>';

        echo '<ul class="g2rd-hub__facts">';
        foreach ( $facts as $fact ) {
            echo '<li><a class="g2rd-hub__chip" href="' . \esc_url( $fact['href'] ) . '">';
            echo '<span class="g2rd-hub__dot is-' . \esc_attr( $fact['tone'] ) . '" aria-hidden="true"></span>';
            echo \esc_html( $fact['label'] ) . '</a></li>';
        }
        echo '</ul>';

        echo '<nav class="g2rd-hub__links" aria-label="' . \esc_attr__( 'Raccourcis G2RD', 'g2rd' ) . '">';
        foreach ( $links as $link ) {
            echo '<a class="g2rd-hub__link" href="' . \esc_url( $link['href'] ) . '">' . \esc_html( $link['label'] ) . '</a>';
        }
        echo '</nav>';
        echo '</div>';
    }

    /**
     * Icône du menu « G2RD » : un anneau ouvert qui dessine un G, en SVG inline.
     * WordPress recolore les SVG `fill`/`stroke` noirs selon le schéma de couleurs.
     *
     * @return string Data URI SVG.
     */
    public static function menuIcon(): string {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">'
            . '<path fill="none" stroke="black" stroke-width="2.4" stroke-linecap="round" d="M15.8 6.2A7 7 0 1 0 17 10h-6.2"/>'
            . '</svg>';
        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- encodage d'une icône SVG de menu, format attendu par add_menu_page().
        return 'data:image/svg+xml;base64,' . \base64_encode( $svg );
    }

    /**
     * Vrai si le hook de page courant appartient au menu « G2RD ».
     *
     * @param string $hook Suffixe de page admin.
     * @return bool
     */
    public static function isConsolePage( string $hook ): bool {
        return 'toplevel_page_' . self::MENU_SLUG === $hook
            || 0 === \strpos( $hook, self::MENU_SLUG . '_page_' );
    }

    /**
     * Charge la feuille de style de la console (et ses polices, via `g2rd-admin`)
     * sur toute page rattachée au menu « G2RD », y compris celles des extensions.
     *
     * @param string $hook Suffixe de page admin.
     * @return void
     */
    public function enqueueConsoleAssets( string $hook ): void {
        if ( ! self::isConsolePage( $hook ) ) {
            return;
        }
        self::enqueueStyle();

        // Contrat d'interface dans <head> : un script sans source imprime son code
        // inline avant tout script d'extension chargé en pied de page.
        \wp_register_script( 'g2rd-console-contract', '', [], (string) self::CONTRACT_VERSION, false );
        \wp_enqueue_script( 'g2rd-console-contract' );
        \wp_add_inline_script( 'g2rd-console-contract', self::contractScript() );
    }

    /**
     * Enregistre et charge la feuille de style partagée. Idempotent : une extension
     * peut l'appeler elle-même si sa page vit hors du menu « G2RD ».
     *
     * @return void
     */
    public static function enqueueStyle(): void {
        $rel  = '/blocks/g2rd-options-page/build/style-index.css';
        $path = \get_template_directory() . $rel;
        if ( ! \file_exists( $path ) ) {
            return;
        }
        if ( ! \wp_style_is( self::STYLE_HANDLE, 'registered' ) ) {
            \wp_register_style(
                self::STYLE_HANDLE,
                \get_template_directory_uri() . $rel,
                [ 'wp-components', 'dashicons', 'g2rd-admin' ],
                (string) \filemtime( $path )
            );
        }
        \wp_enqueue_style( self::STYLE_HANDLE );
    }

    /**
     * Ouvre une page de la console : bandeau sombre, barre, titre, tuiles, puis la
     * zone de contenu. À fermer avec close().
     *
     * @param array $args {
     *     @type string $eyebrow     Sur-titre (nom du module). Optionnel.
     *     @type string $title       Titre de la page. Requis.
     *     @type string $description Description courte. Optionnel.
     *     @type array  $tiles       Jusqu'à 3 tuiles : [ 'label', 'value', 'hint', 'mono' => bool ]. Optionnel.
     *     @type array  $chips       Puces d'état : [ 'label', 'tone' => 'ok'|'warn'|'' ]. Optionnel.
     * }
     * @return void
     */
    public static function open( array $args ): void {
        $eyebrow     = (string) ( $args['eyebrow'] ?? '' );
        $title       = (string) ( $args['title'] ?? '' );
        $description = (string) ( $args['description'] ?? '' );
        $tiles       = \array_slice( (array) ( $args['tiles'] ?? [] ), 0, 3 );
        $chips       = (array) ( $args['chips'] ?? [] );
        $version     = (string) \wp_get_theme( \get_template() )->get( 'Version' );

        echo '<hr class="wp-header-end">';
        echo '<div class="wrap" id="g2rd-options-root"><div class="g2rd-app g2rd-app--php">';
        echo '<div class="g2rd-band" aria-hidden="true"></div>';
        echo '<div class="g2rd-inner">';

        echo '<header class="g2rd-topbar">';
        echo '<div class="g2rd-topbar__brand">';
        echo '<span class="g2rd-topbar__mark" aria-hidden="true">G</span>';
        echo '<h1 class="g2rd-topbar__title">' . \esc_html__( 'Console G2RD', 'g2rd' ) . '</h1>';
        echo '<span class="g2rd-chip g2rd-chip--mono">v' . \esc_html( $version ) . '</span>';
        echo '</div>';
        if ( $chips ) {
            echo '<div class="g2rd-topbar__status">';
            foreach ( $chips as $chip ) {
                $tone = \in_array( $chip['tone'] ?? '', [ 'ok', 'warn' ], true ) ? ' is-' . $chip['tone'] : '';
                echo '<span class="g2rd-chip">';
                if ( $tone ) {
                    echo '<span class="g2rd-dot' . \esc_attr( $tone ) . '" aria-hidden="true"></span>';
                }
                echo \esc_html( (string) ( $chip['label'] ?? '' ) ) . '</span>';
            }
            echo '</div>';
        }
        echo '</header>';

        echo '<div class="g2rd-page-head">';
        if ( '' !== $eyebrow ) {
            echo '<p class="g2rd-page-head__eyebrow">' . \esc_html( $eyebrow ) . '</p>';
        }
        echo '<h2 class="g2rd-page-head__title">' . \esc_html( $title ) . '</h2>';
        if ( '' !== $description ) {
            echo '<p class="g2rd-page-head__desc">' . \esc_html( $description ) . '</p>';
        }
        echo '</div>';

        if ( $tiles ) {
            echo '<div class="g2rd-tiles">';
            foreach ( $tiles as $tile ) {
                $mono = ! empty( $tile['mono'] ) || \is_numeric( $tile['value'] ?? null );
                echo '<div class="g2rd-tile">';
                echo '<small class="g2rd-tile__label">' . \esc_html( (string) ( $tile['label'] ?? '' ) ) . '</small>';
                echo '<div class="g2rd-tile__row">';
                echo '<b class="g2rd-tile__value' . ( $mono ? ' is-mono' : '' ) . '">' . \esc_html( (string) ( $tile['value'] ?? '' ) ) . '</b>';
                if ( ! empty( $tile['hint'] ) ) {
                    echo '<span class="g2rd-tile__hint">' . \esc_html( (string) $tile['hint'] ) . '</span>';
                }
                echo '</div></div>';
            }
            echo '</div>';
        }

        echo '<div class="g2rd-body"><main class="g2rd-main"><div class="g2rd-tab-content">';
    }

    /**
     * Ferme une page ouverte avec open().
     *
     * @return void
     */
    public static function close(): void {
        echo '</div></main></div></div></div></div>';
    }
}
