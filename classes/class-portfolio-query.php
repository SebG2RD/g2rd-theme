<?php

namespace G2RD;

/**
 * Gestion des requêtes personnalisées pour le portfolio
 * 
 * Cette classe gère les requêtes personnalisées pour afficher les projets
 * en fonction du membre de l'équipe.
 *
 * @package G2RD
 * @since 1.0.0
 */
class PortfolioQuery
{
    /**
     * Clé de cache pour les requêtes du portfolio
     */
    private const CACHE_KEY_PREFIX = 'g2rd_portfolio_query_';

    /**
     * Durée de mise en cache en secondes (1 heure)
     */
    private const CACHE_DURATION = 3600;

    /**
     * Enregistre les hooks nécessaires
     *
     * @since 1.0.0
     * @return void
     */
    public function registerHooks(): void
    {
        \add_filter('query_loop_block_query_vars', [$this, 'filterPortfolioQuery'], 10, 2);
        \add_action('save_post_portfolio', [$this, 'clearPortfolioCache']);
        \add_action('edited_qui', [$this, 'clearPortfolioCache']);
    }

    /**
     * Nettoie le cache des requêtes portfolio
     */
    public function clearPortfolioCache(): void
    {
        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $wpdb->esc_like(self::CACHE_KEY_PREFIX) . '%'
            )
        );
    }

    /**
     * Génère une clé de cache unique pour la requête
     */
    private function generateCacheKey(string $member_name): string
    {
        return self::CACHE_KEY_PREFIX . md5($member_name);
    }

    /**
     * Filtre la requête pour afficher les projets par membre
     *
     * @since 1.0.0
     * @param array $query_args Arguments de la requête
     * @param \WP_Block $block Instance du bloc
     * @return array Arguments de la requête modifiés
     */
    public function filterPortfolioQuery($query_args, $block): array
    {
        // Vérifier si nous sommes sur une page single-qui-sommes-nous
        if (!\is_singular('qui-sommes-nous')) {
            return $query_args;
        }

        // Vérifier si c'est bien une requête pour le portfolio
        if ($query_args['post_type'] !== 'portfolio') {
            return $query_args;
        }

        // Récupérer le prénom du membre depuis le titre de la page
        $member_name = \get_the_title();
        
        // Générer la clé de cache
        $cache_key = $this->generateCacheKey($member_name);
        
        // Essayer de récupérer les résultats du cache
        $cached_results = \get_transient($cache_key);
        
        if ($cached_results !== false) {
            return $cached_results;
        }

        // Construire la requête optimisée
        $query_args['tax_query'] = [
            [
                'taxonomy' => 'qui',
                'field'    => 'name',
                'terms'    => $member_name,
            ],
        ];

        // Optimiser la requête
        $query_args['no_found_rows'] = true; // Désactive la pagination si non nécessaire
        $query_args['update_post_meta_cache'] = false; // Désactive la mise en cache des meta si non utilisées
        $query_args['update_post_term_cache'] = true; // Active la mise en cache des termes

        // Mettre en cache les résultats
        \set_transient($cache_key, $query_args, self::CACHE_DURATION);

        // Activer le débogage pour voir la requête SQL
        if (defined('WP_DEBUG') && WP_DEBUG) {
            \add_filter('posts_request', function ($sql) {
                error_log('Portfolio Query SQL: ' . $sql);
                return $sql;
            });
        }

        return $query_args;
    }
}
