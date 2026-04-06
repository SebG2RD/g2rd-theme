<?php
/**
 * Menu conditionnel — affichage selon l'état de connexion ou le rôle
 *
 * Filtre le rendu des blocs core/navigation-link et core/navigation-submenu
 * pour masquer des éléments selon des classes CSS ajoutées dans l'éditeur FSE.
 *
 * Classes CSS disponibles à ajouter sur un lien de navigation :
 *  - menu-if-logged-in    → visible uniquement si l'utilisateur est connecté
 *  - menu-if-logged-out   → visible uniquement si l'utilisateur n'est PAS connecté
 *  - menu-if-role-{slug}  → visible uniquement pour un rôle donné
 *                           ex. menu-if-role-administrator, menu-if-role-shop_manager
 *
 * @package G2RD
 * @since   1.2.2
 */

namespace G2RD;

/**
 * Gestion de l'affichage conditionnel des éléments de navigation FSE.
 */
class ConditionalMenu
{
    /**
     * Préfixe des classes de rôle.
     *
     * @since 1.2.2
     * @var string
     */
    private const ROLE_PREFIX = 'menu-if-role-';

    /**
     * Blocs de navigation pris en charge.
     *
     * @since 1.2.2
     * @var string[]
     */
    private const SUPPORTED_BLOCKS = [
        'core/navigation-link',
        'core/navigation-submenu',
    ];

    /**
     * Enregistre les hooks WordPress.
     *
     * @since 1.2.2
     * @return void
     */
    public function registerHooks(): void
    {
        \add_filter('render_block', [$this, 'filterConditionalBlock'], 10, 2);
    }

    /**
     * Filtre le rendu d'un bloc de navigation selon les classes conditionnelles.
     *
     * @since 1.2.2
     * @param string               $block_content Contenu HTML rendu.
     * @param array<string, mixed> $block         Données du bloc.
     * @return string HTML filtré (vide si la condition n'est pas remplie).
     */
    public function filterConditionalBlock( string $block_content, array $block ): string
    {
        if ( ! \in_array( $block['blockName'], self::SUPPORTED_BLOCKS, true ) ) {
            return $block_content;
        }

        $classes = $block['attrs']['className'] ?? '';

        if ( empty( $classes ) ) {
            return $block_content;
        }

        $class_list = \explode( ' ', $classes );

        // Condition : connecté uniquement
        if ( \in_array( 'menu-if-logged-in', $class_list, true ) && ! \is_user_logged_in() ) {
            return '';
        }

        // Condition : non connecté uniquement
        if ( \in_array( 'menu-if-logged-out', $class_list, true ) && \is_user_logged_in() ) {
            return '';
        }

        // Condition : rôle spécifique
        foreach ( $class_list as $class ) {
            if ( \str_starts_with( $class, self::ROLE_PREFIX ) ) {
                $required_role = \substr( $class, \strlen( self::ROLE_PREFIX ) );
                if ( ! $this->currentUserHasRole( $required_role ) ) {
                    return '';
                }
                break;
            }
        }

        return $block_content;
    }

    /**
     * Vérifie si l'utilisateur courant possède un rôle donné.
     *
     * @since 1.2.2
     * @param string $role Slug du rôle WordPress.
     * @return bool
     */
    private function currentUserHasRole( string $role ): bool
    {
        if ( ! \is_user_logged_in() ) {
            return false;
        }

        $user = \wp_get_current_user();
        return \in_array( $role, (array) $user->roles, true );
    }
}
