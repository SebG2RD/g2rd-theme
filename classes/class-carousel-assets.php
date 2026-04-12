<?php
/**
 * Gestionnaire des assets pour le block G2RD Carousel (Swiper.js)
 *
 * @package G2RD
 * @since 1.0.0
 */

namespace G2RD;

class CarouselAssets {
    /**
     * Enregistre les hooks WordPress
     */
    public function register_hooks() {
        \add_action('wp_enqueue_scripts', [ $this, 'enqueueCarouselAssets' ]);
        \add_action('enqueue_block_assets', [ $this, 'enqueueCarouselEditorAssets' ]);
    }

    /**
     * Détermine si la page courante contient un bloc carrousel.
     */
    private function pageHasCarousel(): bool {
        if (\is_singular()) {
            return \has_block('g2rd/carousel');
        }

        // Archives, home, etc. : vérifier tous les posts de la query courante
        global $wp_query;
        if (!empty($wp_query->posts)) {
            foreach ($wp_query->posts as $post) {
                if (\has_block('g2rd/carousel', $post)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Charge Swiper dans le canvas iframe de l'éditeur Gutenberg.
     * `enqueue_block_assets` s'exécute dans le canvas de l'éditeur en WP 6.3+.
     */
    public function enqueueCarouselEditorAssets() {
        if ( ! \is_admin() ) {
            return;
        }

        \wp_enqueue_script(
            'swiper-js',
            \get_template_directory_uri() . '/blocks/g2rd-carousel/swiper-bundle.min.js',
            [],
            '11.0.5',
            true
        );

        \wp_enqueue_style(
            'swiper-css',
            \get_template_directory_uri() . '/blocks/g2rd-carousel/swiper-bundle.min.css',
            [],
            '11.0.5'
        );
    }

    /**
     * Enqueue les scripts et styles nécessaires au carrousel
     */
    public function enqueueCarouselAssets() {
        if (!$this->pageHasCarousel()) {
            return;
        }

        // Charger Swiper.js depuis les fichiers locaux
        \wp_enqueue_script(
            'swiper-js',
            get_template_directory_uri() . '/blocks/g2rd-carousel/swiper-bundle.min.js',
            [],
            '11.0.5',
            true
        );

        // Charger les styles Swiper
        \wp_enqueue_style(
            'swiper-css',
            get_template_directory_uri() . '/blocks/g2rd-carousel/swiper-bundle.min.css',
            [],
            '11.0.5'
        );

        // Charger les styles du block carrousel
        \wp_enqueue_style(
            'g2rd-carousel-style',
            get_template_directory_uri() . '/blocks/g2rd-carousel/src/carousel.css',
            ['swiper-css'],
            '1.0.0'
        );

        // Charger le script frontend du carrousel APRÈS Swiper
        \wp_enqueue_script(
            'g2rd-carousel-frontend',
            get_template_directory_uri() . '/blocks/g2rd-carousel/build/carousel-frontend.js',
            ['swiper-js'],
            '1.0.0',
            true
        );

        // Ajouter des données localisées pour le debug
        \wp_localize_script('g2rd-carousel-frontend', 'g2rdCarouselData', [
            'swiperUrl' => get_template_directory_uri() . '/blocks/g2rd-carousel/swiper-bundle.min.js',
            'themeUrl' => get_template_directory_uri(),
        ]);
    }
} 
