# Serveur MCP G2RD — Guide utilisateur

Le thème G2RD intègre un **serveur MCP natif** (Model Context Protocol) qui permet à des agents IA externes — Claude Desktop, Cursor, ChatGPT, Gemini, etc. — de se connecter à votre site WordPress et d'effectuer des opérations supervisées via une interface sécurisée à 7 couches.

---

## Sommaire

1. [Prérequis](#prérequis)
2. [Architecture et sécurité](#architecture-et-sécurité)
3. [Créer un token d'accès](#créer-un-token-daccès)
4. [Connecter Claude Desktop](#connecter-claude-desktop)
5. [Outils disponibles](#outils-disponibles)
6. [Flux de confirmation des écritures](#flux-de-confirmation-des-écritures)
7. [Interface d'administration](#interface-dadministration)
8. [Détecteur d'anomalies](#détecteur-danomalies)
9. [Variables d'environnement avancées](#variables-denvironnement-avancées)

---

## Prérequis

- WordPress 6.5+
- PHP 8.0+ avec l'extension OpenSSL activée
- Thème G2RD version 1.12.0+
- URL du site accessible en HTTPS (recommandé)

---

## Architecture et sécurité

Chaque requête MCP traverse **7 couches de sécurité** dans l'ordre strict suivant :

| Couche | Contrôle | Décision |
|--------|----------|----------|
| C1 | Authentification Bearer token | Token présent, valide, non révoqué, non expiré |
| C2 | Portée du token (scope) | `read_only` ou `editor` selon l'opération |
| C3 | Capacités WordPress | `current_user_can()` sur l'utilisateur propriétaire du token |
| C4 | Rate limiting | 60 req/min, 1 000 req/24h, 10 écritures/h par token |
| C5 | Validation de l'input | Schéma JSON, taille max 1 MB, patterns dangereux rejetés |
| C6 | Confirmation humaine | Les opérations d'écriture nécessitent validation par email |
| C7 | Audit log immuable | Toutes les requêtes — autorisées ET refusées — sont enregistrées |

**Principe fail-closed** : toute couche qui échoue arrête la requête immédiatement. La réponse d'erreur ne révèle jamais quelle couche a échoué.

### Format des tokens

Les tokens commencent par `g2rd_` suivi de 40 caractères base62 (~237 bits d'entropie). Ce préfixe permet la détection automatique dans les scanners de secrets GitHub/GitLab.

### Chiffrement

Les arguments des opérations d'écriture en attente de confirmation sont chiffrés en **AES-256-GCM** au repos. Les tokens ne sont jamais stockés en clair — seul un HMAC-SHA256 est conservé en base.

---

## Créer un token d'accès

1. Dans l'administration WordPress, aller dans **Options G2RD → MCP Tokens**
2. Renseigner un **nom** descriptif (ex. : `Claude Desktop - MacBook Pro`)
3. Choisir la **portée** :
   - `Lecture seule` — outils de lecture uniquement (`get-site-info`, `list-posts`, `get-post`)
   - `Éditeur` — + outils d'écriture (`create-post`, `update-post`) avec confirmation obligatoire
4. Définir la **durée de validité** (1 à 365 jours, défaut 30)
5. Cliquer **Créer** et **copier immédiatement** le token affiché

> ⚠️ Le token en clair n'est affiché qu'une seule fois. Il n'est pas stocké — impossible à récupérer après fermeture.

---

## Connecter Claude Desktop

L'endpoint MCP est exposé à l'URL :

```
POST https://votre-site.fr/wp-json/g2rd/mcp/v1
```

### Configuration `claude_desktop_config.json`

```json
{
  "mcpServers": {
    "g2rd-wordpress": {
      "command": "npx",
      "args": ["-y", "@anthropic-ai/mcp-client-http"],
      "env": {
        "MCP_SERVER_URL": "https://votre-site.fr/wp-json/g2rd/mcp/v1",
        "MCP_AUTH_TOKEN": "g2rd_VOTRE_TOKEN_ICI"
      }
    }
  }
}
```

### Configuration Cursor

Dans **Settings → Features → MCP Servers → Add new MCP server** :

```json
{
  "name": "G2RD WordPress",
  "url": "https://votre-site.fr/wp-json/g2rd/mcp/v1",
  "headers": {
    "Authorization": "Bearer g2rd_VOTRE_TOKEN_ICI"
  }
}
```

### Test rapide (curl)

```bash
curl -X POST https://votre-site.fr/wp-json/g2rd/mcp/v1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer g2rd_VOTRE_TOKEN_ICI" \
  -d '{"jsonrpc":"2.0","method":"tools/list","id":1}'
```

---

## Outils disponibles

### Lecture seule (`read_only` et `editor`)

#### `g2rd/get-site-info`
Retourne les informations générales du site.

```json
{
  "name": "Mon Site",
  "url": "https://mon-site.fr",
  "description": "Description du site",
  "language": "fr-FR",
  "timezone": "Europe/Paris",
  "version": "6.5.0"
}
```

#### `g2rd/list-posts`
Liste les articles publiés avec pagination.

| Paramètre | Type | Défaut | Description |
|-----------|------|--------|-------------|
| `post_type` | string | `post` | Type de contenu WordPress |
| `per_page` | integer | 10 | Résultats par page (max 50) |
| `page` | integer | 1 | Numéro de page |
| `search` | string | — | Terme de recherche |

#### `g2rd/get-post`
Retourne le contenu complet d'un article par ID.

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `post_id` | integer | oui | ID WordPress de l'article |

---

### Écriture (scope `editor` + confirmation requise)

> Toutes les opérations d'écriture déclenchent un email de confirmation à l'administrateur. L'opération n'est exécutée qu'après validation explicite. Les demandes expirent après **15 minutes**.

#### `g2rd/create-post`
Crée un nouvel article.

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `title` | string | oui | Titre de l'article |
| `content` | string | non | Contenu HTML (filtré `wp_kses_post`) |
| `status` | string | non | `draft` (défaut), `pending`, `publish` |
| `post_type` | string | non | `post` (défaut) ou tout CPT actif |
| `excerpt` | string | non | Extrait |

#### `g2rd/update-post`
Modifie un article existant.

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `post_id` | integer | oui | ID de l'article à modifier |
| `title` | string | non | Nouveau titre |
| `content` | string | non | Nouveau contenu |
| `excerpt` | string | non | Nouvel extrait |

---

## Flux de confirmation des écritures

```
Agent MCP appelle g2rd/create-post
        │
        ▼
Serveur MCP enregistre la demande (chiffrée) en base
        │
        ▼
Email envoyé à l'admin : résumé de l'opération + liens Confirmer / Refuser
        │
   ┌────┴────┐
   │         │
   ▼         ▼
Confirmer  Refuser
   │         │
   ▼         ▼
Opération  Aucune écriture
exécutée   Audit: rolled_back
Audit: allowed
```

La réponse immédiate à l'agent est :

```json
{
  "status": "pending",
  "message": "La demande a été soumise à l'administrateur pour confirmation.",
  "expires_at": "2026-05-13 14:35:00"
}
```

### Sécurité du flux de confirmation

- Les liens Confirmer/Refuser contiennent un token de 256 bits (32 octets aléatoires) — impossible à deviner par force brute
- L'accès à `admin-post.php` exige d'être connecté à WordPress
- Single-use : une fois cliqué, le lien ne peut pas être réutilisé
- TTL 15 minutes : après expiration, la demande est automatiquement annulée

---

## Interface d'administration

Accessible depuis **Options G2RD** (menu admin).

### Onglet MCP Tokens

- Liste des tokens actifs (préfixe, portée, date de création, expiration)
- Création de nouveaux tokens avec copie one-shot du token brut
- Révocation immédiate d'un token (l'agent est bloqué dès la prochaine requête)

### Onglet MCP Audit

- Journal immuable de toutes les requêtes (autorisées, refusées, annulées)
- Filtrage par décision : `allowed`, `denied`, `pending`, `rolled_back`
- Pagination (25 entrées par page)
- Intégrité vérifiable par hash de chaînage

### Onglet MCP File

- Demandes d'écriture en attente de confirmation
- Boutons **Confirmer** / **Refuser** directement dans l'interface (alternative à l'email)
- Countdown jusqu'à expiration
- Se rafraîchit à la demande

### Badge barre d'admin

Un badge rouge sur l'icône MCP dans la barre d'administration indique le nombre de demandes en attente. Il se met à jour automatiquement toutes les 60 secondes.

---

## Détecteur d'anomalies

Le serveur MCP surveille en continu trois patterns de comportement suspects :

| Pattern | Déclencheur | Sévérité |
|---------|-------------|----------|
| **Brute force IP** | ≥5 refus depuis la même IP en 15 min | `critical` (≥10) / `high` (5-9) |
| **Taux de refus élevé** | Token avec >50% de refus sur 24h (min 5 requêtes) | `high` (>80%) / `medium` (50-80%) |
| **Pic de volume** | Volume de la dernière heure >3× la moyenne 7 jours | `high` (>5×) / `medium` (3-5×) |

Les résultats sont accessibles via `GET /wp-json/g2rd/v1/mcp-anomalies` et affichés dans le **panneau MCP Assistant** de l'éditeur Gutenberg.

---

## Variables d'environnement avancées

Ces constantes peuvent être définies dans `wp-config.php` pour remplacer les valeurs dérivées automatiquement.

```php
// Clé de chiffrement AES-256-GCM (base64, 32 octets)
// Par défaut : dérivée de AUTH_KEY via HMAC-SHA256
define( 'G2RD_MCP_ENCRYPTION_KEY', 'votre_cle_base64_ici' );

// Clé d'intégrité pour le hash de chaînage de l'audit log
// Par défaut : dérivée de G2RD_MCP_ENCRYPTION_KEY + contexte 'audit_chain'
define( 'G2RD_MCP_AUDIT_SECRET', 'votre_secret_audit_base64_ici' );
```

> Ces constantes sont optionnelles. Les valeurs dérivées automatiquement sont cryptographiquement sûres pour la grande majorité des déploiements.
