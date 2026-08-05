# MCP — Variations WooCommerce

Décision d'architecture et **protocole de vérification** pour `classes/class-mcp-woo-variations.php`.

> Comme pour les couches produit, WooCommerce n'est pas installé dans l'environnement de test. Le code est validé statiquement (PHPCS, PHPStan, 185 tests PHPUnit) mais **la chaîne complète n'a pas été exécutée contre une boutique réelle**. Les étapes ci-dessous sont à dérouler.

## Le problème d'origine

Sur un produit variable, `g2rd_get-woo-product` renvoie des champs vides :

```json
{"id":1639,"name":"Miel de châtaignier","type":"variable",
 "regular_price":"","sale_price":"","price":"7","stock_quantity":null}
```

Prix, promo et stock vivent sur les **variations**, pas sur le parent. Le champ `price` ne donne que le **plancher de la fourchette**.

Cas réel : une fiche annonçait « pot de 500 g uniquement, 12 € » alors que la boutique affichait « à partir de 7 € » — une variation de 250 g existait encore et restait achetable. Le texte et la boutique se contredisaient, et rien dans l'outillage ne permettait ni de le constater, ni de le corriger.

## Points de conception

### Le stock existe à deux niveaux

`manage_stock` est présent sur le parent **et** sur chaque variation. Un `stock_quantity: null` seul est ambigu : illimité, ou hérité du parent ?

D'où le champ **`stock_source`** :

| Valeur | Signification |
|--------|---------------|
| `variation` | La variation gère son propre stock |
| `parent` | Hérité du parent — `stock_quantity` reflète celui du parent |
| `none` | Aucun stock géré |

### Les prix restent des chaînes

WooCommerce stocke `_price` en chaîne. `"7.10"` et `"7.1"` ne sont pas la même valeur à l'écriture, et `0.1 + 0.2` ne vaut pas `0.3`. Aucune conversion en `float` n'est faite pour le stockage : validation par expression régulière, écriture de la chaîne telle quelle.

Le contrat est **strictement celui de `g2rd_update-woo-product`** — même méthode `McpWooProducts::normalize_price()`, rendue publique pour cela plutôt que dupliquée. Une copie aurait fini par diverger.

### Le prix promo est comparé au prix normal *effectif*

Si l'appel ne fournit qu'un `sale_price`, il est comparé au `regular_price` **déjà en base**, pas à une chaîne vide. Sans ça, un promo supérieur au prix existant passerait la validation et WooCommerce l'ignorerait silencieusement.

### Les caches du parent sont invalidés

Toute écriture appelle `wc_delete_product_transients()` sur le parent, puis `WC_Product_Variable::sync()` pour recalculer la fourchette. Le cache page est purgé aussi (LiteSpeed, WP Rocket) : sans ça, la boutique affiche encore l'ancien plancher, ce qui se signale comme un bug alors que ce n'en est pas un.

### Attributs lisibles

`wc_attribute_label()` pour le libellé, résolution du terme pour la valeur. La sortie porte les deux : `slug` (`500-g`) pour réécrire, `value` (`500 g`) pour lire et rédiger.

## Protocole de vérification

### 1. Lister les variations

```json
{ "tool": "g2rd_list-woo-variations", "arguments": { "product_id": 1639 } }
```

| À vérifier | Attendu |
|------------|---------|
| Nombre d'entrées | autant que de formats réels |
| `attributes[].value` | « 500 g », pas « 500-g » |
| `regular_price` | décimal, ex. `"12.00"` |
| `stock_source` | `variation`, `parent` ou `none` — jamais absent |
| `enabled` | `false` sur une variation désactivée |

### 2. Produit simple

```json
{ "tool": "g2rd_list-woo-variations", "arguments": { "product_id": <ID_SIMPLE> } }
```

**Attendu** : une erreur explicite nommant le type réel et renvoyant vers `g2rd_get-woo-product`. **Jamais un tableau vide.**

### 3. Mise à jour partielle

```json
{ "tool": "g2rd_update-woo-variation",
  "arguments": { "variation_id": <ID>, "stock_quantity": 12 } }
```

**Attendu** : le stock change, **prix, SKU et image restent identiques**. Compare la réponse avec l'état relevé à l'étape 1.

### 4. Prix promo invalide

```json
{ "tool": "g2rd_update-woo-variation",
  "arguments": { "variation_id": <ID>, "sale_price": "15.00" } }
```

sur une variation à 12 € → **refusé**, message « must be lower ».

### 5. Suppression et recalcul de la fourchette

Relève d'abord la fourchette affichée en boutique, puis supprime la variation la moins chère :

```json
{ "tool": "g2rd_delete-woo-variation", "arguments": { "variation_id": <ID_250G> } }
```

**Attendu** : après confirmation, la fiche produit n'affiche plus « à partir de 7 € » mais le prix de la variation restante. C'est le test le plus discriminant — il valide l'invalidation des transients.

### 6. Dernière variation

Sur un produit n'ayant plus qu'une variation :

**Attendu** : **refusé**, avec la raison (« would leave a variable product with no purchasable variation ») et la marche à suivre.

### 7. Lot mixte

Un `g2rd_batch` mêlant `update-woo-product` et `update-woo-variation` doit renvoyer **un statut par opération**, pas un succès global — comportement acquis depuis la correction du défaut C.

## Ce qui n'est pas couvert

- Les **attributs locaux** (non globaux, définis produit par produit) : le libellé retombe sur le nom brut faute de taxonomie à interroger.
- La **création d'attributs** : `create-woo-variation` exige que la combinaison corresponde à des attributs déjà déclarés sur le parent, il ne les crée pas.
- Les variations **téléchargeables** et leurs fichiers.
