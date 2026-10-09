<?php
/**
 * G2RD Child — refonte 2026.
 *
 * Rôles de ce fichier :
 *  1. Lire g2rd.json (racine du thème PARENT) de façon sûre et le convertir
 *     en custom properties CSS `--g2rd-*` (source unique des tokens).
 *  2. Charger les styles/scripts de la nouvelle interface, en s'appuyant sur
 *     GSAP déjà enregistré par le parent (aucun double chargement).
 *  3. Enregistrer la catégorie de patterns et les editor styles.
 *
 * @package G2RD_Child
 * @license EUPL-1.2
 */

declare(strict_types=1);

namespace G2RD_Child;

\defined('ABSPATH') || exit;

const VERSION = '1.6.3';

/**
 * Valeurs de repli si g2rd.json est absent ou invalide.
 * Le site reste fonctionnel et fidèle à la charte quoi qu'il arrive.
 */
function default_tokens(): array
{
    return [
        'colors' => [
            'ink' => '#020617', 'primary' => '#0f172a', 'surfaceSoft' => '#1e293b',
            'accent' => '#a3e635', 'accentHover' => '#84cc16', 'accentDeep' => '#4d7c0f',
            'backgroundLight' => '#f8fafc', 'surface' => '#f1f5f9',
            'textDark' => '#0f172a', 'textLight' => '#f8fafc',
            'textDim' => '#aab7c9', 'textMuted' => '#334155',
            'borderLight' => '#e2e8f0', 'borderDark' => 'rgba(148, 163, 184, 0.16)',
        ],
        'layout' => [
            'contentWidth' => '960px', 'wideWidth' => '1440px',
            'sectionSpacing' => 'clamp(5rem, 10vw, 8.5rem)',
            'gutter' => 'clamp(1.25rem, 4vw, 2.5rem)',
        ],
        'radius' => ['s' => '8px', 'm' => '12px', 'l' => '16px', 'full' => '999px'],
        'typography' => [
            'display' => 'clamp(2.75rem, 6.6vw, 6rem)', 'h2' => 'clamp(2rem, 4.2vw, 3.4rem)',
            'h3' => 'clamp(1.25rem, 2vw, 1.6rem)', 'body' => '1.125rem',
            'small' => '0.88rem', 'meta' => '0.78rem',
        ],
        'animation' => ['fast' => '0.2s', 'easing' => 'cubic-bezier(0.16, 1, 0.3, 1)'],
    ];
}

/**
 * Lit g2rd.json depuis la racine du thème parent, avec cache invalidé
 * par la date de modification du fichier. Jamais d'erreur fatale.
 */
function tokens(): array
{
    static $tokens = null;
    if (null !== $tokens) {
        return $tokens;
    }

    $defaults = default_tokens();
    $path     = \get_template_directory() . '/g2rd.json';

    if (!\is_readable($path)) {
        return $tokens = $defaults;
    }

    $mtime     = (int) @\filemtime($path);
    $cache_key = 'g2rd_child_tokens_' . $mtime;
    $cached    = \get_transient($cache_key);
    if (\is_array($cached)) {
        return $tokens = $cached;
    }

    $data = \wp_json_file_decode($path, ['associative' => true]);
    if (!\is_array($data)) {
        return $tokens = $defaults;
    }

    // Fusion superficielle par groupe : le JSON complète les valeurs par défaut.
    $tokens = $defaults;
    foreach (['colors', 'layout', 'radius', 'typography', 'animation'] as $group) {
        if (isset($data[$group]) && \is_array($data[$group])) {
            $tokens[$group] = \array_merge($defaults[$group] ?? [], $data[$group]);
        }
    }

    \set_transient($cache_key, $tokens, HOUR_IN_SECONDS);

    return $tokens;
}

/**
 * Convertit les tokens en bloc :root { --g2rd-* } (valeurs échappées).
 */
