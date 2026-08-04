# MCP — Produits FluentCart

Décision d'architecture et **protocole de vérification** pour `classes/class-mcp-products.php` (thème 1.28.0).

> Ce document contient les étapes que je n'ai pas pu exécuter : elles demandent un WordPress avec FluentCart actif et un accès MCP au site. Le code a été validé statiquement (lint, PHPCS WordPress et Security à 0, 146 tests PHPUnit) mais **la chaîne complète n'a pas été testée contre une base réelle**.

## Pourquoi cette classe existe

`g2rd_create-post` n'appelle que `wp_insert_post()`. Un produit FluentCart a besoin de trois choses pour être vendable, et les deux dernières vivent dans les tables du plugin :

| # | Élément | Table |
|---|---------|-------|
| 1 | Le post `fluent-products` | `wp_posts` |
| 2 | Une ligne de détail produit | `fct_product_details` |
| 3 | Au moins une variation **avec prix** | `fct_product_variations` |

Sans 2 et 3, le produit apparaît dans la liste des articles, mais l'écran Pricing ne peut rien enregistrer et le produit n'est pas achetable. C'est le cas du post 1785225.

## Séquence officielle suivie

Relevée dans `FluentCart\App\Http\Controllers\ProductController::create()` (FluentCart 1.6.0), l'implémentation de référence utilisée par l'admin :

```php
1. wp_insert_post([ post_title, post_name, post_status, post_type => 'fluent-products' ])
2. ProductDetail::query()->create([ post_id, fulfillment_type, variation_type, ... ])
3. ProductVariation::query()->create([ post_id, item_price, payment_type, other_info, ... ])
4. product_details.default_variation_id ← id de la variation par défaut
```

Le modèle `Product` porte d'ailleurs cette note : *« This model is intended to be use for relationships and DB query. For insert update we will use WordPress's native functions »* — le post passe donc bien par `wp_insert_post`, seules les lignes `fct_*` passent par les modèles.

## Points de schéma non évidents

| Point | Réalité | Conséquence |
|-------|---------|-------------|
| **Unité de prix** | `item_price` est un `double` contenant des **centimes** (confirmé par `CurrenciesHelper::centsToDecimal()`) | Le contrat d'entrée exige un entier ; une décimale est refusée |
| **Termes d'abonnement** | Pas de colonnes dédiées : ils vivent dans le JSON `other_info` (`repeat_interval`, `times`, `trial_days`) | Mappés dans `build_other_info()` |
| **Renouvellement illimité** | `times` vaut la **chaîne vide**, pas `0` | `cycles = 0` en entrée → `''` en base |
| **`default_variation_id`** | Sans lui, l'écran Pricing n'a aucune ligne à lier | Renseigné systématiquement |

## Protocole de vérification

### Prérequis

- FluentCart 1.6.0 actif
- Un token MCP de scope `editor`
- Accès à l'admin et à la base

### 1. Créer le produit via MCP uniquement

```json
{
  "tool": "g2rd_create-product",
  "arguments": {
    "title": "TEST MCP",
    "status": "publish",
    "fulfillment_type": "service",
    "variations": [
      {
        "payment_type": "subscription",
        "price": 20000,
        "billing_interval": "month",
        "billing_interval_count": 1,
        "is_default": true
      }
    ]
  }
}
```

⚠️ `price` vaut **20000**, pas `200`. C'est 20 000 centimes.

**Attendu** : l'outil renvoie un accusé « en attente de confirmation », puis un e-mail arrive. Il doit contenir :

```
• Titre : TEST MCP
• Statut : publish
• Type de produit : service

TARIFS qui seront enregistrés :
   • 200,00 € par month (renouvellement illimité)  [par défaut]
```

**Si le tarif n'apparaît pas dans l'e-mail, arrête-toi là** : c'est le signe que la charge utile n'a pas été validée comme prévu.

Clique le lien de confirmation.

### 2. Vérifier l'écran Pricing

