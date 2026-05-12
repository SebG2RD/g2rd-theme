<?php
/**
 * Tests de securite — McpRateLimiter
 *
 * @package    G2RD\Tests
 * @since      1.12.0
 */

declare(strict_types=1);

namespace G2RD\Tests;

use G2RD\McpEncryption;
use G2RD\McpRateLimiter;
use PHPUnit\Framework\TestCase;

/**
 * Verifie les trois buckets independants du rate limiter.
 *
 * Les transients WordPress sont stubbes dans bootstrap.php via un tableau
 * en memoire, donc aucune dependance a la base de donnees.
 */
final class McpRateLimiterTest extends TestCase {

	private McpRateLimiter $limiter;

	protected function setUp(): void {
		// Vider le store transient avant chaque test pour isolation complete.
		global $g2rd_transient_store;
		$g2rd_transient_store = [];

		$crypto        = new McpEncryption();
		$this->limiter = new McpRateLimiter( $crypto );
	}

	// ── Test 1 : bucket global requests ──────────────────────────────────────

	/**
	 * check_requests() doit autoriser jusqu'a REQUESTS_PER_MINUTE requetes
	 * puis bloquer la suivante.
	 */
	public function test_requests_bucket_blocks_after_limit(): void {
		$ip    = '192.168.1.1';
		$limit = McpRateLimiter::REQUESTS_PER_MINUTE;

		// Consommer toutes les requetes du bucket.
		for ( $i = 0; $i < $limit; $i++ ) {
			$this->assertTrue(
				$this->limiter->check_requests( $ip ),
				"La requete #{$i} doit etre autorisee"
			);
		}

		// La requete suivante depasse la limite.
		$this->assertFalse(
			$this->limiter->check_requests( $ip ),
			'La requete apres la limite doit etre bloquee'
		);

		// remaining_requests() doit retourner 0.
		$this->assertSame(
			0,
			$this->limiter->remaining_requests( $ip ),
			'remaining_requests() doit retourner 0 quand le bucket est vide'
		);
	}

	// ── Test 2 : lockout apres echecs d'auth ─────────────────────────────────

	/**
	 * record_auth_failure() doit retourner true jusqu'au seuil,
	 * puis false. is_locked_out() doit reflechir cet etat.
	 */
	public function test_auth_failures_trigger_lockout(): void {
		$ip        = '10.0.0.5';
		$threshold = McpRateLimiter::AUTH_FAILURE_THRESHOLD;

		// Les premiers echecs restent en-dessous du seuil.
		for ( $i = 1; $i < $threshold; $i++ ) {
			$this->assertTrue(
				$this->limiter->record_auth_failure( $ip ),
				"L'echec #{$i} ne doit pas encore declencher le lockout"
			);
			$this->assertFalse(
				$this->limiter->is_locked_out( $ip ),
				"L'IP ne doit pas encore etre bloquee apres {$i} echec(s)"
			);
		}

		// Le dernier echec atteint exactement le seuil (retourne encore true).
		$this->assertTrue(
			$this->limiter->record_auth_failure( $ip ),
			'Le {$threshold}e echec doit retourner true (seuil atteint mais pas depasse)'
		);

		// L'echec suivant depasse le seuil → lockout.
		$this->assertFalse(
			$this->limiter->record_auth_failure( $ip ),
			'Un echec supplementaire doit retourner false (lockout actif)'
		);
		$this->assertTrue(
			$this->limiter->is_locked_out( $ip ),
			'is_locked_out() doit retourner true apres le seuil'
		);

		// Apres une auth reussie, le lockout est leve.
		$this->limiter->reset_auth_failures( $ip );
		$this->assertFalse(
			$this->limiter->is_locked_out( $ip ),
			'is_locked_out() doit retourner false apres reset_auth_failures()'
		);
	}

	// ── Test 3 : buckets requests et destructive sont independants ────────────

	/**
	 * Le bucket destructive est independant du bucket global.
	 * Saturer le bucket destructive ne doit pas affecter le bucket requests.
	 */
	public function test_destructive_bucket_independent_from_requests(): void {
		$ip = '172.16.0.1';

		// Saturer le bucket destructive.
		for ( $i = 0; $i < McpRateLimiter::DESTRUCTIVE_PER_MINUTE; $i++ ) {
			$this->assertTrue(
				$this->limiter->check_destructive( $ip ),
				"L'operation destructive #{$i} doit etre autorisee"
			);
		}

		// Le bucket destructive est plein.
		$this->assertFalse(
			$this->limiter->check_destructive( $ip ),
			'check_destructive() doit bloquer apres la limite'
		);

		// Le bucket global reste intact.
		$this->assertTrue(
			$this->limiter->check_requests( $ip ),
			'check_requests() doit rester disponible meme quand check_destructive() est plein'
		);
	}

	// ── Test 4 : consume retourne false exactement a la limite ────────────────

	/**
	 * La 61e requete (>= limite) doit etre refusee, pas la 60e.
	 * Verifie que la comparaison est >= et non >.
	 */
	public function test_consume_blocks_at_limit_not_beyond(): void {
		$ip    = '203.0.113.1';
		$limit = McpRateLimiter::REQUESTS_PER_MINUTE;

		// Requetes 1 a limit : toutes autorisees.
		for ( $i = 1; $i <= $limit; $i++ ) {
			$allowed = $this->limiter->check_requests( $ip );
			$this->assertTrue( $allowed, "La requete #{$i} sur {$limit} doit etre autorisee" );
		}

		// Requete limit+1 : refusee.
		$this->assertFalse(
			$this->limiter->check_requests( $ip ),
			"La requete numero " . ( $limit + 1 ) . " doit etre refusee (>= limite)"
		);

		// IP differente : bucket independant, encore autorisee.
		$other_ip = '203.0.113.2';
		$this->assertTrue(
			$this->limiter->check_requests( $other_ip ),
			'Une IP differente doit avoir son propre bucket independant'
		);
	}
}
