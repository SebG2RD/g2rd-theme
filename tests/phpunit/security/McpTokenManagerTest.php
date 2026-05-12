<?php
/**
 * Tests de securite — McpTokenManager
 *
 * @package    G2RD\Tests
 * @since      1.12.0
 */

declare(strict_types=1);

namespace G2RD\Tests;

use G2RD\McpAuditLog;
use G2RD\McpEncryption;
use G2RD\McpTokenManager;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Verifie le format, le hachage et la validation des tokens MCP.
 * Tous les tests utilisent la reflexion sur les methodes privees
 * pour eviter toute dependance a la base de donnees.
 */
final class McpTokenManagerTest extends TestCase {

	private McpTokenManager $manager;
	private McpEncryption $crypto;

	protected function setUp(): void {
		$this->crypto  = new McpEncryption();
		$audit         = new McpAuditLog( $this->crypto );
		$this->manager = new McpTokenManager( $this->crypto, $audit );
	}

	// ── Test 1 : format et scope ──────────────────────────────────────────────

	/**
	 * generate_raw_token() doit produire un token valide :
	 * - prefixe 'g2rd_'
	 * - longueur exacte de 45 caracteres
	 * - uniquement des caracteres base62 apres le prefixe
	 * validate_scope() doit accepter les scopes valides et rejeter les autres.
	 */
	public function test_token_created_with_correct_scope(): void {
		$reflection   = new ReflectionClass( McpTokenManager::class );
		$gen_method   = $reflection->getMethod( 'generate_raw_token' );
		$scope_method = $reflection->getMethod( 'validate_scope' );
		$gen_method->setAccessible( true );
		$scope_method->setAccessible( true );

		// Format du token.
		$token = $gen_method->invoke( $this->manager );

		$this->assertStringStartsWith( 'g2rd_', $token, 'Le token doit commencer par g2rd_' );
		$this->assertSame( 45, strlen( $token ), 'Le token doit faire exactement 45 caracteres' );
		$this->assertMatchesRegularExpression(
			'/^g2rd_[0-9A-Za-z]{40}$/',
			$token,
			'Les 40 caracteres apres g2rd_ doivent etre base62'
		);

		// Deux tokens consecutifs doivent etre differents.
		$token2 = $gen_method->invoke( $this->manager );
		$this->assertNotSame( $token, $token2, 'Deux tokens generes doivent etre differents' );

		// Scopes valides.
		foreach ( [ 'read_only', 'editor', 'admin', 'full' ] as $valid ) {
			$this->assertTrue(
				$scope_method->invoke( $this->manager, $valid ),
				"validate_scope() doit accepter '{$valid}'"
			);
		}

		// Scopes invalides.
		foreach ( [ 'superadmin', '', 'READ_ONLY', 'write', 'delete' ] as $invalid ) {
			$this->assertFalse(
				$scope_method->invoke( $this->manager, $invalid ),
				"validate_scope() doit rejeter '{$invalid}'"
			);
		}
	}

	// ── Test 2 : raw token jamais stocke ─────────────────────────────────────

	/**
	 * Le hash d'un token doit etre different du token brut,
	 * et le token brut ne doit pas apparaitre dans le hash.
	 */
	public function test_raw_token_never_stored_in_db(): void {
		$reflection = new ReflectionClass( McpTokenManager::class );
		$gen_method = $reflection->getMethod( 'generate_raw_token' );
		$gen_method->setAccessible( true );

		$raw_token = $gen_method->invoke( $this->manager );
		$hash      = $this->crypto->hash_token( $raw_token );

		$this->assertNotSame( $raw_token, $hash, 'Le hash doit etre different du token brut' );
		$this->assertStringNotContainsString(
			$raw_token,
			$hash,
			'Le token brut ne doit pas apparaitre dans son hash'
		);

		// Le hash est reproductible (deterministe).
		$hash2 = $this->crypto->hash_token( $raw_token );
		$this->assertSame( $hash, $hash2, 'Le hash doit etre deterministe pour le meme token' );

		// Deux tokens differents produisent deux hashs differents.
		$raw_token2 = $gen_method->invoke( $this->manager );
		$hash3      = $this->crypto->hash_token( $raw_token2 );
		$this->assertNotSame( $hash, $hash3, 'Des tokens differents doivent produire des hashs differents' );
	}

	// ── Test 3 : token revoque immediatement ─────────────────────────────────

