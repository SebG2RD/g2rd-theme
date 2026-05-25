<?php
/**
 * Tests — McpAbilities (read-only + write tool registry)
 *
 * Uses in-memory WP stubs from bootstrap.php:
 *   $g2rd_post_store   — controls get_post() results
 *   $g2rd_query_store  — controls WP_Query results
 *
 * @package    G2RD\Tests
 * @since      1.12.0
 */

declare(strict_types=1);

namespace G2RD\Tests;

use G2RD\McpAbilities;
use G2RD\McpConfirmationQueue;
use PHPUnit\Framework\TestCase;
use WP_Post;

/**
 * Verifies all three read-only tools in McpAbilities.
 */
final class McpAbilitiesTest extends TestCase {

	private McpAbilities $abilities;

	/** @var array<string, mixed> Minimal gate result for call() */
	private array $gate;

	protected function setUp(): void {
		global $g2rd_post_store, $g2rd_query_store;
		$g2rd_post_store  = [];
		$g2rd_query_store = [ 'posts' => [], 'found_posts' => 0, 'max_num_pages' => 1 ];

		$this->abilities = new McpAbilities(); // no queue — write tools return "unavailable"
		$this->gate      = [
			'allowed'       => true,
			'user_id'       => 1,
			'token_id'      => 1,
			'scope'         => 'read_only',
			'denial_reason' => '',
		];
	}

	// ── Test 1 : list_tools structure ─────────────────────────────────────────

	/**
	 * list_tools() returns exactly 37 tools (19 read-only + 18 write), each with required keys.
	 */
	public function test_list_tools_returns_five_tools(): void {
		$tools = $this->abilities->list_tools();

		$this->assertCount( 37, $tools );

		$names = array_column( $tools, 'name' );
		$this->assertContains( 'g2rd_get-site-info', $names );
		$this->assertContains( 'g2rd_list-posts', $names );
		$this->assertContains( 'g2rd_get-post', $names );
		$this->assertContains( 'g2rd_create-post', $names );
		$this->assertContains( 'g2rd_update-post', $names );

		foreach ( $tools as $tool ) {
			$this->assertArrayHasKey( 'name', $tool );
			$this->assertArrayHasKey( 'description', $tool );
			$this->assertArrayHasKey( 'inputSchema', $tool );
			// Internal fields must NOT be exposed.
			$this->assertArrayNotHasKey( 'required_scope', $tool );
			$this->assertArrayNotHasKey( 'wp_capability', $tool );
		}
	}

	// ── Test 2 : get() lookup ─────────────────────────────────────────────────

	/**
	 * get() returns the full tool definition including required_scope.
	 */
	public function test_get_returns_tool_definition(): void {
		$tool = $this->abilities->get( 'g2rd_list-posts' );

		$this->assertNotNull( $tool );
		$this->assertSame( 'g2rd_list-posts', $tool['name'] );
		$this->assertSame( 'read_only', $tool['required_scope'] );
		$this->assertSame( 'read', $tool['wp_capability'] );
	}

	/**
	 * get() returns null for an unknown tool name.
	 */
	public function test_get_returns_null_for_unknown_tool(): void {
		$this->assertNull( $this->abilities->get( 'g2rd_nonexistent' ) );
	}

	// ── Test 3 : get-site-info ────────────────────────────────────────────────

	/**
	 * get-site-info returns site metadata in text/JSON content.
	 */
	public function test_get_site_info_returns_metadata(): void {
		$result = $this->abilities->call( 'g2rd_get-site-info', [], $this->gate );

		$this->assertFalse( $result['isError'] );
		$this->assertSame( 'text', $result['content'][0]['type'] );

		$data = json_decode( $result['content'][0]['text'], true );
		$this->assertSame( 'Test Site', $data['name'] );
		$this->assertSame( 'https://example.com', $data['url'] );
		$this->assertSame( 'UTC', $data['timezone'] );
		$this->assertSame( '6.5.0-test', $data['wp_version'] );
	}

	// ── Test 4 : list-posts ───────────────────────────────────────────────────

	/**
	 * list-posts with an unknown post type returns isError = true.
	 */
	public function test_list_posts_unknown_post_type_returns_error(): void {
		$result = $this->abilities->call(
			'g2rd_list-posts',
			[ 'post_type' => 'nonexistent_type' ],
			$this->gate
		);

		$this->assertTrue( $result['isError'] );
		$this->assertStringContainsString( 'not accessible', $result['content'][0]['text'] );
	}

