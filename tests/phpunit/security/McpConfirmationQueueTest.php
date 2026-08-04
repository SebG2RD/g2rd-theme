<?php
/**
 * Tests — McpConfirmationQueue (write-ability confirmation queue)
 *
 * Uses:
 *   - Reflection to test private methods (generate_token, is_expired)
 *   - $wpdb stub (G2rdWpdbStub) controlled via globals for DB assertions
 *   - $g2rd_post_store for wp_insert_post / wp_update_post
 *   - $g2rd_wp_mail_log to assert email dispatch
 *   - McpEncryption (real instance) for encrypt/decrypt roundtrip
 *
 * @package    G2RD\Tests
 * @since      1.12.0
 */

declare(strict_types=1);

namespace G2RD\Tests;

use G2RD\McpConfirmationQueue;
use G2RD\McpEncryption;
use G2RD\McpAuditLog;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Verifies McpConfirmationQueue token generation, TTL, encrypt/decrypt, and DB operations.
 */
final class McpConfirmationQueueTest extends TestCase {

	private McpConfirmationQueue $queue;
	private McpEncryption $crypto;
	private ReflectionClass $ref;

	protected function setUp(): void {
		global $wpdb, $g2rd_post_store, $g2rd_wp_mail_log,
			$g2rd_wpdb_insert_return, $g2rd_wpdb_update_return,
			$g2rd_wpdb_get_row_return, $g2rd_user_can,
			$g2rd_current_user_id, $g2rd_wp_insert_post_result,
			$g2rd_wp_update_post_result;

		// Reset all controllable globals.
		$g2rd_post_store            = [];
		$g2rd_wp_mail_log           = [];
		$g2rd_wpdb_insert_return    = true;
		$g2rd_wpdb_update_return    = 1;
		$g2rd_wpdb_get_row_return   = null;
		$g2rd_user_can              = true;
		$g2rd_current_user_id       = 0;
		$g2rd_wp_insert_post_result = null;
		$g2rd_wp_update_post_result = null;

		// Reset wpdb spy state.
		$wpdb->inserts                 = [];
		$wpdb->updates                 = [];
		$wpdb->insert_id               = 0;
		$wpdb->last_error              = '';
		$wpdb->max_arguments_enc_bytes = null; // null = LONGTEXT (schema >= 1.1.0).

		$this->crypto = new McpEncryption();

		$audit_mock = $this->getMockBuilder( McpAuditLog::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'log' ] )
			->getMock();
		$audit_mock->method( 'log' )->willReturn( 1 );

