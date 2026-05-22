# Module IA G2RD — Appel direct Anthropic

**Date :** 2026-05-22
**Statut :** Approuvé

## Contexte

Le module IA G2RD (`classes/ai/`) délègue actuellement tous les appels LLM à `wp_ai_client()`, une fonction fournie par le plugin WordPress AI Connectors. Ce plugin n'est pas installé sur les sites G2RD, ce qui rend le module inopérant : `connectorReady` vaut toujours `false`, l'UI affiche "Connecteur IA non configuré", et aucun endpoint REST ne répond correctement.

L'objectif est de remplacer cette dépendance par un appel direct à l'API Anthropic, piloté par une clé API saisie dans la page d'options G2RD.

## Périmètre

4 fichiers modifiés, 0 nouveau fichier :

| Fichier | Changement |
|---|---|
| `classes/ai/class-ai-client.php` | Remplace `wp_ai_client()` par `wp_remote_post()` vers Anthropic |
| `classes/ai/class-ai-module.php` | `connectorReady` → `AiClient::is_available()` |
| `classes/ai/class-ai-rest.php` | Endpoint `settings` : lecture/écriture `api_key` |
| `blocks/g2rd-options-page/src/tabs/TabIA.js` | Champ API key + suppression section WP AI |

## Section 1 — Stockage de la clé API

La clé est stockée dans l'option WordPress existante `g2rd_ai_settings` (tableau associatif), sous la clé `api_key`.

### Sécurité

- **Entrée :** `sanitize_text_field()` sur la valeur POST avant stockage.
- **Sortie REST (GET):** la clé brute n'est **jamais** renvoyée. On renvoie à la place :
  - `api_key_set` (bool) — indique si une clé est configurée
  - `api_key_preview` (string) — `"••••" . substr($key, -4)` si clé présente, sinon `""`
- **Localization JS (`g2rdAiConfig`) :** le champ `api_key` est absent. Seul `connectorReady` (bool) est transmis au frontend.
- **Capacité requise :** `manage_options` sur le `permission_callback` de l'endpoint `settings` (déjà en place).

### Règle de mise à jour

- Champ vide soumis → clé existante conservée (pas d'écrasement par chaîne vide).
- Champ non vide soumis → nouvelle clé enregistrée.

## Section 2 — `AiClient::generate()`

### Implémentation

```
AiClient::generate(string $prompt, array $args = []) : string|WP_Error

1. Lire $settings = get_option('g2rd_ai_settings', [])
2. $api_key = $settings['api_key'] ?? ''
3. Si vide → WP_Error('ai_not_configured', 'Clé API Anthropic non configurée.', 503)
4. wp_remote_post('https://api.anthropic.com/v1/messages', [
     timeout  => 30,
     headers  => [
       'x-api-key'         => $api_key,
       'anthropic-version' => '2023-06-01',
       'content-type'      => 'application/json',
     ],
     body => wp_json_encode([
       'model'      => 'claude-sonnet-4-6',
       'max_tokens' => $args['max_tokens'] ?? 2048,
       'messages'   => [['role' => 'user', 'content' => $prompt]],
     ]),
   ])
5. Si wp_is_wp_error($response) → WP_Error('ai_http_error', ...)
6. $code = wp_remote_retrieve_response_code($response)
   Si $code !== 200 → WP_Error('ai_api_error', message Anthropic, $code)
7. $body = json_decode(wp_remote_retrieve_body($response), true)
8. Retourner $body['content'][0]['text'] (string)
```

### `AiClient::is_available()`

```php
$settings = get_option( 'g2rd_ai_settings', [] );
return ! empty( $settings['api_key'] );
```

### `AiModule` — flag `connectorReady`

```php
'connectorReady' => \G2RD\AiClient::is_available(),
```

Remplace : `'connectorReady' => \function_exists( 'wp_ai_client' )`

## Section 3 — `TabIA.js`

### Ajout : section "Clé API Anthropic"

Nouvelle section dans le formulaire, placée avant les toggles d'activation :

- **Label :** "Clé API Anthropic"
- **Composant :** `TextControl` avec `type="password"` et `autoComplete="off"`
- **Placeholder :** `sk-ant-...`
- **Texte d'aide :** "Obtenir une clé sur console.anthropic.com"

**Comportement selon l'état :**

| État serveur | Affichage |
|---|---|
| `api_key_set: false` | Champ vide, placeholder `sk-ant-...` |
| `api_key_set: true` | Champ désactivé affichant `api_key_preview` (`••••ant3`) + bouton "Modifier" |

Le bouton "Modifier" réactive le champ et le vide pour permettre la re-saisie.

**Logique de sauvegarde :** si le champ est vide au moment du POST, la clé `api_key` est omise du payload (pas envoyée) → le serveur conserve l'ancienne valeur.

### Suppression : section "Connecteur IA WordPress (WP 7.0+)"

Le bloc d'information sur `wp_ai_client()` est retiré — il n'est plus pertinent.

## Flux complet après implémentation

```
Utilisateur saisit clé dans TabIA.js
  → POST /g2rd/v1/ai/settings { api_key: "sk-ant-..." }
  → AiRest sauvegarde (sanitize + option)
  → AiModule recharge g2rdAiConfig.connectorReady = true
  → G2RDAiModal / G2RDAiInspectorPanel : boutons actifs

Clic "Générer"
  → useG2RDAi.generate({ endpoint, payload })
  → POST /wp-json/g2rd/v1/ai/block-action (+ nonce)
  → AiRest::handle_block_action()
  → AiPrompts::get_prompt(action, context)
  → AiClient::generate(prompt)
  → wp_remote_post(api.anthropic.com/v1/messages)
  → retourne text → REST response
  → UI affiche résultat inline
```

## Non-inclus dans ce spec

- Streaming (Server-Sent Events) — ajout ultérieur si besoin
- Choix du modèle depuis l'UI — `claude-sonnet-4-6` fixé en constante
- Fallback vers `wp_ai_client()` — retiré pour simplifier, peut être réintroduit en 3 lignes