	/**
	 * list-posts with a valid post type returns a properly structured payload.
	 */
	public function test_list_posts_returns_paginated_data(): void {
		global $g2rd_query_store;

		$post         = new WP_Post();
		$post->ID     = 42;
		$post->post_title   = 'Hello World';
		$post->post_name    = 'hello-world';
		$post->post_excerpt = 'An excerpt.';
		$post->post_date_gmt = '2026-01-01 10:00:00';
		$post->post_status  = 'publish';

		$g2rd_query_store = [
			'posts'        => [ $post ],
			'found_posts'  => 1,
			'max_num_pages' => 1,
		];

		$result = $this->abilities->call( 'g2rd_list-posts', [ 'per_page' => 5, 'page' => 1 ], $this->gate );

		$this->assertFalse( $result['isError'] );

		$data = json_decode( $result['content'][0]['text'], true );
		$this->assertSame( 1, $data['total'] );
		$this->assertSame( 1, $data['page'] );
		$this->assertSame( 5, $data['per_page'] );
		$this->assertCount( 1, $data['posts'] );
		$this->assertSame( 42, $data['posts'][0]['id'] );
		$this->assertSame( 'Hello World', $data['posts'][0]['title'] );
	}

	/**
	 * per_page is clamped to 50 maximum.
	 */
	public function test_list_posts_clamps_per_page_to_50(): void {
		global $g2rd_query_store;
		$g2rd_query_store = [ 'posts' => [], 'found_posts' => 0, 'max_num_pages' => 1 ];

		$result = $this->abilities->call( 'g2rd_list-posts', [ 'per_page' => 999 ], $this->gate );

		$this->assertFalse( $result['isError'] );
		$data = json_decode( $result['content'][0]['text'], true );
		$this->assertSame( 50, $data['per_page'] );
	}

	// ── Test 5 : get-post ─────────────────────────────────────────────────────

	/**
	 * get-post without post_id returns isError = true.
	 */
	public function test_get_post_missing_id_returns_error(): void {
		$result = $this->abilities->call( 'g2rd_get-post', [], $this->gate );

		$this->assertTrue( $result['isError'] );
		$this->assertStringContainsString( 'post_id', $result['content'][0]['text'] );
	}

	/**
	 * get-post with a non-existent post ID returns isError = true.
	 */
	public function test_get_post_not_found_returns_error(): void {
		$result = $this->abilities->call( 'g2rd_get-post', [ 'post_id' => 9999 ], $this->gate );

		$this->assertTrue( $result['isError'] );
		$this->assertStringContainsString( '9999', $result['content'][0]['text'] );
	}

	/**
	 * get-post with a draft post returns isError = true (read_only cannot access drafts).
	 */
	public function test_get_post_draft_returns_error(): void {
		global $g2rd_post_store;

		$post              = new WP_Post();
		$post->ID          = 10;
		$post->post_status = 'draft';
		$g2rd_post_store[10] = $post;

		$result = $this->abilities->call( 'g2rd_get-post', [ 'post_id' => 10 ], $this->gate );

		$this->assertTrue( $result['isError'] );
		$this->assertStringContainsString( 'not accessible', $result['content'][0]['text'] );
	}

	/**
	 * get-post with a published post returns the full data payload.
	 */
	public function test_get_post_published_returns_data(): void {
		global $g2rd_post_store;

		$post                  = new WP_Post();
		$post->ID              = 7;
		$post->post_title      = 'My Post';
		$post->post_name       = 'my-post';
		$post->post_status     = 'publish';
		$post->post_content    = '<p>Hello <b>World</b></p>';
		$post->post_excerpt    = 'Short excerpt.';
		$post->post_date_gmt   = '2026-03-01 08:00:00';
		$post->post_modified_gmt = '2026-03-02 09:00:00';
		$post->post_type       = 'post';
		$post->post_author     = 1;
		$g2rd_post_store[7]    = $post;

		$result = $this->abilities->call( 'g2rd_get-post', [ 'post_id' => 7 ], $this->gate );

		$this->assertFalse( $result['isError'] );

		$data = json_decode( $result['content'][0]['text'], true );
		$this->assertSame( 7, $data['id'] );
		$this->assertSame( 'My Post', $data['title'] );
		$this->assertSame( 'publish', $data['status'] );
		// Content should have HTML tags stripped.
		$this->assertStringNotContainsString( '<p>', $data['content_text'] );
		$this->assertStringContainsString( 'Hello', $data['content_text'] );
	}

	// ── Test 6 : call() unknown tool ──────────────────────────────────────────

	/**
	 * call() with an unregistered tool name returns isError = true.
	 */
	public function test_call_unknown_tool_returns_error(): void {
		$result = $this->abilities->call( 'g2rd_delete-everything', [], $this->gate );

		$this->assertTrue( $result['isError'] );
		$this->assertStringContainsString( 'Unknown tool', $result['content'][0]['text'] );
	}

	// ── Test 7 : write tools — scope + queue ─────────────────────────────────

