# MCP Server SP-1 — Security Foundations

**Date:** 2026-05-12
**Auteur:** Sebastien GERARD
**Statut:** Approuve
**Version cible:** integre dans la branche feature/mcp-server, release en SP-6 avec 1.12.0

---

## Contexte et objectif

Le theme G2RD FSE 1.11.2 integre deja la WordPress Abilities API (`class-abilities.php`) qui expose les CPTs via le registre WP natif. Ce sous-projet SP-1 pose les **fondations de securite** necessaires a un serveur MCP custom qui permetra aux clients externes (Claude Desktop, Cursor, ChatGPT, Gemini) de se connecter via un endpoint REST WordPress en parlant le protocole MCP (JSON-RPC 2.0).

SP-1 ne cree aucun endpoint visible de l'exterieur. Il cree uniquement les classes et tables que les sous-projets SP-2 a SP-6 utiliseront.

### Ce que SP-1 n'est PAS

- Pas un remplacement de `class-abilities.php` — les deux coexistent
- Pas un endpoint MCP — cela arrive en SP-2
- Pas une interface admin — cela arrive en SP-4
- Pas un bump de version — la release 1.12.0 se fait en SP-6

---

## Decisions architecturales

| Decision | Choix | Raison |
|---|---|---|
| Transport MCP | Endpoint REST WordPress custom | Compatibilite tous clients MCP, zero dependance externe, WP 6.5+ suffisant |
| Dependances Composer | Zero ajout | OpenSSL et hash_hmac natifs PHP 8.0+ suffisants, pas de surface d'attaque supplementaire |
| Cles de chiffrement | Derive de AUTH_KEY par defaut, constants wp-config optionnelles | Zero friction installation client, upgrade possible pour deployments experts |
| Interface validation | Page admin standalone (onglets dans g2rd-options-page) | Accessible depuis tout ecran admin, reutilise le build React existant |
| Bloc Gutenberg | Hors scope SP-1 a SP-4 | Arrive en SP-5, YAGNI |

---

## Perimetre SP-1

### Livrables

```
classes/
  class-mcp-encryption.php        # AES-256-GCM via OpenSSL natif
  class-mcp-token-manager.php     # Cycle de vie des tokens HMAC-SHA256
  class-mcp-rate-limiter.php      # Token bucket via transients WordPress
  class-mcp-audit-log.php         # INSERT-only avec hash de chainage
  class-mcp-security-gate.php     # Orchestrateur 7 couches (couches 6+7 en stub)

migrations/
  001-mcp-tables.php              # g2rd_mcp_tokens + g2rd_mcp_audit_log

tests/security/
  McpEncryptionTest.php           # 4 tests
  McpTokenManagerTest.php         # 5 tests
  McpRateLimiterTest.php          # 4 tests
  McpAuditLogTest.php             # 4 tests
```

### Hors scope SP-1

- `g2rd_mcp_confirmation_queue` (table SP-3)
- Endpoint REST MCP (SP-2)
- Interface admin (SP-4)
- Anomaly detector (SP-5)
- Bloc Gutenberg mcp-assistant (SP-5)
- Bridge JavaScript (SP-5)
- Documentation utilisateur (SP-6)
- Bump version 1.12.0 (SP-6)

---

## Modele de donnees

### Table `g2rd_mcp_tokens`

