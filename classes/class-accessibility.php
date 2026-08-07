<?php

/**
 * Socle d'accessibilité RGAA du thème.
 *
 * À ne pas confondre avec le panneau d'accessibilité (assets/js/accessibility.js),
 * qui offre à l'internaute des préférences de confort — taille de texte, contraste,
 * masque de lecture. Ce panneau est une surcouche optionnelle et ne participe pas
 * à la conformité : le RGAA s'évalue sur le HTML livré.
 *
 * Cette classe fournit donc ce qui doit être présent sur *toutes* les pages, quelle
 * que soit la configuration du site, et n'est jamais conditionnée à une option.
 *
 * @package G2RD
 * @since 1.33.0
 * @license EUPL-1.2
 * @copyright (c) 2024 Sebastien GERARD
 * @link https://accessibilite.numerique.gouv.fr/methode/criteres-et-tests/
 */

namespace G2RD;

/**
 * Classe Accessibility
 *
 * Critères RGAA couverts ici :
 * - 12.5 — lien d'accès rapide au contenu principal.
 * - 9.1  — cible du lien d'évitement, en s'assurant que le landmark principal
 *          porte bien un identifiant.
 */
class Accessibility {

    /**
     * Identifiant posé sur le landmark principal et visé par le lien d'évitement.
     */
    private const MAIN_ID = 'g2rd-contenu-principal';

    /**
     * Vrai dès que l'identifiant a été posé sur un premier landmark principal.
     *
     * Un document ne doit comporter qu'un seul `id` donné. Les gabarits imbriquent
     * parfois un groupe `main` dans un autre (contenu de page inséré dans un
     * gabarit qui en déclare déjà un) : sans ce garde-fou, l'identifiant serait
     * dupliqué et le lien d'évitement deviendrait ambigu.
     *
     * @var bool
     */
    private bool $main_marked = false;

    /**
     * Enregistre les hooks.
     *
     * @since 1.33.0
     * @return void
     */
    public function register_hooks(): void {
        \add_action( 'wp_body_open', [ $this, 'renderSkipLink' ], 1 );
        \add_action( 'wp_head', [ $this, 'outputSkipLinkStyles' ], 5 );
        \add_filter( 'render_block_core/group', [ $this, 'identifyMainLandmark' ], 10, 2 );
        \add_action( 'wp', [ $this, 'resetMainFlag' ] );
        \add_action( 'admin_notices', [ $this, 'renderContrastNotice' ] );
    }

    /**
     * Avertit dans l'administration quand la palette active comporte des
     * combinaisons sous le seuil AA.
     *
     * Le thème ne modifie pas les couleurs : une charte est une décision de
     * marque, et la corriger d'office donnerait un site conforme mais hors
     * identité. L'avertissement signale les associations à ne pas employer
     * pour du texte, la décision restant à l'équipe.
     *
     * @since 1.33.0
     * @return void
     */
    public function renderContrastNotice(): void {
        if ( ! \current_user_can( 'edit_theme_options' ) ) {
            return;
        }

        $screen = \function_exists( 'get_current_screen' ) ? \get_current_screen() : null;
        $screen_id = $screen->id ?? '';

        // Cantonné aux écrans où l'on travaille l'apparence : ailleurs, ce
        // serait un bandeau permanent que l'on finit par ne plus lire.
        if ( ! \in_array( $screen_id, [ 'themes', 'appearance_page_g2rd-options' ], true ) ) {
            return;
        }

        $failing = $this->paletteFailures();

        if ( [] === $failing ) {
            return;
        }

        $shown = \array_slice( $failing, 0, 5 );
        $rest  = \count( $failing ) - \count( $shown );

        echo '<div class="notice notice-warning"><p><strong>';
        echo \esc_html__( 'Accessibilité — contrastes de la palette', 'g2rd' );
        echo '</strong></p><p>';
        \printf(
            /* translators: %d: nombre de combinaisons. */
            \esc_html__( '%d combinaison(s) de la palette active passent sous le seuil AA du RGAA (4,5:1) et ne doivent donc pas servir à afficher du texte :', 'g2rd' ),
            (int) \count( $failing )
        );
        echo '</p><ul style="list-style:disc;margin-left:1.5em">';

        foreach ( $shown as $pair ) {
            \printf(
                '<li><strong>%1$s</strong> / <strong>%2$s</strong> — %3$s:1</li>',
                \esc_html( $pair['a'] ),
                \esc_html( $pair['b'] ),
                \esc_html( \number_format_i18n( $pair['ratio'], 2 ) )
            );
        }

        echo '</ul>';

        if ( $rest > 0 ) {
            echo '<p>';
            \printf(
                /* translators: %d: nombre de combinaisons restantes. */
                \esc_html__( '… et %d autre(s).', 'g2rd' ),
                (int) $rest
            );
            echo '</p>';
        }

        echo '<p><em>';
        echo \esc_html__( 'Le thème ne modifie aucune couleur : ces couples restent utilisables pour des aplats ou des bordures, mais pas pour du texte. La conformité se constate sur les pages réelles.', 'g2rd' );
        echo '</em></p></div>';
    }

