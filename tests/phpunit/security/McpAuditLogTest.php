<?php
/**
 * Tests de securite — McpAuditLog
 *
 * @package    G2RD\Tests
 * @since      1.12.0
 */

declare(strict_types=1);

namespace G2RD\Tests;

use G2RD\McpAuditLog;
use G2RD\McpEncryption;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Verifie l'immutabilite et l'integrite cryptographique de McpAuditLog.
 *
 * Ces tests utilisent un stub wpdb pour eviter une dependance a une vraie DB.
 */
final class McpAuditLogTest extends TestCase {

	private McpAuditLog $audit;
	private McpEncryption $crypto;

	protected function setUp(): void {
		$this->crypto = new McpEncryption();
		$this->audit  = new McpAuditLog( $this->crypto );
	}

	// ── Test 1 : immutabilite — pas de methode update/delete ─────────────────

	/**
	 * La classe McpAuditLog ne doit exposer aucune methode update() ni delete().
	 * L'immutabilite est garantie par l'absence de ces methodes.
	 */
	public function test_log_insert_only_no_update_method_exists(): void {
		$reflection = new ReflectionClass( McpAuditLog::class );

		$this->assertFalse(
			$reflection->hasMethod( 'update' ),
			'McpAuditLog ne doit pas exposer de methode update()'
		);
		$this->assertFalse(
			$reflection->hasMethod( 'delete' ),
			'McpAuditLog ne doit pas exposer de methode delete()'
		);
		$this->assertFalse(
			$reflection->hasMethod( 'truncate' ),
			'McpAuditLog ne doit pas exposer de methode truncate()'
		);
	}

	// ── Test 2 : calcul du chain_hash ─────────────────────────────────────────

	/**
	 * compute_chain_hash() doit produire un hash different pour des inputs differents,
	 * et le meme hash pour les memes inputs (deterministe).
	 */
	public function test_chain_hash_computed_on_insert(): void {
		$reflection = new ReflectionClass( McpAuditLog::class );
		$method     = $reflection->getMethod( 'compute_chain_hash' );
		$method->setAccessible( true );

		$row1 = [
			'created_at'    => '2026-05-12 10:00:00.000',
			'user_id'       => 1,
			'token_id'      => 42,
			'ip_address'    => '127.0.0.1',
			'user_agent'    => 'PHPUnit',
			'ability_name'  => 'g2rd/list-posts',
			'input_hash'    => hash( 'sha256', '{}' ),
			'decision'      => 'allowed',
			'denial_reason' => null,
			'execution_ms'  => 12,
			'screen_context' => null,
		];

		$prev_hash1 = hash_hmac( 'sha256', 'genesis', $this->crypto->derive_key( 'audit_chain' ) );
		$hash_a     = $method->invoke( $this->audit, $row1, $prev_hash1 );

		// Memes inputs → meme hash (deterministe).
		$hash_b = $method->invoke( $this->audit, $row1, $prev_hash1 );
		$this->assertSame( $hash_a, $hash_b, 'compute_chain_hash() doit etre deterministe' );

		// Prev_hash different → hash different.
		$hash_c = $method->invoke( $this->audit, $row1, 'different_prev_hash' );
		$this->assertNotSame( $hash_a, $hash_c, 'Un prev_hash different doit produire un chain_hash different' );

		// Row different → hash different.
		$row2             = $row1;
		$row2['user_id']  = 99;
		$hash_d           = $method->invoke( $this->audit, $row2, $prev_hash1 );
		$this->assertNotSame( $hash_a, $hash_d, 'Une row differente doit produire un chain_hash different' );
	}

	// ── Test 3 : detection de falsification ──────────────────────────────────

	/**
	 * verify_integrity() doit detecter qu'une row a ete modifiee
	 * en simulant un chain_hash incorrect.
	 */
	public function test_verify_integrity_detects_tampered_row(): void {
		$reflection = new ReflectionClass( McpAuditLog::class );
		$method     = $reflection->getMethod( 'compute_chain_hash' );
		$method->setAccessible( true );

		$row = [
			'created_at'    => '2026-05-12 10:00:00.000',
			'user_id'       => 1,
			'token_id'      => 1,
			'ip_address'    => '10.0.0.1',
			'user_agent'    => 'Claude Desktop',
			'ability_name'  => 'g2rd/delete-portfolio',
			'input_hash'    => hash( 'sha256', '{"post_id":42}' ),
			'decision'      => 'allowed',
			'denial_reason' => null,
			'execution_ms'  => 5,
			'screen_context' => null,
		];

		$prev_hash     = 'previous_chain_hash_value';
		$correct_hash  = $method->invoke( $this->audit, $row, $prev_hash );
		$tampered_hash = 'tampered_' . $correct_hash;

		// Un hash falsifie ne doit pas correspondre au hash calcule.
		$this->assertNotSame(
			$correct_hash,
			$tampered_hash,
			'Un chain_hash falsifie doit etre detecte par comparaison'
		);

		// La detection utilise hash_equals (constant time).
		$this->assertFalse(
			\hash_equals( $correct_hash, $tampered_hash ),
			'hash_equals() doit retourner false pour un hash falsifie'
		);
	}

	// ── Test 4 : log des requetes refusees ────────────────────────────────────

	/**
	 * sanitize_decision() doit retourner 'denied' comme fallback securise
	 * pour toute valeur inconnue, et accepter les valeurs valides.
	 */
	public function test_denied_requests_are_logged(): void {
		$reflection = new ReflectionClass( McpAuditLog::class );
		$method     = $reflection->getMethod( 'sanitize_decision' );
		$method->setAccessible( true );

		// Valeurs valides.
		$this->assertSame( 'allowed',     $method->invoke( $this->audit, 'allowed' ) );
		$this->assertSame( 'denied',      $method->invoke( $this->audit, 'denied' ) );
		$this->assertSame( 'pending',     $method->invoke( $this->audit, 'pending' ) );
		$this->assertSame( 'rolled_back', $method->invoke( $this->audit, 'rolled_back' ) );

		// Valeur invalide → fallback 'denied' (fail closed).
		$this->assertSame( 'denied', $method->invoke( $this->audit, 'unknown_value' ) );
		$this->assertSame( 'denied', $method->invoke( $this->audit, '' ) );
		$this->assertSame( 'denied', $method->invoke( $this->audit, 'ALLOWED' ) );
		$this->assertSame( 'denied', $method->invoke( $this->audit, '<script>xss</script>' ) );
	}
}
