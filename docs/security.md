# Guide de sécurité

## Bonnes pratiques

- Gardez WordPress, le thème et les plugins à jour
- Utilisez des mots de passe forts pour tous les comptes
- Limitez les accès administrateur (rôles WordPress)
- Désactivez l'édition de fichiers dans l'admin (`define('DISALLOW_FILE_EDIT', true);`)
- Vérifiez les permissions des fichiers (644 pour les fichiers, 755 pour les dossiers)
- Sauvegardez régulièrement votre site

## Sécurité du code

- Échappez toutes les sorties (`esc_html`, `esc_attr`, etc.)
- Vérifiez les nonces pour les actions sensibles (`wp_nonce_field`, `check_admin_referer`)
- Utilisez les capacités WordPress (`current_user_can`) pour restreindre l'accès aux actions sensibles
- Ne stockez jamais de mots de passe en clair dans la base de données

## Exemples dans le thème

- Utilisation de `wp_nonce_field()` dans les formulaires d'admin
- Vérification des capacités avec `current_user_can('edit_posts')`
- Échappement des champs avec `esc_html()` dans les templates

## Plugins recommandés

- Wordfence Security
- Sucuri Security
- iThemes Security

## Ressources

- [Sécurité WordPress](https://wordpress.org/support/article/hardening-wordpress/)
- [Best Practices for WordPress Development](https://developer.wordpress.org/plugins/security/)