    /**
     * Palette active et paires en échec, mises en cache.
     *
     * Le calcul est quadratique sur la palette et ne change qu'avec elle : le
     * refaire à chaque écran d'administration serait gratuit en résultat et
     * coûteux en temps.
     *
     * @since 1.33.0
     * @return array<int, array{a:string, b:string, ratio:float}>
     */
    private function paletteFailures(): array {
        $palette = [];

        if ( \class_exists( '\WP_Theme_JSON_Resolver' ) ) {
            $data = \WP_Theme_JSON_Resolver::get_merged_data()->get_settings();
            $palette = $data['color']['palette']['theme'] ?? [];
        }

        if ( [] === $palette ) {
            return [];
        }

        $used = $this->collectUsedPairs();

        $key = 'g2rd_contrast_' . \md5( (string) \wp_json_encode( [ $palette, $used ] ) );
        $cached = \get_transient( $key );

        if ( \is_array( $cached ) ) {
            return $cached;
        }

        $failing = self::failingPairs( $palette, $used );
        \set_transient( $key, $failing, \DAY_IN_SECONDS );

        return $failing;
    }

    /**
     * Couples texte/fond employés par le balisage livré avec le thème.
     *
     * Les gabarits, parties et compositions sont le seul balisage que le thème
     * maîtrise. Le contenu des pages, lui, vit en base et échappe à cette
     * analyse : l'alerte porte donc sur ce que le thème propose, pas sur ce que
     * le site affiche réellement.
     *
     * @since 1.33.0
     * @return array<int, array{0:string, 1:string}>
     */
    private function collectUsedPairs(): array {
        $pairs = [];
        $root  = \get_template_directory();

        foreach ( [ '/templates/*.html', '/parts/*.html', '/patterns/*.php' ] as $pattern ) {
            foreach ( (array) \glob( $root . $pattern ) as $file ) {
                if ( ! \is_readable( $file ) ) {
                    continue;
                }

                $markup = (string) \file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Fichier du thème, chemin interne contrôlé.
                if ( '' === $markup ) {
                    continue;
                }
                foreach ( self::usedColorPairs( $markup ) as $pair ) {
                    $pairs[] = $pair;
                }
            }
        }

        return $pairs;
    }

    /**
     * Réinitialise le drapeau à chaque rendu de page.
     *
     * @since 1.33.0
     * @return void
     */
    public function resetMainFlag(): void {
        $this->main_marked = false;
    }

