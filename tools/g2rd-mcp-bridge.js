#!/usr/bin/env node
/**
 * G2RD MCP Bridge — pont stdio ↔ HTTP pour Claude Desktop
 *
 * Ce script fait le lien entre Claude Desktop (qui communique en JSON-RPC 2.0
 * via stdin/stdout) et le serveur MCP WordPress G2RD (endpoint REST HTTP POST).
 *
 * Configuration dans claude_desktop_config.json — copiez-la depuis l'onglet
 * « MCP Tokens » de la page d'options du thème, qui la génère déjà remplie.
 * Le bloc est à FUSIONNER dans l'objet "mcpServers" existant, pas à coller à la
 * place du fichier.
 *
 *   {
 *     "mcpServers": {
 *       "g2rd-votre-site": {
 *         "command": "g2rd-mcp-bridge",
 *         "env": {
 *           "G2RD_MCP_URL":   "https://votre-site.com/wp-json/g2rd/v1/mcp",
 *           "G2RD_MCP_TOKEN": "votre-token-mcp-ici"
 *         }
 *       }
 *     }
 *   }
 *
 * La clé du serveur est dérivée du domaine du site, afin que plusieurs sites
 * G2RD puissent coexister dans la même configuration sans s'écraser.
 *
 * « g2rd-mcp-bridge » suppose que le binaire npm est installé : lancer `npm link`
 * une fois depuis la racine du thème. À défaut, utiliser "command": "node" avec
 * le chemin absolu vers ce fichier en argument.
 *
 * Variables d'environnement :
 *   G2RD_MCP_URL   — URL complète de l'endpoint MCP WordPress (obligatoire),
 *                    de la forme https://votre-site.com/wp-json/g2rd/v1/mcp
 *   G2RD_MCP_TOKEN — Bearer token généré depuis les réglages MCP G2RD (obligatoire)
 *
 * @package G2RD
 */

'use strict';

const https    = require( 'https' );
const http     = require( 'http' );
const readline = require( 'readline' );
const { URL }  = require( 'url' );

// ── Configuration ─────────────────────────────────────────────────────────────

const MCP_URL   = ( process.env.G2RD_MCP_URL   || '' ).trim();
const MCP_TOKEN = ( process.env.G2RD_MCP_TOKEN  || '' ).trim();

// Debug optionnel : activer avec G2RD_MCP_DEBUG=1 (ou true/yes/on).
// Les traces vont toujours sur stderr — jamais sur stdout, qui est réservé
// au flux JSON-RPC du protocole MCP.
const DEBUG = /^(1|true|yes|on)$/i.test( ( process.env.G2RD_MCP_DEBUG || '' ).trim() );
const dbg   = ( msg ) => {
	if ( DEBUG ) process.stderr.write( '[g2rd-mcp-bridge] ' + msg + '\n' );
};

if ( ! MCP_URL ) {
	process.stderr.write( '[g2rd-mcp-bridge] Erreur : G2RD_MCP_URL est requis.\n' );
	process.exit( 1 );
}

if ( ! MCP_TOKEN ) {
	process.stderr.write( '[g2rd-mcp-bridge] Erreur : G2RD_MCP_TOKEN est requis.\n' );
	process.exit( 1 );
}

let parsedUrl;
try {
	parsedUrl = new URL( MCP_URL );
} catch {
	process.stderr.write( '[g2rd-mcp-bridge] Erreur : G2RD_MCP_URL invalide.\n' );
	process.exit( 1 );
}

// ── Lecture stdin ligne par ligne ─────────────────────────────────────────────

const rl = readline.createInterface( {
	input:     process.stdin,
	crlfDelay: Infinity,
	terminal:  false,
} );

