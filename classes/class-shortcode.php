<?php

/**
 * Gestion des shortcodes personnalisés
 * 
 * Cette classe gère l'enregistrement et l'affichage des shortcodes pour les métadonnées personnalisées.
 *
 * @package G2RD
 * @since 1.0.2
 * @license EUPL-1.2
 * @copyright (c) 2024 Sebastien GERARD
 * @link https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 */

namespace G2RD;

/**
 * Classe Shortcode
 * 
 * Gère les shortcodes personnalisés pour l'affichage des métadonnées dans les templates.
 */
class Shortcode {
    /**
     * Enregistre les hooks globaux nécessaires (pour les shortcodes)
     *
     * @since 1.0.2
     * @return void
     */
    public function register_hooks(): void {
        add_action('init', [$this, 'registerBindingSources']);
    }

    /**
     * Enregistre les shortcodes pour l'affichage des métadonnées dans les templates
     *
     * @since 1.0.2
     * @return void
     */
    public function registerBindingSources(): void {
        // Portfolio
        add_shortcode('portfolio_link', [$this, 'portfolioLinkShortcode']);
        add_shortcode('portfolio_perf', [$this, 'portfolioPerfShortcode']);
        add_shortcode('portfolio_a11y', [$this, 'portfolioA11yShortcode']);
        add_shortcode('portfolio_bp', [$this, 'portfolioBpShortcode']);
        add_shortcode('portfolio_seo', [$this, 'portfolioSeoShortcode']);
        // Qui sommes-nous
        add_shortcode('experience_dev', [$this, 'experienceDevShortcode']);
        add_shortcode('soft_skills', [$this, 'softSkillsShortcode']);
        add_shortcode('methodologie', [$this, 'methodologieShortcode']);
        add_shortcode('objectif', [$this, 'objectifShortcode']);
        add_shortcode('icones_images', [$this, 'iconesImagesShortcode']);

        // Block binding : URL du projet portfolio (bouton « Visiter le site » dans
        // single-portfolio.html → attribut url lié à la source g2rd/portfolio-link).
        if (function_exists('register_block_bindings_source')) {
            register_block_bindings_source(
                'g2rd/portfolio-link',
                [
                    'label'              => __('Lien du projet (portfolio)', 'g2rd'),
                    'get_value_callback' => [$this, 'getPortfolioLinkValue'],
                    'uses_context'       => ['postId'],
                ]
            );

            // Block binding : champs texte du membre « Qui sommes-nous »
            // (single-qui-sommes-nous.html → paragraphes natifs liés à la source
            // g2rd/member-meta, args.key = experience_dev|soft_skills|methodologie|objectif).
            register_block_bindings_source(
                'g2rd/member-meta',
                [
                    'label'              => __('Champ membre (Qui sommes-nous)', 'g2rd'),
                    'get_value_callback' => [$this, 'getMemberMetaValue'],
                    'uses_context'       => ['postId'],
                ]
            );
        }
    }

    /**
     * Valeur du block binding g2rd/portfolio-link : l'URL du projet du portfolio courant.
     *
     * Lue depuis le meta `_portfolio_link` du post (contexte postId du bloc, avec repli
     * sur le post courant). Renvoie null si aucune URL n'est définie → le bouton conserve
     * son attribut d'origine plutôt que d'afficher un lien vide.
     *
     * @since 1.24.5
     * @param array         $source_args    Arguments du binding (inutilisés).
     * @param \WP_Block|null $block_instance Instance du bloc (fournit le contexte postId).
     * @return string|null
     */
    public function getPortfolioLinkValue($source_args, $block_instance = null) {
        $post_id = 0;
        if (is_object($block_instance) && isset($block_instance->context['postId'])) {
            $post_id = (int) $block_instance->context['postId'];
        }
        if (!$post_id) {
            $post_id = (int) get_the_ID();
        }
        if (!$post_id) {
            return null;
        }

        $link = get_post_meta($post_id, '_portfolio_link', true);
        if (empty($link)) {
            return null;
        }

        return esc_url($link);
    }

    /**
     * Valeur du block binding g2rd/member-meta : un champ texte du membre courant.
     *
     * Le paramètre args.key (sans le préfixe underscore) est restreint à une liste
     * blanche puis lu depuis la méta protégée `_{key}` du post (contexte postId, avec
     * repli sur le post courant). Renvoie null si le champ est vide ou la clé inconnue
     * → le paragraphe conserve son contenu d'origine plutôt qu'un vide.
     *
     * @since 1.25.2
     * @param array          $source_args    Arguments du binding (clé du champ).
     * @param \WP_Block|null $block_instance Instance du bloc (fournit le contexte postId).
     * @return string|null
     */
    public function getMemberMetaValue($source_args, $block_instance = null) {
        if (empty($source_args['key'])) {
            return null;
        }
        $allowed = ['experience_dev', 'soft_skills', 'methodologie', 'objectif'];
        $key     = \sanitize_key($source_args['key']);
        if (!\in_array($key, $allowed, true)) {
            return null;
        }
        $post_id = 0;
        if (is_object($block_instance) && isset($block_instance->context['postId'])) {
            $post_id = (int) $block_instance->context['postId'];
        }
        if (!$post_id) {
            $post_id = (int) get_the_ID();
        }
        if (!$post_id) {
            return null;
        }
        $value = get_post_meta($post_id, '_' . $key, true);
        if ($value === '' || $value === false) {
            return null;
        }
        return wp_kses_post($value);
    }

