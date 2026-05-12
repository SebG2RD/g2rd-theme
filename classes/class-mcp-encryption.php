<?php
/**
 * MCP Encryption
 *
 * AES-256-GCM wrapper using native PHP OpenSSL. No external dependencies,
 * no WordPress dependencies — fully testable in isolation.
 *
 * Key derivation strategy:
 *   - If G2RD_MCP_ENCRYPTION_KEY is defined in wp-config.php (>= 32 chars), use it.
 *   - Otherwise derive from WordPress AUTH_KEY via HMAC-SHA256.
 * Context-specific subkeys prevent key reuse across operations.
 *
 * @package    G2RD
 * @since      1.12.0
 * @license    EUPL-1.2
 * @copyright  (c) 2026 Sebastien GERARD
 */

namespace G2RD;

/**
 * Handles all cryptographic operations for the MCP server.
 *
 * All methods are stateless. Never logs plaintexts or raw tokens.
 * Returns false on decryption error instead of throwing exceptions.
 */
class McpEncryption {

	/** @var string OpenSSL cipher algorithm. */
	private const CIPHER = 'aes-256-gcm';

	/** @var int GCM standard IV length in bytes. */
	private const IV_LENGTH = 12;

	/** @var int GCM authentication tag length in bytes. */
	private const TAG_LENGTH = 16;

	/** @var string Prefix for all context-specific subkey derivations. */
	private const KEY_CONTEXT_PREFIX = 'g2rd_mcp_';

	// ── Public API ────────────────────────────────────────────────────────────

	/**
	 * Encrypts plaintext using AES-256-GCM.
	 *
	 * Uses a fresh random IV for every call — identical plaintexts produce
	 * different ciphertexts. The GCM authentication tag guarantees integrity.
	 *
	 * Output format: base64( iv[12 bytes] . tag[16 bytes] . ciphertext )
	 *
	 * @param string $plaintext Data to encrypt.
	 * @return string Base64-encoded payload. Empty string on OpenSSL failure (should never occur).
	 */
	public function encrypt( string $plaintext ): string {
		$key = $this->derive_key( 'encryption' );
		$iv  = \random_bytes( self::IV_LENGTH );
		$tag = '';

		$ciphertext = \openssl_encrypt(
			$plaintext,
			self::CIPHER,
			$key,
			\OPENSSL_RAW_DATA,
			$iv,
			$tag,
			'',
			self::TAG_LENGTH
		);

		if ( false === $ciphertext ) {
			return '';
		}

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Encoding binary crypto output (IV + GCM tag + ciphertext) for safe text storage, not code obfuscation.
		return \base64_encode( $iv . $tag . $ciphertext );
	}

	/**
	 * Decrypts a payload produced by encrypt().
	 *
	 * Returns false if the base64 is malformed, the payload is too short,
	 * or the GCM authentication tag does not match (tampered data).
	 *
	 * @param string $ciphertext Base64-encoded payload from encrypt().
	 * @return string|false Decrypted plaintext or false on any failure.
	 */
	public function decrypt( string $ciphertext ): string|false {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Decoding binary crypto payload (IV + GCM tag + ciphertext), not executing obfuscated code.
		$raw = \base64_decode( $ciphertext, true );

		if ( false === $raw || \strlen( $raw ) < self::IV_LENGTH + self::TAG_LENGTH + 1 ) {
			return false;
		}

		$iv        = \substr( $raw, 0, self::IV_LENGTH );
		$tag       = \substr( $raw, self::IV_LENGTH, self::TAG_LENGTH );
		$encrypted = \substr( $raw, self::IV_LENGTH + self::TAG_LENGTH );
		$key       = $this->derive_key( 'encryption' );

		return \openssl_decrypt( $encrypted, self::CIPHER, $key, \OPENSSL_RAW_DATA, $iv, $tag );
	}

	/**
	 * Hashes a raw token for secure database storage using HMAC-SHA256.
	 *
	 * The returned hex string is what gets stored — the raw token is never persisted.
	 *
	 * @param string $raw_token Raw token value (the secret, never stored).
	 * @return string 64-character hex HMAC hash.
	 */
	public function hash_token( string $raw_token ): string {
		return \hash_hmac( 'sha256', $raw_token, $this->derive_key( 'token_hash' ) );
	}

	/**
	 * Verifies a raw token against its stored hash in constant time.
	 *
	 * Uses hash_equals() to prevent timing-based side-channel attacks.
	 *
	 * @param string $raw_token   Raw token submitted by the client.
	 * @param string $stored_hash Hash retrieved from the database.
	 * @return bool True if the token matches.
	 */
	public function verify_token_hash( string $raw_token, string $stored_hash ): bool {
		return \hash_equals( $this->hash_token( $raw_token ), $stored_hash );
	}

	/**
	 * Derives a context-specific 32-byte binary subkey from the master key.
	 *
	 * Each context ('encryption', 'token_hash', 'audit_chain', …) produces
	 * a different subkey, preventing key reuse across operations.
	 *
	 * @param string $context Subkey context identifier.
	 * @return string 32-byte binary key suitable for AES-256 / HMAC-SHA256.
	 */
	public function derive_key( string $context ): string {
		// Standard HKDF-like pattern: HMAC(key=secret, data=context_info).
		// The master key is the HMAC key (secret); context is the info (data).
		return \hash_hmac( 'sha256', self::KEY_CONTEXT_PREFIX . $context, $this->get_master_key(), true );
	}

	// ── Private helpers ───────────────────────────────────────────────────────

	/**
	 * Returns the master encryption key.
	 *
	 * Priority order:
	 * 1. G2RD_MCP_ENCRYPTION_KEY constant in wp-config.php (>= 32 chars).
	 * 2. Key derived from WordPress AUTH_KEY (present in every WP install).
	 *
	 * @return string Master key string.
	 */
	private function get_master_key(): string {
		if (
			\defined( 'G2RD_MCP_ENCRYPTION_KEY' )
			&& \is_string( \G2RD_MCP_ENCRYPTION_KEY )
			&& \strlen( \G2RD_MCP_ENCRYPTION_KEY ) >= 32
		) {
			return \G2RD_MCP_ENCRYPTION_KEY;
		}

		// AUTH_KEY is defined in every WordPress wp-config.php.
		// In test environments, define G2RD_MCP_ENCRYPTION_KEY or
		// ensure AUTH_KEY is defined in the PHPUnit bootstrap.
		$auth_key = \defined( 'AUTH_KEY' ) ? (string) \AUTH_KEY : 'g2rd_fallback_key_define_auth_key_in_tests';

		// AUTH_KEY is the HMAC key (secret); static string is the info (data).
		return \hash_hmac( 'sha256', 'g2rd_mcp_master_v1', $auth_key );
	}
}