**FluentCart → Products → TEST MCP → onglet Pricing**

| À vérifier | Attendu |
|------------|---------|
| L'onglet s'affiche | sans erreur JS ni écran vide |
| Le tarif est présent | 200,00 € / mois |
| Le tarif est modifiable | change le prix, enregistre |
| L'enregistrement fonctionne | pas de « Product variation not found » |

C'est le test le plus discriminant : c'est précisément ce qui échouait avec un produit créé par `create-post`.

### 3. Vérifier le front

Ouvre `/item/test-mcp/`.

| À vérifier | Attendu |
|------------|---------|
| Le prix affiché | « 200.00€ par mois » |
| Le bouton Acheter | présent et cliquable |
| Le tunnel | s'ouvre sur le checkout |

### 4. Comparer en base avec le produit de référence

Remplace `PREFIX` par le préfixe de tes tables (souvent `wp_`), et `<ID_TEST>` par l'ID renvoyé à l'étape 1.

```sql
-- Détail produit : TEST MCP vs « Publication réseaux sociaux » (1785277)
SELECT post_id, fulfillment_type, variation_type, default_variation_id,
       manage_stock, stock_availability
FROM   PREFIXfct_product_details
WHERE  post_id IN (<ID_TEST>, 1785277);

-- Variations
SELECT post_id, serial_index, variation_title, payment_type,
       item_price, compare_price, stock_status, item_status, other_info
FROM   PREFIXfct_product_variations
WHERE  post_id IN (<ID_TEST>, 1785277);
```

**Attendu** : mêmes colonnes renseignées sur les deux lignes. En particulier `default_variation_id` non nul, `item_price` = 20000, `payment_type` = `subscription`, et `other_info` contenant `repeat_interval: "month"` avec `times: ""`.

**Si une colonne obligatoire est vide côté TEST MCP alors qu'elle est remplie côté 1785277, note laquelle** — c'est ce qu'il faudra corriger.

### 5. Non-régression des outils existants

| Outil | Appel | Attendu |
|-------|-------|---------|
| `g2rd_create-post` | un article normal, `post_type: "post"` | créé comme avant |
| `g2rd_create-post` | `post_type: "fc_product"` | **refusé** — « Unknown post type », **aucun post créé** |
| `g2rd_create-post` | `post_type: "fluent-products"` | **refusé** — renvoie vers `g2rd_create-product` |
| `g2rd_list-posts` | `post_type: "nonexistent"` | erreur listant les types valides |
| `g2rd_list-post-types` | — | liste incluant `fluent-products` |
| `g2rd_upload-media` | une image | inchangé |
| `g2rd_get-seo-data` | un article | inchangé |

Les deux refus de `create-post` sont le cœur du correctif : avant, le premier créait un post orphelin.

### 6. Nettoyage

```json
{ "tool": "g2rd_delete-product", "arguments": { "product_id": <ID_TEST> } }
```

Le produit part à la corbeille. La suppression définitive n'est jamais exposée.

## Ce qui reste non vérifié

- Le comportement de `ProductVariation::query()->create()` face à une colonne inattendue
- Le rendu exact du prix sur le front, qui dépend du thème FluentCart et des réglages de devise
- Le cas multi-variations (plusieurs tarifs sur un même produit)

Ces trois points passeront à l'étape 2 du protocole. Remonte-moi les écarts constatés.

## Ajouter un champ au mapping

Tout se passe dans `classes/class-mcp-products.php` :

1. **Sourcer la colonne** dans `database/Migrations/ProductVariationMigrator.php` ou `ProductDetailsMigrator.php` — ne jamais la deviner, une colonne inexistante est ignorée silencieusement par l'ORM.
2. Ajouter la validation dans `validate_variation()` avec les valeurs acceptées dans le message d'erreur.
3. Mapper dans `write_product_rows()` ou `build_other_info()` selon que la donnée est une colonne ou une clé JSON.
4. Ajouter un test dans `tests/phpunit/security/McpProductsTest.php`.