    // === Shortcodes Portfolio ===
    /**
     * Affiche le lien du portfolio
     *
     * @since 1.0.2
     * @return string
     */
    public function portfolioLinkShortcode(): string {
        if (!is_singular('portfolio')) {
            return '';
        }
        $post_id = get_the_ID();
        $link    = get_post_meta($post_id, '_portfolio_link', true);
        if (empty($link)) {
            return '#';
        }
        return esc_url($link);
    }

    /**
     * Affiche le score de performance du portfolio
     *
     * @since 1.0.2
     * @return string
     */
    public function portfolioPerfShortcode(): string {
        if (!is_singular('portfolio')) {
            return '';
        }
        $post_id = get_the_ID();
        $value   = get_post_meta($post_id, '_portfolio_perf', true);
        if ($value === '' || $value === false) {
            return '—';
        }
        return esc_html($value) . ' / 100';
    }

    /**
     * Affiche le score d'accessibilité du portfolio
     *
     * @since 1.0.2
     * @return string
     */
    public function portfolioA11yShortcode(): string {
        if (!is_singular('portfolio')) {
            return '';
        }
        $post_id = get_the_ID();
        $value   = get_post_meta($post_id, '_portfolio_a11y', true);
        if ($value === '' || $value === false) {
            return '—';
        }
        return esc_html($value) . ' / 100';
    }

    /**
     * Affiche le score de bonnes pratiques du portfolio
     *
     * @since 1.0.2
     * @return string
     */
    public function portfolioBpShortcode(): string {
        if (!is_singular('portfolio')) {
            return '';
        }
        $post_id = get_the_ID();
        $value   = get_post_meta($post_id, '_portfolio_bp', true);
        if ($value === '' || $value === false) {
            return '—';
        }
        return esc_html($value) . ' / 100';
    }

    /**
     * Affiche le score SEO du portfolio
     *
     * @since 1.0.2
     * @return string
     */
    public function portfolioSeoShortcode(): string {
        if (!is_singular('portfolio')) {
            return '';
        }
        $post_id = get_the_ID();
        $value   = get_post_meta($post_id, '_portfolio_seo', true);
        if ($value === '' || $value === false) {
            return '—';
        }
        return esc_html($value) . ' / 100';
    }

    // === Shortcodes Qui sommes-nous ===
    /**
     * Affiche l'expérience en développement
     *
     * @since 1.0.2
     * @return string
     */
    public function experienceDevShortcode(): string {
        if (!is_singular('qui-sommes-nous')) {
            return '';
        }
        $post_id    = get_the_ID();
        $experience = get_post_meta($post_id, '_experience_dev', true);
        if (empty($experience)) {
            return 'Aucune expérience en développement spécifiée';
        }
        return wp_kses_post($experience);
    }

    /**
     * Affiche les soft skills
     *
     * @since 1.0.2
     * @return string
     */
    public function softSkillsShortcode(): string {
        if (!is_singular('qui-sommes-nous')) {
            return '';
        }
        $post_id     = get_the_ID();
        $soft_skills = get_post_meta($post_id, '_soft_skills', true);
        if (empty($soft_skills)) {
            return 'Aucun soft skill spécifié';
        }
        return wp_kses_post($soft_skills);
    }

    /**
     * Affiche la méthodologie
     *
     * @since 1.0.2
     * @return string
     */
    public function methodologieShortcode(): string {
        if (!is_singular('qui-sommes-nous')) {
            return '';
        }
        $post_id      = get_the_ID();
        $methodologie = get_post_meta($post_id, '_methodologie', true);
        if (empty($methodologie)) {
            return 'Aucune méthodologie spécifiée';
        }
        return wp_kses_post($methodologie);
    }

    /**
     * Affiche l'objectif
     *
     * @since 1.0.2
     * @return string
     */
    public function objectifShortcode(): string {
        if (!is_singular('qui-sommes-nous')) {
            return '';
        }
        $post_id  = get_the_ID();
        $objectif = get_post_meta($post_id, '_objectif', true);
        if (empty($objectif)) {
            return 'Aucun objectif spécifié';
        }
        return wp_kses_post($objectif);
    }

    /**
     * Affiche les icônes
     *
     * @since 1.0.2
     * @return string
     */
    public function iconesImagesShortcode(): string {
        if (!is_singular('qui-sommes-nous')) {
            return '';
        }
        $post_id = get_the_ID();
        $images  = get_post_meta($post_id, '_icones_images', true);
        if (empty($images) || !is_array($images)) {
            return 'Aucune icône spécifiée';
        }
        $output = '<div class="g2rd-member-icones" style="display:flex;flex-wrap:wrap;gap:var(--wp--preset--spacing--s, 1rem);justify-content:center;align-items:center">';
        foreach ($images as $image) {
            if (!empty($image)) {
                $output .= '<img src="' . esc_url($image) . '" alt="" width="48" height="48" loading="lazy" class="g2rd-member-icone" style="height:48px;width:auto" />';
            }
        }
        $output .= '</div>';
        return $output;
    }

}