function tokens_css(): string
{
    $map = [
        'colors'     => '',
        'layout'     => 'layout-',
        'radius'     => 'radius-',
        'typography' => 'type-',
        'animation'  => 'anim-',
    ];

    $lines = [];
    foreach ($map as $group => $prefix) {
        foreach (tokens()[$group] ?? [] as $key => $value) {
            if (!\is_scalar($value)) {
                continue;
            }
            $slug = \strtolower((string) \preg_replace('/([a-z])([A-Z])/', '$1-$2', (string) $key));
            // Une custom property n'accepte qu'un jeu de caractères restreint.
            $safe = \preg_replace('/[^a-zA-Z0-9#%,.()\/\s\-]/', '', (string) $value);
            $lines[] = \sprintf('--g2rd-%s%s: %s;', $prefix, $slug, $safe);
        }
    }

    return ':root{' . \implode('', $lines) . '}';
}

/**
 * Styles front. `global` + `components` partout, `home` sur la page d'accueil,
 * `pages` sur le reste. Les tokens sont injectés en inline sur `global`.
 */
\add_action('wp_enqueue_scripts', function (): void {
    $uri = \get_stylesheet_directory_uri();
    $dir = \get_stylesheet_directory();

    $css = function (string $handle, string $file, array $deps = []) use ($uri, $dir): void {
        $path = $dir . '/assets/css/' . $file;
        if (\is_readable($path)) {
            \wp_enqueue_style($handle, $uri . '/assets/css/' . $file, $deps, (string) \filemtime($path));
        }
    };

    $css('g2rd-child-global', 'global.css');
    \wp_add_inline_style('g2rd-child-global', tokens_css());

    $css('g2rd-child-pages', 'pages.css', ['g2rd-child-global']);

    // home.css est la bibliothèque de composants partagés : le processus
    // (.g2rd-process), le CTA final (.g2rd-cta) et l'éditorial (.g2rd-editorial)
    // sont utilisés sur les fiches prestations et la plupart des pages
    // intérieures. Chargée partout — un chargement conditionnel laissait ces
    // sections sans styles hors accueil (rendu front ≠ éditeur).
    $css('g2rd-child-home', 'home.css', ['g2rd-child-global']);

    $css('g2rd-child-magic-bento', 'magic-bento.css', ['g2rd-child-global']);

    $css('g2rd-child-animations', 'animations.css', ['g2rd-child-global']);
}, 20);

/**
 * Scripts front. `global.js` (menu, bouton animations, lettres) est autonome ;
 * `animations.js` dépend de GSAP/ScrollTrigger déjà enregistrés par le parent.
 */
\add_action('wp_enqueue_scripts', function (): void {
    $uri = \get_stylesheet_directory_uri();
    $dir = \get_stylesheet_directory();

    // decor.js d'abord : il injecte le décor aria-hidden (scène du hero,
    // panneau, visuels bento, track du processus, bouton animations) afin
    // que patterns et parts ne contiennent que des blocs natifs/g2rd.
    // Les autres scripts en dépendent pour cibler ces éléments.
    if (\is_readable($dir . '/assets/js/decor.js')) {
        \wp_enqueue_script(
            'g2rd-child-decor',
            $uri . '/assets/js/decor.js',
            [],
            (string) \filemtime($dir . '/assets/js/decor.js'),
            ['strategy' => 'defer', 'in_footer' => true]
        );
    }
    $decor_dep = \wp_script_is('g2rd-child-decor', 'registered') ? ['g2rd-child-decor'] : [];

    if (\is_readable($dir . '/assets/js/global.js')) {
        \wp_enqueue_script(
            'g2rd-child-global',
            $uri . '/assets/js/global.js',
            $decor_dep,
            (string) \filemtime($dir . '/assets/js/global.js'),
            ['strategy' => 'defer', 'in_footer' => true]
        );
    }

    if (\is_readable($dir . '/assets/js/magic-bento.js')) {
        $bento_deps = $decor_dep;
        foreach (['gsap', 'scrolltrigger'] as $handle) {
            if (\wp_script_is($handle, 'registered')) {
                $bento_deps[] = $handle;
            }
        }
        \wp_enqueue_script(
            'g2rd-child-magic-bento',
            $uri . '/assets/js/magic-bento.js',
            $bento_deps,
            (string) \filemtime($dir . '/assets/js/magic-bento.js'),
            ['strategy' => 'defer', 'in_footer' => true]
        );
    }

    if (\is_readable($dir . '/assets/js/animations.js')) {
        // GSAP est fourni par le parent (class-gsap-animations). Si les handles
        // manquent (parent modifié), le script se désactive proprement côté JS.
        $deps = $decor_dep;
        foreach (['gsap', 'scrolltrigger'] as $handle) {
            if (\wp_script_is($handle, 'registered')) {
                $deps[] = $handle;
            }
        }
        \wp_enqueue_script(
            'g2rd-child-animations',
            $uri . '/assets/js/animations.js',
            $deps,
            (string) \filemtime($dir . '/assets/js/animations.js'),
            ['strategy' => 'defer', 'in_footer' => true]
        );
    }
}, 20);