rl.on( 'line', ( line ) => {
	const trimmed = line.trim();
	if ( ! trimmed ) return;

	let body;
	try {
		body = JSON.parse( trimmed );
	} catch {
		writeStdout( {
			jsonrpc: '2.0',
			error:   { code: -32700, message: 'Parse error' },
			id:      null,
		} );
		return;
	}

	const isNotification = ! ( 'id' in body );

	postToWordPress( JSON.stringify( body ) )
		.then( ( { body: raw, status } ) => {
			dbg( 'HTTP ' + status + ' ← ' + ( body.method || '?' ) + ' id=' + ( body.id ?? 'none' ) );

			// Notifications MCP (sans id) : le serveur retourne 202 sans corps.
			// Le protocole MCP interdit de répondre à une notification — on ignore.
			if ( isNotification || ( status === 202 && ! raw ) ) return;

			dbg( 'raw: ' + raw.slice( 0, 500 ) + ( raw.length > 500 ? '…' : '' ) );

			let parsed;
			try {
				// Supprimer un éventuel BOM UTF-8 avant le JSON.
				parsed = JSON.parse( raw.replace( /^\xef\xbb\xbf/, '' ) );
			} catch {
				dbg( 'parse error — raw non-JSON' );
				writeStdout( {
					jsonrpc: '2.0',
					error:   { code: -32603, message: 'Invalid JSON from server (HTTP ' + status + ')' },
					id:      body.id ?? null,
				} );
				return;
			}

			// Réponse JSON-RPC valide : transmettre telle quelle uniquement si la
			// structure est conforme (jsonrpc:"2.0" + result OU error, pas de clés WP).
			if (
				parsed &&
				parsed.jsonrpc === '2.0' &&
				! ( 'code' in parsed && 'message' in parsed && ! ( 'result' in parsed || 'error' in parsed ) )
			) {
				process.stdout.write( JSON.stringify( parsed ) + '\n' );
				return;
			}

			// Réponse d'erreur WordPress ({"code":"...","message":"...","data":{...}})
			// ou réponse hybride malformée : envelopper dans un JSON-RPC error.
			const wpMsg = ( parsed && parsed.message )
				? String( parsed.message )
				: 'WordPress returned a non-JSON-RPC response (HTTP ' + status + ')';
			const wpCode = ( parsed && parsed.data && parsed.data.status )
				? -32000 - parseInt( parsed.data.status, 10 )
				: -32603;

			dbg( 'WP error wrapped: ' + JSON.stringify( parsed ) );
			writeStdout( {
				jsonrpc: '2.0',
				error:   { code: wpCode, message: wpMsg, data: parsed },
				id:      body.id ?? null,
			} );
		} )
		.catch( ( err ) => {
			dbg( 'network error: ' + String( err.message || err ) );
			writeStdout( {
				jsonrpc: '2.0',
				error:   { code: -32603, message: String( err.message || err ) },
				id:      body.id ?? null,
			} );
		} );
} );

rl.on( 'close', () => {
	process.exit( 0 );
} );

// ── HTTP POST vers WordPress ──────────────────────────────────────────────────

function postToWordPress( payload ) {
	return new Promise( ( resolve, reject ) => {
		const isHttps = parsedUrl.protocol === 'https:';
		const lib     = isHttps ? https : http;
		const port    = parsedUrl.port
			? parseInt( parsedUrl.port, 10 )
			: ( isHttps ? 443 : 80 );

		const options = {
			hostname: parsedUrl.hostname,
			port,
			path:     parsedUrl.pathname + parsedUrl.search,
			method:   'POST',
			headers:  {
				'Content-Type':   'application/json',
				'Content-Length': Buffer.byteLength( payload ),
				'Accept':         'application/json',
				'Authorization':  'Bearer ' + MCP_TOKEN,
			},
			// Timeout 30 s pour les outils lents (éditeur, etc.)
			timeout: 30_000,
		};

		const req = lib.request( options, ( res ) => {
			let data = '';
			res.on( 'data',  ( chunk ) => { data += chunk; } );
			res.on( 'end',   ()        => { resolve( { body: data.trim(), status: res.statusCode } ); } );
			res.on( 'error', reject );
		} );

		req.on( 'timeout', () => {
			req.destroy();
			reject( new Error( 'Request timeout (30 s)' ) );
		} );

		req.on( 'error', reject );
		req.write( payload );
		req.end();
	} );
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function writeStdout( obj ) {
	process.stdout.write( JSON.stringify( obj ) + '\n' );
}
