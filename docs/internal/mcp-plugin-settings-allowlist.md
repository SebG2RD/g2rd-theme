# MCP — Allowlist des réglages de plugins

Décision d'architecture et guide d'extension pour `classes/class-mcp-plugin-settings.php`.

> La documentation utilisateur des trois outils vit dans `docs/mcp-server.md` (non versionné, cf. `.gitignore`). Ce fichier-ci ne couvre que la conception et la procédure d'ajout.

## Pourquoi une allowlist et pas `update_option()` à distance

L'outil `g2rd/update-option` existant est limité à neuf options WordPress de base. Étendre le MCP aux réglages de plugins posait un choix :

| Option | Verdict |
|--------|---------|
| Écriture libre sur toute option de plugin | **Rejetée.** Transforme le MCP en `update_option()` distant : clés de licence, endpoints de paiement, comptes SMTP, règles de pare-feu deviennent modifiables par un agent porteur d'un token `editor`. La confirmation e-mail resterait le seul rempart. |
| Découverte dynamique via l'API Settings + denylist | **Rejetée.** *Fail-open* : un réglage sensible non prévu par la denylist passe. Beaucoup de plugins n'appellent pas `register_setting()`, la couverture serait imprévisible. |
| **Registre déclaratif en dur** | **Retenue.** *Fail-closed* : ce qui n'est pas déclaré n'existe pas. Le moteur reste générique, seules les données changent. |

## Modèles de stockage

Les plugins ne stockent pas leurs réglages de la même façon. Le moteur en supporte trois :

| Modèle | Constante | Exemple |
|--------|-----------|---------|
| Option scalaire | `STORAGE_SCALAR` | WooCommerce — `woocommerce_enable_reviews` |
| Tableau PHP sérialisé | `STORAGE_ARRAY` | SEOPress, Yoast |
| Chaîne JSON | `STORAGE_JSON` | All in One SEO (`json_decode`) |

**Les booléens sont le principal piège** — chaque plugin fait autrement, et deviner corrompt silencieusement la configuration du client :

| `bool_format` | Stockage « activé » | Stockage « désactivé » | Plugin |
|---------------|---------------------|------------------------|--------|
| `one_or_unset` | `'1'` | **clé supprimée** | SEOPress |
| `native` | `true` | `false` | Yoast |
| `yes_no` | `'yes'` | `'no'` | WooCommerce |

Le cas SEOPress est le plus subtil : écrire `''` ne désactive **pas** le réglage, il faut retirer la clé (`uproot()`).

## Le piège des extensions payantes (`requires`)

Un réglage peut vivre dans une option détenue par une **extension payante** du plugin. Écrire cette option sur un site qui n'a que la version gratuite **réussit en base** — et le plugin ignore la valeur. L'agent reçoit un succès, le client ne voit aucun effet.

Cas concret : le sitemap Google News de SEOPress vit dans `seopress_pro_option_name`, mais **le plugin gratuit définit aussi `SEOPRESS_VERSION`**. Seul `SEOPRESS_PRO_VERSION` prouve le palier — c'est d'ailleurs la sonde que SEOPress utilise lui-même (`'HAS_PRO' => defined('SEOPRESS_PRO_VERSION')`).

D'où le champ `requires`, évalué en plus de `detect` :

```php
'requires' => [ 'constant' => 'SEOPRESS_PRO_VERSION' ],
```

**Règle** : dès qu'une option porte un nom évoquant un palier (`_pro_`, `_premium_`, `_addon_`), vérifier quelle constante prouve réellement ce palier. `describe()` expose le résultat via le drapeau `available`.

## Plugins volontairement exclus

| Plugin | Motif |
|--------|-------|
| W3 Total Cache | Configuration en **fichier**, hors `wp_options` — incompatible avec un moteur d'options |
| LiteSpeed Cache | Écrit via `self::update_option()` ; le nom réel en base n'est pas confirmable dans la source |
| SureCart / FluentCart | Pilotés par API, adjacents aux moyens de paiement |

Ces exclusions sont des décisions, pas des oublis. Ne pas les lever sans avoir sourcé les clés réelles.

## Procédure d'ajout d'un réglage

Tout se passe dans la constante `REGISTRY`. **Aucun autre fichier à modifier** : les énums des schémas d'outils sont dérivées de `plugin_slugs()` et `setting_slugs()`.

### 1. Sourcer la clé réelle — jamais la deviner

```bash
curl -sL -o plugin.zip "https://downloads.wordpress.org/plugin/<slug>.zip"
unzip -q plugin.zip -d src && grep -rn "<sous_cle>" src --include="*.php"
```

Une clé fausse écrit une option fantôme : aucune erreur, aucun effet, et un réglage client qu'on croit modifié.

### 2. Identifier le modèle de stockage et le format de valeur

Chercher comment le plugin *lit* le réglage (`get_option`, `json_decode`, comparaison à `'1'`/`'yes'`/`true`).

### 3. Déclarer l'entrée

```php
'mon_plugin' => [
    'label'    => 'Mon Plugin',
    'detect'   => [ 'constant' => 'MON_PLUGIN_VERSION' ], // ou [ 'class' => 'MaClasse' ]
    'settings' => [
        'mon_reglage' => [
            'label'        => 'Description lisible du réglage.',
            'option'       => 'mon_plugin_options',
            'storage'      => self::STORAGE_ARRAY,
            'path'         => [ 'sous_cle' ],        // [] si storage = scalar
            'type'         => 'boolean',             // boolean | text | enum | post_types_map
            'bool_format'  => 'yes_no',              // one_or_unset | native | yes_no
            'side_effects' => [ 'flush_cache' ],     // + 'flush_rewrite' si URL réécrite
            'verify_path'  => '/mon-endpoint.xml',   // optionnel
        ],
    ],
],
```

### 4. Effets de bord

- `flush_cache` — quasi toujours souhaitable.
- `flush_rewrite` — **obligatoire** pour tout réglage exposant une URL réécrite. Sans lui, activer le sitemap Google News de SEOPress laisse `/news.xml` en 404.

### 5. Ajouter un test

Dans `tests/phpunit/security/McpPluginSettingsTest.php`, vérifier au minimum que **les clés sœurs survivent** à l'écriture — c'est la garantie la plus facile à casser lors d'une modification du moteur.

## Garde-fou automatique

`SECRET_PATTERNS` rejette toute entrée dont l'option ou le chemin contient `passw`, `secret`, `token`, `api_key`, `salt`, `licen`, `auth`… Une entrée pointant par erreur vers un secret est refusée à l'exécution, même déclarée dans `REGISTRY`. C'est de la défense en profondeur **par-dessus** l'allowlist, pas un substitut : la revue de l'entrée reste le contrôle principal.
