# Console G2RD — menu unique et charte commune pour les extensions

Depuis la 1.41.0, le thème crée un **menu de premier niveau « G2RD »** dans l'administration
WordPress (slug `g2rd`, position 61, juste sous Apparence). Toutes les interfaces G2RD s'y
rangent en sous-menus : le thème (première entrée, « Thème »), l'assistant de démarrage, puis
les extensions — connecteur, facturation et les plugins à venir.

L'ancienne adresse `themes.php?page=g2rd-options` est redirigée (301) vers `admin.php?page=g2rd`.

## 1. Rattacher une page d'extension au menu « G2RD »

Écouter l'action `g2rd_console_menu`, déclenchée dès que le menu parent existe (priorité 9 de
`admin_menu`). Elle reçoit le slug du parent, aussi disponible via `\G2RD\AdminConsole::MENU_SLUG`.

```php
add_action( 'g2rd_console_menu', function ( string $parent ): void {
    add_submenu_page(
        $parent,                                   // 'g2rd'
        __( 'Console G2RD — Facturation', 'g2rd-facturation' ),
        __( 'Facturation', 'g2rd-facturation' ),   // libellé du sous-menu
        'manage_options',
        'g2rd-facturation',                        // slug : préfixe g2rd- obligatoire
        'g2rd_facturation_render_page'
    );
} );
```

Si le thème G2RD n'est pas actif, l'action n'est jamais déclenchée : prévoir un repli
(`add_menu_page` propre à l'extension) dans ce cas.

```php
add_action( 'admin_menu', function (): void {
    if ( ! class_exists( '\G2RD\AdminConsole' ) ) {
        add_menu_page( 'Facturation', 'Facturation', 'manage_options', 'g2rd-facturation', 'g2rd_facturation_render_page' );
    }
}, 20 );
```

## 2. Feuille de style et polices : automatiques

Sur toute page dont le hook commence par `g2rd_page_` (sous-menu du menu « G2RD »), le thème
charge :

- `g2rd-admin` — habillage global de wp-admin, polices **Instrument Sans** (texte) et
  **IBM Plex Mono** (valeurs), self-hostées dans `assets/fonts/` ;
- `g2rd-console` — la feuille de la console (bandeau, cartes, lignes de réglages, champs,
  boutons, tableaux), compilée depuis `blocks/g2rd-options-page/src/style.css`.

Une page hors du menu peut la charger elle-même : `\G2RD\AdminConsole::enqueueStyle();`.

## 3. Dessiner la page avec la charte

`AdminConsole::open()` rend le bandeau sombre, la barre (monogramme, « Console G2RD », version
du thème), le titre de page et jusqu'à trois tuiles de verre ; `AdminConsole::close()` ferme.
Entre les deux, du HTML qui réutilise les classes de la console.

```php
function g2rd_facturation_render_page(): void {
    \G2RD\AdminConsole::open( [
        'eyebrow'     => __( 'Facturation', 'g2rd-facturation' ),
        'title'       => __( 'Documents', 'g2rd-facturation' ),
        'description' => __( 'Devis, factures et acomptes synchronisés avec Abby.', 'g2rd-facturation' ),
        'chips'       => [ [ 'label' => __( 'Abby connecté', 'g2rd-facturation' ), 'tone' => 'ok' ] ],
        'tiles'       => [
            [ 'label' => __( 'À finaliser', 'g2rd-facturation' ), 'value' => 4, 'hint' => __( 'brouillons chez Abby', 'g2rd-facturation' ) ],
            [ 'label' => __( 'En attente de paiement', 'g2rd-facturation' ), 'value' => 1, 'hint' => __( 'document finalisé', 'g2rd-facturation' ) ],
            [ 'label' => __( 'Payés', 'g2rd-facturation' ), 'value' => 2, 'hint' => __( 'ce trimestre', 'g2rd-facturation' ) ],
        ],
    ] );
    ?>
    <section class="g2rd-section">
        <h3 class="g2rd-section__title"><?php esc_html_e( 'Créer un document', 'g2rd-facturation' ); ?></h3>
        <p class="g2rd-section__desc"><?php esc_html_e( 'Le document est créé en brouillon chez Abby, qui le numérote à la finalisation.', 'g2rd-facturation' ); ?></p>
        <!-- formulaire : champs en creux (.g2rd-field__label + .g2rd-field__input), boutons WP .button-primary -->
    </section>
    <?php
    \G2RD\AdminConsole::close();
}
```

### Classes utiles

| Besoin | Classes |
| --- | --- |
| Carte blanche | `.g2rd-section`, `.g2rd-section__title` (h3), `.g2rd-section__desc`, `.g2rd-section__head` (titre + actions) |
| Lignes de réglages (libellé + description + contrôle) | `.g2rd-setting-list` > `.g2rd-setting-row[.is-on]` > `.g2rd-setting-row__icon`, `__main` (`__title`, `__desc`), `__control` |
| Cuvette de formulaire | `.g2rd-panel`, `.g2rd-panel__title`, `.g2rd-form-grid` |
| Champ brut | `.g2rd-field__label` + `.g2rd-field__input` + `.g2rd-field__hint` |
| Tableau | `<table class="widefat striped g2rd-table">`, `.is-date`, `.is-mono`, `code.g2rd-code-inline` (puce lime) |
| Étiquettes d'état | `.g2rd-tag.g2rd-tag--success|warning|danger|info|neutral` (point coloré automatique) |
| Encarts | `.g2rd-alert.g2rd-alert--success|warning|danger|info`, `.g2rd-empty` |
| Texte secondaire, rangées | `.g2rd-muted`, `.g2rd-row`, `.g2rd-stack` |

Les composants `@wordpress/components` (`Button`, `TextControl`, `SelectControl`,
`FormToggle`…) sont repeints automatiquement à l'intérieur de `.g2rd-app` : une extension React
peut les utiliser tels quels, sans CSS supplémentaire.

## 4. Onglet dans la console du thème (alternative)

Pour une interface légère, une extension peut aussi apparaître comme **onglet** de la page
« Thème » plutôt que comme sous-menu : filtre PHP `g2rd_options_external_tabs` + renderer JS
`window.G2RDOptions.registerTab({ key, mount })` (cf. `docs/plugin-wordpress.md`). Le sous-menu
reste la voie recommandée dès que l'extension a plusieurs écrans.

## 5. Règles

- Slug de page préfixé `g2rd-`, capacité `manage_options` sauf besoin justifié.
- Ne jamais redéfinir les tokens `--g2rd-*` : la charte est celle du thème (direction « Verre »,
  cf. `CHANGELOG_DESIGN.md`). Une couleur propre à l'extension reste une couleur de **statut**.
- Pas d'emoji dans les titres de menu ni dans l'interface : icônes SVG linéaires.
- Texte blanc uniquement sur le bandeau ; lime en aplat avec texte encre, jamais en texte sur
  fond clair.