	/**
	 * get() returns the write tool definitions with correct required_scope.
	 */
	public function test_write_tools_have_editor_scope(): void {
		$create = $this->abilities->get( 'g2rd_create-post' );
		$update = $this->abilities->get( 'g2rd_update-post' );

		$this->assertNotNull( $create );
		$this->assertSame( 'editor', $create['required_scope'] );
		$this->assertSame( 'edit_posts', $create['wp_capability'] );

		$this->assertNotNull( $update );
		$this->assertSame( 'editor', $update['required_scope'] );
		$this->assertSame( 'edit_posts', $update['wp_capability'] );
	}

	/**
	 * create-post without editor scope returns isError = true.
	 */
	public function test_create_post_wrong_scope_returns_error(): void {
		$gate   = array_merge( $this->gate, [ 'scope' => 'read_only' ] );
		$result = $this->abilities->call( 'g2rd_create-post', [ 'title' => 'Test' ], $gate );

		$this->assertTrue( $result['isError'] );
		$this->assertStringContainsString( 'editor scope', $result['content'][0]['text'] );
	}

	/**
	 * create-post with editor scope but no queue returns "unavailable" error.
	 */
	public function test_create_post_without_queue_returns_unavailable_error(): void {
		$gate   = array_merge( $this->gate, [ 'scope' => 'editor', 'client_ip' => '127.0.0.1' ] );
		// $this->abilities has no queue (constructed without one in setUp).
		$result = $this->abilities->call( 'g2rd_create-post', [ 'title' => 'Test' ], $gate );

		$this->assertTrue( $result['isError'] );
		$this->assertStringContainsString( 'unavailable', $result['content'][0]['text'] );
	}

	/**
	 * create-post with editor scope and a mock queue returns a pending acknowledgement.
	 */
	public function test_create_post_with_queue_returns_pending(): void {
		$queue_mock = $this->getMockBuilder( McpConfirmationQueue::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'enqueue' ] )
			->getMock();

		$queue_mock->method( 'enqueue' )->willReturn( [
			'confirm_token' => str_repeat( 'a', 64 ),
			'reject_token'  => str_repeat( 'b', 64 ),
			'expires_at'    => gmdate( 'Y-m-d H:i:s', time() + 900 ),
		] );

		$abilities_with_queue = new McpAbilities( $queue_mock );
		$gate = array_merge( $this->gate, [ 'scope' => 'editor', 'client_ip' => '127.0.0.1' ] );

		$result = $abilities_with_queue->call( 'g2rd_create-post', [ 'title' => 'New Post' ], $gate );

		$this->assertFalse( $result['isError'] );

		$data = json_decode( $result['content'][0]['text'], true );
		$this->assertSame( 'pending', $data['status'] );
		$this->assertArrayHasKey( 'expires_at', $data );
		$this->assertArrayHasKey( 'message', $data );
	}

	/**
	 * update-post with editor scope and a mock queue returns a pending acknowledgement.
	 */
	public function test_update_post_with_queue_returns_pending(): void {
		$queue_mock = $this->getMockBuilder( McpConfirmationQueue::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'enqueue' ] )
			->getMock();

		$queue_mock->method( 'enqueue' )->willReturn( [
			'confirm_token' => str_repeat( 'c', 64 ),
			'reject_token'  => str_repeat( 'd', 64 ),
			'expires_at'    => gmdate( 'Y-m-d H:i:s', time() + 900 ),
		] );

		$abilities_with_queue = new McpAbilities( $queue_mock );
		$gate = array_merge( $this->gate, [ 'scope' => 'editor', 'client_ip' => '127.0.0.1' ] );

		$result = $abilities_with_queue->call( 'g2rd_update-post', [ 'post_id' => 1, 'title' => 'Updated' ], $gate );

		$this->assertFalse( $result['isError'] );

		$data = json_decode( $result['content'][0]['text'], true );
		$this->assertSame( 'pending', $data['status'] );
	}

	/**
	 * create-post returns error when the queue enqueue() fails.
	 */
	public function test_create_post_queue_failure_returns_error(): void {
		$queue_mock = $this->getMockBuilder( McpConfirmationQueue::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'enqueue' ] )
			->getMock();

		$queue_mock->method( 'enqueue' )->willReturn( false );

		$abilities_with_queue = new McpAbilities( $queue_mock );
		$gate = array_merge( $this->gate, [ 'scope' => 'editor', 'client_ip' => '127.0.0.1' ] );

		$result = $abilities_with_queue->call( 'g2rd_create-post', [ 'title' => 'Fail' ], $gate );

		$this->assertTrue( $result['isError'] );
		$this->assertStringContainsString( 'Failed to enqueue', $result['content'][0]['text'] );
	}
}
