<?php
/**
 * Gestion du bloc G2RD Pin Scroll
 *
 * Charge GSAP, ScrollTrigger et le script frontend uniquement sur les pages
 * contenant le bloc, et uniquement si la feature est activée et la licence valide.
 * Si les conditions ne sont pas remplies, retire le bloc de l'inserteur Gutenberg.
 *
 * @package    G2RD
 * @since      1.10.8
 * @license    EUPL-1.2
 * @copyright  (c) 2025 Sebastien GERARD
 */

namespace G2RD;

/**
 * Bloc G2RD Pin Scroll — Séquence d'images synchronisée au défilement
 */
class PinScroll {

    /** @var bool Feature activée dans les options */
    private bool $enabled;

    /** @var bool Licence valide */
    private bool $licensed;

    /** @var string Version du thème pour le cache-busting */
    private string $theme_version;

    /**
     * @param LicenseManager $license_manager Instance du gestionnaire de licences.
     */
    public function __construct( LicenseManager $license_manager ) {
        $this->enabled       = ThemeOptions::isFeatureEnabled( 'pin_scroll' );
        $this->licensed      = $license_manager->isLicenseValid();
        $this->theme_version = \wp_get_theme()->get( 'Version' );

        $this->register_hooks();
    }

    /**
     * Enregistre les hooks WordPress.
     *
     * @return void
     */
    private function register_hooks(): void {
        if ( ! $this->enabled || ! $this->licensed ) {
            \add_filter( 'allowed_block_types_all', [ $this, 'restrict_block' ], 10, 2 );
            return;
        }

        \add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /**
     * Retire g2rd/pin-scroll des blocs autorisés dans l'inserteur.
     *
     * @param bool|array<string>       $allowed Blocs autorisés (true = tous).
     * @param \WP_Block_Editor_Context $context Contexte de l'éditeur.
     * @return bool|array<string>
     */
    public function restrict_block( $allowed, $context ): bool|array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        $slug = 'g2rd/pin-scroll';

        if ( true === $allowed ) {
            $all = \array_keys( \WP_Block_Type_Registry::get_instance()->get_all_registered() );
            return \array_values( \array_filter( $all, static fn( $n ) => $n !== $slug ) );
        }

        return \array_values( \array_filter( (array) $allowed, static fn( $n ) => $n !== $slug ) );
    }

    /**
     * Charge GSAP, ScrollTrigger et le script frontend du bloc.
     * Conditionnel : uniquement si la page contient le bloc.
     *
     * @return void
     */
    public function enqueue_assets(): void {
        if ( ! $this->page_has_block() || \is_admin() || ScriptsManager::is_speed_test() ) {
            return;
        }

        \wp_enqueue_script(
            'gsap',
            'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js',
            [],
            '3.12.2',
            true
        );

        \wp_enqueue_script(
            'scrolltrigger',
            'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js',
            [ 'gsap' ],
            '3.12.2',
            true
        );

        \wp_enqueue_script(
            'g2rd-pin-scroll-view',
            \get_template_directory_uri() . '/blocks/g2rd-pin-scroll/build/view.js',
            [ 'scrolltrigger' ],
            $this->theme_version,
            true
        );
    }

    /**
     * Vérifie si la page courante contient le bloc g2rd/pin-scroll.
     *
     * @return bool
     */
    private function page_has_block(): bool {
        if ( \is_singular() ) {
            return \has_block( 'g2rd/pin-scroll' );
        }

        global $wp_query;
        if ( ! empty( $wp_query->posts ) ) {
            foreach ( $wp_query->posts as $post ) {
                if ( \has_block( 'g2rd/pin-scroll', $post ) ) {
                    return true;
                }
            }
        }

        return false;
    }
}
