<?php
/**
 * Tests de securite — McpEncryption
 *
 * @package    G2RD\Tests
 * @since      1.12.0
 */

declare(strict_types=1);

namespace G2RD\Tests;

use G2RD\McpEncryption;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Verifie le comportement cryptographique de McpEncryption.
 */
final class McpEncryptionTest extends TestCase {

	private McpEncryption $crypto;

	protected function setUp(): void {
		$this->crypto = new McpEncryption();
	}

	// ── Test 1 : chiffrement/dechiffrement ────────────────────────────────────

	/**
	 * Un plaintext chiffre puis dechiffre doit etre identique a l'original.
	 */
	public function test_encrypt_decrypt_roundtrip(): void {
		$plaintext  = 'g2rd_mcp_test_secret_payload_123!@#';
		$ciphertext = $this->crypto->encrypt( $plaintext );

		$this->assertNotEmpty( $ciphertext, 'encrypt() ne doit pas retourner une chaine vide' );
		$this->assertNotSame( $plaintext, $ciphertext, 'Le ciphertext ne doit pas etre identique au plaintext' );

		$decrypted = $this->crypto->decrypt( $ciphertext );

		$this->assertSame( $plaintext, $decrypted, 'Le plaintext dechiffre doit etre identique a l\'original' );
	}

	// ── Test 2 : IV aleatoire ─────────────────────────────────────────────────

	/**
	 * Deux chiffrements du meme plaintext doivent produire des ciphertexts differents
	 * car chaque appel genere un IV aleatoire unique.
	 */
	public function test_different_iv_each_encryption(): void {
		$plaintext   = 'meme_plaintext_deux_fois';
		$ciphertext1 = $this->crypto->encrypt( $plaintext );
		$ciphertext2 = $this->crypto->encrypt( $plaintext );

		$this->assertNotSame(
			$ciphertext1,
			$ciphertext2,
			'Deux chiffrements du meme plaintext doivent produire des ciphertexts differents (IV aleatoire)'
		);

		// Les deux doivent neanmoins se dechiffrer correctement.
		$this->assertSame( $plaintext, $this->crypto->decrypt( $ciphertext1 ) );
		$this->assertSame( $plaintext, $this->crypto->decrypt( $ciphertext2 ) );
	}

	// ── Test 3 : detection de falsification ──────────────────────────────────

	/**
	 * Modifier un seul byte du ciphertext doit invalider le tag GCM
	 * et faire retourner false a decrypt().
	 */
	public function test_tampered_ciphertext_returns_false(): void {
		$ciphertext = $this->crypto->encrypt( 'donnee_sensible' );
		$raw        = base64_decode( $ciphertext, true );

		$this->assertNotFalse( $raw, 'Le ciphertext doit etre du base64 valide' );

		// On modifie le dernier byte du ciphertext (apres IV + tag).
		$tampered    = $raw;
		$last_offset = strlen( $tampered ) - 1;
		$tampered[ $last_offset ] = chr( ( ord( $tampered[ $last_offset ] ) + 1 ) % 256 );

		$result = $this->crypto->decrypt( base64_encode( $tampered ) );

		$this->assertFalse(
			$result,
			'decrypt() doit retourner false si le ciphertext a ete falsifie'
		);
	}

	// ── Test 4 : constant-time comparison ────────────────────────────────────

	/**
	 * verify_token_hash() doit utiliser hash_equals() (comparaison en temps constant)
	 * et retourner true uniquement pour le bon token.
	 */
	public function test_hash_token_constant_time(): void {
		// Verification comportementale : seul le bon token passe.
		$raw_token = 'g2rd_AbCdEfGhIjKlMnOpQrStUvWxYz0123456789ab';
		$hash      = $this->crypto->hash_token( $raw_token );

		$this->assertTrue(
			$this->crypto->verify_token_hash( $raw_token, $hash ),
			'verify_token_hash() doit retourner true pour le bon token'
		);

		$this->assertFalse(
			$this->crypto->verify_token_hash( $raw_token . 'x', $hash ),
			'verify_token_hash() doit retourner false pour un token modifie'
		);

		$this->assertFalse(
			$this->crypto->verify_token_hash( 'autre_token', $hash ),
			'verify_token_hash() doit retourner false pour un token different'
		);

		// Verification structurelle : hash_equals est appele dans la methode.
		$reflection = new ReflectionMethod( McpEncryption::class, 'verify_token_hash' );
		$filename   = $reflection->getFileName();
		$start      = $reflection->getStartLine();
		$end        = $reflection->getEndLine();

		$this->assertNotFalse( $filename );
		$lines   = file( $filename );
		$body    = implode( '', array_slice( $lines, $start - 1, $end - $start + 1 ) );

		$this->assertStringContainsString(
			'hash_equals',
			$body,
			'verify_token_hash() doit utiliser hash_equals() pour la comparaison en temps constant'
		);
	}
}