```sql
CREATE TABLE {prefix}g2rd_mcp_tokens (
  id            BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
  user_id       BIGINT UNSIGNED  NOT NULL,
  token_name    VARCHAR(100)     NOT NULL,
  token_hash    VARCHAR(255)     NOT NULL,
  token_prefix  VARCHAR(8)       NOT NULL,
  scope         ENUM('read_only','editor','admin','full') NOT NULL DEFAULT 'read_only',
  allowed_ips   TEXT             NULL,
  last_used_at  DATETIME         NULL,
  last_used_ip  VARCHAR(45)      NULL,
  expires_at    DATETIME         NOT NULL,
  created_at    DATETIME         NOT NULL,
  revoked_at    DATETIME         NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY   token_hash (token_hash),
  KEY          user_id (user_id),
  KEY          expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Regles :
- `token_hash` : HMAC-SHA256 du token brut — jamais le token en clair
- `token_prefix` : 8 premiers caracteres du token brut pour identification humaine (ex. `g2rd_A3xZ`)
- `allowed_ips` : CSV d'IPs exactes IPv4/IPv6 — pas de CIDR en SP-1 (parsing CIDR prevu en SP-4), NULL = toutes IPs autorisees
- `expires_at` : rotation obligatoire 90 jours apres creation

### Table `g2rd_mcp_audit_log`

```sql
CREATE TABLE {prefix}g2rd_mcp_audit_log (
  id               BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
  created_at       DATETIME(3)      NOT NULL,
  user_id          BIGINT UNSIGNED  NOT NULL,
  token_id         BIGINT UNSIGNED  NOT NULL,
  ip_address       VARCHAR(45)      NOT NULL,
  user_agent       VARCHAR(255)     NOT NULL,
  ability_name     VARCHAR(100)     NOT NULL,
  input_hash       VARCHAR(64)      NOT NULL,
  decision         ENUM('allowed','denied','pending','rolled_back') NOT NULL,
  denial_reason    VARCHAR(255)     NULL,
  execution_ms     SMALLINT UNSIGNED NULL,
  screen_context   VARCHAR(500)     NULL,      -- URL admin courante (header X-G2RD-Screen, optionnel)
  chain_hash       VARCHAR(64)      NOT NULL,
  PRIMARY KEY  (id),
  KEY  user_id (user_id),
  KEY  token_id (token_id),
  KEY  created_at (created_at),
  KEY  decision (decision)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Regles :
- `input_hash` : SHA-256 de l'input sanitize — jamais le payload en clair (conformite RGPD)
- `chain_hash` : `HMAC(SHA256, concat(toutes_colonnes + chain_hash_precedent), audit_secret)` ou `audit_secret = McpEncryption::derive_key('audit_chain')` — toute modification d'une ligne invalide toutes les lignes suivantes
- INSERT-only : la classe n'expose aucune methode UPDATE ni DELETE
- Retention : 30 jours par defaut, 1 an pour decision `allowed` sur abilities destructives

---

## Architecture des cinq classes

### `class-mcp-encryption.php`

Wrapper autour d'OpenSSL natif. Zero dependance WordPress, zero logique metier.

**Algorithme :** AES-256-GCM (mode AEAD — authentifie et chiffre en une operation, resiste aux padding oracle attacks)

**Derivation de cle :**
```
cle_maitre = defined('G2RD_MCP_ENCRYPTION_KEY')
             ? G2RD_MCP_ENCRYPTION_KEY
             : hash_hmac('sha256', AUTH_KEY, 'g2rd_mcp_enc_v1')

sous_cle(context) = hash_hmac('sha256', cle_maitre, 'g2rd_mcp_' . context)
```

**Format ciphertext :** `base64( iv[12 bytes] . tag[16 bytes] . ciphertext )`

**Interface publique :**
```php
encrypt(string $plaintext): string
decrypt(string $ciphertext): string|false   # false si tamper detecte
hash_token(string $raw_token): string       # HMAC-SHA256
verify_token_hash(string $raw, string $hash): bool  # hash_equals() — constant time
derive_key(string $context): string
```

Invariants : ne loggue jamais les plaintexts, retourne `false` sur erreur (pas d'exception), independante de WordPress.

---

### `class-mcp-token-manager.php`

Gere le cycle de vie complet des tokens. Depend de `McpEncryption`.

**Format token brut :** `g2rd_` + 40 caracteres base62 (`random_bytes(30)` encode en base62)
- Prefixe `g2rd_` : detection automatique dans les scanners de secrets GitHub/GitLab
- 40 chars base62 : ~237 bits d'entropie — resistant aux attaques brute-force

**Interface publique :**
```php
create_token(int $user_id, string $name, string $scope, array $options = []): array
  # Retourne ['token' => 'g2rd_XXX...', 'id' => 42, 'expires_at' => '...']
  # SEUL endroit ou le token brut est accessible — jamais stocke, jamais reloggue

validate_token(string $raw_token): array|false
  # Retourne ['id', 'user_id', 'scope', 'allowed_ips', 'expires_at'] ou false

revoke_token(int $token_id, int $requesting_user_id): bool
rotate_token(int $token_id, int $requesting_user_id): array|false
  # Revoque l'ancien, cree un nouveau, retourne le nouveau token brut

list_tokens(int $user_id): array
  # Retourne id, name, scope, token_prefix, last_used_at, last_used_ip, expires_at
  # Jamais le hash, jamais le token brut

get_token_stats(int $token_id): array
  # last_used_at, last_used_ip, request_count_24h
```

`validate_token()` utilise `hash_equals()` pour la comparaison — resistant aux timing attacks.

---

### `class-mcp-rate-limiter.php`

Implementation token bucket via transients WordPress. Zero dependance externe.

**Quatre buckets independants par token :**

| Bucket | Limite | Fenetre | Usage |
|---|---|---|---|
| `requests` | 60 | 1 minute | Toutes requetes |
| `daily` | 1000 | 24 heures | Toutes requetes |
| `destructive_hour` | 10 | 1 heure | Abilities destructives uniquement |
| `destructive_minute` | 3 | 1 minute | Abilities destructives uniquement |
| `auth_failures` | 5 | 15 minutes | Echecs auth — blocage auto apres 5 |

**Cle transient :** `g2rd_mcp_rl_{token_id}_{bucket}` — prefixe long pour eviter les collisions

**Interface publique :**
```php
check(int $token_id, string $bucket): bool
  # Verifie sans consommer — pour pre-check

consume(int $token_id, string $bucket): bool
  # Verifie ET decremente — retourne false si limite atteinte

get_remaining(int $token_id, string $bucket): array
  # ['remaining' => 45, 'reset_at' => 1747123456]

reset(int $token_id, string $bucket): void
  # Admin only — efface le transient
```

Backoff exponentiel sur `auth_failures` : apres 3 echecs, delai 2^n secondes entre tentatives (stocke dans un second transient).

---

### `class-mcp-audit-log.php`

Table immuable avec integrite verifiable par hash de chainage.

**Interface publique :**
```php
log(array $entry): int
  # Calcule chain_hash automatiquement, INSERT, retourne l'ID
  # $entry attendu : user_id, token_id, ip_address, user_agent,
  #                  ability_name, input (sera hash), decision,
  #                  denial_reason?, execution_ms?, screen_context?

query(array $filters = [], int $page = 1, int $per_page = 50): array
  # Filtres : user_id, token_id, decision, ability_name, date_from, date_to

export(array $filters, string $format = 'csv'): string
  # CSV ou JSON — JSON inclut une signature HMAC pour verification externe

verify_integrity(int $from_id = 1, int $to_id = 0): array
  # ['valid' => true, 'checked' => 1234, 'broken_at' => null]
  # Ou ['valid' => false, 'broken_at' => 456]

get_last_chain_hash(): string
  # Retourne le chain_hash de la derniere ligne — utilise par log()
```

La methode `log()` est la seule qui ecrit. Pas de methode `update()` ni `delete()`. Pas de methode `truncate()`. Intentionnel.

---

### `class-mcp-security-gate.php`

Orchestrateur des 7 couches. Point d'entree unique de toutes les requetes MCP.

**En SP-1 :** couches 1 a 5 implementees, couches 6 (confirmation) et 7 (log) partiellement — le gate est fonctionnel mais sans endpoint pour l'invoquer (arrive en SP-2).

**Interface publique :**
```php
validate_request(WP_REST_Request $request): array|WP_Error
  # Succes : ['token' => [...], 'user_id' => 42, 'scope' => 'editor']
  # Echec  : WP_Error avec code 'mcp_unauthorized' et status 401
  # La reponse d'erreur ne revele jamais quelle couche a echoue
```

**Couches privees (ordre strict) :**
```php
private verify_authentication(WP_REST_Request $r): array|WP_Error
  # Header 'Authorization: Bearer g2rd_XXX' obligatoire
  # Format valide, TokenManager::validate_token(), non revoque, non expire

private verify_token_scope(array $token, string $required_scope): true|WP_Error
  # Hierarchie : full > admin > editor > read_only
  # Token scope >= required scope

private verify_capability(array $token, string $capability, int $object_id = 0): true|WP_Error
  # wp_set_current_user($token['user_id']) puis current_user_can()
  # object_id pour edit_post($post_id), delete_post($post_id)

private check_rate_limits(array $token, bool $is_destructive = false): true|WP_Error
  # RateLimiter::consume() sur les buckets pertinents

private validate_input(array $input, array $schema): true|WP_Error
  # JSON Schema validation : types, longueurs, formats
  # Rejet si payload > 1MB
  # Rejet si patterns : <script, javascript:, data:, vbscript:
  # Detection patterns SQLi basiques

private check_confirmation_required(string $ability): bool
  # STUB SP-1 — retourne toujours false
  # Implémenté en SP-3

private log_request(array $context): void
  # AuditLog::log() — TOUJOURS appele, meme sur refus en couche 1
```

**Principe fail-closed :** toute couche qui echoue appelle immediatement `log_request()` avec `decision = 'denied'` et retourne `WP_Error`. Pas d'execution partielle possible.

---

## Flow de securite complet

```
Requete MCP entrant
        │
        ▼
[C1] verify_authentication()
  ├── Authorization header absent     → 401 + log(denied, 'no_token')
  ├── Format invalide                  → 401 + log(denied, 'invalid_format')
  ├── Token inconnu en DB              → 401 + log(denied, 'unknown_token')
  ├── Token revoque                    → 401 + log(denied, 'revoked')
  └── Token expire                     → 401 + log(denied, 'expired')
        │ OK
        ▼
[C2] verify_token_scope(required_scope)
  └── scope insuffisant                → 403 + log(denied, 'insufficient_scope')
        │ OK
        ▼
[C3] verify_capability(capability)
  ├── user inexistant                  → 403 + log(denied, 'user_not_found')
  └── capability manquante             → 403 + log(denied, 'capability_denied')
        │ OK
        ▼
[C4] check_rate_limits()
  ├── bucket requests epuise           → 429 + log(denied, 'rate_limit_requests')
  ├── bucket daily epuise              → 429 + log(denied, 'rate_limit_daily')
  └── auth_failures bloque             → 429 + log(denied, 'auth_blocked')
        │ OK
        ▼
[C5] validate_input(input, schema)
  ├── payload > 1MB                    → 400 + log(denied, 'payload_too_large')
  ├── schema invalide                  → 400 + log(denied, 'invalid_input')
  └── pattern dangereux detecte        → 400 + log(denied, 'dangerous_input')
        │ OK
        ▼
[C6] check_confirmation_required()    ← STUB SP-1
        │ OK (toujours en SP-1)
        ▼
[C7] log_request(decision = 'allowed')
        │
        ▼
Retourne ['token' => ..., 'user_id' => ..., 'scope' => ...]
(utilise par le controller MCP en SP-2)
```

**Reponse d'erreur normalisee (aucun detail interne) :**
```json
{ "code": "mcp_unauthorized", "message": "Request denied.", "data": { "status": 401 } }
```

---

## Migration SQL

### `migrations/001-mcp-tables.php`

- Definit une fonction `g2rd_mcp_run_migration_001()` hookee a `after_switch_theme` depuis `functions.php` (passe 1, ordre de chargement prioritaire, apres `class-mcp-audit-log.php`)
- Utilise `dbDelta()` — idempotent, ne recrée pas si les tables existent
- Prefixe `$wpdb->prefix` — compatible multisite
- Verifie `get_option('g2rd_mcp_db_version')` avant execution — skip si version deja installee
- Note : en SP-2, ce hook sera transfere dans `class-mcp-server.php` via `register_activation_hook()`

---

## Tests de securite SP-1

**17 tests, 4 fichiers PHPUnit**

### `McpEncryptionTest.php` — 4 tests
```
test_encrypt_decrypt_roundtrip
  # encrypt puis decrypt retourne le plaintext original

test_different_iv_each_encryption
  # Deux encrypt du meme plaintext produisent des ciphertexts differents

test_tampered_ciphertext_returns_false
  # Modifier un byte du ciphertext → decrypt() retourne false

test_hash_token_constant_time
  # verify_token_hash() utilise hash_equals() — verifie via reflection ou comportement
```

### `McpTokenManagerTest.php` — 5 tests
```
test_token_created_with_correct_scope
  # create_token() retourne scope correct, token commence par 'g2rd_'

test_raw_token_never_stored_in_db
  # Apres create_token(), la valeur brute est absente de g2rd_mcp_tokens

test_revoked_token_rejected_immediately
  # revoke_token() puis validate_token() retourne false sans delai

test_expired_token_rejected
  # Token avec expires_at dans le passe → validate_token() retourne false

test_validate_wrong_user_rejected
  # Token cree pour user_id=1 → validate_token() ne peut pas etre utilise par user_id=2
```

### `McpRateLimiterTest.php` — 4 tests
```
test_requests_bucket_enforced
  # 60 consume() succeeds, le 61e retourne false

test_auth_failures_trigger_lockout_after_5
  # 5 consume('auth_failures') puis check('auth_failures') retourne false

test_destructive_bucket_independent_from_requests
  # Epuiser 'requests' n'affecte pas 'destructive_hour'

test_consume_returns_false_when_limit_reached
  # consume() retourne false exactement a la limite (pas avant, pas apres)
```

### `McpAuditLogTest.php` — 4 tests
```
test_log_insert_only_no_update_method_exists
  # La classe McpAuditLog n'a pas de methode update() ni delete() (reflection)

test_chain_hash_computed_on_insert
  # Deux log() consecutifs → chain_hash[2] contient chain_hash[1]

test_verify_integrity_detects_tampered_row
  # Modifier directement une ligne en DB → verify_integrity() retourne broken_at != null

test_denied_requests_are_logged
  # Appel log() avec decision='denied' → ligne presente en DB
```

---

## Conditions de succes SP-1

- `composer run phpunit` : 17/17 tests passent, 0 skipped
- `composer run phpcs` : 0 erreur WordPress Coding Standards
- `composer run phpcs:security` : 0 issue critique
- `WP_DEBUG=true` : 0 notice, 0 warning PHP pendant l'activation
- Activation du theme : tables creees, `g2rd_mcp_db_version` presente en options
- Double activation : migration idempotente, pas de doublon de colonnes
- Desactivation du theme : tables conservees (les donnees d'audit ne sont pas supprimees a la desactivation)

---

## Sequence d'implementation dans SP-1

```
1. class-mcp-encryption.php        # zero dependance WP — testable en isolation totale
2. migrations/001-mcp-tables.php   # tables requises par les classes suivantes
3. class-mcp-audit-log.php         # depend des tables, independante des autres classes
4. class-mcp-token-manager.php     # depend de Encryption + AuditLog
5. class-mcp-rate-limiter.php      # depend uniquement des transients WP
6. class-mcp-security-gate.php     # depend de tout ce qui precede
7. Tests dans l'ordre des classes  # un fichier de test par classe
```

---

## Roadmap des sous-projets suivants

| SP | Contenu principal | Prerequis |
|---|---|---|
| SP-2 | Endpoint REST MCP, protocole JSON-RPC 2.0, abilities lecture seule | SP-1 |
| SP-3 | Abilities ecriture, `g2rd_mcp_confirmation_queue`, email confirmation | SP-2 |
| SP-4 | Onglets admin dans g2rd-options-page (tokens, audit, confirmations) | SP-3 |
| SP-5 | Anomaly detector, bridge JS, bloc Gutenberg mcp-assistant | SP-4 |
| SP-6 | Documentation, durcissement, release 1.12.0 | SP-5 |
