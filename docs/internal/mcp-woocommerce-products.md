# MCP — Produits WooCommerce

Décision d'architecture et **protocole de vérification** pour `classes/class-mcp-woo-products.php` (thème 1.29.0).

> Comme pour FluentCart, les étapes de vérification demandent un WordPress avec WooCommerce actif et un accès MCP : elles n'ont **pas** été exécutées. Le code est validé statiquement (lint, PHPCS WordPress et Security à 0, 165 tests PHPUnit).

## Le piège central : deux contrats de prix opposés

C'est le point qui mérite le plus d'attention, parce qu'il ne produit aucune erreur technique.

| Outil | Plateforme | Champ | Unité | 200 € s'écrit |
|-------|-----------|-------|-------|---------------|
| `g2rd_create-product` | FluentCart | `price` | **centimes entiers** | `20000` |
| `g2rd_create-woo-product` | WooCommerce | `regular_price` | **décimales** | `"200.00"` |

Source : `WC_Product::set_regular_price( string $price )` prend un montant décimal ; `CurrenciesHelper::centsToDecimal()` confirme que FluentCart stocke des centimes.

Un agent qui reporte l'habitude des centimes sur WooCommerce crée un produit à **20 000 €**, et rien ne le signale : `20000` est un montant parfaitement valide.

### Trois garde-fous

1. **Le schéma le dit** — la description de l'outil nomme explicitement le contraste avec `g2rd_create-product`.
2. **La validation refuse les montants mal formés** — `"19.999"`, `"1 000"`, `"gratuit"`, un tableau, un nombre négatif.
3. **L'e-mail de confirmation affiche le prix formaté** — c'est la vraie protection. Un administrateur qui lit « 20 000,00 € » pour un produit à 200 € refuse l'opération.

La virgule décimale française est acceptée (`"19,99"` → `19.99`) : un agent reprend souvent le prix tel qu'il s'affiche sur le site.

## Approche

Tout passe par les classes CRUD officielles, jamais par `update_post_meta` :

```php
$product = new WC_Product_Simple();   // ou WC_Product_Variable
$product->set_name( … );
$product->set_regular_price( '200.00' );
…
$id = $product->save();
```

Les setters garantissent que WooCommerce met à jour ses **tables de recherche** (`wc_product_meta_lookup`), déclenche ses hooks et reste compatible avec ses migrations. Écrire les métadonnées à la main laisserait la boutique dans un état incohérent : le produit apparaîtrait en base mais pas dans les filtres de prix ni le tri catalogue.

Suppression : `$product->delete( false )` — corbeille, jamais de suppression définitive.

## Protocole de vérification

### Prérequis

- WooCommerce actif
- Un token MCP de scope `editor`
- Un utilisateur disposant de `edit_products`

### 1. Créer le produit

```json
{
  "tool": "g2rd_create-woo-product",
  "arguments": {
    "name": "TEST MCP WOO",
    "status": "publish",
    "type": "simple",
    "regular_price": "200.00",
    "sale_price": "149.90",
    "sku": "TEST-MCP-WOO",
    "short_description": "Produit de test créé via MCP.",
    "manage_stock": true,
    "stock_quantity": 5
  }
}
```

**L'e-mail de confirmation doit contenir :**

```
• Nom : TEST MCP WOO
• Type : simple — Statut : publish

PRIX qui sera enregistré :
   • Prix normal : 200,00 €
   • Prix promo  : 149,90 €
• UGS : TEST-MCP-WOO
```

**Si le prix affiché n'est pas celui attendu, refuse l'opération** — c'est exactement le rôle de cet e-mail.

### 2. Vérifier la fiche produit en administration

**Produits → TEST MCP WOO**

| À vérifier | Attendu |
|------------|---------|
| Onglet Général | Prix régulier 200,00 — Prix promo 149,90 |
| Onglet Inventaire | UGS renseignée, gestion de stock active, quantité 5 |
| Le produit s'enregistre | modifier le prix, Mettre à jour, sans erreur |

### 3. Vérifier le front

Ouvre la fiche produit publique.

| À vérifier | Attendu |
|------------|---------|
| Prix affiché | 200,00 € barré, 149,90 € en promo |
| Bouton | « Ajouter au panier » fonctionnel |
| Panier | le produit s'ajoute au bon prix |

C'est le test le plus discriminant : il valide que WooCommerce considère le produit comme **achetable**, ce que `is_purchasable()` reflète dans `g2rd_get-woo-product`.

### 4. Vérifier la lecture

```json
{ "tool": "g2rd_get-woo-product", "arguments": { "product_id": <ID> } }
```

Contrôle que `purchasable` vaut `true`, `on_sale` vaut `true`, et que `price_formatted` affiche bien `149,90 €`.

### 5. Mise à jour partielle

```json
{
  "tool": "g2rd_update-woo-product",
  "arguments": { "product_id": <ID>, "regular_price": "180.00" }
}
```

**Attendu** : le prix change, mais l'UGS, la description courte et le stock **restent inchangés**. C'est la garantie de mise à jour partielle — un champ omis n'est jamais réinitialisé.

### 6. Refus attendus

| Appel | Attendu |
|-------|---------|
| `regular_price: 20000` | **accepté** mais l'e-mail affiche « 20 000,00 € » — c'est à toi de refuser |
| `regular_price: "19.999"` | refusé, message mentionnant « decimal amount » |
| `sale_price` ≥ `regular_price` | refusé, message « must be lower » |
| `type: "abonnement"` | refusé, liste des types acceptés |
| création sans `regular_price` | refusé, « regular_price is required » |

### 7. Nettoyage

```json
{ "tool": "g2rd_delete-woo-product", "arguments": { "product_id": <ID> } }
```

Corbeille, jamais de suppression définitive.

## Ce qui n'est pas couvert

- **Les produits variables avec variations** : `type: "variable"` crée le produit parent, mais les variations elles-mêmes (attributs, combinaisons, prix par variation) ne sont pas encore pilotables. À ajouter si le besoin se présente.
- Les produits **groupés** et **externes** sont acceptés en type mais leurs champs spécifiques (produits liés, URL externe) ne sont pas exposés.
- Les téléchargements associés à un produit téléchargeable.

## Ajouter un champ

1. Vérifier le setter dans `includes/abstracts/abstract-wc-product.php` — ne jamais deviner un nom de setter, un appel inexistant est silencieusement ignoré par `method_exists()`.
2. Valider dans `validate()` avec les valeurs acceptées dans le message d'erreur.
3. Ajouter au tableau `$simple_setters` de `apply_fields()`, ou traiter à part si la logique le demande.
4. Exposer dans le schéma de l'outil.
5. Ajouter un test dans `tests/phpunit/security/McpWooProductsTest.php`.