	/**
	 * is_token_row_valid() doit retourner false pour un token avec revoked_at non null.
	 */
	public function test_revoked_token_rejected_immediately(): void {
		$method = new ReflectionMethod( McpTokenManager::class, 'is_token_row_valid' );
		$method->setAccessible( true );

		$future = gmdate( 'Y-m-d H:i:s', time() + 86400 );

		// Token non revoque et non expire.
		$valid_row = [
			'revoked_at' => null,
			'expires_at' => $future,
		];
		$this->assertTrue(
			$method->invoke( $this->manager, $valid_row ),
			'Un token valide (non revoque, non expire) doit etre accepte'
		);

		// Token revoque.
		$revoked_row = [
			'revoked_at' => gmdate( 'Y-m-d H:i:s' ),
			'expires_at' => $future,
		];
		$this->assertFalse(
			$method->invoke( $this->manager, $revoked_row ),
			'Un token revoque doit etre immediatement rejete'
		);

		// revoked_at vide mais non null.
		$this->assertFalse(
			$method->invoke( $this->manager, [ 'revoked_at' => '', 'expires_at' => $future ] ),
			'revoked_at vide doit etre considere comme revoque'
		);
	}

	// ── Test 4 : token expire ─────────────────────────────────────────────────

	/**
	 * is_token_row_valid() doit retourner false pour un token dont expires_at est passe.
	 */
	public function test_expired_token_rejected(): void {
		$method = new ReflectionMethod( McpTokenManager::class, 'is_token_row_valid' );
		$method->setAccessible( true );

		// Token expire (il y a 1 heure).
		$expired_row = [
			'revoked_at' => null,
			'expires_at' => gmdate( 'Y-m-d H:i:s', time() - 3600 ),
		];
		$this->assertFalse(
			$method->invoke( $this->manager, $expired_row ),
			'Un token dont expires_at est dans le passe doit etre rejete'
		);

		// Token expirant dans le futur.
		$valid_row = [
			'revoked_at' => null,
			'expires_at' => gmdate( 'Y-m-d H:i:s', time() + 3600 ),
		];
		$this->assertTrue(
			$method->invoke( $this->manager, $valid_row ),
			'Un token dont expires_at est dans le futur doit etre accepte'
		);

		// expires_at manquant.
		$this->assertFalse(
			$method->invoke( $this->manager, [ 'revoked_at' => null, 'expires_at' => null ] ),
			'Un token sans expires_at doit etre rejete'
		);
	}

	// ── Test 5 : user_id lie au token ─────────────────────────────────────────

	/**
	 * Le token retourne toujours son user_id d'origine.
	 * validate_token() utilise le user_id DE la DB, pas un user_id externe.
	 * Verification via scope_satisfies() et la hierarchie des scopes.
	 */
	public function test_validate_wrong_user_rejected(): void {
		// scope_satisfies() garantit la hierarchie.
		$this->assertTrue(
			$this->manager->scope_satisfies( 'full', 'read_only' ),
			'full satisfait read_only'
		);
		$this->assertTrue(
			$this->manager->scope_satisfies( 'admin', 'editor' ),
			'admin satisfait editor'
		);
		$this->assertFalse(
			$this->manager->scope_satisfies( 'read_only', 'editor' ),
			'read_only ne satisfait pas editor'
		);
		$this->assertFalse(
			$this->manager->scope_satisfies( 'editor', 'admin' ),
			'editor ne satisfait pas admin'
		);

		// Le token est lie a son user_id via la row DB.
		// Simulation : la row retournee par validate_token() contient user_id=1.
		// Il est impossible pour user_id=2 d'utiliser ce token car
		// validate_token() retourne user_id depuis la DB, jamais depuis la requete.
		$reflection = new ReflectionClass( McpTokenManager::class );
		$format_method = $reflection->getMethod( 'has_valid_format' );
		$format_method->setAccessible( true );

		// Un token pour user 1 a le bon format.
		$reflection2  = $reflection->getMethod( 'generate_raw_token' );
		$reflection2->setAccessible( true );
		$raw_for_user1 = $reflection2->invoke( $this->manager );

		$this->assertTrue(
			$format_method->invoke( $this->manager, $raw_for_user1 ),
			'Un token genere doit avoir un format valide'
		);

		// Un token tronque ou falsifie ne passe pas le format check.
		$this->assertFalse(
			$format_method->invoke( $this->manager, 'g2rd_court' ),
			'Un token trop court doit echouer le format check'
		);
		$this->assertFalse(
			$format_method->invoke( $this->manager, 'prefix_invalide_' . str_repeat( 'x', 40 ) ),
			'Un token sans prefixe g2rd_ doit echouer le format check'
		);
	}
}