/**
 * Garde-fou (v1.6.1) : le parent (inc/rgaa-accessibility.php, section 8) lit la
 * clé méta « _wp_alt_text », inexistante, et force alt="" + role="presentation"
 * sur TOUTES les images passées par wp_get_attachment_image (images mises en
 * avant, logo…). Ce filtre, exécuté après (priorité 20), restaure l'alt de la
 * médiathèque (clé « _wp_attachment_image_alt ») et retire le rôle décoratif
 * dès qu'un alt existe. À retirer une fois le parent corrigé.
 */
\add_filter('wp_get_attachment_image_attributes', function (array $attr, $attachment): array {
    if (!$attachment instanceof \WP_Post) {
        return $attr;
    }
    $alt = \trim(\wp_strip_all_tags((string) \get_post_meta($attachment->ID, '_wp_attachment_image_alt', true)));
    if ($alt === '') {
        return $attr;
    }
    if (!isset($attr['alt']) || \trim((string) $attr['alt']) === '') {
        $attr['alt'] = $alt;
    }
    if (isset($attr['role']) && $attr['role'] === 'presentation') {
        unset($attr['role']);
    }
    return $attr;
}, 20, 2);

/**
 * SEO / accessibilité : garantit un texte alternatif sur le logo (header et
 * footer) et un intitulé sur le lien qui l'entoure, même si l'alt du média
 * est vide en médiathèque. Corrige « image sans alt » et « lien sans ancre ».
 */
\add_filter('render_block_core/site-logo', function (string $html): string {
    if ($html === '') {
        return $html;
    }
    $alt = \esc_attr(\get_bloginfo('name') . ' — retour à l\'accueil');
    // Alt vide ou absent sur l'image du logo
    $html = \str_replace('alt=""', 'alt="' . $alt . '"', $html);
    if (\strpos($html, '<img') !== false && \strpos($html, 'alt=') === false) {
        $html = \str_replace('<img ', '<img alt="' . $alt . '" ', $html);
    }
    // Intitulé du lien (le logo est le seul contenu du lien)
    if (\strpos($html, 'aria-label=') === false) {
        $html = \str_replace('class="custom-logo-link"', 'class="custom-logo-link" aria-label="' . $alt . '"', $html);
    }
    return $html;
}, 10, 1);

/**
 * Applique la préférence « animations désactivées » avant le premier rendu
 * (évite tout flash), en tête de document. Lecture protégée du localStorage.
 */
\add_action('wp_head', function (): void {
    echo '<script>try{if(localStorage.getItem("g2rd-anim")==="off"){document.documentElement.classList.add("g2rd-anim-off");}}catch(e){}</script>' . "\n";
}, 1);

/**
 * Catégorie de patterns du thème enfant.
 */
\add_action('init', function (): void {
    \register_block_pattern_category('g2rd-refonte', [
        'label' => \__('G2RD — Refonte', 'g2rd-child'),
    ]);
});

/**
 * Le rendu dans l'éditeur doit rester proche du front.
 */
\add_action('after_setup_theme', function (): void {
    \add_editor_style([
        'assets/css/global.css',
        'assets/css/pages.css',
        'assets/css/home.css',
    ]);
});
