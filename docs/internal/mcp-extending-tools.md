# Étendre le serveur MCP — filtre `g2rd_mcp_abilities`

Décision d'architecture et guide d'extension pour `classes/class-mcp-abilities.php`.

> La documentation utilisateur des outils vit dans `docs/mcp-server.md` (non versionné, cf. `.gitignore`). Ce fichier-ci ne couvre que la conception et la procédure d'enregistrement depuis un plugin.

Depuis la **1.38.0**, un plugin peut enregistrer ses propres outils MCP sans modification du thème. Le cas d'usage d'origine est le plugin `g2rd-facturation`, qui expose ses factures à un agent MCP.

## Pourquoi un filtre et pas un registre partagé

Le registre de `McpAbilities` est un tableau construit en dur dans le constructeur. Trois options se présentaient :

| Option | Verdict |
|--------|---------|
| Éditer le tableau du thème à chaque nouvel outil | **Rejetée.** Couple le cycle de release du thème à celui de chaque plugin. |
| Registre statique partagé (`McpAbilities::register()`) | **Rejetée.** Impose un ordre de chargement : un plugin chargé après la première instanciation du serveur arriverait trop tard, sans erreur visible. |
| **Filtre WordPress en fin de constructeur** | **Retenue.** Le point d'extension est réévalué à chaque instanciation, l'ordre de chargement des plugins n'a plus d'importance, et le mécanisme est celui que tout développeur WordPress connaît déjà. |

## Forme d'une entrée

Une entrée tierce reprend la forme d'une entrée du cœur (`name`, `description`, `required_scope`, `wp_capability`, `inputSchema`) et ajoute une clé **`callback`** contenant un appelable.

```php
add_filter(
    'g2rd_mcp_abilities',
    static function ( array $registry ): array {
        $registry['g2rd_invoice-total'] = [
            'name'           => 'g2rd_invoice-total',
            'description'    => 'Returns the total amount of an invoice.',
            'required_scope' => 'read_only',   // 'read_only' ou 'editor'
            'wp_capability'  => 'read',        // capability WordPress exigée
            'inputSchema'    => [
                'type'       => 'object',
                'properties' => [
                    'invoice_id' => [
                        'type'        => 'integer',
                        'description' => 'Invoice ID.',
                    ],
                ],
                'required'   => [ 'invoice_id' ],
            ],
            'callback'       => static function ( array $args, array $gate_result ): array {
                $total = g2rd_facturation_get_total( (int) ( $args['invoice_id'] ?? 0 ) );

                if ( null === $total ) {
                    return [
                        'content' => [ [ 'type' => 'text', 'text' => 'Facture introuvable.' ] ],
                        'isError' => true,
                    ];
                }

                return [
                    'content' => [ [ 'type' => 'text', 'text' => \wp_json_encode( [ 'total' => $total ] ) ] ],
                ];
            },
        ];

        return $registry;
    }
);
```

Le nom de l'outil doit rester unique et préfixé (`g2rd_…`), puisqu'il sert de clé dans le registre.

## Signature et retour du callback

Le callback reçoit deux arguments :

| Argument | Type | Contenu |
|----------|------|---------|
| `$args` | `array` | Les arguments de `tools/call`, déjà normalisés en tableau. |
| `$gate_result` | `array` | Le résultat d'autorisation : `user_id`, `token_id`, `scope`, `client_ip`… |

Il doit retourner une charge utile MCP :

```php
// Succès
[ 'content' => [ [ 'type' => 'text', 'text' => '…' ] ] ]

// Échec
[ 'content' => [ [ 'type' => 'text', 'text' => 'Message d’erreur' ] ], 'isError' => true ]
```

Un callback qui retourne autre chose qu'un tableau est converti en erreur d'outil par `call()`, plutôt que transmis tel quel au client MCP.

## Garde-fous

Le filtre est appliqué au registre déjà construit, puis chaque entrée est examinée. Sont **ignorées silencieusement** :

- toute entrée dont la clé existe déjà dans le registre du cœur — un plugin ne peut donc pas détourner `g2rd_delete-post` ni aucun autre outil natif ;
- toute entrée qui n'est pas un tableau, ou à laquelle il manque `name`, `description` ou `inputSchema` ;
- toute entrée sans `callback`, ou dont le `callback` n'est pas appelable.

Les champs de portée omis retombent sur des valeurs **restrictives** : `required_scope` vaut `read_only` et `wp_capability` vaut `manage_options`. Un oubli de déclaration ferme l'accès au lieu de l'ouvrir.

L'autorisation elle-même n'est pas déléguée : `McpServer` applique la `McpSecurityGate` sur `required_scope` et `wp_capability` **avant** d'appeler `call()`. Un outil tiers bénéficie donc du même contrôle de token, de portée, de capability, de limitation de débit et de journal d'audit que les outils du cœur.

`list_tools()` ne renvoie que `name`, `description` et `inputSchema` : ni le `callback` ni les champs de portée ne sont exposés dans la réponse `tools/list`.

## Chemin d'exécution du cœur : inchangé

`call()` teste la présence d'un `callback` avant son `switch`. Aucun outil du cœur n'en déclare — un test le vérifie explicitement (`McpAbilitiesFilterTest::test_no_core_tool_declares_a_callback`) — donc leur chemin d'exécution est strictement identique à celui d'avant la 1.38.0.

## Limite connue : outils en écriture et file de confirmation

Les outils d'écriture du cœur ne s'exécutent pas directement. Ils sont mis en file par `McpConfirmationQueue`, exécutés seulement après validation de l'administrateur par lien e-mail, puis dispatchés par `dispatch_operation()` — **qui possède son propre `switch` sur le nom de l'outil**.

Ce `switch` n'a pas de point d'extension. En conséquence :

> Un outil tiers déclaré avec `required_scope => 'editor'` et passant par la file de confirmation **échouera au moment de la confirmation**, faute de branche correspondante dans `dispatch_operation()`.

Ce n'est pas une régression : le cas n'existait pas avant la 1.38.0. Tant que ce besoin ne se présente pas, **les outils tiers doivent rester en `read_only`**, ou assumer eux-mêmes leur écriture dans leur `callback` sans passer par la file — en sachant qu'ils perdent alors la confirmation par e-mail.

Étendre la file de confirmation aux outils tiers demanderait un second point d'extension dans `dispatch_operation()`, et une façon de rejouer le callback après confirmation. Ce travail n'est pas engagé.

## Tests

`tests/phpunit/security/McpAbilitiesFilterTest.php` couvre l'enregistrement nominal, la délégation vers le callback, les quatre garde-fous, les valeurs par défaut restrictives et la non-régression du registre du cœur.

Le registre de filtres de `tests/phpunit/bootstrap.php` (`add_filter` / `apply_filters` / `remove_all_filters`) est une implémentation en mémoire : sans elle, `apply_filters` renvoyait sa valeur inchangée et tout test du point d'extension aurait été vide de sens.
