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

        // Un ancrage défini par l'utilisateur fait autorité : on ne l'écrase pas,
        // des liens internes peuvent déjà pointer dessus.
        if ( ! empty( $block['attrs']['anchor'] ) ) {
            $this->main_marked = true;
            return $content;
        }

        if ( ! \preg_match( '/^(\s*<main\b)([^>]*)(>)/i', $content, $m ) ) {
            return $content;
        }

        if ( false !== \stripos( $m[2], ' id=' ) ) {
            $this->main_marked = true;
            return $content;
        }

        $this->main_marked = true;

        return \preg_replace(
            '/^(\s*<main\b)([^>]*)(>)/i',
            '$1$2 id="' . self::MAIN_ID . '" tabindex="-1"$3',
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
}