    /**
     * Affiche le lien d'évitement en tout premier élément du `body`.
     *
     * RGAA 12.5. Le lien doit être le premier élément focusable de la page pour
     * qu'une navigation au clavier puisse sauter l'en-tête et le menu dès la
     * première tabulation.
     *
     * @since 1.33.0
     * @return void
     */
    public function renderSkipLink(): void {
        if ( \is_admin() ) {
            return;
        }

        \printf(
            '<a class="g2rd-skip-link screen-reader-text" href="#%1$s">%2$s</a>',
            \esc_attr( self::MAIN_ID ),
            \esc_html__( 'Aller au contenu principal', 'g2rd' )
        );
    }

    /**
     * Styles du lien d'évitement, en ligne dans le `head`.
     *
     * En ligne et non dans une feuille : le lien est le premier élément focusable
     * de la page, il doit donc être stylé avant tout chargement différé. Une
     * feuille séparée le laisserait apparaître en clair le temps du rendu.
     *
     * Le lien reste dans le flux (`clip-path` et taille nulle) plutôt que masqué
     * par `display:none`, qui le retirerait de l'ordre de tabulation et le rendrait
     * inatteignable — le contraire de l'effet recherché.
     *
     * @since 1.33.0
     * @return void
     */
    public function outputSkipLinkStyles(): void {
        if ( \is_admin() ) {
            return;
        }

        $css = '.g2rd-skip-link{position:absolute;top:0;left:0;z-index:100000;'
            . 'width:1px;height:1px;overflow:hidden;clip-path:inset(50%);white-space:nowrap;'
            . 'padding:0;margin:0;border:0;'
            . 'background:var(--wp--preset--color--primary,#0f172a);'
            . 'color:var(--wp--preset--color--white,#fff);'
            . 'font-family:inherit;font-size:1rem;font-weight:600;text-decoration:underline;}'
            . '.g2rd-skip-link:focus{width:auto;height:auto;overflow:visible;clip-path:none;'
            . 'padding:0.75rem 1.25rem;margin:0.5rem;border-radius:4px;'
            . 'outline:3px solid var(--wp--preset--color--accent,#ec4899);outline-offset:2px;}'
            // Le landmark reçoit le focus par programme : il ne doit pas pour
            // autant afficher un contour, l'internaute n'ayant pas tabulé jusqu'à lui.
            . '#' . self::MAIN_ID . ':focus{outline:none;}';

        \printf( '<style id="g2rd-skip-link-css">%s</style>' . "\n", $css ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS littéral construit ci-dessus, sans donnée externe.
    }

    /**
     * Pose l'identifiant sur le premier groupe rendu en tant que `main`.
     *
     * Le lien d'évitement a besoin d'une cible. Passer par un filtre plutôt que
     * par un ancrage écrit dans les fichiers de gabarit couvre aussi les gabarits
     * personnalisés par l'utilisateur, qui vivent en base et non dans le thème.
     *
     * `tabindex="-1"` est indispensable : sans lui, les navigateurs déplacent bien
     * la vue mais pas le focus clavier, et la tabulation suivante repart du haut
     * de la page — le lien paraît alors sans effet au clavier.
     *
     * @since 1.33.0
     * @param string $content Rendu HTML du bloc.
     * @param array  $block   Données du bloc.
     * @return string
     */
    public function identifyMainLandmark( $content, $block ): string {
        $content = (string) $content;

        if ( $this->main_marked || \is_admin() ) {
            return $content;
        }

        if ( 'main' !== ( $block['attrs']['tagName'] ?? '' ) ) {
            return $content;
        }

        if ( ! \preg_match( '/^(\s*<main\b[^>]*>)/i', $content, $m ) ) {
            return $content;
        }

        $this->main_marked = true;

        /*
         * La cible est une ancre dédiée insérée en tête du landmark, et non un
         * identifiant posé sur <main>. Poser l'identifiant échouait dès que
         * l'auteur avait défini son propre ancrage ou qu'un identifiant existait
         * déjà : on renonçait alors à marquer, et le lien d'évitement pointait
         * vers une cible absente — il ne faisait plus rien, précisément pour les
         * pages les plus travaillées.
         *
         * Une ancre séparée n'entre en conflit avec rien et fonctionne dans tous
         * les cas. `tabindex="-1"` la rend focusable par programme sans l'ajouter
         * à l'ordre de tabulation.
         */
        $anchor = \sprintf(
            '<span id="%s" tabindex="-1" class="screen-reader-text"></span>',
            \esc_attr( self::MAIN_ID )
        );

        return \preg_replace(
            '/^(\s*<main\b[^>]*>)/i',
            '$1' . $anchor,
            $content,
            1
        );
    }

    /**
     * Identifiant du landmark principal, pour les autres modules du thème.
     *
     * @since 1.33.0
     * @return string
     */
    public static function mainId(): string {
        return self::MAIN_ID;
    }

    // ── Contrastes de la palette (RGAA 10.6) ───────────────────────────────

    /**
     * Seuil AA pour du texte de taille courante.
     */
    private const AA_TEXT = 4.5;

    /**
     * Convertit une couleur CSS hexadécimale en composantes RVB.
     *
     * Seul le format hexadécimal est traité : les palettes du thème n'utilisent
     * que celui-là. Une valeur non reconnue renvoie null et la paire est
     * simplement ignorée, plutôt que de produire un faux résultat.
     *
     * @since 1.33.0
     * @param string $hex Couleur, avec ou sans croisillon.
     * @return array{0:int,1:int,2:int}|null
     */
    public static function hexToRgb( string $hex ): ?array {
        $hex = \ltrim( \trim( $hex ), '#' );

        if ( 3 === \strlen( $hex ) ) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if ( ! \preg_match( '/^[0-9a-f]{6}$/i', $hex ) ) {
            return null;
        }

        return [
            (int) \hexdec( \substr( $hex, 0, 2 ) ),
            (int) \hexdec( \substr( $hex, 2, 2 ) ),
            (int) \hexdec( \substr( $hex, 4, 2 ) ),
        ];
    }

    /**
     * Luminance relative d'une couleur, au sens WCAG.
     *
     * @since 1.33.0
     * @param array{0:int,1:int,2:int} $rgb Composantes 0–255.
     * @return float
     */
    public static function relativeLuminance( array $rgb ): float {
        $channels = [];

        foreach ( $rgb as $value ) {
            $c = $value / 255;
            $channels[] = $c <= 0.03928
                ? $c / 12.92
                : \pow( ( $c + 0.055 ) / 1.055, 2.4 );
        }

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }

    /**
     * Rapport de contraste entre deux couleurs hexadécimales.
     *
     * @since 1.33.0
     * @param string $a Première couleur.
     * @param string $b Seconde couleur.
     * @return float|null Rapport de 1 à 21, ou null si une couleur est illisible.
     */
    public static function contrastRatio( string $a, string $b ): ?float {
        $rgb_a = self::hexToRgb( $a );
        $rgb_b = self::hexToRgb( $b );

        if ( null === $rgb_a || null === $rgb_b ) {
            return null;
        }

        $lum_a = self::relativeLuminance( $rgb_a );
        $lum_b = self::relativeLuminance( $rgb_b );

        $lighter = \max( $lum_a, $lum_b );
        $darker  = \min( $lum_a, $lum_b );

        return ( $lighter + 0.05 ) / ( $darker + 0.05 );
    }

    /**
     * Repère les paires de la palette qui ne tiennent pas le seuil AA.
     *
     * Le paramètre `$used` restreint l'examen aux couples réellement associés
     * dans le balisage. Sans lui, une palette de treize couleurs produit
     * soixante-dix-huit combinaisons dont la plupart n'ont aucun sens — « blanc
     * sur gris clair » n'est pas une association d'auteur, c'est deux fonds. Le
     * signalement deviendrait un bandeau qu'on cesse de lire, et la seule
     * information utile s'y perdrait.
     *
     * Les paires sont dédoublonnées : le contraste étant symétrique, « A sur B »
     * et « B sur A » donnent le même rapport.
     *
     * @since 1.33.0
     * @param array<int, array{slug?:string, name?:string, color?:string}> $palette Palette theme.json.
     * @param array<int, array{0:string, 1:string}>|null                   $used    Couples de slugs texte/fond, ou null pour tout examiner.
     * @return array<int, array{a:string, b:string, ratio:float}> Trié du pire au moins mauvais.
     */
    public static function failingPairs( array $palette, ?array $used = null ): array {
        $colors = [];

        foreach ( $palette as $entry ) {
            $hex = (string) ( $entry['color'] ?? '' );
            if ( null === self::hexToRgb( $hex ) ) {
                continue; // dégradés, currentColor, transparent…
            }
            $slug = (string) ( $entry['slug'] ?? '' );
            $colors[ $slug ] = [
                'label' => (string) ( $entry['name'] ?? $slug ?: $hex ),
                'hex'   => $hex,
                'slug'  => $slug,
            ];
        }

        // Sans liste d'usages, on examine toutes les combinaisons.
        if ( null === $used ) {
            $used = [];
            $slugs = \array_keys( $colors );
            $count = \count( $slugs );
            for ( $i = 0; $i < $count; $i++ ) {
                for ( $j = $i + 1; $j < $count; $j++ ) {
                    $used[] = [ $slugs[ $i ], $slugs[ $j ] ];
                }
            }
        }

        $failing = [];
        $seen    = [];

        foreach ( $used as $pair ) {
            [ $text, $background ] = $pair;

            if ( ! isset( $colors[ $text ], $colors[ $background ] ) ) {
                continue;
            }

            // Le contraste étant symétrique, une clé triée suffit à dédoublonner.
            $key = $text < $background ? $text . '|' . $background : $background . '|' . $text;
            if ( isset( $seen[ $key ] ) ) {
                continue;
            }
            $seen[ $key ] = true;

            $ratio = self::contrastRatio( $colors[ $text ]['hex'], $colors[ $background ]['hex'] );

            if ( null === $ratio || $ratio >= self::AA_TEXT ) {
                continue;
            }

            $failing[] = [
                'a'     => $colors[ $text ]['label'],
                'b'     => $colors[ $background ]['label'],
                'ratio' => \round( $ratio, 2 ),
            ];
        }

        \usort( $failing, static fn( $x, $y ) => $x['ratio'] <=> $y['ratio'] );

        return $failing;
    }

    /**
     * Relève les couples texte/fond effectivement posés sur un même élément.
     *
     * WordPress rend les couleurs de palette en classes `has-{slug}-color` et
     * `has-{slug}-background-color`. Deux de ces classes sur la même balise
     * signalent une association voulue par l'auteur — la seule qui mérite
     * d'être vérifiée.
     *
     * @since 1.33.0
     * @param string $markup Balisage à analyser.
     * @return array<int, array{0:string, 1:string}>
     */
    public static function usedColorPairs( string $markup ): array {
        $pairs = [];

        if ( ! \preg_match_all( '/class="([^"]*)"/i', $markup, $matches ) ) {
            return $pairs;
        }

        foreach ( $matches[1] as $class_attr ) {
            if ( ! \preg_match( '/\bhas-([a-z0-9-]+)-background-color\b/i', $class_attr, $bg ) ) {
                continue;
            }
            // Le suffixe « -background-color » contient « -color » : on l'écarte
            // d'abord, sans quoi le fond serait aussi pris pour une couleur de texte.
            $without_bg = \str_replace( $bg[0], '', $class_attr );

            if ( ! \preg_match( '/\bhas-([a-z0-9-]+)-color\b/i', $without_bg, $fg ) ) {
                continue;
            }

            $pairs[] = [ $fg[1], $bg[1] ];
        }

        return $pairs;
    }
}
