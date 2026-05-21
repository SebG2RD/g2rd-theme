#!/usr/bin/env node
/**
 * G2RD MCP Bridge — pont stdio ↔ HTTP pour Claude Desktop
 *
 * Ce script fait le lien entre Claude Desktop (qui communique en JSON-RPC 2.0
 * via stdin/stdout) et le serveur MCP WordPress G2RD (endpoint REST HTTP POST).
 *
 * Configuration dans claude_desktop_config.json :
 *
 *   {
 *     "mcpServers": {
 *       "g2rd": {
 *         "command": "node",
 *         "args": ["C:\\chemin\\vers\\g2rd-theme\\tools\\g2rd-mcp-bridge.js"],
 *         "env": {
 *           "G2RD_MCP_URL":   "https://votre-site.com/wp-json/g2rd/mcp/v1/",
 *           "G2RD_MCP_TOKEN": "votre-token-mcp-ici"
 *         }
 *       }
 *     }
 *   }
 *
 * Variables d'environnement :
 *   G2RD_MCP_URL   — URL complète de l'endpoint MCP WordPress (obligatoire)
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

	postToWordPress( JSON.stringify( body ) )
		.then( ( raw ) => {
			// Écrire la réponse brute si c'est du JSON valide, sinon erreur interne.
			try {
				JSON.parse( raw );
				process.stdout.write( raw + '\n' );
			} catch {
				writeStdout( {
					jsonrpc: '2.0',
					error:   { code: -32603, message: 'Invalid JSON from server' },
					id:      body.id ?? null,
				} );
			}
		} )
		.catch( ( err ) => {
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
			res.on( 'end',   ()        => { resolve( data.trim() ); } );
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