		$this->queue = new McpConfirmationQueue( $this->crypto, $audit_mock );
		$this->ref   = new ReflectionClass( McpConfirmationQueue::class );
	}

	/** Calls a private method via reflection. */
	private function call_private( string $method, array $args = [] ): mixed {
		$m = $this->ref->getMethod( $method );
		$m->setAccessible( true );
		return $m->invokeArgs( $this->queue, $args );
	}

	// ── Test 1 : generate_token ───────────────────────────────────────────────

	/**
	 * generate_token() returns a 64-char lowercase hex string.
	 */
	public function test_generate_token_returns_64_hex_chars(): void {
		$token = $this->call_private( 'generate_token' );

		$this->assertIsString( $token );
		$this->assertSame( 64, strlen( $token ) );
		$this->assertMatchesRegularExpression( '/^[0-9a-f]{64}$/', $token );
	}

	/**
	 * Two consecutive generate_token() calls return different values.
	 */
	public function test_generate_token_is_unique(): void {
		$a = $this->call_private( 'generate_token' );
		$b = $this->call_private( 'generate_token' );

		$this->assertNotSame( $a, $b );
	}

	// ── Test 2 : is_expired ───────────────────────────────────────────────────

	/**
	 * is_expired() returns false for an expires_at in the future.
	 */
	public function test_is_expired_future_returns_false(): void {
		$entry = [ 'expires_at' => gmdate( 'Y-m-d H:i:s', time() + 900 ) ];

		$result = $this->call_private( 'is_expired', [ $entry ] );

		$this->assertFalse( $result );
	}

	/**
	 * is_expired() returns true for an expires_at in the past.
	 */
	public function test_is_expired_past_returns_true(): void {
		$entry = [ 'expires_at' => gmdate( 'Y-m-d H:i:s', time() - 1 ) ];

		$result = $this->call_private( 'is_expired', [ $entry ] );

		$this->assertTrue( $result );
	}

	// ── Test 3 : encrypt / decrypt roundtrip ─────────────────────────────────

	/**
	 * McpEncryption encrypt/decrypt roundtrip returns the original payload.
	 * Validates the crypto layer that the queue depends on.
	 */
	public function test_encryption_roundtrip(): void {
		$payload   = wp_json_encode( [ 'title' => 'Test post', 'status' => 'draft' ] );
		$encrypted = $this->crypto->encrypt( (string) $payload );

		$this->assertIsString( $encrypted );
		$this->assertNotSame( $payload, $encrypted );

		$decrypted = $this->crypto->decrypt( $encrypted );

		$this->assertSame( $payload, $decrypted );
	}

	// ── Test 4 : enqueue ─────────────────────────────────────────────────────

	/**
	 * enqueue() inserts a row into the DB and returns confirm/reject tokens.
	 */
	public function test_enqueue_inserts_row_and_returns_tokens(): void {
		global $wpdb;

		$result = $this->queue->enqueue(
			1,
			1,
			'127.0.0.1',
			'g2rd_create-post',
			[ 'title' => 'Hello World', 'status' => 'draft' ]
		);

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'confirm_token', $result );
		$this->assertArrayHasKey( 'reject_token', $result );
		$this->assertArrayHasKey( 'expires_at', $result );

		// Confirm token format.
		$this->assertMatchesRegularExpression( '/^[0-9a-f]{64}$/', $result['confirm_token'] );
		$this->assertMatchesRegularExpression( '/^[0-9a-f]{64}$/', $result['reject_token'] );
		$this->assertNotSame( $result['confirm_token'], $result['reject_token'] );

		// One DB insert must have happened.
		$this->assertCount( 1, $wpdb->inserts );
		$this->assertStringContainsString( 'g2rd_mcp_confirmation_queue', $wpdb->inserts[0]['table'] );
	}

	/**
	 * enqueue() sends an email after a successful DB insert.
	 */
	public function test_enqueue_sends_email(): void {
		global $g2rd_wp_mail_log;

		$this->queue->enqueue( 1, 1, '127.0.0.1', 'g2rd_create-post', [ 'title' => 'Test' ] );

		$this->assertCount( 1, $g2rd_wp_mail_log );
		$this->assertSame( 'admin@example.com', $g2rd_wp_mail_log[0]['to'] );
		$this->assertStringContainsString( 'g2rd_create-post', $g2rd_wp_mail_log[0]['message'] );
	}

	/**
	 * enqueue() returns false when the DB insert fails.
	 */
	public function test_enqueue_returns_false_on_db_failure(): void {
		global $g2rd_wpdb_insert_return;
		$g2rd_wpdb_insert_return = false;

		$result = $this->queue->enqueue( 1, 1, '127.0.0.1', 'g2rd_create-post', [ 'title' => 'Fail' ] );

		$this->assertFalse( $result );
	}

	// ── Test 4b : large payloads ──────────────────────────────────────────────

	/**
	 * A 1 MB payload survives the whole flow: enqueue → email → confirm → execute.
	 *
	 * This is the regression guard for the LONGTEXT migration (schema 1.1.0):
	 * arguments_enc used to be TEXT, whose 65 535-BYTE ceiling rejected any
	 * operation above ~49 KB of JSON once base64 encryption inflated it by 4/3.
	 */
	public function test_enqueue_and_confirm_one_megabyte_payload(): void {
		global $wpdb, $g2rd_wp_mail_log, $g2rd_wpdb_get_row_return,
			$g2rd_wpdb_update_return, $g2rd_post_store;

		$content = str_repeat( 'A', 1024 * 1024 );

		$post              = new \WP_Post();
		$post->ID          = 42;
		$post->post_type   = 'post';
		$post->post_title  = 'Large page';
		$g2rd_post_store[42] = $post;

		// 1. Enqueue.
		$handles = $this->queue->enqueue(
			1,
			1,
			'127.0.0.1',
			'g2rd_update-post',
			[ 'post_id' => 42, 'content' => $content ]
		);

		$this->assertIsArray( $handles, $this->queue->get_last_error() );
		$this->assertCount( 1, $wpdb->inserts );

		// The stored ciphertext is larger than the old TEXT column could hold.
		$stored = (string) $wpdb->inserts[0]['data']['arguments_enc'];
		$this->assertGreaterThan( 65535, strlen( $stored ) );

		// 2. Email.
		$this->assertCount( 1, $g2rd_wp_mail_log );
		$this->assertStringContainsString( $handles['confirm_token'], $g2rd_wp_mail_log[0]['message'] );

		// 3. Confirm — replay the row exactly as it was written.
		$row                      = $wpdb->inserts[0]['data'];
		$row['id']                = 7;
		$g2rd_wpdb_get_row_return = $row;
		$g2rd_wpdb_update_return  = 1;

		$this->assertTrue( $this->queue->confirm( $handles['confirm_token'] ) );

		// 4. Execution — the post carries the full 1 MB, not a truncated copy.
		$this->assertSame( $content, $g2rd_post_store[42]->post_content );
	}

	/**
	 * The historical failure is reproduced when the column is capped at 65 535 bytes.
	 *
	 * Proves the diagnosis: with a TEXT-sized column a 61 KB payload is refused
	 * by wpdb (insert returns false) and no confirmation email is ever sent.
	 */
	public function test_enqueue_fails_on_text_sized_column_and_sends_no_email(): void {
		global $wpdb, $g2rd_wp_mail_log;

		$wpdb->max_arguments_enc_bytes = 65535; // Pre-1.1.0 TEXT column.

		$result = $this->queue->enqueue(
			1,
			1,
			'127.0.0.1',
			'g2rd_update-post',
			[ 'post_id' => 42, 'content' => str_repeat( 'B', 62000 ) ]
		);

		$this->assertFalse( $result );
		$this->assertStringContainsString( 'Database insert failed', $this->queue->get_last_error() );
		$this->assertEmpty( $g2rd_wp_mail_log );
	}

	/**
	 * A payload above MAX_ARGUMENTS_BYTES is refused before reaching the database,
	 * with an error naming both the received size and the limit.
	 */
	public function test_enqueue_rejects_oversized_payload_with_explicit_error(): void {
		global $wpdb, $g2rd_wp_mail_log;

		$result = $this->queue->enqueue(
			1,
			1,
			'127.0.0.1',
			'g2rd_update-post',
			[ 'post_id' => 42, 'content' => str_repeat( 'C', 4 * 1024 * 1024 ) ]
		);

		$this->assertFalse( $result );

		$error = $this->queue->get_last_error();
		$this->assertStringContainsString( 'too large', $error );
		$this->assertStringContainsString( number_format_i18n( 3145728 ), $error );

		// Nothing was attempted: no insert, no email.
		$this->assertCount( 0, $wpdb->inserts );
		$this->assertEmpty( $g2rd_wp_mail_log );
	}

	/**
	 * enqueue() surfaces $wpdb->last_error instead of failing mutely.
	 */
	public function test_enqueue_reports_wpdb_last_error(): void {
		global $wpdb, $g2rd_wpdb_insert_return;

		$g2rd_wpdb_insert_return = false;
		$wpdb->last_error        = "Data too long for column 'arguments_enc' at row 1";

		$result = $this->queue->enqueue( 1, 1, '127.0.0.1', 'g2rd_update-post', [ 'post_id' => 42 ] );

		$this->assertFalse( $result );
		$this->assertStringContainsString( "Data too long for column 'arguments_enc'", $this->queue->get_last_error() );
	}

	// ── Test 5 : confirm (create-post path) ───────────────────────────────────

	/**
	 * confirm() returns false for an unknown token.
	 */
	public function test_confirm_returns_false_for_unknown_token(): void {
		$result = $this->queue->confirm( str_repeat( 'a', 64 ) );

		$this->assertFalse( $result );
	}

	/**
	 * confirm() returns false for an expired entry.
	 */
	public function test_confirm_returns_false_for_expired_entry(): void {
		global $g2rd_wpdb_get_row_return;
		$g2rd_wpdb_get_row_return = [
			'id'            => 1,
			'confirm_token' => str_repeat( 'b', 64 ),
			'reject_token'  => str_repeat( 'c', 64 ),
			'status'        => 'pending',
			'expires_at'    => gmdate( 'Y-m-d H:i:s', time() - 1 ),
			'ability_name'  => 'g2rd_create-post',
			'arguments_enc' => '',
			'user_id'       => 1,
			'token_id'      => 1,
			'ip_address'    => '127.0.0.1',
		];

		$result = $this->queue->confirm( str_repeat( 'b', 64 ) );

		$this->assertFalse( $result );
	}

	/**
	 * confirm() returns false when the entry is already resolved (status != 'pending').
	 */
	public function test_confirm_returns_false_for_already_resolved_entry(): void {
		global $g2rd_wpdb_get_row_return;
		$g2rd_wpdb_get_row_return = [
			'id'            => 2,
			'confirm_token' => str_repeat( 'd', 64 ),
			'reject_token'  => str_repeat( 'e', 64 ),
			'status'        => 'confirmed',
			'expires_at'    => gmdate( 'Y-m-d H:i:s', time() + 900 ),
			'ability_name'  => 'g2rd_create-post',
			'arguments_enc' => '',
			'user_id'       => 1,
			'token_id'      => 1,
			'ip_address'    => '127.0.0.1',
		];

		$result = $this->queue->confirm( str_repeat( 'd', 64 ) );

		$this->assertFalse( $result );
	}

	/**
	 * confirm() executes the write op and returns true for a valid pending entry.
	 */
	public function test_confirm_executes_create_post_and_returns_true(): void {
		global $g2rd_wpdb_get_row_return, $g2rd_wpdb_update_return;

		$arguments     = [ 'title' => 'Created via MCP', 'status' => 'draft', 'post_type' => 'post' ];
		$arguments_enc = (string) $this->crypto->encrypt( (string) wp_json_encode( $arguments ) );

		$g2rd_wpdb_get_row_return = [
			'id'            => 3,
			'confirm_token' => str_repeat( 'f', 64 ),
			'reject_token'  => str_repeat( '0', 64 ),
			'status'        => 'pending',
			'expires_at'    => gmdate( 'Y-m-d H:i:s', time() + 900 ),
			'ability_name'  => 'g2rd_create-post',
			'arguments_enc' => $arguments_enc,
			'user_id'       => 1,
			'token_id'      => 1,
			'ip_address'    => '127.0.0.1',
		];
		$g2rd_wpdb_update_return = 1;

		$result = $this->queue->confirm( str_repeat( 'f', 64 ) );

		$this->assertTrue( $result );
	}

	/**
	 * confirm() returns false when the race-condition guard fires (0 rows updated).
	 */
	public function test_confirm_returns_false_on_race_condition(): void {
		global $g2rd_wpdb_get_row_return, $g2rd_wpdb_update_return;

		$arguments_enc            = (string) $this->crypto->encrypt( (string) wp_json_encode( [] ) );
		$g2rd_wpdb_update_return  = 0; // Another request already resolved this.
		$g2rd_wpdb_get_row_return = [
			'id'            => 4,
			'confirm_token' => str_repeat( '1', 64 ),
			'reject_token'  => str_repeat( '2', 64 ),
			'status'        => 'pending',
			'expires_at'    => gmdate( 'Y-m-d H:i:s', time() + 900 ),
			'ability_name'  => 'g2rd_create-post',
			'arguments_enc' => $arguments_enc,
			'user_id'       => 1,
			'token_id'      => 1,
			'ip_address'    => '127.0.0.1',
		];

		$result = $this->queue->confirm( str_repeat( '1', 64 ) );

		$this->assertFalse( $result );
	}

	// ── Test 6 : reject ───────────────────────────────────────────────────────

	/**
	 * reject() returns false for an unknown token.
	 */
	public function test_reject_returns_false_for_unknown_token(): void {
		$result = $this->queue->reject( str_repeat( '3', 64 ) );

		$this->assertFalse( $result );
	}

	/**
	 * reject() returns true and does NOT call wp_insert_post for a valid pending entry.
	 */
	public function test_reject_returns_true_and_skips_write_op(): void {
		global $g2rd_wpdb_get_row_return, $g2rd_wpdb_update_return, $g2rd_post_store;

		$g2rd_wpdb_get_row_return = [
			'id'            => 5,
			'confirm_token' => str_repeat( '4', 64 ),
			'reject_token'  => str_repeat( '5', 64 ),
			'status'        => 'pending',
			'expires_at'    => gmdate( 'Y-m-d H:i:s', time() + 900 ),
			'ability_name'  => 'g2rd_create-post',
			'arguments_enc' => '',
			'user_id'       => 1,
			'token_id'      => 1,
			'ip_address'    => '127.0.0.1',
		];
		$g2rd_wpdb_update_return = 1;

		$result = $this->queue->reject( str_repeat( '5', 64 ) );

		$this->assertTrue( $result );
		// No post was created.
		$this->assertEmpty( $g2rd_post_store );
	}

	// ── Test 7 : prune_expired ────────────────────────────────────────────────

	/**
	 * prune_expired() returns the number of rows updated (from the wpdb stub).
	 */
	public function test_prune_expired_returns_row_count(): void {
		global $g2rd_wpdb_query_return;
		$g2rd_wpdb_query_return = 3;

		$result = $this->queue->prune_expired();

		$this->assertSame( 3, $result );
	}
}
