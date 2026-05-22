# Validation IP aux points d'entrée

**Date :** 2026-05-22
**Statut :** Approuvé

## Contexte

L'audit sécurité a identifié que les adresses IP issues de `$_SERVER['REMOTE_ADDR']` et des options `allowed_ips` des tokens MCP ne sont validées qu'avec `sanitize_text_field()`. Cela permet de stocker des chaînes arbitraires dans les logs et la base de données.

La correction s'applique aux points d'entrée uniquement — les classes en aval font confiance à ce qu'elles reçoivent.

## Périmètre

3 endroits dans 3 fichiers existants. Aucun nouveau fichier.

| Fichier | Méthode | Changement |
|---|---|---|
| `classes/class-mcp-server.php` | `extract_client_ip()` | `filter_var(..., FILTER_VALIDATE_IP) ?: 'unknown'` sur la valeur finale |
| `classes/ai/class-ai-rest.php` | `log_action()` | Même validation inline sur `REMOTE_ADDR` |
| `classes/class-mcp-token-manager.php` | `create_token()` | `array_filter` sur `allowed_ips` — rejette les entrées non-IP |

## Section 1 — `McpServer::extract_client_ip()`

Méthode existante qui résout l'IP réelle (REMOTE_ADDR ou X-Forwarded-For selon proxy whitelist). On ajoute la validation en dernière étape :

```php
// Avant retour final :
return filter_var( $resolved_ip, FILTER_VALIDATE_IP ) !== false
    ? $resolved_ip
    : 'unknown';
```

Toutes les classes en aval (McpSecurityGate, McpAuditLog, McpTokenManager::update_last_used) reçoivent une IP validée ou `'unknown'` — aucune modification nécessaire sur elles.

## Section 2 — `AiRest::log_action()`

L'endpoint IA ne passe pas par McpServer. Validation inline :

```php
$ip = (string) filter_var(
    sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ),
    FILTER_VALIDATE_IP
);
if ( empty( $ip ) ) {
    $ip = 'unknown';
}
```

## Section 3 — `McpTokenManager::create_token()` — `allowed_ips`

Les IPs soumises lors de la création d'un token sont filtrées — toute entrée non-IP est silencieusement rejetée :

```php
$valid_ips = array_filter(
    array_map( 'sanitize_text_field', $options['allowed_ips'] ),
    static fn( string $ip ) => false !== filter_var( $ip, FILTER_VALIDATE_IP )
);
$allowed_ips = ! empty( $valid_ips ) ? implode( ',', $valid_ips ) : null;
```

## Non-inclus

- Validation CIDR (ex. `192.168.1.0/24`) — non supportée actuellement, hors périmètre
- IPv6 dans `allowed_ips` — `FILTER_VALIDATE_IP` valide IPv4 et IPv6 nativement, déjà couvert
